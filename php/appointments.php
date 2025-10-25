<?php
require_once 'db_connect.php';

// Handle Add
if (isset($_POST['add'])) {
    if (isset($_POST['donor_id']) && isset($_POST['camp_id']) && isset($_POST['appointment_time']) && isset($_POST['status'])) {
        $stmt = $pdo->prepare("INSERT INTO appointment (donor_id, camp_id, appointment_time, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['donor_id'], $_POST['camp_id'], $_POST['appointment_time'], $_POST['status']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Edit
if (isset($_POST['update'])) {
    if (isset($_POST['appointment_id']) && isset($_POST['donor_id']) && isset($_POST['camp_id']) && isset($_POST['appointment_time']) && isset($_POST['status'])) {
        $stmt = $pdo->prepare("UPDATE appointment SET donor_id=?, camp_id=?, appointment_time=?, status=? WHERE appointment_id=?");
        $stmt->execute([$_POST['donor_id'], $_POST['camp_id'], $_POST['appointment_time'], $_POST['status'], $_POST['appointment_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM appointment WHERE appointment_id=?");
    $stmt->execute([$_POST['appointment_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Data for selects
$donors = $pdo->query("SELECT donor_id, full_name FROM donor ORDER BY full_name ASC")->fetchAll();
$camps = $pdo->query("SELECT camp_id, title FROM camp ORDER BY title ASC")->fetchAll();

// Status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Query 1: Appointments list with JOIN
$sql = "SELECT a.appointment_id, a.donor_id, a.camp_id, a.appointment_time, a.status, a.created_at,
               d.full_name, d.blood_group,
               c.title as camp_title
        FROM appointment a
        JOIN donor d ON a.donor_id = d.donor_id
        JOIN camp c ON a.camp_id = c.camp_id
        ORDER BY a.appointment_time DESC";
$appointments = $pdo->query($sql)->fetchAll();

// Query 2: Status count (GROUP BY)
$status_count = $pdo->query("SELECT status, COUNT(*) as total FROM appointment GROUP BY status")->fetchAll();

// Query 3: Total count
$total_count = $pdo->query("SELECT COUNT(*) FROM appointment")->fetchColumn();

// Query 4: Upcoming appointments (DATE)
$upcoming_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM appointment
    WHERE appointment_time >= NOW() AND status = 'Booked'
")->fetch()['count'];

// Query 5: Recent appointments (last 7 days)
$recent_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM appointment
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch()['count'];

// Query 6: Appointments by camp (JOIN + GROUP BY)
$by_camp = $pdo->query("
    SELECT c.title, COUNT(a.appointment_id) as total
    FROM appointment a
    JOIN camp c ON a.camp_id = c.camp_id
    GROUP BY c.camp_id, c.title
    ORDER BY total DESC
")->fetchAll();

$statusBtns = [
    'Booked' => ['color' => '#22c55e', 'text' => '#fff', 'icon' => '📅'],
    'Completed' => ['color' => '#3b82f6', 'text' => '#fff', 'icon' => '✅'],
    'NoShow' => ['color' => '#e11d48', 'text' => '#fff', 'icon' => '❌'],
];

include '_header.php';
?>

<style>
.stat-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    text-align: center;
    transition: transform 0.3s ease;
}
.stat-box:hover {
    transform: translateY(-5px);
}
.stat-box h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.stat-box p {
    font-size: 0.9rem;
    opacity: 0.9;
    margin: 0;
}
.status-filter-btn {
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.status-filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.status-filter-btn.active {
    border: 3px solid #1e293b !important;
    transform: scale(1.05);
}
.query-box {
    background: #0d1117;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
}
.query-badge {
    background: #667eea;
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 8px;
}
.query-title-text {
    color: #93c5fd;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 6px;
    font-style: italic;
}
.query-sql {
    background: #1a1f2e;
    color: #e6edf3;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    padding: 12px;
    border-radius: 8px;
    overflow-x: auto;
    line-height: 1.5;
    white-space: pre;
    border-left: 3px solid #667eea;
}
.section-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-calendar-check text-success"></i> Appointments</h2>
                    <p class="text-muted mb-0">Manage donor appointments for blood donation camps</p>
                </div>
                <a href="../index.html" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Home</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column - Main Content -->
        <div class="col-12 col-lg-8">
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><?php echo number_format($total_count); ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <h3><?php echo number_format($upcoming_count); ?></h3>
                        <p>Upcoming Booked</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <h3><?php echo number_format($recent_count); ?></h3>
                        <p>Last 7 Days</p>
                    </div>
                </div>
            </div>

            <!-- Add Button & Status Filters -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Appointment
                </button>
                
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($statusBtns as $key => $style):
                        $active = ($statusFilter === $key) ? 'active' : '';
                        $count = 0;
                        foreach ($status_count as $s) {
                            if (isset($s['status']) && $s['status'] === $key) $count = $s['total'];
                        }
                    ?>
                        <a href="?status=<?php echo $key; ?>" 
                           class="status-filter-btn <?php echo $active; ?>" 
                           style="background:<?php echo $style['color']; ?>;color:<?php echo $style['text']; ?>;">
                            <span><?php echo $style['icon']; ?></span>
                            <span><?php echo $key; ?></span>
                            <span class="badge" style="background:rgba(255,255,255,0.3);color:<?php echo $style['text']; ?>;">
                                <?php echo $count; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($statusFilter): ?>
                        <a href="?" class="status-filter-btn" style="background:#64748b;color:#fff;">
                            <i class="bi bi-x-circle"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    <?php echo $statusFilter ? "Appointments - " . $statusFilter : "All Appointments"; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Camp Name</th>
                                <th>Appointment Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $filtered = 0;
                        foreach ($appointments as $row):
                            if ($statusFilter && (!isset($row['status']) || $row['status'] !== $statusFilter)) continue;
                            $filtered++;
                        ?>
                            <tr>
                                <td><?php echo $row['appointment_id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($row['blood_group']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['camp_title']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['appointment_time'])); ?></td>
                                <td>
                                    <?php
                                    $status = $row['status'];
                                    $style = $statusBtns[$status] ?? ['color' => '#64748b', 'text' => '#fff', 'icon' => ''];
                                    ?>
                                    <span class="badge" style="background:<?php echo $style['color']; ?>;color:<?php echo $style['text']; ?>;">
                                        <?php echo $style['icon']; ?> <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm edit-btn"
                                        data-id="<?php echo $row['appointment_id']; ?>"
                                        data-donor="<?php echo $row['donor_id']; ?>"
                                        data-camp="<?php echo $row['camp_id']; ?>"
                                        data-time="<?php echo date('Y-m-d\TH:i', strtotime($row['appointment_time'])); ?>"
                                        data-status="<?php echo $row['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Delete this appointment?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($filtered === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No appointments found</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Appointments by Camp -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-building text-success"></i>
                    Appointments by Camp
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Camp Name</th>
                                <th>Total Appointments</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_camp as $camp): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($camp['title']); ?></td>
                                <td><strong><?php echo $camp['total']; ?></strong> appointments</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column - SQL Queries -->
        <div class="col-12 col-lg-4">
            <div style="position: sticky; top: 20px;">
                
                <div class="query-box">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-code-square" style="font-size: 1.5rem; color: #60a5fa;"></i>
                        <span style="margin-left: 10px; font-weight: 700; font-size: 1.2rem;">SQL Queries</span>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#1 SELECT + JOIN</div>
                        <div class="query-title-text">Purpose: Display all appointments with details</div>
                        <div class="query-sql">SELECT a.*, d.full_name, d.blood_group,
       c.title as camp_title
FROM appointment a
JOIN donor d 
  ON a.donor_id = d.donor_id
JOIN camp c 
  ON a.camp_id = c.camp_id
ORDER BY a.appointment_time DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 GROUP BY (Status)</div>
                        <div class="query-title-text">Purpose: Count appointments by status</div>
                        <div class="query-sql">SELECT status, COUNT(*) as total
FROM appointment
GROUP BY status</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Total appointment count</div>
                        <div class="query-sql">SELECT COUNT(*) FROM appointment</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 WHERE + DATE (Upcoming)</div>
                        <div class="query-title-text">Purpose: Find upcoming booked appointments</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM appointment
WHERE appointment_time >= NOW() 
AND status = 'Booked'</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 DATE Functions (Recent)</div>
                        <div class="query-title-text">Purpose: Recent appointments (7 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM appointment
WHERE created_at >= 
  DATE_SUB(NOW(), INTERVAL 7 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 JOIN + GROUP BY</div>
                        <div class="query-title-text">Purpose: Appointments per camp</div>
                        <div class="query-sql">SELECT c.title, 
       COUNT(a.appointment_id) as total
FROM appointment a
JOIN camp c 
  ON a.camp_id = c.camp_id
GROUP BY c.camp_id, c.title
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 INSERT</div>
                        <div class="query-title-text">Purpose: Add new appointment</div>
                        <div class="query-sql">INSERT INTO appointment 
  (donor_id, camp_id, appointment_time, 
   status, created_at)
VALUES (?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 UPDATE</div>
                        <div class="query-title-text">Purpose: Update appointment details</div>
                        <div class="query-sql">UPDATE appointment
SET donor_id=?, camp_id=?, 
    appointment_time=?, status=?
WHERE appointment_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE</div>
                        <div class="query-title-text">Purpose: Remove appointment</div>
                        <div class="query-sql">DELETE FROM appointment
WHERE appointment_id=?</div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Add Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Donor</label>
                        <select class="form-select" name="donor_id" required>
                            <option value="">Select donor</option>
                            <?php foreach ($donors as $d): ?>
                                <option value="<?php echo $d['donor_id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp</label>
                        <select class="form-select" name="camp_id" required>
                            <option value="">Select camp</option>
                            <?php foreach ($camps as $c): ?>
                                <option value="<?php echo $c['camp_id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Appointment Time</label>
                        <input type="datetime-local" class="form-control" name="appointment_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="Booked">📅 Booked</option>
                            <option value="Completed">✅ Completed</option>
                            <option value="NoShow">❌ NoShow</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="appointment_id" id="editAppointmentId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Donor</label>
                        <select class="form-select" id="editDonor" name="donor_id" required>
                            <?php foreach ($donors as $d): ?>
                                <option value="<?php echo $d['donor_id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp</label>
                        <select class="form-select" id="editCamp" name="camp_id" required>
                            <?php foreach ($camps as $c): ?>
                                <option value="<?php echo $c['camp_id']; ?>"><?php echo htmlspecialchars($c['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Appointment Time</label>
                        <input type="datetime-local" class="form-control" id="editTime" name="appointment_time" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="Booked">📅 Booked</option>
                            <option value="Completed">✅ Completed</option>
                            <option value="NoShow">❌ NoShow</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editAppointmentId').value = btn.getAttribute('data-id');
        document.getElementById('editDonor').value = btn.getAttribute('data-donor');
        document.getElementById('editCamp').value = btn.getAttribute('data-camp');
        document.getElementById('editTime').value = btn.getAttribute('data-time');
        document.getElementById('editStatus').value = btn.getAttribute('data-status');
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
});
</script>

<?php include '_footer.php'; ?>