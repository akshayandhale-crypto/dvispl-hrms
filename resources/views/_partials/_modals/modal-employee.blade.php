<div wire:ignore.self class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-simple">
    <div class="modal-content p-0 p-md-5">
      <div class="modal-body">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-4">
          <h3 class="mb-2">{{ $isEdit ? __('Update Staff Member') : __('Add Hostel Staff') }}</h3>
          <p class="text-muted">{{ __('Enter the staff name, work department and hostel assignment.') }}</p>
        </div>
        <form wire:submit="submitEmployee" class="row g-3">
          <div class="col-12">
            <label class="form-label">{{ __('Full Name') }} *</label>
            <input wire:model="employeeInfo.firstName" class="form-control @error('employeeInfo.firstName') is-invalid @enderror" type="text" placeholder="Enter staff full name">
            @error('employeeInfo.firstName') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Hostel') }} *</label>
            <select wire:model="employeeInfo.hostelId" class="form-select @error('employeeInfo.hostelId') is-invalid @enderror">
              <option value="">Select Hostel</option>
              @foreach($hostels as $hostel)
                <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
              @endforeach
            </select>
            @error('employeeInfo.hostelId') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Department') }} *</label>
            <select wire:model="employeeInfo.department" class="form-select @error('employeeInfo.department') is-invalid @enderror">
              <option value="Housekeeping">Housekeeping</option>
              <option value="Security">Security</option>
            </select>
            @error('employeeInfo.department') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ __('Assignment Start Date') }} *</label>
            <input wire:model="employeeInfo.startDate" class="form-control @error('employeeInfo.startDate') is-invalid @enderror" type="date">
            @error('employeeInfo.startDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
          <div class="col-md-6 d-flex align-items-center">
            <div class="form-check mt-4">
              <input wire:model.live="employeeInfo.isReliever" class="form-check-input" type="checkbox" id="isReliever">
              <label class="form-check-label" for="isReliever">{{ __('Reliever Staff') }}</label>
            </div>
          </div>
          @if(!empty($employeeInfo['isReliever']))
            <div class="col-12">
              <label class="form-label">{{ __('Reliever Category') }} *</label>
              <select wire:model="employeeInfo.relieverType" class="form-select @error('employeeInfo.relieverType') is-invalid @enderror">
                <option value="">Select Reliever Category</option>
                <option value="Girls Reliever">Girls Reliever</option>
                <option value="Boys Reliever">Boys Reliever</option>
              </select>
              @error('employeeInfo.relieverType') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          @endif
          <div class="col-12">
            <label class="form-label">{{ __('Notes') }}</label>
            <textarea wire:model="employeeInfo.notes" class="form-control" rows="2"></textarea>
          </div>
          <div class="col-12 text-center mt-4">
            <button type="submit" class="btn btn-primary me-sm-3 me-1">{{ __('Save Staff') }}</button>
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
