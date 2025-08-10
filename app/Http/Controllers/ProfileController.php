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
use Illuminate\Support\Facades\Log;
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
        try {
            // Support explicit public preview (treat viewer as public when previewing)
            $previewPublic = (bool) $request->boolean('preview_public');
            $viewerForGate = $previewPublic ? null : $request->user();

            // If not previewing and the viewer cannot see the profile, block
            if (!$previewPublic && !$user->isProfileViewableBy($viewerForGate)) {
                abort(403, 'This profile is private.');
            }

            // Load necessary relationships with null checks
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

            // Ensure profile settings instance exists for frontend consumption with safe defaults
            $defaults = [
                'profile_visible' => true,
                'show_real_name' => false,
                'show_email' => false,
                'show_location' => false,
                'show_age' => false,
                'show_bio' => true,
                'show_favorite_team' => true,
                'show_supporter_since' => true,
                'show_social_links' => true,
                'show_tournament_history' => true,
                'show_statistics' => true,
                'show_achievements' => true,
                'show_current_tournaments' => true,
                'show_pick_history' => true,
                'show_team_preferences' => true,
                'show_last_active' => true,
                'show_join_date' => true,
            ];
            // Persist settings if missing, then safely merge defaults for serialization
            $settingsModel = $user->getOrCreateProfileSettings();
            $user->setRelation('profileSettings', $settingsModel->fill(array_merge($defaults, $settingsModel->toArray())));

            // Get current active tournaments for the user
            $currentTournaments = collect();
            try {
                $currentTournaments = $user->tournaments()
                    ->where('status', 'active')
                    ->with(['participants' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }])
                    ->get()
                    ->map(function ($tournament) {
                        return [
                            'id' => $tournament->id,
                            'name' => $tournament->name,
                            'status' => $tournament->status,
                            'current_points' => $tournament->participants->first()?->total_points ?? 0,
                            'current_gameweek' => $tournament->current_gameweek ?? 1,
                            'created_at' => $tournament->created_at,
                        ];
                    });
            } catch (\Exception $e) {
                $currentTournaments = collect();
            }

            // Get team preferences (could be from picks or favorites)
            $teamPreferences = collect();
            try {
                // Get most picked teams by the user
                $teamPreferences = Team::whereHas('picks', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->withCount(['picks' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }])
                ->orderBy('picks_count', 'desc')
                ->limit(6)
                ->get();
            } catch (\Exception $e) {
                $teamPreferences = collect();
            }

            // Get user's tournament statistics safely
            $recentTournaments = collect();
            try {
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
            } catch (\Exception $e) {
                // If tournaments fail to load, just use empty collection
                $recentTournaments = collect();
            }

            // Attach additional data to user object
            $user->current_tournaments = $currentTournaments;
            $user->team_preferences = $teamPreferences;

            return Inertia::render('Profile/Show', [
                'profileUser' => $user,
                'recentTournaments' => $recentTournaments,
                'isOwnProfile' => $request->user()?->id === $user->id,
                'canEdit' => $request->user()?->id === $user->id,
                'previewPublic' => $previewPublic,
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Profile show error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'viewer_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return a basic profile view
            return Inertia::render('Profile/Show', [
                'profileUser' => $user,
                'recentTournaments' => collect(),
                'isOwnProfile' => $request->user()?->id === $user->id,
                'canEdit' => $request->user()?->id === $user->id,
                'previewPublic' => false,
            ]);
        }
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
        
        // Keep UserProfileSetting in sync for overlapping privacy flags
        $settings = $user->getOrCreateProfileSettings();
        $settingsPayload = [];
        foreach (['show_real_name','show_email','show_location','show_age'] as $flag) {
            if (array_key_exists($flag, $validated)) {
                $settingsPayload[$flag] = (bool) $validated[$flag];
            }
        }
        if (array_key_exists('profile_public', $validated)) {
            $settingsPayload['profile_visible'] = (bool) $validated['profile_public'];
        }
        if (!empty($settingsPayload)) {
            $settings->update($settingsPayload);
        }
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
            'show_bio' => ['boolean'],
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

        // Use configured default disk (public locally, s3 in production if set)
        $disk = config('filesystems.default', 'public');

        try {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk($disk)->exists('avatars/' . $user->avatar)) {
                Storage::disk($disk)->delete('avatars/' . $user->avatar);
            }

            // Store new avatar (make public so it can be served without signed URLs)
            $filename = $user->id . '_' . time() . '.' . $request->file('avatar')->getClientOriginalExtension();
            Storage::disk($disk)->putFileAs(
                'avatars',
                $request->file('avatar'),
                $filename,
                ['visibility' => 'public']
            );

            $user->update(['avatar' => $filename]);
            $user->updateLastActive();
        } catch (\Throwable $e) {
            Log::error('Avatar upload error', [
                'disk' => $disk,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['avatar' => 'Failed to upload avatar. Please try again.']);
        }

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

        $disk = config('filesystems.default', 'public');
        try {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk($disk)->exists('avatars/' . $user->avatar)) {
                Storage::disk($disk)->delete('avatars/' . $user->avatar);
            }

            // Store new avatar
            $filename = $user->id . '_' . time() . '.png';
            Storage::disk($disk)->putFileAs(
                'avatars',
                $request->file('avatar'),
                $filename,
                ['visibility' => 'public']
            );

            $user->update(['avatar' => $filename]);
            $user->updateLastActive();
        } catch (\Throwable $e) {
            Log::error('Cropped avatar upload error', [
                'disk' => $disk,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['avatar' => 'Failed to upload avatar. Please try again.']);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Remove user avatar
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $disk = config('filesystems.default', 'public');
        if ($user->avatar && Storage::disk($disk)->exists('avatars/' . $user->avatar)) {
            Storage::disk($disk)->delete('avatars/' . $user->avatar);
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
