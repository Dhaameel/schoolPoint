# schoolPoint — Architecture and Rebuild Plan

Status: **Stage 0 — plan approved with changes. Stage 1 has not started.**

This document is the approval gate for the rebuild.

The old repository was tagged:

```text
full-version-green
```

The old repository is reference material only. The new application is a fresh Laravel 13 project at:

```text
C:\dev\schoolpoint
```

It has its own Git repository. No file from the old repository was copied into it.

Reusable ideas from the old code:

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
- a repository or interface layer
- Docker
- online payment gateways
- a library, payroll or messaging module
- an e-mail service in version 1

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

Users log in with a unique username. E-mail is optional and is not used in
version 1: no login by e-mail, no e-mail verification and no e-mail password
reset. The username is required and unique.

### 3.1 Admin accounts

Admins can manage the whole school:

```text
students, teachers, classes, subjects, attendance,
fees, reports, settings, admin accounts
```

Admin accounts support optional TOTP two-factor authentication:

- package: `pragmarx/google2fa`
- QR code drawn inside the application
- recovery codes generated once and stored encrypted
- each recovery code can be used once
- 2FA can be enabled or disabled from the Security page
- 2FA is optional per admin account

Password resets in version 1 are handled without e-mail:

- an admin can reset another admin or teacher password;
- a server operator can reset a password with an artisan command.

Version 1 needs no e-mail service, no password-reset mail route and no SMTP configuration.

Artisan commands:

```text
app:create-admin
app:reset-password
app:disable-two-factor
app:fees-reconcile
```

`app:create-admin`:

- prompts for name, username and password;
- creates an admin account;
- assigns `role = admin`;
- optionally marks the password as temporary.

`app:reset-password`:

- takes a username;
- sets a new password;
- marks `must_change_password = true`;
- never prints the existing password.

`app:disable-two-factor`:

- takes a username;
- clears the two-factor secret, confirmation and recovery codes;
- leaves the account password unchanged.

`app:fees-reconcile`:

- scans fees in chunks or by id range;
- recalculates `amount_paid` from payment and reversal rows;
- reports mismatches before and after correction;
- supports `--dry-run`;
- writes an audit log entry when it changes a fee.

Login protection:

- log in by username and password;
- rate-limit login attempts by username and IP address;
- use Laravel's rate limiter and throttling middleware;
- return a generic error for wrong credentials;
- log authentication failures without logging passwords.

### 3.2 Teacher accounts

Teachers receive logins created by an admin.

When an admin creates a teacher:

1. The admin chooses the teacher's unique username.
2. A user record and a teacher record are created.
3. A temporary password is generated.
4. The temporary password is shown to the admin once.
5. The teacher must change the password at first login.
6. The temporary password is never stored in plain text.

Admin controls for a teacher:

- deactivate the teacher;
- reactivate the teacher;
- reset the teacher password.

A deactivated teacher:

- cannot log in;
- has existing sessions invalidated where practical;
- keeps their records for reports and history.

Teacher access is limited to:

```text
Dashboard
My classes
Attendance
```

A teacher sees only classes where they are:

- the class teacher (`school_classes.class_teacher_id`), or
- a subject teacher (`class_subject.teacher_id`).

A teacher has no access to:

```text
fees
settings
admin reports
student management
teacher management
class or subject management
```

Policy tests must prove these restrictions.

### 3.3 Students and guardians

Students and guardians do not have logins.

A student is a record with:

- student ID
- optional admission number
- guardian name
- guardian phone
- class
- status

Guardians are not separate login accounts.

### 3.4 Authorization

Authorization uses Laravel Policies.

There is no `spatie/laravel-permission`, no roles table and no permissions table.

Rules:

- `admin` passes every policy.
- `teacher` passes only policies for assigned classes and today's attendance.
- Every controller action is protected by a Policy or an explicit admin check.
- Policies never rely on a permissions package.
- Deactivated users fail authentication before policy checks.

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
- Controllers are thin.
- Multi-step operations go into one Action class.
- Business rules affecting one record may live on the model.
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
├─ Console/Commands/
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

Conventions:

- all tables use `id`, `created_at` and `updated_at` unless noted;
- money is `DECIMAL(12,2)`, never float;
- academic and fee records use foreign keys with `ON DELETE RESTRICT` and `ON UPDATE RESTRICT`;
- students, teachers and fees are never hard-deleted;
- students use `status`;
- teachers use `status`;
- fees use `status = void` and `void_reason`;
- academic and fee history must remain readable.

### 6.1 `users`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `name` | string | required |
| `username` | string | required, unique, login identifier |
| `email` | string nullable | optional, not used in v1 |
| `password` | string | hashed |
| `role` | string | `admin` or `teacher` |
| `must_change_password` | boolean | default `false` |
| `is_active` | boolean | default `true` |
| `two_factor_secret` | text nullable | encrypted |
| `two_factor_recovery_codes` | text nullable | encrypted array |
| `two_factor_confirmed_at` | timestamp nullable | |
| `last_login_at` | timestamp nullable | |
| `remember_token` | string nullable | Laravel |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(username)
index(email)
index(role)
index(is_active)
```

Foreign keys: none.

No hard deletes.

### 6.2 `teachers`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `user_id` | bigint unsigned | unique, FK → `users.id` |
| `employee_number` | string nullable | unique when present |
| `phone` | string nullable | |
| `photo_path` | string nullable | |
| `status` | string | `active` or `inactive` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(user_id)
unique(employee_number)
index(status)
```

Foreign keys:

```text
user_id → users.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

No hard deletes. Set `status = inactive`.

### 6.3 `students`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `student_id` | string | unique, generated |
| `admission_number` | string nullable | unique when present |
| `first_name` | string | required |
| `last_name` | string | required |
| `gender` | string nullable | |
| `date_of_birth` | date nullable | |
| `guardian_name` | string | required |
| `guardian_phone` | string | required |
| `photo_path` | string nullable | |
| `class_id` | bigint unsigned | FK → `school_classes.id` |
| `status` | string | `active`, `graduated`, `left` |
| `student_year` | integer | year used in the ID |
| `student_sequence` | integer | sequence used in the ID |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(student_id)
unique(admission_number)
unique(student_year, student_sequence)
index(class_id, status)
index(status)
```

Foreign keys:

```text
class_id → school_classes.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

No hard deletes. Change `status` instead.

### 6.4 `student_id_counters`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `year` | integer | unique |
| `last_sequence` | integer unsigned | default `0` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(year)
```

Foreign keys: none.

Student ID generation:

1. Start a database transaction.
2. `SELECT ... FOR UPDATE` the counter row for the current year.
3. Increment `last_sequence`.
4. Format `{PREFIX}-{YEAR}-{SEQUENCE}`.
5. Insert the student.
6. Commit.
7. The unique index on `students.student_id` is the final guard.

Example:

```text
SP-2026-000001
```

### 6.5 `school_classes`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `name` | string | unique |
| `code` | string nullable | unique when present |
| `class_teacher_id` | bigint unsigned nullable | FK → `teachers.id` |
| `status` | string | `active` or `archived` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(name)
unique(code)
index(class_teacher_id)
index(status)
```

Foreign keys:

```text
class_teacher_id → teachers.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

No sections. A class is one class.

### 6.6 `subjects`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `name` | string | unique |
| `code` | string nullable | unique when present |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(name)
unique(code)
```

Foreign keys: none.

### 6.7 `class_subject`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `class_id` | bigint unsigned | FK → `school_classes.id` |
| `subject_id` | bigint unsigned | FK → `subjects.id` |
| `teacher_id` | bigint unsigned nullable | FK → `teachers.id` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(class_id, subject_id)
index(teacher_id)
index(subject_id)
```

Foreign keys:

```text
class_id → school_classes.id ON DELETE RESTRICT ON UPDATE RESTRICT
subject_id → subjects.id ON DELETE RESTRICT ON UPDATE RESTRICT
teacher_id → teachers.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

One teacher may be assigned per subject per class. The teacher is nullable, so a
class subject can exist before a teacher is chosen. Reassigning is an update.

### 6.8 `attendance`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `student_id` | bigint unsigned | FK → `students.id` |
| `class_id` | bigint unsigned | FK → `school_classes.id` |
| `date` | date | required |
| `status` | string | `present`, `absent`, `late` |
| `marked_by` | bigint unsigned | FK → `users.id` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(student_id, date)
index(date)
index(class_id, date)
index(marked_by)
```

Foreign keys:

```text
student_id → students.id ON DELETE RESTRICT ON UPDATE RESTRICT
class_id → school_classes.id ON DELETE RESTRICT ON UPDATE RESTRICT
marked_by → users.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

Rules:

- one row per student per day;
- teachers create and edit today only;
- admins create and edit any date;
- attendance is never hard-deleted.

### 6.9 `fee_structures`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `class_id` | bigint unsigned | FK → `school_classes.id` |
| `name` | string | required |
| `amount` | decimal(12,2) | required |
| `currency` | char(3) | required |
| `is_active` | boolean | default `true` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(class_id, name, currency)
index(class_id)
index(is_active)
```

Foreign keys:

```text
class_id → school_classes.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

### 6.10 `fees`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `student_id` | bigint unsigned | FK → `students.id` |
| `class_id` | bigint unsigned | FK → `school_classes.id`, copied at billing time |
| `fee_structure_id` | bigint unsigned nullable | FK → `fee_structures.id` |
| `title` | string | required |
| `amount` | decimal(12,2) | required |
| `amount_paid` | decimal(12,2) | default `0.00`, recalculated inside the locked payment transaction |
| `currency` | char(3) | copied from the structure |
| `status` | string | `unpaid`, `partial`, `paid`, `void` |
| `due_date` | date nullable | |
| `billed_at` | datetime nullable | |
| `voided_at` | datetime nullable | |
| `void_reason` | text nullable | required when voiding |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(student_id, fee_structure_id)
index(student_id)
index(class_id)
index(fee_structure_id)
index(status)
index(due_date)
```

Foreign keys:

```text
student_id → students.id ON DELETE RESTRICT ON UPDATE RESTRICT
class_id → school_classes.id ON DELETE RESTRICT ON UPDATE RESTRICT
fee_structure_id → fee_structures.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

The unique `(student_id, fee_structure_id)` prevents billing the same class twice from creating duplicate fees. A null `fee_structure_id` allows manual fees; MySQL permits multiple NULL values in a unique index.

No hard deletes. Fees are voided with a reason.

### 6.11 `fee_payments`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `fee_id` | bigint unsigned | FK → `fees.id` |
| `reference` | string | unique, one-time |
| `receipt_number` | string | unique |
| `amount` | decimal(12,2) | required |
| `type` | string | `payment` or `reversal` |
| `reversal_of_id` | bigint unsigned nullable | unique, FK → `fee_payments.id` |
| `reason` | text nullable | required on reversal |
| `paid_at` | datetime | required |
| `method` | string nullable | cash, bank, mobile money |
| `note` | text nullable | |
| `created_by` | bigint unsigned | FK → `users.id` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(reference)
unique(receipt_number)
unique(reversal_of_id)
index(fee_id)
index(paid_at)
index(created_by)
```

Foreign keys:

```text
fee_id → fees.id ON DELETE RESTRICT ON UPDATE RESTRICT
reversal_of_id → fee_payments.id ON DELETE RESTRICT ON UPDATE RESTRICT
created_by → users.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

No update or delete routes. Mistakes are corrected by a reversal.

### 6.12 `audit_logs`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `user_id` | bigint unsigned nullable | FK → `users.id` |
| `action` | string | required |
| `description` | text nullable | |
| `metadata` | json nullable | ids, amounts, reasons |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
index(action)
index(created_at)
index(user_id)
```

Foreign keys:

```text
user_id → users.id ON DELETE RESTRICT ON UPDATE RESTRICT
```

Audit logs never store passwords or password hashes.

### 6.13 `settings`

| Column | Type | Rules |
|---|---|---|
| `id` | bigint unsigned | primary key |
| `key` | string | unique |
| `value` | text nullable | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indexes:

```text
unique(key)
```

Foreign keys: none.

Example keys:

```text
school_name
school_logo_path
currency
student_id_prefix
receipt_prefix
timezone
```

The application timezone is read from `settings.timezone` at boot. The
package-level default is a fallback only. All "today" checks use the configured
timezone, not the server timezone and not UTC blindly.

---

## 7. Business rules

### 7.1 Admin 2FA and commands

Admins can enable TOTP from the Security page.

Flow:

1. Generate a secret with `pragmarx/google2fa`.
2. Draw the QR code inside the application.
3. Show the secret for manual entry.
4. Confirm with a 6-digit code.
5. Generate recovery codes once.
6. Store secret and recovery codes encrypted.
7. At login, ask for a code only when 2FA is confirmed.
8. A recovery code is removed after use.

Artisan commands:

```text
app:create-admin
app:reset-password
app:disable-two-factor
```

No e-mail service is required in version 1.

Login rate limiting:

- throttle failed logins by e-mail and IP;
- use a generic error message;
- never log the submitted password.

### 7.2 Teacher account creation and deactivation

Action: `CreateTeacherAccountAction`

1. Admin submits teacher name, e-mail and phone.
2. Action creates the `users` row with role `teacher`.
3. Action creates the `teachers` row.
4. A random temporary password is generated.
5. The password is hashed.
6. The plain temporary password is returned once for display.
7. `must_change_password` is set to `true`.
8. Audit log records `teachers.created`.
9. At first login, middleware redirects to the password-change page.
10. The teacher cannot open any other page until the password is changed.

Admin can deactivate a teacher:

- set `teachers.status = inactive`;
- set `users.is_active = false`;
- audit log `teachers.deactivated`.

A deactivated teacher cannot log in.

Admin can reset a teacher password:

- generate or accept a temporary password;
- hash it;
- set `must_change_password = true`;
- audit log `teachers.password_reset`.

### 7.3 Student creation

Action: `CreateStudentAction`

1. Admin submits student details and class.
2. Start a transaction.
3. Lock the `student_id_counters` row for the year.
4. Increment the sequence.
5. Generate `{PREFIX}-{YEAR}-{SEQUENCE}`.
6. Insert the student with status `active`.
7. Write an audit log entry.
8. Commit.

No delete route exists.

### 7.4 Attendance

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

### 7.5 Fees

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
4. It copies amount, currency and class_id onto each fee.
5. It skips students who already have that fee structure.
6. The unique `(student_id, fee_structure_id)` index prevents duplicates.
7. It writes one audit entry with the count.

Record a payment:

1. Validate reference, amount and paid date.
2. Start a database transaction.
3. Lock the fee row with `lockForUpdate`.
4. Reject a voided fee.
5. Reject a duplicate reference.
6. Reject zero, negative or over-balance amounts.
7. Create the payment.
8. Generate the receipt number inside the transaction.
9. Recalculate `fees.amount_paid` from all payment and reversal rows while
   the fee row is still locked.
10. Recalculate `status` from the new `amount_paid`.
11. Write the audit log.
12. Commit.

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
6. Recalculate `amount_paid` from all payment and reversal rows while the fee
   row is still locked.
7. Recalculate the fee status.
8. Write the audit log.

Void:

1. Lock the fee row.
2. Reject an already-voided fee.
3. Reject a fee with net payments.
4. Require a reason.
5. Set status to `void`, `voided_at` and `void_reason`.
6. Keep the record visible.
7. Exclude voided fees from totals.
8. Write the audit log.

Money is stored as `DECIMAL(12,2)` and calculated with `bcmath`.

`app:fees-reconcile` detects and repairs `amount_paid` values that do not
match the payment rows. Tests must prove that a deliberately corrupted
`amount_paid` is detected, that the command repairs it, and that the corrected
value matches the sum of payments minus reversals.

### 7.6 Reports

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

Report indexes:

```text
attendance.date
fee_payments.paid_at
fees.due_date
```

### 7.7 Audit

Audit actions include:

```text
admins.created
admins.password_reset
admins.two_factor_enabled
admins.two_factor_disabled
teachers.created
teachers.updated
teachers.deactivated
teachers.reactivated
teachers.password_reset
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

## 8. Backups

Version 1 must document:

- a daily `mysqldump` backup;
- retention of at least 14 daily files;
- backup location outside the web root;
- file permissions that protect the dump;
- a restore rehearsal in Stage 7.

Example daily command:

```sh
mysqldump \
  --single-transaction \
  --routines \
  --triggers \
  --set-gtid-purged=OFF \
  -u schoolpoint -p schoolpoint \
  | gzip > /var/backups/schoolpoint/schoolpoint-$(date +%F).sql.gz
```

Example cron entry:

```cron
0 2 * * * /usr/local/bin/schoolpoint-backup.sh >> /var/log/schoolpoint-backup.log 2>&1
```

Stage 7 must include an actual restore rehearsal into a temporary database, a smoke test, and removal of the temporary database.

---

## 9. UI rules

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

The final theme, login page and application layout are built in Stage 1 from the stated palette and the approved reference screens, before any module page. Every later stage is built on the finished look.

---

## 10. Localization

All user-facing text is loaded with `__()`.

Initial language files:

```text
lang/en/
lang/so/
lang/ar/
```

Rules:

- English ships complete in version 1;
- `lang/so` and `lang/ar` are present and wired but intentionally untranslated;
- no machine translation is added;
- no hardcoded UI text in Blade or controllers;
- validation messages use language keys;
- date and currency formats come from settings or locale helpers;
- Arabic layout must support right-to-left direction.

---

## 11. Testing strategy

Tests are written per module, not at the end.

Required test groups:

```text
Auth and forced password change
Login rate limiting
Admin 2FA and recovery codes
Artisan commands
Users and teacher accounts
Teacher deactivation and password reset
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
- deactivated teacher cannot log in
- admin can reset a teacher password
- teacher sees only assigned classes
- teacher cannot open fees or settings
- teacher cannot mark attendance for another class
- teacher cannot edit yesterday
- teacher "today only" still works across midnight in the configured timezone
- admin can edit any date
- duplicate attendance for `(student, date)` is rejected
- student ID is unique and sequential
- students cannot be hard-deleted
- student and teacher photo_path are optional
- login uses username, not e-mail
- artisan commands accept a username
- billing a class twice does not duplicate fees
- two payment attempts cannot overpay
- duplicate payment reference records once
- receipt number is unique and follows the format
- reversal restores balance
- void requires no net payments and a reason
- `app:fees-reconcile` detects and repairs an incorrect `amount_paid`
- payments and reversals recalculate `amount_paid` inside the locked transaction
- every report respects the selected date range
- CSV and print views contain the same figures

Both SQLite and MySQL test runs are required. The MySQL suite must include the two-connection fee lock test.

---

## 12. Delivery stages

### Stage 0 — Plan and scaffold

Status: **complete, awaiting go-ahead for Stage 1**

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

### Stage 1 — Foundation, theme, login and layout

Tag:

```text
stage-1-foundation-green
```

Scope:

- MySQL configuration
- Bootstrap 5.3, Vite, Inter and Bootstrap Icons from npm
- one SCSS variables file containing the palette
- split-screen login page using username and password
- final application shell: top bar and role-specific sidebar
- admin sidebar: Dashboard, Students, Teachers, Classes, Subjects, Attendance, Fees, Reports, Settings
- teacher sidebar: Dashboard, My classes, Attendance
- sidebar collapses to a menu button on phones
- users table with unique username and optional unused e-mail
- login, logout and session handling
- login rate limiting by username and IP
- forced password change after a temporary password
- optional admin TOTP 2FA and recovery codes
- `app:create-admin`, `app:reset-password`, `app:disable-two-factor`
- Settings page: school name, logo, currency, ID prefix, receipt prefix, timezone
- settings timezone applied at boot
- audit log helper
- English language files complete
- `lang/so` and `lang/ar` present but untranslated
- empty-state placeholder pages for every menu item
- migrations and `migrate --seed` creates no users
- tests for auth, rate limiting, password change, 2FA, commands, settings, timezone and audit
- screenshots of login and app shell at desktop and phone widths
- tag and push to the private repository

Every later stage is built on this finished look.

### Stage 2 — People and academics

Tag:

```text
stage-2-people-green
```

Scope:

- students, teacher accounts, classes and subjects
- student ID generation with locked counter
- class teacher and subject teacher assignments
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
- teacher today-only rule using the configured timezone
- admin any-date rule
- attendance summary
- midnight boundary test for the teacher "today only" rule
- tests for uniqueness and policy rules

### Stage 4 — Fees

Tag:

```text
stage-4-fees-green
```

Scope:

- fee structures
- bill-the-whole-class action
- duplicate-billing protection
- `fees.class_id` copied at billing time
- payments with row lock and one-time reference
- receipt numbers
- reversals and voids
- currency per fee
- audit log
- `app:fees-reconcile`
- reconcile mismatch test
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
- report indexes
- tests for ranges and totals

### Stage 6 — Dashboards and polish

Tag:

```text
stage-6-ui-green
```

Scope:

- admin dashboard
- teacher dashboard
- empty-state and search audit across every list
- responsive checks
- translation completeness
- RTL check
- final visual polish on the Stage 1 layout
- browser checks on desktop and phone

### Stage 7 — Release hardening

Tag:

```text
stage-7-release-green
```

Scope:

- full SQLite and MySQL suites
- policy tests for every role
- security review
- daily `mysqldump` documentation
- actual restore rehearsal into a temporary database
- backup and restore guide
- deployment guide
- final browser checks
- final release notes

---

## 13. Private GitHub repository

The old repository already uses the name `schoolPoint`, so the rebuild needs a different name.

Proposed private repository:

```text
Dhaameel/schoolpoint-rebuild
```

Create it manually:

1. Open `https://github.com/new`.
2. Owner: `Dhaameel`.
3. Name: `schoolpoint-rebuild`.
4. Visibility: **Private**.
5. Do not add a README, `.gitignore` or license.
6. Create the repository.

Then:

```powershell
cd C:\dev\schoolpoint
git remote add origin https://github.com/Dhaameel/schoolpoint-rebuild.git
git push -u origin main
git push origin --tags
```

GitHub authentication must be completed by the owner. Never paste a token into chat.

If another repository name is preferred, use that name in both GitHub and the `git remote add` command.

---

## 14. Approval gate

Stage 0 stops here.

Stage 1 must not begin until the owner explicitly says go.

The owner must approve:

- the roles and policy approach
- the teacher class-teacher and subject-teacher access rules
- the table design and foreign-key restrictions
- the attendance rules
- the fee rules
- the report set
- the Stage 1–7 order and tags
- the final private repository name
