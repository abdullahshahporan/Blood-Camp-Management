<?php
require_once 'db_connect.php';

// Handle Add
if (isset($_POST['add'])) {
        $stmt = $pdo->prepare("INSERT INTO appointment (donor_id, camp_id, appointment_time, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
                $_POST['donor_id'],
                $_POST['camp_id'],
                $_POST['appointment_time'],
                $_POST['status']
        ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
}
// Handle Edit
if (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE appointment SET donor_id=?, camp_id=?, appointment_time=?, status=? WHERE appointment_id=?");
        $stmt->execute([
                $_POST['donor_id'],
                $_POST['camp_id'],
                $_POST['appointment_time'],
                $_POST['status'],
                $_POST['appointment_id']
        ]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
}
// Handle Delete
if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM appointment WHERE appointment_id=?");
        $stmt->execute([$_POST['appointment_id']]);
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit();
}

// Data for selects
$donors = $pdo->query("SELECT donor_id, full_name FROM donor ORDER BY full_name ASC")->fetchAll();
$camps = $pdo->query("SELECT camp_id, title FROM camp ORDER BY title ASC")->fetchAll();

// Status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Appointments list
$sql = "SELECT a.appointment_id, a.donor_id, a.camp_id, a.appointment_time, a.status, d.full_name, c.title as camp_title FROM appointment a JOIN donor d ON a.donor_id = d.donor_id JOIN camp c ON a.camp_id = c.camp_id ORDER BY a.appointment_time DESC";
$appointments = $pdo->query($sql)->fetchAll();

// Status count
$status_count = $pdo->query("SELECT status, COUNT(*) as total FROM appointment GROUP BY status")->fetchAll();
$statusBtns = [
        'Booked' => ['color'=>'#22c55e','text'=>'#fff'],
        'Completed' => ['color'=>'#fde047','text'=>'#000'],
        'NoShow' => ['color'=>'#e11d48','text'=>'#fff'],
];

include '_header.php';
?>
<div class="row g-4 align-items-start">
        <div class="col-12 col-lg-9">
                <div class="card border-0 shadow-sm">
                        <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h3 class="mb-0 fw-bold">Appointments</h3>
                                        <a href="../index.html" class="btn btn-outline-secondary fw-bold"><i class="bi bi-house"></i> Go to Home</a>
                                        <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">Add</button>
                                </div>
                                <div class="mb-3 d-flex gap-2">
                                        <?php foreach ($statusBtns as $key => $style):
                                                $active = ($statusFilter === $key) ? 'border border-2 border-dark' : '';
                                                $count = 0;
                                                foreach ($status_count as $s) {
                                                    if (isset($s['status']) && $s['status'] === $key) $count = $s['total'];
                                                }
                                        ?>
                                                <a href="?status=<?php echo $key; ?>" class="btn fw-bold px-3 py-1 <?php echo $active; ?>" style="background:<?php echo $style['color']; ?>;color:<?php echo $style['text']; ?>;box-shadow:none;transition:none;outline:none;">
                                                        <?php echo $key; ?> <span class="badge ms-1" style="background:<?php echo $style['text']; ?>;color:<?php echo $style['color']; ?>;font-weight:600;"><?php echo $count; ?></span>
                                                </a>
                                        <?php endforeach; ?>
                                        <?php if ($statusFilter): ?>
                                                <a href="?" class="btn btn-outline-secondary fw-bold px-3 py-1">Show All</a>
                                        <?php endif; ?>
                                </div>
                                <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                                <thead class="table-primary">
                                                        <tr>
                                                                <th>ID</th>
                                                                <th>Donor Name</th>
                                                                <th>Camp Name</th>
                                                                <th>Status</th>
                                                                <th>Action</th>
                                                        </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($appointments as $row):
                                                        if ($statusFilter && (!isset($row['status']) || $row['status'] !== $statusFilter)) continue; ?>
                                                        <tr>
                                                            <td><?php echo isset($row['appointment_id']) ? $row['appointment_id'] : ''; ?></td>
                                                            <td><?php echo isset($row['full_name']) ? htmlspecialchars($row['full_name']) : ''; ?></td>
                                                            <td><?php echo isset($row['camp_title']) ? htmlspecialchars($row['camp_title']) : ''; ?></td>
                                                            <td>
                                                                <?php
                                                                $status = isset($row['status']) ? $row['status'] : '';
                                                                if ($status === 'Booked') {
                                                                    echo '<span class="badge" style="background:#22c55e;color:#fff;font-weight:600;">Booked</span>';
                                                                } elseif ($status === 'Completed') {
                                                                    echo '<span class="badge" style="background:#fde047;color:#000;font-weight:600;">Completed</span>';
                                                                } elseif ($status === 'NoShow') {
                                                                    echo '<span class="badge" style="background:#e11d48;color:#fff;font-weight:600;">NoShow</span>';
                                                                } else {
                                                                    echo '<span class="badge bg-secondary">'.($status ? htmlspecialchars($status) : 'N/A').'</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="appointment_id" value="<?php echo isset($row['appointment_id']) ? $row['appointment_id'] : ''; ?>">
                                                                    <button type="button" class="btn btn-warning btn-sm edit-btn"
                                                                        data-id="<?php echo isset($row['appointment_id']) ? $row['appointment_id'] : ''; ?>"
                                                                        data-donor="<?php echo isset($row['donor_id']) ? $row['donor_id'] : ''; ?>"
                                                                        data-camp="<?php echo isset($row['camp_id']) ? $row['camp_id'] : ''; ?>"
                                                                        data-time="<?php echo isset($row['appointment_time']) ? $row['appointment_time'] : ''; ?>"
                                                                        data-status="<?php echo isset($row['status']) ? $row['status'] : ''; ?>"
                                                                        title="Edit">Edit</button>
                                                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this appointment?')">Delete</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                        </table>
                                </div>
                        </div>
                </div>
        </div>
        <div class="col-12 col-lg-3">
    <div style="background:#181f2a;border-radius:20px;padding:22px 18px 18px 18px;box-shadow:0 2px 12px 0 #10131a;">
        <div class="d-flex align-items-center mb-3" style="gap:8px;">
            <i class="bi bi-terminal" style="color:#fff;font-size:1.2rem;"></i>
            <span style="color:#fff;font-weight:600;font-size:1.15rem;letter-spacing:0.5px;">SQL Queries</span>
        </div>
        <div class="mb-3">
            <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.95rem;padding:3px 14px 3px 14px;border-radius:12px;display:inline-block;margin-bottom:7px;">#1 SELECT (JOIN)</span>
            <div style="background:#232b3a;border-radius:10px;padding:12px 14px 10px 14px;">
                <div style="color:#b6c3e0;font-size:0.97rem;font-weight:500;margin-bottom:4px;">Feature: Main appointment list with donor and camp name (table display, filter, sort)</div>
                <pre style="background:none;color:#e0e7ef;font-family:Consolas,monospace;font-size:13px;padding:0;margin:0;white-space:pre-line;">SELECT a.*, d.full_name, c.title as camp_title
    FROM appointment a
    JOIN donor d ON a.donor_id = d.donor_id
    JOIN camp c ON a.camp_id = c.camp_id
    ORDER BY a.appointment_time DESC</pre>
            </div>
        </div>
        <div class="mb-3">
            <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.95rem;padding:3px 14px 3px 14px;border-radius:12px;display:inline-block;margin-bottom:7px;">#2 INSERT</span>
            <div style="background:#232b3a;border-radius:10px;padding:12px 14px 10px 14px;">
                <div style="color:#b6c3e0;font-size:0.97rem;font-weight:500;margin-bottom:4px;">Feature: Add new appointment (form submit)</div>
                <pre style="background:none;color:#e0e7ef;font-family:Consolas,monospace;font-size:13px;padding:0;margin:0;white-space:pre-line;">INSERT INTO appointment (donor_id, camp_id, appointment_time, status, created_at)
    VALUES (?, ?, ?, ?, NOW())</pre>
            </div>
        </div>
        <div class="mb-3">
            <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.95rem;padding:3px 14px 3px 14px;border-radius:12px;display:inline-block;margin-bottom:7px;">#3 UPDATE</span>
            <div style="background:#232b3a;border-radius:10px;padding:12px 14px 10px 14px;">
                <div style="color:#b6c3e0;font-size:0.97rem;font-weight:500;margin-bottom:4px;">Feature: Edit appointment (form submit)</div>
                <pre style="background:none;color:#e0e7ef;font-family:Consolas,monospace;font-size:13px;padding:0;margin:0;white-space:pre-line;">UPDATE appointment SET donor_id=?, camp_id=?, appointment_time=?, status=? WHERE appointment_id=?</pre>
            </div>
        </div>
        <div class="mb-3">
            <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.95rem;padding:3px 14px 3px 14px;border-radius:12px;display:inline-block;margin-bottom:7px;">#4 DELETE</span>
            <div style="background:#232b3a;border-radius:10px;padding:12px 14px 10px 14px;">
                <div style="color:#b6c3e0;font-size:0.97rem;font-weight:500;margin-bottom:4px;">Feature: Delete appointment (form submit)</div>
                <pre style="background:none;color:#e0e7ef;font-family:Consolas,monospace;font-size:13px;padding:0;margin:0;white-space:pre-line;">DELETE FROM appointment WHERE appointment_id=?</pre>
            </div>
        </div>
        <div>
            <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.95rem;padding:3px 14px 3px 14px;border-radius:12px;display:inline-block;margin-bottom:7px;">#5 COUNT BY STATUS</span>
            <div style="background:#232b3a;border-radius:10px;padding:12px 14px 10px 14px;">
                <div style="color:#b6c3e0;font-size:0.97rem;font-weight:500;margin-bottom:4px;">Feature: Count appointments by status (for analytics/statistics)</div>
                <pre style="background:none;color:#e0e7ef;font-family:Consolas,monospace;font-size:13px;padding:0;margin:0;white-space:pre-line;">SELECT status, COUNT(*) as total FROM appointment GROUP BY status</pre>
            </div>
        </div>
    </div>
                <div id="sql-queries-panel"></div>
        </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addDonor" class="form-label">Donor</label>
                        <select class="form-select" id="addDonor" name="donor_id" required>
                            <option value="">Select donor</option>
                            <?php foreach ($donors as $d): ?>
                                <option value="<?php echo $d['donor_id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addCamp" class="form-label">Camp</label>
                        <select class="form-select" id="addCamp" name="camp_id" required>
                            <option value="">Select camp</option>
                            <?php foreach ($camps as $c): ?>
                                <option value="<?php echo $c['camp_id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addTime" class="form-label">Appointment Time</label>
                        <input type="datetime-local" class="form-control" id="addTime" name="appointment_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="addStatus" class="form-label">Status</label>
                        <select class="form-select" id="addStatus" name="status" required>
                            <option value="Booked">Booked</option>
                            <option value="Completed">Completed</option>
                            <option value="NoShow">NoShow</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="appointment_id" id="editAppointmentId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editDonor" class="form-label">Donor</label>
                        <select class="form-select" id="editDonor" name="donor_id" required>
                            <option value="">Select donor</option>
                            <?php foreach ($donors as $d): ?>
                                <option value="<?php echo $d['donor_id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editCamp" class="form-label">Camp</label>
                        <select class="form-select" id="editCamp" name="camp_id" required>
                            <option value="">Select camp</option>
                            <?php foreach ($camps as $c): ?>
                                <option value="<?php echo $c['camp_id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editTime" class="form-label">Appointment Time</label>
                        <input type="datetime-local" class="form-control" id="editTime" name="appointment_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="Booked">Booked</option>
                            <option value="Completed">Completed</option>
                            <option value="NoShow">NoShow</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Strict, non-animated, non-hover edit modal
document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
                document.getElementById('editAppointmentId').value = btn.getAttribute('data-id');
                document.getElementById('editDonor').value = btn.getAttribute('data-donor');
                document.getElementById('editCamp').value = btn.getAttribute('data-camp');
                document.getElementById('editTime').value = btn.getAttribute('data-time').replace(' ', 'T');
                document.getElementById('editStatus').value = btn.getAttribute('data-status');
                var editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
        });
});
</script>
<?php include '_footer.php'; ?>

