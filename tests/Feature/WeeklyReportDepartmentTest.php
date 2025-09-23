<?php

namespace Tests\Feature;

use App\Models\WeeklyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WeeklyReportDepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock authentication middleware by setting a fake user
        $this->withoutMiddleware();
    }

    public function test_get_weekly_reports_by_department_requires_department_parameter(): void
    {
        // Mock the pgc_employee database helper function
        DB::shouldReceive('connection')
            ->with('pgc_employee')
            ->andReturnSelf();
        DB::shouldReceive('table')
            ->with('vEmployee')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('pluck')
            ->andReturn(collect([]));

        $response = $this->getJson('/api/weekly-reports/department');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['department']);
    }

    public function test_get_weekly_reports_by_department_returns_empty_when_no_department_employees(): void
    {
        // Mock empty employee list for department
        DB::shouldReceive('table')
            ->with('vEmployee')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('DeptDesc', 'Non-existent Department')
            ->andReturnSelf();
        DB::shouldReceive('pluck')
            ->with('emp_no')
            ->andReturn(collect([]));

        $response = $this->getJson('/api/weekly-reports/department?department=Non-existent Department');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'data' => [],
                    'total' => 0,
                ],
                'message' => 'No employees found in the specified department',
            ]);
    }

    public function test_get_weekly_reports_by_department_returns_filtered_reports(): void
    {
        // Create test data
        $employee1 = 'EMP001';
        $employee2 = 'EMP002';
        $employee3 = 'EMP003';

        $report1 = WeeklyReport::factory()->create(['employee_id' => $employee1]);
        $report2 = WeeklyReport::factory()->create(['employee_id' => $employee2]);
        $report3 = WeeklyReport::factory()->create(['employee_id' => $employee3]);

        // Mock employee list for IT Department
        DB::shouldReceive('table')
            ->with('vEmployee')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('DeptDesc', 'IT Department')
            ->andReturnSelf();
        DB::shouldReceive('pluck')
            ->with('emp_no')
            ->andReturn(collect([$employee1, $employee2]));

        $response = $this->getJson('/api/weekly-reports/department?department=IT Department');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total', 2)
            ->assertJsonCount(2, 'data.data');
    }

    public function test_get_weekly_reports_by_department_with_status_filter(): void
    {
        // Create test data with different statuses
        $employee1 = 'EMP001';

        $draftReport = WeeklyReport::factory()->create([
            'employee_id' => $employee1,
            'status' => 'draft',
        ]);
        $submittedReport = WeeklyReport::factory()->create([
            'employee_id' => $employee1,
            'status' => 'submitted',
        ]);

        // Mock employee list for IT Department
        DB::shouldReceive('table')
            ->with('vEmployee')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->with('DeptDesc', 'IT Department')
            ->andReturnSelf();
        DB::shouldReceive('pluck')
            ->with('emp_no')
            ->andReturn(collect([$employee1]));

        $response = $this->getJson('/api/weekly-reports/department?department=IT Department&status=draft');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.total', 1)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_get_weekly_reports_by_department_validates_status_parameter(): void
    {
        $response = $this->getJson('/api/weekly-reports/department?department=IT Department&status=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
