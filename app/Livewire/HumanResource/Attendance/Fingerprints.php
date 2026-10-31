<?php

namespace App\Livewire\HumanResource\Attendance;

use App\Exports\ExportFingerprints;
use App\Imports\ImportFingerprints;
use App\Livewire\Sections\Navbar\Navbar;
use App\Models\Employee;
use App\Models\Fingerprint;
use App\Models\Import;
use App\Notifications\DefaultNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Fingerprints extends Component
{
    use WithFileUploads, WithPagination;

    // Variables - Start //
    public $employees = [];

    public $selectedEmployee;

    public $selectedEmployeeId;

    public $dateRange;

    public $fromDate;

    public $toDate;

    public $isAbsence = false;

    public $isOneFingerprint = false;

    public $confirmedId;

    public $isEdit = false;

    public $fingerprint;

    public $date;

    public $checkIn;

    public $checkOut;

    public $file;
    // Variables - End //

    public function mount()
    {
        abort_unless(Auth::user()->hasAnyRole(['Admin', 'HR', 'Warden', 'Hostel Supervisor']), 403);

        $this->employees = $this->accessibleEmployees()->orderBy('first_name')->get();

        $this->selectedEmployeeId = Auth::user()->hasAnyRole(['Admin', 'HR'])
            ? (Auth::user()->employee_id ?: $this->employees->first()?->id)
            : $this->employees->first()?->id;
        $this->selectedEmployee = $this->selectedEmployeeId
            ? $this->accessibleEmployees()->find($this->selectedEmployeeId)
            : null;

        $currentDate = Carbon::now();
        $previousMonth = $currentDate->copy()->subMonth();
        $this->dateRange = $previousMonth->format('Y-m-01').' to '.$currentDate->format('Y-m-d');
    }

    protected function accessibleEmployees(): Builder
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Admin', 'HR'])) {
            return Employee::query()->where('is_active', true);
        }

        abort_unless($user->hasAnyRole(['Warden', 'Hostel Supervisor']), 403);

        $assignmentRoles = $user->getRoleNames()
            ->intersect(['Warden', 'Hostel Supervisor'])
            ->values()
            ->all();

        $hostelIds = $user->hostels()
            ->wherePivotIn('assignment_role', $assignmentRoles)
            ->where('hostels.is_active', true)
            ->select('hostels.id');

        return Employee::query()
            ->where('is_active', true)
            ->whereHas('hostelAssignments', function ($query) use ($hostelIds) {
                $query->whereIn('hostel_id', $hostelIds)
                    ->whereNull('end_date');
            });
    }

    protected function employeeIsAccessibleOnDate(int $employeeId, string $date): bool
    {
        return $this->accessibleEmployees()
            ->whereKey($employeeId)
            ->whereHas('hostelAssignments', function ($query) use ($date) {
                $query->whereDate('start_date', '<=', $date)
                    ->where(function ($assignment) use ($date) {
                        $assignment->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $date);
                    });
            })
            ->exists();
    }

    public function render()
    {
        $fingerprints = $this->applyFilter();

        return view('livewire.human-resource.attendance.fingerprints', [
            'fingerprints' => $fingerprints,
        ]);
    }

    public function applyFilter()
    {
        $dates = explode(' to ', (string) $this->dateRange);
        abort_unless(count($dates) === 2, 422);

        $this->fromDate = Carbon::parse($dates[0])->toDateString();
        $this->toDate = Carbon::parse($dates[1])->toDateString();
        abort_if($this->fromDate > $this->toDate, 422);

        $this->employees = $this->accessibleEmployees()->orderBy('first_name')->get();

        if (!$this->selectedEmployeeId) {
            $this->selectedEmployee = null;
            return Fingerprint::query()->whereRaw('1 = 0')->paginate(7);
        }

        abort_unless(
            $this->accessibleEmployees()->whereKey($this->selectedEmployeeId)->exists(),
            403
        );

        $this->selectedEmployee = Employee::findOrFail($this->selectedEmployeeId);

        $query = Fingerprint::query()
            ->where('employee_id', $this->selectedEmployeeId)
            ->whereBetween('date', [$this->fromDate, $this->toDate])
            ->when($this->isAbsence, fn ($q) => $q->whereNull('log'))
            ->when($this->isOneFingerprint, fn ($q) => $q->whereNotNull('check_in')->whereNull('check_out'))
            ->orderBy('date');

        if (!Auth::user()->hasAnyRole(['Admin', 'HR'])) {
            $query->whereHas('employee.hostelAssignments', function ($assignment) {
                $assignment->whereDate('start_date', '<=', $this->toDate)
                    ->where(function ($dates) {
                        $dates->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $this->fromDate);
                    })
                    ->whereIn('hostel_id', Auth::user()->hostels()
                        ->wherePivotIn('assignment_role', Auth::user()->getRoleNames()->intersect(['Warden', 'Hostel Supervisor'])->all())
                        ->select('hostels.id'));
            });
        }

        return $query->paginate(7);
    }

    public function submitFingerprint()
    {
        $this->isEdit ? $this->editFingerprint() : $this->addFingerprint();
    }

    #[Renderless]
    public function showNewFingerprintModal()
    {
        $this->reset('isEdit', 'date', 'checkIn', 'checkOut');
    }

    #[Renderless]
    public function showEditFingerprintModal(Fingerprint $fingerprint)
    {
        abort_unless(Auth::user()->hasAnyRole(['Admin', 'HR', 'Warden', 'Hostel Supervisor']), 403);

        $fingerprint = Fingerprint::findOrFail($fingerprint->id);
        abort_unless($this->employeeIsAccessibleOnDate((int) $fingerprint->employee_id, Carbon::parse($fingerprint->date)->toDateString()), 403);
        $this->selectedEmployeeId = $fingerprint->employee_id;
        $this->isEdit = true;

        $this->fingerprint = $fingerprint;

        $this->date = $fingerprint->date;
        $this->checkIn = $fingerprint->check_in;
        $this->checkOut = $fingerprint->check_out;
    }

    protected function validateManualAttendance(?int $ignoreFingerprintId = null): void
    {
        abort_unless(Auth::user()->hasAnyRole(['Admin', 'HR']), 403);

        $this->validate([
            'selectedEmployeeId' => 'required|integer|exists:employees,id',
            'date' => 'required|date_format:Y-m-d',
            'checkIn' => 'required|date_format:H:i',
            'checkOut' => 'required|date_format:H:i',
        ]);

        abort_unless($this->employeeIsAccessibleOnDate((int) $this->selectedEmployeeId, $this->date), 403);

        $duplicate = Fingerprint::query()
            ->where('employee_id', $this->selectedEmployeeId)
            ->whereDate('date', $this->date)
            ->when($ignoreFingerprintId, fn ($query) => $query->where('id', '!=', $ignoreFingerprintId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => __('An attendance record already exists for this employee on the selected date. Edit the existing record instead.'),
            ]);
        }
    }

    public function addFingerprint()
    {
        $this->validateManualAttendance();

        Fingerprint::create([
            'employee_id' => $this->selectedEmployeeId,
            'date' => $this->date,
            'log' => $this->checkIn.' '.$this->checkOut,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
        ]);

        $this->dispatch('closeCanvas', elementId: '#addRecordSidebar');
        $this->dispatch('toastr', type: 'success' /* , title: 'Done!' */, message: __('Going Well!'));
    }

    public function editFingerprint()
    {
        $fingerprintId = $this->fingerprint?->id;
        abort_unless($fingerprintId, 404);

        $this->validateManualAttendance((int) $fingerprintId);

        $fingerprint = Fingerprint::query()
            ->whereKey($fingerprintId)
            ->where('employee_id', $this->selectedEmployeeId)
            ->firstOrFail();

        $fingerprint->update([
            'date' => $this->date,
            'log' => $this->checkIn.' '.$this->checkOut,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
        ]);

        $this->dispatch('closeCanvas', elementId: '#addRecordSidebar');
        $this->dispatch('toastr', type: 'success' /* , title: 'Done!' */, message: __('Going Well!'));

        $this->reset('isEdit', 'date', 'checkIn', 'checkOut');
    }

    public function confirmDeleteFingerprint($id)
    {
        $this->confirmedId = $id;
    }

    public function deleteFingerprint(Fingerprint $fingerprint)
    {
        abort_unless(Auth::user()->hasAnyRole(['Admin', 'HR']), 403);

        $fingerprint = Fingerprint::query()
            ->whereKey($fingerprint->id)
            ->where('employee_id', $this->selectedEmployeeId)
            ->firstOrFail();
        abort_unless($this->employeeIsAccessibleOnDate((int) $fingerprint->employee_id, Carbon::parse($fingerprint->date)->toDateString()), 403);

        $fingerprint->delete();
        $this->dispatch('toastr', type: 'success' /* , title: 'Done!' */, message: __('Going Well!'));
    }

    public function importFromExcel()
    {
        abort_unless(Auth::user()->hasAnyRole(['Admin', 'HR']), 403);

        $this->validate(
            [
                'file' => 'required|mimes:xlsx',
            ],
            [
                'file.required' => 'Please select a file to upload',
                'file.mimes' => 'Excel files is accepted only',
            ]
        );

        try {
            $fileRecord = Import::create([
                'file_name' => $this->file->getClientOriginalName(),
                'file_size' => $this->file->getSize(),
                'file_ext' => $this->file->getClientOriginalExtension(),
                'file_type' => $this->file->getClientMimeType(),
                'status' => 'waiting',
            ]);

            $destinationPath = 'imports';
            $path = Storage::putFileAs($destinationPath, $this->file, $this->file->getClientOriginalName());

            $this->dispatch('activeProgressBar')->to(Navbar::class);

            $import_date = new ImportFingerprints(Auth::user()->id, $fileRecord->id);
            Excel::import($import_date, $path);

            Notification::send(
                Auth::user(),
                new DefaultNotification(Auth::user()->id, 'Successfully imported the fingerprint file')
            );
            $this->dispatch('refreshNotifications')->to(Navbar::class);

            session()->flash('info', __('Stay tuned! The file is doing a little dance as we speak.'));
        } catch (Exception $e) {
            session()->flash('error', __('Error occurred: ').$e->getMessage());
        }

        $this->dispatch('closeModal', elementId: '#importModal');
    }

    public function exportToExcel()
    {
        $fingerprints = $this->applyFilter()->getCollection();

        $fileName = 'Fingerprints - '.Carbon::now();

        return Excel::download(new ExportFingerprints($fingerprints), $fileName.'.xlsx');
    }
}
