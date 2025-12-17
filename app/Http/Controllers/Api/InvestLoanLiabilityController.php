<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestLoanLiability;
use Illuminate\Http\Request;

class InvestLoanLiabilityController extends Controller
{
    public function index()
    {
        $items = InvestLoanLiability::orderBy('date', 'desc')->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:partner,shareholder,investment_day_term,loan,account_payable,account_receivable',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $item = InvestLoanLiability::create($request->all());

        return response()->json($item, 201);
    }

    public function show(InvestLoanLiability $investLoanLiability)
    {
        return response()->json($investLoanLiability);
    }

    public function update(Request $request, InvestLoanLiability $investLoanLiability)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:partner,shareholder,investment_day_term,loan,account_payable,account_receivable',
            'amount' => 'nullable|numeric|min:0',
            'date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $investLoanLiability->update($request->all());

        return response()->json($investLoanLiability);
    }

    public function destroy(InvestLoanLiability $investLoanLiability)
    {
        $investLoanLiability->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }

    public function summary()
    {
        $summary = [
            'partner' => InvestLoanLiability::where('type', 'partner')->where('status', 'active')->sum('amount'),
            'shareholder' => InvestLoanLiability::where('type', 'shareholder')->where('status', 'active')->sum('amount'),
            'investment_day_term' => InvestLoanLiability::where('type', 'investment_day_term')->where('status', 'active')->sum('amount'),
            'loan' => InvestLoanLiability::where('type', 'loan')->where('status', 'active')->sum('amount'),
            'account_payable' => InvestLoanLiability::where('type', 'account_payable')->where('status', 'active')->sum('amount'),
            'account_receivable' => InvestLoanLiability::where('type', 'account_receivable')->where('status', 'active')->sum('amount'),
        ];

        return response()->json($summary);
    }
}
