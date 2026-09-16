<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(private Turnstile $turnstile) {}

    public function create(Request $request): Response
    {
        if (AppSettings::isInviteOnly()) {
            return Inertia::render('Auth/Register', [
                'mode' => 'invite_closed',
            ]);
        }

        $request->session()->put('register_form_started_at', now()->timestamp);

        return Inertia::render('Auth/Register', [
            'mode' => 'public',
            'restrictionsEnabled' => (bool) config('app.restrictions_enabled'),
            'turnstileSiteKey' => $this->turnstile->siteKey(),
        ]);
    }

    public function createInvite(Request $request, string $token): Response
    {
        $invite = Invite::query()->usable()->where('token', $token)->first();

        if (! $invite) {
            return Inertia::render('Auth/Register', [
                'mode' => 'invite_invalid',
            ]);
        }

        $request->session()->put('register_form_started_at', now()->timestamp);

        return Inertia::render('Auth/Register', [
            'mode' => 'invite',
            'inviteToken' => $invite->token,
            'inviteEmail' => $invite->email,
            'restrictionsEnabled' => false,
            'turnstileSiteKey' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $invite = $this->resolveInvite($request);

        if (! $invite && AppSettings::isInviteOnly()) {
            return redirect()
                ->route('register')
                ->with('error', 'Registration is by invite only. Ask an admin for a unique invite link.');
        }

        if ($request->filled('website')) {
            return $this->fakeSuccess($invite !== null);
        }

        if (! $invite && $this->submittedTooQuickly($request)) {
            return $this->fakeSuccess(false);
        }

        if (! $invite && ! $this->turnstile->verify($request->input('turnstile_token'), $request->ip())) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'Please confirm you are human and try again.',
            ]);
        }

        $request->validate($this->rules($invite), [
            'name.regex' => 'Please enter your full name (first and last name).',
        ]);

        if ($invite?->email && strcasecmp($invite->email, $request->email) !== 0) {
            throw ValidationException::withMessages([
                'email' => 'This invite is for a different email address.',
            ]);
        }

        $user = DB::transaction(function () use ($request, $invite) {
            if ($invite) {
                $invite = Invite::query()->whereKey($invite->id)->lockForUpdate()->first();
                if (! $invite?->isUsable()) {
                    throw ValidationException::withMessages([
                        'invite_token' => 'This invite link is no longer valid.',
                    ]);
                }
            }

            $restrictionsEnabled = (bool) config('app.restrictions_enabled');
            $autoApprove = $invite !== null || ! $restrictionsEnabled;

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'invite_id' => $invite?->id,
                'is_approved' => $autoApprove,
                'approved_at' => $autoApprove ? now() : null,
                'approval_token' => $autoApprove ? null : Str::random(32),
            ]);

            if ($invite) {
                $invite->update([
                    'used_at' => now(),
                    'used_by' => $user->id,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        if ($invite || $user->is_approved) {
            Auth::login($user);

            return redirect(route('welcome', absolute: false));
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been created and is awaiting approval. You will receive an email once approved.');
    }

    private function resolveInvite(Request $request): ?Invite
    {
        $token = $request->input('invite_token');
        if (blank($token)) {
            return null;
        }

        return Invite::query()->usable()->where('token', $token)->first();
    }

    private function submittedTooQuickly(Request $request): bool
    {
        $started = $request->session()->get('register_form_started_at');

        return ! is_numeric($started) || (now()->timestamp - (int) $started) < 2;
    }

    private function fakeSuccess(bool $fromInvite): RedirectResponse
    {
        if ($fromInvite) {
            return redirect()->route('login')->with('status', 'Your account has been created. You can sign in now.');
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been created and is awaiting approval. You will receive an email once approved.');
    }

    private function rules(?Invite $invite): array
    {
        $nameRules = $invite
            ? ['required', 'string', 'max:80']
            : ['required', 'string', 'max:80', 'regex:/^[\p{L}]+(?:[ \'\-][\p{L}]+)+$/u'];

        return [
            'name' => $nameRules,
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'invite_token' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],
            'turnstile_token' => ['nullable', 'string'],
        ];
    }
}
