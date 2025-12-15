@extends('layouts.app')

@section('title', 'Edit Entity')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <a href="{{ route('entities.index') }}" class="text-primary hover:underline">Entities</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Edit Entity</span>
        </li>
    </ul>

    <div class="pt-5">
        <form id="entityForm" action="{{ route('entities.update', $entity) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <div class="mb-5 flex items-center justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">Edit Entity: {{ $entity->entity_name }}</h5>
                </div>

                <!-- Basic Information -->
                <h6 class="mb-4 text-base font-bold">Basic Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="entity_name">Entity Name <span class="text-danger">*</span></label>
                        <input id="entity_name" type="text" name="entity_name" class="form-input" placeholder="e.g., VOLTRONIX CONTRACTING LLC" value="{{ old('entity_name', $entity->entity_name) }}" required/>
                    </div>

                    <div>
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="active" {{ old('status', $entity->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $entity->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="entity_description">Description</label>
                    <textarea id="entity_description" name="entity_description" rows="3" class="form-textarea" placeholder="Enter entity description">{{ old('entity_description', $entity->entity_description) }}</textarea>
                </div>

                <hr class="my-6 border-white-light dark:border-[#1b2e4b]">

                <!-- Document Information -->
                <h6 class="mb-4 text-base font-bold">Document Information</h6>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label for="trade_license_renewal_date">Trade License Renewal Date</label>
                        <input id="trade_license_renewal_date" type="date" name="trade_license_renewal_date" class="form-input" value="{{ old('trade_license_renewal_date', $entity->trade_license_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="trade_license_document">Trade License Document</label>
                        @if($entity->trade_license_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $entity->trade_license_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="trade_license_document" type="file" name="trade_license_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="est_card_renewal_date">EST Card Renewal Date</label>
                        <input id="est_card_renewal_date" type="date" name="est_card_renewal_date" class="form-input" value="{{ old('est_card_renewal_date', $entity->est_card_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="est_card_document">EST Card Document</label>
                        @if($entity->est_card_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $entity->est_card_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="est_card_document" type="file" name="est_card_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="warehouse_ejari_renewal_date">Warehouse EJARI Renewal Date</label>
                        <input id="warehouse_ejari_renewal_date" type="date" name="warehouse_ejari_renewal_date" class="form-input" value="{{ old('warehouse_ejari_renewal_date', $entity->warehouse_ejari_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="warehouse_ejari_document">Warehouse EJARI Document</label>
                        @if($entity->warehouse_ejari_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $entity->warehouse_ejari_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="warehouse_ejari_document" type="file" name="warehouse_ejari_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="camp_ejari_renewal_date">Camp EJARI Renewal Date</label>
                        <input id="camp_ejari_renewal_date" type="date" name="camp_ejari_renewal_date" class="form-input" value="{{ old('camp_ejari_renewal_date', $entity->camp_ejari_renewal_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="camp_ejari_document">Camp EJARI Document</label>
                        @if($entity->camp_ejari_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $entity->camp_ejari_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="camp_ejari_document" type="file" name="camp_ejari_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 mt-5">
                    <div>
                        <label for="workman_insurance_expiry_date">Workman Insurance Expiry Date</label>
                        <input id="workman_insurance_expiry_date" type="date" name="workman_insurance_expiry_date" class="form-input" value="{{ old('workman_insurance_expiry_date', $entity->workman_insurance_expiry_date?->format('Y-m-d')) }}"/>
                    </div>
                    <div>
                        <label for="workman_insurance_document">Workman Insurance Document</label>
                        @if($entity->workman_insurance_document)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $entity->workman_insurance_document) }}" target="_blank" class="text-primary hover:underline text-sm flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z"/>
                                    </svg>
                                    View Current
                                </a>
                            </div>
                        @endif
                        <input id="workman_insurance_document" type="file" name="workman_insurance_document" class="form-input" accept=".pdf,.jpg,.jpeg,.png">
                        <p class="text-xs text-gray-500 mt-1">Max: 5MB (PDF, JPG, PNG)</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex items-center justify-end gap-2">
                    <a href="{{ route('entities.show', $entity) }}" class="btn btn-outline-danger">Cancel</a>
                    <button type="submit" id="submitBtn" class="btn btn-success gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11 4H4C2.89543 4 2 4.89543 2 6V20C2 21.1046 2.89543 22 4 22H18C19.1046 22 20 21.1046 20 20V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M18.5 2.5L21.5 5.5M22 4L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Update Entity
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('entityForm');
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
