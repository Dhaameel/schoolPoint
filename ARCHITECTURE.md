# schoolPoint — Architecture and Rebuild Plan

Status: **Stage 0 — plan only. No application features have been built.**

This document is the approval gate. Stage 1 must not begin until the product owner approves this plan.

The new application is a fresh Laravel 13 project at:

```text
C:\dev\schoolpoint
```

It has its own Git repository. No file from the old repository was copied into it. The old repository was tagged:

```text
full-version-green
```

The old code is reference material only. Ideas that may be reused:

- fee payment row locking
- one-time payment references
- receipt number format
- reversal instead of editing or deleting payments
- audit logging

Everything else is written fresh.

---

## 1. Product scope

schoolPoint is a school management system for a single school.

It manages:

- students and guardians as records
- teachers and their logins
- classes, subjects and teacher assignments
- daily attendance
- fee structures, fees and payments
- reports for students, teachers and classes
- audit history

It does not manage:

- student or guardian logins
- multiple schools or tenants
- a permissions package
- a repository/interface layer
- Docker
- online payment gateways
- a library, payroll or messaging module

---

## 2. Technology

| Item | Choice |
|---|---|
| Framework | Laravel 13 |
| PHP | PHP 8.4 |
| Database | MySQL 8.4 |
| Front end | Blade, Bootstrap 5.3 from npm, Vite |
| Font | Inter, self-hosted through `@fontsource/inter` |
| Icons | Bootstrap Icons from npm |
| JavaScript | Minimal vanilla JavaScript plus Bootstrap |
| Tests | PHPUnit, per module |
| Containers | None |

No CDN assets. No external asset URLs.

Palette:

```text
Blue:       #0b5ed7
Dark blue:  #0a3d62
Cream:      #fffdf5
Green:      #198754
Red:        #dc3545
Amber:      #d97706
```

---

## 3. Users and roles

There are exactly two login roles, stored in `users.role`:

```text
admin
teacher
```

The owner, head teacher and managers all use the `admin` role.

### Admin

The admin can manage the whole school:

```text
students, teachers, classes, subjects, attendance,
fees, reports and settings
```

### Teacher

Teacher accounts are created by an admin.

When an admin creates a teacher:

1. A user record and a teacher record are created.
2. A temporary password is generated.
3. The temporary password is shown to the admin once.
4. The teacher must change the password at first login.
5. The temporary password is never stored in plain text.

Teacher access is limited to:

```text
Dashboard
My classes
Attendance
```

A teacher can mark attendance only for classes and subjects assigned to them.

### Students and guardians

Students and guardians do not have logins.

A student is a record with:

- student ID
- optional admission number
- guardian name
- guardian phone
- class
- status

Guardians are not separate login accounts.

### Authorization

Authorization is implemented with Laravel Policies.

There is no `spatie/laravel-permission`, no roles table and no permissions table.

Rules:

- `admin` passes every policy.
- `teacher` passes only policies for their own assigned classes and today's attendance.
- Every controller action is protected by a Policy or an explicit admin check.
- No policy relies on a package.

---

## 4. Menu

### Admin

```text
Dashboard
Students
Teachers
Classes
Subjects
Attendance
Fees
Reports
Settings
```

### Teacher

```text
Dashboard
My classes
Attendance
```

No student or guardian menu exists.

Every menu item opens one page. One page has one job.

---

## 5. Application layers

The request flow is:

```text
Route
  → thin Controller
    → FormRequest validation
      → Action class when the operation has multiple steps
        → Eloquent model
          → Policy checks
```

Layer rules:

- No repository layer.
- No interface layer.
- No service container indirection unless Laravel needs it.
- Controllers are thin. They validate through a FormRequest, call an Action or model, and return a view or redirect.
- Multi-step operations go into one Action class.
- Business rules that affect one record may live on the model.
- Policies contain authorization only.
- FormRequests contain validation and authorization hand-off only.

### Folder structure

```text
app/
├─ Actions/
│  ├─ Attendance/
│  ├─ Fees/
│  ├─ Students/
│  └─ Teachers/
├─ Http/
│  ├─ Controllers/
│  │  ├─ Admin/
│  │  │  ├─ DashboardController.php
│  │  │  ├─ StudentController.php
│  │  │  ├─ TeacherController.php
│  │  │  ├─ ClassController.php
│  │  │  ├─ SubjectController.php
│  │  │  ├─ AttendanceController.php
│  │  │  ├─ FeeController.php
│  │  │  ├─ FeePaymentController.php
│  │  │  ├─ ReportController.php
│  │  │  └─ SettingController.php
│  │  └─ Teacher/
│  │     ├─ DashboardController.php
│  │     ├─ MyClassController.php
│  │     └─ AttendanceController.php
│  ├─ Middleware/
│  └─ Requests/
│     ├─ Admin/
│     └─ Teacher/
├─ Models/
├─ Policies/
└─ Support/
   └─ Money.php
```

Views are grouped by feature:

```text
resources/views/
├─ layouts/
├─ components/
├─ admin/
│  ├─ dashboard/
│  ├─ students/
│  ├─ teachers/
│  ├─ classes/
│  ├─ subjects/
│  ├─ attendance/
│  ├─ fees/
│  ├─ reports/
│  └─ settings/
├─ teacher/
│  ├─ dashboard/
│  ├─ classes/
│  └─ attendance/
├─ reports/
│  ├─ student/
│  ├─ teacher/
│  └─ class/
└─ print/
```

All user-facing text is in language files:

```text
lang/en/
lang/so/
lang/ar/
```

No hardcoded labels in Blade or controllers. Arabic readiness includes `dir="rtl"` support.

---

## 6. Database

All tables use `id`, `created_at` and `updated_at` unless noted.

### `users`

| Column | Type | Notes |
|---|---|---|
| `name` | string | login name |
| `email` | string | unique |
| `password` | string | hashed |
| `role` | string | `admin` or `teacher` |
| `must_change_password` | boolean | default `false` |
| `is_active` | boolean | default `true` |
| `remember_token` | string nullable | Laravel |

No hard deletes. Deactivate with `is_active`.

### `teachers`

| Column | Type | Notes |
|---|---|---|
| `user_id` | foreign key | unique, references `users` |
| `employee_number` | string nullable | unique when present |
| `phone` | string nullable | |
| `status` | string | `active` or `inactive` |

No hard deletes. Set `status` to `inactive`.

### `students`

| Column | Type | Notes |
|---|---|---|
| `student_id` | string | unique, generated |
| `admission_number` | string nullable | optional |
| `first_name` | string | |
| `last_name` | string | |
| `gender` | string nullable | |
| `date_of_birth` | date nullable | |
| `guardian_name` | string | |
| `guardian_phone` | string | |
| `class_id` | foreign key | references `school_classes` |
| `status` | string | `active`, `graduated`, `left` |
| `student_year` | integer | year used in the ID |
| `student_sequence` | integer | sequence used in the ID |

Indexes:

```text
unique(student_id)
unique(student_year, student_sequence)
index(class_id, status)
```

No hard deletes. Status is changed instead.

Student ID format:

```text
{PREFIX}-{YEAR}-{SEQUENCE}
```

Example:

```text
SP-2026-000001
```

- `PREFIX` comes from `settings.student_id_prefix`.
- `YEAR` is the current year.
- `SEQUENCE` is zero-padded and unique per year.
- Generation runs inside a transaction and locks the sequence source.
- A unique index is the final guard against duplicates.

### `school_classes`

| Column | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `code` | string nullable | unique when present |
| `status` | string | `active` or `archived` |

No sections. A class is one class.

### `subjects`

| Column | Type | Notes |
|---|---|---|
| `name` | string | unique |
| `code` | string nullable | unique when present |

### `class_subject`

| Column | Type | Notes |
|---|---|---|
| `class_id` | foreign key | |
| `subject_id` | foreign key | |
| `teacher_id` | foreign key | references `teachers` |

Rules:

```text
unique(class_id, subject_id)
```

One teacher is assigned per subject per class. Reassigning is an update.

### `attendance`

| Column | Type | Notes |
|---|---|---|
| `student_id` | foreign key | |
| `class_id` | foreign key | |
| `date` | date | |
| `status` | string | `present`, `absent`, `late` |
| `marked_by` | foreign key | references `users` |

Rules:

```text
unique(student_id, date)
index(class_id, date)
```

- One row per student per day.
- Teachers can create and edit only today's attendance.
- Admins can create and edit any date.
- Attendance is never hard-deleted.

### `fee_structures`

| Column | Type | Notes |
|---|---|---|
| `class_id` | foreign key | |
| `name` | string | e.g. Term 1 tuition |
| `amount` | decimal(12,2) | |
| `currency` | char(3) | copied to each fee |
| `is_active` | boolean | default `true` |

Rules:

```text
unique(class_id, name, currency)
```

### `fees`

| Column | Type | Notes |
|---|---|---|
| `student_id` | foreign key | |
| `fee_structure_id` | foreign key nullable | null for a manual fee |
| `title` | string | |
| `amount` | decimal(12,2) | |
| `amount_paid` | decimal(12,2) | default `0.00` |
| `currency` | char(3) | stored on the fee |
| `status` | string | `unpaid`, `partial`, `paid`, `void` |
| `due_date` | date nullable | |
| `billed_at` | datetime nullable | |
| `voided_at` | datetime nullable | |
| `void_reason` | text nullable | |

Rules:

```text
unique(student_id, fee_structure_id)
```

A null `fee_structure_id` allows multiple manual fees for the same student.

No hard deletes. Fees are voided with a reason.

### `fee_payments`

| Column | Type | Notes |
|---|---|---|
| `fee_id` | foreign key | |
| `reference` | string | unique, one-time |
| `receipt_number` | string | unique |
| `amount` | decimal(12,2) | |
| `type` | string | `payment` or `reversal` |
| `reversal_of_id` | foreign key nullable | unique, references `fee_payments` |
| `reason` | text nullable | required on reversal |
| `paid_at` | datetime | |
| `method` | string nullable | cash, bank, mobile money |
| `note` | text nullable | |
| `created_by` | foreign key | references `users` |

No update or delete routes. A mistake is corrected by a reversal.

### `audit_logs`

| Column | Type | Notes |
|---|---|---|
| `user_id` | foreign key nullable | |
| `action` | string | e.g. `fees.payment_recorded` |
| `description` | text nullable | |
| `metadata` | json nullable | ids, amounts, reasons |

Indexes:

```text
index(action)
index(created_at)
```

### `settings`

| Column | Type | Notes |
|---|---|---|
| `key` | string | unique |
| `value` | text nullable | |

Example keys:

```text
school_name
school_logo_path
currency
student_id_prefix
receipt_prefix
timezone
```

---

## 7. Business rules

### 7.1 Teacher account creation

Action: `CreateTeacherAccountAction`

1. Admin submits teacher name, e-mail and phone.
2. Action creates the `users` row with role `teacher`.
3. Action creates the `teachers` row.
4. A random temporary password is generated.
5. The password is hashed and stored.
6. The plain temporary password is returned once for display.
7. `must_change_password` is set to `true`.
8. Audit log records `teachers.created`.
9. At first login, middleware redirects to the password-change page.
10. The teacher cannot open any other page until the password is changed.

### 7.2 Student creation

Action: `CreateStudentAction`

1. Admin submits student details and class.
2. Action starts a database transaction.
3. It generates the next student ID sequence for the year.
4. It inserts the student with status `active`.
5. It writes an audit log entry.
6. It commits.

No delete route exists.

### 7.3 Attendance

Action: `MarkAttendanceAction`

1. Admin or teacher opens a class roster for a date.
2. The page shows one row per active student.
3. Each row has a status: present, absent or late.
4. Saving uses an upsert on `(student_id, date)`.
5. The action records `marked_by`.
6. Teachers are blocked from dates other than today.
7. Teachers are blocked from classes they do not teach.
8. Admins can open and correct any date.
9. Audit log records the class, date and marker.

### 7.4 Fees

Actions:

```text
BillClassFeesAction
RecordFeePaymentAction
ReverseFeePaymentAction
VoidFeeAction
```

Bill the whole class:

1. Admin selects a fee structure.
2. Action loads all active students in that class.
3. It creates one fee per student.
4. It copies amount and currency onto each fee.
5. It skips students who already have that fee structure.
6. It writes one audit entry with the count.

Record a payment:

1. Validate reference, amount and paid date.
2. Start a database transaction.
3. Lock the fee row with `lockForUpdate`.
4. Reject a voided fee.
5. Reject a duplicate reference.
6. Reject zero, negative or over-balance amounts.
7. Create the payment.
8. Generate the receipt number inside the transaction.
9. Recalculate `amount_paid` and `status`.
10. Write the audit log.
11. Commit.

Receipt format:

```text
{PREFIX}-{YYYYMMDD}-{SEQUENCE}
```

Example:

```text
RCPT-20261109-000001
```

Reversal:

1. Lock the fee and payment rows.
2. Reject a reversal of a reversal.
3. Reject a second reversal of the same payment.
4. Require a reason.
5. Create a reversal row.
6. Recalculate the fee balance and status.
7. Write the audit log.

Void:

1. Lock the fee row.
2. Reject an already-voided fee.
3. Reject a fee with net payments.
4. Require a reason.
5. Set status to `void`, `voided_at` and `void_reason`.
6. Keep the record visible.
7. Exclude voided fees from totals.
8. Write the audit log.

Money is stored as `DECIMAL(12,2)` and calculated with `bcmath`. No floats.

### 7.5 Reports

One reusable date-range component:

```text
Today
This week
This month
This year
Custom range
```

Every report uses the same component and validates the range.

Student report:

```text
profile
fees billed
fees paid
balance
attendance summary for the range
```

Teacher report:

```text
classes and subjects assigned
attendance records marked in the range
```

Class report:

```text
attendance percentage for the range
fees collected
fees outstanding
list of students who owe
```

Every report has:

```text
HTML print view
CSV download
```

### 7.6 Audit

Audit actions include:

```text
teachers.created
teachers.updated
students.created
students.updated
students.status_changed
classes.created
classes.updated
subjects.created
subjects.updated
class_subject.assigned
attendance.marked
attendance.updated
fees.structure_created
fees.billed
fees.payment_recorded
fees.payment_reversed
fees.voided
settings.updated
```

Audit metadata stores IDs, amounts, dates and reasons. It never stores passwords or password hashes.

---

## 8. UI rules

- Bootstrap 5.3 from npm, bundled by Vite.
- Inter self-hosted through `@fontsource/inter`.
- Bootstrap Icons from npm.
- No CDN links.
- Sidebar and top bar share one layout.
- Admin sidebar: Dashboard, Students, Teachers, Classes, Subjects, Attendance, Fees, Reports, Settings.
- Teacher sidebar: Dashboard, My classes, Attendance.
- Active item uses the blue fill.
- Every list has a search box.
- Every empty state explains what to do and includes a button.
- One page does one job.
- Forms use plain words and show validation next to the field.
- Destructive actions require confirmation and a reason where appropriate.
- Tables are responsive.
- Print views hide navigation and buttons.
- Currency comes from settings and is stored on each fee.
- Arabic readiness: language files and `dir` support.

---

## 9. Testing strategy

Tests are written per module, not at the end.

Required test groups:

```text
Auth and forced password change
Users and teacher accounts
Students and student ID generation
Classes
Subjects and class_subject
Attendance rules and uniqueness
Fee structures and class billing
Fee payments, locking, references and receipts
Reversals and voids
Reports and date ranges
Settings
Policies for admin and teacher
Audit logs
```

Important tests:

- teacher first login forces password change
- temporary password cannot be reused after change
- student ID is unique and sequential
- students cannot be hard-deleted
- teacher cannot mark attendance for another class
- teacher cannot edit yesterday
- admin can edit any date
- duplicate attendance for `(student, date)` is rejected
- two payment attempts cannot overpay
- duplicate payment reference records once
- receipt number is unique and follows the format
- reversal restores balance
- void requires no net payments and a reason
- teacher cannot open fees or reports
- every report respects the selected date range
- CSV and print views contain the same figures

Both SQLite and MySQL test runs are required. The MySQL suite must include the two-connection fee lock test.

---

## 10. Delivery stages

### Stage 0 — Plan and scaffold

Status: **current stage**

Deliverables:

- old repository tagged `full-version-green`
- fresh Laravel 13 project at `C:\dev\schoolpoint`
- own Git repository
- this document
- private GitHub repository steps

Tag:

```text
stage-0-plan-green
```

Exit gate: product owner approval.

### Stage 1 — Foundation

Tag:

```text
stage-1-foundation-green
```

Scope:

- MySQL configuration
- authentication for admin and teacher
- forced password change
- layouts, navigation and flash messages
- settings table and settings page
- audit log base
- language files and translation helper
- Vite, Bootstrap 5.3, Inter and Bootstrap Icons
- tests for auth, settings and audit

### Stage 2 — People and academics

Tag:

```text
stage-2-people-green
```

Scope:

- students, teacher accounts, classes and subjects
- student ID generation
- class_subject assignments
- search and empty states
- policies for admin and teacher
- no hard deletes
- tests for every module

### Stage 3 — Attendance

Tag:

```text
stage-3-attendance-green
```

Scope:

- daily class roster
- one row per student per day
- teacher today-only rule
- admin any-date rule
- attendance summary
- tests for uniqueness and policy rules

### Stage 4 — Fees

Tag:

```text
stage-4-fees-green
```

Scope:

- fee structures
- bill-the-whole-class action
- payments with row lock and one-time reference
- receipt numbers
- reversals and voids
- currency per fee
- audit log
- concurrency test

### Stage 5 — Reports

Tag:

```text
stage-5-reports-green
```

Scope:

- shared date-range component
- student report
- teacher report
- class report
- print views
- CSV downloads
- tests for ranges and totals

### Stage 6 — UI and language polish

Tag:

```text
stage-6-ui-green
```

Scope:

- complete Bootstrap/Vite styling
- responsive sidebar and tables
- search on every list
- empty states with actions
- all text moved to language files
- Somali and Arabic skeleton files
- RTL check
- browser checks on desktop and phone

### Stage 7 — Release hardening

Tag:

```text
stage-7-release-green
```

Scope:

- full SQLite and MySQL suites
- security review
- backup and restore guide
- deployment guide
- final browser checks
- final release notes

---

## 11. Private GitHub repository

The old repository already uses the name `schoolPoint`, so the rebuild needs a
different name. Proposed name:

```text
schoolpoint-rebuild
```

Create the repository manually:

1. Open `https://github.com/new`.
2. Owner: `Dhaameel`.
3. Name: `schoolpoint-rebuild`.
4. Visibility: **Private**.
5. Do not add a README, `.gitignore` or license.
6. Create the repository.

Then, after approval and before pushing:

```powershell
cd C:\dev\schoolpoint
git remote add origin https://github.com/Dhaameel/schoolpoint-rebuild.git
git push -u origin main
git push origin --tags
```

GitHub authentication must be completed by the owner. Use GitHub Desktop or a
personal access token. Never paste a token into chat.

If another repository name is preferred, use that name in both the GitHub form
and the `git remote add` command.

---

## 12. Approval gate

Stage 0 stops here.

Nothing from Stage 1 will be built until the owner approves:

- the roles and policy approach
- the table design
- the attendance rules
- the fee rules
- the report set
- the stage order
- the private GitHub repository name and owner
