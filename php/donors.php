<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['full_name']) && isset($_POST['phone']) && isset($_POST['blood_group']) && isset($_POST['last_donation']) && isset($_POST['location'])) {
        $stmt = $pdo->prepare("INSERT INTO donor (full_name, phone, blood_group, last_donation, location, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['blood_group'], $_POST['last_donation'], $_POST['location']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['donor_id']) && isset($_POST['full_name']) && isset($_POST['phone']) && isset($_POST['blood_group']) && isset($_POST['last_donation']) && isset($_POST['location'])) {
        $stmt = $pdo->prepare("UPDATE donor SET full_name=?, phone=?, blood_group=?, last_donation=?, location=? WHERE donor_id=?");
        $stmt->execute([$_POST['full_name'], $_POST['phone'], $_POST['blood_group'], $_POST['last_donation'], $_POST['location'], $_POST['donor_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM donor WHERE donor_id=?");
    $stmt->execute([$_POST['donor_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Build filter query with SUBQUERY
$where = [];
$params = [];

if (!empty($_GET['blood_group']) && is_array($_GET['blood_group'])) {
    $in = implode(',', array_fill(0, count($_GET['blood_group']), '?'));
    $where[] = "blood_group IN ($in)";
    foreach ($_GET['blood_group'] as $bg) {
        $params[] = $bg;
    }
}

if (!empty($_GET['location'])) {
    $where[] = "location LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
}

if (!empty($_GET['last_donation_range'])) {
    $months = $_GET['last_donation_range'];
    if ($months === 'over12') {
        $where[] = "last_donation <= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
    } else {
        $where[] = "last_donation >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)";
        $params[] = (int)$months;
    }
}

// Main query using SUBQUERY
$sql = "SELECT * FROM (
    SELECT donor_id, full_name, phone, blood_group, last_donation, location, created_at 
    FROM donor
) AS subq";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY donor_id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();

// Query 2: Total count
$total_count = $pdo->query("SELECT COUNT(*) FROM donor")->fetchColumn();

// Query 3: Blood group distribution (GROUP BY)
$blood_group_stats = $pdo->query("
    SELECT blood_group, COUNT(*) as total 
    FROM donor 
    GROUP BY blood_group 
    ORDER BY total DESC
")->fetchAll();

// Query 4: Recent donors (last 30 days)
$recent_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM donor
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch()['count'];

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
.filter-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    margin-right: 8px;
    margin-bottom: 8px;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-people text-primary"></i> Blood Donors</h2>
                    <p class="text-muted mb-0">Manage donor information and search by filters</p>
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
                        <p>Total Donors</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo number_format(count($donors)); ?></h3>
                        <p>Filtered Results</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo number_format($recent_count); ?></h3>
                        <p>Added Last 30 Days</p>
                    </div>
                </div>
            </div>

            <!-- Filter & Add Buttons -->
            <div class="mb-3 d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="bi bi-funnel"></i> Filter Donors
                </button>
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Donor
                </button>
                
                <?php if (!empty($_GET)): ?>
                    <a href="?" class="btn btn-outline-danger fw-bold">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Active Filters Display -->
            <?php if (!empty($_GET)): ?>
            <div class="mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold text-muted">Active Filters:</span>
                    <?php if (!empty($_GET['blood_group'])): ?>
                        <span class="filter-badge">
                            Blood Group: <?php echo implode(', ', $_GET['blood_group']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['location'])): ?>
                        <span class="filter-badge">
                            Location: <?php echo htmlspecialchars($_GET['location']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($_GET['last_donation_range'])): ?>
                        <span class="filter-badge">
                            Last Donation: 
                            <?php 
                            $range = $_GET['last_donation_range'];
                            echo $range === 'over12' ? '1 year or more' : "Within $range months";
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Donors Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    Donors List (<?php echo count($donors); ?> results)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Blood Group</th>
                                <th>Last Donation</th>
                                <th>Location</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($donors) === 0): ?>
                            <tr><td colspan="7" class="text-center text-muted">No donors found matching your criteria</td></tr>
                        <?php else: ?>
                            <?php foreach ($donors as $donor): ?>
                                <tr>
                                    <td><?php echo $donor['donor_id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($donor['blood_group']); ?></span></td>
                                    <td><?php echo $donor['last_donation'] ? date('M d, Y', strtotime($donor['last_donation'])) : 'Never'; ?></td>
                                    <td><?php echo htmlspecialchars($donor['location']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $donor['donor_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($donor['full_name']); ?>"
                                                data-phone="<?php echo htmlspecialchars($donor['phone']); ?>"
                                                data-group="<?php echo $donor['blood_group']; ?>"
                                                data-lastdon="<?php echo $donor['last_donation']; ?>"
                                                data-location="<?php echo htmlspecialchars($donor['location']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="donor_id" value="<?php echo $donor['donor_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Delete this donor?')">
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

            <!-- Blood Group Distribution -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Blood Group Distribution
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blood Group</th>
                                <th>Total Donors</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($blood_group_stats as $stat): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($stat['blood_group']); ?></span></td>
                                <td><strong><?php echo $stat['total']; ?></strong> donors</td>
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
                        <div class="query-badge">#1 SUBQUERY (Filter)</div>
                        <div class="query-title-text">Purpose: Filter donors with complex criteria</div>
                        <div class="query-sql">SELECT * FROM (
  SELECT donor_id, full_name, phone, 
         blood_group, last_donation, 
         location, created_at
  FROM donor
) AS subq
WHERE blood_group IN (?, ?)
AND location LIKE ?
AND last_donation >= 
    DATE_SUB(CURDATE(), INTERVAL ? MONTH)
ORDER BY donor_id ASC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 SELECT (All)</div>
                        <div class="query-title-text">Purpose: Display all donors</div>
                        <div class="query-sql">SELECT donor_id, full_name, phone, 
       blood_group, last_donation, location
FROM donor
ORDER BY donor_id ASC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Total donor count</div>
                        <div class="query-sql">SELECT COUNT(*) FROM donor</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 GROUP BY</div>
                        <div class="query-title-text">Purpose: Donors per blood group</div>
                        <div class="query-sql">SELECT blood_group, COUNT(*) as total
FROM donor
GROUP BY blood_group
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 DATE Functions</div>
                        <div class="query-title-text">Purpose: Recent donors (30 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM donor
WHERE created_at >= 
  DATE_SUB(NOW(), INTERVAL 30 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 INSERT</div>
                        <div class="query-title-text">Purpose: Add new donor</div>
                        <div class="query-sql">INSERT INTO donor 
  (full_name, phone, blood_group, 
   last_donation, location, created_at)
VALUES (?, ?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 UPDATE</div>
                        <div class="query-title-text">Purpose: Update donor information</div>
                        <div class="query-sql">UPDATE donor
SET full_name=?, phone=?, blood_group=?, 
    last_donation=?, location=?
WHERE donor_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 DELETE</div>
                        <div class="query-title-text">Purpose: Remove donor</div>
                        <div class="query-sql">DELETE FROM donor
WHERE donor_id=?</div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="get">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Donors</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Blood Group</label>
                            <div class="d-flex flex-column gap-2">
                                <?php $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']; 
                                foreach ($groups as $bg): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="blood_group[]" 
                                               value="<?php echo $bg; ?>" id="bg-<?php echo $bg; ?>"
                                               <?php if (!empty($_GET['blood_group']) && in_array($bg, $_GET['blood_group'])) echo 'checked'; ?>>
                                        <label class="form-check-label" for="bg-<?php echo $bg; ?>">
                                            <?php echo $bg; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" class="form-control" name="location" 
                                   placeholder="e.g. Khulna" 
                                   value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
                            <small class="text-muted">Search by location name</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Last Donation</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="last_donation_range" 
                                           id="donated3m" value="3" 
                                           <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='3') echo 'checked'; ?>>
                                    <label class="form-check-label" for="donated3m">Within 3 months</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="last_donation_range" 
                                           id="donated6m" value="6" 
                                           <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='6') echo 'checked'; ?>>
                                    <label class="form-check-label" for="donated6m">Within 6 months</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="last_donation_range" 
                                           id="donated12m" value="12" 
                                           <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='12') echo 'checked'; ?>>
                                    <label class="form-check-label" for="donated12m">Within 12 months</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="last_donation_range" 
                                           id="donated1yplus" value="over12" 
                                           <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='over12') echo 'checked'; ?>>
                                    <label class="form-check-label" for="donated1yplus">1 year or more</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?" class="btn btn-outline-danger">Clear All</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Donor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Blood Group</label>
                        <select class="form-select" name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Donation Date</label>
                        <input type="date" class="form-control" name="last_donation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" required placeholder="e.g. Khulna, Dhaka">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Donor</button>
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
                <input type="hidden" name="donor_id" id="editDonorId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Donor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="editPhone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Blood Group</label>
                        <select class="form-select" id="editGroup" name="blood_group" required>
                            <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                                <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Donation Date</label>
                        <input type="date" class="form-control" id="editLastDon" name="last_donation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" id="editLocation" name="location" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Donor</button>
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
            document.getElementById('editDonorId').value = button.getAttribute('data-id');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editPhone').value = button.getAttribute('data-phone');
            document.getElementById('editGroup').value = button.getAttribute('data-group');
            document.getElementById('editLastDon').value = button.getAttribute('data-lastdon');
            document.getElementById('editLocation').value = button.getAttribute('data-location');
        });
    }
});
</script>

<?php include '_footer.php'; ?>