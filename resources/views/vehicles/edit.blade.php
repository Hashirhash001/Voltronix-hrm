@extends('layouts.app')

@section('title', 'Edit Vehicle')

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
            <span>Edit Vehicle</span>
        </li>
    </ul>

    <div class="pt-5">
        <form id="vehicleForm" action="{{ route('vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Edit Vehicle: {{ $vehicle->vehicle_number }}</h5>
                </div>

                <!-- Basic Information -->
                <h6 class="mb-4 text-base font-bold">Basic Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label for="vehicle_number">Vehicle Number <span class="text-danger">*</span></label>
                        <input id="vehicle_number" type="text" name="vehicle_number" class="form-input" placeholder="e.g., E0001" value="{{ old('vehicle_number', $vehicle->vehicle_number) }}" required/>
                    </div>

                    <div>
                        <label for="vehicle_name">Vehicle Name <span class="text-danger">*</span></label>
                        <input id="vehicle_name" type="text" name="vehicle_name" class="form-input" placeholder="e.g., PRADO" value="{{ old('vehicle_name', $vehicle->vehicle_name) }}" required/>
                    </div>

                    <div>
                        <label for="vehicle_plate_number">Vehicle Plate Number <span class="text-danger">*</span></label>
                        <input id="vehicle_plate_number" type="text" name="vehicle_plate_number" class="form-input" placeholder="e.g., S70710" value="{{ old('vehicle_plate_number', $vehicle->vehicle_plate_number) }}" required/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mt-5">
                    <div>
                        <label for="assigned_to">Assigned To</label>
                        <input id="assigned_to" type="text" name="assigned_to" class="form-input" placeholder="e.g., MARIES" value="{{ old('assigned_to', $vehicle->assigned_to) }}"/>
                    </div>

                    <div>
                        <label for="under_company">Under Company</label>
                        <select id="under_company" name="under_company" class="form-select">
                            <option value="">Select Company</option>
                            <option value="MARIES" {{ old('under_company', $vehicle->under_company) === 'MARIES' ? 'selected' : '' }}>MARIES</option>
                            <option value="KALI" {{ old('under_company', $vehicle->under_company) === 'KALI' ? 'selected' : '' }}>KALI</option>
                            <option value="CONT" {{ old('under_company', $vehicle->under_company) === 'CONT' ? 'selected' : '' }}>CONT</option>
                            <option value="SWITCH" {{ old('under_company', $vehicle->under_company) === 'SWITCH' ? 'selected' : '' }}>SWITCH</option>
                        </select>
                    </div>

                    <div>
                        <label for="insurance">Insurance</label>
                        <input id="insurance" type="text" name="insurance" class="form-input" placeholder="Insurance details" value="{{ old('insurance', $vehicle->insurance) }}"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status', $vehicle->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $vehicle->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="maintenance" {{ old('status', $vehicle->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>

                    <div>
                        <label for="remarks">Remarks</label>
                        <input id="remarks" type="text" name="remarks" class="form-input" placeholder="Any additional notes" value="{{ old('remarks', $vehicle->remarks) }}"/>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Document Information -->
                <h6 class="mb-4 text-base font-bold">Document Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="mulkiya_expiry_date">Mulkiya Expiry Date</label>
                        <input id="mulkiya_expiry_date" type="date" name="mulkiya_expiry_date" class="form-input" value="{{ old('mulkiya_expiry_date', $vehicle->mulkiya_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="mulkiya_document">Mulkiya Document</label>
                        @if($vehicle->mulkiya_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $vehicle->mulkiya_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="mulkiya_document" type="file" name="mulkiya_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="driving_license_expiry_date">Driving License Expiry Date</label>
                        <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" class="form-input" value="{{ old('driving_license_expiry_date', $vehicle->driving_license_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="driving_license_document">Driving License Document</label>
                        @if($vehicle->driving_license_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $vehicle->driving_license_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="driving_license_document" type="file" name="driving_license_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-success gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.5 2.5L21.5 5.5M22 4L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Update Vehicle
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('vehicleForm');
        const submitBtn = document.getElementById('submitBtn');

        if (form && submitBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const originalHTML = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin h-4 w-4 inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Updating...';

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = data.redirect;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#d33'
                        });
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An unexpected error occurred.',
                        confirmButtonColor: '#d33'
                    });
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHTML;
                });
            });
        }
    });
</script>
@endpush
@endsection
