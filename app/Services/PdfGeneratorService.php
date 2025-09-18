<?php

namespace App\Services;

use App\Models\WeeklyReport;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    public function generateWeeklyReportPdf(WeeklyReport $weeklyReport): string
    {
        $weeklyReport->load(['entries']);

        // Create a mock employee object with the available data
        $mockEmployee = (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $mockEmployee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('print-media-type', true)
            ->setOption('disable-smart-shrinking', true)
            ->output();
    }

    public function downloadWeeklyReportPdf(WeeklyReport $weeklyReport): \Symfony\Component\HttpFoundation\Response
    {
        $weeklyReport->load(['entries']);

        // Create a mock employee object with the available data
        $mockEmployee = (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $mockEmployee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        $filename = sprintf(
            'weekly_report_%s_to_%s.pdf',
            $weeklyReport->period_start->format('Y-m-d'),
            $weeklyReport->period_end->format('Y-m-d')
        );

        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('print-media-type', true)
            ->setOption('disable-smart-shrinking', true)
            ->download($filename);
    }

    public function previewWeeklyReportPdf(WeeklyReport $weeklyReport): \Symfony\Component\HttpFoundation\Response
    {
        $weeklyReport->load(['entries']);

        // Create a mock employee object with the available data
        $mockEmployee = (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $mockEmployee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        return SnappyPdf::loadHTML($html)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-right', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('enable-local-file-access', true)
            ->setOption('print-media-type', true)
            ->setOption('disable-smart-shrinking', true)
            ->inline();
    }
}
