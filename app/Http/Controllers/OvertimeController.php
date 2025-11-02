<?php
// app/Http/Controllers/OvertimeController.php

namespace App\Http\Controllers;

use App\Models\OvertimeRecord;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $query = OvertimeRecord::with('employee', 'attendance');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('overtime_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $overtimeRecords = $query->orderBy('overtime_date', 'desc')
                                ->paginate(50);

        return view('overtime.index', compact('overtimeRecords'));
    }

    public function show(OvertimeRecord $overtimeRecord)
    {
        $overtimeRecord->load('employee', 'attendance');
        return view('overtime.show', compact('overtimeRecord'));
    }

    public function approve(OvertimeRecord $overtimeRecord)
    {
        $overtimeRecord->update(['status' => 'approved']);
        return redirect()->back()->with('success', 'Overtime approved successfully!');
    }

    public function reject(Request $request, OvertimeRecord $overtimeRecord)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string'
        ]);

        $overtimeRecord->update([
            'status' => 'rejected',
            'reason' => $validated['reason'] ?? null
        ]);

        return redirect()->back()->with('success', 'Overtime rejected!');
    }

    public function export(Request $request)
    {
        $query = OvertimeRecord::with('employee');

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('overtime_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $overtimeRecords = $query->orderBy('overtime_date', 'desc')->get();

        $filename = 'overtime_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($overtimeRecords) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Date', 'Staff Number', 'Employee Name',
                'Overtime Hours', 'Overtime Rate', 'Overtime Amount',
                'Status'
            ]);

            foreach ($overtimeRecords as $record) {
                fputcsv($file, [
                    $record->overtime_date->format('Y-m-d'),
                    $record->employee->staff_number,
                    $record->employee->employee_name,
                    $record->overtime_hours,
                    $record->overtime_rate,
                    $record->overtime_amount,
                    $record->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
