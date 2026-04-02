<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Land;
use Illuminate\Http\Request;

class LandController extends Controller
{
    public function index(Request $request)
    {
        $query = Land::query()->orderBy('name');

        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        if ($request->filled('project_id')) {
            $query->whereHas('projects', function ($projectQuery) use ($request) {
                $projectQuery->where('projects.id', $request->project_id);
            });
        }

        $lands = $query->with(['currentCultivation.project'])->get();

        return response()->json($lands);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'size' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $land = Land::create([
            ...$data,
            'unit' => $data['unit'] ?? 'acre',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($land, 201);
    }

    public function update(Request $request, Land $land)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'size' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $land->update($data);

        return response()->json($land);
    }

    public function destroy(Land $land)
    {
        if ($land->expenses()->exists() || $land->cultivations()->exists()) {
            return response()->json([
                'message' => 'This land already has ledger history and cannot be deleted.'
            ], 422);
        }

        $land->projects()->detach();
        $land->delete();

        return response()->json(['message' => 'Land deleted successfully']);
    }
}
