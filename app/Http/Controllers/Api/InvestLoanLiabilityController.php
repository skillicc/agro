<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvestLoanLiability;
use App\Models\InvestLoanLiabilityPayment;
use Illuminate\Http\Request;

class InvestLoanLiabilityController extends Controller
{
    public function index(Request $request)
    {
        $query = InvestLoanLiability::with(['payments'])
            ->withSum('sharePayments as total_share_paid', 'amount')
            ->withSum('profitWithdrawals as total_profit_withdrawn', 'amount')
            ->withSum('loanPayments as total_loan_paid', 'amount');

        // Filter by name
        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('date', 'desc')->get();

        // Calculate loan rest amount and total share value for each item
        $items->transform(function ($item) {
            if ($item->type === 'loan') {
                $item->loan_rest_amount = $item->total_payable - ($item->total_loan_paid ?? 0);
            }
            if (in_array($item->type, ['partner', 'shareholder'])) {
                $item->total_share_value = $item->total_share_value;
            }
            return $item;
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'partner_id' => 'nullable|string|max:50|unique:invest_loan_liabilities,partner_id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'type' => 'required|in:investor,partner,shareholder,investment_day_term,loan,account_payable,account_receivable',
            'amount' => 'nullable|numeric|min:0',
            'share_value' => 'nullable|numeric|min:0',
            'number_of_shares' => 'nullable|integer|min:0',
            'face_value_per_share' => 'nullable|numeric|min:0',
            'premium_value_per_share' => 'nullable|numeric|min:0',
            'honorarium' => 'nullable|numeric|min:0',
            'honorarium_type' => 'nullable|in:monthly,yearly',
            'invest_period' => 'nullable|integer|in:4,6,12,18,24',
            'profit_rate' => 'nullable|numeric|min:0|max:100',
            'loan_type' => 'nullable|in:with_profit,without_profit',
            'received_amount' => 'nullable|numeric|min:0',
            'total_payable' => 'nullable|numeric|min:0',
            'receive_date' => 'nullable|date',
            'date' => 'nullable|date',
            'appoint_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'withdraw_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $data = $request->all();

        // Auto-calculate due_date for investor based on appoint_date and invest_period
        if ($request->type === 'investor' && $request->appoint_date && $request->invest_period) {
            $data['due_date'] = date('Y-m-d', strtotime($request->appoint_date . ' + ' . $request->invest_period . ' months'));
        }

        // For loan without profit, set received_amount to 0 if not provided
        if ($request->type === 'loan' && $request->loan_type === 'without_profit') {
            if (!isset($data['received_amount'])) {
                $data['received_amount'] = 0;
            }
        }

        $item = InvestLoanLiability::create($data);

        return response()->json($item, 201);
    }

    public function show(InvestLoanLiability $investLoanLiability)
    {
        $investLoanLiability->load(['payments' => function ($q) {
            $q->orderBy('date', 'desc');
        }]);

        $investLoanLiability->total_share_paid = $investLoanLiability->sharePayments()->sum('amount');
        $investLoanLiability->total_profit_withdrawn = $investLoanLiability->profitWithdrawals()->sum('amount');

        return response()->json($investLoanLiability);
    }

    public function update(Request $request, InvestLoanLiability $investLoanLiability)
    {
        $request->validate([
            'partner_id' => 'nullable|string|max:50|unique:invest_loan_liabilities,partner_id,' . $investLoanLiability->id,
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'type' => 'nullable|in:investor,partner,shareholder,investment_day_term,loan,account_payable,account_receivable',
            'amount' => 'nullable|numeric|min:0',
            'share_value' => 'nullable|numeric|min:0',
            'number_of_shares' => 'nullable|integer|min:0',
            'face_value_per_share' => 'nullable|numeric|min:0',
            'premium_value_per_share' => 'nullable|numeric|min:0',
            'honorarium' => 'nullable|numeric|min:0',
            'honorarium_type' => 'nullable|in:monthly,yearly',
            'invest_period' => 'nullable|integer|in:4,6,12,18,24',
            'profit_rate' => 'nullable|numeric|min:0|max:100',
            'loan_type' => 'nullable|in:with_profit,without_profit',
            'received_amount' => 'nullable|numeric|min:0',
            'total_payable' => 'nullable|numeric|min:0',
            'receive_date' => 'nullable|date',
            'date' => 'nullable|date',
            'appoint_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'withdraw_date' => 'nullable|date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,completed,cancelled',
        ]);

        $data = $request->all();

        // Auto-calculate due_date for investor based on appoint_date and invest_period
        $type = $request->type ?? $investLoanLiability->type;
        $appointDate = $request->appoint_date ?? $investLoanLiability->appoint_date;
        $investPeriod = $request->invest_period ?? $investLoanLiability->invest_period;

        if ($type === 'investor' && $appointDate && $investPeriod) {
            $data['due_date'] = date('Y-m-d', strtotime($appointDate . ' + ' . $investPeriod . ' months'));
        }

        // For loan without profit, set received_amount to 0 if not provided
        $loanType = $request->loan_type ?? $investLoanLiability->loan_type;
        if ($type === 'loan' && $loanType === 'without_profit') {
            if (!isset($data['received_amount'])) {
                $data['received_amount'] = 0;
            }
        }

        $investLoanLiability->update($data);

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
            'investor' => InvestLoanLiability::where('type', 'investor')->where('status', 'active')->sum('amount'),
            'partner' => InvestLoanLiability::where('type', 'partner')->where('status', 'active')->sum('amount'),
            'shareholder' => InvestLoanLiability::where('type', 'shareholder')->where('status', 'active')->sum('amount'),
            'investment_day_term' => InvestLoanLiability::where('type', 'investment_day_term')->where('status', 'active')->sum('amount'),
            'loan' => InvestLoanLiability::where('type', 'loan')->where('status', 'active')->sum('amount'),
            'account_payable' => InvestLoanLiability::where('type', 'account_payable')->where('status', 'active')->sum('amount'),
            'account_receivable' => InvestLoanLiability::where('type', 'account_receivable')->where('status', 'active')->sum('amount'),
        ];

        return response()->json($summary);
    }

    /**
     * Add a payment (share payment, profit withdrawal, or honorarium payment)
     */
    public function addPayment(Request $request, InvestLoanLiability $investLoanLiability)
    {
        $request->validate([
            'type' => 'required|in:share_payment,profit_withdrawal,honorarium_payment,loan_payment',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'for_year' => 'nullable|integer|min:2000|max:2100',
            'for_period' => 'nullable|integer|in:4,6,12,18,24',
            'note' => 'nullable|string|max:255',
        ]);

        $payment = $investLoanLiability->payments()->create([
            'type' => $request->type,
            'amount' => $request->amount,
            'date' => $request->date,
            'for_year' => $request->for_year,
            'for_period' => $request->for_period,
            'note' => $request->note,
            'created_by' => auth()->id(),
        ]);

        // Update total amount if it's a share payment
        if ($request->type === 'share_payment') {
            $investLoanLiability->amount = $investLoanLiability->sharePayments()->sum('amount');
            $investLoanLiability->save();
        }

        // Update amount (paid) for loans - keep track of total paid
        if ($request->type === 'loan_payment') {
            $investLoanLiability->amount = $investLoanLiability->loanPayments()->sum('amount');
            $investLoanLiability->save();
        }

        return response()->json([
            'message' => 'Payment added successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Get all payments for an item
     */
    public function getPayments(InvestLoanLiability $investLoanLiability)
    {
        $payments = $investLoanLiability->payments()
            ->with('creator:id,name')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Update a payment
     */
    public function updatePayment(Request $request, InvestLoanLiabilityPayment $payment)
    {
        $request->validate([
            'type' => 'nullable|in:share_payment,profit_withdrawal,honorarium_payment,loan_payment',
            'amount' => 'nullable|numeric|min:0.01',
            'date' => 'nullable|date',
            'for_year' => 'nullable|integer|min:2000|max:2100',
            'for_period' => 'nullable|integer|in:4,6,12,18,24',
            'note' => 'nullable|string|max:255',
        ]);

        $investLoanLiability = $payment->investLoanLiability;
        $oldType = $payment->type;
        $oldAmount = $payment->amount;

        $payment->update($request->only(['type', 'amount', 'date', 'for_year', 'for_period', 'note']));

        // Update total amount if it's a share payment (type changed or amount changed)
        if ($payment->type === 'share_payment' || $oldType === 'share_payment') {
            $investLoanLiability->amount = $investLoanLiability->sharePayments()->sum('amount');
            $investLoanLiability->save();
        }

        // Update amount for loans (type changed or amount changed)
        if ($payment->type === 'loan_payment' || $oldType === 'loan_payment') {
            $investLoanLiability->amount = $investLoanLiability->loanPayments()->sum('amount');
            $investLoanLiability->save();
        }

        return response()->json([
            'message' => 'Payment updated successfully',
            'payment' => $payment,
        ]);
    }

    /**
     * Delete a payment
     */
    public function deletePayment(InvestLoanLiabilityPayment $payment)
    {
        $investLoanLiability = $payment->investLoanLiability;
        $paymentType = $payment->type;

        $payment->delete();

        // Update total amount if it was a share payment
        if ($paymentType === 'share_payment') {
            $investLoanLiability->amount = $investLoanLiability->sharePayments()->sum('amount');
            $investLoanLiability->save();
        }

        // Update amount for loans
        if ($paymentType === 'loan_payment') {
            $investLoanLiability->amount = $investLoanLiability->loanPayments()->sum('amount');
            $investLoanLiability->save();
        }

        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
