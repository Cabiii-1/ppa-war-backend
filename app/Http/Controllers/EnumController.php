<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnumController extends Controller
{
    public function getStatusOptions(): \Illuminate\Http\JsonResponse
    {
        try {
            // Get enum values from database schema
            $result = DB::select("SHOW COLUMNS FROM entries LIKE 'status'");

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Status column not found'
                ], 404);
            }

            $enumString = $result[0]->Type;

            // Extract enum values from the type string
            // Format: enum('Accomplished','In Progress','Delayed','Others')
            preg_match_all("/'([^']+)'/", $enumString, $matches);
            $enumValues = $matches[1] ?? [];

            return response()->json([
                'success' => true,
                'data' => $enumValues
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch status options',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
