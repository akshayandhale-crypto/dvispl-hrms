<div>
@php $configData = Helper::appClasses(); @endphp
@section('title', 'Hostel Staff - HRMS')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-1">Hostel Staff</h4>
    <p class="text-muted mb-0">Manage housekeeping, security and reliever staff across hostels.</p>
  </div>
  <button wire:click="showCreateEmployeeModal" type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">
    <span class="ti ti-plus me-1"></span>{{ __('Add Staff Member') }}
  </button>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Staff Directory</h5>
    <div class="col-md-4">
      <input wire:model.live.debounce.300ms="searchTerm" type="search" class="form-control" placeholder="Search name, hostel or department">
    </div>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Staff Name</th><th>Hostel</th><th>Department</th><th>Category</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($employees as $employee)
          @php $assignment = $employee->hostelAssignments->first(); @endphp
          <tr>
            <td>{{ $employee->id }}</td>
            <td>
              <a href="{{ route('structure-employees-info', $employee->id) }}">{{ $employee->full_name }}</a>
            </td>
            <td>{{ $assignment?->hostel?->name ?? 'Not assigned' }}</td>
            <td>{{ $employee->staff_department }}</td>
            <td>
              @if($employee->is_reliever)
                <span class="badge bg-label-info">{{ $employee->reliever_type ?: 'Reliever' }}</span>
              @else
                <span class="badge bg-label-secondary">Regular</span>
              @endif
            </td>
            <td>
              @if($employee->is_active)
                <span class="badge bg-label-success">Active</span>
              @else
                <span class="badge bg-label-danger">Inactive</span>
              @endif
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" wire:click="showEditEmployeeModal({{ $employee->id }})" data-bs-toggle="modal" data-bs-target="#employeeModal" title="Edit">
                <i class="ti ti-pencil"></i>
              </button>
              <button type="button" class="btn btn-sm btn-icon btn-outline-danger" wire:click="confirmDeleteEmployee({{ $employee->id }})" title="Archive">
                <i class="ti ti-archive"></i>
              </button>
              @if($confirmedId === $employee->id)
                <button type="button" class="btn btn-sm btn-danger" wire:click="deleteEmployee({{ $employee->id }})">Confirm archive</button>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center py-5">
            <h5>No staff records yet</h5>
            <p class="text-muted mb-0">Add your hostel staff or import the approved staff list when ready.</p>
          </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $employees->links() }}</div>
</div>

@include('_partials/_modals/modal-employee')
</div>
