<?php

namespace App\Http\Controllers\Api;

use App\Models\AccountsReceivable;
use App\Models\AccountsReceivablePayment;
use Illuminate\Http\Request;

class AccountsReceivableController
{
    public function index()
    {
        return AccountsReceivable::with(['customer', 'sale', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show($id)
    {
        return AccountsReceivable::with(['customer', 'sale', 'payments'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'total_amount' => 'required|numeric|min:0',
            'sale_date' => 'required|date',
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $validated['outstanding_amount'] = $validated['total_amount'];
        $validated['created_by'] = auth()->id();

        return AccountsReceivable::create($validated);
    }

    public function update(Request $request, $id)
    {
        $ar = AccountsReceivable::findOrFail($id);

        $validated = $request->validate([
            'due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $ar->update($validated);
        return $ar;
    }

    public function destroy($id)
    {
        AccountsReceivable::destroy($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Payment methods
    public function addPayment(Request $request, $id)
    {
        $ar = AccountsReceivable::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:' . $ar->outstanding_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank,check,mobile_banking',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $payment = $ar->payments()->create($validated);
        $ar->updateBalance();

        return $payment;
    }

    public function getPayments($id)
    {
        return AccountsReceivable::findOrFail($id)->payments()->get();
    }

    public function deletePayment($paymentId)
    {
        $payment = AccountsReceivablePayment::findOrFail($paymentId);
        $ar = $payment->accountsReceivable;
        $payment->delete();
        $ar->updateBalance();

        return response()->json(['message' => 'Payment deleted']);
    }

    // Customer outstanding
    public function customerOutstanding($customerId)
    {
        $receivables = AccountsReceivable::where('customer_id', $customerId)
            ->where('status', '!=', 'paid')
            ->with('payments')
            ->get();

        $total_outstanding = $receivables->sum('outstanding_amount');

        return [
            'total_outstanding' => $total_outstanding,
            'receivables' => $receivables,
        ];
    }

    // Report - All outstanding
    public function outstandingReport()
    {
        $receivables = AccountsReceivable::where('outstanding_amount', '>', 0)
            ->with(['customer', 'sale'])
            ->orderBy('outstanding_amount', 'desc')
            ->get();

        $total = $receivables->sum('outstanding_amount');

        return [
            'total_outstanding' => $total,
            'receivables' => $receivables,
        ];
    }
}
