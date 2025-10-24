
<?php
require_once 'db_connect.php';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
// Handle Insert
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO location (name, address, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$_POST['name'], $_POST['address']]);
}
// Handle Update
if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE location SET name=?, address=? WHERE location_id=?");
    $stmt->execute([$_POST['name'], $_POST['address'], $_POST['location_id']]);
}
// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM location WHERE location_id=?");
    $stmt->execute([$_POST['location_id']]);
}
// Filter or List all
if ($filter !== '') {
    $stmt = $pdo->prepare("SELECT location_id, name, address, DATE(created_at) as created_at FROM location WHERE name LIKE ? OR address LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$filter%", "%$filter%"]);
    $locations = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT location_id, name, address, DATE(created_at) as created_at FROM location ORDER BY created_at DESC");
    $locations = $stmt->fetchAll();
}
include '_header.php';
?>
    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-geo-alt fs-3 text-primary me-2"></i>
                            <h3 class="mb-0 fw-bold">Locations</h3>
                        </div>
                       <a href="../index.html" class="btn btn-outline-secondary fw-bold"><i class="bi bi-house"></i> Go to Home</a>
                    </div>
                    <form method="get" class="row g-2 mb-4 align-items-center">
                        <div class="col-md-5">
                            <input type="text" name="filter" class="form-control" placeholder="Filter by keyword (e.g. Khulna)" value="<?= htmlspecialchars($filter) ?>">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary fw-bold"><i class="bi bi-funnel"></i> Filter</button>
                        </div>
                        <div class="col-md-2 d-grid ms-auto">
                            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Add</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
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
                            <?php foreach ($locations as $loc): ?>
                                <tr>
                                    <td><?= isset($loc['location_id']) ? $loc['location_id'] : '' ?></td>
                                    <td id="name-<?= $loc['location_id'] ?>">
                                        <?= isset($loc['name']) ? htmlspecialchars($loc['name']) : '' ?>
                                    </td>
                                    <td id="address-<?= $loc['location_id'] ?>">
                                        <?= isset($loc['address']) ? htmlspecialchars($loc['address']) : '' ?>
                                    </td>
                                    <td><?= isset($loc['created_at']) ? $loc['created_at'] : '' ?></td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="location_id" value="<?= isset($loc['location_id']) ? $loc['location_id'] : '' ?>">
                                            <button type="button" class="btn btn-warning btn-sm edit-btn" data-id="<?= $loc['location_id'] ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this location?')"><i class="bi bi-trash"></i></button>
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
        <div class="col-12 col-lg-2 mb-4 mb-lg-0">
            <div class="query-box p-2 mt-4 mt-lg-0">
                <div class="mb-2"><i class="bi bi-terminal"></i> <span class="query-title">SQL Queries</span></div>
                <div class="mb-2">
                    <span class="query-badge">#1 SELECT (All)</span>
                    <div class="query-sql small">SELECT location_id, name, address, DATE(created_at) as created_at FROM location ORDER BY created_at DESC</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#2 FILTER</span>
                    <div class="query-sql small">SELECT location_id, name, address, DATE(created_at) as created_at FROM location WHERE name LIKE '%keyword%' OR address LIKE '%keyword%' ORDER BY created_at DESC</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#3 INSERT</span>
                    <div class="query-sql small">INSERT INTO location (name, address, created_at) VALUES ('Name', 'Address', NOW())</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#4 UPDATE</span>
                    <div class="query-sql small">UPDATE location SET name='Name', address='Address' WHERE location_id=1</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#5 DELETE</span>
                    <div class="query-sql small">DELETE FROM location WHERE location_id=1</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#6 COUNT</span>
                    <div class="query-sql small">SELECT COUNT(*) FROM location</div>
                </div>
            </div>
        </div>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="addName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="addAddress" name="address" required>
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
<script>
// Make fields editable only after clicking Edit
document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.edit-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                        var id = btn.getAttribute('data-id');
                        var nameCell = document.getElementById('name-' + id);
                        var addressCell = document.getElementById('address-' + id);
                        var name = nameCell.innerText;
                        var address = addressCell.innerText;
                        nameCell.innerHTML = '';
                        addressCell.innerHTML = '';
                        var form = document.createElement('form');
                        form.method = 'post';
                        form.className = 'd-inline-flex gap-1 align-items-center mb-0';
                        form.innerHTML =
                                '<input type="hidden" name="location_id" value="' + id + '">' +
                                '<input type="text" name="name" value="' + name + '" class="form-control form-control-sm" required placeholder="Name" style="max-width:110px">' +
                                '<input type="text" name="address" value="' + address + '" class="form-control form-control-sm" required placeholder="Address" style="max-width:140px">' +
                                '<button type="submit" name="update" class="btn btn-success btn-sm" title="Save"><i class="bi bi-check"></i></button>';
                        nameCell.appendChild(form);
                });
        });
});
</script>
    </div>
    <?php include '_footer.php'; ?>
