<?php
/**
 * Sirajganj Road Chowrasta High School - Fee Management & Payment Records
 */

$pageTitle = 'Fee Management & Payments';
require_once __DIR__ . '/../includes/header.php';

$activeSession = getCurrentSession();
$sessionId = $activeSession ? (int)$activeSession['id'] : 1;

$action = getString('action');
$classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
$feeTypes = Database::fetchAll("SELECT * FROM fee_types WHERE status = 'active' ORDER BY id ASC");

// 1. Create Fee Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee_type'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $feeName = getString('fee_name', 'POST');
        $desc = getString('description', 'POST');
        $amount = (float)($_POST['amount'] ?? 0);
        $frequency = getString('frequency', 'POST') ?: 'monthly';

        if (empty($feeName) || $amount <= 0) {
            setFlash('danger', 'Valid fee name and positive amount required.');
        } else {
            try {
                Database::insert(
                    "INSERT INTO fee_types (fee_name, description, amount, frequency, status) VALUES (?, ?, ?, ?, 'active')",
                    [$feeName, $desc, $amount, $frequency]
                );
                logActivity('fee_type_created', "Created fee category: {$feeName} (Tk {$amount})");
                setFlash('success', "Fee category {$feeName} added.");
            } catch (Exception $e) {
                setFlash('danger', 'Error creating fee type: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/fees/index.php'));
}

// 2. Assign Fee to Entire Class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bill_class_fee'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $classId = getInt('class_id', 'POST');
        $feeTypeId = getInt('fee_type_id', 'POST');
        $month = getString('month', 'POST');
        $dueDate = getString('due_date', 'POST') ?: date('Y-m-d', strtotime('+15 days'));

        $feeType = Database::fetchOne("SELECT * FROM fee_types WHERE id = ?", [$feeTypeId]);
        $enrolledStudents = Database::fetchAll("SELECT student_id FROM enrollments WHERE class_id = ? AND session_id = ?", [$classId, $sessionId]);

        if (!$feeType || empty($enrolledStudents)) {
            setFlash('danger', 'Invalid fee type or no students found in this class.');
        } else {
            Database::beginTransaction();
            try {
                $count = 0;
                foreach ($enrolledStudents as $stu) {
                    Database::insert(
                        "INSERT INTO student_fees (session_id, student_id, fee_type_id, month, amount, due_date, status) 
                         VALUES (?, ?, ?, ?, ?, ?, 'unpaid')",
                        [$sessionId, $stu['student_id'], $feeTypeId, $month ?: null, $feeType['amount'], $dueDate]
                    );
                    $count++;
                }
                Database::commit();
                logActivity('fee_billed', "Billed {$feeType['fee_name']} to {$count} students in Class ID {$classId}");
                setFlash('success', "Fee successfully billed to {$count} students!");
            } catch (Exception $e) {
                Database::rollback();
                setFlash('danger', 'Error billing class fee: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/fees/index.php'));
}

// 3. Record Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'CSRF expired.');
    } else {
        $studentFeeId = getInt('student_fee_id', 'POST');
        $amountPaid = (float)($_POST['amount_paid'] ?? 0);
        $method = getString('payment_method', 'POST') ?: 'Cash';
        $trxId = getString('transaction_id', 'POST');
        $remarks = getString('remarks', 'POST');

        $feeRecord = Database::fetchOne("SELECT * FROM student_fees WHERE id = ?", [$studentFeeId]);
        if (!$feeRecord || $amountPaid <= 0) {
            setFlash('danger', 'Invalid fee record or payment amount.');
        } else {
            Database::beginTransaction();
            try {
                $invoiceNo = 'INV-' . date('Y') . '-' . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                Database::insert(
                    "INSERT INTO payments (invoice_no, student_fee_id, amount_paid, payment_method, transaction_id, payment_date, received_by, remarks) 
                     VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)",
                    [$invoiceNo, $studentFeeId, $amountPaid, $method, $trxId ?: null, $_SESSION['user_id'], $remarks ?: null]
                );

                // Update fee status
                $totalPaid = (float)Database::fetchColumn("SELECT SUM(amount_paid) FROM payments WHERE student_fee_id = ?", [$studentFeeId]);
                $newStatus = $totalPaid >= (float)$feeRecord['amount'] ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid');
                Database::execute("UPDATE student_fees SET status = ? WHERE id = ?", [$newStatus, $studentFeeId]);

                Database::commit();
                logActivity('payment_recorded', "Recorded payment of Tk {$amountPaid} (Invoice: {$invoiceNo})");
                setFlash('success', "Payment recorded successfully! Invoice No: {$invoiceNo}");
            } catch (Exception $e) {
                Database::rollback();
                setFlash('danger', 'Error saving payment: ' . $e->getMessage());
            }
        }
    }
    redirect(url('admin/fees/index.php'));
}

// Listing recent student fee dues
$feeRecords = Database::fetchAll(
    "SELECT sf.*, s.first_name, s.last_name, s.admission_no, e.roll_no, c.class_name, sec.section_name, ft.fee_name 
     FROM student_fees sf 
     JOIN students s ON sf.student_id = s.id 
     JOIN enrollments e ON (e.student_id = s.id AND e.session_id = sf.session_id) 
     JOIN classes c ON e.class_id = c.id 
     JOIN sections sec ON e.section_id = sec.id 
     JOIN fee_types ft ON sf.fee_type_id = ft.id 
     WHERE sf.session_id = ? 
     ORDER BY sf.id DESC LIMIT 25",
    [$sessionId]
);

// Listing recent payments
$paymentsList = Database::fetchAll(
    "SELECT p.*, s.first_name, s.last_name, s.admission_no, ft.fee_name, u.full_name as receiver_name 
     FROM payments p 
     JOIN student_fees sf ON p.student_fee_id = sf.id 
     JOIN students s ON sf.student_id = s.id 
     JOIN fee_types ft ON sf.fee_type_id = ft.id 
     LEFT JOIN users u ON p.received_by = u.id 
     ORDER BY p.id DESC LIMIT 15"
);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Fee Management & Payment Receipts</h4>
        <p class="text-muted small mb-0">Manage billing categories, class fee generation, and tuition payments.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#newFeeTypeModal">
            <i class="fas fa-plus me-1"></i> New Fee Category
        </button>
        <button type="button" class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#billClassModal">
            <i class="fas fa-file-invoice me-1"></i> Bill Fee to Class
        </button>
    </div>
</div>

<!-- Tabs for Dues and Payment History -->
<ul class="nav nav-pills mb-4" id="feeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active rounded-pill px-4" id="dues-tab" data-bs-toggle="pill" data-bs-target="#duesTabPane" type="button">
            <i class="fas fa-receipt me-1"></i> Student Fee Dues & Billing (<?= count($feeRecords) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link rounded-pill px-4" id="payments-tab" data-bs-toggle="pill" data-bs-target="#paymentsTabPane" type="button">
            <i class="fas fa-money-check-alt me-1"></i> Recent Payment Receipts (<?= count($paymentsList) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="feeTabsContent">
    <!-- Tab 1: Fee Dues -->
    <div class="tab-pane fade show active" id="duesTabPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Class / Roll</th>
                            <th>Fee Category</th>
                            <th>Month</th>
                            <th>Amount (BDT)</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feeRecords)): ?>
                            <?php foreach ($feeRecords as $fee): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($fee['first_name'] . ' ' . $fee['last_name']) ?></div>
                                        <small class="text-muted"><?= e($fee['admission_no']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($fee['class_name']) ?></span>
                                        <span class="badge bg-dark rounded-pill">Roll <?= e((string)$fee['roll_no']) ?></span>
                                    </td>
                                    <td class="fw-semibold text-primary"><?= e($fee['fee_name']) ?></td>
                                    <td><?= e($fee['month'] ?: 'Yearly / Term') ?></td>
                                    <td class="fw-bold text-success"><?= formatCurrency($fee['amount']) ?></td>
                                    <td class="small text-muted"><?= formatDate($fee['due_date']) ?></td>
                                    <td>
                                        <span class="badge <?= $fee['status'] === 'paid' ? 'bg-success' : ($fee['status'] === 'partial' ? 'bg-warning text-dark' : 'bg-danger') ?> rounded-pill px-3 py-1">
                                            <?= strtoupper($fee['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($fee['status'] !== 'paid'): ?>
                                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3" 
                                                    data-bs-toggle="modal" data-bs-target="#payModal" 
                                                    data-id="<?= $fee['id'] ?>" data-name="<?= e($fee['first_name'] . ' ' . $fee['last_name']) ?>" 
                                                    data-fee="<?= e($fee['fee_name']) ?>" data-amount="<?= $fee['amount'] ?>">
                                                <i class="fas fa-hand-holding-usd me-1"></i> Pay
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No student fee dues recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Payments History -->
    <div class="tab-pane fade" id="paymentsTabPane" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice No</th>
                            <th>Student</th>
                            <th>Fee Category</th>
                            <th>Amount Paid</th>
                            <th>Method</th>
                            <th>Trx ID</th>
                            <th>Date</th>
                            <th>Collected By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($paymentsList)): ?>
                            <?php foreach ($paymentsList as $pay): ?>
                                <tr>
                                    <td class="fw-bold text-success"><?= e($pay['invoice_no']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= e($pay['first_name'] . ' ' . $pay['last_name']) ?></div>
                                        <small class="text-muted"><?= e($pay['admission_no']) ?></small>
                                    </td>
                                    <td><?= e($pay['fee_name']) ?></td>
                                    <td class="fw-bold text-success"><?= formatCurrency($pay['amount_paid']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($pay['payment_method']) ?></span></td>
                                    <td><small class="text-muted"><?= e($pay['transaction_id'] ?: '-') ?></small></td>
                                    <td class="small text-muted"><?= formatDate($pay['payment_date'], 'd M Y') ?></td>
                                    <td class="small text-muted"><?= e($pay['receiver_name'] ?: 'Admin') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No payment records logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== NEW FEE TYPE MODAL ==================== -->
<div class="modal fade" id="newFeeTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold">Add Fee Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/fees/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="save_fee_type" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fee Title <span class="text-danger">*</span></label>
                        <input type="text" name="fee_name" class="form-control" placeholder="e.g. Monthly Tuition Fee" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Standard Amount (BDT) <span class="text-danger">*</span></label>
                        <input type="number" step="10" name="amount" class="form-control" value="500" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Billing Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="monthly">Monthly</option>
                            <option value="termly">Term-End</option>
                            <option value="yearly">Yearly</option>
                            <option value="one_time">One-Time Only</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Optional particulars">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== BILL CLASS MODAL ==================== -->
<div class="modal fade" id="billClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice text-warning me-2"></i> Bill Fee to Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/fees/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="bill_class_fee" value="1">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Target Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fee Category <span class="text-danger">*</span></label>
                        <select name="fee_type_id" class="form-select" required>
                            <option value="">-- Choose Category --</option>
                            <?php foreach ($feeTypes as $ft): ?>
                                <option value="<?= $ft['id'] ?>"><?= e($ft['fee_name'] . ' (Tk ' . $ft['amount'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Applicable Month (if monthly)</label>
                        <select name="month" class="form-select">
                            <option value="">N/A (Termly / Yearly / One-Time)</option>
                            <?php foreach (['January','February','March','April','May','June','July','August','September','October','November','December'] as $m): ?>
                                <option value="<?= $m ?>" <?= date('F') === $m ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Generate Fee Invoices</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== RECORD PAYMENT MODAL ==================== -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0 bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd text-success me-2"></i> Collect Student Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= url('admin/fees/index.php') ?>" method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="record_payment" value="1">
                <input type="hidden" name="student_fee_id" id="modalPayFeeId">

                <div class="modal-body p-4">
                    <div class="mb-3 p-3 rounded-3 bg-light">
                        <div class="small text-muted">Student:</div>
                        <h6 class="fw-bold text-dark mb-1" id="modalPayStudentName"></h6>
                        <small class="text-success" id="modalPayFeeName"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Amount Paid (BDT) <span class="text-danger">*</span></label>
                        <input type="number" step="10" name="amount_paid" id="modalPayAmount" class="form-control fs-5 fw-bold text-success" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash">Cash at Counter</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Rocket">Rocket</option>
                            <option value="Bank">Bank Deposit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Transaction ID / Slip Reference</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="e.g. TR-98234876">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">Generate Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payModal = document.getElementById('payModal');
    if (payModal) {
        payModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('modalPayFeeId').value = button.getAttribute('data-id');
            document.getElementById('modalPayStudentName').textContent = button.getAttribute('data-name');
            document.getElementById('modalPayFeeName').textContent = button.getAttribute('data-fee');
            document.getElementById('modalPayAmount').value = button.getAttribute('data-amount');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
