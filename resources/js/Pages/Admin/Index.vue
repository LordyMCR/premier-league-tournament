<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import TournamentLayout from '@/Layouts/TournamentLayout.vue';
import axios from 'axios';

const commands = ref([]);
const selectedCommand = ref(null);
const isExecuting = ref(false);
const commandOutput = ref(null);
const showOutput = ref(false);
const commandHistory = ref([]);
const liveLog = ref('');
const logContainer = ref(null);

// User Management
const activeUserTab = ref('pending'); // 'pending', 'approved', 'denied', 'all'
const users = ref([]);
const userSearch = ref('');
const userLoading = ref(false);
const userCurrentPage = ref(1);
const userTotalPages = ref(1);

onMounted(async () => {
    try {
        const response = await axios.get(route('admin.command-status'));
        commands.value = response.data.commands;
    } catch (error) {
        console.error('Failed to load commands:', error);
    }
});

const addToLog = (message, type = 'info') => {
    const timestamp = new Date().toLocaleTimeString();
    const prefix = type === 'error' ? '❌' : type === 'success' ? '✅' : '▶️';
    liveLog.value += `[${timestamp}] ${prefix} ${message}\n`;
    
    // Auto-scroll to bottom
    nextTick(() => {
        if (logContainer.value) {
            logContainer.value.scrollTop = logContainer.value.scrollHeight;
        }
    });
};

const executeCommand = async (commandName) => {
    if (isExecuting.value) return;
    
    if (!window.confirm(`Are you sure you want to execute: ${commandName}?`)) {
        return;
    }
    
    isExecuting.value = true;
    selectedCommand.value = commandName;
    commandOutput.value = null;
    showOutput.value = false;
    
    addToLog(`Executing command: ${commandName}`, 'info');
    
    const startTime = Date.now();
    
    try {
        const response = await axios.post(route('admin.execute-command'), {
            command: commandName,
        });
        
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        
        commandOutput.value = {
            success: true,
            message: response.data.message,
            output: response.data.output,
        };
        
        // Add to history
        commandHistory.value.unshift({
            command: commandName,
            timestamp: new Date().toLocaleString(),
            duration: `${duration}s`,
            success: true,
            output: response.data.output,
        });
        
        // Keep only last 10 commands in history
        if (commandHistory.value.length > 10) {
            commandHistory.value = commandHistory.value.slice(0, 10);
        }
        
        addToLog(`Command completed successfully in ${duration}s`, 'success');
        
        // Log the output
        if (response.data.output) {
            addToLog('Output:\n' + response.data.output, 'info');
        }
        
        showOutput.value = true;
    } catch (error) {
        const duration = ((Date.now() - startTime) / 1000).toFixed(2);
        
        commandOutput.value = {
            success: false,
            message: error.response?.data?.message || 'Command execution failed',
            output: error.response?.data?.output || null,
        };
        
        // Add to history
        commandHistory.value.unshift({
            command: commandName,
            timestamp: new Date().toLocaleString(),
            duration: `${duration}s`,
            success: false,
            output: error.response?.data?.message,
        });
        
        addToLog(`Command failed: ${error.response?.data?.message || 'Unknown error'}`, 'error');
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

const clearLog = () => {
    if (window.confirm('Clear the live log?')) {
        liveLog.value = '';
        addToLog('Log cleared', 'info');
    }
};

const clearHistory = () => {
    if (window.confirm('Clear command history?')) {
        commandHistory.value = [];
        addToLog('Command history cleared', 'info');
    }
};

const viewHistoryOutput = (historyItem) => {
    commandOutput.value = {
        success: historyItem.success,
        message: historyItem.success ? 'Command completed' : 'Command failed',
        output: historyItem.output,
    };
    showOutput.value = true;
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
    loadUsers();
});

// User Management Functions
const loadUsers = async (page = 1) => {
    userLoading.value = true;
    try {
        const response = await axios.get(route('admin.users'), {
            params: {
                type: activeUserTab.value,
                search: userSearch.value,
                page: page,
            }
        });
        users.value = response.data.data;
        userCurrentPage.value = response.data.current_page;
        userTotalPages.value = response.data.last_page;
    } catch (error) {
        console.error('Failed to load users:', error);
        addToLog('Failed to load users: ' + (error.response?.data?.message || 'Unknown error'), 'error');
    } finally {
        userLoading.value = false;
    }
};

const switchUserTab = (tab) => {
    activeUserTab.value = tab;
    userCurrentPage.value = 1;
    loadUsers(1);
};

const searchUsers = () => {
    userCurrentPage.value = 1;
    loadUsers(1);
};

const approveUser = async (userId) => {
    if (!window.confirm('Are you sure you want to approve this user?')) {
        return;
    }
    
    try {
        const response = await axios.post(route('admin.users.approve', userId));
        addToLog(response.data.message, 'success');
        loadUsers(userCurrentPage.value);
    } catch (error) {
        addToLog('Failed to approve user: ' + (error.response?.data?.message || 'Unknown error'), 'error');
    }
};

const disapproveUser = async (userId) => {
    if (!window.confirm('Are you sure you want to disapprove this user? They will be logged out and unable to access the system.')) {
        return;
    }
    
    try {
        const response = await axios.post(route('admin.users.disapprove', userId));
        addToLog(response.data.message, 'success');
        loadUsers(userCurrentPage.value);
    } catch (error) {
        addToLog('Failed to disapprove user: ' + (error.response?.data?.message || 'Unknown error'), 'error');
    }
};

const denyUser = async (userId) => {
    const user = users.value.find(u => u.id === userId);
    if (!window.confirm(`Are you sure you want to deny ${user?.name || 'this user'}? They will receive a denial email and will not be able to access the system.`)) {
        return;
    }
    
    try {
        const response = await axios.post(route('admin.users.deny', userId));
        addToLog(response.data.message, 'success');
        loadUsers(userCurrentPage.value);
    } catch (error) {
        addToLog('Failed to deny user: ' + (error.response?.data?.message || 'Unknown error'), 'error');
    }
};

const removeUser = async (userId) => {
    const user = users.value.find(u => u.id === userId);
    if (!window.confirm(`Are you sure you want to remove ${user?.name || 'this user'}? This will:\n\n- Soft delete their account\n- Remove them from all tournaments\n- Delete all their picks\n\nThis action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await axios.delete(route('admin.users.remove', userId));
        addToLog(response.data.message, 'success');
        loadUsers(userCurrentPage.value);
    } catch (error) {
        addToLog('Failed to remove user: ' + (error.response?.data?.message || 'Unknown error'), 'error');
    }
};
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

        <!-- Live Log Panel -->
        <div class="mb-6 bg-gray-900 rounded-xl shadow-lg border border-green-200 overflow-hidden">
            <div class="bg-gray-800 px-4 py-3 flex items-center justify-between border-b border-gray-700">
                <div class="flex items-center">
                    <i class="fas fa-terminal text-green-400 mr-2"></i>
                    <h3 class="text-white font-semibold">Live Command Log</h3>
                </div>
                <button 
                    @click="clearLog"
                    class="text-xs px-3 py-1 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors"
                >
                    <i class="fas fa-trash-alt mr-1"></i>
                    Clear
                </button>
            </div>
            <div 
                ref="logContainer"
                class="p-4 font-mono text-sm text-green-400 h-64 overflow-y-auto bg-gray-900"
            >
                <pre v-if="liveLog" class="whitespace-pre-wrap">{{ liveLog }}</pre>
                <div v-else class="text-gray-500 italic">No commands executed yet...</div>
            </div>
        </div>

        <!-- Command History -->
        <div v-if="commandHistory.length > 0" class="mb-6 bg-white rounded-xl shadow-lg border border-green-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-b border-green-200">
                <div class="flex items-center">
                    <i class="fas fa-history text-green-600 mr-2"></i>
                    <h3 class="text-gray-900 font-semibold">Command History</h3>
                </div>
                <button 
                    @click="clearHistory"
                    class="text-xs px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded transition-colors"
                >
                    <i class="fas fa-trash-alt mr-1"></i>
                    Clear History
                </button>
            </div>
            <div class="divide-y divide-gray-200">
                <div 
                    v-for="(item, index) in commandHistory" 
                    :key="index"
                    class="px-6 py-4 hover:bg-gray-50 transition-colors cursor-pointer"
                    @click="viewHistoryOutput(item)"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center',
                                item.success ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
                            ]">
                                <i :class="item.success ? 'fas fa-check' : 'fas fa-times'"></i>
                            </span>
                            <div>
                                <p class="font-medium text-gray-900">{{ item.command }}</p>
                                <p class="text-xs text-gray-500">{{ item.timestamp }} • Duration: {{ item.duration }}</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="mb-6 bg-white rounded-xl shadow-lg border border-green-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-green-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-users text-green-600 mr-2"></i>
                        User Management
                    </h3>
                </div>
                
                <!-- Search and Tabs -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="userSearch"
                            @keyup.enter="searchUsers"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button
                            @click="switchUserTab('pending')"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                activeUserTab === 'pending'
                                    ? 'bg-yellow-600 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            Pending
                        </button>
                        <button
                            @click="switchUserTab('approved')"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                activeUserTab === 'approved'
                                    ? 'bg-green-600 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            Approved
                        </button>
                        <button
                            @click="switchUserTab('denied')"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                activeUserTab === 'denied'
                                    ? 'bg-red-600 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            Denied
                        </button>
                        <button
                            @click="switchUserTab('all')"
                            :class="[
                                'px-4 py-2 rounded-lg font-medium transition-colors',
                                activeUserTab === 'all'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            All Users
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div v-if="userLoading" class="p-8 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Loading users...</p>
            </div>
            <div v-else-if="users.length === 0" class="p-8 text-center text-gray-500">
                <i class="fas fa-users text-3xl mb-2"></i>
                <p>No users found.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tournaments</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center text-white font-medium">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span v-if="user.is_admin" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Admin
                                    </span>
                                    <span v-else-if="user.is_approved" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Approved
                                    </span>
                                    <span v-else-if="user.denied_at" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Denied
                                    </span>
                                    <span v-else class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                    <span v-if="user.deleted_at" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Deleted
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>Participating: {{ user.tournaments_count }}</div>
                                <div>Created: {{ user.created_tournaments_count }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ user.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div v-if="!user.deleted_at && !user.is_admin" class="flex justify-end gap-2">
                                    <!-- Pending users (not approved, not denied): Show Approve and Deny buttons -->
                                    <template v-if="!user.is_approved && !user.denied_at">
                                        <button
                                            @click="approveUser(user.id)"
                                            class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-xs"
                                        >
                                            <i class="fas fa-check mr-1"></i>
                                            Approve
                                        </button>
                                        <button
                                            @click="denyUser(user.id)"
                                            class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition-colors text-xs"
                                        >
                                            <i class="fas fa-times mr-1"></i>
                                            Deny
                                        </button>
                                    </template>
                                    
                                    <!-- Approved users: Show only Remove button -->
                                    <template v-else-if="user.is_approved">
                                        <button
                                            @click="removeUser(user.id)"
                                            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors text-xs"
                                        >
                                            <i class="fas fa-trash mr-1"></i>
                                            Remove
                                        </button>
                                    </template>
                                    
                                    <!-- Denied users: No actions (they're already denied) -->
                                    <template v-else-if="user.denied_at">
                                        <span class="text-gray-400 text-xs">Denied</span>
                                    </template>
                                </div>
                                <span v-else-if="user.deleted_at" class="text-gray-400 text-xs">Deleted</span>
                                <span v-else-if="user.is_admin" class="text-gray-400 text-xs">Admin</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="userTotalPages > 1" class="bg-gray-50 px-6 py-4 border-t border-green-200 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Page {{ userCurrentPage }} of {{ userTotalPages }}
                </div>
                <div class="flex gap-2">
                    <button
                        @click="loadUsers(userCurrentPage - 1)"
                        :disabled="userCurrentPage === 1"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            userCurrentPage === 1
                                ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                : 'bg-green-600 text-white hover:bg-green-700'
                        ]"
                    >
                        Previous
                    </button>
                    <button
                        @click="loadUsers(userCurrentPage + 1)"
                        :disabled="userCurrentPage === userTotalPages"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-colors',
                            userCurrentPage === userTotalPages
                                ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
                                : 'bg-green-600 text-white hover:bg-green-700'
                        ]"
                    >
                        Next
                    </button>
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
