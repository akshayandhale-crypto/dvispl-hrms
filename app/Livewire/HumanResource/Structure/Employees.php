<?php

namespace App\Livewire\HumanResource\Structure;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\Hostel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Employees extends Component
{
    use WithPagination;

    public $searchTerm = '';
    public $contracts;
    public $hostels;
    public $employee;
    public $employeeInfo = [];
    public $isEdit = false;
    public $confirmedId;

    public function mount(): void
    {
        $this->contracts = Contract::all();
        $this->hostels = Hostel::where('is_active', true)->orderBy('name')->get();
    }

    public function render()
    {
        $employees = Employee::query()
            ->with(['hostelAssignments' => function ($query) {
                $query->whereNull('end_date')->with('hostel')->latest('start_date');
            }])
            ->when($this->searchTerm, function ($query) {
                $term = '%'.$this->searchTerm.'%';
                $query->where(function ($search) use ($term) {
                    $search->where('id', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('staff_department', 'like', $term)
                        ->orWhereHas('hostelAssignments.hostel', fn ($hostel) => $hostel->where('name', 'like', $term));
                });
            })
            ->orderBy('id')
            ->paginate(20);

        return view('livewire.human-resource.structure.employees', [
            'employees' => $employees,
        ]);
    }

    public function submitEmployee(): void
    {
        $this->validate([
            'employeeInfo.firstName' => 'required|string|max:255',
            'employeeInfo.hostelId' => 'required|integer|exists:hostels,id',
            'employeeInfo.department' => 'required|in:Housekeeping,Security',
            'employeeInfo.startDate' => 'required|date',
            'employeeInfo.isReliever' => 'boolean',
            'employeeInfo.relieverType' => 'nullable|required_if:employeeInfo.isReliever,1|in:Girls Reliever,Boys Reliever',
            'employeeInfo.notes' => 'nullable|string|max:1000',
        ]);

        $this->isEdit ? $this->editEmployee() : $this->addEmployee();
    }

    public function showCreateEmployeeModal(): void
    {
        $this->reset('isEdit', 'employee', 'employeeInfo');
        $this->employeeInfo = [
            'department' => 'Housekeeping',
            'startDate' => now()->toDateString(),
            'isReliever' => false,
            'relieverType' => '',
        ];
    }

    public function addEmployee()
    {
        $createdEmployee = DB::transaction(function () {
            $employee = Employee::create([
                'contract_id' => null,
                'first_name' => trim($this->employeeInfo['firstName']),
                'father_name' => null,
                'last_name' => null,
                'mother_name' => null,
                'birth_and_place' => null,
                'national_number' => null,
                'mobile_number' => null,
                'degree' => null,
                'gender' => null,
                'address' => null,
                'staff_department' => $this->employeeInfo['department'],
                'is_reliever' => (bool) ($this->employeeInfo['isReliever'] ?? false),
                'reliever_type' => ($this->employeeInfo['isReliever'] ?? false) ? $this->employeeInfo['relieverType'] : null,
                'notes' => $this->employeeInfo['notes'] ?? null,
                'profile_photo_path' => null,
            ]);

            $employee->hostelAssignments()->create([
                'hostel_id' => $this->employeeInfo['hostelId'],
                'start_date' => $this->employeeInfo['startDate'],
                'notes' => $this->employeeInfo['isReliever'] ?? false ? 'Reliever staff' : null,
            ]);

            return $employee;
        });

        $this->dispatch('closeModal', elementId: '#employeeModal');
        $this->dispatch('toastr', type: 'success', message: __('Employee added successfully.'));
        return redirect()->route('structure-employees');
    }

    public function showEditEmployeeModal(Employee $employee): void
    {
        $this->isEdit = true;
        $this->employee = $employee;
        $assignment = $employee->hostelAssignments()->whereNull('end_date')->latest('start_date')->first();

        $this->employeeInfo = [
            'firstName' => $employee->first_name,
            'department' => $employee->staff_department,
            'hostelId' => $assignment?->hostel_id,
            'startDate' => $assignment?->start_date?->format('Y-m-d') ?? now()->toDateString(),
            'isReliever' => (bool) $employee->is_reliever,
            'relieverType' => $employee->reliever_type,
            'notes' => $employee->notes,
        ];
    }

    public function editEmployee(): void
    {
        DB::transaction(function () {
            $this->employee->update([
                'first_name' => trim($this->employeeInfo['firstName']),
                'staff_department' => $this->employeeInfo['department'],
                'is_reliever' => (bool) ($this->employeeInfo['isReliever'] ?? false),
                'reliever_type' => ($this->employeeInfo['isReliever'] ?? false) ? $this->employeeInfo['relieverType'] : null,
                'notes' => $this->employeeInfo['notes'] ?? null,
            ]);

            $assignment = $this->employee->hostelAssignments()->whereNull('end_date')->latest('start_date')->first();
            if ($assignment && (int) $assignment->hostel_id === (int) $this->employeeInfo['hostelId']) {
                $assignment->update(['start_date' => $this->employeeInfo['startDate']]);
            } else {
                if ($assignment) {
                    $assignment->update(['end_date' => now()->toDateString()]);
                }

                $this->employee->hostelAssignments()->create([
                    'hostel_id' => $this->employeeInfo['hostelId'],
                    'start_date' => $this->employeeInfo['startDate'],
                    'notes' => 'Hostel assignment updated',
                ]);
            }
        });

        $this->dispatch('closeModal', elementId: '#employeeModal');
        $this->dispatch('toastr', type: 'success', message: __('Employee updated successfully.'));
    }

    public function confirmDeleteEmployee($id): void
    {
        $this->confirmedId = (int) $id;
    }

    public function deleteEmployee(Employee $employee): void
    {
        $employee->delete();
        $this->confirmedId = null;
        $this->dispatch('toastr', type: 'success', message: __('Employee archived successfully.'));
    }
}
