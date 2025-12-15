@extends('layouts.app')

@section('title', 'View Vehicle')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('vehicles.index') }}" class="text-primary hover:underline">Vehicles</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>{{ $vehicle->vehicle_number }}</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-semibold dark:text-white-light">{{ $vehicle->vehicle_name }} ({{ $vehicle->vehicle_number }})</h2>
            <div class="flex gap-2">
                <a href="{{ route('vehicles.edit', $vehicle->id) }}" class="btn btn-info">Edit</a>
                <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Basic Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Basic Information</h5>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-white-dark">Vehicle Number:</span>
                        <span class="font-semibold">{{ $vehicle->vehicle_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Vehicle Name:</span>
                        <span class="font-semibold">{{ $vehicle->vehicle_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Plate Number:</span>
                        <span class="font-semibold">{{ $vehicle->vehicle_plate_number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Assigned To:</span>
                        <span class="font-semibold">{{ $vehicle->assigned_to ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Under Company:</span>
                        <span class="font-semibold">{{ $vehicle->under_company ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Insurance:</span>
                        <span class="font-semibold">{{ $vehicle->insurance ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white-dark">Status:</span>
                        <span class="badge {{ $vehicle->status === 'active' ? 'bg-success' : ($vehicle->status === 'maintenance' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($vehicle->status) }}
                        </span>
                    </div>
                    @if($vehicle->remarks)
                        <div class="flex justify-between">
                            <span class="text-white-dark">Remarks:</span>
                            <span class="font-semibold">{{ $vehicle->remarks }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Document Information -->
            <div class="panel">
                <div class="mb-4 border-b border-white-light pb-4 dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-semibold">Document Information</h5>
                </div>
                <div class="grid grid-cols-1 gap-4">
                    @php
                        $documents = [
                            ['field' => 'mulkiya_expiry_date', 'doc_field' => 'mulkiya_document', 'label' => 'Mulkiya'],
                            ['field' => 'driving_license_expiry_date', 'doc_field' => 'driving_license_document', 'label' => 'Driving License'],
                        ];
                    @endphp

                    @foreach($documents as $doc)
                        @php
                            $field = $doc['field'];
                            $docField = $doc['doc_field'];
                            $label = $doc['label'];
                            $hasExpiry = $vehicle->$field !== null;
                            $hasDocument = $vehicle->$docField !== null;
                        @endphp

                        @if($hasExpiry)
                            @php
                                $status = $vehicle->getDocumentStatus($vehicle->$field);
                            @endphp
                            <div class="rounded-lg border border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="font-semibold text-sm dark:text-white-light">{{ $label }}</span>
                                    <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                </div>
                                <p class="text-xs text-white-dark">
                                    {{ \Carbon\Carbon::parse($vehicle->$field)->format('d M Y') }}
                                </p>
                                @if($status['days'] >= 0)
                                    <p class="mt-1 text-xs font-semibold" style="color: {{ $status['class'] === 'success' ? '#00ab55' : '#e7515a' }}">
                                        {{ $status['days'] }} days left
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-semibold text-danger">
                                        Expired {{ abs($status['days']) }} days ago
                                    </p>
                                @endif

                                @if($hasDocument)
                                    <a href="{{ asset('storage/' . $vehicle->$docField) }}" target="_blank" class="mt-3 btn btn-sm btn-outline-primary w-full gap-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                        </svg>
                                        View Document
                                    </a>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-white-light p-4 dark:border-[#1b2e4b] bg-white dark:bg-[#0e1726]">
                                <span class="text-xs text-white-dark">{{ $label }}: Not set</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
