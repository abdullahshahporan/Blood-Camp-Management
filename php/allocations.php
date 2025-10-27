<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['request_id']) && isset($_POST['unit_id'])) {
        // Update blood unit status to Reserved when allocated
        $stmt = $pdo->prepare("UPDATE blood_unit SET status='Reserved' WHERE unit_id=?");
        $stmt->execute([$_POST['unit_id']]);
        
        // Insert allocation record
        $stmt = $pdo->prepare("INSERT INTO allocation (request_id, unit_id, allocated_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_POST['request_id'], $_POST['unit_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    // Get unit_id before deletion
    $stmt = $pdo->prepare("SELECT unit_id FROM allocation WHERE allocation_id=?");
    $stmt->execute([$_POST['allocation_id']]);
    $unit_id = $stmt->fetchColumn();
    
    // Delete allocation
    $stmt = $pdo->prepare("DELETE FROM allocation WHERE allocation_id=?");
    $stmt->execute([$_POST['allocation_id']]);
    
    // Update blood unit status back to Available
    $stmt = $pdo->prepare("UPDATE blood_unit SET status='Available' WHERE unit_id=?");
    $stmt->execute([$unit_id]);
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query 1: List all allocations with JOIN (multiple tables)
$stmt = $pdo->query("
    SELECT a.allocation_id, a.allocated_at,
           r.request_id, r.hospital_name, r.blood_group, r.urgency,
           b.unit_id, b.status as unit_status,
           d.full_name as donor_name,
           l.name as location_name
    FROM allocation a
    JOIN blood_request r ON a.request_id = r.request_id
    JOIN blood_unit b ON a.unit_id = b.unit_id
    JOIN donation dn ON b.donation_id = dn.donation_id
    JOIN donor d ON dn.donor_id = d.donor_id
    JOIN location l ON b.location_id = l.location_id
    ORDER BY a.allocated_at DESC
");
$allocations = $stmt->fetchAll();

// Query 2: Total count (AGGREGATE)
$count = $pdo->query("SELECT COUNT(*) FROM allocation")->fetchColumn();

// Query 3: Allocations by blood group (JOIN + GROUP BY)
$by_blood_group = $pdo->query("
    SELECT r.blood_group, COUNT(a.allocation_id) as total_allocations
    FROM allocation a
    JOIN blood_request r ON a.request_id = r.request_id
    GROUP BY r.blood_group
    ORDER BY total_allocations DESC
")->fetchAll();

// Query 4: Recent allocations (last 7 days) - DATE
$recent_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM allocation
    WHERE allocated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch()['count'];

// Query 5: Allocations by urgency (JOIN + GROUP BY + CASE)
$by_urgency = $pdo->query("
    SELECT r.urgency, COUNT(a.allocation_id) as total
    FROM allocation a
    JOIN blood_request r ON a.request_id = r.request_id
    GROUP BY r.urgency
    ORDER BY FIELD(r.urgency, 'Urgent', 'Normal')
")->fetchAll();

// Query 6: Top hospitals receiving allocations (JOIN + GROUP BY + LIMIT)
$top_hospitals = $pdo->query("
    SELECT r.hospital_name, COUNT(a.allocation_id) as allocation_count
    FROM allocation a
    JOIN blood_request r ON a.request_id = r.request_id
    GROUP BY r.hospital_name
    ORDER BY allocation_count DESC
    LIMIT 5
")->fetchAll();

// Query 7: Allocations by location (JOIN + GROUP BY)
$by_location = $pdo->query("
    SELECT l.name as location_name, COUNT(a.allocation_id) as total
    FROM allocation a
    JOIN blood_unit b ON a.unit_id = b.unit_id
    JOIN location l ON b.location_id = l.location_id
    GROUP BY l.location_id, l.name
    ORDER BY total DESC
")->fetchAll();

// Query 8: Available requests (not yet allocated) - SUBQUERY
$pending_requests = $pdo->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM allocation WHERE request_id = r.request_id) as allocated_count
    FROM blood_request r
    WHERE r.request_id NOT IN (
        SELECT DISTINCT request_id FROM allocation
    )
    OR (SELECT COUNT(*) FROM allocation WHERE request_id = r.request_id) < r.units_requested
    ORDER BY r.urgency DESC, r.created_at DESC
")->fetchAll();

// Get lists for dropdowns
$available_requests = $pdo->query("
    SELECT request_id, hospital_name, blood_group, units_requested, urgency 
    FROM blood_request 
    ORDER BY urgency DESC, created_at DESC
")->fetchAll();

$available_units = $pdo->query("
    SELECT bu.unit_id, bu.status, bu.expiry_date, donor.full_name, donor.blood_group
    FROM blood_unit bu
    JOIN donation d ON bu.donation_id = d.donation_id
    JOIN donor ON d.donor_id = donor.donor_id
    WHERE bu.status = 'Available'
    ORDER BY bu.expiry_date ASC
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
.pending-alert {
    background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%);
    border-left: 4px solid #ffa726;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-boxes text-primary"></i> Blood Allocations</h2>
                    <p class="text-muted mb-0">Manage blood unit allocations to hospital requests</p>
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
                        <h3><?php echo number_format($count); ?></h3>
                        <p>Total Allocations</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo number_format($recent_count); ?></h3>
                        <p>Last 7 Days</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><?php echo number_format(count($pending_requests)); ?></h3>
                        <p>Pending Requests</p>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Allocate Blood Unit
                </button>
            </div>

            <!-- Pending Requests Alert -->
          <!-- <! <?php if (count($pending_requests) > 0): ?>
            <div class="pending-alert">
                <h6 class="fw-bold mb-2"><i class="bi bi-clock-history"></i> Pending Requests (<?php echo count($pending_requests); ?>)</h6>
                <p class="mb-0 small">These requests are waiting for allocation or need more units.</p>
            </div>
            <?php endif; ?>*-->

            <!-- All Allocations Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    All Allocations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Hospital</th>
                                <th>Blood Group</th>
                                <th>Urgency</th>
                                <th>Unit ID</th>
                                <th>Donor</th>
                                <th>Location</th>
                                <th>Allocated Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($allocations) === 0): ?>
                            <tr><td colspan="9" class="text-center text-muted">No allocations found</td></tr>
                        <?php else: ?>
                            <?php foreach ($allocations as $alloc): ?>
                            <tr>
                                <td><?php echo $alloc['allocation_id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($alloc['hospital_name']); ?></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($alloc['blood_group']); ?></span></td>
                                <td>
                                    <span class="badge <?php echo $alloc['urgency'] === 'Urgent' ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $alloc['urgency']; ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($alloc['unit_id']); ?></code></td>
                                <td><?php echo htmlspecialchars($alloc['donor_name']); ?></td>
                                <td><?php echo htmlspecialchars($alloc['location_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($alloc['allocated_at'])); ?></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="allocation_id" value="<?php echo $alloc['allocation_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Delete this allocation? The unit will be marked as Available again.')">
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

            <!-- Allocations by Blood Group -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Allocations by Blood Group
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blood Group</th>
                                <th>Total Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_blood_group as $bg): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($bg['blood_group']); ?></span></td>
                                <td><strong><?php echo $bg['total_allocations']; ?></strong> units</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Allocations by Urgency -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-speedometer2 text-warning"></i>
                    Allocations by Urgency Level
                </div>
                <div class="row g-3">
                    <?php foreach ($by_urgency as $urg): ?>
                    <div class="col-md-6">
                        <div class="p-3 rounded" style="background: <?php echo $urg['urgency'] === 'Urgent' ? 'linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%)' : 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)'; ?>; border-left: 4px solid <?php echo $urg['urgency'] === 'Urgent' ? '#dc3545' : '#28a745'; ?>;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-1"><?php echo $urg['urgency']; ?></h5>
                                    <p class="mb-0 text-muted small"><?php echo $urg['total']; ?> allocations</p>
                                </div>
                                <div class="fs-1 <?php echo $urg['urgency'] === 'Urgent' ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $urg['urgency'] === 'Urgent' ? '🚨' : '✅'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Top Hospitals -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-hospital text-info"></i>
                    Top 5 Hospitals (Most Allocations)
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Hospital Name</th>
                                <th>Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $rank = 1; foreach ($top_hospitals as $hospital): ?>
                            <tr>
                                <td>
                                    <?php if($rank <= 3): ?>
                                        <span class="badge bg-warning text-dark">⭐ #<?php echo $rank; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($hospital['hospital_name']); ?></td>
                                <td><strong><?php echo $hospital['allocation_count']; ?></strong> units</td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Allocations by Location -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    Allocations by Blood Bank Location
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Location</th>
                                <th>Total Allocations</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_location as $loc): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($loc['location_name']); ?></td>
                                <td><strong><?php echo $loc['total']; ?></strong> units</td>
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
                        <div class="query-title-text">Purpose: Display all allocations with details</div>
                        <div class="query-sql">SELECT a.allocation_id, a.allocated_at,
       r.hospital_name, r.blood_group,
       b.unit_id, b.status,
       d.full_name as donor_name,
       l.name as location_name
FROM allocation a
JOIN blood_request r 
  ON a.request_id = r.request_id
JOIN blood_unit b 
  ON a.unit_id = b.unit_id
JOIN donation dn 
  ON b.donation_id = dn.donation_id
JOIN donor d 
  ON dn.donor_id = d.donor_id
JOIN location l 
  ON b.location_id = l.location_id
ORDER BY a.allocated_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Count total allocations</div>
                        <div class="query-sql">SELECT COUNT(*) FROM allocation</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 JOIN + GROUP BY</div>
                        <div class="query-title-text">Purpose: Allocations per blood group</div>
                        <div class="query-sql">SELECT r.blood_group,
       COUNT(a.allocation_id) as total
FROM allocation a
JOIN blood_request r 
  ON a.request_id = r.request_id
GROUP BY r.blood_group
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 DATE Functions</div>
                        <div class="query-title-text">Purpose: Recent allocations (7 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM allocation
WHERE allocated_at >= 
  DATE_SUB(NOW(), INTERVAL 7 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 GROUP BY + FIELD</div>
                        <div class="query-title-text">Purpose: Allocations by urgency</div>
                        <div class="query-sql">SELECT r.urgency, COUNT(a.allocation_id) as total
FROM allocation a
JOIN blood_request r 
  ON a.request_id = r.request_id
GROUP BY r.urgency
ORDER BY FIELD(r.urgency, 'Urgent', 'Normal')</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 GROUP BY + LIMIT</div>
                        <div class="query-title-text">Purpose: Top 5 hospitals</div>
                        <div class="query-sql">SELECT r.hospital_name,
       COUNT(a.allocation_id) as total
FROM allocation a
JOIN blood_request r 
  ON a.request_id = r.request_id
GROUP BY r.hospital_name
ORDER BY total DESC
LIMIT 5</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 JOIN + GROUP BY (Location)</div>
                        <div class="query-title-text">Purpose: Allocations by location</div>
                        <div class="query-sql">SELECT l.name, COUNT(a.allocation_id) as total
FROM allocation a
JOIN blood_unit b 
  ON a.unit_id = b.unit_id
JOIN location l 
  ON b.location_id = l.location_id
GROUP BY l.location_id, l.name
ORDER BY total DESC</div>
                    </div>

                   <!-- <div class="mb-3">
                        <div class="query-badge">#8 SUBQUERY (Pending)</div>
                        <div class="query-title-text">Purpose: Find pending requests</div>
                        <div class="query-sql">SELECT r.*
FROM blood_request r
WHERE r.request_id NOT IN (
  SELECT DISTINCT request_id 
  FROM allocation
)
OR (SELECT COUNT(*) FROM allocation 
    WHERE request_id = r.request_id) 
   < r.units_requested</div>
                    </div>-->

                    <div class="mb-3">
                        <div class="query-badge">#8 INSERT + UPDATE</div>
                        <div class="query-title-text">Purpose: Allocate unit & update status</div>
                        <div class="query-sql">-- Update unit status
UPDATE blood_unit 
SET status='Reserved' 
WHERE unit_id=?;

-- Insert allocation
INSERT INTO allocation 
  (request_id, unit_id, allocated_at)
VALUES (?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE + UPDATE</div>
                        <div class="query-title-text">Purpose: Remove allocation & restore unit</div>
                        <div class="query-sql">-- Delete allocation
DELETE FROM allocation 
WHERE allocation_id=?;

-- Restore unit status
UPDATE blood_unit 
SET status='Available' 
WHERE unit_id=?</div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Allocate Blood Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Blood Request</label>
                        <select name="request_id" class="form-select" required>
                            <option value="">Select Request</option>
                            <?php foreach ($available_requests as $req): ?>
                                <option value="<?php echo $req['request_id']; ?>">
                                    #<?php echo $req['request_id']; ?> - <?php echo htmlspecialchars($req['hospital_name']); ?> 
                                    (<?php echo $req['blood_group']; ?>) - <?php echo $req['urgency']; ?> 
                                    - Needs: <?php echo $req['units_requested']; ?> units
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Blood Unit</label>
                        <select name="unit_id" class="form-select" required>
                            <option value="">Select Unit</option>
                            <?php foreach ($available_units as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit['unit_id']); ?>">
                                    <?php echo htmlspecialchars($unit['unit_id']); ?> - 
                                    <?php echo $unit['blood_group']; ?> - 
                                    Donor: <?php echo htmlspecialchars($unit['full_name']); ?> - 
                                    Expires: <?php echo date('Y-m-d', strtotime($unit['expiry_date'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <small><i class="bi bi-info-circle"></i> <strong>Note:</strong> The selected blood unit will be marked as "Reserved" after allocation.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Allocate Unit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '_footer.php'; ?>