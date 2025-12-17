<?php

namespace App\Http\Controllers\Api;

use App\Models\AccountsPayable;
use App\Models\AccountsPayablePayment;
use Illuminate\Http\Request;

class AccountsPayableController
{
    public function index()
    {
        return AccountsPayable::with(['supplier', 'purchase', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show($id)
    {
        return AccountsPayable::with(['supplier', 'purchase', 'payments'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'total_amount' => 'required|numeric|min:0',
            'purchase_date' => 'required|date',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $validated['outstanding_amount'] = $validated['total_amount'];
        $validated['created_by'] = auth()->id();

        return AccountsPayable::create($validated);
    }

    public function update(Request $request, $id)
    {
        $ap = AccountsPayable::findOrFail($id);

        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $ap->update($validated);
        return $ap;
    }

    public function destroy($id)
    {
        AccountsPayable::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Payment methods
    public function addPayment(Request $request, $id)
    {
        $ap = AccountsPayable::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $ap->outstanding_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,check,mobile_banking',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $payment = $ap->payments()->create($validated);
        $ap->updateBalance();

        return $payment;
    }

    public function getPayments($id)
    {
        return AccountsPayable::findOrFail($id)->payments()->get();
    }

    public function deletePayment($paymentId)
    {
        $payment = AccountsPayablePayment::findOrFail($paymentId);
        $ap = $payment->accountsPayable;
        $payment->delete();
        $ap->updateBalance();

        return response()->json(['message' => 'Payment deleted']);
    }

    // Supplier outstanding
    public function supplierOutstanding($supplierId)
    {
        $payables = AccountsPayable::where('supplier_id', $supplierId)
            ->where('status', '!=', 'paid')
            ->with('payments')
            ->get();

        $total_outstanding = $payables->sum('outstanding_amount');

        return [
            'total_outstanding' => $total_outstanding,
            'payables' => $payables,
        ];
    }

    // Report - All outstanding
    public function outstandingReport()
    {
        $payables = AccountsPayable::where('outstanding_amount', '>', 0)
            ->with(['supplier', 'purchase'])
            ->orderBy('outstanding_amount', 'desc')
            ->get();

        $total = $payables->sum('outstanding_amount');

        return [
            'total_outstanding' => $total,
            'payables' => $payables,
        ];
    }
}
