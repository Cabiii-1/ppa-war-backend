<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EntryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Entry::query();

            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            if ($request->has('entry_date')) {
                $query->where('entry_date', $request->entry_date);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $query->whereBetween('entry_date', [$request->date_from, $request->date_to]);
            }

            $entries = $query->with('weeklyReport')
                ->orderBy('entry_date', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $entries,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve entries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|string|max:255',
                'entry_date' => 'required|date',
                'ppa' => 'required|string',
                'kpi' => 'required|string',
                'status' => 'required|string|max:255',
                'status_comment' => 'nullable|string',
                'remarks' => 'nullable|string',
                'weekly_report_id' => 'nullable|exists:weekly_reports,id',
            ]);

            $entry = Entry::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Entry created successfully',
                'data' => $entry->load('weeklyReport'),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Entry $entry): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $entry->load('weeklyReport'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Entry $entry): JsonResponse
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'sometimes|required|string|max:255',
                'entry_date' => 'sometimes|required|date',
                'ppa' => 'sometimes|required|string',
                'kpi' => 'sometimes|required|string',
                'status' => 'sometimes|required|string|max:255',
                'status_comment' => 'nullable|string',
                'remarks' => 'nullable|string',
                'weekly_report_id' => 'nullable|exists:weekly_reports,id',
            ]);

            $entry->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Entry updated successfully',
                'data' => $entry->load('weeklyReport'),
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
                'message' => 'Failed to update entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Entry $entry): JsonResponse
    {
        try {
            $entry->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entry deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|exists:entries,id',
            ]);

            $deletedCount = Entry::whereIn('id', $validated['ids'])->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} entries",
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
                'message' => 'Failed to delete entries',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from',
                'employee_id' => 'nullable|string',
                'status' => 'nullable|string',
            ]);

            $query = Entry::whereBetween('entry_date', [$validated['date_from'], $validated['date_to']]);

            if (! empty($validated['employee_id'])) {
                $query->where('employee_id', $validated['employee_id']);
            }

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            $entries = $query->with('weeklyReport')
                ->orderBy('entry_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $entries,
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
                'message' => 'Failed to retrieve entries by date range',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
