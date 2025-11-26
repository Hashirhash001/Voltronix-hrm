<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates attendance for November 2025 (working days only: Mon-Sat)
     */
    public function run()
    {
        // Get all active employees
        $employees = Employee::where('status', 'active')->get();

        // November 2025 date range
        $startDate = Carbon::parse('2025-11-01');
        $endDate = Carbon::parse('2025-11-30');

        $attendancePatterns = [
            'punctual' => 0.15,      // 15% always on time
            'mostly_ontime' => 0.40, // 40% mostly on time
            'sometimes_late' => 0.30,// 30% sometimes late
            'often_late' => 0.15,    // 15% often late
        ];

        foreach ($employees as $employee) {
            // Assign a pattern to each employee
            $rand = mt_rand(1, 100) / 100;
            if ($rand <= 0.15) {
                $pattern = 'punctual';
            } elseif ($rand <= 0.55) {
                $pattern = 'mostly_ontime';
            } elseif ($rand <= 0.85) {
                $pattern = 'sometimes_late';
            } else {
                $pattern = 'often_late';
            }

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dayOfWeek = $currentDate->dayOfWeek;

                // Skip Sundays (day 0)
                if ($dayOfWeek === 0) {
                    $currentDate->addDay();
                    continue;
                }

                // 95% attendance rate (5% random absences)
                $isPresent = mt_rand(1, 100) > 5;

                if (!$isPresent) {
                    // Mark as absent or leave
                    $status = mt_rand(1, 100) > 50 ? 'absent' : 'leave';

                    Attendance::create([
                        'employee_id' => $employee->id,
                        'staff_number' => $employee->staff_number,
                        'attendance_date' => $currentDate->format('Y-m-d'),
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'status' => $status,
                        'regular_hours' => 0,
                        'overtime_hours' => 0,
                        'total_hours' => 0,
                        'late_minutes' => 0,
                        'is_late' => false,
                        'notes' => $status === 'leave' ? 'Approved leave' : 'Absent',
                    ]);

                    $currentDate->addDay();
                    continue;
                }

                // Generate check-in time based on pattern
                $checkInTime = $this->generateCheckInTime($pattern, $currentDate);

                // Generate check-out time (standard 8am-6pm, with some variation)
                $checkOutTime = $this->generateCheckOutTime($currentDate);

                // Create attendance record
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'staff_number' => $employee->staff_number,
                    'attendance_date' => $currentDate->format('Y-m-d'),
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'status' => 'present',
                    'notes' => null,
                ]);

                // Calculate hours (this will set late_minutes, is_late, regular_hours, overtime_hours)
                $attendance->calculateHours();

                // Create overtime record if overtime exists
                if ($attendance->hasOvertime()) {
                    $overtimeRecord = OvertimeRecord::create([
                        'employee_id' => $attendance->employee_id,
                        'attendance_id' => $attendance->id,
                        'overtime_date' => $attendance->attendance_date,
                        'overtime_hours' => $attendance->overtime_hours,
                        'overtime_rate' => 1.5,
                        'status' => 'pending',
                    ]);
                    $overtimeRecord->calculateOvertimeAmount();
                }

                $currentDate->addDay();
            }

            $this->command->info("Generated attendance for: {$employee->employee_name} ({$employee->staff_number})");
        }

        $this->command->info('Attendance seeding completed!');
    }

    /**
     * Generate check-in time based on employee pattern
     */
    private function generateCheckInTime($pattern, $date)
    {
        $baseTime = Carbon::parse($date)->setTime(8, 0, 0); // 8:00 AM

        switch ($pattern) {
            case 'punctual':
                // Always between 7:50 - 8:05
                $minutes = mt_rand(-10, 5);
                break;

            case 'mostly_ontime':
                // Usually on time, occasionally 5-15 mins late
                $rand = mt_rand(1, 100);
                if ($rand <= 80) {
                    $minutes = mt_rand(-5, 5);
                } else {
                    $minutes = mt_rand(5, 15);
                }
                break;

            case 'sometimes_late':
                // 50% on time, 50% late (5-30 mins)
                $rand = mt_rand(1, 100);
                if ($rand <= 50) {
                    $minutes = mt_rand(-5, 5);
                } else {
                    $minutes = mt_rand(10, 30);
                }
                break;

            case 'often_late':
                // Frequently late (15-60 mins)
                $rand = mt_rand(1, 100);
                if ($rand <= 20) {
                    $minutes = mt_rand(-5, 5);
                } else {
                    $minutes = mt_rand(15, 60);
                }
                break;

            default:
                $minutes = 0;
        }

        return $baseTime->addMinutes($minutes);
    }

    /**
     * Generate check-out time
     * Standard: 6:00 PM, with variations for overtime
     */
    private function generateCheckOutTime($date)
    {
        $baseTime = Carbon::parse($date)->setTime(18, 0, 0); // 6:00 PM

        $rand = mt_rand(1, 100);

        if ($rand <= 60) {
            // 60% leave on time or slightly early/late (5:50 - 6:10 PM)
            $minutes = mt_rand(-10, 10);
        } elseif ($rand <= 85) {
            // 25% work 30 mins - 1 hour overtime (6:30 - 7:00 PM)
            $minutes = mt_rand(30, 60);
        } elseif ($rand <= 95) {
            // 10% work 1-2 hours overtime (7:00 - 8:00 PM)
            $minutes = mt_rand(60, 120);
        } else {
            // 5% work 2-4 hours overtime (8:00 - 10:00 PM)
            $minutes = mt_rand(120, 240);
        }

        return $baseTime->addMinutes($minutes);
    }
}
