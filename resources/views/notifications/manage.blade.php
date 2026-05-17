@extends('layouts.admin')

@section('title', 'Notification Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Notification Management</h1>
        <div class="flex space-x-3">
            <button onclick="openTestModal()" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Test Notification
            </button>
            <button onclick="openSendModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Send Notification
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('types')" id="types-tab"
                        class="py-2 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                    Notification Types
                </button>
                <button onclick="showTab('templates')" id="templates-tab"
                        class="py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Templates
                </button>
                <button onclick="showTab('stats')" id="stats-tab"
                        class="py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Statistics
                </button>
            </nav>
        </div>
    </div>

    <!-- Notification Types Tab -->
    <div id="types-content" class="tab-content">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Notification Types by Category</h3>
            </div>
            <div id="notification-types-container" class="p-6">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading notification types...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Templates Tab -->
    <div id="templates-content" class="tab-content hidden">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Notification Templates</h3>
                <p class="mt-1 text-sm text-gray-600">Manage email, SMS, and in-app notification templates</p>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label for="template-type-select" class="block text-sm font-medium text-gray-700 mb-2">
                        Select Notification Type
                    </label>
                    <select id="template-type-select" onchange="loadTemplates(this.value)"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a notification type...</option>
                    </select>
                </div>
                <div id="templates-container">
                    <p class="text-sm text-gray-500">Select a notification type to view templates</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Tab -->
    <div id="stats-content" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Notifications</dt>
                                <dd id="total-notifications" class="text-lg font-medium text-gray-900">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Unread Notifications</dt>
                                <dd id="unread-notifications" class="text-lg font-medium text-gray-900">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Types</dt>
                                <dd id="active-types" class="text-lg font-medium text-gray-900">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V6a2 2 0 00-2-2"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Templates</dt>
                                <dd id="total-templates" class="text-lg font-medium text-gray-900">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div id="recent-activity" class="p-6">
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading statistics...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div id="send-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Send Notification</h3>
            <form id="send-notification-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notification Type</label>
                    <select id="send-notification-type" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select notification type...</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recipient Type</label>
                    <select id="recipient-type" onchange="toggleRecipientFields()" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select recipient type...</option>
                        <option value="single">Single User</option>
                        <option value="bulk">Multiple Users</option>
                        <option value="admin">All Admins</option>
                    </select>
                </div>
                <div id="user-id-field" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User ID</label>
                    <input type="number" id="user-id" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div id="user-ids-field" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">User IDs (comma-separated)</label>
                    <input type="text" id="user-ids" placeholder="1,2,3,4"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="channels" value="database" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">In-App Notification</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels" value="mail" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="channels" value="sms" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">SMS</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSendModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Send Notification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Notification Modal -->
<div id="test-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Test Notification</h3>
            <form id="test-notification-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notification Type</label>
                    <select id="test-notification-type" required
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select notification type...</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="test-channels" value="database" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">In-App Notification</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="test-channels" value="mail" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Email</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="test-channels" value="sms" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">SMS</span>
                        </label>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeTestModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        Send Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let notificationTypes = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadNotificationTypes();
    loadStats();
});

// Tab management
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('[id$="-tab"]').forEach(tab => {
        tab.classList.remove('border-blue-500', 'text-blue-600');
        tab.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Add active class to selected tab
    const activeTab = document.getElementById(tabName + '-tab');
    activeTab.classList.add('border-blue-500', 'text-blue-600');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    
    // Load content based on tab
    if (tabName === 'templates') {
        populateTemplateTypeSelect();
    } else if (tabName === 'stats') {
        loadStats();
    }
}

// Load notification types
function loadNotificationTypes() {
    fetch('/api/notifications/types')
        .then(response => response.json())
        .then(data => {
            notificationTypes = data;
            displayNotificationTypes(data);
            populateNotificationTypeSelects();
        })
        .catch(error => {
            console.error('Error loading notification types:', error);
            document.getElementById('notification-types-container').innerHTML = 
                '<p class="text-red-500">Error loading notification types</p>';
        });
}

// Display notification types
function displayNotificationTypes(types) {
    const container = document.getElementById('notification-types-container');
    let html = '';
    
    Object.keys(types).forEach(category => {
        html += `
            <div class="mb-6">
                <h4 class="text-lg font-medium text-gray-900 mb-3 capitalize">${category.replace('_', ' & ')}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        `;
        
        types[category].forEach(type => {
            const priorityColor = type.priority === 'high' ? 'red' : type.priority === 'medium' ? 'yellow' : 'green';
            html += `
                <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h5 class="font-medium text-gray-900">${type.name}</h5>
                            <p class="text-sm text-gray-600 mt-1">${type.description}</p>
                            <div class="mt-2 flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${priorityColor}-100 text-${priorityColor}-800">
                                    ${type.priority} priority
                                </span>
                                ${type.is_active ? 
                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>' :
                                    '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>'
                                }
                            </div>
                            <div class="mt-2">
                                <p class="text-xs text-gray-500">Channels: ${type.default_channels.join(', ')}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Populate notification type selects
function populateNotificationTypeSelects() {
    const selects = [
        document.getElementById('send-notification-type'),
        document.getElementById('test-notification-type')
    ];
    
    selects.forEach(select => {
        select.innerHTML = '<option value="">Select notification type...</option>';
        
        Object.keys(notificationTypes).forEach(category => {
            const optgroup = document.createElement('optgroup');
            optgroup.label = category.replace('_', ' & ').toUpperCase();
            
            notificationTypes[category].forEach(type => {
                if (type.is_active) {
                    const option = document.createElement('option');
                    option.value = type.key;
                    option.textContent = type.name;
                    optgroup.appendChild(option);
                }
            });
            
            select.appendChild(optgroup);
        });
    });
}

// Populate template type select
function populateTemplateTypeSelect() {
    const select = document.getElementById('template-type-select');
    select.innerHTML = '<option value="">Select a notification type...</option>';
    
    Object.keys(notificationTypes).forEach(category => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = category.replace('_', ' & ').toUpperCase();
        
        notificationTypes[category].forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            optgroup.appendChild(option);
        });
        
        select.appendChild(optgroup);
    });
}

// Load templates for a notification type
function loadTemplates(notificationTypeId) {
    if (!notificationTypeId) {
        document.getElementById('templates-container').innerHTML = 
            '<p class="text-sm text-gray-500">Select a notification type to view templates</p>';
        return;
    }
    
    document.getElementById('templates-container').innerHTML = 
        '<div class="text-center py-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div></div>';
    
    fetch(`/api/notifications/templates/${notificationTypeId}`)
        .then(response => response.json())
        .then(templates => {
            displayTemplates(templates);
        })
        .catch(error => {
            console.error('Error loading templates:', error);
            document.getElementById('templates-container').innerHTML = 
                '<p class="text-red-500">Error loading templates</p>';
        });
}

// Display templates
function displayTemplates(templates) {
    const container = document.getElementById('templates-container');
    
    if (templates.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500">No templates found for this notification type</p>';
        return;
    }
    
    let html = '<div class="space-y-6">';
    
    templates.forEach(template => {
        html += `
            <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <h5 class="font-medium text-gray-900 capitalize">${template.channel} Template</h5>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${template.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${template.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                ${template.subject ? `<p class="text-sm font-medium text-gray-700 mb-2">Subject: ${template.subject}</p>` : ''}
                <div class="bg-gray-50 rounded p-3">
                    <pre class="text-sm text-gray-700 whitespace-pre-wrap">${template.body}</pre>
                </div>
                ${template.variables && template.variables.length > 0 ? `
                    <div class="mt-3">
                        <p class="text-xs text-gray-500">Available variables: ${template.variables.join(', ')}</p>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Load statistics
function loadStats() {
    fetch('/api/notifications/stats')
        .then(response => response.json())
        .then(stats => {
            document.getElementById('total-notifications').textContent = stats.total_notifications || 0;
            document.getElementById('unread-notifications').textContent = stats.unread_notifications || 0;
            
            // Count active notification types
            let activeTypes = 0;
            Object.keys(notificationTypes).forEach(category => {
                activeTypes += notificationTypes[category].filter(type => type.is_active).length;
            });
            document.getElementById('active-types').textContent = activeTypes;
            
            // This would need to be calculated from templates
            document.getElementById('total-templates').textContent = '-';
            
            displayRecentActivity(stats.recent_activity || []);
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            document.getElementById('recent-activity').innerHTML = 
                '<p class="text-red-500">Error loading statistics</p>';
        });
}

// Display recent activity
function displayRecentActivity(activities) {
    const container = document.getElementById('recent-activity');
    
    if (activities.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500">No recent activity</p>';
        return;
    }
    
    let html = '<div class="space-y-3">';
    
    activities.forEach(activity => {
        html += `
            <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM9 7H4l5-5v5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">${activity.title}</p>
                    <p class="text-xs text-gray-500">${activity.type} • ${new Date(activity.created_at).toLocaleString()}</p>
                </div>
                <div class="flex-shrink-0">
                    ${activity.read_at ? 
                        '<span class="w-2 h-2 bg-gray-300 rounded-full"></span>' :
                        '<span class="w-2 h-2 bg-blue-500 rounded-full"></span>'
                    }
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Modal functions
function openSendModal() {
    document.getElementById('send-modal').classList.remove('hidden');
}

function closeSendModal() {
    document.getElementById('send-modal').classList.add('hidden');
    document.getElementById('send-notification-form').reset();
    toggleRecipientFields();
}

function openTestModal() {
    document.getElementById('test-modal').classList.remove('hidden');
}

function closeTestModal() {
    document.getElementById('test-modal').classList.add('hidden');
    document.getElementById('test-notification-form').reset();
}

function toggleRecipientFields() {
    const recipientType = document.getElementById('recipient-type').value;
    const userIdField = document.getElementById('user-id-field');
    const userIdsField = document.getElementById('user-ids-field');
    
    userIdField.classList.add('hidden');
    userIdsField.classList.add('hidden');
    
    if (recipientType === 'single') {
        userIdField.classList.remove('hidden');
    } else if (recipientType === 'bulk') {
        userIdsField.classList.remove('hidden');
    }
}

// Form submissions
document.getElementById('send-notification-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const recipientType = document.getElementById('recipient-type').value;
    const notificationType = document.getElementById('send-notification-type').value;
    
    // Get selected channels
    const channels = Array.from(document.querySelectorAll('input[name="channels"]:checked')).map(cb => cb.value);
    
    let endpoint = '/api/notifications/send';
    let data = {
        notification_type: notificationType,
        channels: channels,
        data: {}
    };
    
    if (recipientType === 'single') {
        data.user_id = document.getElementById('user-id').value;
    } else if (recipientType === 'bulk') {
        endpoint = '/api/notifications/send-bulk';
        data.user_ids = document.getElementById('user-ids').value.split(',').map(id => parseInt(id.trim()));
    } else if (recipientType === 'admin') {
        endpoint = '/api/notifications/send-admin';
    }
    
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Notification sent successfully!');
            closeSendModal();
        } else {
            alert('Error sending notification: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending notification');
    });
});

document.getElementById('test-notification-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const notificationType = document.getElementById('test-notification-type').value;
    const channels = Array.from(document.querySelectorAll('input[name="test-channels"]:checked')).map(cb => cb.value);
    
    fetch('/api/notifications/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            notification_type: notificationType,
            channels: channels
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('Test notification sent successfully!');
            closeTestModal();
        } else {
            alert('Error sending test notification: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error sending test notification');
    });
});
</script>
@endsection