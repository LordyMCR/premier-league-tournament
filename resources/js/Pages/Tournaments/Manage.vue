<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';

const props = defineProps({
    tournament: Object,
    participants: Array,
});

const editMode = ref(false);
const showDeleteModal = ref(false);
const participantToRemove = ref(null);

const form = useForm({
    name: props.tournament.name,
    description: props.tournament.description,
    max_participants: props.tournament.max_participants,
    is_private: props.tournament.is_private,
    status: props.tournament.status,
});

const updateTournament = () => {
    form.put(route('tournaments.update', props.tournament.id), {
        preserveScroll: true,
        onSuccess: () => {
            editMode.value = false;
        },
    });
};

const copyJoinCode = () => {
    navigator.clipboard.writeText(props.tournament.join_code);
    alert('Join code copied to clipboard!');
};

const confirmRemoveParticipant = (participant) => {
    participantToRemove.value = participant;
    showDeleteModal.value = true;
};

const removeParticipant = () => {
    if (!participantToRemove.value) return;
    
    useForm({}).delete(route('tournaments.participants.remove', {
        tournament: props.tournament.id,
        participant: participantToRemove.value.id
    }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            participantToRemove.value = null;
        },
    });
};
</script>

<template>
    <TournamentLayout :title="`Manage ${tournament.name}`">
        <div class="max-w-6xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-xl p-4 sm:p-6 text-white shadow-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold">Tournament Management</h1>
                        <p class="text-green-100 mt-1 text-sm sm:text-base">Manage settings and participants</p>
                    </div>
                    <Link :href="route('tournaments.show', tournament.id)"
                          class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition-all font-medium text-sm text-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Tournament
                    </Link>
                </div>
            </div>

            <!-- Tournament Settings -->
            <div class="bg-white rounded-xl shadow-sm border border-green-200 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Tournament Settings</h2>
                        <p class="text-gray-600 text-xs sm:text-sm mt-1">Update your tournament details</p>
                    </div>
                    <button 
                        v-if="!editMode"
                        @click="editMode = true"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-all font-medium text-sm w-full sm:w-auto">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Settings
                    </button>
                </div>

                <form v-if="editMode" @submit.prevent="updateTournament" class="space-y-4 sm:space-y-6">
                    <!-- Tournament Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tournament Name</label>
                        <input 
                            v-model="form.name"
                            type="text"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm sm:text-base"
                            placeholder="Enter tournament name"
                            required
                        />
                        <p v-if="form.errors.name" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea 
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm sm:text-base"
                            placeholder="Enter tournament description (optional)"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.description }}</p>
                    </div>

                    <!-- Max Participants -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Participants</label>
                        <input 
                            v-model.number="form.max_participants"
                            type="number"
                            min="2"
                            max="1000"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm sm:text-base"
                            required
                        />
                        <p class="text-gray-500 text-xs sm:text-sm mt-1">Current participants: {{ participants.length }}</p>
                        <p v-if="form.errors.max_participants" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.max_participants }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tournament Status</label>
                        <select 
                            v-model="form.status"
                            class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm sm:text-base"
                        >
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                        <p class="text-gray-500 text-xs sm:text-sm mt-1">
                            <span v-if="form.status === 'pending'">Tournament is waiting to start</span>
                            <span v-else-if="form.status === 'active'">Tournament is currently running</span>
                            <span v-else>Tournament has ended</span>
                        </p>
                        <p v-if="form.errors.status" class="text-red-600 text-xs sm:text-sm mt-1">{{ form.errors.status }}</p>
                    </div>

                    <!-- Privacy -->
                    <div>
                        <label class="flex items-start sm:items-center">
                            <input 
                                v-model="form.is_private"
                                type="checkbox"
                                class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-0.5 sm:mt-0"
                            />
                            <span class="ml-3 text-sm font-medium text-gray-700">Private Tournament</span>
                        </label>
                        <p class="text-gray-500 text-xs sm:text-sm mt-1 ml-8">Private tournaments require a join code</p>
                        <p v-if="form.errors.is_private" class="text-red-600 text-xs sm:text-sm mt-1 ml-8">{{ form.errors.is_private }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-all font-medium disabled:opacity-50 text-sm sm:text-base">
                            <i class="fas fa-save mr-2"></i>
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                        <button 
                            type="button"
                            @click="editMode = false; form.reset()"
                            class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-all font-medium text-sm sm:text-base">
                            Cancel
                        </button>
                    </div>
                </form>

                <!-- View Mode -->
                <div v-else class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Tournament Name</p>
                            <p class="text-gray-900 font-semibold mt-1 text-sm sm:text-base">{{ tournament.name }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Status</p>
                            <p class="mt-1 text-sm sm:text-base">
                                <span v-if="tournament.status === 'active'" class="text-green-600 font-semibold">Active</span>
                                <span v-else-if="tournament.status === 'pending'" class="text-blue-600 font-semibold">Pending</span>
                                <span v-else class="text-gray-600 font-semibold">Completed</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Maximum Participants</p>
                            <p class="text-gray-900 font-semibold mt-1 text-sm sm:text-base">{{ tournament.max_participants }}</p>
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Privacy</p>
                            <p class="text-gray-900 font-semibold mt-1 text-sm sm:text-base">{{ tournament.is_private ? 'Private' : 'Public' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Description</p>
                            <p class="text-gray-900 mt-1 text-sm sm:text-base">{{ tournament.description || 'No description provided' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Join Code -->
            <div class="bg-gradient-to-r from-blue-50 to-green-50 rounded-xl shadow-sm border border-green-200 p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-2">Join Code</h3>
                <p class="text-gray-600 text-xs sm:text-sm mb-4">Share this code with others to join your tournament</p>
                
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <div class="bg-white px-4 sm:px-6 py-3 rounded-lg border-2 border-green-300 shadow-sm flex-1 text-center">
                        <p class="text-xl sm:text-2xl font-mono font-bold text-green-700 tracking-wider">{{ tournament.join_code }}</p>
                    </div>
                    <button 
                        @click="copyJoinCode"
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-all font-medium text-sm sm:text-base">
                        <i class="fas fa-copy mr-2"></i>
                        Copy
                    </button>
                </div>
            </div>

            <!-- Participants Management -->
            <div class="bg-white rounded-xl shadow-sm border border-green-200 p-4 sm:p-6">
                <div class="mb-4 sm:mb-6">
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">Participants</h2>
                    <p class="text-gray-600 text-xs sm:text-sm mt-1">
                        {{ participants.length }} / {{ tournament.max_participants }} participants
                    </p>
                </div>

                <div v-if="participants.length === 0" class="text-center py-8 sm:py-12">
                    <i class="fas fa-users text-gray-300 text-5xl sm:text-6xl mb-4"></i>
                    <p class="text-gray-500 font-medium text-sm sm:text-base">No participants yet</p>
                    <p class="text-gray-400 text-xs sm:text-sm mt-2">Share your join code to invite players</p>
                </div>

                <div v-else class="space-y-3">
                    <div 
                        v-for="participant in participants" 
                        :key="participant.id"
                        class="bg-gray-50 rounded-lg p-3 sm:p-4 hover:bg-gray-100 transition-all border border-gray-200">
                        <!-- Mobile Layout -->
                        <div class="flex items-start gap-3 sm:hidden">
                            <img 
                                :src="participant.user.avatar_url" 
                                :alt="participant.user.name"
                                class="w-12 h-12 rounded-full border-2 border-green-200 flex-shrink-0"
                                @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(participant.user.name)}&background=22C55E&color=fff&size=48`"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-900 text-sm truncate">{{ participant.user.name }}</p>
                                <p class="text-xs text-gray-600 truncate">{{ participant.user.email }}</p>
                                <div class="flex items-center gap-4 mt-2">
                                    <div>
                                        <p class="text-xs text-gray-600">Total Points</p>
                                        <p class="text-base font-bold text-green-600">{{ participant.total_points || 0 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600">Joined</p>
                                        <p class="text-xs font-medium text-gray-900">{{ new Date(participant.joined_at).toLocaleDateString() }}</p>
                                    </div>
                                </div>
                                <button 
                                    @click="confirmRemoveParticipant(participant)"
                                    class="mt-3 w-full bg-red-50 text-red-600 px-3 py-2 rounded-lg hover:bg-red-100 transition-all font-medium border border-red-200 text-sm">
                                    <i class="fas fa-user-minus mr-2"></i>
                                    Remove
                                </button>
                            </div>
                        </div>

                        <!-- Desktop Layout -->
                        <div class="hidden sm:flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <img 
                                    :src="participant.user.avatar_url" 
                                    :alt="participant.user.name"
                                    class="w-12 h-12 rounded-full border-2 border-green-200"
                                    @error="$event.target.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(participant.user.name)}&background=22C55E&color=fff&size=48`"
                                />
                                <div>
                                    <p class="font-semibold text-gray-900">{{ participant.user.name }}</p>
                                    <p class="text-sm text-gray-600">{{ participant.user.email }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Total Points</p>
                                    <p class="text-lg font-bold text-green-600">{{ participant.total_points || 0 }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">Joined</p>
                                    <p class="text-sm font-medium text-gray-900">{{ new Date(participant.joined_at).toLocaleDateString() }}</p>
                                </div>
                                <button 
                                    @click="confirmRemoveParticipant(participant)"
                                    class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition-all font-medium border border-red-200">
                                    <i class="fas fa-user-minus mr-2"></i>
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="bg-red-50 rounded-xl shadow-sm border-2 border-red-200 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-red-900">Danger Zone</h3>
                        <p class="text-red-700 text-xs sm:text-sm mt-1">Irreversible and destructive actions</p>
                    </div>
                    <button 
                        @click="$inertia.delete(route('tournaments.destroy', tournament.id), { 
                            onBefore: () => confirm('Are you sure you want to delete this tournament? This action cannot be undone and will remove all participant data, picks, and history.') 
                        })"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-all font-medium text-sm w-full sm:w-auto">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Tournament
                    </button>
                </div>
            </div>
        </div>

        <!-- Remove Participant Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-4 sm:p-6">
                <div class="text-center">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2">Remove Participant?</h3>
                    <p class="text-gray-600 mb-6 text-sm sm:text-base">
                        Are you sure you want to remove <strong>{{ participantToRemove?.user.name }}</strong> from this tournament? 
                        This will delete all their picks and cannot be undone.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button 
                            @click="removeParticipant"
                            class="flex-1 bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 transition-all font-medium text-sm sm:text-base">
                            Yes, Remove
                        </button>
                        <button 
                            @click="showDeleteModal = false; participantToRemove = null"
                            class="flex-1 bg-gray-200 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-300 transition-all font-medium text-sm sm:text-base">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
