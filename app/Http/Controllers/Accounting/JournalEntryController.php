<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\ChartOfAccount;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    protected JournalEntryService $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $type = $request->get('type');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = JournalEntry::with(['createdBy', 'approvedBy'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('entry_type', $type);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('entry_date', [$startDate, $endDate]);
        }

        $entries = $query->paginate(25);
        $statuses = JournalEntry::getStatuses();
        $entryTypes = JournalEntry::getEntryTypes();

        return view('accounting.journal-entries.index', compact('entries', 'statuses', 'entryTypes', 'status', 'type', 'startDate', 'endDate'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::active()
            ->where('allow_manual_entry', true)
            ->orderBy('account_code')
            ->get();

        return view('accounting.journal-entries.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        $totalDebit = collect($validated['lines'])->sum('debit_amount');
        $totalCredit = collect($validated['lines'])->sum('credit_amount');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()->withErrors(['lines' => 'Total debits must equal total credits.']);
        }

        try {
            $entry = $this->journalService->createEntry([
                'entry_date' => $validated['entry_date'],
                'entry_type' => 'manual',
                'description' => $validated['description'],
                'status' => 'pending_approval',
            ], $validated['lines']);

            return redirect()->route('accounting.journal-entries.show', $entry)
                ->with('success', 'Journal entry created and submitted for approval.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(JournalEntry $journalEntry)
    {
        $journalEntry->load(['lines.account', 'createdBy', 'approvedBy', 'postedBy', 'fiscalYear', 'period']);
        
        return view('accounting.journal-entries.show', compact('journalEntry'));
    }

    public function edit(JournalEntry $journalEntry)
    {
        if (!in_array($journalEntry->status, ['draft', 'rejected'])) {
            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('error', 'Only draft or rejected entries can be edited.');
        }

        $journalEntry->load('lines');
        $accounts = ChartOfAccount::active()
            ->where('allow_manual_entry', true)
            ->orderBy('account_code')
            ->get();

        return view('accounting.journal-entries.edit', compact('journalEntry', 'accounts'));
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        if (!in_array($journalEntry->status, ['draft', 'rejected'])) {
            return redirect()->route('accounting.journal-entries.show', $journalEntry)
                ->with('error', 'Only draft or rejected entries can be edited.');
        }

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.debit_amount' => 'nullable|numeric|min:0',
            'lines.*.credit_amount' => 'nullable|numeric|min:0',
        ]);

        $totalDebit = collect($validated['lines'])->sum('debit_amount');
        $totalCredit = collect($validated['lines'])->sum('credit_amount');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()->withErrors(['lines' => 'Total debits must equal total credits.']);
        }

        $journalEntry->update([
            'entry_date' => $validated['entry_date'],
            'description' => $validated['description'],
            'status' => 'pending_approval',
        ]);

        $journalEntry->lines()->delete();

        foreach ($validated['lines'] as $line) {
            $journalEntry->lines()->create([
                'tenant_id' => $journalEntry->tenant_id,
                'account_id' => $line['account_id'],
                'description' => $line['description'] ?? null,
                'debit_amount' => $line['debit_amount'] ?? 0,
                'credit_amount' => $line['credit_amount'] ?? 0,
            ]);
        }

        $journalEntry->calculateTotals();

        return redirect()->route('accounting.journal-entries.show', $journalEntry)
            ->with('success', 'Journal entry updated and resubmitted for approval.');
    }

    public function approve(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'pending_approval') {
            return back()->with('error', 'Only pending entries can be approved.');
        }

        try {
            $this->journalService->approveEntry($journalEntry, auth()->id());
            return back()->with('success', 'Journal entry approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function post(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'approved') {
            return back()->with('error', 'Only approved entries can be posted.');
        }

        try {
            $this->journalService->postEntry($journalEntry, auth()->id());
            return back()->with('success', 'Journal entry posted to general ledger.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, JournalEntry $journalEntry)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        if ($journalEntry->status !== 'pending_approval') {
            return back()->with('error', 'Only pending entries can be rejected.');
        }

        $this->journalService->rejectEntry($journalEntry, auth()->id(), $request->reason);

        return back()->with('success', 'Journal entry rejected.');
    }

    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'posted') {
            return back()->with('error', 'Only posted entries can be reversed.');
        }

        try {
            $reversalEntry = $this->journalService->reverseEntry(
                $journalEntry,
                auth()->id(),
                $request->get('reason')
            );

            return redirect()->route('accounting.journal-entries.show', $reversalEntry)
                ->with('success', 'Reversal entry created. Please review and post.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pending()
    {
        $entries = JournalEntry::with(['createdBy'])
            ->where('status', 'pending_approval')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('accounting.journal-entries.pending', compact('entries'));
    }
}
