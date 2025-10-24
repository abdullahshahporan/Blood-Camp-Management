<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['hospital_name']) && isset($_POST['blood_group']) && isset($_POST['units_requested']) && isset($_POST['urgency'])) {
        $stmt = $pdo->prepare("INSERT INTO blood_request (hospital_name, blood_group, units_requested, urgency, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['hospital_name'], $_POST['blood_group'], $_POST['units_requested'], $_POST['urgency']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['request_id']) && isset($_POST['hospital_name']) && isset($_POST['blood_group']) && isset($_POST['units_requested']) && isset($_POST['urgency'])) {
        $stmt = $pdo->prepare("UPDATE blood_request SET hospital_name=?, blood_group=?, units_requested=?, urgency=? WHERE request_id=?");
        $stmt->execute([$_POST['hospital_name'], $_POST['blood_group'], $_POST['units_requested'], $_POST['urgency'], $_POST['request_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM blood_request WHERE request_id=?");
    $stmt->execute([$_POST['request_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query 1: List all requests
$stmt = $pdo->query("SELECT * FROM blood_request ORDER BY created_at DESC");
$requests = $stmt->fetchAll();

// Query 2: Count by blood group (GROUP BY)
$group_count = $pdo->query("
    SELECT blood_group, COUNT(*) as total, SUM(units_requested) as total_units
    FROM blood_request 
    GROUP BY blood_group 
    ORDER BY total DESC
")->fetchAll();

// Query 3: Count by urgency (GROUP BY)
$urgency_count = $pdo->query("
    SELECT urgency, COUNT(*) as total, SUM(units_requested) as total_units
    FROM blood_request 
    GROUP BY urgency
    ORDER BY FIELD(urgency, 'Urgent', 'Normal')
")->fetchAll();

// Query 4: Total statistics (AGGREGATE)
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_requests,
        SUM(units_requested) as total_units,
        COUNT(CASE WHEN urgency = 'Urgent' THEN 1 END) as urgent_requests,
        COUNT(CASE WHEN urgency = 'Normal' THEN 1 END) as normal_requests
    FROM blood_request
")->fetch();

// Query 5: Recent requests (last 7 days) - DATE + WHERE
$recent_requests = $pdo->query("
    SELECT COUNT(*) as count
    FROM blood_request
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch()['count'];

// Query 6: All urgent requests (WHERE)
$critical_requests = $pdo->query("
    SELECT *
    FROM blood_request
    WHERE urgency = 'Urgent'
    ORDER BY created_at DESC
")->fetchAll();

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
.urgency-badge-Urgent {
    background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}
.urgency-badge-Normal {
    background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}
.query-title-text {
    color: #93c5fd;
    font-size: 0.8rem;
    font-weight: 500;
    margin-bottom: 6px;
    font-style: italic;
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
.alert-urgent {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
    border-left: 4px solid #dc3545;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-clipboard-pulse text-danger"></i> Blood Requests</h2>
                    <p class="text-muted mb-0">Manage hospital blood requests and track urgency</p>
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
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><?php echo number_format($stats['total_requests']); ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo number_format($stats['total_units']); ?></h3>
                        <p>Units Requested</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);">
                        <h3><?php echo number_format($stats['urgent_requests']); ?></h3>
                        <p>Urgent Requests</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo number_format($recent_requests); ?></h3>
                        <p>Last 7 Days</p>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Request
                </button>
            </div>

            <!-- Critical/Urgent Requests Alert -->
            <?php if (count($critical_requests) > 0): ?>
            <div class="alert alert-urgent mb-4">
                <h5 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill text-danger"></i> All Urgent Requests</h5>
                <p class="mb-2 small">These urgent requests require immediate attention:</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Hospital</th>
                                <th>Blood Group</th>
                                <th>Units</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($critical_requests as $cr): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($cr['hospital_name']); ?></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($cr['blood_group']); ?></span></td>
                                <td><strong><?php echo $cr['units_requested']; ?> units</strong></td>
                                <td><?php echo date('Y-m-d', strtotime($cr['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Requests Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    All Blood Requests
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Hospital Name</th>
                                <th>Blood Group</th>
                                <th>Units Requested</th>
                                <th>Urgency</th>
                                <th>Request Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($requests) === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No requests found</td></tr>
                        <?php else: ?>
                            <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo $req['request_id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($req['hospital_name']); ?></td>
                                <td><span class="badge bg-danger fs-6"><?php echo htmlspecialchars($req['blood_group']); ?></span></td>
                                <td><strong><?php echo number_format($req['units_requested']); ?></strong> units</td>
                                <td>
                                    <span class="urgency-badge-<?php echo $req['urgency']; ?>">
                                        <?php echo $req['urgency']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?php echo $req['request_id']; ?>"
                                            data-hospital="<?php echo htmlspecialchars($req['hospital_name']); ?>"
                                            data-blood="<?php echo $req['blood_group']; ?>"
                                            data-units="<?php echo $req['units_requested']; ?>"
                                            data-urgency="<?php echo $req['urgency']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Delete this request?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Requests by Blood Group -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Requests by Blood Group
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blood Group</th>
                                <th>Total Requests</th>
                                <th>Total Units</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($group_count as $gc): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($gc['blood_group']); ?></span></td>
                                <td><?php echo $gc['total']; ?> requests</td>
                                <td><strong><?php echo number_format($gc['total_units']); ?></strong> units</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Requests by Urgency -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-speedometer2 text-warning"></i>
                    Requests by Urgency Level
                </div>
                <div class="row g-3">
                    <?php foreach ($urgency_count as $uc): ?>
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background: <?php echo $uc['urgency'] === 'Urgent' ? 'linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%)' : 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)'; ?>; border-left: 4px solid <?php echo $uc['urgency'] === 'Urgent' ? '#dc3545' : '#28a745'; ?>;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo $uc['urgency']; ?></h5>
                                    <p class="mb-0 text-muted small"><?php echo $uc['total']; ?> requests | <?php echo number_format($uc['total_units']); ?> units</p>
                                </div>
                                <div class="fs-1 <?php echo $uc['urgency'] === 'Urgent' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $uc['urgency'] === 'Urgent' ? '🚨' : '✅'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                        <div class="query-badge">#1 SELECT (All Requests)</div>
                        <div class="query-title-text">Purpose: Display all blood requests</div>
                        <div class="query-sql">SELECT * FROM blood_request
ORDER BY created_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 GROUP BY + SUM</div>
                        <div class="query-title-text">Purpose: Count requests per blood group</div>
                        <div class="query-sql">SELECT blood_group, 
       COUNT(*) as total,
       SUM(units_requested) as total_units
FROM blood_request 
GROUP BY blood_group 
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 GROUP BY (Urgency)</div>
                        <div class="query-title-text">Purpose: Analyze requests by urgency level</div>
                        <div class="query-sql">SELECT urgency, COUNT(*) as total,
       SUM(units_requested) as total_units
FROM blood_request 
GROUP BY urgency
ORDER BY FIELD(urgency, 'Urgent', 'Normal')</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 AGGREGATE + CASE</div>
                        <div class="query-title-text">Purpose: Calculate overall statistics</div>
                        <div class="query-sql">SELECT 
  COUNT(*) as total_requests,
  SUM(units_requested) as total_units,
  COUNT(CASE WHEN urgency = 'Urgent' 
        THEN 1 END) as urgent_requests,
  COUNT(CASE WHEN urgency = 'Normal' 
        THEN 1 END) as normal_requests
FROM blood_request</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 DATE Functions</div>
                        <div class="query-title-text">Purpose: Find recent requests (7 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM blood_request
WHERE created_at >= 
  DATE_SUB(NOW(), INTERVAL 7 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 WHERE (Filter Urgent)</div>
                        <div class="query-title-text">Purpose: Find all urgent requests</div>
                        <div class="query-sql">SELECT *
FROM blood_request
WHERE urgency = 'Urgent'
ORDER BY created_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 INSERT</div>
                        <div class="query-title-text">Purpose: Add new blood request</div>
                        <div class="query-sql">INSERT INTO blood_request 
  (hospital_name, blood_group, 
   units_requested, urgency, created_at)
VALUES (?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 UPDATE</div>
                        <div class="query-title-text">Purpose: Update existing request</div>
                        <div class="query-sql">UPDATE blood_request
SET hospital_name=?, blood_group=?, 
    units_requested=?, urgency=?
WHERE request_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE</div>
                        <div class="query-title-text">Purpose: Remove blood request</div>
                        <div class="query-sql">DELETE FROM blood_request
WHERE request_id=?</div>
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
                    <h5 class="modal-title">Add New Blood Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hospital Name</label>
                        <input type="text" name="hospital_name" class="form-control" required placeholder="e.g. Khulna Medical College Hospital">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select" required>
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Units Requested</label>
                        <input type="number" name="units_requested" class="form-control" min="1" required placeholder="e.g. 5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urgency Level</label>
                        <select name="urgency" class="form-select" required>
                            <option value="">Select Urgency</option>
                            <option value="Urgent">🚨 Urgent</option>
                            <option value="Normal">✅ Normal</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Request</button>
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
                <div class="modal-header">
                    <h5 class="modal-title">Edit Blood Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="edit_request_id">
                    <div class="mb-3">
                        <label class="form-label">Hospital Name</label>
                        <input type="text" name="hospital_name" id="edit_hospital_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" id="edit_blood_group" class="form-select" required>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Units Requested</label>
                        <input type="number" name="units_requested" id="edit_units_requested" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Urgency Level</label>
                        <select name="urgency" id="edit_urgency" class="form-select" required>
                            <option value="Urgent">🚨 Urgent</option>
                            <option value="Normal">✅ Normal</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal with data
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('edit_request_id').value = button.getAttribute('data-id');
            document.getElementById('edit_hospital_name').value = button.getAttribute('data-hospital');
            document.getElementById('edit_blood_group').value = button.getAttribute('data-blood');
            document.getElementById('edit_units_requested').value = button.getAttribute('data-units');
            document.getElementById('edit_urgency').value = button.getAttribute('data-urgency');
        });
    }
});
</script>

<?php include '_footer.php'; ?>