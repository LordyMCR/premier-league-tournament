<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
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

    <TournamentLayout>
        <!-- Page Header -->
        <div class="mb-8">
            <div class="bg-white rounded-xl shadow-lg border border-green-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Admin Panel</h1>
                        <p class="text-gray-600 mt-1">Manage and maintain your tournament system</p>
                    </div>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-purple-100 text-purple-800 border border-purple-300">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Administrator
                    </span>
                </div>
            </div>
        </div>
        <!-- Alert Banner -->
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 shadow-sm">
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
            <div v-for="(cmds, category) in groupedCommands" :key="category" class="bg-white overflow-hidden shadow-lg rounded-xl border border-green-200">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-folder-open text-green-600 mr-2"></i>
                        {{ category }}
                    </h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div v-for="command in cmds" :key="command.name" 
                             class="border border-gray-200 rounded-lg p-4 hover:border-green-400 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ command.name }}</h4>
                                <button 
                                    @click="executeCommand(command.name)"
                                    :disabled="isExecuting"
                                    :class="[
                                        'px-3 py-1 rounded-lg text-sm font-medium transition-all duration-200 shadow-sm',
                                        isExecuting && selectedCommand === command.name
                                            ? 'bg-gray-400 text-white cursor-not-allowed'
                                            : 'bg-green-600 hover:bg-green-700 text-white hover:shadow-md'
                                    ]"
                                >
                                    <span v-if="isExecuting && selectedCommand === command.name">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>
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
            <div class="relative top-20 mx-auto p-5 border border-green-200 w-11/12 max-w-4xl shadow-2xl rounded-xl bg-white" @click.stop>
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Command Output</h3>
                    <button @click="closeOutput" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="mb-4">
                    <div :class="[
                        'px-4 py-2 rounded-lg border',
                        commandOutput.success ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200'
                    ]">
                        {{ commandOutput.message }}
                    </div>
                </div>

                <div v-if="commandOutput.output" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto shadow-inner">
                    <pre>{{ commandOutput.output }}</pre>
                </div>

                <div class="mt-4 flex justify-end">
                    <button 
                        @click="closeOutput"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors shadow-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </TournamentLayout>
</template>
