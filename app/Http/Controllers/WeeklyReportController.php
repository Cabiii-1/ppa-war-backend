<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\WeeklyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WeeklyReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = WeeklyReport::query();

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $reports = $query->withCount('entries')
                ->orderBy('period_start', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve weekly reports',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_ids' => 'required|array|min:1',
                'entry_ids.*' => 'integer|exists:entries,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after_or_equal:period_start',
            ]);

            return DB::transaction(function () use ($validated) {
                // Get the first entry to determine employee_id
                $firstEntry = Entry::find($validated['entry_ids'][0]);
                if (! $firstEntry) {
                    throw new \Exception('Entry not found');
                }

                // Verify all entries belong to the same employee
                $entryEmployeeIds = Entry::whereIn('id', $validated['entry_ids'])
                    ->pluck('employee_id')
                    ->unique();

                if ($entryEmployeeIds->count() > 1) {
                    throw new \Exception('All entries must belong to the same employee');
                }

                // Check if entries are already assigned to a weekly report
                $assignedEntries = Entry::whereIn('id', $validated['entry_ids'])
                    ->whereNotNull('weekly_report_id')
                    ->count();

                if ($assignedEntries > 0) {
                    throw new \Exception('Some entries are already assigned to a weekly report');
                }

                // Create the weekly report
                $weeklyReport = WeeklyReport::create([
                    'employee_id' => $firstEntry->employee_id,
                    'period_start' => $validated['period_start'],
                    'period_end' => $validated['period_end'],
                    'status' => 'draft',
                ]);

                // Update entries to link them to the weekly report
                Entry::whereIn('id', $validated['entry_ids'])
                    ->update(['weekly_report_id' => $weeklyReport->id]);

                // Load the weekly report with entry count
                $weeklyReport->loadCount('entries');

                return response()->json([
                    'success' => true,
                    'message' => 'Weekly report created successfully',
                    'data' => $weeklyReport,
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create weekly report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(WeeklyReport $weeklyReport): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $weeklyReport->load(['entries' => function ($query) {
                    $query->orderBy('entry_date', 'asc');
                }]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve weekly report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, WeeklyReport $weeklyReport): JsonResponse
    {
        try {
            $validated = $request->validate([
                'entry_ids' => 'required|array|min:1',
                'entry_ids.*' => 'integer|exists:entries,id',
            ]);

            return DB::transaction(function () use ($validated, $weeklyReport) {
                // Get the first entry to determine employee_id
                $firstEntry = Entry::find($validated['entry_ids'][0]);
                if (! $firstEntry) {
                    throw new \Exception('Entry not found');
                }

                // Verify all entries belong to the same employee and match the report's employee
                $entryEmployeeIds = Entry::whereIn('id', $validated['entry_ids'])
                    ->pluck('employee_id')
                    ->unique();

                if ($entryEmployeeIds->count() > 1) {
                    throw new \Exception('All entries must belong to the same employee');
                }

                if ($firstEntry->employee_id !== $weeklyReport->employee_id) {
                    throw new \Exception('Entries must belong to the same employee as the weekly report');
                }

                // Check if any of the new entries are already assigned to a different weekly report
                $conflictingEntries = Entry::whereIn('id', $validated['entry_ids'])
                    ->whereNotNull('weekly_report_id')
                    ->where('weekly_report_id', '!=', $weeklyReport->id)
                    ->count();

                if ($conflictingEntries > 0) {
                    throw new \Exception('Some entries are already assigned to a different weekly report');
                }

                // Remove weekly_report_id from current entries
                Entry::where('weekly_report_id', $weeklyReport->id)
                    ->update(['weekly_report_id' => null]);

                // Assign new entries to the weekly report
                Entry::whereIn('id', $validated['entry_ids'])
                    ->update(['weekly_report_id' => $weeklyReport->id]);

                // Load the updated weekly report with entry count
                $weeklyReport->loadCount('entries');

                return response()->json([
                    'success' => true,
                    'message' => 'Weekly report updated successfully',
                    'data' => $weeklyReport,
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update weekly report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request, WeeklyReport $weeklyReport): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:draft,submitted,archived',
            ]);

            $weeklyReport->update([
                'status' => $validated['status'],
                'submitted_at' => $validated['status'] === 'submitted' ? now() : $weeklyReport->submitted_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Weekly report status updated successfully',
                'data' => $weeklyReport->loadCount('entries'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update weekly report status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(WeeklyReport $weeklyReport): JsonResponse
    {
        try {
            return DB::transaction(function () use ($weeklyReport) {
                // Remove weekly_report_id from associated entries
                Entry::where('weekly_report_id', $weeklyReport->id)
                    ->update(['weekly_report_id' => null]);

                // Delete the weekly report
                $weeklyReport->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Weekly report deleted successfully',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete weekly report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByDepartment(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'department' => 'required|string',
                'status' => 'sometimes|string|in:draft,submitted,archived',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            // Get employee IDs from the specified department
            $employeeIds = pgc_employee()->table('vEmployee')
                ->where('DeptDesc', $validated['department'])
                ->pluck('emp_no');

            if ($employeeIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'data' => [],
                        'current_page' => 1,
                        'total' => 0,
                        'per_page' => $request->get('per_page', 15),
                        'last_page' => 1,
                        'from' => null,
                        'to' => null,
                    ],
                    'message' => 'No employees found in the specified department',
                ]);
            }

            $query = WeeklyReport::whereIn('employee_id', $employeeIds);

            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $reports = $query->withCount('entries')
                ->orderBy('period_start', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $reports,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve weekly reports by department',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
