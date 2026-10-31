# DVISPL HRMS Audit and Development Roadmap

Audit baseline: 2026-10-31
Target users: Office employees and staff across multiple hostels.
Confirmed attendance approach: Manual attendance entry (no biometric device integration in this phase).
Confirmed payroll scope: Attendance and leave data for payroll preparation only; no salary calculation/payslip module until separately approved.
Priority: Stabilize existing workflows before adding payroll or other modules.

## Current application inventory

- Laravel 10, Livewire 3, MySQL-oriented schema, Laravel Sail/Docker.
- Existing areas: employee records, organization structure (centers/departments/positions), timelines, attendance records currently named fingerprints, Excel attendance import, leave management, holidays, discounts, messaging, asset inventory, user roles.
- Existing automated tests are primarily Laravel Jetstream authentication/profile/session/token scaffolding. HR attendance, leave, imports, authorization boundaries, payroll and multi-hostel workflows have no meaningful feature coverage identified in the current test tree.
- No payroll, salary structure, payslip, recruitment, candidate or onboarding implementation was found in the current application tree.

## Confirmed issues from static review

### Critical / high

1. **Leave record authorization and integrity**
   - The Livewire leave component accepts client-controlled employee and pivot record IDs.
   - Update/delete flows fetch records by ID without a demonstrated employee/center authorization scope.
   - The leave form needs explicit validation for leave type, date order, daily/hourly rules, and overlapping leave requests.
   - Component initialization assumes the signed-in user has an employee record and a current timeline; null relationships can cause runtime errors.

2. **Fingerprint attendance access and validation**
   - Fingerprint component loads all employees into a public Livewire property.
   - Employee selection and fingerprint edit/delete actions need server-side authorization and center/hostel scoping.
   - Manual attendance writes validate only required fields; date/time formats, check-in/check-out order and duplicate employee/date records need validation.
   - Existing Excel importer expects columns ac_no, date, time and treats ac_no as the internal employee primary key. This must be verified against the actual biometric machine export before connecting a device.
   - Import status is updated row-by-row, which may be inefficient for large attendance files.

3. **Employee timeline integrity**
   - Timeline state changes assume a timeline exists in some paths.
   - Updating, deleting or setting a timeline as current does not visibly enforce exactly one active timeline per employee.
   - Timeline operations should be transaction-safe and validate center, department, position and date ranges.

4. **Demo credentials and seeders**
   - AdminUserSeeder creates admin@demo.com with the known password admin.
   - DatabaseSeeder assumes the administrator is user ID 1 and creates roles without an idempotent lookup.
   - Several demo seeders use random records and are not safe to run against a populated production database. Keep demo seeding disabled in production and replace fixed credentials with an explicit local-only setup flow.

5. **Database and data quality**
   - Employee schema has unique national number and mobile number fields; employee input validation and existing imported data must be checked for duplicates and country-specific formatting before enforcing stricter rules.
   - The timelines.is_sequent property is referenced by the model/component but absent from the original timeline creation migration. A forward migration is included in this stabilization branch.
   - Audit fields (created_by, updated_by, deleted_by) are required on several tables; verify all import and background-job paths populate them consistently.
   - Review indexes and foreign-key delete behavior after inspecting a copy of the actual database.

### Medium

6. **Automated tests**
   - Add feature tests for role and center boundaries, leave create/update/approve/reject/delete, attendance import validation/deduplication, timeline changes, and payroll calculations.
   - Configure tests to use a disposable SQLite/MySQL test database. Never run destructive test migrations against production data.

7. **Configuration and build**
   - Stabilization branch corrects the recursive npm build script, India timezone defaults, and Docker Compose user/group defaults.
   - These changes have not yet been run through Composer, npm, Docker, PHPUnit or migrations in the actual development workspace.

## Target architecture

### Shared employee master

One employee master for all DVISPL staff with:
- Employee code, legal name and contact details
- Employment type, joining/exit dates and status
- Department, designation and reporting manager
- Work category: Office / Hostel / Other
- User account and role mapping
- Document metadata and audit trail

### Multiple hostel structure

Keep organizational department/designation separate from physical work location:
- Work locations / hostels (one office location plus multiple hostel locations)
- Employee assignment history with effective dates
- Primary reporting manager / warden and supervisor relationships
- Shift, weekly-off and holiday calendars by location
- Staff transfer history without changing employee identity

Do not overload the existing centers table until its current production usage and holiday/working-hour dependencies are verified.

### Hostel attendance access foundation (current implementation phase)

- Added separate `hostels`, `hostel_user` and `employee_hostel_assignments` tables. This deliberately does not overload the existing organizational `centers` table.
- Added Hostel and EmployeeHostelAssignment models and User/Employee relationships.
- Added Warden and Hostel Supervisor roles through an idempotent seeder.
- Attendance route now admits Admin, HR, Warden and Hostel Supervisor. Manual attendance list and mutations are scoped to employees with a current assignment in a hostel assigned to that user; record edits/deletes verify the assignment against the attendance date.
- Excel import remains restricted to Admin/HR.
- Remaining prerequisite before wardens can use this live: an Admin-facing screen/process to create hostel records, assign staff to hostels, and map users to hostels with the correct assignment_role. Until that assignment data exists, Warden/Supervisor accounts correctly see no employees.
- Add automated authorization tests and staging verification before merge. Existing attendance table name `fingerprints` is retained for backward compatibility for now.

### Manual attendance and payroll preparation

- Allow authorized HR/Admin users to add, edit and review daily check-in/check-out records.
- Add Admin-facing hostel and user/employee assignment management, then complete Warden/Supervisor attendance workflow scoped to assigned hostels; do not grant broad HR access to hostel users.
- Validate employee, date and time formats; prevent duplicate employee/date entries and retain who created or amended each record.
- Add reason and approver details for corrections, late marks, missing punches and overtime.
- Derive monthly payable days, paid/unpaid leave, absences and approved overtime as payroll inputs.
- Payroll scope in this phase is attendance/leave reporting and export only. Salary structures, statutory calculations and payslips are out of scope until explicitly approved.
- Keep the existing Excel import only if HR confirms it is an approved manual/bulk-upload process; do not connect biometric hardware or treat machine identifiers as employee database IDs.

### Payroll (after attendance foundation)

- Effective-dated salary structures and components
- Earnings, allowances, reimbursements and deductions
- Attendance/leave inputs and approved overtime
- Statutory settings configured by qualified payroll/finance staff
- Payroll period, maker-checker approval and locked payroll runs
- Payslip generation, payroll register and accounting exports
- Audit trail and reversal/adjustment process

Do not hardcode statutory rates or tax rules; make them configurable and verify with current professional guidance.

## Delivery order

1. Stabilize employee master, role/center boundaries, leave, timelines and imports.
2. Verify migration compatibility and data quality on a database copy.
3. Add automated tests and staging validation.
4. Add work locations, hostel assignments, shift and warden/supervisor scope.
5. Add multiple-hostel assignment, warden/supervisor access and shift calendars.
6. Build attendance-based payroll preparation reports and exports (not salary calculation/payslips).
7. Improve dashboards, employee self-service and management MIS.

## Required validation before merge/deployment

- composer install / composer validate
- npm ci and npm run build
- PHP syntax checks and Laravel Pint
- php artisan test on a disposable test database
- php artisan migrate on a staging database copy
- Docker build and application smoke test
- Review PR diff and migration rollback
- Backup and approved deployment window

Never use migrate:fresh on a database containing real employee records.
