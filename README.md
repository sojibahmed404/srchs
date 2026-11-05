# Sirajganj Road Chowrasta High School Management Portal

> **Official Web Portal & Integrated School Management Information System (SMIS)**  
> **Institution:** Sirajganj Road Chowrasta High School (সিরাজগঞ্জ রোড চৌরাস্তা উচ্চ বিদ্যালয়)  
> **Location:** Hatikumrul, Ullapara, Sirajganj, Bangladesh  
> **EIIN:** 128456 | **Board:** Rajshahi Education Board | **Estd:** 1974  
> **Education Level:** Secondary Education (Class 6 to Class 10)

---

## 1. Project Overview

The **Sirajganj Road Chowrasta High School Management Portal** is an enterprise-grade, modern, responsive school website and management software built using clean Core PHP 8+, PDO MySQL, Bootstrap 5, vanilla JavaScript, and Chart.js.

The system features:
- **Public School Website (15 Pages):** Modern, bilingual (Bengali & English), SEO-optimized, mobile-friendly design with rich school history, faculty profiles, curriculum, online admission application, and public examination result lookup.
- **Admin Management Portal:** Full operational oversight including Student/Teacher CRUD, Class/Section/Subject management, Academic Sessions, Weekly Timetables, Daily Attendance, Examination Scheduling, Marks Calculation (Bangladeshi GPA 5.00 grading system), Result Publication, Fee Invoicing/Collection, Circulars, Photo Gallery, and System Logs.
- **Teacher Dashboard:** Role-restricted portal for educators to manage their assigned classes and subjects, record daily attendance, upload assignments/study materials, enter exam marks, and view their personalized weekly routine.

---

## 2. Technology Stack

- **Backend:** Pure Core PHP 8+ (No heavy frameworks like Laravel/Symfony; clean MVC-inspired separation)
- **Database:** MySQL / MariaDB (Strict relational design, 32 normalized tables, PDO prepared statements, InnoDB engine, foreign keys)
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Font Awesome 6.4 Free, Chart.js
- **Typography:** Google Fonts (`Inter` for modern UI readability and `Hind Siliguri` for Bengali scripts)
- **Security:** Bcrypt password hashing (`PASSWORD_BCRYPT`), CSRF token protection on all forms, XSS sanitization, rate-limiting on login, session timeout controls, and Apache `.htaccess` hardening.

---

## 3. System Architecture & Folder Structure

```text
srchs/
├── index.php                      # Homepage (Hero, notices, statistics, events, gallery)
├── about.php                      # About school, mission, vision, core values
├── history.php                    # Institution history & milestone timeline (1974–2026)
├── headmaster-message.php         # Headmaster's welcome address & contact details
├── teachers.php                   # Public faculty & staff directory
├── academics.php                  # Curriculum overview, daily schedule, GPA grading rules
├── classes.php                    # Class 6 to 10 details, sections, mapped subjects
├── facilities.php                 # Science labs, computer lab, library, sports grounds
├── admission-info.php             # Admission procedures, eligibility, required documents
├── admission.php                  # Online student admission application form
├── notices.php                    # Public circulars & notice board with attachments
├── results.php                    # Public marksheet search by exam, class, and roll
├── events.php                     # School news, events, and co-curricular listings
├── gallery.php                    # Categorized photo gallery with modal lightboxes
├── contact.php                    # Contact information & direct inquiry form
├── .htaccess                      # Apache security rules, header hardening, folder shields
├── auth/                          # Authentication Subsystem
│   ├── login.php                  # Unified role-based login portal
│   ├── logout.php                 # Secure session destruction and redirection
│   └── forgot-password.php        # Password recovery token workflow
├── admin/                         # Administrative Control Panel
│   ├── dashboard.php              # Real-time metrics, Chart.js attendance, quick actions
│   ├── includes/                  # Admin header, footer, navigation sidebar
│   ├── students/index.php         # Student management (Enrollment, profiles, status)
│   ├── teachers/                  # Teacher faculty directory & user linkage
│   │   ├── index.php              # Faculty CRUD, auto-generated teacher codes
│   │   ├── assignments.php        # Class-Section-Subject teacher assignments
│   │   └── materials.php          # Oversight of homework & study materials
│   ├── classes/                   # Academic organization
│   │   ├── index.php              # Classes (6-10) and Section capacities
│   │   └── sessions.php           # Academic sessions (Active session switcher)
│   ├── subjects/index.php         # Subjects registry & class-subject full/pass mark mapping
│   ├── attendance/index.php       # Administrative attendance taker and records
│   ├── routines/index.php         # Master 7-period weekly timetable scheduler
│   ├── exams/index.php            # Exam creation, terms, result publication toggles
│   ├── results/                   # Tabulation sheets and marks entry
│   │   ├── index.php              # Class tabulation sheet & class rank calculation
│   │   └── marks.php              # Subject marks entry with automatic GPA calculation
│   ├── admissions/index.php       # Online admission applicant review & 1-click enrollment
│   ├── fees/index.php             # Fee structures, batch class billing, invoice payments
│   ├── notices/index.php          # Notice publishing, file attachments, pinned notices
│   ├── gallery/index.php          # Photo uploads, categorized albums
│   ├── users/index.php            # Admin and teacher user accounts, password resets
│   ├── settings/index.php         # School settings (Name, EIIN, contact, headmaster quote)
│   ├── settings/logs.php          # Complete audit trail with timestamps and IP logs
│   └── reports/index.php          # Printable reports (Student roster, faculty, fee dues)
├── teacher/                       # Teacher Portal
│   ├── dashboard.php              # Daily teaching periods, assigned subjects, quick links
│   ├── includes/                  # Teacher header, footer, restricted sidebar
│   ├── profile.php                # Teacher profile management & password change
│   ├── my-classes.php             # Assigned courses, sections, and student counts
│   ├── students.php               # Student directory restricted to assigned classes
│   ├── attendance.php             # Daily student attendance recorder
│   ├── marks-entry.php            # Exam marks entry restricted to assigned courses
│   ├── assignments.php            # Homework & study material upload and tracking
│   └── routine.php                # Teacher's personal weekly class timetable
├── includes/                      # Core System Libraries
│   ├── config.php                 # Environment constants, dynamic BASE_URL, file limits
│   ├── db.php                     # Singleton PDO database connector & query wrapper
│   ├── functions.php              # Security (CSRF, XSS), file upload, alerts, pagination
│   └── auth.php                   # Session security, role verification, password hashing
├── assets/                        # Static Resources
│   ├── css/style.css              # Custom styling, dark green/gold palette, responsive
│   ├── js/main.js                 # UI interactions, form validation, AJAX handlers
│   └── images/                    # Logos, banners, portraits, placeholders
├── uploads/                       # User-uploaded files (Protected)
│   ├── admissions/                # Admission applicant documents
│   ├── assignments/               # Lesson notes and homework attachments
│   ├── gallery/                   # Campus and event photos
│   ├── notices/                   # PDF circular attachments
│   ├── student/                   # Student ID portraits
│   └── teacher/                   # Teacher faculty portraits
└── database/
    └── school_management.sql      # Complete 32-table database schema with seed data
```

---

## 4. Database Architecture (32 Normalized Tables)

The database `school_management` is designed with strict referential integrity (InnoDB engine, 58 Foreign Keys, 11 Unique Constraints):

1. **`roles`**: System roles (`admin`, `teacher`, `student`, `guardian`).
2. **`users`**: Authentication credentials, bcrypt passwords, role association, status (`active`, `suspended`).
3. **`teachers`**: Faculty profiles (teacher code, designation, qualification, specialization, joining date).
4. **`academic_sessions`**: Academic years (e.g. `2026`), `is_current` flag.
5. **`classes`**: Academic levels (Class 6 to Class 10).
6. **`sections`**: Class sections (Section A, Section B) with student capacity.
7. **`subjects`**: Subject catalog (Bengali, English, Mathematics, Science, etc.).
8. **`class_subjects`**: Maps subjects to classes with `full_marks`, `pass_marks`, `is_optional`.
9. **`teacher_assignments`**: Assigns a teacher to a specific Class, Section, and Subject for an Academic Session.
10. **`students`**: Student records (Admission No, names EN/BN, gender, DOB, blood group, phone).
11. **`guardians`**: Parent/guardian contact details, profession, NID.
12. **`enrollments`**: Connects student, class, section, session, and class roll number with unique constraint `(session_id, class_id, section_id, roll_no)`.
13. **`attendance`**: Daily attendance records (`present`, `absent`, `late`, `excused`) with unique constraint preventing duplicate daily marks.
14. **`periods`**: Class schedule periods (Period 1 to 7, Tiffin break).
15. **`routines`**: Master weekly timetable linking session, class, section, day, period, teacher, subject, and room number.
16. **`exams`**: Examination events (Term, Half-Yearly, Final), dates, and publication status.
17. **`exam_subjects`**: Examination schedules per subject (date, room).
18. **`marks`**: Subject-wise marks obtained, grade points, letter grades, and absent flags.
19. **`grading_scales`**: Official NCTB GPA 5.00 grading scale (80-100% = A+ 5.00, down to 0-32% = F 0.00).
20. **`results`**: Cumulative exam results per student (total marks, GPA, letter grade, rank, pass/fail status).
21. **`notices`**: Public circulars, categories, priority pin, PDF attachments.
22. **`events`**: Campus events and news with start/end dates.
23. **`galleries`**: Photo gallery images with category tags.
24. **`assignments`**: Homework and study material uploads with deadlines.
25. **`assignment_submissions`**: Student assignment submissions.
26. **`admissions`**: Online admission applications with status tracking (`pending`, `approved`, `rejected`).
27. **`fee_types`**: Tuition, admission, exam, and session fee definitions.
28. **`student_fees`**: Invoiced fee records per student.
29. **`payments`**: Fee payment transactions with receipt/invoice numbers (`INV-2026-XXXXX`).
30. **`settings`**: Key-value system configurations (School name, EIIN, contact info, headmaster quote).
31. **`activity_logs`**: Security audit log with user ID, action, description, IP address, and timestamp.
32. **`password_resets`**: Secure token storage for password recovery.

---

## 5. Local Setup Instructions (XAMPP / WAMP)

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.4+
- Apache Web Server with `mod_rewrite` enabled

### Step 1: Copy Files
Place the project folder into your web server's document root:
- **XAMPP (Windows):** `C:\xampp\htdocs\srchs`
- **WAMP (Windows):** `C:\wamp64\www\srchs`
- **Linux LAMP:** `/var/www/html/srchs`

### Step 2: Create Database & Import SQL
1. Open your browser and navigate to **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a new database named `school_management` with collation `utf8mb4_unicode_ci`.
3. Select `school_management` and navigate to the **Import** tab.
4. Click **Choose File**, select `database/school_management.sql` from the project directory, and click **Import**.
5. All 32 tables, foreign keys, and seed data will be created automatically.

### Step 3: Configure Database Connection
Open `includes/config.php` in a text editor and verify database settings:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
```
*Note:* The `BASE_URL` constant is detected dynamically by default. If running in a custom virtual host, set it explicitly:
```php
define('BASE_URL', 'http://localhost/srchs');
```

### Step 4: Ensure Directory Permissions
Ensure the web server has write access to the `uploads/` directory:
- On Linux/macOS:
  ```bash
  chmod -R 775 uploads/
  chown -R www-data:www-data uploads/
  ```

### Step 5: Access the Application
- **Public School Website:** `http://localhost/srchs/`
- **Portal Login:** `http://localhost/srchs/auth/login.php`

---

## 6. Default Login Credentials (Seed Accounts)

All accounts are pre-seeded in the database with secure bcrypt hashes:

| Role | Username | Password | Purpose |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` | `Admin@2026#Secure` | Full administrative access to all modules, settings, and logs |
| **Headmaster (Teacher)** | `headmaster` | `Teacher@2026#Secure` | Teacher dashboard & administrative oversight |
| **Teacher (Senior English)** | `rowshan.ara` | `Teacher@2026#Secure` | Teacher dashboard (Class 9 & 10 English) |
| **Teacher (Senior Mathematics)** | `aminul.haque` | `Teacher@2026#Secure` | Teacher dashboard (Class 8, 9, 10 Mathematics) |
| **Teacher (General Science)** | `sharmin.sultana` | `Teacher@2026#Secure` | Teacher dashboard (Class 6 & 7 Science) |

---

## 7. cPanel Production Deployment Instructions

### Step 1: Prepare Files
1. Compress all files in the project directory into a single `.zip` file (exclude development artifacts like `.git`).

### Step 2: Create Database in cPanel
1. Log in to your **cPanel**.
2. Under the **Databases** section, click **MySQL® Databases**.
3. Create a new database (e.g. `cpaneluser_srchs`).
4. Create a new MySQL user (e.g. `cpaneluser_dbuser`) with a strong password.
5. In the **Add User To Database** section, select the user and database, click **Add**, and grant **ALL PRIVILEGES**.

### Step 3: Import Database Schema via phpMyAdmin
1. In cPanel, click **phpMyAdmin**.
2. Select your newly created database on the left navigation panel.
3. Click the **Import** tab.
4. Select `school_management.sql` and click **Go**.

### Step 4: Upload and Extract Files
1. In cPanel, open **File Manager**.
2. Navigate to `public_html` (or your desired subdomain directory, e.g. `public_html/school`).
3. Click **Upload** and select your `.zip` archive.
4. Once uploaded, right-click the file and select **Extract**.

### Step 5: Update Production Configuration
1. In File Manager, right-click `includes/config.php` and click **Edit**.
2. Update the database credentials to match your cPanel MySQL setup:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'cpaneluser_srchs');
   define('DB_USER', 'cpaneluser_dbuser');
   define('DB_PASS', 'YourSecurePasswordHere');
   define('DB_PORT', '3306');
   ```
3. Set `BASE_URL` to your live domain:
   ```php
   define('BASE_URL', 'https://srchs.edu.bd');
   ```

### Step 6: Verify Permissions & SSL
1. In File Manager, verify that `uploads/` and all its subfolders (`admissions`, `assignments`, `gallery`, `notices`, `student`, `teacher`) have permission set to `0755` (or `0775`).
2. Ensure SSL/TLS is active via cPanel's **AutoSSL** or **Let's Encrypt**.
3. The `.htaccess` file will automatically enforce security headers and block direct HTTP access to `includes/` and `database/`.

---

## 8. User Roles & Access Control

### System Administrator
- **Students & Teachers:** Full CRUD with roll uniqueness validation, guardian linkage, teacher code generation, and automated account provisioning.
- **Academics:** Academic session switcher, class and section capacity tracking, master subject catalogue, class-subject pass/full marks mapping, and teacher-course assignments.
- **Timetables & Routines:** Master weekly routine matrix with period slots, break times, teacher assignments, and room numbers.
- **Attendance:** Class-wide daily attendance taking with duplicate prevention via `ON DUPLICATE KEY UPDATE`.
- **Examinations & Results:** Exam scheduling, marks tabulation sheets, automatic GPA & grade point computation (Bangladeshi GPA 5.00 standard), class rank generation, and public result publishing toggle.
- **Admissions:** Online admission application review, approval, rejection, and 1-click automatic student enrollment.
- **Fees & Billing:** Fee category management, batch class fee generation, student invoice payment collection (`INV-2026-XXXXX`), receipt generation.
- **Website CMS:** Notice board circulars with file attachments, photo gallery management, and system metadata settings.
- **Security & Logs:** User account suspension/activation, password resets, and comprehensive audit activity logging.

### Teacher
- **Personal Dashboard:** Real-time overview of assigned courses, students, and today's teaching routine.
- **Assigned Classes & Students:** Student rosters strictly restricted to classes and sections assigned to that specific teacher.
- **Daily Attendance:** Quick daily attendance interface with "Mark All Present/Absent" shortcuts, automatic duplicate prevention.
- **Marks Entry:** Course marks entry strictly restricted to assigned classes and subjects, real-time validation against full marks, automatic GP calculation, and absent toggle.
- **Homework & Materials:** Upload assignments and study documents with optional PDF/image attachments and submission due dates.
- **My Routine:** Personalized weekly timetable matrix (Sunday–Thursday) with period timings and classroom locations.
- **Profile & Security:** Personal bio updates and secure self-service password changes.

---

## 9. Security Features

- **Password Hashing:** Passwords are never stored in plain text; hashed using PHP's native `password_hash($pass, PASSWORD_BCRYPT)`.
- **SQL Injection Prevention:** 100% of database interactions utilize PDO prepared statements with bound parameters.
- **CSRF Defense:** All POST operations require a cryptographically secure token validated via `verifyCSRFToken()`.
- **XSS Protection:** All dynamic outputs rendered in HTML templates are escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` via the helper `e()`.
- **Rate Limiting:** Failed login attempts are throttled to prevent brute-force attacks.
- **File Upload Hardening:** Strict MIME type and extension whitelisting, randomized safe filenames, and storage outside executable PHP contexts.
- **Role Isolation:** Server-side role checks (`requireAdmin()`, `requireTeacher()`) prevent horizontal and vertical privilege escalation.
- **Audit Logging:** Administrative actions, logins, marks submissions, and settings modifications are recorded with timestamps, user IDs, and client IP addresses.

---

## 10. License & Support

Developed for **Sirajganj Road Chowrasta High School**, Hatikumrul, Ullapara, Sirajganj, Bangladesh.  
For technical support or feature requests, contact the school ICT Administration or system maintainer.
