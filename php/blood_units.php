<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    if (isset($_POST['donor_id']) && isset($_POST['camp_id']) && isset($_POST['donation_time']) && isset($_POST['volume_ml'])) {
        $stmt = $pdo->prepare("INSERT INTO donation (donor_id, camp_id, donation_time, volume_ml, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$_POST['donor_id'], $_POST['camp_id'], $_POST['donation_time'], $_POST['volume_ml']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Update
if (isset($_POST['update'])) {
    if (isset($_POST['donation_id']) && isset($_POST['donor_id']) && isset($_POST['camp_id']) && isset($_POST['donation_time']) && isset($_POST['volume_ml'])) {
        $stmt = $pdo->prepare("UPDATE donation SET donor_id=?, camp_id=?, donation_time=?, volume_ml=? WHERE donation_id=?");
        $stmt->execute([$_POST['donor_id'], $_POST['camp_id'], $_POST['donation_time'], $_POST['volume_ml'], $_POST['donation_id']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM donation WHERE donation_id=?");
    $stmt->execute([$_POST['donation_id']]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Query 1: List all donations with JOIN (donor name, camp title, location)
$stmt = $pdo->query("
    SELECT d.donation_id, d.donation_time, d.volume_ml, d.created_at,
           donor.full_name, donor.blood_group,
           camp.title as camp_title,
           location.name as location_name
    FROM donation d
    JOIN donor ON d.donor_id = donor.donor_id
    JOIN camp ON d.camp_id = camp.camp_id
    JOIN location ON camp.location_id = location.location_id
    ORDER BY d.donation_time DESC
");
$donations = $stmt->fetchAll();

// Query 2: Total volume donated (AGGREGATE)
$total_volume = $pdo->query("SELECT COALESCE(SUM(volume_ml), 0) as total FROM donation")->fetch()['total'];

// Query 3: Donations by blood group (JOIN + GROUP BY)
$by_blood_group = $pdo->query("
    SELECT donor.blood_group, COUNT(d.donation_id) as total_donations, SUM(d.volume_ml) as total_volume
    FROM donation d
    JOIN donor ON d.donor_id = donor.donor_id
    GROUP BY donor.blood_group
    ORDER BY total_donations DESC
")->fetchAll();

// Query 4: Recent donations (last 30 days) - SUBQUERY + DATE
$recent_donations = $pdo->query("
    SELECT COUNT(*) as count
    FROM donation
    WHERE donation_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
")->fetch()['count'];

// Query 5: Top 5 donors by donation count (JOIN + GROUP BY + HAVING + LIMIT)
$top_donors = $pdo->query("
    SELECT donor.full_name, donor.blood_group, COUNT(d.donation_id) as donation_count, SUM(d.volume_ml) as total_donated
    FROM donor
    JOIN donation d ON donor.donor_id = d.donor_id
    GROUP BY donor.donor_id, donor.full_name, donor.blood_group
    HAVING donation_count >= 1
    ORDER BY donation_count DESC, total_donated DESC
    LIMIT 5
")->fetchAll();

// Query 6: Donations per camp (JOIN + GROUP BY + AGGREGATE)
$by_camp = $pdo->query("
    SELECT camp.title, location.name as location_name, COUNT(d.donation_id) as total_donations, COALESCE(SUM(d.volume_ml), 0) as total_volume
    FROM camp
    LEFT JOIN donation d ON camp.camp_id = d.camp_id
    LEFT JOIN location ON camp.location_id = location.location_id
    GROUP BY camp.camp_id, camp.title, location.name
    ORDER BY total_donations DESC
")->fetchAll();

// Get lists for dropdowns
$donors = $pdo->query("SELECT donor_id, full_name, blood_group FROM donor ORDER BY full_name")->fetchAll();
$camps = $pdo->query("SELECT camp_id, title FROM camp ORDER BY title")->fetchAll();

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
                    <h2 class="fw-bold mb-1"><i class="bi bi-droplet-fill text-danger"></i> Blood Donations</h2>
                    <p class="text-muted mb-0">Manage and track all blood donation records</p>
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
                    <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <h3><?php echo number_format(count($donations)); ?></h3>
                        <p>Total Donations</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h3><?php echo number_format($total_volume / 1000, 1); ?>L</h3>
                        <p>Total Volume</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <h3><?php echo number_format($recent_donations); ?></h3>
                        <p>Last 30 Days</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                        <h3><?php echo count($donations) > 0 ? number_format($total_volume / count($donations)) : 0; ?> ml</h3>
                        <p>Avg Volume</p>
                    </div>
                </div>
            </div>

            <!-- Add Button -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Donation
                </button>
            </div>

            <!-- Donations Table -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-table text-primary"></i>
                    All Donations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Camp</th>
                                <th>Location</th>
                                <th>Volume (ml)</th>
                                <th>Donation Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($donations) === 0): ?>
                            <tr><td colspan="8" class="text-center text-muted">No donations found</td></tr>
                        <?php else: ?>
                            <?php foreach ($donations as $don): ?>
                            <tr>
                                <td><?php echo $don['donation_id']; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($don['full_name']); ?></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($don['blood_group']); ?></span></td>
                                <td><?php echo htmlspecialchars($don['camp_title']); ?></td>
                                <td><?php echo htmlspecialchars($don['location_name']); ?></td>
                                <td><strong><?php echo number_format($don['volume_ml']); ?></strong></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($don['donation_time'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-warning btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?php echo $don['donation_id']; ?>"
                                            data-donor="<?php echo $don['full_name']; ?>"
                                            data-time="<?php echo date('Y-m-d\TH:i', strtotime($don['donation_time'])); ?>"
                                            data-volume="<?php echo $don['volume_ml']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="donation_id" value="<?php echo $don['donation_id']; ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Delete this donation?')">
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

            <!-- Donations by Blood Group -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-pie-chart-fill text-success"></i>
                    Donations by Blood Group
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Blood Group</th>
                                <th>Total Donations</th>
                                <th>Total Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_blood_group as $bg): ?>
                            <tr>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($bg['blood_group']); ?></span></td>
                                <td><?php echo $bg['total_donations']; ?></td>
                                <td><?php echo number_format($bg['total_volume']); ?> ml</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Donors -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-trophy-fill text-warning"></i>
                    Top 5 Donors
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Donor Name</th>
                                <th>Blood Group</th>
                                <th>Donations</th>
                                <th>Total Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $rank = 1; foreach ($top_donors as $donor): ?>
                            <tr>
                                <td>
                                    <?php if($rank <= 3): ?>
                                        <span class="badge bg-warning text-dark">🏆 #<?php echo $rank; ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">#<?php echo $rank; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($donor['full_name']); ?></td>
                                <td><span class="badge bg-danger"><?php echo htmlspecialchars($donor['blood_group']); ?></span></td>
                                <td><?php echo $donor['donation_count']; ?> times</td>
                                <td><?php echo number_format($donor['total_donated']); ?> ml</td>
                            </tr>
                        <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Donations per Camp -->
            <div class="section-card">
                <div class="section-title">
                    <i class="bi bi-bar-chart-fill text-info"></i>
                    Donations per Camp
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Camp Name</th>
                                <th>Location</th>
                                <th>Total Donations</th>
                                <th>Total Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($by_camp as $camp): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($camp['title']); ?></td>
                                <td><?php echo htmlspecialchars($camp['location_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $camp['total_donations']; ?></span></td>
                                <td><?php echo number_format($camp['total_volume']); ?> ml</td>
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
                
                <!-- Query Box -->
                <div class="query-box">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-code-square" style="font-size: 1.5rem; color: #60a5fa;"></i>
                        <span style="margin-left: 10px; font-weight: 700; font-size: 1.2rem;">SQL Queries</span>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#1 JOIN (Multiple Tables)-Purpose: Display all donations</div>
                        <div class="query-sql">SELECT d.donation_id, d.donation_time,
       d.volume_ml, donor.full_name,
       donor.blood_group, camp.title,
       location.name as location_name
FROM donation d
JOIN donor ON d.donor_id = donor.donor_id
JOIN camp ON d.camp_id = camp.camp_id
JOIN location 
  ON camp.location_id = location.location_id
ORDER BY d.donation_time DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#2 AGGREGATE (SUM) -Purpose: Calculate the total volume of blood donated</div>
                        <div class="query-sql">SELECT SUM(volume_ml) as total
FROM donation</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#3 GROUP BY + JOIN -Purpose: Show the number of donations and total volume per blood group</div>
                        <div class="query-sql">SELECT donor.blood_group,
       COUNT(d.donation_id) as total_donations,
       SUM(d.volume_ml) as total_volume
FROM donation d
JOIN donor ON d.donor_id = donor.donor_id
GROUP BY donor.blood_group
ORDER BY total_donations DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#4 DATE + SUBQUERY - Purpose: Count how many donations occurred in the last 30 days.</div>
                        <div class="query-sql">SELECT COUNT(*) as count
FROM donation
WHERE donation_time >= 
  DATE_SUB(CURDATE(), INTERVAL 30 DAY)</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#5 HAVING + LIMIT - Purpose: Find the Top 5 donors</div>
                        <div class="query-sql">SELECT donor.full_name, 
       COUNT(d.donation_id) as donation_count,
       SUM(d.volume_ml) as total_donated
FROM donor
JOIN donation d 
  ON donor.donor_id = d.donor_id
GROUP BY donor.donor_id
HAVING donation_count >= 1
ORDER BY donation_count DESC
LIMIT 5</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#6 LEFT JOIN + GROUP BY - Purpose: Display total donations and volume for each camp</div>
                        <div class="query-sql">SELECT camp.title, location.name,
       COUNT(d.donation_id) as total_donations,
       SUM(d.volume_ml) as total_volume
FROM camp
LEFT JOIN donation d 
  ON camp.camp_id = d.camp_id
LEFT JOIN location 
  ON camp.location_id = location.location_id
GROUP BY camp.camp_id
ORDER BY total_donations DESC</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#7 INSERT - Purpose: Add a new donation</div>
                        <div class="query-sql">INSERT INTO donation 
  (donor_id, camp_id, donation_time, 
   volume_ml, created_at)
VALUES (?, ?, ?, ?, NOW())</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#8 UPDATE - Purpose: Edit an existing donation</div>
                        <div class="query-sql">UPDATE donation
SET donor_id=?, camp_id=?, 
    donation_time=?, volume_ml=?
WHERE donation_id=?</div>
                    </div>

                    <div class="mb-3">
                        <div class="query-badge">#9 DELETE - Purpose: Remove a donation record</div>
                        <div class="query-sql">DELETE FROM donation
WHERE donation_id=?</div>
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
                    <h5 class="modal-title">Add New Donation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Donor</label>
                        <select name="donor_id" class="form-select" required>
                            <option value="">Select Donor</option>
                            <?php foreach ($donors as $donor): ?>
                                <option value="<?php echo $donor['donor_id']; ?>">
                                    <?php echo htmlspecialchars($donor['full_name']); ?> (<?php echo $donor['blood_group']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp</label>
                        <select name="camp_id" class="form-select" required>
                            <option value="">Select Camp</option>
                            <?php foreach ($camps as $camp): ?>
                                <option value="<?php echo $camp['camp_id']; ?>">
                                    <?php echo htmlspecialchars($camp['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Donation Date & Time</label>
                        <input type="datetime-local" name="donation_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Volume (ml)</label>
                        <input type="number" name="volume_ml" class="form-control" min="1" max="1000" required placeholder="e.g. 450">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Add Donation</button>
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
                    <h5 class="modal-title">Edit Donation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="donation_id" id="edit_donation_id">
                    <div class="mb-3">
                        <label class="form-label">Donor</label>
                        <select name="donor_id" id="edit_donor_id" class="form-select" required>
                            <?php foreach ($donors as $donor): ?>
                                <option value="<?php echo $donor['donor_id']; ?>">
                                    <?php echo htmlspecialchars($donor['full_name']); ?> (<?php echo $donor['blood_group']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Camp</label>
                        <select name="camp_id" id="edit_camp_id" class="form-select" required>
                            <?php foreach ($camps as $camp): ?>
                                <option value="<?php echo $camp['camp_id']; ?>">
                                    <?php echo htmlspecialchars($camp['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Donation Date & Time</label>
                        <input type="datetime-local" name="donation_time" id="edit_donation_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Volume (ml)</label>
                        <input type="number" name="volume_ml" id="edit_volume_ml" class="form-control" min="1" max="1000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update" class="btn btn-warning">Update Donation</button>
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
            document.getElementById('edit_donation_id').value = button.getAttribute('data-id');
            document.getElementById('edit_donation_time').value = button.getAttribute('data-time');
            document.getElementById('edit_volume_ml').value = button.getAttribute('data-volume');
        });
    }
});
</script>

<?php include '_footer.php'; ?>