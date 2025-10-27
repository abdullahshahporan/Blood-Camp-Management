<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['unit_id']) && isset($_POST['donation_id']) && isset($_POST['location_id']) && isset($_POST['status']) && isset($_POST['expiry_date'])) {
        $stmt = $pdo->prepare("INSERT INTO blood_unit (unit_id, donation_id, location_id, status, expiry_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['unit_id'], $_POST['donation_id'], $_POST['location_id'], $_POST['status'], $_POST['expiry_date']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['unit_id']) && isset($_POST['donation_id']) && isset($_POST['location_id']) && isset($_POST['status']) && isset($_POST['expiry_date'])) {
        $stmt = $pdo->prepare("UPDATE blood_unit SET donation_id=?, location_id=?, status=?, expiry_date=? WHERE unit_id=?");
        $stmt->execute([$_POST['donation_id'], $_POST['location_id'], $_POST['status'], $_POST['expiry_date'], $_POST['unit_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM blood_unit WHERE unit_id=?");
    $stmt->execute([$_POST['unit_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query 1: List all blood units with JOIN
$stmt = $pdo->query("
    SELECT bu.unit_id, bu.status, bu.expiry_date, bu.created_at,
           d.full_name as donor_name, d.blood_group,
           l.name as location_name
    FROM blood_unit bu
    JOIN donation dn ON bu.donation_id = dn.donation_id
    JOIN donor d ON dn.donor_id = d.donor_id
    JOIN location l ON bu.location_id = l.location_id
    ORDER BY bu.created_at DESC
");
$units = $stmt->fetchAll();

// Query 2: Count by status (GROUP BY)
$status_count = $pdo->query("
    SELECT status, COUNT(*) as total 
    FROM blood_unit 
    GROUP BY status
")->fetchAll();

// Query 3: Total count
$total_count = $pdo->query("SELECT COUNT(*) FROM blood_unit")->fetchColumn();

// Query 4: Expiring soon (next 30 days)
$expiring_units = $pdo->query("
    SELECT bu.unit_id, d.full_name, d.blood_group, bu.expiry_date, bu.status, l.name as location_name
    FROM blood_unit bu
    JOIN donation dn ON bu.donation_id = dn.donation_id
    JOIN donor d ON dn.donor_id = d.donor_id
    JOIN location l ON bu.location_id = l.location_id
    WHERE bu.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND bu.status = 'Available'
    ORDER BY bu.expiry_date ASC
")->fetchAll();

// Query 5: Units by blood group (JOIN + GROUP BY)
$by_blood_group = $pdo->query("
    SELECT d.blood_group, 
           COUNT(CASE WHEN bu.status = 'Available' THEN 1 END) as available,
           COUNT(CASE WHEN bu.status = 'Reserved' THEN 1 END) as reserved,
           COUNT(CASE WHEN bu.status = 'Expired' THEN 1 END) as expired
    FROM blood_unit bu
    JOIN donation dn ON bu.donation_id = dn.donation_id
    JOIN donor d ON dn.donor_id = d.donor_id
    GROUP BY d.blood_group
    ORDER BY d.blood_group
")->fetchAll();

// Query 6: Units by location (JOIN + GROUP BY)
$by_location = $pdo->query("
    SELECT l.name as location_name,
           COUNT(CASE WHEN bu.status = 'Available' THEN 1 END) as available,
           COUNT(CASE WHEN bu.status = 'Reserved' THEN 1 END) as reserved,
           COUNT(CASE WHEN bu.status = 'Expired' THEN 1 END) as expired
    FROM blood_unit bu
    JOIN location l ON bu.location_id = l.location_id
    GROUP BY l.location_id, l.name
    ORDER BY available DESC
")->fetchAll();

// Get lists for dropdowns
$donations = $pdo->query("
    SELECT dn.donation_id, d.full_name, d.blood_group, dn.donation_time
    FROM donation dn
    JOIN donor d ON dn.donor_id = d.donor_id
    ORDER BY dn.donation_time DESC
")->fetchAll();

$locations = $pdo->query("SELECT location_id, name FROM location ORDER BY name")->fetchAll();

// Calculate statistics
$stats = [];
foreach ($status_count as $s) {
    $stats[$s['status']] = $s['total'];
}

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
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}
.alert-warning {
    background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%);
    border-left: 4px solid #ffa726;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-capsule text-warning"></i> Blood Units Inventory</h2>
                    <p class="text-muted mb-0">Track and manage blood unit storage and status</p>
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
                        <h3><?php echo number_format($total_count); ?></h3>
                        <p>Total Units</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <h3><?php echo number_format($stats['Available'] ?? 0); ?></h3>
                        <p>Available</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <h3><?php echo number_format($stats['Reserved'] ?? 0); ?></h3>
                        <p>Reserved</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                        <h3><?php echo number_format($stats['Expired'] ?? 0); ?></h3>
                        <p>Expired</p>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Blood Unit
                </button>
            </div>

            <!-- Expiring Soon Alert -->
            <?php if (count($expiring_units) > 0): ?>
            <div class="alert alert-warning mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Units Expiring Soon (Next 30 Days)</h6>
                <p class="mb-2 small">These units need immediate attention:</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Unit ID</th>
                                <th>Blood Group</th>
                                <th>Donor</th>
                                <th>Location</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiring_units as $unit): 
                                $daysLeft = ceil((strtotime($unit['expiry_date']) - time()) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($unit['unit_id']); ?></code></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($unit['blood_group']); ?></span></td>
                                <td><?php echo htmlspecialchars($unit['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($unit['location_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($unit['expiry_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $daysLeft <= 10 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                                        <?php echo $daysLeft; ?> days
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Blood Units Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    All Blood Units (<?php echo count($units); ?>)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Unit ID</th>
                                <th>Blood Group</th>
                                <th>Donor</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($units) === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No blood units found</td></tr>
                        <?php else: ?>
                            <?php foreach ($units as $unit): 
                                $isExpired = strtotime($unit['expiry_date']) < time();
                                $statusClass = $unit['status'] === 'Available' ? 'bg-success' : ($unit['status'] === 'Reserved' ? 'bg-warning text-dark' : 'bg-secondary');
                            ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($unit['unit_id']); ?></code></td>
                                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($unit['blood_group']); ?></span></td>
                                    <td><?php echo htmlspecialchars($unit['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($unit['location_name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $unit['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isExpired): ?>
                                            <span class="text-danger fw-bold">
                                                <?php echo date('M d, Y', strtotime($unit['expiry_date'])); ?> ❌
                                            </span>
                                        <?php else: ?>
                                            <?php echo date('M d, Y', strtotime($unit['expiry_date'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-unitid="<?php echo htmlspecialchars($unit['unit_id']); ?>"
                                                data-status="<?php echo $unit['status']; ?>"
                                                data-expiry="<?php echo $unit['expiry_date']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unit['unit_id']); ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Delete this unit?')">
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

            <!-- Units by Blood Group -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Units by Blood Group
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blood Group</th>
                                <th>Available</th>
                                <th>Reserved</th>
                                <th>Expired</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_blood_group as $bg): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($bg['blood_group']); ?></span></td>
                                <td><span class="badge bg-success"><?php echo $bg['available']; ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $bg['reserved']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $bg['expired']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Units by Location -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    Units by Location
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Location</th>
                                <th>Available</th>
                                <th>Reserved</th>
                                <th>Expired</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_location as $loc): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($loc['location_name']); ?></td>
                                <td><span class="badge bg-success"><?php echo $loc['available']; ?></span></td>
                                <td><span class="badge bg-warning text-dark"><?php echo $loc['reserved']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $loc['expired']; ?></span></td>
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
                        <div class="query-badge">#1 Multi-table JOIN</div>
                        <div class="query-title-text">Purpose: Display all units with complete details</div>
                        <div class="query-sql">SELECT bu.unit_id, bu.status, 
       bu.expiry_date,
       d.full_name, d.blood_group,
       l.name as location_name
FROM blood_unit bu
JOIN donation dn 
  ON bu.donation_id = dn.donation_id
JOIN donor d 
  ON dn.donor_id = d.donor_id
JOIN location l 
  ON bu.location_id = l.location_id
ORDER BY bu.created_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 GROUP BY (Status)</div>
                        <div class="query-title-text">Purpose: Count units by status</div>
                        <div class="query-sql">SELECT status, COUNT(*) as total
FROM blood_unit
GROUP BY status</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Total unit count</div>
                        <div class="query-sql">SELECT COUNT(*) FROM blood_unit</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 WHERE + DATE (Expiring)</div>
                        <div class="query-title-text">Purpose: Find units expiring soon</div>
                        <div class="query-sql">SELECT bu.unit_id, d.full_name, 
       bu.expiry_date
FROM blood_unit bu
JOIN donation dn 
  ON bu.donation_id = dn.donation_id
JOIN donor d 
  ON dn.donor_id = d.donor_id
WHERE bu.expiry_date 
  BETWEEN CURDATE() 
  AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
AND bu.status = 'Available'</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 CASE + GROUP BY</div>
                        <div class="query-title-text">Purpose: Units by blood group with status</div>
                        <div class="query-sql">SELECT d.blood_group,
       COUNT(CASE WHEN bu.status = 'Available' 
             THEN 1 END) as available,
       COUNT(CASE WHEN bu.status = 'Reserved' 
             THEN 1 END) as reserved
FROM blood_unit bu
JOIN donation dn 
  ON bu.donation_id = dn.donation_id
JOIN donor d 
  ON dn.donor_id = d.donor_id
GROUP BY d.blood_group</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 JOIN + GROUP BY (Location)</div>
                        <div class="query-title-text">Purpose: Units by location inventory</div>
                        <div class="query-sql">SELECT l.name,
       COUNT(CASE WHEN bu.status = 'Available' 
             THEN 1 END) as available
FROM blood_unit bu
JOIN location l 
  ON bu.location_id = l.location_id
GROUP BY l.location_id, l.name</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 INSERT</div>
                        <div class="query-title-text">Purpose: Add new blood unit</div>
                        <div class="query-sql">INSERT INTO blood_unit 
  (unit_id, donation_id, location_id, 
   status, expiry_date, created_at)
VALUES (?, ?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 UPDATE</div>
                        <div class="query-title-text">Purpose: Update unit status and details</div>
                        <div class="query-sql">UPDATE blood_unit
SET donation_id=?, location_id=?, 
    status=?, expiry_date=?
WHERE unit_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE</div>
                        <div class="query-title-text">Purpose: Remove blood unit from inventory</div>
                        <div class="query-sql">DELETE FROM blood_unit
WHERE unit_id=?</div>
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
                    <h5 class="modal-title">Add New Blood Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Unit ID</label>
                        <input type="text" class="form-control" name="unit_id" required placeholder="e.g. BU001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Donation</label>
                        <select class="form-select" name="donation_id" required>
                            <option value="">Select Donation</option>
                            <?php foreach ($donations as $don): ?>
                                <option value="<?php echo $don['donation_id']; ?>">
                                    <?php echo htmlspecialchars($don['full_name']); ?> - <?php echo $don['blood_group']; ?> (<?php echo date('M d, Y', strtotime($don['donation_time'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Storage Location</label>
                        <select class="form-select" name="location_id" required>
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" name="expiry_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Unit</button>
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
                <input type="hidden" name="unit_id" id="editUnitId">
                <input type="hidden" name="donation_id" value="1">
                <input type="hidden" name="location_id" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Update Blood Unit Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Unit ID</label>
                        <input type="text" class="form-control" id="displayUnitId" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" id="editStatus" name="status" required>
                            <option value="Available">Available</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Expired">Expired</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" id="editExpiry" name="expiry_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal
document.addEventListener('DOMContentLoaded', function() {
    var editModal = document.getElementById('editModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('editUnitId').value = button.getAttribute('data-unitid');
            document.getElementById('displayUnitId').value = button.getAttribute('data-unitid');
            document.getElementById('editStatus').value = button.getAttribute('data-status');
            document.getElementById('editExpiry').value = button.getAttribute('data-expiry');
        });
    }
});
</script>

<?php include '_footer.php'; ?>