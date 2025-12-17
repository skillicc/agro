<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\Advance;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('project');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $employees = $query->orderBy('name')->get();

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'salary_amount' => 'required|numeric|min:0',
            'joining_date' => 'nullable|date',
        ]);

        $employee = Employee::create($request->all());

        return response()->json($employee->load('project'), 201);
    }

    public function show(Employee $employee)
    {
        $employee->load(['project', 'salaries', 'advances']);
        return response()->json($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'salary_amount' => 'required|numeric|min:0',
            'joining_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ]);

        $employee->update($request->all());

        return response()->json($employee->load('project'));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully']);
    }

    public function paySalary(Request $request, Employee $employee)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $salary = Salary::create([
            'project_id' => $employee->project_id,
            'employee_id' => $employee->id,
            'amount' => $request->amount,
            'month' => $request->month,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($salary->load(['employee', 'creator']), 201);
    }

    public function giveAdvance(Request $request, Employee $employee)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        $advance = Advance::create([
            'project_id' => $employee->project_id,
            'employee_id' => $employee->id,
            'amount' => $request->amount,
            'date' => $request->date,
            'reason' => $request->reason,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($advance->load(['employee', 'creator']), 201);
    }

    public function salaries(Request $request)
    {
        $query = Salary::with(['project', 'employee', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->month) {
            $query->where('month', $request->month);
        }

        return response()->json($query->orderBy('payment_date', 'desc')->get());
    }

    public function advances(Request $request)
    {
        $query = Advance::with(['project', 'employee', 'creator']);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }
}
