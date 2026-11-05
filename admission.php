<?php
/**
 * Sirajganj Road Chowrasta High School - Online Admission Application
 */

require_once __DIR__ . '/includes/auth.php';

$admissionStatus = getSetting('online_admission_status', 'open');
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$currentSession = getCurrentSession();
$sessionId = $currentSession ? (int)$currentSession['id'] : 1;

$successApplicationNo = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation failed (CSRF). Please refresh and submit again.');
    } elseif ($admissionStatus !== 'open') {
        setFlash('danger', 'Online admission application is currently closed.');
    } else {
        $studentName = getString('student_name', 'POST');
        $desiredClassId = getInt('desired_class_id', 'POST');
        $gender = getString('gender', 'POST');
        $dob = getString('dob', 'POST');
        $bloodGroup = getString('blood_group', 'POST');
        $religion = getString('religion', 'POST');
        $previousSchool = getString('previous_school', 'POST');
        $fatherName = getString('father_name', 'POST');
        $fatherPhone = getString('father_phone', 'POST');
        $motherName = getString('mother_name', 'POST');
        $guardianPhone = getString('guardian_phone', 'POST');
        $presentAddress = getString('present_address', 'POST');
        $permanentAddress = getString('permanent_address', 'POST');

        if (empty($permanentAddress)) {
            $permanentAddress = $presentAddress;
        }

        // Validate
        $errors = [];
        if (empty($studentName)) $errors[] = 'Student full name is required.';
        if ($desiredClassId <= 0) $errors[] = 'Please select a class for admission.';
        if (empty($gender)) $errors[] = 'Gender selection is required.';
        if (empty($dob)) $errors[] = 'Date of birth is required.';
        if (empty($fatherName)) $errors[] = 'Father’s name is required.';
        if (empty($fatherPhone)) $errors[] = 'Father’s phone number is required.';
        if (empty($motherName)) $errors[] = 'Mother’s name is required.';
        if (empty($guardianPhone)) $errors[] = 'Guardian active contact phone is required.';
        if (empty($presentAddress)) $errors[] = 'Present address is required.';

        // Photo Upload Handling
        $photoPath = null;
        if (!empty($_FILES['photo']['name']) && empty($errors)) {
            $uploadResult = uploadFile($_FILES['photo'], 'admissions', ALLOWED_IMAGE_TYPES);
            if ($uploadResult['success']) {
                $photoPath = $uploadResult['path'];
            } else {
                $errors[] = 'Photo upload: ' . $uploadResult['message'];
            }
        }

        if (empty($errors)) {
            // Generate Application Number: ADM-YEAR-XXXX
            $randomDigits = str_pad((string)mt_rand(100, 9999), 4, '0', STR_PAD_LEFT);
            $appNo = 'ADM-' . date('Y') . '-' . $randomDigits;

            try {
                Database::insert(
                    "INSERT INTO admissions (application_no, desired_class_id, session_id, student_name, gender, dob, blood_group, religion, previous_school, father_name, father_phone, mother_name, guardian_phone, present_address, permanent_address, photo, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
                    [
                        $appNo, $desiredClassId, $sessionId, $studentName, $gender, $dob, 
                        $bloodGroup ?: null, $religion ?: 'Islam', $previousSchool ?: null,
                        $fatherName, $fatherPhone, $motherName, $guardianPhone,
                        $presentAddress, $permanentAddress, $photoPath
                    ]
                );

                logActivity('admission_applied', "New online application submitted: {$appNo} by {$studentName}");

                setFlash('success', "Your admission application has been registered successfully! Application tracking ID: {$appNo}. Please print or save this reference.");
                $successApplicationNo = $appNo;

            } catch (Exception $e) {
                error_log("Admission Submission Error: " . $e->getMessage());
                setFlash('danger', 'A database error occurred while registering the application. Please try again or contact the office.');
            }
        } else {
            setFlash('danger', implode('<br>', $errors));
        }
    }
}

$pageTitle = 'Online Admission Application - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Online Admission Application</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('admission-info.php') ?>" class="text-warning text-decoration-none">Admission Info</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Apply Online</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Main Form Section -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <?php if ($successApplicationNo): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 mb-5 text-center" style="background-color: #ecfdf5; border: 2px solid #10b981 !important;">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #10b981;">
                            <i class="fas fa-check fs-2"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-2">Application Received!</h3>
                        <p class="text-muted mb-3">Please preserve your application number for verification and admission updates.</p>
                        <div class="display-6 fw-bold text-success mb-4 p-3 bg-white rounded-3 shadow-sm d-inline-block">
                            <?= e($successApplicationNo) ?>
                        </div>
                        <div>
                            <button onclick="window.print()" class="btn btn-outline-success px-4 py-2 rounded-pill me-2">
                                <i class="fas fa-print me-1"></i> Print Confirmation
                            </button>
                            <a href="<?= url('admission.php') ?>" class="btn btn-success px-4 py-2 rounded-pill">
                                Submit Another Form
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($admissionStatus !== 'open'): ?>
                    <div class="alert alert-warning p-4 rounded-4 text-center">
                        <i class="fas fa-lock fs-2 mb-2 d-block text-warning"></i>
                        <h5 class="fw-bold">Online Admission Is Currently Closed</h5>
                        <p class="mb-0">Please visit the school administrative office or check the notice board for forthcoming admission windows.</p>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow rounded-4 p-4 p-md-5 bg-white" style="border-top: 6px solid #123B2A !important;">
                        <div class="border-bottom pb-3 mb-4">
                            <span class="text-success fw-bold text-uppercase small"><i class="fas fa-pen-nib me-1"></i> Application Portal</span>
                            <h3 class="fw-bold text-dark mt-1" style="color: #123B2A;">Student Admission Application Form</h3>
                            <p class="text-muted small mb-0">Fields marked with an asterisk (<span class="text-danger">*</span>) are mandatory.</p>
                        </div>

                        <form action="<?= url('admission.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <?= csrfField() ?>

                            <!-- Step 1: Student Information -->
                            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-user-graduate text-success me-2"></i> 1. Student Personal Details</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student Full Name (English) <span class="text-danger">*</span></label>
                                    <input type="text" name="student_name" class="form-control" placeholder="e.g. Md. Tanvir Hasan" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Class Applying For <span class="text-danger">*</span></label>
                                    <select name="desired_class_id" class="form-select" required>
                                        <option value="">-- Choose Class --</option>
                                        <?php foreach ($classes as $cls): ?>
                                            <option value="<?= $cls['id'] ?>"><?= e($cls['class_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="dob" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                                    <select name="gender" class="form-select" required>
                                        <option value="">-- Select Gender --</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Blood Group</label>
                                    <select name="blood_group" class="form-select">
                                        <option value="">Unknown / Select</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Religion</label>
                                    <select name="religion" class="form-select">
                                        <option value="Islam">Islam</option>
                                        <option value="Hinduism">Hinduism</option>
                                        <option value="Christianity">Christianity</option>
                                        <option value="Buddhism">Buddhism</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Previous School Attended</label>
                                    <input type="text" name="previous_school" class="form-control" placeholder="Name of primary/previous school">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Student Photo (Max 5MB, JPG/PNG)</label>
                                    <input type="file" name="photo" class="form-control image-preview-input" data-preview="photoPreview" accept="image/*">
                                </div>
                                <div class="col-md-6 text-center text-md-start">
                                    <img id="photoPreview" src="#" alt="Preview" class="rounded border shadow-sm" style="display:none; width: 90px; height: 90px; object-fit: cover;">
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Step 2: Parent / Guardian Information -->
                            <h5 class="fw-bold text-dark mb-3"><i class="fas fa-users text-success me-2"></i> 2. Guardian & Contact Details</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Father’s Name <span class="text-danger">*</span></label>
                                    <input type="text" name="father_name" class="form-control" placeholder="Father's full name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Father’s Mobile Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="father_phone" class="form-control" placeholder="017xxxxxxxx" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Mother’s Name <span class="text-danger">*</span></label>
                                    <input type="text" name="mother_name" class="form-control" placeholder="Mother's full name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Emergency Guardian Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="guardian_phone" class="form-control" placeholder="Primary contact number" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Present Residential Address <span class="text-danger">*</span></label>
                                    <textarea name="present_address" class="form-control" rows="3" placeholder="Village / Road, Post Office, Upazila, District" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Permanent Address</label>
                                    <textarea name="permanent_address" class="form-control" rows="3" placeholder="Leave blank if identical to present address"></textarea>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="declarationCheck" required>
                                <label class="form-check-label small text-muted" for="declarationCheck">
                                    I certify that all information submitted in this application is accurate and verified by the parents/guardians. I agree to abide by the disciplinary rules of Sirajganj Road Chowrasta High School.
                                </label>
                            </div>

                            <div class="text-end">
                                <button type="reset" class="btn btn-outline-secondary px-4 py-2 rounded-pill me-2">Reset</button>
                                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
