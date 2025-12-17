<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Production;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Production::with(['project', 'product', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $productions = $query->orderBy('date', 'desc')->get();

        return response()->json($productions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'cost' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $production = Production::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($production->load(['project', 'product', 'creator']), 201);
    }

    public function show(Production $production)
    {
        return response()->json($production->load(['project', 'product', 'creator']));
    }

    public function update(Request $request, Production $production)
    {
        $request->validate([
            'cost' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $production->update($request->only(['cost', 'date', 'note']));

        return response()->json($production->load(['project', 'product', 'creator']));
    }

    public function destroy(Production $production)
    {
        $production->delete();
        return response()->json(['message' => 'Production record deleted successfully']);
    }
}
