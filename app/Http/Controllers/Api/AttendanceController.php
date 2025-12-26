<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Get attendance for a specific date (auto-generate if not exists)
    public function index(Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        // Get all active employees
        $employees = Employee::where('is_active', true)
            ->with(['project'])
            ->orderBy('name')
            ->get();

        // Auto-generate attendance for employees who don't have it yet
        foreach ($employees as $employee) {
            Attendance::firstOrCreate(
                ['employee_id' => $employee->id, 'date' => $date],
                ['status' => 'present']
            );
        }

        // Get all attendances for the date with employee info
        $attendances = Attendance::with(['employee.project'])
            ->whereDate('date', $date)
            ->whereHas('employee', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        // Summary
        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
        ];

        return response()->json([
            'date' => $date,
            'attendances' => $attendances,
            'summary' => $summary,
        ]);
    }

    // Toggle single employee attendance (present/absent)
    public function toggle(Request $request, Attendance $attendance)
    {
        $attendance->update([
            'status' => $attendance->status === 'present' ? 'absent' : 'present'
        ]);

        return response()->json($attendance->load('employee.project'));
    }

    // Cancel single employee attendance (mark as absent)
    public function cancel(Attendance $attendance)
    {
        $attendance->update(['status' => 'absent']);

        return response()->json($attendance->load('employee.project'));
    }

    // Cancel all attendances for a date
    public function cancelAll(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        Attendance::whereDate('date', $request->date)->update(['status' => 'absent']);

        return response()->json(['message' => 'All attendances cancelled for ' . $request->date]);
    }

    // Mark all as present for a date
    public function markAllPresent(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        Attendance::whereDate('date', $request->date)->update(['status' => 'present']);

        return response()->json(['message' => 'All marked present for ' . $request->date]);
    }

    // Get monthly summary for an employee
    public function monthlySummary(Request $request, Employee $employee)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get();

        return response()->json([
            'employee' => $employee->load('project'),
            'month' => $month,
            'year' => $year,
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
        ]);
    }

    // Get monthly report for all employees
    public function monthlyReport(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $employees = Employee::where('is_active', true)
            ->with(['project'])
            ->get()
            ->map(function ($employee) use ($month, $year) {
                $attendances = Attendance::where('employee_id', $employee->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->get();

                return [
                    'employee' => $employee,
                    'total_days' => $attendances->count(),
                    'present_days' => $attendances->where('status', 'present')->count(),
                    'absent_days' => $attendances->where('status', 'absent')->count(),
                ];
            });

        return response()->json([
            'month' => $month,
            'year' => $year,
            'report' => $employees,
        ]);
    }
}
