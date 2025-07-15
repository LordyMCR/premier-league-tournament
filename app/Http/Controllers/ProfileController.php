<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Models\Team;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display a user's public profile
     */
    public function show(Request $request, User $user): Response
    {
        // Check if profile is viewable
        if (!$user->isProfileViewableBy($request->user())) {
            abort(403, 'This profile is private.');
        }

        // Increment profile view count
        $user->incrementProfileViews($request->user());

        // Load necessary relationships
        $user->load([
            'favoriteTeam',
            'statistics',
            'profileSettings',
            'achievements' => function ($query) {
                $query->wherePivot('is_featured', true)->orderBy('earned_at', 'desc');
            },
            'tournaments' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        // Get user's tournament statistics
        $recentTournaments = $user->tournaments()
            ->with(['participants' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($tournament) {
                return [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'points' => $tournament->participants->first()?->total_points ?? 0,
                    'created_at' => $tournament->created_at,
                ];
            });

        return Inertia::render('Profile/Show', [
            'profileUser' => $user,
            'recentTournaments' => $recentTournaments,
            'isOwnProfile' => $request->user()?->id === $user->id,
            'canEdit' => $request->user()?->id === $user->id,
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['favoriteTeam', 'statistics', 'profileSettings']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $user,
            'teams' => Team::select('id', 'name', 'short_name', 'primary_color')->orderBy('name')->get(),
            'statistics' => $user->getOrCreateStatistics(),
            'profileSettings' => $user->getOrCreateProfileSettings(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's extended profile information
     */
    public function updateExtended(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'favorite_team_id' => ['nullable', 'exists:teams,id'],
            'supporter_since' => ['nullable', 'integer', 'min:1888', 'max:' . date('Y')],
            'twitter_handle' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_]{1,50}$/'],
            'instagram_handle' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.]{1,50}$/'],
            'display_name' => ['nullable', 'string', 'max:50'],
            'show_real_name' => ['boolean'],
            'show_email' => ['boolean'],
            'show_location' => ['boolean'],
            'show_age' => ['boolean'],
            'profile_public' => ['boolean'],
        ]);

        $user->update($validated);
        $user->updateLastActive();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update user's profile privacy settings
     */
    public function updatePrivacy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $settings = $user->getOrCreateProfileSettings();
        
        $validated = $request->validate([
            'profile_visible' => ['boolean'],
            'show_real_name' => ['boolean'],
            'show_email' => ['boolean'],
            'show_location' => ['boolean'],
            'show_age' => ['boolean'],
            'show_favorite_team' => ['boolean'],
            'show_supporter_since' => ['boolean'],
            'show_social_links' => ['boolean'],
            'show_tournament_history' => ['boolean'],
            'show_statistics' => ['boolean'],
            'show_achievements' => ['boolean'],
            'show_current_tournaments' => ['boolean'],
            'show_pick_history' => ['boolean'],
            'show_team_preferences' => ['boolean'],
            'show_last_active' => ['boolean'],
            'show_join_date' => ['boolean'],
            'allow_profile_views' => ['boolean'],
            'count_profile_views' => ['boolean'],
            'show_profile_view_count' => ['boolean'],
            'searchable_by_email' => ['boolean'],
            'searchable_by_name' => ['boolean'],
            'allow_tournament_invites' => ['boolean'],
            'public_leaderboard_participation' => ['boolean'],
        ]);

        $settings->update($validated);

        return Redirect::route('profile.edit')->with('status', 'privacy-updated');
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        // Store new avatar
        $filename = $user->id . '_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
        $path = $request->file('avatar')->storeAs('avatars', $filename, 'public');

        $user->update(['avatar' => $filename]);
        $user->updateLastActive();

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Upload cropped avatar from blob data
     */
    public function uploadCroppedAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        // Store new avatar
        $filename = $user->id . '_' . time() . '.png';
        $path = $request->file('avatar')->storeAs('avatars', $filename, 'public');

        $user->update(['avatar' => $filename]);
        $user->updateLastActive();

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Remove user avatar
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        $user->update(['avatar' => null]);

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Delete avatar if exists
        if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
            Storage::disk('public')->delete('avatars/' . $user->avatar);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
