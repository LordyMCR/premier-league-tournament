<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const commands = ref([]);
const selectedCommand = ref(null);
const isExecuting = ref(false);
const commandOutput = ref(null);
const showOutput = ref(false);

onMounted(async () => {
    try {
        const response = await axios.get(route('admin.command-status'));
        commands.value = response.data.commands;
    } catch (error) {
        console.error('Failed to load commands:', error);
    }
});

const executeCommand = async (commandName) => {
    if (isExecuting.value) return;
    
    if (!confirm(`Are you sure you want to execute: ${commandName}?`)) {
        return;
    }
    
    isExecuting.value = true;
    selectedCommand.value = commandName;
    commandOutput.value = null;
    showOutput.value = false;
    
    try {
        const response = await axios.post(route('admin.execute-command'), {
            command: commandName,
        });
        
        commandOutput.value = {
            success: true,
            message: response.data.message,
            output: response.data.output,
        };
        showOutput.value = true;
    } catch (error) {
        commandOutput.value = {
            success: false,
            message: error.response?.data?.message || 'Command execution failed',
            output: error.response?.data?.output || null,
        };
        showOutput.value = true;
    } finally {
        isExecuting.value = false;
        selectedCommand.value = null;
    }
};

const closeOutput = () => {
    showOutput.value = false;
    commandOutput.value = null;
};

// Group commands by category
const groupedCommands = ref({});
onMounted(() => {
    axios.get(route('admin.command-status')).then(response => {
        const cmds = response.data.commands;
        groupedCommands.value = cmds.reduce((acc, cmd) => {
            if (!acc[cmd.category]) {
                acc[cmd.category] = [];
            }
            acc[cmd.category].push(cmd);
            return acc;
        }, {});
    });
});
</script>

<template>
    <Head title="Admin Panel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Panel</h2>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    Administrator
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Alert Banner -->
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Warning:</strong> These commands affect the production database. Use with caution.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Command Categories -->
                <div class="space-y-6">
                    <div v-for="(cmds, category) in groupedCommands" :key="category" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ category }}</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div v-for="command in cmds" :key="command.name" 
                                     class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors">
                                    <div class="flex items-start justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ command.name }}</h4>
                                        <button 
                                            @click="executeCommand(command.name)"
                                            :disabled="isExecuting"
                                            :class="[
                                                'px-3 py-1 rounded text-sm font-medium transition-colors',
                                                isExecuting && selectedCommand === command.name
                                                    ? 'bg-gray-400 text-white cursor-not-allowed'
                                                    : 'bg-green-600 hover:bg-green-700 text-white'
                                            ]"
                                        >
                                            <span v-if="isExecuting && selectedCommand === command.name">
                                                Running...
                                            </span>
                                            <span v-else>Execute</span>
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ command.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Output Modal -->
                <div v-if="showOutput" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click="closeOutput">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white" @click.stop>
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Command Output</h3>
                            <button @click="closeOutput" class="text-gray-400 hover:text-gray-600">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <div :class="[
                                'px-4 py-2 rounded-lg',
                                commandOutput.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                            ]">
                                {{ commandOutput.message }}
                            </div>
                        </div>

                        <div v-if="commandOutput.output" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                            <pre>{{ commandOutput.output }}</pre>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button 
                                @click="closeOutput"
                                class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
