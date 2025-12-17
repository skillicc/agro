<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::withCount('payments')->orderBy('loan_date', 'desc')->get();
        return response()->json($loans);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lender_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0',
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date',
            'tenure_months' => 'nullable|integer|min:1',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $loan = Loan::create([
            ...$request->all(),
            'outstanding_balance' => $request->principal_amount,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($loan, 201);
    }

    public function show(Loan $loan)
    {
        $loan->load(['payments' => function ($q) {
            $q->latest()->with('creator');
        }]);
        return response()->json($loan);
    }

    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'lender_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'interest_rate' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'tenure_months' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $loan->update($request->only(['lender_name', 'phone', 'address', 'interest_rate', 'due_date', 'tenure_months', 'note']));
        return response()->json($loan);
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();
        return response()->json(['message' => 'Loan deleted successfully']);
    }

    public function addPayment(Request $request, Loan $loan)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'reference_no' => $request->reference_no,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($payment->load(['loan', 'creator']), 201);
    }
}
