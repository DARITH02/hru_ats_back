# Academic Attendance Management System

## Documentation

Repository documentation, screenshots, and Khmer translation are available in [`docs/README.md`](docs/README.md).

## Project Objective

This project is a Laravel-based academic attendance management system designed to help an institution manage class attendance, student records, teacher activity, semester grading, attendance reports, and administrative operations from one web platform.

The main objective is to replace manual attendance tracking with a more reliable digital workflow. The system supports QR-based student check-in, teacher-managed attendance sessions, admin monitoring, semester score management, attendance issue review, PDF/Excel exports, and Telegram notification support.

## Core Goals

- Track student attendance accurately for every class session.
- Allow teachers to manage their assigned classes, QR codes, attendance records, and subject scores.
- Allow admins and super admins to manage students, instructors, courses, departments, subjects, class groups, permissions, and reports.
- Provide student-facing attendance access for scanning QR codes and viewing attendance data.
- Generate reports for attendance issues, semester results, course review, and institutional summaries.
- Improve operational visibility through dashboards, statistics, exports, and Telegram notifications.
- Strengthen system security through role-based access control, authenticated admin APIs, scoped teacher access, and safer credential handling.

## User Roles

### Super Admin

Super admins have the highest level of control. They can approve users, delete high-impact records, manage accounts, generate academic calendars, and perform restricted administrative actions.

### Admin

Admins manage most academic and operational data, including students, instructors, classes, subjects, departments, majors, class groups, attendance sessions, permissions, settings, exports, and reports.

### Teacher

Teachers manage only their own assigned classes and sessions. They can generate or regenerate QR codes, monitor live attendance, manually check students in, update session status, manage semester scores, and export subject reports.

### Student

Students can use QR-based attendance check-in and access student attendance information through the student-facing API or portal features.

## Main Features

### Authentication and Authorization

- Web login and API login.
- Role-based middleware for `admin`, `super_admin`, and `teacher`.
- Admin approval workflow for non-student accounts.
- Laravel Sanctum token support for protected API routes.
- Optional student-code login controlled by `ALLOW_STUDENT_CODE_LOGIN`.

### Attendance Management

- QR-based attendance verification.
- Dynamic QR token regeneration.
- Check-in time window validation.
- Optional location validation through campus geofencing settings.
- Manual teacher check-in for enrolled students.
- Attendance status tracking: present, late, absent, excused, scheduled, completed, skipped.
- Live attendance feed for teacher monitoring.

### Academic Management

- Students, instructors, courses/classes, subjects, departments, majors, and class groups.
- Class-to-group assignment support.
- Semester assignment and academic period management.
- Attendance sessions generated and managed per class schedule.
- Student permissions for excused attendance cases.

### Semester Scores and Reports

- Teacher and admin score management.
- Semester grading preview.
- Student score breakdowns for attendance, midterm, assignment, final, and total score.
- Semester reports in PDF.
- Subject score exports.
- Attendance issue reports.
- Institutional and system summary exports.

### Admin Dashboard

- Global student and attendance statistics.
- Active and upcoming session visibility.
- Attendance rate and absence rate overview.
- Top absent students and classes.
- Recent activity tracking.
- Admin pages for students, courses, instructors, results, permissions, settings, and Telegram bots.

### Telegram Integration

- Store and activate Telegram bots.
- Send test notifications.
- Send attendance and result reports to Telegram.
- Sync Telegram chat IDs from bot updates.

## Security Improvements Included

The project includes recent security hardening:

- Admin class termination API is protected by authentication and admin role middleware.
- Teacher API access is scoped to the authenticated teacher's own sessions and semester assignments.
- Teachers cannot update another teacher's session, QR code, attendance record, or score by changing IDs.
- Fixed default passwords such as `password123` and `student123` were removed from account creation.
- New student and instructor accounts receive generated temporary passwords.
- Super admin key fallback was removed from configuration.
- Student-code-as-password login is disabled by default and controlled by `ALLOW_STUDENT_CODE_LOGIN`.

## Important Security Notes

Before production deployment:

- Set `APP_DEBUG=false`.
- Use a strong `APP_KEY`.
- Rotate any exposed secrets such as database passwords, Cloudinary credentials, Telegram tokens, and super admin keys.
- Set a strong `SUPER_ADMIN_KEY` only in the real environment file.
- Keep `ALLOW_STUDENT_CODE_LOGIN=false` unless a temporary migration period is required.
- Set secure cookie values for HTTPS deployments:
  - `SESSION_SECURE_COOKIE=true`
  - `SESSION_ENCRYPT=true`
- Review public student endpoints before exposing the system publicly.
- Restrict or authenticate location collection endpoints.
- Update PHP dependencies regularly and run `composer audit`.
- Do not expose MySQL or phpMyAdmin publicly in production.

## Technology Stack

- Laravel 12
- PHP 8.2+
- Laravel Sanctum
- MySQL
- Redis
- Nginx
- Docker Compose
- Vite
- JavaScript
- Blade templates
- Maatwebsite Excel
- DomPDF
- Cloudinary integration
- Telegram Bot API

## Project Structure

- `app/Http/Controllers/Api` - API controllers for auth, admin, teacher, attendance, and location features.
- `app/Http/Controllers/Admin` - Web admin UI controllers.
- `app/Http/Controllers/Auth` - Web authentication controllers.
- `app/Models` - Eloquent models for users, students, teachers, classes, attendance, settings, Telegram bots, and academic entities.
- `app/Services` - Business logic for attendance scoring, attendance processing, and Telegram notifications.
- `database/migrations` - Database schema definitions.
- `database/seeders` - Initial data seeders.
- `resources/views` - Blade UI pages and PDF templates.
- `routes/web.php` - Web routes.
- `routes/api.php` - API routes.
- `config` - Laravel configuration files.
- `docker-compose.yml` - Local Docker environment.
- `Dockerfile` and `start.sh` - Container build and startup process.

## Local Setup

### Using Docker Compose

```bash
docker compose up -d --build
```

The app is configured to run on:

```text
http://localhost:8080
```

phpMyAdmin is configured locally on:

```text
http://localhost:8081
```

### Clear Laravel Cache

```bash
docker compose exec app php artisan optimize:clear
```

### Run Migrations

```bash
docker compose exec app php artisan migrate --force
```

### Run PHP Syntax Check Example

```bash
docker compose exec app php -l app/Http/Controllers/Api/TeacherController.php
```

### Run NPM Audit

```bash
npm audit --omit=dev
```

### Run Composer Audit

```bash
docker compose exec app composer audit --locked
```

## Environment Configuration

Create a `.env` file from `.env.example` and configure:

```env
APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=

DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SUPER_ADMIN_KEY=
ALLOW_STUDENT_CODE_LOGIN=false

TELEGRAM_BOT_TOKEN=
CLOUDINARY_URL=
```

Do not commit real `.env` secrets to version control.

## Expected Workflow

1. Super admin or admin creates academic data such as departments, majors, groups, subjects, teachers, and students.
2. Admin creates classes and assigns teachers, groups, schedules, semesters, and sessions.
3. Teacher opens an active session and generates a QR code.
4. Student scans the QR code and submits attendance verification.
5. System validates the QR token, student enrollment, time window, and optional location.
6. Teacher monitors attendance in real time.
7. Admin and teacher review results, update scores, and export reports.
8. Telegram notifications can be sent for reports or summaries.

## Current Limitations

- Imported students receive generated passwords but need a proper onboarding or password reset flow.
- Some public student endpoints should be reviewed before public deployment.
- Full automated tests are not currently available because the test directory structure is incomplete.
- Docker Compose exposes MySQL and phpMyAdmin for local development and should be hardened for production.

## Production Checklist

- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Generate a fresh `APP_KEY`.
- Rotate all real secrets.
- Use strong database credentials.
- Disable public database and phpMyAdmin exposure.
- Run `composer audit --locked` and update vulnerable dependencies.
- Run `npm audit --omit=dev`.
- Run migrations.
- Clear and rebuild Laravel caches.
- Confirm admin, teacher, and student workflows.
- Confirm role access and teacher ownership restrictions.

## License

This project is built on Laravel. Confirm the final project license with the project owner before public distribution.
