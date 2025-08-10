<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewUserRegistration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'restrictionsEnabled' => config('app.restrictions_enabled'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $restrictionsEnabled = config('app.restrictions_enabled');
        
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        // If restrictions are enabled, user needs approval
        if ($restrictionsEnabled) {
            $userData['is_approved'] = false;
            $userData['approval_token'] = Str::random(32);
        }

        $user = User::create($userData);

        event(new Registered($user));

        // Send admin notification if restrictions are enabled and user needs approval
        if ($restrictionsEnabled && !$user->is_approved) {
            try {
                $adminEmail = config('app.admin_email', 'daniel.lord18@gmail.com');
                \Log::info('Sending admin notification to: ' . $adminEmail);
                Notification::route('mail', $adminEmail)
                    ->notify(new NewUserRegistration($user));
                \Log::info('Admin notification sent successfully');
            } catch (\Exception $e) {
                \Log::error('Failed to send admin notification: ' . $e->getMessage());
            }
        }

        // Only auto-login if not restricted or if approved
        if (!$restrictionsEnabled || $user->is_approved) {
            Auth::login($user);
            return redirect(route('welcome', absolute: false));
        }

        // If restricted and awaiting approval, redirect with message
        return redirect(route('login'))->with('status', 'Your account has been created and is awaiting approval. You will receive an email once approved.');
    }
}
