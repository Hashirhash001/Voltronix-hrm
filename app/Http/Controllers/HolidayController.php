<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Entity;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $query = Holiday::with('entity')
            ->orderBy('holiday_date', 'desc');

        // Filter by year
        if ($request->has('year')) {
            $year = $request->year;
            $query->whereYear('holiday_date', $year);
        } else {
            $query->whereYear('holiday_date', Carbon::now()->year);
        }

        // Filter by entity
        if ($request->has('entity_id') && $request->entity_id) {
            $query->forEntity($request->entity_id);
        }

        $holidays = $query->paginate(15);
        $entities = Entity::orderBy('entity_name')->get();
        $years = range(Carbon::now()->year - 1, Carbon::now()->year + 2);

        return view('holidays.index', compact('holidays', 'entities', 'years'));
    }

    public function show(Holiday $holiday)
    {
        return response()->json([
            'success' => true,
            'holiday' => $holiday->load('entity'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'type' => 'required|in:public,optional,entity_specific',
            'entity_id' => 'nullable|exists:entities,id',
            'description' => 'nullable|string',
        ]);

        try {
            $holiday = Holiday::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Holiday created successfully!',
                'holiday' => $holiday->load('entity'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create holiday: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'holiday_name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'type' => 'required|in:public,optional,entity_specific',
            'entity_id' => 'nullable|exists:entities,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $holiday->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully!',
                'holiday' => $holiday->load('entity'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update holiday: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Holiday $holiday)
    {
        try {
            $holiday->delete();

            return response()->json([
                'success' => true,
                'message' => 'Holiday deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete holiday: ' . $e->getMessage(),
            ], 422);
        }
    }

    // Check if a date is a holiday
    public function checkDate(Request $request)
    {
        $date = $request->input('date');
        $entityId = $request->input('entity_id');

        $isHoliday = Holiday::isHoliday($date, $entityId);
        $holidayName = Holiday::getHolidayName($date, $entityId);

        return response()->json([
            'is_holiday' => $isHoliday,
            'holiday_name' => $holidayName,
        ]);
    }

    // Get holidays for calendar/date range
    public function getHolidaysInRange(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());
        $entityId = $request->input('entity_id');

        $query = Holiday::with('entity')
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$startDate, $endDate]);

        if ($entityId) {
            $query->where(function($q) use ($entityId) {
                $q->whereNull('entity_id')
                  ->orWhere('entity_id', $entityId);
            });
        }

        $holidays = $query->orderBy('holiday_date', 'asc')->get();

        // Format dates properly for JavaScript
        $holidays = $holidays->map(function($holiday) {
            return [
                'id' => $holiday->id,
                'holiday_name' => $holiday->holiday_name,
                'holiday_date' => $holiday->holiday_date->format('Y-m-d'), // ✅ Force Y-m-d format
                'type' => $holiday->type,
                'entity_id' => $holiday->entity_id,
                'description' => $holiday->description,
                'is_active' => $holiday->is_active,
                'entity' => $holiday->entity ? [
                    'id' => $holiday->entity->id,
                    'entity_name' => $holiday->entity->entity_name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'holidays' => $holidays,
        ]);
    }
}
