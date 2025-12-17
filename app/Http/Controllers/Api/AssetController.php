<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['project', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $assets = $query->orderBy('created_at', 'desc')->get();

        return response()->json($assets);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'condition' => 'nullable|in:good,fair,poor',
        ]);

        $asset = Asset::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json($asset->load(['project', 'creator']), 201);
    }

    public function show(Asset $asset)
    {
        return response()->json($asset->load(['project', 'creator']));
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'condition' => 'nullable|in:good,fair,poor',
        ]);

        $asset->update($request->all());

        return response()->json($asset->load(['project', 'creator']));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->json(['message' => 'Asset deleted successfully']);
    }
}
