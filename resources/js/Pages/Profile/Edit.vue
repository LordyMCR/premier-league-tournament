<script setup>
import { ref, computed } from 'vue'
import TournamentLayout from '@/Layouts/TournamentLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import AvatarCropper from '@/Components/AvatarCropper.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import Checkbox from '@/Components/Checkbox.vue'

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    user: Object,
    teams: Array,
    statistics: Object,
    profileSettings: Object,
})

// Tab management
const activeTab = ref('profile')

// Extended profile form
const extendedForm = useForm({
    bio: props.user.bio || '',
    location: props.user.location || '',
    date_of_birth: props.user.date_of_birth ? new Date(props.user.date_of_birth).toISOString().split('T')[0] : '',
    favorite_team_id: props.user.favorite_team_id || '',
    supporter_since: String(props.user.supporter_since || ''),
    twitter_handle: props.user.twitter_handle || '',
    instagram_handle: props.user.instagram_handle || '',
    display_name: props.user.display_name || '',
    // Initialize visibility booleans from profileSettings when available to avoid drift
    show_real_name: props.profileSettings?.show_real_name ?? (props.user.show_real_name ?? true),
    show_email: props.profileSettings?.show_email ?? (props.user.show_email ?? false),
    show_location: props.profileSettings?.show_location ?? (props.user.show_location ?? true),
    show_age: props.profileSettings?.show_age ?? (props.user.show_age ?? false),
    profile_public: props.profileSettings?.profile_visible ?? (props.user.profile_public ?? true),
})

// Privacy settings form
const privacyForm = useForm({
    profile_visible: props.profileSettings?.profile_visible ?? true,
    show_real_name: props.profileSettings?.show_real_name ?? true,
    show_email: props.profileSettings?.show_email ?? false,
    show_location: props.profileSettings?.show_location ?? true,
    show_age: props.profileSettings?.show_age ?? false,
    show_bio: props.profileSettings?.show_bio ?? true,
    show_favorite_team: props.profileSettings?.show_favorite_team ?? true,
    show_supporter_since: props.profileSettings?.show_supporter_since ?? true,
    show_social_links: props.profileSettings?.show_social_links ?? true,
    show_tournament_history: props.profileSettings?.show_tournament_history ?? true,
    show_statistics: props.profileSettings?.show_statistics ?? true,
    show_achievements: props.profileSettings?.show_achievements ?? true,
    show_current_tournaments: props.profileSettings?.show_current_tournaments ?? true,
    show_pick_history: props.profileSettings?.show_pick_history ?? false,
    show_team_preferences: props.profileSettings?.show_team_preferences ?? true,
    show_last_active: props.profileSettings?.show_last_active ?? true,
    show_join_date: props.profileSettings?.show_join_date ?? true,
    searchable_by_email: props.profileSettings?.searchable_by_email ?? false,
    searchable_by_name: props.profileSettings?.searchable_by_name ?? true,
    allow_tournament_invites: props.profileSettings?.allow_tournament_invites ?? true,
    public_leaderboard_participation: props.profileSettings?.public_leaderboard_participation ?? true,
})

// Avatar upload and cropping
const avatarForm = useForm({
    avatar: null,
})

const avatarPreview = ref(null)
const showCropper = ref(false)
const selectedImageFile = ref(null)

// Submit handler
const submitExtendedProfile = () => {
    // Convert supporter_since back to number before submitting
    const formData = { ...extendedForm.data() }
    if (formData.supporter_since) {
        formData.supporter_since = parseInt(formData.supporter_since)
    }
    extendedForm.clearErrors()
    extendedForm.patch(route('profile.update.extended'), formData)
}

const submitPrivacySettings = () => {
    privacyForm.patch(route('profile.update.privacy'))
}

// removed quick privacy controls (now centralized under Privacy tab only)

const handleAvatarChange = (event) => {
    const file = event.target.files[0]
    
    if (file) {
        // Store the file and show cropper
        selectedImageFile.value = file
        showCropper.value = true
        
        // Reset the file input
        event.target.value = ''
    }
}

const handleCropComplete = (croppedBlob) => {
    // Convert blob to file
    const croppedFile = new File([croppedBlob], 'avatar.png', { type: 'image/png' })
    avatarForm.avatar = croppedFile
    
    // Create preview URL
    const reader = new FileReader()
    reader.onload = (e) => {
        avatarPreview.value = e.target.result
    }
    reader.readAsDataURL(croppedFile)
    
    // Immediately upload the cropped avatar
    avatarForm.post(route('profile.avatar.upload'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset state and close cropper after successful upload
            avatarForm.reset()
            showCropper.value = false
            selectedImageFile.value = null
        },
        onError: () => {
            // Keep cropper closed but retain preview so user can retry
            showCropper.value = false
        }
    })
}

const handleCropCancel = () => {
    showCropper.value = false
    selectedImageFile.value = null
}

const uploadAvatar = () => {
    avatarForm.post(route('profile.avatar.upload'), {
        onSuccess: () => {
            avatarForm.reset()
            avatarPreview.value = null
        }
    })
}

const removeAvatar = () => {
    if (confirm('Are you sure you want to remove your avatar?')) {
        useForm({}).delete(route('profile.avatar.remove'))
    }
}

const winRate = computed(() => {
    if (!props.statistics || props.statistics.total_tournaments === 0) {
        return 0
    }
    return Math.round((props.statistics.tournaments_won / props.statistics.total_tournaments) * 100)
})

const handleImageError = (event) => {
    // Fallback to default avatar URL generator
    event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(props.user.name)}&background=10B981&color=fff&size=150`
}

const handleImageLoad = (event) => {
    // Image loaded successfully, no need to do anything
}

// Status banner mapping from server-provided status prop
const statusMessage = computed(() => {
    switch (props.status) {
        case 'profile-updated':
            return 'Your profile has been saved.'
        case 'privacy-updated':
            return 'Your privacy settings have been saved.'
        case 'avatar-updated':
            return 'Your avatar has been updated.'
        case 'avatar-removed':
            return 'Your avatar has been removed.'
        default:
            return ''
    }
})
</script>

<template>
    <Head title="Profile Settings" />

    <TournamentLayout>
        <div class="min-h-screen py-4 sm:py-8">
            <div class="max-w-6xl mx-auto px-3 sm:px-4 lg:px-8">
                <!-- Header with Quick Actions -->
                <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md mb-6 sm:mb-8">
                    <div class="flex flex-col space-y-4 sm:flex-row sm:justify-between sm:items-start sm:space-y-0">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Profile Settings</h1>
                            <p class="text-gray-600 mt-2 text-sm sm:text-base">
                                Manage your account, privacy settings, and tournament preferences
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <Link :href="route('profile.show', { user: user.id, preview_public: 1 })" 
                                  class="px-3 py-2 sm:px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors text-sm sm:text-base">
                                <i class="fas fa-eye mr-1 sm:mr-2"></i>
                                <span class="hidden sm:inline">View Public</span>
                                <span class="sm:hidden">View</span>
                                Profile
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="bg-white rounded-xl p-2 border border-green-200 shadow-md mb-6 sm:mb-8">
                    <!-- Success Banner -->
                    <div v-if="statusMessage" class="mb-2">
                        <div class="flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-3 py-2 text-green-800 text-sm">
                            <i class="fas fa-check-circle"></i>
                            <span>{{ statusMessage }}</span>
                        </div>
                    </div>
                    <nav class="flex overflow-x-auto no-scrollbar">
                        <button @click="activeTab = 'profile'"
                                :class="activeTab === 'profile' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
                                class="flex-shrink-0 px-3 py-2 sm:px-4 rounded-lg font-medium transition-all text-sm sm:text-base whitespace-nowrap">
                            <i class="fas fa-user mr-1 sm:mr-2"></i>
                            <span class="hidden xs:inline">Profile</span>
                            <span class="xs:hidden">Info</span>
                        </button>
                        <button @click="activeTab = 'avatar'" 
                                :class="activeTab === 'avatar' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
                                class="flex-shrink-0 px-3 py-2 sm:px-4 rounded-lg font-medium transition-all text-sm sm:text-base whitespace-nowrap ml-1 sm:ml-2">
                            <i class="fas fa-image mr-1 sm:mr-2"></i>
                            Avatar
                        </button>
                        <button @click="activeTab = 'privacy'" 
                                :class="activeTab === 'privacy' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
                                class="flex-shrink-0 px-3 py-2 sm:px-4 rounded-lg font-medium transition-all text-sm sm:text-base whitespace-nowrap ml-1 sm:ml-2">
                            <i class="fas fa-shield-alt mr-1 sm:mr-2"></i>
                            Privacy
                        </button>
                        <button @click="activeTab = 'statistics'" 
                                :class="activeTab === 'statistics' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
                                class="flex-shrink-0 px-3 py-2 sm:px-4 rounded-lg font-medium transition-all text-sm sm:text-base whitespace-nowrap ml-1 sm:ml-2">
                            <i class="fas fa-chart-bar mr-1 sm:mr-2"></i>
                            <span class="hidden xs:inline">Statistics</span>
                            <span class="xs:hidden">Stats</span>
                        </button>
                        <button @click="activeTab = 'account'" 
                                :class="activeTab === 'account' ? 'bg-green-600 text-white' : 'text-gray-600 hover:bg-green-50'"
                                class="flex-shrink-0 px-3 py-2 sm:px-4 rounded-lg font-medium transition-all text-sm sm:text-base whitespace-nowrap ml-1 sm:ml-2">
                            <i class="fas fa-cog mr-1 sm:mr-2"></i>
                            Account
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="space-y-6 sm:space-y-8">
                    <!-- Profile Info Tab -->
                    <div v-show="activeTab === 'profile'" class="space-y-4 sm:space-y-6">
                        <!-- Basic Information (existing component) -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Basic Information</h2>
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                            />
                        </div>

                        <!-- Extended Profile Information -->
                        <div class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4">Extended Profile</h2>
                            <form @submit.prevent="submitExtendedProfile" class="space-y-4">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <!-- Display Name -->
                                    <div>
                                        <InputLabel for="display_name" value="Display Name" />
                                        <TextInput
                                            id="display_name"
                                            v-model="extendedForm.display_name"
                                            type="text"
                                            placeholder="How you want to be shown to others"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="extendedForm.errors.display_name" class="mt-2" />
                                    </div>

                                    <!-- Location -->
                                    <div>
                                        <InputLabel for="location" value="Location" />
                                        <TextInput
                                            id="location"
                                            v-model="extendedForm.location"
                                            type="text"
                                            placeholder="City, Country"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="extendedForm.errors.location" class="mt-2" />
                                    </div>

                                    <!-- Date of Birth -->
                                    <div>
                                        <InputLabel for="date_of_birth" value="Date of Birth" />
                                        <TextInput
                                            id="date_of_birth"
                                            v-model="extendedForm.date_of_birth"
                                            type="date"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="extendedForm.errors.date_of_birth" class="mt-2" />
                                    </div>

                                    <!-- Favorite Team -->
                                    <div>
                                        <InputLabel for="favorite_team_id" value="Favorite Team" />
                                        <select
                                            id="favorite_team_id"
                                            v-model="extendedForm.favorite_team_id"
                                            class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-3 py-2 text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        >
                                            <option value="">Select your favorite team</option>
                                            <option v-for="team in teams" :key="team.id" :value="team.id">
                                                {{ team.name }}
                                            </option>
                                        </select>
                                        <InputError :message="extendedForm.errors.favorite_team_id" class="mt-2" />
                                    </div>

                                    <!-- Supporter Since -->
                                    <div>
                                        <InputLabel for="supporter_since" value="Supporting Since (Year)" />
                                        <TextInput
                                            id="supporter_since"
                                            v-model="extendedForm.supporter_since"
                                            type="number"
                                            :min="1888"
                                            :max="new Date().getFullYear()"
                                            placeholder="e.g. 2010"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="extendedForm.errors.supporter_since" class="mt-2" />
                                    </div>

                                    <!-- Twitter Handle -->
                                    <div>
                                        <InputLabel for="twitter_handle" value="Twitter Handle" />
                                        <div class="mt-1 flex rounded-lg shadow-sm border border-gray-300 focus-within:ring-2 focus-within:ring-green-500 focus-within:border-green-500">
                                            <span class="inline-flex items-center px-2 sm:px-3 bg-gray-100 text-gray-900 text-sm rounded-l-lg border-r border-gray-300">
                                                @
                                            </span>
                                            <input
                                                id="twitter_handle"
                                                v-model="extendedForm.twitter_handle"
                                                type="text"
                                                placeholder="username"
                                                class="flex-1 bg-white text-gray-900 placeholder-gray-600 text-sm rounded-r-lg border-0 focus:ring-0 focus:outline-none px-3 py-2"
                                            />
                                        </div>
                                        <InputError :message="extendedForm.errors.twitter_handle" class="mt-2" />
                                    </div>

                                    <!-- Instagram Handle -->
                                    <div>
                                        <InputLabel for="instagram_handle" value="Instagram Handle" />
                                        <div class="mt-1 flex rounded-lg shadow-sm border border-gray-300 focus-within:ring-2 focus-within:ring-green-500 focus-within:border-green-500">
                                            <span class="inline-flex items-center px-2 sm:px-3 bg-gray-100 text-gray-900 text-sm rounded-l-lg border-r border-gray-300">
                                                @
                                            </span>
                                            <input
                                                id="instagram_handle"
                                                v-model="extendedForm.instagram_handle"
                                                type="text"
                                                placeholder="username"
                                                class="flex-1 bg-white text-gray-900 placeholder-gray-600 text-sm rounded-r-lg border-0 focus:ring-0 focus:outline-none px-3 py-2"
                                            />
                                        </div>
                                        <InputError :message="extendedForm.errors.instagram_handle" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div>
                                    <InputLabel for="bio" value="Bio" />
                                    <textarea
                                        id="bio"
                                        v-model="extendedForm.bio"
                                        rows="4"
                                        placeholder="Tell others about yourself..."
                                        class="mt-1 block w-full bg-white border border-gray-300 text-gray-900 rounded-lg px-3 py-2 placeholder-gray-600 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                                    ></textarea>
                                    <InputError :message="extendedForm.errors.bio" class="mt-2" />
                                </div>

                                

                                <div class="flex justify-end pt-4">
                                    <PrimaryButton :class="{ 'opacity-25': extendedForm.processing }" :disabled="extendedForm.processing">
                                        <i class="fas fa-save mr-2"></i>
                                        Save Profile
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Avatar Tab -->
                    <div v-show="activeTab === 'avatar'" class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6">Profile Avatar</h2>
                        
                        <div class="flex flex-col space-y-6 sm:space-y-8 lg:flex-row lg:space-y-0 lg:space-x-8">
                            <!-- Current Avatar -->
                            <div class="text-center flex-shrink-0">
                                <div class="relative inline-block">
                                    <img 
                                        :src="avatarPreview || user.avatar_url" 
                                        :alt="user.name"
                                        class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-emerald-400/50 shadow-2xl"
                                        @error="handleImageError"
                                        @load="handleImageLoad"
                                    >
                                    <div v-if="user.favorite_team" 
                                         class="absolute -bottom-1 -right-1 sm:-bottom-2 sm:-right-2 w-8 h-8 sm:w-12 sm:h-12 rounded-full border-2 border-white shadow-lg overflow-hidden"
                                         :style="{ backgroundColor: user.favorite_team.primary_color || '#10B981' }">
                                        <img :src="user.favorite_team.logo_url"
                                             :alt="user.favorite_team.name"
                                             class="w-full h-full object-cover" />
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Form -->
                            <div class="flex-1 min-w-0">
                                <form @submit.prevent="uploadAvatar" class="space-y-4">
                                    <div>
                                        <InputLabel for="avatar" value="Upload New Avatar" />
                                        <input
                                            id="avatar"
                                            type="file"
                                            accept="image/*"
                                            @change="handleAvatarChange"
                                            class="mt-1 block w-full text-white text-sm file:mr-2 sm:file:mr-4 file:py-2 file:px-3 sm:file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-600 file:text-white hover:file:bg-emerald-700"
                                        />
                                        <InputError :message="avatarForm.errors.avatar" class="mt-2" />
                                        <p class="text-gray-400 text-xs sm:text-sm mt-1">
                                            Maximum file size: 2MB. Accepted formats: JPG, PNG, GIF
                                        </p>
                                    </div>

                                    <div class="flex flex-col space-y-3 sm:flex-row sm:space-y-0 sm:space-x-3">
                                        <SecondaryButton 
                                            v-if="user.avatar"
                                            @click="removeAvatar"
                                            type="button"
                                            class="text-sm"
                                        >
                                            <i class="fas fa-trash mr-2"></i>
                                            Remove Avatar
                                        </SecondaryButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Privacy Tab -->
                    <div v-show="activeTab === 'privacy'" class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6">Privacy Settings</h2>
                        
                        <form @submit.prevent="submitPrivacySettings" class="space-y-6">
                            <!-- Privacy Mode Note -->
                            <div class="rounded-lg bg-gray-50 border border-gray-200 px-3 py-2 text-gray-700 text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-info-circle"></i>
                                    <span>
                                        When profile visibility is disabled, your profile is hidden from others. You can still adjust granular settings below; they take effect once you make your profile visible again.
                                    </span>
                                </div>
                            </div>
                            <!-- Profile Visibility -->
                            <div class="space-y-3">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">Profile Visibility</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <label class="flex items-center space-x-2 text-sm sm:text-base">
                                        <Checkbox v-model:checked="privacyForm.profile_visible" />
                                        <span class="text-gray-700">Profile visible to other users</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-3">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">Personal Information</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <label class="flex items-center space-x-2 text-sm sm:text-base opacity-100" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_real_name" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show real name</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_email" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show email address</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_location" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show location</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_age" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show age</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_bio" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show bio/about section</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_favorite_team" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show favorite team</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_supporter_since" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show supporter since year</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_social_links" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show social media links</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_last_active" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show last active time</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_join_date" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show join date</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Tournament Data -->
                            <div class="space-y-3">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">Tournament Data</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_tournament_history" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show tournament history</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_statistics" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show statistics</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_achievements" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show achievements</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_current_tournaments" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show current tournaments</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_pick_history" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show pick history</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.show_team_preferences" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Show team preferences</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Search and Social -->
                            <div class="space-y-3">
                                <h3 class="text-base sm:text-lg font-medium text-gray-900">Search & Social</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.searchable_by_name" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Searchable by name</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.searchable_by_email" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Searchable by email</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.allow_tournament_invites" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Allow tournament invites</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-sm sm:text-base" :class="{ 'opacity-50 pointer-events-none': !privacyForm.profile_visible }">
                                        <Checkbox v-model:checked="privacyForm.public_leaderboard_participation" :disabled="!privacyForm.profile_visible" />
                                        <span class="text-gray-700">Participate in public leaderboards</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <PrimaryButton :class="{ 'opacity-25': privacyForm.processing }" :disabled="privacyForm.processing">
                                    <i class="fas fa-shield-alt mr-2"></i>
                                    Save Privacy Settings
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <!-- Statistics Tab -->
                    <div v-show="activeTab === 'statistics'" class="bg-white rounded-xl p-6 border border-green-200 shadow-md">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6">Your Tournament Statistics</h2>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <!-- Tournament Stats -->
                            <div class="bg-gray-50 rounded-xl p-3 sm:p-4 text-center border border-gray-200">
                                <div class="text-xl sm:text-3xl font-bold text-green-600">{{ statistics?.total_tournaments || 0 }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Tournaments Played</div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-xl p-3 sm:p-4 text-center border border-gray-200">
                                <div class="text-xl sm:text-3xl font-bold text-yellow-600">{{ statistics?.tournaments_won || 0 }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Tournaments Won</div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-xl p-3 sm:p-4 text-center border border-gray-200">
                                <div class="text-xl sm:text-3xl font-bold text-blue-600">{{ statistics?.total_points || 0 }}</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Total Points</div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-xl p-3 sm:p-4 text-center border border-gray-200">
                                <div class="text-xl sm:text-3xl font-bold text-purple-600">{{ winRate }}%</div>
                                <div class="text-gray-600 text-xs sm:text-sm">Win Rate</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mt-6 sm:mt-8">
                            <!-- Pick Statistics -->
                            <div class="bg-gray-50 rounded-xl p-4 sm:p-6 border border-gray-200">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Pick Statistics</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Total Picks</span>
                                        <span class="text-gray-900 font-semibold text-sm sm:text-base">{{ statistics?.total_picks || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Winning Picks</span>
                                        <span class="text-green-600 font-semibold text-sm sm:text-base">{{ statistics?.winning_picks || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Drawing Picks</span>
                                        <span class="text-yellow-600 font-semibold text-sm sm:text-base">{{ statistics?.drawing_picks || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Losing Picks</span>
                                        <span class="text-red-600 font-semibold text-sm sm:text-base">{{ statistics?.losing_picks || 0 }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Streak Information -->
                            <div class="bg-gray-50 rounded-xl p-4 sm:p-6 border border-gray-200">
                                <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Streaks & Records</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Current Win Streak</span>
                                        <span class="text-green-600 font-semibold text-sm sm:text-base">{{ statistics?.current_win_streak || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Longest Win Streak</span>
                                        <span class="text-purple-600 font-semibold text-sm sm:text-base">{{ statistics?.longest_win_streak || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Best Tournament Score</span>
                                        <span class="text-yellow-600 font-semibold text-sm sm:text-base">{{ statistics?.highest_tournament_score || 0 }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 text-sm sm:text-base">Average Points</span>
                                        <span class="text-blue-600 font-semibold text-sm sm:text-base">{{ statistics?.average_points_per_tournament || 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Tab -->
                    <div v-show="activeTab === 'account'" class="space-y-4 sm:space-y-6">
                        <div class="bg-white rounded-xl border border-green-200 shadow-md p-4 sm:p-6">
                            <UpdatePasswordForm />
                        </div>

                        <div class="bg-white rounded-xl border border-green-200 shadow-md p-4 sm:p-6">
                            <DeleteUserForm />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Avatar Cropper Modal -->
        <AvatarCropper 
            :show="showCropper"
            :image-file="selectedImageFile"
            @crop="handleCropComplete"
            @close="handleCropCancel"
        />
    </TournamentLayout>
</template>

<style>
/* Hide scrollbar for tab navigation */
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Custom breakpoint for extra small screens */
@media (min-width: 475px) {
    .xs\:inline {
        display: inline;
    }
    .xs\:hidden {
        display: none;
    }
}
</style>
