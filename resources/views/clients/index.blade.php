@extends('layouts.app')

@section('title', __('messages.borrowers'))
@section('page-title', __('messages.borrowers'))

@section('content')
{{-- Import success/error messages --}}
@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
    {{ session('success') }}
</div>
@endif
@if(session('import_errors') && count(session('import_errors')) > 0)
<div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded max-h-60 overflow-y-auto">
    <strong>{{ __('messages.import_details') }}</strong>
    <ul class="mt-2 list-disc list-inside text-sm">
        @foreach(session('import_errors') as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div x-data="clientsIndex()" x-init="init()" class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.borrowers') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.manage_borrowers') }}</p>
            <a href="{{ route('loans.create') }}" 
               class="mt-3 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('messages.create_loan') }}
            </a>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('clients.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                {{ __('messages.add_borrower') }}
            </a>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.search') }}</label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               x-model="filters.search"
                               @input.debounce.300ms="loadClients()"
                               placeholder="{{ __('messages.search_borrower_placeholder') }}"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.loan_status') }}</label>
                    <select id="status" 
                            x-model="filters.status"
                            @change="loadClients()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <option value="">{{ __('messages.all_statuses') }}</option>
                        <option value="active">{{ __('messages.active') }}</option>
                        <option value="inactive">{{ __('messages.inactive') }}</option>
                        <option value="blacklisted">{{ __('messages.blacklisted') }}</option>
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">{{ __('messages.sort_by') }}</label>
                    <select id="sort" 
                            x-model="filters.sort"
                            @change="loadClients()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                        <option value="created_at_desc">{{ __('messages.newest_first') }}</option>
                        <option value="created_at_asc">{{ __('messages.oldest_first') }}</option>
                        <option value="name_asc">{{ __('messages.name_a_z') }}</option>
                        <option value="name_desc">{{ __('messages.name_z_a') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrowers Table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ __('messages.borrowers') }} (<span x-text="pagination.total || 0"></span>)
                </h3>
                <div class="flex items-center space-x-2">
                    <button @click="exportClients()" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ __('messages.export') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ __('messages.loading_borrowers') }}
            </div>
        </div>

        <!-- Table -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.borrower') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.contact') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.location') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loans') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.groups') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_status') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.joined') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="client in clients" :key="client.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-primary-700" x-text="getInitials(client.first_name, client.last_name)"></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="client.first_name + ' ' + client.last_name"></div>
                                        <div class="text-sm text-gray-500" x-text="client.client_id"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="client.email"></div>
                                <div class="text-sm text-gray-500" x-text="client.phone"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="client.district"></div>
                                <div class="text-sm text-gray-500" x-text="client.region"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <span x-text="client.loans_count || 0"></span> loans
                                </div>
                                <div class="text-sm text-gray-500" x-text="formatCurrency(client.total_outstanding || 0)"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="group in client.groups || []" :key="group.id">
                                        <a :href="'/groups/' + group.id" 
                                           class="inline-flex items-center px-2 py-1 rounded-full text-xs"
                                           :class="group.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                           x-text="group.name">
                                        </a>
                                    </template>
                                    <template x-if="!(client.groups && client.groups.length)">
                                        <span class="text-xs text-gray-500">—</span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': client.status === 'active',
                                          'bg-gray-100 text-gray-800': client.status === 'inactive',
                                          'bg-red-100 text-red-800': client.status === 'blacklisted'
                                      }"
                                      x-text="client.status.charAt(0).toUpperCase() + client.status.slice(1)">
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(client.created_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a :href="'/clients/' + client.id"
                                       class="text-primary-600 hover:text-primary-900 text-sm">{{ __('messages.view') }}</a>
                                    <a :href="'/clients/' + client.id + '/edit'"
                                       class="text-indigo-600 hover:text-indigo-900 text-sm">{{ __('messages.edit') }}</a>
                                    <button @click="deleteClient(client)" class="text-red-600 hover:text-red-900 text-sm">{{ __('messages.delete') }}</button>
                                    <button @click="toggleStatus(client)" 
                                            class="text-sm"
                                            :class="client.status === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                                            x-text="client.status === 'active' ? '{{ __('messages.suspend') }}' : '{{ __('messages.activate') }}'">
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Empty State -->
            <div x-show="clients.length === 0 && !loading" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('messages.no_borrowers_found') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('messages.get_started_borrower') }}</p>
                <div class="mt-6">
                    <a href="{{ route('clients.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        {{ __('messages.add_borrower') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="previousPage()" 
                            :disabled="pagination.current_page <= 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('messages.previous') }}
                    </button>
                    <button @click="nextPage()" 
                            :disabled="pagination.current_page >= pagination.last_page"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('messages.next') }}
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            {{ __('messages.showing') }} <span x-text="pagination.from || 0"></span> {{ __('messages.to') }} <span x-text="pagination.to || 0"></span> {{ __('messages.of') }} <span x-text="pagination.total || 0"></span> {{ __('messages.results') }}
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <button @click="previousPage()" 
                                    :disabled="pagination.current_page <= 1"
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">{{ __('messages.previous') }}</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <template x-for="page in getPageNumbers()" :key="page">
                                <button @click="goToPage(page)" 
                                        :class="page === pagination.current_page ? 
                                            'bg-primary-50 border-primary-500 text-primary-600' : 
                                            'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                        x-text="page">
                                </button>
                            </template>
                            <button @click="nextPage()" 
                                    :disabled="pagination.current_page >= pagination.last_page"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="sr-only">{{ __('messages.next') }}</span>
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function clientsIndex() {
    return {
        clients: [],
        loading: false,
        filters: {
            search: '',
            status: '',
            sort: 'created_at_desc'
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
            from: 0,
            to: 0
        },

        init() {
            this.loadClients();
        },

        async loadClients() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await fetch(`/clients/data?${params}`, { credentials: 'same-origin' });
                if (!response.ok) {
                    console.error('Failed to load borrowers:', response.status);
                    this.clients = [];
                    this.pagination = {
                        current_page: 1,
                        last_page: 1,
                        per_page: this.pagination.per_page,
                        total: 0,
                        from: 0,
                        to: 0
                    };
                    return;
                }
                const raw = await response.json();
                const page = raw.data || {};

                this.clients = Array.isArray(page.data) ? page.data : [];
                this.pagination = {
                    current_page: page.current_page || 1,
                    last_page: page.last_page || 1,
                    per_page: page.per_page || this.pagination.per_page,
                    total: page.total || 0,
                    from: page.from || 0,
                    to: page.to || 0
                };
            } catch (error) {
                console.error('Error loading borrowers:', error);
                this.clients = [];
            } finally {
                this.loading = false;
            }
        },

        async deleteClient(client) {
            const isSuperAdmin = window.AppUser?.role === 'superadmin';
            const loansCount = client.loans_count || 0;
            
            let confirmMsg = `Delete borrower "${client.first_name} ${client.last_name}"?\n\n`;
            if (loansCount > 0) {
                confirmMsg += `This borrower has ${loansCount} loan(s).\n`;
                if (isSuperAdmin) {
                    confirmMsg += `As superadmin, this will also delete ALL related loans, schedules, and repayments.\n\n`;
                } else {
                    confirmMsg += `You cannot delete borrowers with active loans.\n\n`;
                }
            }
            confirmMsg += `This action cannot be undone.`;
            
            if (!confirm(confirmMsg)) {
                return;
            }
            
            try {
                // Add force parameter for superadmin to delete with related records
                const url = isSuperAdmin ? `/clients/${client.id}?force=1` : `/clients/${client.id}`;
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });

                const data = await response.json().catch(() => ({}));
                
                if (response.ok) {
                    alert(data.message || 'Borrower deleted successfully');
                    await this.loadClients();
                } else {
                    alert(data.message || 'Error deleting borrower');
                }
            } catch (error) {
                console.error('Error deleting borrower:', error);
                alert('Error deleting borrower');
            }
        },

        async toggleStatus(client) {
            if (!confirm(`Are you sure you want to ${client.status === 'active' ? 'suspend' : 'activate'} this borrower?`)) {
                return;
            }

            try {
                const response = await fetch(`/clients/${client.id}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    await this.loadClients();
                } else {
                    const data = await response.json().catch(() => ({}));
                    alert(data.message || 'Error updating borrower status');
                }
            } catch (error) {
                console.error('Error toggling borrower status:', error);
                alert('Error updating borrower status');
            }
        },

        async exportClients() {
            try {
                const params = new URLSearchParams(this.filters);
                window.open(`/clients/export?${params}`, '_blank');
            } catch (error) {
                console.error('Error exporting borrowers:', error);
                alert('Error exporting borrowers');
            }
        },

        async purgeAll() {
            if (!confirm('Delete ALL borrowers and related loans? This cannot be undone.')) {
                return;
            }
            if (!confirm('Please confirm again: this will remove ALL borrowers for this tenant.')) {
                return;
            }
            try {
                const response = await fetch('/clients/purge', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    credentials: 'same-origin'
                });

                if (response.ok) {
                    await this.loadClients();
                    alert('All borrowers deleted successfully.');
                } else {
                    const data = await response.json().catch(() => ({}));
                    alert(data.message || 'Error purging borrowers');
                }
            } catch (error) {
                console.error('Error purging borrowers:', error);
                alert('Error purging borrowers');
            }
        },
        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.loadClients();
            }
        },

        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.loadClients();
            }
        },

        goToPage(page) {
            this.pagination.current_page = page;
            this.loadClients();
        },

        getPageNumbers() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            
            // Show first page
            if (current > 3) pages.push(1);
            if (current > 4) pages.push('...');
            
            // Show pages around current
            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            
            // Show last page
            if (current < last - 3) pages.push('...');
            if (current < last - 2) pages.push(last);
            
            return pages.filter(page => page !== '...' || pages.indexOf(page) === pages.lastIndexOf(page));
        },

        getInitials(firstName, lastName) {
            return (firstName?.charAt(0) || '') + (lastName?.charAt(0) || '');
        },

        formatCurrency(amount) {
            const s = window.AppSettings || {};
            const locale = s.locale || 'en-TZ';
            const currency = s.currency || 'TZS';
            return new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                minimumFractionDigits: 0
            }).format(amount);
        },

        formatDate(date) {
            const s = window.AppSettings || {};
            const locale = s.locale || 'en-US';
            return new Date(date).toLocaleDateString(locale, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endpush
@endsection