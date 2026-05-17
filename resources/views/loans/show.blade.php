@extends('layouts.app')

@section('title', __('messages.loan_details'))
@section('page-title', __('messages.loan_details'))

@section('content')
<div x-data="loanShow({{ $loan->id }})" x-init="init()" class="space-y-6">
    <!-- Header -->
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900" x-text="loan?.loan_number ? `{{ __('messages.loan') }} ${loan.loan_number}` : '{{ __('messages.loan_details') }}'"></h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.view_loan_progress') }}</p>
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('loans.edit', $loan->id) }}" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                {{ __('messages.edit') }}
            </a>
            <button @click="confirmDelete()" class="px-3 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{ __('messages.delete') }}
            </button>
            <a href="{{ route('loans.index') }}" class="px-3 py-2 text-sm bg-gray-100 text-gray-800 rounded-md">{{ __('messages.back_to_loans') }}</a>
        </div>
    </div>

    <!-- Status/Stage Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.loan_status') }}</p>
                <p class="mt-2 text-lg font-bold" style="color:#111827;" x-text="(loan?.status || '-').toUpperCase()"></p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.stage') }}</p>
                <p class="mt-2 text-lg font-bold" style="color:#111827;" x-text="formatStage(loan?.approval_stage)"></p>
                <p class="mt-1 text-xs" style="color:#6b7280;">{{ __('messages.stage_status') }}: <span x-text="(loan?.approval_stage_status || '-')"></span></p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.applied') }}</p>
                <p class="mt-2 text-lg font-bold" style="color:#111827;" x-text="formatDate(loan?.application_date || loan?.created_at)"></p>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.principal_amount') }}</p>
                <p class="mt-2 text-lg font-bold" style="color:#111827;" x-text="formatCurrency(loan?.principal || 0)"></p>
            </div>
        </div>
    </div>

    <!-- Borrower & Product -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#92400e;">{{ __('messages.borrower') }}</h3>
                <div class="text-sm space-y-2">
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.name') }}:</span> <span x-text="`${loan?.client?.first_name || ''} ${loan?.client?.last_name || ''}`.trim()"></span></p>
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.client_id') }}:</span> <span x-text="loan?.client?.client_id || '-'"></span></p>
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.phone_number') }}:</span> <span x-text="loan?.client?.phone || '-'"></span></p>
                </div>
            </div>
        </div>
        <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
            <div class="p-5">
                <h3 class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#92400e;">{{ __('messages.product') }}</h3>
                <div class="text-sm space-y-2">
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.name') }}:</span> <span x-text="loan?.product?.name || '-'"></span></p>
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.interest_rate') }}:</span> <span x-text="loan?.product?.interest_rate ? `${loan.product.interest_rate}%` : '-'"></span></p>
                    <p style="color:#111827;"><span class="font-semibold" style="color:#6b7280;">{{ __('messages.term') }}:</span> <span x-text="loan?.tenure || '-'"></span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="p-5">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.stage_decisions') }}</h3>
                <div class="flex space-x-2">
                    <button @click="openDecisionModal('approve')" class="px-3 py-2 text-sm font-semibold text-white rounded-md" style="background:#16a34a;" :disabled="!canDecide()">{{ __('messages.approve') }}</button>
                    <button @click="openDecisionModal('reject')" class="px-3 py-2 text-sm font-semibold text-white rounded-md" style="background:#dc2626;" :disabled="!canDecide()">{{ __('messages.reject') }}</button>
                    <button @click="openHistory()" class="px-3 py-2 text-sm font-semibold rounded-md" style="background:#fefce8;color:#92400e;border:1px solid #fde68a;">{{ __('messages.history') }}</button>
                </div>
            </div>
            <p class="mt-2 text-xs" style="color:#6b7280;">{{ __('messages.approvals_role_gated') }}</p>
        </div>
    </div>

    <!-- Attachments -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="p-5">
            <h3 class="text-xs font-bold uppercase tracking-widest mb-4" style="color:#92400e;">{{ __('messages.loan_attachments') }}</h3>
            <div x-show="(loan?.documents || []).length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="doc in (loan?.documents || [])" :key="doc.id">
                    <div class="flex items-center justify-between p-4 rounded-lg" style="background:#f9fafb;border:1px solid #e5e7eb;">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <template x-if="doc.mime_type?.includes('image')">
                                    <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </template>
                                <template x-if="doc.mime_type?.includes('pdf')">
                                    <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0112.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </template>
                            </div>
                            <div>
                                <p class="text-sm font-medium" style="color:#111827;" x-text="doc.original_name"></p>
                                <p class="text-xs" style="color:#6b7280;">
                                    <span x-text="getDocTypeLabel(doc.document_type)"></span> •
                                    <span x-text="formatFileSize(doc.size)"></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a :href="`/loans/${loan.id}/documents/${doc.id}/view`" target="_blank" 
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                {{ __('messages.view') }}
                            </a>
                            <a :href="`/loans/${loan.id}/documents/${doc.id}/download`"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('messages.download') }}
                            </a>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="!(loan?.documents || []).length" class="text-center py-8" style="color:#6b7280;">
                <svg class="mx-auto h-12 w-12" style="color:#4b5563;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-2 text-sm">{{ __('messages.no_attachments_loan') }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Repayments -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="px-5 pt-5 pb-2">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.recent_repayments_loan') }}</h3>
                <a :href="`/repayments?loan_id=${loanId}`"
                   class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-md" style="background:#16a34a;color:#ffffff;">
                    {{ __('messages.view_all') }}
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full" style="border-collapse:collapse;">
                <thead>
                    <tr style="background:#f3f4f6;">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.date') }}</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.amount') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.payment_method') }}</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.reference') }}</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase" style="color:#92400e;border-bottom:2px solid #e5e7eb;">{{ __('messages.loan_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(rep, idx) in (loan?.repayments || []).slice(0, 10)" :key="rep.id">
                        <tr :style="idx % 2 === 0 ? 'background:#f9fafb;' : 'background:#ffffff;'">
                            <td class="px-5 py-3 whitespace-nowrap text-sm" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatDate(rep.payment_date || rep.created_at)"></td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm text-right font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatCurrency(rep.amount)"></td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="(rep.payment_method || '-').replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase())"></td>
                            <td class="px-5 py-3 whitespace-nowrap text-sm" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="rep.reference || '-'"></td>
                            <td class="px-5 py-3 whitespace-nowrap text-center" style="border-bottom:1px solid #e5e7eb;">
                                <span :style="{
                                    background: rep.status === 'completed' || rep.status === 'paid' ? '#16a34a' : rep.status === 'failed' ? '#dc2626' : '#4b5563',
                                    color: '#fff',
                                    padding: '2px 10px',
                                    borderRadius: '4px',
                                    fontSize: '0.72rem',
                                    fontWeight: '700',
                                    letterSpacing: '0.08em',
                                    display: 'inline-block'
                                }" x-text="(rep.status || '-').toUpperCase()"></span>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!(loan?.repayments || []).length">
                        <td colspan="5" class="px-5 py-6 text-sm text-center" style="color:#6b7280;background:#ffffff;">{{ __('messages.no_repayments_loan') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Schedules -->
    <div class="overflow-hidden rounded-xl" style="background:#ffffff;border:1px solid #e5e7eb;">
        <div class="px-5 pt-5 pb-0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-bold uppercase tracking-widest" style="color:#92400e;">{{ __('messages.repayment_schedule') }}</h3>
                <div class="flex space-x-2 no-print">
                    @if(auth()->user()?->isAdmin() || auth()->user()?->hasPermission('loans.edit'))
                    <button @click="syncSchedules()" :disabled="syncingSchedules"
                            class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md disabled:opacity-50"
                            style="background:#1e40af;color:#ffffff;" title="Fix schedule statuses from recorded repayments">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span x-text="syncingSchedules ? 'Syncing...' : 'Sync Schedules'"></span>
                    </button>
                    @endif
                    <button @click="printSchedule()" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md" style="background:#fefce8;color:#92400e;border:1px solid #fde68a;">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        {{ __('messages.print') }}
                    </button>
                    <button @click="downloadScheduleCSV()" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md" style="background:#16a34a;color:#ffffff;">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        {{ __('messages.download_csv') }}
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex rounded-lg overflow-hidden mb-0" style="background:#f3f4f6;display:inline-flex;">
                <button @click="scheduleTab='detail'"
                        :style="scheduleTab==='detail' ? 'background:#c4621a;color:#fff;' : 'background:transparent;color:#6b7280;'"
                        class="px-6 py-2 text-sm font-semibold transition-colors"
                        x-text="scheduleDetailLabel()"></button>
                <button @click="scheduleTab='yearly'"
                        :style="scheduleTab==='yearly' ? 'background:#c4621a;color:#fff;' : 'background:transparent;color:#6b7280;'"
                        class="px-6 py-2 text-sm font-semibold transition-colors">Yearly breakdown</button>
            </div>
        </div>

        <!-- Detail Breakdown — bank-statement format -->
        <div x-show="scheduleTab==='detail'" class="overflow-x-auto mt-0">
            <table class="min-w-full text-xs" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="px-3 py-3 text-left font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">#</th>
                        <th class="px-3 py-3 text-left font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Due Date</th>
                        <th class="px-3 py-3 text-left font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Event Type</th>
                        <th class="px-3 py-3 text-left font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Currency</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#15803d;border-bottom:2px solid #e5e7eb;">Principal Amount</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#c2410c;border-bottom:2px solid #e5e7eb;">Interest Amount</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Total Amount</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#1d4ed8;border-bottom:2px solid #e5e7eb;">Serviced Amount</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#dc2626;border-bottom:2px solid #e5e7eb;">Unserviced Amount</th>
                        <th class="px-3 py-3 text-center font-bold whitespace-nowrap" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Status</th>
                        <th class="px-3 py-3 text-right font-bold whitespace-nowrap" style="background:#f3f4f6;color:#dc2626;border-bottom:2px solid #e5e7eb;">Days in Arrears</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Disbursement row -->
                    <tr style="background:#f9fafb;" x-show="loan?.disbursed_at">
                        <td class="px-3 py-2 whitespace-nowrap" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">—</td>
                        <td class="px-3 py-2 whitespace-nowrap font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatFullDate(loan?.disbursed_at)"></td>
                        <td class="px-3 py-2 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                            <span style="background:#1d4ed8;color:#ffffff;padding:2px 8px;border-radius:4px;font-weight:700;letter-spacing:0.06em;">DISBURSEMENT</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">TZS</td>
                        <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" style="color:#15803d;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(loan?.principal || 0)"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">0.00</td>
                        <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(loan?.principal || 0)"></td>
                        <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">0.00</td>
                        <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">0.00</td>
                        <td class="px-3 py-2 whitespace-nowrap text-center" style="border-bottom:1px solid #e5e7eb;">
                            <span style="background:#16a34a;color:#ffffff;padding:2px 8px;border-radius:4px;font-weight:700;">PAID</span>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">0</td>
                    </tr>
                    <template x-for="(s, idx) in detailScheduleRows()" :key="s.id">
                        <tr :style="idx % 2 === 0 ? 'background:#ffffff;' : 'background:#f9fafb;'">
                            <td class="px-3 py-2 whitespace-nowrap font-semibold" style="color:#6b7280;border-bottom:1px solid #e5e7eb;" x-text="s.installment_number"></td>
                            <td class="px-3 py-2 whitespace-nowrap font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatFullDate(s.due_date)"></td>
                            <td class="px-3 py-2 whitespace-nowrap" style="border-bottom:1px solid #e5e7eb;">
                                <span style="background:#92400e;color:#fde68a;padding:2px 8px;border-radius:4px;font-weight:700;letter-spacing:0.06em;">REPAYMENT</span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap" style="color:#6b7280;border-bottom:1px solid #e5e7eb;">TZS</td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#15803d;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(s.principal_amount)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#c2410c;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(s.interest_amount)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(s.total_amount)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#1d4ed8;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(s.paid_amount || 0)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-semibold"
                                :style="scheduleUnserviced(s) > 0 ? 'color:#dc2626;border-bottom:1px solid #e5e7eb;' : 'color:#6b7280;border-bottom:1px solid #e5e7eb;'"
                                x-text="formatNumber(scheduleUnserviced(s))"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-center" style="border-bottom:1px solid #e5e7eb;">
                                <span :style="scheduleStatusStyle(s)" x-text="scheduleStatusLabel(s)"></span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-semibold"
                                :style="scheduleDaysArrears(s) > 0 ? 'color:#dc2626;border-bottom:1px solid #e5e7eb;' : 'color:#6b7280;border-bottom:1px solid #e5e7eb;'"
                                x-text="scheduleDaysArrears(s) > 0 ? scheduleDaysArrears(s) : '0'"></td>
                        </tr>
                    </template>
                    <!-- Totals row -->
                    <tr x-show="(loan?.schedules || []).length > 0" style="background:#f3f4f6;font-weight:700;">
                        <td class="px-3 py-3 text-sm font-bold" colspan="4" style="color:#92400e;border-top:2px solid #16a34a;">Total Events: <span x-text="(loan?.schedules||[]).length"></span></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#15803d;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().principal)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#c2410c;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().interest)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#111827;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().total)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#1d4ed8;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().serviced)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#dc2626;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().unserviced)"></td>
                        <td class="px-3 py-3" style="border-top:2px solid #16a34a;" colspan="2">
                            <span class="text-xs" style="color:#6b7280;">Paid: <span style="color:#15803d;" x-text="scheduleTotals().servicedCount"></span> &nbsp;|&nbsp; Unpaid: <span style="color:#dc2626;" x-text="scheduleTotals().unservicedCount"></span></span>
                        </td>
                    </tr>
                    <tr x-show="!(loan?.schedules || []).length">
                        <td colspan="11" class="px-5 py-6 text-sm text-center" style="color:#6b7280;background:#ffffff;">{{ __('messages.no_schedules') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Yearly Breakdown -->
        <div x-show="scheduleTab==='yearly'" class="overflow-x-auto mt-0">
            <table class="min-w-full text-xs" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <th class="px-3 py-3 text-left font-bold" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Year</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#15803d;border-bottom:2px solid #e5e7eb;">Principal Amount</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#c2410c;border-bottom:2px solid #e5e7eb;">Interest Amount</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#92400e;border-bottom:2px solid #e5e7eb;">Total Amount</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#1d4ed8;border-bottom:2px solid #e5e7eb;">Serviced Amount</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#dc2626;border-bottom:2px solid #e5e7eb;">Unserviced Amount</th>
                        <th class="px-3 py-3 text-right font-bold" style="background:#f3f4f6;color:#374151;border-bottom:2px solid #e5e7eb;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(yr, idx) in yearlyScheduleRows()" :key="yr.year">
                        <tr :style="idx % 2 === 0 ? 'background:#ffffff;' : 'background:#f9fafb;'">
                            <td class="px-3 py-2 whitespace-nowrap font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="yr.year"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#15803d;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.principal)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#c2410c;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.interest)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.total)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#1d4ed8;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.serviced)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right" style="color:#dc2626;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.unserviced)"></td>
                            <td class="px-3 py-2 whitespace-nowrap text-right font-semibold" style="color:#111827;border-bottom:1px solid #e5e7eb;" x-text="formatNumber(yr.balance)"></td>
                        </tr>
                    </template>
                    <!-- Final total row -->
                    <tr x-show="(loan?.schedules || []).length > 0" style="background:#f3f4f6;font-weight:700;">
                        <td class="px-3 py-3 text-sm font-bold" style="color:#92400e;border-top:2px solid #16a34a;">Total</td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#15803d;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().principal)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#c2410c;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().interest)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#111827;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().total)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#1d4ed8;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().serviced)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#dc2626;border-top:2px solid #16a34a;" x-text="formatNumber(scheduleTotals().unserviced)"></td>
                        <td class="px-3 py-3 text-sm text-right font-bold" style="color:#111827;border-top:2px solid #16a34a;">0.00</td>
                    </tr>
                    <tr x-show="!(loan?.schedules || []).length">
                        <td colspan="7" class="px-5 py-6 text-sm text-center" style="color:#6b7280;background:#ffffff;">{{ __('messages.no_schedules') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Decision Modal -->
    <div x-show="decisionModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-medium" x-text="decisionModal.action==='approve' ? '{{ __('messages.approve_loan_stage') }}' : '{{ __('messages.reject_loan_stage') }}'"></h3>
            </div>
            <div class="px-6 py-4 space-y-3">
                <label class="block text-sm font-medium text-gray-700">{{ __('messages.comment') }}</label>
                <textarea x-model="decisionModal.comment" rows="4" class="w-full border rounded-md p-2"></textarea>
                <p class="text-xs text-gray-500">{{ __('messages.comment_audit_hint') }}</p>
                <div x-show="decisionModal.error" class="text-sm text-red-600" x-text="decisionModal.error"></div>
            </div>
            <div class="px-6 py-4 border-t flex justify-end space-x-2">
                <button @click="decisionModal.open=false" class="px-3 py-2 text-sm bg-gray-100 text-gray-800 rounded-md">{{ __('messages.cancel') }}</button>
                <button @click="submitDecision()" :disabled="decisionModal.loading" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md disabled:opacity-50">{{ __('messages.submit') }}</button>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div x-show="historyModal.open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-medium">{{ __('messages.approval_history') }}</h3>
            </div>
            <div class="px-6 py-4">
                <div x-show="historyModal.loading" class="text-sm text-gray-500">{{ __('messages.loading_history') }}</div>
                <div x-show="!historyModal.loading">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.stage') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.loan_status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.by') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.at') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('messages.comment') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="r in historyModal.records" :key="r.id">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="formatStage(r.stage)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="(r.status || '-').toUpperCase()"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="r.user?.name || '-' "></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="formatDate(r.decided_at)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap" x-text="r.comment || ''"></td>
                                </tr>
                            </template>
                            <tr x-show="!(historyModal.records || []).length">
                                <td colspan="5" class="px-6 py-4 text-sm text-gray-500">{{ __('messages.no_history') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-4 border-t text-right">
                <button @click="historyModal.open=false" class="px-3 py-2 text-sm bg-gray-100 text-gray-800 rounded-md">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush

@push('scripts')
<script>
function loanShow(id) {
    return {
        loanId: id,
        loan: @json($loan),
        loading: true,
        error: null,
        decisionModal: { open: false, action: 'approve', comment: '', error: null, loading: false },
        historyModal: { open: false, records: [], loading: false },
        scheduleTab: 'detail',
        syncingSchedules: false,
        async init() {
            try { await this.initSanctum(); } catch (e) {}
            await this.loadLoan();
            this.loading = false;
        },
        async initSanctum() {
            try {
                await fetch('/sanctum/csrf-cookie', {
                    credentials: 'include',
                    referrer: window.location.href,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
            } catch (e) { console.warn('CSRF cookie init failed:', e); }
        },
        fetchOptions(method = 'GET', body = null) {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (method !== 'GET') {
                headers['Content-Type'] = 'application/json';
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                if (token) headers['X-CSRF-TOKEN'] = token;
                try {
                    const xsrfPair = (document.cookie || '').split('; ').find(row => row.startsWith('XSRF-TOKEN='));
                    if (xsrfPair) {
                        const xsrf = decodeURIComponent(xsrfPair.split('=')[1] || '');
                        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
                    }
                } catch (_) {}
            }
            const opts = { method, headers, credentials: 'include', referrer: window.location.href };
            if (body) opts.body = body;
            return opts;
        },
        async loadLoan() {
            try {
                const response = await fetch(`/api/loans/${this.loanId}`, this.fetchOptions('GET'));
                const raw = await response.json();
                const data = raw?.data ?? raw;
                if (response.ok && data) {
                    this.loan = data;
                    this.error = null;
                } else {
                    console.error('Failed to load loan:', response.status, raw?.message);
                    this.error = raw?.message || 'Unable to load loan details';
                }
            } catch (e) {
                console.error('Error loading loan:', e);
                this.error = 'Unable to load loan details';
            }
        },
        canDecide() {
            const userRole = (window.AppUser?.role || '').toLowerCase();
            const userRoleSlugs = (window.AppUser?.roleSlugs || []).map(s => s.toLowerCase());
            const stage = this.loan?.approval_stage || null;
            const status = this.loan?.status || '';
            const stageStatus = this.loan?.approval_stage_status || 'pending';
            if (!stage || status !== 'pending' || stageStatus !== 'pending') return false;

            // Admin/Administrator can always approve
            if (userRole === 'admin' || userRole === 'administrator') return true;
            if (userRoleSlugs.includes('admin') || userRoleSlugs.includes('administrator')) return true;

            // Use dynamic approval pipeline from backend
            const map = window.AppUser?.approvalStageMap || {};
            const allowedRoles = (map[stage] || []).map(r => r.toLowerCase());

            // Check role column OR any RBAC slug
            return allowedRoles.some(r => r === userRole || userRoleSlugs.includes(r));
        },
        openDecisionModal(action) {
            this.decisionModal.action = action;
            this.decisionModal.comment = '';
            this.decisionModal.error = null;
            this.decisionModal.open = true;
        },
        async submitDecision() {
            try {
                this.decisionModal.error = null;
                this.decisionModal.loading = true;
                // Refresh CSRF cookie to satisfy Sanctum for stateful POST
                await this.initSanctum();

                const url = this.decisionModal.action === 'approve'
                    ? `/loans/${this.loanId}/stage/approve`
                    : `/loans/${this.loanId}/stage/reject`;
                const response = await fetch(url, this.fetchOptions('POST', JSON.stringify({ comment: this.decisionModal.comment || null })));
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data?.message || 'Decision failed');
                this.decisionModal.open = false;
                this.decisionModal.comment = '';
                await this.loadLoan();
            } catch (e) {
                console.error('Error submitting decision:', e);
                this.decisionModal.error = e.message || 'Could not submit decision';
            } finally {
                this.decisionModal.loading = false;
            }
        },
        openHistory() {
            this.historyModal.open = true;
            this.historyModal.records = [];
            this.historyModal.loading = true;
            this.loadHistory();
        },
        async loadHistory() {
            try {
                const response = await fetch(`/loans/${this.loanId}/approvals`, this.fetchOptions('GET'));
                const raw = await response.json();
                const records = raw?.data ?? raw;
                this.historyModal.records = Array.isArray(records) ? records : [];
            } catch (e) {
                console.error('Error loading approval history:', e);
                this.historyModal.records = [];
            } finally {
                this.historyModal.loading = false;
            }
        },
        formatStage(stage) {
            const map = { cso_review: 'CSO Review', loan_officer_review: 'Loan Officer Review', manager_review: 'Manager Review', gm_approval: 'GM Approval' };
            return map[stage] || (stage ? stage.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) : '-');
        },
        formatDate(value) { try { const d = new Date(value); if (isNaN(d)) return value || ''; const dd = String(d.getDate()).padStart(2,'0'); const mm = String(d.getMonth()+1).padStart(2,'0'); const yyyy = d.getFullYear(); return dd+'/'+mm+'/'+yyyy; } catch (_) { return value || ''; } },
        formatCurrency(value) { 
            try { 
                const formatted = new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS' }).format(Number(value || 0)); 
                return formatted.replace('TZS', 'TSHS'); 
            } catch (_) { 
                return `TSHS ${Number(value || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; 
            } 
        },
        getDocTypeLabel(type) {
            const labels = {
                'loan_contract': 'Loan Contract',
                'collateral_picture': 'Collateral Picture',
                'borrower_photo': 'Borrower Photo',
                'other': 'Other Attachment'
            };
            return labels[type] || 'Attachment';
        },
        formatFileSize(bytes) {
            if (!bytes) return '0 KB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        },
        async syncSchedules() {
            if (this.syncingSchedules) return;
            if (!confirm('This will re-calculate all schedule paid amounts from recorded repayments. Continue?')) return;
            this.syncingSchedules = true;
            try {
                const response = await fetch(`/api/loans/${this.loanId}/sync-schedules`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    alert(data.message);
                    await this.loadLoan();
                } else {
                    alert('Sync failed: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                alert('Network error during sync.');
            } finally {
                this.syncingSchedules = false;
            }
        },
        printSchedule() {
            window.print();
        },
        downloadScheduleCSV() {
            const schedules = this.loan?.schedules || [];
            if (!schedules.length) {
                alert('No schedule data to download');
                return;
            }
            
            const headers = ['#', 'Due Date', 'Event Type', 'Currency', 'Principal Amount', 'Interest Amount', 'Total Amount', 'Serviced Amount', 'Unserviced Amount', 'Status', 'Days in Arrears'];
            const rows = schedules.map(s => [
                s.installment_number,
                this.formatFullDate(s.due_date),
                'REPAYMENT',
                'TZS',
                s.principal_amount,
                s.interest_amount,
                s.total_amount,
                s.paid_amount || 0,
                this.scheduleUnserviced(s),
                this.scheduleStatusLabel(s),
                this.scheduleDaysArrears(s)
            ]);
            
            let csvContent = headers.join(',') + '\n';
            rows.forEach(row => {
                csvContent += row.join(',') + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `loan_${this.loan.loan_number}_schedule.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        async confirmDelete() {
            const clientName = `${this.loan?.client?.first_name || ''} ${this.loan?.client?.last_name || ''}`.trim();
            const principal = this.formatCurrency(this.loan?.principal || 0);
            if (!confirm(`Are you sure you want to delete this loan?\n\nBorrower: ${clientName}\nAmount: ${principal}\n\nThis action cannot be undone.`)) {
                return;
            }
            try {
                await this.initSanctum();
                const response = await fetch(`/loans/${this.loanId}`, this.fetchOptions('DELETE'));
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    alert('Loan deleted successfully');
                    window.location.href = '/loans';
                } else {
                    alert(data?.message || 'Failed to delete loan');
                }
            } catch (e) {
                console.error('Error deleting loan:', e);
                alert('Failed to delete loan: ' + e.message);
            }
        },
        detailScheduleRows() {
            return this.loan?.schedules || [];
        },
        monthlyScheduleRows() {
            return this.loan?.schedules || [];
        },
        yearlyScheduleRows() {
            const schedules = this.loan?.schedules || [];
            const map = {};
            let runningBalance = Number(this.loan?.principal || 0);
            schedules.forEach(s => {
                const year = new Date(s.due_date).getFullYear();
                if (!map[year]) map[year] = { year, principal: 0, interest: 0, total: 0, serviced: 0, unserviced: 0, balance: 0 };
                map[year].principal  += Number(s.principal_amount || 0);
                map[year].interest   += Number(s.interest_amount  || 0);
                map[year].total      += Number(s.total_amount     || 0);
                map[year].serviced   += Number(s.paid_amount      || 0);
                map[year].unserviced += this.scheduleUnserviced(s);
                runningBalance -= Number(s.principal_amount || 0);
                map[year].balance = Math.max(0, runningBalance);
            });
            return Object.values(map).sort((a, b) => a.year - b.year);
        },
        scheduleTotals() {
            const schedules = this.loan?.schedules || [];
            const serviced   = schedules.reduce((sum, s) => sum + Number(s.paid_amount || 0), 0);
            const total      = schedules.reduce((sum, s) => sum + Number(s.total_amount || 0), 0);
            const unserviced = schedules.reduce((sum, s) => sum + this.scheduleUnserviced(s), 0);
            const servicedCount   = schedules.filter(s => (s.status === 'paid' || s.status === 'completed')).length;
            const unservicedCount = schedules.filter(s => !(s.status === 'paid' || s.status === 'completed')).length;
            return {
                principal:      schedules.reduce((sum, s) => sum + Number(s.principal_amount || 0), 0),
                interest:       schedules.reduce((sum, s) => sum + Number(s.interest_amount  || 0), 0),
                total,
                serviced,
                unserviced,
                servicedCount,
                unservicedCount,
            };
        },
        formatFullDate(value) {
            try {
                const d = new Date(value);
                if (isNaN(d)) return value || '';
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yyyy = d.getFullYear();
                return `${dd}/${mm}/${yyyy}`;
            } catch (_) { return value || ''; }
        },
        scheduleUnserviced(s) {
            const total = Number(s.total_amount || 0);
            const paid  = Number(s.paid_amount  || 0);
            return Math.max(0, total - paid);
        },
        scheduleStatusLabel(s) {
            const st = (s.status || '').toLowerCase();
            if (st === 'paid' || st === 'completed') return 'PAID';
            return 'UNPAID';
        },
        scheduleStatusStyle(s) {
            const label = this.scheduleStatusLabel(s);
            const base = 'padding:2px 8px;border-radius:4px;font-weight:700;letter-spacing:0.06em;';
            if (label === 'PAID')   return base + 'background:#16a34a;color:#fff;';
            return base + 'background:#dc2626;color:#fff;';
        },
        scheduleDaysArrears(s) {
            const st = (s.status || '').toLowerCase();
            if (st === 'paid' || st === 'completed') return 0;
            const today = new Date(); today.setHours(0,0,0,0);
            const due   = new Date(s.due_date); due.setHours(0,0,0,0);
            const diff  = Math.floor((today - due) / 86400000);
            return diff > 0 ? diff : 0;
        },
        formatMonthYear(value) {
            try {
                const d = new Date(value);
                if (isNaN(d)) return value || '';
                return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            } catch (_) { return value || ''; }
        },
        formatNumber(value) {
            try {
                return Number(value || 0).toLocaleString('en-TZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } catch (_) { return Number(value || 0).toFixed(2); }
        },
        scheduleDetailLabel() {
            const s = (this.loan?.repayment_schedule || 'monthly').toLowerCase();
            if (s === 'daily')   return 'Daily breakdown';
            if (s === 'weekly')  return 'Weekly breakdown';
            if (s === 'yearly')  return 'Yearly breakdown';
            return 'Monthly breakdown';
        },
        scheduleDetailColLabel() {
            const s = (this.loan?.repayment_schedule || 'monthly').toLowerCase();
            if (s === 'daily')   return 'Date';
            if (s === 'weekly')  return 'Week';
            if (s === 'yearly')  return 'Year';
            return 'Month';
        },
        formatScheduleDate(value) {
            try {
                const d = new Date(value);
                if (isNaN(d)) return value || '';
                const s = (this.loan?.repayment_schedule || 'monthly').toLowerCase();
                if (s === 'daily') {
                    return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
                }
                if (s === 'weekly') {
                    const weekStart = new Date(d);
                    weekStart.setDate(d.getDate() - d.getDay());
                    const weekEnd = new Date(weekStart);
                    weekEnd.setDate(weekStart.getDate() + 6);
                    const fmt = dt => dt.toLocaleDateString('en-US', { day: '2-digit', month: 'short' });
                    return `${fmt(weekStart)} – ${fmt(weekEnd)}, ${d.getFullYear()}`;
                }
                if (s === 'yearly') {
                    return d.getFullYear().toString();
                }
                return d.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            } catch (_) { return value || ''; }
        },
    };
}
</script>
@endpush
@endsection