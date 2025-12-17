<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DayTermInvestment;
use App\Models\DayTermPayment;
use Illuminate\Http\Request;

class DayTermInvestmentController extends Controller
{
    public function index()
    {
        $investments = DayTermInvestment::withCount('payments')->orderBy('start_date', 'desc')->get();
        return response()->json($investments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'investor_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'daily_amount' => 'required|numeric|min:0',
            'total_days' => 'required|integer|min:1',
            'start_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $endDate = \Carbon\Carbon::parse($request->start_date)->addDays($request->total_days - 1);
        $totalAmount = $request->daily_amount * $request->total_days;

        $investment = DayTermInvestment::create([
            'investor_name' => $request->investor_name,
            'phone' => $request->phone,
            'daily_amount' => $request->daily_amount,
            'total_days' => $request->total_days,
            'start_date' => $request->start_date,
            'end_date' => $endDate,
            'total_amount' => $totalAmount,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($investment, 201);
    }

    public function show(DayTermInvestment $dayTermInvestment)
    {
        $dayTermInvestment->load(['payments' => function ($q) {
            $q->latest()->with('creator');
        }]);
        return response()->json($dayTermInvestment);
    }

    public function update(Request $request, DayTermInvestment $dayTermInvestment)
    {
        $request->validate([
            'investor_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'return_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,completed,cancelled',
            'note' => 'nullable|string',
        ]);

        $dayTermInvestment->update($request->only(['investor_name', 'phone', 'return_amount', 'status', 'note']));
        return response()->json($dayTermInvestment);
    }

    public function destroy(DayTermInvestment $dayTermInvestment)
    {
        $dayTermInvestment->delete();
        return response()->json(['message' => 'Day term investment deleted successfully']);
    }

    public function addPayment(Request $request, DayTermInvestment $dayTermInvestment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'day_number' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $payment = DayTermPayment::create([
            'day_term_investment_id' => $dayTermInvestment->id,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'day_number' => $request->day_number,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($payment->load(['dayTermInvestment', 'creator']), 201);
    }
}
