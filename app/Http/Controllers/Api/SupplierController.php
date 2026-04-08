<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('purchases')
            ->withSum('purchases as gross_purchase', 'total')
            ->withSum('payments as total_payment_amount', 'amount')
            ->withSum('payments as total_payment_discount', 'discount')
            ->get();

        $returnedAmounts = $this->returnedAmountMap($suppliers->pluck('id')->all());

        $suppliers->each(function ($supplier) use ($returnedAmounts) {
            $grossPurchase = (float) ($supplier->gross_purchase ?? 0);
            $totalReturned = (float) ($returnedAmounts[$supplier->id] ?? 0);
            $totalPaid = (float) ($supplier->total_payment_amount ?? 0) + (float) ($supplier->total_payment_discount ?? 0);

            $supplier->gross_purchase = $grossPurchase;
            $supplier->total_returned = $totalReturned;
            $supplier->total_purchase = max(0, $grossPurchase - $totalReturned);
            $supplier->total_paid = $totalPaid;
            $supplier->total_due = max(0, $supplier->total_purchase - $totalPaid);
        });

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
        $returns = $this->supplierReturnHistory($supplier);

        $grossPurchase = (float) $purchases->sum('total');
        $totalReturned = (float) $returns->sum('amount');
        $totalPaid = (float) $payments->sum('amount') + (float) $payments->sum('discount');
        $totalPurchase = max(0, $grossPurchase - $totalReturned);
        $totalDue = max(0, $totalPurchase - $totalPaid);

        return response()->json([
            'supplier' => $supplier,
            'purchases' => $purchases,
            'payments' => $payments,
            'returns' => $returns,
            'gross_purchase' => $grossPurchase,
            'total_returned' => $totalReturned,
            'total_purchase' => $totalPurchase,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
        ]);
    }

    private function returnedAmountMap(array $supplierIds): array
    {
        $supplierIds = array_values(array_filter($supplierIds));

        if (empty($supplierIds)) {
            return [];
        }

        return DB::table('product_return_batches')
            ->join('stock_batches', 'product_return_batches.stock_batch_id', '=', 'stock_batches.id')
            ->join('purchase_items', 'stock_batches.purchase_item_id', '=', 'purchase_items.id')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->whereIn('purchases.supplier_id', $supplierIds)
            ->groupBy('purchases.supplier_id')
            ->select('purchases.supplier_id', DB::raw('SUM(product_return_batches.quantity * product_return_batches.cost_price) as total_returned'))
            ->pluck('total_returned', 'purchases.supplier_id')
            ->map(fn ($amount) => (float) $amount)
            ->all();
    }

    private function supplierReturnHistory(Supplier $supplier)
    {
        return DB::table('product_returns')
            ->join('product_return_batches', 'product_returns.id', '=', 'product_return_batches.product_return_id')
            ->join('stock_batches', 'product_return_batches.stock_batch_id', '=', 'stock_batches.id')
            ->join('purchase_items', 'stock_batches.purchase_item_id', '=', 'purchase_items.id')
            ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('products', 'product_returns.product_id', '=', 'products.id')
            ->where('purchases.supplier_id', $supplier->id)
            ->groupBy('product_returns.id', 'product_returns.date', 'product_returns.reason', 'products.name')
            ->orderByDesc('product_returns.date')
            ->orderByDesc('product_returns.id')
            ->select([
                'product_returns.id',
                'product_returns.date',
                'product_returns.reason',
                'products.name as product_name',
                DB::raw('SUM(product_return_batches.quantity * product_return_batches.cost_price) as amount'),
            ])
            ->get();
    }

    public function addPayment(Request $request, Supplier $supplier)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = SupplierPayment::create([
            'supplier_id' => $supplier->id,
            'amount' => $request->amount,
            'discount' => $request->discount ?? 0,
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
