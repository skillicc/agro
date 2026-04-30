<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EmployeeController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Project;
use App\Models\SalaryAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        $this->assertSame(21, $novemberData['present_days']);
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
}