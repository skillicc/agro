<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\LandController;
use App\Http\Controllers\Api\ProjectController;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProjectLandManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_can_be_created_with_lands_and_land_wise_expense_summary(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $landController = app(LandController::class);
        $projectController = app(ProjectController::class);
        $expenseController = app(ExpenseController::class);

        $landA = $this->makeAuthenticatedRequest([
            'name' => 'North Plot',
            'location' => 'Block A',
            'size' => 1.50,
            'unit' => 'acre',
        ], $admin);
        $landAResponse = $landController->store($landA);

        $landB = $this->makeAuthenticatedRequest([
            'name' => 'South Plot',
            'location' => 'Block B',
            'size' => 1.00,
            'unit' => 'acre',
        ], $admin);
        $landBResponse = $landController->store($landB);

        $this->assertSame(201, $landAResponse->getStatusCode());
        $this->assertSame(201, $landBResponse->getStatusCode());

        $landAData = $landAResponse->getData(true);
        $landBData = $landBResponse->getData(true);

        $projectRequest = $this->makeAuthenticatedRequest([
            'name' => 'Tomato Season 2026',
            'type' => 'field',
            'location' => 'Gazipur',
            'description' => 'Tomato cultivation project',
            'start_date' => '2026-04-01',
            'land_ids' => [$landAData['id'], $landBData['id']],
        ], $admin);
        $projectResponse = $projectController->store($projectRequest);

        $this->assertSame(201, $projectResponse->getStatusCode());

        $projectData = $projectResponse->getData(true);

        $this->assertDatabaseHas('land_project', [
            'project_id' => $projectData['id'],
            'land_id' => $landAData['id'],
        ]);

        $category = ExpenseCategory::where('project_type', 'field')->firstOrFail();

        $expenseRequest = $this->makeAuthenticatedRequest([
            'project_id' => $projectData['id'],
            'land_id' => $landAData['id'],
            'expense_category_id' => $category->id,
            'amount' => 1500,
            'date' => '2026-04-02',
            'description' => 'Irrigation setup',
        ], $admin);
        $expenseResponse = $expenseController->store($expenseRequest);

        $this->assertSame(201, $expenseResponse->getStatusCode());
        $this->assertSame($landAData['id'], $expenseResponse->getData(true)['land']['id']);

        Sale::create([
            'project_id' => $projectData['id'],
            'land_id' => $landAData['id'],
            'challan_no' => 'SAL-100',
            'date' => '2026-04-03',
            'subtotal' => 4200,
            'discount' => 0,
            'total' => 4200,
            'paid' => 3000,
            'due' => 1200,
            'status' => 'completed',
            'created_by' => $admin->id,
        ]);

        $ledgerRequest = Request::create("/api/projects/{$projectData['id']}/land-ledger", 'GET');
        $ledgerRequest->setUserResolver(fn () => $admin);
        $ledgerResponse = $projectController->landLedger($ledgerRequest, Project::findOrFail($projectData['id']));

        $ledger = $ledgerResponse->getData(true);

        $this->assertSame(1500.0, (float) $ledger['totals']['total_expenses']);
        $this->assertSame(4200.0, (float) ($ledger['totals']['total_sales'] ?? 0));
        $this->assertSame(2700.0, (float) ($ledger['totals']['total_profit'] ?? 0));
        $this->assertCount(2, $ledger['lands']);
        $this->assertSame('North Plot', $ledger['lands'][0]['name']);
        $this->assertSame(1500.0, (float) $ledger['lands'][0]['total_expenses']);
        $this->assertSame(4200.0, (float) ($ledger['lands'][0]['total_sales'] ?? 0));
        $this->assertSame(2700.0, (float) ($ledger['lands'][0]['profit'] ?? 0));
    }

    public function test_expense_history_tracks_who_created_and_updated_it(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'name' => 'History Admin',
        ]);

        $project = Project::create([
            'name' => 'Ledger Project',
            'type' => 'field',
            'location' => 'Gazipur',
            'is_active' => true,
        ]);

        $category = ExpenseCategory::where('project_type', 'field')->firstOrFail();
        $expenseController = app(ExpenseController::class);

        $storeRequest = $this->makeAuthenticatedRequest([
            'project_id' => $project->id,
            'expense_category_id' => $category->id,
            'amount' => 900,
            'date' => '2026-04-04',
            'description' => 'Initial fertilizer',
            'bill_no' => 'EXP-100',
        ], $admin);

        $storeResponse = $expenseController->store($storeRequest);
        $this->assertSame(201, $storeResponse->getStatusCode());

        $expenseId = $storeResponse->getData(true)['id'];

        $updateRequest = $this->makeAuthenticatedRequest([
            'project_id' => $project->id,
            'expense_category_id' => $category->id,
            'amount' => 1200,
            'date' => '2026-04-05',
            'description' => 'Updated fertilizer and labor',
            'bill_no' => 'EXP-100A',
        ], $admin, 'PUT');

        $updateResponse = $expenseController->update($updateRequest, Project::findOrFail($project->id)->expenses()->firstOrFail());
        $this->assertSame(200, $updateResponse->getStatusCode());

        $historyResponse = $expenseController->history(Project::findOrFail($project->id)->expenses()->firstOrFail());
        $historyData = $historyResponse->getData(true);

        $this->assertSame($admin->id, $updateResponse->getData(true)['updated_by']);
        $this->assertCount(2, $historyData);
        $this->assertSame('updated', $historyData[0]['action']);
        $this->assertSame('created', $historyData[1]['action']);
    }

    public function test_land_requires_previous_crop_cycle_to_close_before_next_one_starts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $projectController = app(ProjectController::class);
        $landController = app(LandController::class);

        $project = Project::create([
            'name' => 'Mixed Vegetables',
            'type' => 'field',
            'location' => 'Mymensingh',
            'is_active' => true,
        ]);

        $landRequest = $this->makeAuthenticatedRequest([
            'name' => 'Demo Plot',
            'location' => 'West Side',
            'size' => 0.75,
            'unit' => 'acre',
        ], $admin);
        $landResponse = $landController->store($landRequest);

        $this->assertSame(201, $landResponse->getStatusCode());
        $land = $landResponse->getData(true);

        $project->lands()->sync([$land['id']]);

        $firstCycleRequest = $this->makeAuthenticatedRequest([
            'land_id' => $land['id'],
            'crop_name' => 'Tomato',
            'opening_date' => '2026-04-01',
            'expected_closing_date' => '2026-06-15',
        ], $admin);
        $firstCycleResponse = $projectController->storeLandCultivation($firstCycleRequest, $project);

        $this->assertSame(201, $firstCycleResponse->getStatusCode());

        $secondCycleRequest = $this->makeAuthenticatedRequest([
            'land_id' => $land['id'],
            'crop_name' => 'Chili',
            'opening_date' => '2026-04-20',
        ], $admin);
        $secondCycleResponse = $projectController->storeLandCultivation($secondCycleRequest, $project);

        $this->assertSame(422, $secondCycleResponse->getStatusCode());

        $closeRequest = $this->makeAuthenticatedRequest([
            'status' => 'closed',
            'closing_date' => '2026-06-20',
            'notes' => 'Harvest completed',
        ], $admin, 'PUT');
        $closeResponse = $projectController->updateLandCultivation($closeRequest, $project->landCultivations()->firstOrFail());

        $this->assertSame(200, $closeResponse->getStatusCode());

        $nextCycleRequest = $this->makeAuthenticatedRequest([
            'land_id' => $land['id'],
            'crop_name' => 'Chili',
            'opening_date' => '2026-06-25',
        ], $admin);
        $nextCycleResponse = $projectController->storeLandCultivation($nextCycleRequest, $project->fresh());

        $this->assertSame(201, $nextCycleResponse->getStatusCode());
    }

    private function makeAuthenticatedRequest(array $payload, User $user, string $method = 'POST'): Request
    {
        $request = Request::create('/', $method, $payload);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
