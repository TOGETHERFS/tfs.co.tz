@extends('layouts.admin')

@section('title', 'SMS Wallet Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SMS Wallet Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to SMS Wallets
                        </a>
                        @if(isset($smsWallet))
                            <a href="{{ route('admin.sms-wallets.edit', $smsWallet->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($smsWallet))
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Wallet Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Wallet ID:</strong></td>
                                        <td>{{ $smsWallet->id }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tenant:</strong></td>
                                        <td>{{ $smsWallet->tenant->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Current Balance:</strong></td>
                                        <td>
                                            <span class="badge badge-{{ $smsWallet->balance > 0 ? 'success' : 'warning' }} badge-lg">
                                                {{ number_format($smsWallet->balance) }} SMS Credits
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            @if($smsWallet->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Created Date:</strong></td>
                                        <td>{{ $smsWallet->created_at ? $smsWallet->created_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Last Updated:</strong></td>
                                        <td>{{ $smsWallet->updated_at ? $smsWallet->updated_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Usage Statistics</h5>
                                @if(isset($stats))
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Total SMS Sent:</strong></td>
                                            <td>{{ number_format($stats['total_sent'] ?? 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>SMS This Month:</strong></td>
                                            <td>{{ number_format($stats['monthly_sent'] ?? 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Top-ups:</strong></td>
                                            <td>{{ number_format($stats['total_topups'] ?? 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Last Activity:</strong></td>
                                            <td>{{ $stats['last_activity'] ?? 'No activity' }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Average Daily Usage:</strong></td>
                                            <td>{{ number_format($stats['daily_average'] ?? 0) }} SMS</td>
                                        </tr>
                                    </table>
                                @else
                                    <p class="text-muted">No usage statistics available</p>
                                @endif
                            </div>
                        </div>

                        @if($smsWallet->balance <= 10)
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-warning">
                                        <h6><i class="fas fa-exclamation-triangle"></i> Low Balance Warning</h6>
                                        <p class="mb-0">This wallet has a low balance ({{ $smsWallet->balance }} SMS credits). Consider notifying the tenant to top up their account.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Recent Transactions</h5>
                                @if(isset($recentTransactions) && count($recentTransactions) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Balance After</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentTransactions as $transaction)
                                                    <tr>
                                                        <td>{{ $transaction->created_at ? $transaction->created_at->format('d M Y H:i') : 'N/A' }}</td>
                                                        <td>
                                                            @if($transaction->type == 'credit')
                                                                <span class="badge badge-success">Credit</span>
                                                            @elseif($transaction->type == 'debit')
                                                                <span class="badge badge-danger">Debit</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ ucfirst($transaction->type ?? 'Unknown') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($transaction->type == 'credit')
                                                                <span class="text-success">+{{ number_format($transaction->amount) }}</span>
                                                            @else
                                                                <span class="text-danger">-{{ number_format($transaction->amount) }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ number_format($transaction->balance_after ?? 0) }}</td>
                                                        <td>{{ $transaction->description ?? 'N/A' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="text-muted">No recent transactions found</p>
                                @endif
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <h5>Actions</h5>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.sms-wallets.edit', $smsWallet->id) }}" class="btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Wallet
                                    </a>
                                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addCreditsModal">
                                        <i class="fas fa-plus"></i> Add Credits
                                    </button>
                                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#deductCreditsModal">
                                        <i class="fas fa-minus"></i> Deduct Credits
                                    </button>
                                    @if($smsWallet->is_active)
                                        <button type="button" class="btn btn-danger" onclick="toggleWalletStatus({{ $smsWallet->id }}, false)">
                                            <i class="fas fa-ban"></i> Deactivate
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success" onclick="toggleWalletStatus({{ $smsWallet->id }}, true)">
                                            <i class="fas fa-check"></i> Activate
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Add Credits Modal -->
                        <div class="modal fade" id="addCreditsModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.sms-wallets.add-credits', $smsWallet->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Add SMS Credits</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="credits">Number of Credits</label>
                                                <input type="number" class="form-control" id="credits" name="credits" min="1" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="reason">Reason</label>
                                                <textarea class="form-control" id="reason" name="reason" rows="2" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Add Credits</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Deduct Credits Modal -->
                        <div class="modal fade" id="deductCreditsModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('admin.sms-wallets.deduct-credits', $smsWallet->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Deduct SMS Credits</h5>
                                            <button type="button" class="close" data-dismiss="modal">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label for="deduct_credits">Number of Credits</label>
                                                <input type="number" class="form-control" id="deduct_credits" name="credits" 
                                                       min="1" max="{{ $smsWallet->balance }}" required>
                                                <small class="form-text text-muted">Maximum: {{ $smsWallet->balance }} credits</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="deduct_reason">Reason</label>
                                                <textarea class="form-control" id="deduct_reason" name="reason" rows="2" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-warning">Deduct Credits</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5>SMS Wallet Not Found</h5>
                            <p>The requested SMS wallet could not be found.</p>
                            <a href="{{ route('admin.sms-wallets.index') }}" class="btn btn-primary">Back to SMS Wallets</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleWalletStatus(walletId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    const message = `Are you sure you want to ${action} this SMS wallet?`;
    
    if (confirm(message)) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/sms-wallets/${walletId}/toggle-status`;
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add method
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PATCH';
        form.appendChild(methodInput);
        
        // Add status
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'is_active';
        statusInput.value = activate ? '1' : '0';
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection