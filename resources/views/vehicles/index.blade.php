@extends('layouts.app')

@section('title', 'Vehicles Management')

@section('content')
<div>
    <ul class="flex space-x-2 rtl:space-x-reverse">
        <li>
            <a href="{{ route('dashboard') }}" class="text-primary hover:underline">Dashboard</a>
        </li>
        <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
            <span>Vehicles</span>
        </li>
    </ul>

    <div class="pt-5">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl">Vehicles Management</h2>
            <a href="{{ route('vehicles.create') }}" class="btn btn-primary gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Vehicle
            </a>
        </div>

        <div class="panel">
            <div class="mb-5">
                <div class="table-responsive">
                    <table class="table-hover w-full">
                        <thead>
                            <tr>
                                <th class="text-center">S.No</th>
                                <th class="text-center">Vehicle Number</th>
                                <th>Vehicle Name</th>
                                <th class="text-center">Plate Number</th>
                                <th>Assigned To</th>
                                <th>Company</th>
                                <th class="text-center">Mulkiya Expiry</th>
                                <th class="text-center">License Expiry</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $index => $vehicle)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="text-center font-semibold">{{ $vehicle->vehicle_number }}</td>
                                    <td>{{ $vehicle->vehicle_name }}</td>
                                    <td class="text-center">{{ $vehicle->vehicle_plate_number }}</td>
                                    <td>{{ $vehicle->assigned_to ?? 'N/A' }}</td>
                                    <td>{{ $vehicle->under_company ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        @if($vehicle->mulkiya_expiry_date)
                                            @php
                                                $status = $vehicle->getDocumentStatus($vehicle->mulkiya_expiry_date);
                                            @endphp
                                            <span class="badge bg-{{ $status['class'] }} whitespace-nowrap">
                                                {{ $vehicle->mulkiya_expiry_date->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-white-dark">Not set</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($vehicle->driving_license_expiry_date)
                                            @php
                                                $status = $vehicle->getDocumentStatus($vehicle->driving_license_expiry_date);
                                            @endphp
                                            <span class="badge bg-{{ $status['class'] }} whitespace-nowrap">
                                                {{ $vehicle->driving_license_expiry_date->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-white-dark">Not set</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $vehicle->status === 'active' ? 'bg-success' : ($vehicle->status === 'maintenance' ? 'bg-warning' : 'bg-danger') }}">
                                            {{ ucfirst($vehicle->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-primary">
                                                View
                                            </a>
                                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-info">
                                                Edit
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteVehicle({{ $vehicle->id }})">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-white-dark py-10">
                                        No vehicles found. <a href="{{ route('vehicles.create') }}" class="text-primary hover:underline">Add your first vehicle</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function deleteVehicle(vehicleId) {
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
                fetch(`/vehicles/${vehicleId}`, {
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
                        text: 'An error occurred while deleting the vehicle.',
                        confirmButtonColor: '#d33'
                    });
                });
            }
        });
    }
</script>
@endpush
@endsection
