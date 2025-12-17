<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('sales')->get();
        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $customer = Customer::create($request->all());

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        $customer->load(['sales' => function ($q) {
            $q->latest()->limit(10);
        }, 'payments' => function ($q) {
            $q->latest()->limit(10);
        }]);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($request->all());

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function ledger(Customer $customer)
    {
        $sales = $customer->sales()->with('items.product')->orderBy('date', 'desc')->get();
        $payments = $customer->payments()->orderBy('date', 'desc')->get();

        return response()->json([
            'customer' => $customer,
            'sales' => $sales,
            'payments' => $payments,
            'total_sale' => $customer->total_sale,
            'total_paid' => $customer->total_paid,
            'total_due' => $customer->total_due,
        ]);
    }

    public function addPayment(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        $payment = CustomerPayment::create([
            'customer_id' => $customer->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'payment_method' => $request->payment_method ?? 'cash',
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($payment, 201);
    }

    public function payments(Customer $customer)
    {
        $payments = $customer->payments()->with('creator')->orderBy('date', 'desc')->get();
        return response()->json($payments);
    }

    public function deletePayment(CustomerPayment $payment)
    {
        $payment->delete();
        return response()->json(['message' => 'Payment deleted successfully']);
    }
}
