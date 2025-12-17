<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Damage;
use Illuminate\Http\Request;

class DamageController extends Controller
{
    public function index(Request $request)
    {
        $query = Damage::with(['project', 'product', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $damages = $query->orderBy('date', 'desc')->get();

        return response()->json($damages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'value' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $damage = Damage::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($damage->load(['project', 'product', 'creator']), 201);
    }

    public function show(Damage $damage)
    {
        return response()->json($damage->load(['project', 'product', 'creator']));
    }

    public function update(Request $request, Damage $damage)
    {
        $request->validate([
            'value' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $damage->update($request->only(['value', 'date', 'reason']));

        return response()->json($damage->load(['project', 'product', 'creator']));
    }

    public function destroy(Damage $damage)
    {
        $damage->delete();
        return response()->json(['message' => 'Damage record deleted successfully']);
    }
}
