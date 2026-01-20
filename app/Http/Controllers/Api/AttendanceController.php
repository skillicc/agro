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

        // Build employee query with optional filters
        $employeeQuery = Employee::where('is_active', true);

        // Exclude Administration employees
        $employeeQuery->whereDoesntHave('project', function ($q) {
            $q->where('name', 'Administration');
        });

        // Filter by employee type
        if ($request->employee_type) {
            $employeeQuery->where('employee_type', $request->employee_type);
        }

        // Filter by project
        if ($request->project_id) {
            $employeeQuery->where('project_id', $request->project_id);
        }

        $employees = $employeeQuery->with(['project'])->orderBy('name')->get();

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
            ->whereHas('employee', function ($q) use ($request) {
                $q->where('is_active', true);
                // Exclude Administration employees
                $q->whereDoesntHave('project', function ($pq) {
                    $pq->where('name', 'Administration');
                });
                if ($request->employee_type) {
                    $q->where('employee_type', $request->employee_type);
                }
                if ($request->project_id) {
                    $q->where('project_id', $request->project_id);
                }
            })
            ->get();

        // Summary with type breakdown
        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'leave' => $attendances->where('status', 'leave')->count(),
            'sick_leave' => $attendances->where('status', 'sick_leave')->count(),
            'regular_count' => $attendances->filter(fn($a) => $a->employee->employee_type === 'regular')->count(),
            'contractual_count' => $attendances->filter(fn($a) => $a->employee->employee_type === 'contractual')->count(),
        ];

        return response()->json([
            'date' => $date,
            'attendances' => $attendances,
            'summary' => $summary,
        ]);
    }

    // Toggle single employee attendance (cycles through: present -> absent -> leave -> sick_leave -> present)
    public function toggle(Request $request, Attendance $attendance)
    {
        $statusCycle = ['present', 'absent', 'leave', 'sick_leave'];
        $currentIndex = array_search($attendance->status, $statusCycle);
        $nextIndex = ($currentIndex + 1) % count($statusCycle);

        $attendance->update([
            'status' => $statusCycle[$nextIndex]
        ]);

        return response()->json($attendance->load('employee.project'));
    }

    // Update attendance status directly with optional note
    public function updateStatus(Request $request, Attendance $attendance)
    {
        $request->validate([
            'status' => 'required|in:present,absent,leave,sick_leave',
            'note' => 'nullable|string|max:255',
        ]);

        $attendance->update([
            'status' => $request->status,
            'note' => $request->note,
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
            'leave_days' => $attendances->where('status', 'leave')->count(),
            'sick_leave_days' => $attendances->where('status', 'sick_leave')->count(),
        ]);
    }

    // Get monthly report for all employees
    public function monthlyReport(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $employeeQuery = Employee::where('is_active', true)->with(['project']);

        // Exclude Administration employees
        $employeeQuery->whereDoesntHave('project', function ($q) {
            $q->where('name', 'Administration');
        });

        // Filter by employee type
        if ($request->employee_type) {
            $employeeQuery->where('employee_type', $request->employee_type);
        }

        $employees = $employeeQuery->get()
            ->map(function ($employee) use ($month, $year) {
                $attendances = Attendance::where('employee_id', $employee->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->get();

                $presentDays = $attendances->where('status', 'present')->count();

                // Get absent dates for tooltip
                $absentDates = $attendances->where('status', 'absent')
                    ->pluck('date')
                    ->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M'))
                    ->toArray();

                $result = [
                    'employee' => $employee,
                    'total_days' => $attendances->count(),
                    'present_days' => $presentDays,
                    'absent_days' => $attendances->where('status', 'absent')->count(),
                    'absent_dates' => $absentDates,
                    'leave_days' => $attendances->where('status', 'leave')->count(),
                    'sick_leave_days' => $attendances->where('status', 'sick_leave')->count(),
                ];

                // Add salary info for contractual employees
                if ($employee->isContractual()) {
                    $result['daily_rate'] = $employee->daily_rate;
                    $result['calculated_salary'] = $presentDays * floatval($employee->daily_rate);
                }

                return $result;
            });

        return response()->json([
            'month' => $month,
            'year' => $year,
            'report' => $employees,
        ]);
    }

    // Get daily attendance data for charts (for a specific month)
    public function dailyChartData(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        // Get all dates with attendance records for the month
        $attendances = Attendance::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereHas('employee', function ($q) {
                $q->where('is_active', true);
                // Exclude Administration employees
                $q->whereDoesntHave('project', function ($pq) {
                    $pq->where('name', 'Administration');
                });
            })
            ->get()
            ->groupBy(function ($attendance) {
                return $attendance->date->format('Y-m-d');
            });

        $dailyData = [];
        foreach ($attendances as $date => $records) {
            $dailyData[] = [
                'date' => $date,
                'day' => (int) date('d', strtotime($date)),
                'total' => $records->count(),
                'present' => $records->where('status', 'present')->count(),
                'absent' => $records->where('status', 'absent')->count(),
                'leave' => $records->where('status', 'leave')->count(),
                'sick_leave' => $records->where('status', 'sick_leave')->count(),
            ];
        }

        // Sort by date
        usort($dailyData, function ($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        return response()->json([
            'month' => $month,
            'year' => $year,
            'daily_data' => $dailyData,
        ]);
    }
}
