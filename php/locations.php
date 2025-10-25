<?php
require_once 'db_connect.php';

$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['name']) && isset($_POST['address'])) {
        $stmt = $pdo->prepare("INSERT INTO location (name, address, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$_POST['name'], $_POST['address']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['location_id']) && isset($_POST['name']) && isset($_POST['address'])) {
        $stmt = $pdo->prepare("UPDATE location SET name=?, address=? WHERE location_id=?");
        $stmt->execute([$_POST['name'], $_POST['address'], $_POST['location_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM location WHERE location_id=?");
    $stmt->execute([$_POST['location_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query 1: Filter or List all
if ($filter !== '') {
    $stmt = $pdo->prepare("SELECT location_id, name, address, DATE(created_at) as created_at FROM location WHERE name LIKE ? OR address LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$filter%", "%$filter%"]);
    $locations = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT location_id, name, address, DATE(created_at) as created_at FROM location ORDER BY created_at DESC");
    $locations = $stmt->fetchAll();
}

// Query 2: Total count
$total_count = $pdo->query("SELECT COUNT(*) FROM location")->fetchColumn();

// Query 3: Recent locations (last 30 days)
$recent_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM location
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch()['count'];

// Query 4: Locations by city (GROUP BY)
$by_city = $pdo->query("
    SELECT 
        CASE 
            WHEN address LIKE '%Khulna%' THEN 'Khulna'
            WHEN address LIKE '%Dhaka%' THEN 'Dhaka'
            WHEN address LIKE '%Rajshahi%' THEN 'Rajshahi'
            WHEN address LIKE '%Chittagong%' THEN 'Chittagong'
            ELSE 'Other'
        END as city,
        COUNT(*) as total
    FROM location
    GROUP BY city
    ORDER BY total DESC
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
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1"><i class="bi bi-geo-alt text-danger"></i> Blood Bank Locations</h2>
                    <p class="text-muted mb-0">Manage blood bank and hospital locations</p>
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
                        <p>Total Locations</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo number_format(count($locations)); ?></h3>
                        <p>Search Results</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo number_format($recent_count); ?></h3>
                        <p>Added Last 30 Days</p>
                    </div>
                </div>
            </div>

            <!-- Search & Add Section -->
            <div class="section-card">
                <form method="get" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="filter" class="form-control" 
                                   placeholder="Search by name or address..." 
                                   value="<?php echo htmlspecialchars($filter); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary w-100 fw-bold">
                            <i class="bi bi-funnel"></i> Search
                        </button>
                    </div>
                    <div class="col-md-3">
                        <?php if ($filter !== ''): ?>
                            <a href="?" class="btn btn-outline-danger w-100 fw-bold">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="bi bi-plus-circle"></i> Add Location
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Locations Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    Locations (<?php echo count($locations); ?> results)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($locations) === 0): ?>
                            <tr><td colspan="5" class="text-center text-muted">No locations found</td></tr>
                        <?php else: ?>
                            <?php foreach ($locations as $loc): ?>
                                <tr>
                                    <td><?php echo $loc['location_id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($loc['name']); ?></td>
                                    <td><?php echo htmlspecialchars($loc['address']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($loc['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?php echo $loc['location_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($loc['name']); ?>"
                                                data-address="<?php echo htmlspecialchars($loc['address']); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="location_id" value="<?php echo $loc['location_id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Delete this location?')">
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

            <!-- Locations by City -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-bar-chart-fill text-success"></i>
                    Locations by City
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>City</th>
                                <th>Total Locations</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_city as $city): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <i class="bi bi-geo-alt-fill text-danger"></i> 
                                    <?php echo htmlspecialchars($city['city']); ?>
                                </td>
                                <td><strong><?php echo $city['total']; ?></strong> locations</td>
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
                        <div class="query-badge">#1 SELECT (All)</div>
                        <div class="query-title-text">Purpose: Display all locations</div>
                        <div class="query-sql">SELECT location_id, name, address, 
       DATE(created_at) as created_at
FROM location
ORDER BY created_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 WHERE + LIKE (Filter)</div>
                        <div class="query-title-text">Purpose: Search by name or address</div>
                        <div class="query-sql">SELECT location_id, name, address, 
       DATE(created_at) as created_at
FROM location
WHERE name LIKE '%keyword%' 
OR address LIKE '%keyword%'
ORDER BY created_at DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 AGGREGATE (COUNT)</div>
                        <div class="query-title-text">Purpose: Total location count</div>
                        <div class="query-sql">SELECT COUNT(*) FROM location</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 DATE Functions</div>
                        <div class="query-title-text">Purpose: Recent locations (30 days)</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM location
WHERE created_at >= 
  DATE_SUB(NOW(), INTERVAL 30 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 CASE + GROUP BY</div>
                        <div class="query-title-text">Purpose: Locations grouped by city</div>
                        <div class="query-sql">SELECT 
  CASE 
    WHEN address LIKE '%Khulna%' THEN 'Khulna'
    WHEN address LIKE '%Dhaka%' THEN 'Dhaka'
    ELSE 'Other'
  END as city,
  COUNT(*) as total
FROM location
GROUP BY city
ORDER BY total DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 INSERT</div>
                        <div class="query-title-text">Purpose: Add new location</div>
                        <div class="query-sql">INSERT INTO location 
  (name, address, created_at)
VALUES (?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 UPDATE</div>
                        <div class="query-title-text">Purpose: Update location details</div>
                        <div class="query-sql">UPDATE location
SET name=?, address=?
WHERE location_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 DELETE</div>
                        <div class="query-title-text">Purpose: Remove location</div>
                        <div class="query-sql">DELETE FROM location
WHERE location_id=?</div>
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
                    <h5 class="modal-title">Add New Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Location Name</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g. Khulna Medical College">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" name="address" required placeholder="e.g. Boyra, Khulna">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Location</button>
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
                <input type="hidden" name="location_id" id="editLocationId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Location Name</label>
                        <input type="text" class="form-control" id="editName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control" id="editAddress" name="address" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Location</button>
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
            document.getElementById('editLocationId').value = button.getAttribute('data-id');
            document.getElementById('editName').value = button.getAttribute('data-name');
            document.getElementById('editAddress').value = button.getAttribute('data-address');
        });
    }
});
</script>

<?php include '_footer.php'; ?>