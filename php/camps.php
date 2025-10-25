<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['title']) && isset($_POST['location_id']) && isset($_POST['camp_address']) && isset($_POST['camp_date'])) {
        $stmt = $pdo->prepare("INSERT INTO camp (title, location_id, camp_address, camp_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['title'], $_POST['location_id'], $_POST['camp_address'], $_POST['camp_date']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['camp_id']) && isset($_POST['title']) && isset($_POST['location_id']) && isset($_POST['camp_address']) && isset($_POST['camp_date'])) {
        $stmt = $pdo->prepare("UPDATE camp SET title=?, location_id=?, camp_address=?, camp_date=? WHERE camp_id=?");
        $stmt->execute([$_POST['title'], $_POST['location_id'], $_POST['camp_address'], $_POST['camp_date'], $_POST['camp_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM camp WHERE camp_id=?");
    $stmt->execute([$_POST['camp_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get locations for dropdown
$locations = $pdo->query("SELECT location_id, name FROM location ORDER BY name ASC")->fetchAll();

// Query 1: All camps with JOIN
$sql = "SELECT c.*, l.name as location_name, l.address as location_address 
        FROM camp c 
        JOIN location l ON c.location_id = l.location_id 
        ORDER BY c.camp_date DESC";
$camps = $pdo->query($sql)->fetchAll();

// Query 2: Total count
$count = $pdo->query("SELECT COUNT(*) FROM camp")->fetchColumn();

// Query 3: Camps per location (GROUP BY)
$campsPerLocation = $pdo->query("
    SELECT l.name, COUNT(*) as total 
    FROM camp c 
    JOIN location l ON c.location_id = l.location_id 
    GROUP BY l.location_id, l.name 
    ORDER BY total DESC
")->fetchAll();

// Query 4: Upcoming camps (DATE)
$upcomingCamps = $pdo->query("
    SELECT c.*, l.name as location_name 
    FROM camp c 
    JOIN location l ON c.location_id = l.location_id 
    WHERE camp_date >= CURDATE() 
    ORDER BY camp_date ASC
")->fetchAll();

// Query 5: Past camps (DATE)
$pastCamps = $pdo->query("
    SELECT c.*, l.name as location_name 
    FROM camp c 
    JOIN location l ON c.location_id = l.location_id 
    WHERE camp_date < CURDATE() 
    ORDER BY camp_date DESC
")->fetchAll();

// Query 6: Recent camps (last 30 days)
$recentCount = $pdo->query("
    SELECT COUNT(*) as count
    FROM camp
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
.view-btn {
    border: none;
    border-radius: 8px;
    padding: 8px 16px;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #334155;
}
.view-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    color: #1e293b;
}
.view-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
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
.camp-date-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-calendar-event text-primary"></i> Blood Donation Camps</h2>
                    <p class="text-muted mb-0">Manage and organize blood donation camp events</p>
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
                        <p>Total Camps</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        <h3><?php echo number_format(count($upcomingCamps)); ?></h3>
                        <p>Upcoming Camps</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <h3><?php echo number_format($recentCount); ?></h3>
                        <p>Added Last 30 Days</p>
                    </div>
                </div>
            </div>

            <!-- Add Button & View Filters -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Camp
                </button>
                
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?" class="view-btn <?php echo !isset($_GET['show']) ? 'active' : ''; ?>">
                        <i class="bi bi-list-ul"></i> All Camps
                    </a>
                    <a href="?show=upcoming" class="view-btn <?php echo isset($_GET['show']) && $_GET['show'] === 'upcoming' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-plus"></i> Upcoming
                    </a>
                    <a href="?show=past" class="view-btn <?php echo isset($_GET['show']) && $_GET['show'] === 'past' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-x"></i> Past
                    </a>
                    <a href="?show=group" class="view-btn <?php echo isset($_GET['show']) && $_GET['show'] === 'group' ? 'active' : ''; ?>">
                        <i class="bi bi-bar-chart"></i> By Location
                    </a>
                </div>
            </div>

            <!-- Content Based on View -->
            <?php if (isset($_GET['show']) && $_GET['show'] === 'group'): ?>
                <!-- Camps Per Location -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-bar-chart text-success"></i>
                        Camps Per Location
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Location</th>
                                    <th>Total Camps</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($campsPerLocation as $row): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><strong><?php echo $row['total']; ?></strong> camps</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif (isset($_GET['show']) && $_GET['show'] === 'upcoming'): ?>
                <!-- Upcoming Camps -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-calendar-plus text-success"></i>
                        Upcoming Camps (<?php echo count($upcomingCamps); ?>)
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($upcomingCamps) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No upcoming camps scheduled</td></tr>
                            <?php else: ?>
                                <?php foreach ($upcomingCamps as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['camp_address']); ?></td>
                                        <td>
                                            <span class="camp-date-badge" style="background:#dcfce7;color:#166534;">
                                                📅 <?php echo date('M d, Y', strtotime($row['camp_date'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif (isset($_GET['show']) && $_GET['show'] === 'past'): ?>
                <!-- Past Camps -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-calendar-x text-secondary"></i>
                        Past Camps (<?php echo count($pastCamps); ?>)
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Address</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($pastCamps) === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No past camps found</td></tr>
                            <?php else: ?>
                                <?php foreach ($pastCamps as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['camp_address']); ?></td>
                                        <td>
                                            <span class="camp-date-badge" style="background:#f3f4f6;color:#6b7280;">
                                                📅 <?php echo date('M d, Y', strtotime($row['camp_date'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- All Camps Table -->
                <div class="section-card">
                    <div class="section-title">
                        <i class="bi bi-table text-primary"></i>
                        All Camps
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Camp Address</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (count($camps) === 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">No camps found</td></tr>
                            <?php else: ?>
                                <?php foreach ($camps as $camp): 
                                    $isUpcoming = strtotime($camp['camp_date']) >= strtotime(date('Y-m-d'));
                                ?>
                                    <tr>
                                        <td><?php echo $camp['camp_id']; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($camp['title']); ?></td>
                                        <td><?php echo htmlspecialchars($camp['location_name']); ?></td>
                                        <td><?php echo htmlspecialchars($camp['camp_address']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($camp['camp_date'])); ?></td>
                                        <td>
                                            <?php if ($isUpcoming): ?>
                                                <span class="badge bg-success">Upcoming</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm edit-btn" 
                                                    data-id="<?php echo $camp['camp_id']; ?>" 
                                                    data-title="<?php echo htmlspecialchars($camp['title']); ?>" 
                                                    data-location="<?php echo $camp['location_id']; ?>" 
                                                    data-address="<?php echo htmlspecialchars($camp['camp_address']); ?>" 
                                                    data-date="<?php echo $camp['camp_date']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="camp_id" value="<?php echo $camp['camp_id']; ?>">
                                                <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this camp?')">
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
            <?php endif; ?>

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
                        <div class="query-title-text">Purpose: Display all camps with location</div>
                        <div class="query-sql">SELECT c.*, l.name as location_name
FROM camp c
JOIN location l 
  ON c.location_id = l.location_id
ORDER BY c.camp_date DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Total camp count</div>
                        <div class="query-sql">SELECT COUNT(*) FROM camp</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 GROUP BY</div>
                        <div class="query-title-text">Purpose: Camps per location analysis</div>
                        <div class="query-sql">SELECT l.name, COUNT(*) as total
FROM camp c
JOIN location l 
  ON c.location_id = l.location_id
GROUP BY l.location_id, l.name
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 WHERE + DATE (Upcoming)</div>
                        <div class="query-title-text">Purpose: Find upcoming camps</div>
                        <div class="query-sql">SELECT c.*, l.name as location_name
FROM camp c
JOIN location l 
  ON c.location_id = l.location_id
WHERE camp_date >= CURDATE()
ORDER BY camp_date ASC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 WHERE + DATE (Past)</div>
                        <div class="query-title-text">Purpose: Find past camps</div>
                        <div class="query-sql">SELECT c.*, l.name as location_name
FROM camp c
JOIN location l 
  ON c.location_id = l.location_id
WHERE camp_date < CURDATE()
ORDER BY camp_date DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 DATE Functions (Recent)</div>
                        <div class="query-title-text">Purpose: Recent camps (30 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM camp
WHERE created_at >= 
  DATE_SUB(NOW(), INTERVAL 30 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 INSERT</div>
                        <div class="query-title-text">Purpose: Add new camp</div>
                        <div class="query-sql">INSERT INTO camp 
  (title, location_id, camp_address, 
   camp_date, created_at)
VALUES (?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 UPDATE</div>
                        <div class="query-title-text">Purpose: Update camp details</div>
                        <div class="query-sql">UPDATE camp
SET title=?, location_id=?, 
    camp_address=?, camp_date=?
WHERE camp_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE</div>
                        <div class="query-title-text">Purpose: Remove camp</div>
                        <div class="query-sql">DELETE FROM camp
WHERE camp_id=?</div>
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
                    <h5 class="modal-title">Add New Camp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Camp Title</label>
                        <input type="text" class="form-control" name="title" required placeholder="e.g. Khulna Blood Donation Drive">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select class="form-select" name="location_id" required>
                            <option value="">Select location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp Address</label>
                        <input type="text" class="form-control" name="camp_address" required placeholder="Enter full address">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp Date</label>
                        <input type="date" class="form-control" name="camp_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Camp</button>
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
                <input type="hidden" name="camp_id" id="editCampId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Camp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Camp Title</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select class="form-select" id="editLocation" name="location_id" required>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp Address</label>
                        <input type="text" class="form-control" id="editAddress" name="camp_address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp Date</label>
                        <input type="date" class="form-control" id="editDate" name="camp_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Camp</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal
document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editCampId').value = btn.getAttribute('data-id');
        document.getElementById('editTitle').value = btn.getAttribute('data-title');
        document.getElementById('editLocation').value = btn.getAttribute('data-location');
        document.getElementById('editAddress').value = btn.getAttribute('data-address');
        document.getElementById('editDate').value = btn.getAttribute('data-date');
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
});
</script>

<?php include '_footer.php'; ?>