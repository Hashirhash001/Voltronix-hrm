@extends('layouts.app')

@section('title', 'Entities Management')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Entities</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl">Entities Management</h2>
            <a href="{{ route('entities.create') }}" class="btn btn-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Entity
            </a>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @forelse($entities as $entity)
                <div class="panel">
                    <div class="mb-5 flex items-center justify-between">
                        <h5 class="text-lg font-semibold dark:text-white-light">{{ $entity->entity_name }}</h5>
                        <span class="badge {{ $entity->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($entity->status) }}
                        </span>
                    </div>

                    @if($entity->entity_description)
                        <p class="mb-4 text-white-dark">{{ $entity->entity_description }}</p>
                    @endif

                    <!-- Documents Grid -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @php
                            $documents = [
                                ['field' => 'trade_license_renewal_date', 'doc_field' => 'trade_license_document', 'label' => 'Trade License'],
                                ['field' => 'est_card_renewal_date', 'doc_field' => 'est_card_document', 'label' => 'EST Card'],
                                ['field' => 'warehouse_ejari_renewal_date', 'doc_field' => 'warehouse_ejari_document', 'label' => 'Warehouse EJARI'],
                                ['field' => 'camp_ejari_renewal_date', 'doc_field' => 'camp_ejari_document', 'label' => 'Camp EJARI'],
                                ['field' => 'workman_insurance_expiry_date', 'doc_field' => 'workman_insurance_document', 'label' => 'Workman Insurance'],
                            ];
                        @endphp

                        @foreach($documents as $doc)
                            @php
                                $field = $doc['field'];
                                $docField = $doc['doc_field'];
                                $status = $entity->getDocumentStatus($entity->$field);
                            @endphp

                            @if($entity->$field)
                                <div class="rounded border border-white-light p-3 dark:border-[#1b2e4b]">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-xs font-semibold">{{ $doc['label'] }}</span>
                                        <span class="badge bg-{{ $status['class'] }} text-xs">{{ $status['label'] }}</span>
                                    </div>
                                    <p class="text-xs text-white-dark">
                                        {{ $entity->$field->format('d M Y') }}
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
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-5 flex items-center justify-end gap-2">
                        <a href="{{ route('entities.show', $entity) }}" class="btn btn-primary btn-sm">View Details</a>
                        <a href="{{ route('entities.edit', $entity) }}" class="btn btn-info btn-sm">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" onclick="deleteEntity({{ $entity->id }})">Delete</button>
                    </div>
                </div>
            @empty
                <div class="panel lg:col-span-2">
                    <div class="flex min-h-[400px] items-center justify-center">
                        <p class="text-center text-white-dark">
                            No entities found. <a href="{{ route('entities.create') }}" class="text-primary hover:underline">Add your first entity</a>
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    function deleteEntity(entityId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/entities/${entityId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the entity.',
                        confirmButtonColor: '#d33'
                    });
                });
            }
        });
    }
</script>
@endpush
@endsection
