
<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO camp (title, location_id, camp_address, camp_date, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([
        $_POST['title'],
        $_POST['location_id'],
        $_POST['camp_address'],
        $_POST['camp_date']
    ]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
// Handle Update
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE camp SET title=?, location_id=?, camp_address=?, camp_date=? WHERE camp_id=?");
    $stmt->execute([
        $_POST['title'],
        $_POST['location_id'],
        $_POST['camp_address'],
        $_POST['camp_date'],
        $_POST['camp_id']
    ]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
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
// Sort logic only
$orderBy = isset($_GET['sort']) && $_GET['sort'] === 'id' ? 'c.camp_id ASC' : 'c.camp_date DESC';
$sql = "SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id ORDER BY $orderBy";
$camps = $pdo->query($sql)->fetchAll();
// Aggregate: Count
$count = $pdo->query("SELECT COUNT(*) FROM camp")->fetchColumn();
// Aggregate: Camps per location
$campsPerLocation = $pdo->query("SELECT l.name, COUNT(*) as total FROM camp c JOIN location l ON c.location_id = l.location_id GROUP BY l.name")->fetchAll();
// Upcoming camps (date >= today)
$upcomingCamps = $pdo->query("SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id WHERE camp_date >= CURDATE() ORDER BY camp_date ASC")->fetchAll();
// Past camps (date < today)
$pastCamps = $pdo->query("SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id WHERE camp_date < CURDATE() ORDER BY camp_date DESC")->fetchAll();


include '_header.php';
?>
<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-10">
        <div class="card shadow-lg border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-event fs-3 text-primary me-2"></i>
                        <h3 class="mb-0 fw-bold">Camps <span class="badge bg-primary ms-2"><?php echo $count; ?></span></h3>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../index.html" class="btn btn-outline-secondary fw-bold"><i class="bi bi-house"></i> Go to Home</a>
                        <a href="?sort=id" class="btn btn-outline-primary fw-bold ms-2">Sort by ID</a>
                        <button type="button" class="btn btn-primary fw-bold ms-2" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Add</button>
                    </div>
                </div>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <a href="?" class="btn btn-outline-primary btn-sm">All Camps</a>
                    <a href="?show=group" class="btn btn-outline-primary btn-sm">Camps Per Location</a>
                    <a href="?show=upcoming" class="btn btn-outline-primary btn-sm">Upcoming Camps</a>
                    <a href="?show=past" class="btn btn-outline-primary btn-sm">Past Camps</a>
                </div>
                <?php if (isset($_GET['show']) && $_GET['show'] === 'group'): ?>
                    <div class="card mb-3"><div class="card-body"><h5 class="mb-3">Camps Per Location (GROUP BY)</h5>
                        <table class="table table-sm table-bordered"><thead><tr><th>Location</th><th>Total Camps</th></tr></thead><tbody>
                        <?php foreach ($campsPerLocation as $row): ?>
                            <tr><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo $row['total']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div></div>
                <?php elseif (isset($_GET['show']) && $_GET['show'] === 'upcoming'): ?>
                    <div class="card mb-3 w-100"><div class="card-body"><h5 class="mb-3">Upcoming Camps</h5>
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered w-100"><thead><tr><th>Title</th><th>Location</th><th>Address</th><th>Date</th></tr></thead><tbody>
                        <?php foreach ($upcomingCamps as $row): ?>
                            <tr><td><?php echo htmlspecialchars($row['title']); ?></td><td><?php echo htmlspecialchars($row['location_name']); ?></td><td><?php echo htmlspecialchars($row['camp_address']); ?></td><td><?php echo $row['camp_date']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                        </div>
                    </div></div>
                <?php elseif (isset($_GET['show']) && $_GET['show'] === 'past'): ?>
                    <div class="card mb-3 w-100"><div class="card-body"><h5 class="mb-3">Past Camps</h5>
                        <div class="table-responsive">
                        <table class="table table-sm table-bordered w-100"><thead><tr><th>Title</th><th>Location</th><th>Address</th><th>Date</th></tr></thead><tbody>
                        <?php foreach ($pastCamps as $row): ?>
                            <tr><td><?php echo htmlspecialchars($row['title']); ?></td><td><?php echo htmlspecialchars($row['location_name']); ?></td><td><?php echo htmlspecialchars($row['camp_address']); ?></td><td><?php echo $row['camp_date']; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                        </div>
                    </div></div>
                <?php else: ?>
                    <div class="table-responsive w-100">
                        <table class="table align-middle table-striped w-100">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Camp Address</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($camps as $camp): ?>
                                <tr>
                                    <td><?php echo $camp['camp_id']; ?></td>
                                    <td><?php echo htmlspecialchars($camp['title']); ?></td>
                                    <td><?php echo htmlspecialchars($camp['location_name']); ?></td>
                                    <td><?php echo htmlspecialchars($camp['camp_address']); ?></td>
                                    <td><?php echo $camp['camp_date']; ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="camp_id" value="<?php echo $camp['camp_id']; ?>">
                                            <button type="button" class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $camp['camp_id']; ?>" data-title="<?php echo htmlspecialchars($camp['title']); ?>" data-location="<?php echo $camp['location_id']; ?>" data-address="<?php echo htmlspecialchars($camp['camp_address']); ?>" data-date="<?php echo $camp['camp_date']; ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this camp?')"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-2 mb-4 mb-lg-0">
        <div class="query-box p-2 mt-4 mt-lg-0">
            <div class="mb-2"><i class="bi bi-terminal"></i> <span class="query-title">SQL Queries</span></div>
            <div class="mb-2">
                <span class="query-badge">#1 SELECT (JOIN)</span>
                <div class="query-sql small"><strong>Feature:</strong> Main camp list with location name (table display, filter, sort)<br>
SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id ORDER BY c.camp_date DESC</div>
            </div>
            <div class="mb-2">
                <span class="query-badge">#2 AGGREGATE</span>
                <div class="query-sql small"><strong>Feature:</strong> Total number of camps (badge count)<br>
SELECT COUNT(*) FROM camp</div>
            </div>
            <div class="mb-2">
                <span class="query-badge">#3 GROUP BY</span>
                <div class="query-sql small"><strong>Feature:</strong> Camps per location (for analytics/statistics)<br>
SELECT l.name, COUNT(*) as total FROM camp c JOIN location l ON c.location_id = l.location_id GROUP BY l.name</div>
            </div>
            <div class="mb-2">
                <span class="query-badge">#4 UPCOMING</span>
                <div class="query-sql small"><strong>Feature:</strong> Upcoming camps (date today or later)<br>
SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id WHERE camp_date &gt;= CURDATE() ORDER BY camp_date ASC</div>
            </div>
            <div class="mb-2">
                <span class="query-badge">#5 PAST</span>
                <div class="query-sql small"><strong>Feature:</strong> Past camps (date before today)<br>
SELECT c.*, l.name as location_name FROM camp c JOIN location l ON c.location_id = l.location_id WHERE camp_date &lt; CURDATE() ORDER BY camp_date DESC</div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Camp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="addTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="addLocation" class="form-label">Location</label>
                        <select class="form-select" id="addLocation" name="location_id" required>
                            <option value="">Select location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="addAddress" class="form-label">Camp Address</label>
                        <input type="text" class="form-control" id="addAddress" name="camp_address" required placeholder="Enter full address">
                    </div>
                    <div class="mb-3">
                        <label for="addDate" class="form-label">Camp Date</label>
                        <input type="date" class="form-control" id="addDate" name="camp_date" required>
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

<!-- Edit Modal (strict, no hover, no animation) -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="camp_id" id="editCampId">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Camp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editLocation" class="form-label">Location</label>
                        <select class="form-select" id="editLocation" name="location_id" required>
                            <option value="">Select location</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo $loc['location_id']; ?>"><?php echo htmlspecialchars($loc['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editAddress" class="form-label">Camp Address</label>
                        <input type="text" class="form-control" id="editAddress" name="camp_address" required placeholder="Enter full address">
                    </div>
                    <div class="mb-3">
                        <label for="editDate" class="form-label">Camp Date</label>
                        <input type="date" class="form-control" id="editDate" name="camp_date" required>
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
