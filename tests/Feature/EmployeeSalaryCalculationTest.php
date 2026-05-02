<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EmployeeController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeEmploymentPeriod;
use App\Models\EmployeeWorkingDayOverride;
use App\Models\Project;
use App\Models\Salary;
use App\Models\SalaryAdjustment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeSalaryCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_employee_salary_calculation_uses_historical_salary_and_prorates_join_month(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Idris',
            'position' => 'Field Worker',
            'salary_amount' => 18000,
            'joining_date' => '2025-11-10',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        SalaryAdjustment::create([
            'employee_id' => $employee->id,
            'type' => 'increase',
            'old_salary' => 7200,
            'new_salary' => 12600,
            'amount' => 5400,
            'effective_date' => '2025-12-01',
            'created_by' => $admin->id,
        ]);

        SalaryAdjustment::create([
            'employee_id' => $employee->id,
            'type' => 'increase',
            'old_salary' => 12600,
            'new_salary' => 18000,
            'amount' => 5400,
            'effective_date' => '2026-01-01',
            'created_by' => $admin->id,
        ]);

        foreach (range(10, 30) as $day) {
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => sprintf('2025-11-%02d', $day),
                'status' => 'present',
            ]);
        }

        $controller = app(EmployeeController::class);

        $novemberRequest = Request::create("/api/employees/{$employee->id}/calculate-salary", 'GET', [
            'month' => '2025-11',
        ]);
        $novemberResponse = $controller->calculateSalary($novemberRequest, $employee);
        $novemberData = $novemberResponse->getData(true);

        $this->assertSame('regular', $novemberData['employee_type']);
        $this->assertSame(7200.0, (float) $novemberData['salary_amount']);
        $this->assertSame(0, $novemberData['present_days']);
        $this->assertSame(21, $novemberData['worked_days']);
        $this->assertSame(5040.0, (float) $novemberData['calculated_salary']);
        $this->assertTrue($novemberData['is_prorated']);

        $decemberRequest = Request::create("/api/employees/{$employee->id}/calculate-salary", 'GET', [
            'month' => '2025-12',
        ]);
        $decemberResponse = $controller->calculateSalary($decemberRequest, $employee);
        $decemberData = $decemberResponse->getData(true);

        $this->assertSame(12600.0, (float) $decemberData['salary_amount']);
        $this->assertSame(12600.0, (float) $decemberData['calculated_salary']);
        $this->assertFalse($decemberData['is_prorated']);
    }

    public function test_employee_index_due_uses_previous_month_historical_salary_for_regular_employees(): void
    {
        Carbon::setTestNow('2026-05-02 12:00:00');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Idris',
            'position' => 'Field Worker',
            'salary_amount' => 12600,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        SalaryAdjustment::create([
            'employee_id' => $employee->id,
            'type' => 'increase',
            'old_salary' => 12600,
            'new_salary' => 18000,
            'amount' => 5400,
            'effective_date' => '2025-12-01',
            'created_by' => $admin->id,
        ]);

        Salary::create([
            'project_id' => $project->id,
            'employee_id' => $employee->id,
            'amount' => 5000,
            'month' => '2026-04',
            'payment_date' => '2026-04-29',
            'created_by' => $admin->id,
        ]);

        $controller = app(EmployeeController::class);
        $response = $controller->index(Request::create('/api/employees', 'GET'));
        $employees = collect($response->getData(true));
        $employeeData = $employees->firstWhere('id', $employee->id);

        $this->assertNotNull($employeeData);
        $this->assertSame(18000.0, (float) $employeeData['salary_amount']);
        $this->assertSame(18000.0, (float) $employeeData['calculated_salary']);
        $this->assertSame(13000.0, (float) $employeeData['current_month_due']);

        Carbon::setTestNow();
    }

    public function test_regular_employee_pre_2026_month_ignores_attendance_rows_for_salary_summary(): void
    {
        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Idris',
            'position' => 'Field Worker',
            'salary_amount' => 18000,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2025-12-02',
            'status' => 'present',
        ]);

        $details = $employee->calculateMonthlySalaryDetails('2025-12');

        $this->assertSame(0, $details['present_days']);
        $this->assertSame(31, $details['worked_days']);
        $this->assertSame(0, $details['total_days']);
        $this->assertSame('suggested', $details['working_day_source']);
        $this->assertSame(18000.0, (float) $details['calculated_salary']);
    }

    public function test_pre_2026_manual_working_day_override_updates_regular_salary_summary(): void
    {
        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Idris',
            'position' => 'Field Worker',
            'salary_amount' => 18000,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        EmployeeWorkingDayOverride::create([
            'employee_id' => $employee->id,
            'month' => '2025-12',
            'worked_days' => 15,
            'note' => 'Manual historical correction',
        ]);

        $details = $employee->calculateMonthlySalaryDetails('2025-12');

        $this->assertSame(15, $details['worked_days']);
        $this->assertSame('manual', $details['working_day_source']);
        $this->assertSame(8709.68, (float) $details['calculated_salary']);
    }

    public function test_pre_2026_manual_working_day_override_updates_contractual_salary_summary(): void
    {
        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'contractual',
            'name' => 'Helal',
            'position' => 'Field Worker',
            'salary_amount' => 0,
            'daily_rate' => 500,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        EmployeeWorkingDayOverride::create([
            'employee_id' => $employee->id,
            'month' => '2025-12',
            'worked_days' => 12,
            'note' => 'Manual historical correction',
        ]);

        $details = $employee->calculateMonthlySalaryDetails('2025-12');

        $this->assertSame(12, $details['worked_days']);
        $this->assertSame('manual', $details['working_day_source']);
        $this->assertSame(6000.0, (float) $details['calculated_salary']);
    }

    public function test_regular_employee_gap_month_has_zero_salary_when_no_employment_period_exists(): void
    {
        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Rayhan',
            'position' => 'Manager',
            'salary_amount' => 30000,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        $employee->employmentPeriods()->delete();

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-11-11',
            'end_date' => '2025-12-31',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2026-03-01',
            'end_date' => null,
        ]);

        $details = $employee->fresh()->calculateMonthlySalaryDetails('2026-02');

        $this->assertSame(0, $details['worked_days']);
        $this->assertSame(0.0, (float) $details['calculated_salary']);
        $this->assertTrue($details['is_prorated']);
    }

    public function test_regular_employee_rejoin_month_suggests_only_active_period_days_before_2026(): void
    {
        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Rayhan',
            'position' => 'Manager',
            'salary_amount' => 31000,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        $employee->employmentPeriods()->delete();

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-11-11',
            'end_date' => '2025-11-30',
        ]);

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-12-15',
            'end_date' => null,
        ]);

        $details = $employee->fresh()->calculateMonthlySalaryDetails('2025-12');

        $this->assertSame(17, $details['worked_days']);
        $this->assertSame(17, $details['suggested_worked_days']);
        $this->assertSame('suggested', $details['working_day_source']);
        $this->assertSame(17000.0, (float) $details['calculated_salary']);
        $this->assertTrue($details['is_prorated']);
    }

    public function test_employment_periods_validation_rejects_overlapping_ranges(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $project = Project::create([
            'name' => 'Central',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $employee = Employee::create([
            'project_id' => $project->id,
            'employee_type' => 'regular',
            'name' => 'Rayhan',
            'position' => 'Manager',
            'salary_amount' => 30000,
            'joining_date' => '2025-11-11',
            'earn_leave' => 0,
            'is_active' => true,
        ]);

        $employee->employmentPeriods()->delete();

        EmployeeEmploymentPeriod::create([
            'employee_id' => $employee->id,
            'start_date' => '2025-11-11',
            'end_date' => '2025-12-31',
        ]);

        $controller = app(EmployeeController::class);
        $request = Request::create("/api/employees/{$employee->id}/employment-periods", 'POST', [
            'start_date' => '2025-12-15',
            'end_date' => '2026-01-31',
            'note' => 'Overlap check',
        ]);
        $request->setUserResolver(fn () => $user);

        $this->expectException(ValidationException::class);

        $controller->storeEmploymentPeriod($request, $employee);
    }
}
