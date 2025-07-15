<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    max_participants: 20,
    is_private: false,
});

const submit = () => {
    form.post(route('tournaments.store'));
};

// Set default dates (start tomorrow, end in 20 weeks)
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
const endDate = new Date();
endDate.setDate(endDate.getDate() + (20 * 7)); // 20 weeks from now

form.start_date = tomorrow.toISOString().split('T')[0];
form.end_date = endDate.toISOString().split('T')[0];
</script>

<template>
    <Head title="Create Tournament" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create New Tournament
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Tournament Name -->
                            <div>
                                <InputLabel for="name" value="Tournament Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    placeholder="e.g., Premier League Predictions 2024"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Description (Optional)" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    rows="3"
                                    placeholder="Tell participants what this tournament is about..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Date Range -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="start_date" value="Start Date" />
                                    <TextInput
                                        id="start_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="form.start_date"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.start_date" />
                                </div>

                                <div>
                                    <InputLabel for="end_date" value="End Date" />
                                    <TextInput
                                        id="end_date"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="form.end_date"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.end_date" />
                                </div>
                            </div>

                            <!-- Max Participants -->
                            <div>
                                <InputLabel for="max_participants" value="Maximum Participants" />
                                <TextInput
                                    id="max_participants"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.max_participants"
                                    required
                                    min="2"
                                    max="100"
                                />
                                <InputError class="mt-2" :message="form.errors.max_participants" />
                                <p class="mt-1 text-sm text-gray-600">
                                    Between 2 and 100 participants allowed
                                </p>
                            </div>

                            <!-- Private Tournament -->
                            <div class="flex items-center">
                                <Checkbox
                                    id="is_private"
                                    v-model:checked="form.is_private"
                                />
                                <InputLabel for="is_private" value="Private Tournament" class="ml-2" />
                            </div>
                            <p class="text-sm text-gray-600">
                                Private tournaments can only be joined with the join code
                            </p>

                            <!-- Tournament Rules -->
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="font-semibold text-blue-900 mb-2">Tournament Rules</h3>
                                <ul class="text-sm text-blue-800 space-y-1">
                                    <li>• Each game week, pick one Premier League team to win</li>
                                    <li>• Win = 3 points, Draw = 1 point, Loss = 0 points</li>
                                    <li>• Once you pick a team, you can't pick them again</li>
                                    <li>• 20 game weeks total (one for each Premier League team)</li>
                                    <li>• Highest total score wins the tournament!</li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <a
                                    :href="route('tournaments.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </a>
                                
                                <PrimaryButton 
                                    :class="{ 'opacity-25': form.processing }" 
                                    :disabled="form.processing"
                                >
                                    Create Tournament
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 