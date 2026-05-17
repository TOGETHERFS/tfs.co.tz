@extends('layouts.app')

@section('title', __('messages.edit_loan'))
@section('page-title', __('messages.edit_loan'))

@section('content')
<div x-data="loanEdit()" x-init="init()" class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ __('messages.edit_loan') }} #{{ $loan->loan_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('messages.update_loan_details') }}</p>
        </div>
        <a href="{{ route('loans.show', $loan->id) }}" class="px-4 py-2 text-sm bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
            {{ __('messages.back_to_loan') }}
        </a>
    </div>

    <!-- Borrower Info (Read-only) -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('messages.borrower_information') }}</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-gray-500">{{ __('messages.name') }}:</span>
                <span class="ml-2 text-gray-900">{{ $loan->client->first_name }} {{ $loan->client->last_name }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">{{ __('messages.phone_number') }}:</span>
                <span class="ml-2 text-gray-900">{{ $loan->client->phone ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">{{ __('messages.product') }}:</span>
                <span class="ml-2 text-gray-900">
                    <select x-model="form.product_id" @change="onProductChange()"
                            class="mt-0 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-repayment-type="{{ $product->repayment_type ?? 'amortized' }}"
                                data-interest-rate="{{ $product->interest_rate }}"
                                data-interest-type="{{ $product->interest_type }}"
                            >{{ $product->name }}</option>
                        @endforeach
                    </select>
                </span>
                <p x-show="productChanged" class="mt-1 text-xs text-orange-600 ml-2">
                    &#9888; Changing the product will regenerate the repayment schedule.
                </p>
            </div>
            <div>
                <span class="font-medium text-gray-500">{{ __('messages.created_at') }}:</span>
                <span class="ml-2 text-gray-900">{{ $loan->created_at->format('M d, Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <form @submit.prevent="submitForm()" class="bg-white shadow-sm rounded-lg border border-gray-200 p-6 space-y-6">
        <h3 class="text-lg font-medium text-gray-900">{{ __('messages.loan_details') }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Principal -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.principal_amount') }} (TZS)</label>
                <input type="number" x-model="form.principal" step="1" min="1"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.principal" class="mt-1 text-sm text-red-600" x-text="errors.principal"></p>
            </div>

            <!-- Term -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span x-text="form.repayment_schedule === 'daily' ? 'Term (Days)' : (form.repayment_schedule === 'weekly' ? 'Term (Weeks)' : 'Term (Months)')"></span>
                </label>
                <input type="number" x-model="form.term" min="1"
                       :placeholder="form.repayment_schedule === 'daily' ? 'Enter number of days' : (form.repayment_schedule === 'weekly' ? 'Enter number of weeks' : 'Enter number of months')"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.term" class="mt-1 text-sm text-red-600" x-text="errors.term"></p>
            </div>

            <!-- Repayment Schedule -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.repayment_schedule') }}</label>
                <select x-model="form.repayment_schedule"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="daily">{{ __('messages.daily') }}</option>
                    <option value="weekly">{{ __('messages.weekly') }}</option>
                    <option value="monthly">{{ __('messages.monthly') }}</option>
                </select>
                <p x-show="errors.repayment_schedule" class="mt-1 text-sm text-red-600" x-text="errors.repayment_schedule"></p>
            </div>

            <!-- Interest Rate -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.interest_rate') }} (%)</label>
                <input type="number" x-model="form.interest_rate" step="0.01" min="0" max="100"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.interest_rate" class="mt-1 text-sm text-red-600" x-text="errors.interest_rate"></p>
            </div>

            <!-- Processing Fee Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.processing_fee_type') }}</label>
                <select x-model="form.processing_fee_type"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="percentage">{{ __('messages.percentage') }} (%)</option>
                    <option value="fixed">{{ __('messages.fixed_amount') }} (TZS)</option>
                </select>
            </div>

            <!-- Processing Fee -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <span x-text="form.processing_fee_type === 'percentage' ? 'Processing Fee (%)' : 'Processing Fee (TZS)'"></span>
                </label>
                <input type="number" x-model="form.processing_fee_rate" step="0.01" min="0"
                       :placeholder="form.processing_fee_type === 'percentage' ? 'Enter percentage' : 'Enter amount in TZS'"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.processing_fee_rate" class="mt-1 text-sm text-red-600" x-text="errors.processing_fee_rate"></p>
            </div>

            <!-- First Payment Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.first_payment_date') }}</label>
                <input type="date" x-model="form.first_payment_date"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.first_payment_date" class="mt-1 text-sm text-red-600" x-text="errors.first_payment_date"></p>
            </div>

            <!-- Disbursed Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.disbursed_date') }}</label>
                <input type="date" x-model="form.disbursed_at"
                       class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <p x-show="errors.disbursed_at" class="mt-1 text-sm text-red-600" x-text="errors.disbursed_at"></p>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.loan_status') }}</label>
                <select x-model="form.status"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="pending">{{ __('messages.pending') }}</option>
                    <option value="approved">{{ __('messages.approved') }}</option>
                    <option value="rejected">{{ __('messages.rejected') }}</option>
                    <option value="disbursed">{{ __('messages.disbursed') }}</option>
                    <option value="active">{{ __('messages.active') }}</option>
                    <option value="completed">{{ __('messages.completed') }}</option>
                    <option value="defaulted">{{ __('messages.defaulted') }}</option>
                </select>
            </div>

            <!-- Purpose -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.purpose') }}</label>
                <select x-model="form.purpose"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">{{ __('messages.select_purpose') }}</option>
                    <option value="business">{{ __('messages.business') }}</option>
                    <option value="agriculture">{{ __('messages.agriculture') }}</option>
                    <option value="education">{{ __('messages.education') }}</option>
                    <option value="healthcare">{{ __('messages.healthcare') }}</option>
                    <option value="housing">{{ __('messages.housing') }}</option>
                    <option value="emergency">{{ __('messages.emergency') }}</option>
                    <option value="other">{{ __('messages.other') }}</option>
                </select>
            </div>
        </div>

        <!-- Attachments Section -->
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        {{ __('messages.loan_attachments') }}
                    </label>
                    <p class="text-xs text-gray-500">{{ __('messages.attachments_hint') }}</p>
                </div>
                <button type="button" @click="addAttachment()" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        :disabled="attachments.length >= 10">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('messages.add_attachment') }}
                </button>
            </div>
            
            <div class="space-y-3">
                <template x-for="(att, index) in attachments" :key="index">
                    <div class="flex items-center gap-3 bg-white p-3 rounded-md border border-gray-200">
                        <div class="flex-1">
                            <select x-model="att.type" class="block w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 mb-2">
                                <option value="loan_contract">Loan Contract</option>
                                <option value="spouse_consent">Spouse Consent</option>
                                <option value="guarantor_form">Guarantor Form</option>
                                <option value="collateral">Collateral</option>
                                <option value="other">Other</option>
                            </select>
                            <input type="file" 
                                   @change="handleFileSelect($event, index)"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer">
                            <p x-show="att.error" class="mt-1 text-xs text-red-600" x-text="att.error"></p>
                            <p x-show="att.file && !att.error" class="mt-1 text-xs text-green-600">
                                <svg class="inline w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span x-text="att.file?.name"></span> 
                                (<span x-text="formatFileSize(att.file?.size)"></span>)
                            </p>
                        </div>
                        <button type="button" @click="removeAttachment(index)" class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </template>
                
                <div x-show="attachments.length === 0" class="text-center py-4 text-sm text-gray-500">
                    {{ __('messages.no_attachments') }}
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.notes') }}</label>
            <textarea x-model="form.notes" rows="3"
                      class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                      placeholder="{{ __('messages.additional_notes') }}"></textarea>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
            <a href="{{ route('loans.show', $loan->id) }}" 
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit" :disabled="submitting"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!submitting">{{ __('messages.save_changes') }}</span>
                <span x-show="submitting">{{ __('messages.saving') }}...</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loanEdit() {
    return {
        form: {
            product_id: {{ $loan->product_id ?? 'null' }},
            principal: {{ $loan->principal }},
            term: {{ $loan->term }},
            repayment_schedule: '{{ $loan->repayment_schedule ?? 'monthly' }}',
            interest_rate: {{ $loan->interest_rate }},
            processing_fee_type: '{{ $loan->processing_fee_type ?? 'percentage' }}',
            processing_fee_rate: {{ $loan->principal > 0 ? round(($loan->processing_fee / $loan->principal) * 100, 2) : 0 }},
            first_payment_date: '{{ $loan->first_payment_date?->format('Y-m-d') }}',
            disbursed_at: '{{ $loan->disbursed_at?->format('Y-m-d') ?? '' }}',
            status: '{{ $loan->status }}',
            purpose: '{{ $loan->purpose ?? '' }}',
            notes: @json($loan->notes ?? ''),
        },
        originalProductId: {{ $loan->product_id ?? 'null' }},
        productChanged: false,
        products: {!! json_encode($productsJson) !!},
        errors: {},
        submitting: false,
        attachments: [],

        async init() {
            try { await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' }); } catch (e) {}
        },

        onProductChange() {
            const selected = this.products.find(p => p.id == this.form.product_id);
            if (selected) {
                this.form.interest_rate = selected.interest_rate;
                this.form.processing_fee_type = selected.processing_fee_type;
                // Reset fee rate based on type
                if (selected.processing_fee_type === 'percentage') {
                    this.form.processing_fee_rate = selected.processing_fee;
                } else {
                    this.form.processing_fee_rate = selected.processing_fee;
                }
            }
            this.productChanged = this.form.product_id != this.originalProductId;
        },

        async submitForm() {
            this.errors = {};
            this.submitting = true;

            try {
                const response = await fetch('/loans/{{ $loan->id }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form),
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (response.ok) {
                    try { 
                        await this.uploadAttachments({{ $loan->id }}); 
                    } catch (e) { 
                        console.error('Attachment upload failed:', e); 
                    }
                    alert('Loan updated successfully');
                    window.location.href = '/loans/{{ $loan->id }}';
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        alert(data.message || 'Error updating loan');
                    }
                }
            } catch (error) {
                console.error('Error updating loan:', error);
                alert('Error updating loan');
            } finally {
                this.submitting = false;
            }
        },

        // Attachment management methods
        addAttachment() {
            if (this.attachments.length < 10) {
                this.attachments.push({ type: 'loan_contract', file: null, error: null });
            }
        },

        removeAttachment(index) {
            this.attachments.splice(index, 1);
        },

        handleFileSelect(event, index) {
            const file = event.target.files[0];
            if (!file) {
                this.attachments[index].file = null;
                this.attachments[index].error = null;
                return;
            }

            const maxSize = 1000 * 1024 * 1024; // 1000MB
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            
            if (!allowedTypes.includes(file.type)) {
                this.attachments[index].error = 'Only PDF, JPG, and PNG files are allowed';
                this.attachments[index].file = null;
                return;
            }
            
            if (file.size > maxSize) {
                this.attachments[index].error = `File exceeds 1000MB limit (${this.formatFileSize(file.size)})`;
                this.attachments[index].file = null;
                return;
            }

            this.attachments[index].file = file;
            this.attachments[index].error = null;
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 KB';
            if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
            if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
            return bytes + ' bytes';
        },

        async uploadAttachments(loanId) {
            const validAttachments = this.attachments.filter(a => a.file && !a.error);
            if (validAttachments.length === 0) return;

            const formData = new FormData();
            validAttachments.forEach((att, idx) => {
                formData.append('files[]', att.file);
                formData.append('attachment_types[]', att.type);
            });

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const resp = await fetch(`/loans/${loanId}/documents`, {
                method: 'POST',
                headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                body: formData,
                credentials: 'same-origin'
            });
            if (!resp.ok) {
                let err;
                try { err = await resp.json(); } catch (_) {}
                throw new Error(err?.message || 'Upload failed');
            }
        }
    };
}
</script>
@endpush
@endsection
