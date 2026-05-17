@can('manage-billing')
<div x-data="{
        open: false,
        loading: false,
        summary: null,
        error: null,
        fetchSummary() {
            this.loading = true;
            this.error = null;
            fetch('/api/billing/summary', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(json => { this.summary = json.data || null; })
                .catch(() => { this.error = 'Failed to load billing summary'; })
                .finally(() => { this.loading = false; });
        }
    }"
     x-on:open-billing-modal.window="open = true; fetchSummary()"
     x-cloak>
    <!-- Modal overlay -->
    <div x-show="open" class="fixed inset-0 z-50">
        <div class="fixed inset-0 bg-black/40" @click="open = false"></div>

        <!-- Modal dialog -->
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl bg-white rounded-lg shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Billing & Subscription</h3>
                    <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>

                <div class="px-6 py-4">
                    <template x-if="loading">
                        <div class="py-8 text-center text-gray-600">Loading summary...</div>
                    </template>
                    <template x-if="error">
                        <div class="py-4 text-red-600" x-text="error"></div>
                    </template>

                    <template x-if="summary">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-50 rounded">
                                    <div class="text-xs text-gray-500">Tenant</div>
                                    <div class="text-sm font-medium text-gray-900" x-text="summary.tenant?.name"></div>
                                </div>
                                <div class="p-4 bg-gray-50 rounded">
                                    <div class="text-xs text-gray-500">Current Plan</div>
                                    <div class="text-sm font-medium text-gray-900" x-text="summary.subscription?.plan?.name ?? '—'"></div>
                                </div>
                                <div class="p-4 bg-gray-50 rounded">
                                    <div class="text-xs text-gray-500">Cancel At Period End</div>
                                    <div class="text-sm font-medium" :class="summary.subscription?.cancel_at_period_end ? 'text-red-600' : 'text-gray-900'" x-text="summary.subscription?.cancel_at_period_end ? 'Yes' : 'No'"></div>
                                </div>
                                <div class="p-4 bg-gray-50 rounded">
                                    <div class="text-xs text-gray-500">Latest Invoice</div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <span x-text="summary.latest_invoice?.number ?? '—'"></span>
                                        <span class="ml-2 text-gray-600" x-show="summary.latest_invoice" x-text="`Due ${summary.latest_invoice?.due_date ?? ''}`"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 bg-gray-50 rounded">
                                <div class="text-xs text-gray-500 mb-1">Add-ons</div>
                                <template x-if="(summary.subscription?.items || []).length === 0">
                                    <div class="text-sm text-gray-600">No add-ons configured.</div>
                                </template>
                                <template x-for="item in (summary.subscription?.items || [])" :key="item.id">
                                    <div class="flex items-center justify-between py-1">
                                        <div class="text-sm text-gray-900" x-text="item.addon_slug"></div>
                                        <div class="text-sm text-gray-600" x-text="`${item.quantity} × ${item.unit_price} ${item.currency}`"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t flex items-center justify-between">
                    <div class="text-xs text-gray-500">Use the API to change plan or add-ons.</div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('billing.subscription') }}" class="px-3 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">Manage Billing</a>
                        <button @click="open = false" class="px-3 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endcan