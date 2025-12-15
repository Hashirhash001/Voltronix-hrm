<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number' => 'required|string|unique:vehicles,vehicle_number',
            'vehicle_name' => 'required|string|max:255',
            'vehicle_plate_number' => 'required|string',
            'assigned_to' => 'nullable|string',
            'under_company' => 'nullable|string',
            'mulkiya_expiry_date' => 'nullable|date',
            'mulkiya_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'driving_license_expiry_date' => 'nullable|date',
            'driving_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'insurance' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $vehicle = Vehicle::create([
                'vehicle_number' => $validated['vehicle_number'],
                'vehicle_name' => $validated['vehicle_name'],
                'vehicle_plate_number' => $validated['vehicle_plate_number'],
                'assigned_to' => $validated['assigned_to'] ?? null,
                'under_company' => $validated['under_company'] ?? null,
                'mulkiya_expiry_date' => $validated['mulkiya_expiry_date'] ?? null,
                'driving_license_expiry_date' => $validated['driving_license_expiry_date'] ?? null,
                'insurance' => $validated['insurance'] ?? null,
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Upload documents
            $documentFields = [
                'mulkiya_document',
                'driving_license_document',
            ];

            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filePath = $file->store('vehicle_documents/' . $vehicle->id, 'public');
                    $vehicle->update([$field => $filePath]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle added successfully!',
                'redirect' => route('vehicles.index'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Vehicle Store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create vehicle: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function show(Vehicle $vehicle)
    {
        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'vehicle_number' => 'required|string|unique:vehicles,vehicle_number,' . $vehicle->id,
            'vehicle_name' => 'required|string|max:255',
            'vehicle_plate_number' => 'required|string',
            'assigned_to' => 'nullable|string',
            'under_company' => 'nullable|string',
            'mulkiya_expiry_date' => 'nullable|date',
            'mulkiya_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'driving_license_expiry_date' => 'nullable|date',
            'driving_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'insurance' => 'nullable|string',
            'status' => 'required|in:active,inactive,maintenance',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Handle document uploads
            $documentFields = [
                'mulkiya_document',
                'driving_license_document',
            ];

            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    // Delete old document if exists
                    if ($vehicle->$field) {
                        Storage::disk('public')->delete($vehicle->$field);
                    }
                    $file = $request->file($field);
                    $validated[$field] = $file->store('vehicle_documents/' . $vehicle->id, 'public');
                }
            }

            $vehicle->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle updated successfully!',
                'redirect' => route('vehicles.show', $vehicle),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Vehicle Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vehicle: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Vehicle $vehicle)
    {
        try {
            // Delete all documents
            $documents = [
                $vehicle->mulkiya_document,
                $vehicle->driving_license_document,
            ];

            foreach ($documents as $doc) {
                if ($doc) {
                    Storage::disk('public')->delete($doc);
                }
            }

            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vehicle deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Vehicle Delete Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete vehicle: ' . $e->getMessage(),
            ], 422);
        }
    }
}
