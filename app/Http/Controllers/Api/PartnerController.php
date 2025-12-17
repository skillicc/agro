<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Investment;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        $partners = Partner::withCount('investments')->get();
        return response()->json($partners);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:partner,shareholder',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'share_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $partner = Partner::create($request->all());
        return response()->json($partner, 201);
    }

    public function show(Partner $partner)
    {
        $partner->load(['investments' => function ($q) {
            $q->latest()->with(['project', 'creator']);
        }]);
        return response()->json($partner);
    }

    public function update(Request $request, Partner $partner)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:partner,shareholder',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'share_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $partner->update($request->all());
        return response()->json($partner);
    }

    public function destroy(Partner $partner)
    {
        $partner->delete();
        return response()->json(['message' => 'Partner deleted successfully']);
    }

    public function addTransaction(Request $request, Partner $partner)
    {
        $request->validate([
            'type' => 'required|in:investment,withdrawal',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'payment_method' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $investment = Investment::create([
            'partner_id' => $partner->id,
            'project_id' => $request->project_id,
            'amount' => $request->amount,
            'date' => $request->date,
            'type' => $request->type,
            'payment_method' => $request->payment_method,
            'reference_no' => $request->reference_no,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($investment->load(['partner', 'project', 'creator']), 201);
    }

    public function transactions(Request $request)
    {
        $query = Investment::with(['partner', 'project', 'creator']);

        if ($request->partner_id) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }
}
