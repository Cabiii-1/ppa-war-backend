<?php

namespace App\Http\Controllers;

use App\Models\WeeklyReport;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly PdfGeneratorService $pdfGeneratorService
    ) {}

    public function downloadWeeklyReportPdf(Request $request, WeeklyReport $weeklyReport): Response
    {
        try {
            return $this->pdfGeneratorService->downloadWeeklyReportPdf($weeklyReport, $request->user());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function previewWeeklyReportPdf(Request $request, WeeklyReport $weeklyReport): Response
    {
        try {
            return $this->pdfGeneratorService->previewWeeklyReportPdf($weeklyReport, $request->user());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate PDF preview',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateWeeklyReportPdf(Request $request, WeeklyReport $weeklyReport): JsonResponse
    {
        try {
            $pdfContent = $this->pdfGeneratorService->generateWeeklyReportPdf($weeklyReport, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'PDF generated successfully',
                'size' => strlen($pdfContent),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
