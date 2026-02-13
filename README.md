# College Academic Record Management System (Pure PHP + MySQL)

A professional-looking web-based Academic Record Management System for colleges.

## Modules Included
- Authentication (login/logout)
- Dashboard
- Department Management (Add/Edit/Delete + Filter)
- Programme Management (Add/Edit/Delete + Filter)
- Student Management (Add/Edit/Delete + Filter)
- Course Management (Add/Edit/Delete + Filter)
- Enrollment Management (Add/Edit/Delete + Filter)
- Grade Management (auto letter grade + point + Filter)
- Transcript View (with GPA)
- Printable Transcript (`Print / Save as PDF`)
- Stakeholder/User Management (Admin, Registrar, Lecturer) with lecturer-course assignment
- Lecturer Desk (assigned courses + grading workflow)
- Registrar Finance Desk (fee invoices, payments, balances, outstanding records)

## Database Tables (10)
- `departments`
- `programmes`
- `users`
- `students`
- `courses`
- `lecturer_courses`
- `enrollments`
- `grades`
- `student_fees`
- `fee_payments`

## Setup (WAMP)
1. Import `database/schema.sql` in phpMyAdmin (fresh import recommended because new tables were added).
2. Ensure MySQL credentials in `config/db.php` are correct for your environment.
3. Open in browser:
   - `http://localhost/tvet/`

## Default Login
- Email: `admin@college.local`
- Password: `admin123`

## Stakeholder Roles
- `admin`: full access, user management, lecturer-course assignment
- `registrar`: academic records + finance operations
- `lecturer`: lecturer desk, grade entry for assigned courses, transcript access

## Printable PDF
- Open `Transcript` module, pick a student, then click `Printable PDF`.
- On print page, use browser `Print / Save as PDF`.
