<?php

namespace App\Services;

use App\Models\WeeklyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    public function generateWeeklyReportPdf(WeeklyReport $weeklyReport, $user = null): string
    {
        $weeklyReport->load(['entries']);

        // Use the authenticated user or fallback to mock data
        $division = '';
        if ($user && isset($user->DivDesc) && $user->DivDesc !== '-None-') {
            $division = $user->DivDesc;
        } elseif ($user && isset($user->division) && $user->division !== '-None-') {
            $division = $user->division;
        }

        $employee = $user ? (object) [
            'name' => $user->Fullname ?? $user->name ?? $user->emp_name ?? 'Employee Name',
            'position' => $user->PosDesc ?? $user->position ?? 'Position Title',
            'department' => $user->DeptDesc ?? $user->department ?? 'Department Name',
            'division' => $division,
            'emp_no' => $user->emp_no ?? $weeklyReport->employee_id ?? 'N/A'
        ] : (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'division' => '',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $employee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->output();
    }

    public function downloadWeeklyReportPdf(WeeklyReport $weeklyReport, $user = null): \Symfony\Component\HttpFoundation\Response
    {
        $weeklyReport->load(['entries']);

        // Use the authenticated user or fallback to mock data
        $division = '';
        if ($user && isset($user->DivDesc) && $user->DivDesc !== '-None-') {
            $division = $user->DivDesc;
        } elseif ($user && isset($user->division) && $user->division !== '-None-') {
            $division = $user->division;
        }

        $employee = $user ? (object) [
            'name' => $user->Fullname ?? $user->name ?? $user->emp_name ?? 'Employee Name',
            'position' => $user->PosDesc ?? $user->position ?? 'Position Title',
            'department' => $user->DeptDesc ?? $user->department ?? 'Department Name',
            'division' => $division,
            'emp_no' => $user->emp_no ?? $weeklyReport->employee_id ?? 'N/A'
        ] : (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'division' => '',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $employee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        $filename = sprintf(
            'weekly_report_%s_to_%s.pdf',
            $weeklyReport->period_start->format('Y-m-d'),
            $weeklyReport->period_end->format('Y-m-d')
        );

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function previewWeeklyReportPdf(WeeklyReport $weeklyReport, $user = null): \Symfony\Component\HttpFoundation\Response
    {
        $weeklyReport->load(['entries']);

        // Use the authenticated user or fallback to mock data
        $division = '';
        if ($user && isset($user->DivDesc) && $user->DivDesc !== '-None-') {
            $division = $user->DivDesc;
        } elseif ($user && isset($user->division) && $user->division !== '-None-') {
            $division = $user->division;
        }

        $employee = $user ? (object) [
            'name' => $user->Fullname ?? $user->name ?? $user->emp_name ?? 'Employee Name',
            'position' => $user->PosDesc ?? $user->position ?? 'Position Title',
            'department' => $user->DeptDesc ?? $user->department ?? 'Department Name',
            'division' => $division,
            'emp_no' => $user->emp_no ?? $weeklyReport->employee_id ?? 'N/A'
        ] : (object) [
            'name' => 'Employee Name',
            'position' => 'Position Title',
            'department' => 'Department Name',
            'division' => '',
            'emp_no' => $weeklyReport->employee_id ?? 'N/A'
        ];

        $data = [
            'report' => $weeklyReport,
            'entries' => $weeklyReport->entries,
            'employee' => $employee,
            'generatedAt' => now(),
        ];

        $html = View::make('pdf.weekly-report', $data)->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->stream();
    }
}
