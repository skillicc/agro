<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('purchases')->get();
        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $supplier = Supplier::create($request->all());

        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases' => function ($q) {
            $q->latest()->limit(10);
        }, 'payments' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $supplier->update($request->all());

        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    public function ledger(Supplier $supplier)
    {
        $purchases = $supplier->purchases()->with('items.product')->orderBy('date', 'desc')->get();
        $payments = $supplier->payments()->orderBy('date', 'desc')->get();

        return response()->json([
            'supplier' => $supplier,
            'purchases' => $purchases,
            'payments' => $payments,
            'total_purchase' => $supplier->total_purchase,
            'total_paid' => $supplier->total_paid,
            'total_due' => $supplier->total_due,
        ]);
    }

    public function addPayment(Request $request, Supplier $supplier)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method ?? 'cash',
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($payment, 201);
    }

    public function payments(Supplier $supplier)
    {
        $payments = $supplier->payments()->with('creator')->orderBy('date', 'desc')->get();
        return response()->json($payments);
    }

    public function deletePayment(SupplierPayment $payment)
    {
        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
