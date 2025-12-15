<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntityController extends Controller
{
    public function index()
    {
        $entities = Entity::orderBy('entity_name')->get();
        return view('entities.index', compact('entities'));
    }

    public function create()
    {
        return view('entities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'entity_description' => 'nullable|string',
            'trade_license_renewal_date' => 'nullable|date',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'est_card_renewal_date' => 'nullable|date',
            'est_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'warehouse_ejari_renewal_date' => 'nullable|date',
            'warehouse_ejari_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'camp_ejari_renewal_date' => 'nullable|date',
            'camp_ejari_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'workman_insurance_expiry_date' => 'nullable|date',
            'workman_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $entity = Entity::create([
                'entity_name' => $validated['entity_name'],
                'entity_description' => $validated['entity_description'] ?? null,
                'trade_license_renewal_date' => $validated['trade_license_renewal_date'] ?? null,
                'est_card_renewal_date' => $validated['est_card_renewal_date'] ?? null,
                'warehouse_ejari_renewal_date' => $validated['warehouse_ejari_renewal_date'] ?? null,
                'camp_ejari_renewal_date' => $validated['camp_ejari_renewal_date'] ?? null,
                'workman_insurance_expiry_date' => $validated['workman_insurance_expiry_date'] ?? null,
                'status' => $validated['status'],
            ]);

            // Upload documents
            $documentFields = [
                'trade_license_document',
                'est_card_document',
                'warehouse_ejari_document',
                'camp_ejari_document',
                'workman_insurance_document',
            ];

            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filePath = $file->store('entity_documents/' . $entity->id, 'public');
                    $entity->update([$field => $filePath]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entity added successfully!',
                'redirect' => route('entities.index'),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Entity Store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create entity: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function show(Entity $entity)
    {
        return view('entities.show', compact('entity'));
    }

    public function edit(Entity $entity)
    {
        return view('entities.edit', compact('entity'));
    }

    public function update(Request $request, Entity $entity)
    {
        $validated = $request->validate([
            'entity_name' => 'required|string|max:255',
            'entity_description' => 'nullable|string',
            'trade_license_renewal_date' => 'nullable|date',
            'trade_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'est_card_renewal_date' => 'nullable|date',
            'est_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'warehouse_ejari_renewal_date' => 'nullable|date',
            'warehouse_ejari_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'camp_ejari_renewal_date' => 'nullable|date',
            'camp_ejari_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'workman_insurance_expiry_date' => 'nullable|date',
            'workman_insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            // Handle document uploads
            $documentFields = [
                'trade_license_document',
                'est_card_document',
                'warehouse_ejari_document',
                'camp_ejari_document',
                'workman_insurance_document',
            ];

            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    // Delete old document if exists
                    if ($entity->$field) {
                        Storage::disk('public')->delete($entity->$field);
                    }
                    $file = $request->file($field);
                    $validated[$field] = $file->store('entity_documents/' . $entity->id, 'public');
                }
            }

            $entity->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entity updated successfully!',
                'redirect' => route('entities.show', $entity),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Entity Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update entity: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Entity $entity)
    {
        try {
            // Delete all documents
            $documents = [
                $entity->trade_license_document,
                $entity->est_card_document,
                $entity->warehouse_ejari_document,
                $entity->camp_ejari_document,
                $entity->workman_insurance_document,
            ];

            foreach ($documents as $doc) {
                if ($doc) {
                    Storage::disk('public')->delete($doc);
                }
            }

            $entity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entity deleted successfully!',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Entity Delete Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entity: ' . $e->getMessage(),
            ], 422);
        }
    }
}
