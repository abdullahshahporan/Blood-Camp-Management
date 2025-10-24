<!-- Removed duplicate subquery SQL from below main content; now only in right panel -->
<style>
/* Strict filter modal: no hover, focus, or animation */
#filterModal .form-control:focus, #filterModal .form-select:focus, #filterModal .form-check-input:focus {
    box-shadow: none !important;
    border-color: #ced4da !important;
}
#filterModal .form-control, #filterModal .form-select, #filterModal .form-check-input {
    transition: none !important;
    background: #fff !important;
}
#filterModal .form-check-input:checked {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
}
#filterModal .btn, #filterModal .btn:focus, #filterModal .btn:active, #filterModal .btn:hover {
    box-shadow: none !important;
    transition: none !important;
    outline: none !important;
}
#filterModal .btn-primary {
    background-color: #1677ff !important;
    border-color: #1677ff !important;
}
#filterModal .btn-outline-primary {
    background: #fff !important;
    color: #1677ff !important;
    border-color: #1677ff !important;
}
#filterModal .btn-outline-primary:hover, #filterModal .btn-outline-primary:focus {
    background: #fff !important;
    color: #1677ff !important;
    border-color: #1677ff !important;
}
#filterModal .btn-link {
    color: #e53935 !important;
    text-decoration: none !important;
}
#filterModal .btn-link:hover, #filterModal .btn-link:focus {
    color: #e53935 !important;
    text-decoration: none !important;
}
</style>
<?php
require_once 'db_connect.php';
// Keyword filter
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';
// Location filter
$locationFilter = isset($_GET['location_filter']) ? trim($_GET['location_filter']) : '';
// Handle Insert
if (isset($_POST['add'])) {
    if (
        isset($_POST['full_name']) &&
        isset($_POST['phone']) &&
        isset($_POST['blood_group']) &&
        isset($_POST['last_donation']) &&
        isset($_POST['location'])
    ) {
        $stmt = $pdo->prepare("INSERT INTO donor (full_name, phone, blood_group, last_donation, location) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['phone'],
            $_POST['blood_group'],
            $_POST['last_donation'],
            $_POST['location']
        ]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
// Handle Update
if (isset($_POST['update'])) {
    if (
        isset($_POST['full_name']) &&
        isset($_POST['phone']) &&
        isset($_POST['blood_group']) &&
        isset($_POST['last_donation']) &&
        isset($_POST['location']) &&
        isset($_POST['donor_id'])
    ) {
        $stmt = $pdo->prepare("UPDATE donor SET full_name=?, phone=?, blood_group=?, last_donation=?, location=? WHERE donor_id=?");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['phone'],
            $_POST['blood_group'],
            $_POST['last_donation'],
            $_POST['location'],
            $_POST['donor_id']
        ]);
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
// Filter or List all
// Get locations for dropdown
$locationsList = $pdo->query("SELECT location_id, name FROM location ORDER BY name ASC")->fetchAll();
// Strict filter logic: checkboxes for blood_group, text input for location, radio for last donation, subquery for complex search
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
$sql = "SELECT * FROM (SELECT donor_id, full_name, phone, blood_group, last_donation, location FROM donor) AS subq";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY donor_id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donors = $stmt->fetchAll();
include '_header.php';
?>
    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-people fs-3 text-primary me-2"></i>
                            <h3 class="mb-0 fw-bold">Donors</h3>
                        </div>
                    </div>
                    <div class="row g-2 mb-4 align-items-center">
                        <div class="col-md-2 d-grid">
                            <button type="button" class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#filterModal"><i class="bi bi-funnel"></i> Filter</button>
                        </div>
                        <div class="col-md-2 d-grid ms-auto">
                            <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-circle"></i> Add</button>
                        </div>
                    </div>
<!-- Filter Modal -->
<div class="modal show" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-modal="true" role="dialog" style="display:none; background:transparent;">
  <div class="modal-dialog modal-lg" style="pointer-events:auto;">
    <div class="modal-content" style="box-shadow:0 0 0 1px #e0e0e0; border-radius:12px; border:1px solid #e0e0e0;">
      <form method="get">
        <div class="modal-header" style="border-bottom:1px solid #e0e0e0;">
          <h5 class="modal-title" id="filterModalLabel">Filter Donors</h5>
          <button type="button" class="btn-close" id="closeFilterModal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <div id="filter-summary" class="mb-2" style="font-size:1rem;font-weight:500;color:#1677ff;display:none;"></div>
            <div class="row">
              <div class="col-md-4">
                <label class="form-label">Blood Group</label>
                <div class="d-flex flex-wrap gap-2">
                                    <?php $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']; foreach ($groups as $bg): ?>
                                        <div class="form-check" style="margin-bottom:0;">
                                            <input class="form-check-input" type="checkbox" name="blood_group[]" value="<?php echo $bg; ?>" id="bg-<?php echo $bg; ?>"
                                                    <?php if (!empty($_GET['blood_group']) && is_array($_GET['blood_group']) && in_array($bg, $_GET['blood_group'])) echo 'checked'; ?>
                                                    style="box-shadow:none;outline:none;">
                                            <label class="form-check-label" for="bg-<?php echo $bg; ?>" style="user-select:none;cursor:pointer;"> <?php echo $bg; ?> </label>
                                        </div>
                                    <?php endforeach; ?>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Location</label>
                <input type="text" class="form-control" name="location" placeholder="Type location (e.g. Khulna)" autocomplete="off" value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Last Donation</label>
                <div class="d-flex flex-column gap-1">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="last_donation_range" id="donated3m" value="3" <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='3') echo 'checked'; ?>>
                    <label class="form-check-label" for="donated3m">Within 3 months</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="last_donation_range" id="donated6m" value="6" <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='6') echo 'checked'; ?>>
                    <label class="form-check-label" for="donated6m">Within 6 months</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="last_donation_range" id="donated12m" value="12" <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='12') echo 'checked'; ?>>
                    <label class="form-check-label" for="donated12m">Within 12 months</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="last_donation_range" id="donated1yplus" value="over12" <?php if(isset($_GET['last_donation_range']) && $_GET['last_donation_range']==='over12') echo 'checked'; ?>>
                    <label class="form-check-label" for="donated1yplus">1 year or more</label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid #e0e0e0;">
          <button type="reset" class="btn btn-link text-danger">Clear</button>
          <button type="submit" class="btn btn-primary">Done</button>
        </div>
      </form>
    </div>
  </div>
</div>
                    <div class="table-responsive">
                        <table class="table align-middle">
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
                            <?php foreach ($donors as $donor): ?>
                                <tr>
                                    <td><?php echo isset($donor['donor_id']) ? $donor['donor_id'] : ''; ?></td>
                                    <td id="name-<?php echo $donor['donor_id']; ?>">
                                        <?php echo isset($donor['full_name']) ? htmlspecialchars($donor['full_name']) : ''; ?>
                                    </td>
                                    <td id="phone-<?php echo $donor['donor_id']; ?>">
                                        <?php echo isset($donor['phone']) ? htmlspecialchars($donor['phone']) : ''; ?>
                                    </td>
                                    <td id="group-<?php echo $donor['donor_id']; ?>">
                                        <?php echo isset($donor['blood_group']) ? htmlspecialchars($donor['blood_group']) : ''; ?>
                                    </td>
                                    <td id="lastdon-<?php echo $donor['donor_id']; ?>">
                                        <?php echo isset($donor['last_donation']) ? htmlspecialchars($donor['last_donation']) : ''; ?>
                                    </td>
                                    <td id="location-<?php echo $donor['donor_id']; ?>">
                                        <?php echo isset($donor['location']) ? htmlspecialchars($donor['location']) : ''; ?>
                                    </td>
                                    <td>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="donor_id" value="<?php echo isset($donor['donor_id']) ? $donor['donor_id'] : ''; ?>">
                                            <button type="button" class="btn btn-warning btn-sm edit-btn" data-id="<?php echo $donor['donor_id']; ?>" title="Edit"><i class="bi bi-pencil"></i></button>
                                            <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this donor?')"><i class="bi bi-trash"></i></button>
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
                    <div class="query-sql small">SELECT donor_id, full_name, phone, blood_group, last_donation, DATE(created_at) as created_at FROM donor ORDER BY donor_id ASC</div>
                </div>
                <div class="mb-2">
                                        <span class="query-badge">#2 FILTER (Subquery)</span>
                                        <div class="query-sql small" style="white-space:pre-wrap;">
SELECT donor_id, full_name, phone, blood_group, location,
             MAX(donation_date) AS last_donation,
             DATE(created_at) AS created_at
FROM donors
LEFT JOIN donations ON donors.donor_id = donations.donor_id
WHERE
    -- Filter by blood group
    (blood_group IN ('A+', 'O-') OR 1=1)
    -- Filter by location
    AND (location LIKE '%Khulna%' OR 1=1)
    -- Filter by last donation period (subquery)
    AND (
        donors.donor_id IN (
            SELECT donor_id FROM donations
            WHERE DATEDIFF(CURDATE(), donation_date) <= 180
        )
        OR 1=1
    )
GROUP BY donor_id, full_name, phone, blood_group, location, created_at
ORDER BY donor_id ASC;
                                        </div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#3 INSERT</span>
                    <div class="query-sql small">INSERT INTO donor (full_name, phone, blood_group, last_donation, created_at) VALUES ('Name', 'Phone', 'Group', 'YYYY-MM-DD', NOW())</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#4 UPDATE</span>
                    <div class="query-sql small">UPDATE donor SET full_name='Name', phone='Phone', blood_group='Group', last_donation='YYYY-MM-DD' WHERE donor_id=1</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#5 DELETE</span>
                    <div class="query-sql small">DELETE FROM donor WHERE donor_id=1</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#6 COUNT</span>
                    <div class="query-sql small">SELECT COUNT(*) FROM donor</div>
                </div>
                <div class="mb-2">
                    <span class="query-badge">#7 GROUP BY</span>
                    <div class="query-sql small">SELECT blood_group, COUNT(*) as total FROM donor GROUP BY blood_group</div>
                </div>
            </div>
        </div>
<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add Donor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="addName" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPhone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="addPhone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="addGroup" class="form-label">Blood Group</label>
                        <input type="text" class="form-control" id="addGroup" name="blood_group" required>
                    </div>
                    <div class="mb-3">
                        <label for="addLastDonation" class="form-label">Last Donation</label>
                        <input type="date" class="form-control" id="addLastDonation" name="last_donation" required>
                    </div>
                    <div class="mb-3">
                        <label for="addLocation" class="form-label">Location</label>
                        <input type="text" class="form-control" id="addLocation" name="location" required placeholder="Type location (e.g. Dhaka, Khulna)">
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
<?php
// Build filter SQL query (with subquery for last donation)
$filter_sql = "SELECT * FROM donors WHERE 1";
$filter_conditions = [];
if (!empty($_GET['blood_group']) && is_array($_GET['blood_group'])) {
    $groups = array_map(function($g) { return "'" . addslashes($g) . "'"; }, $_GET['blood_group']);
    $filter_conditions[] = "blood_group IN (" . implode(",", $groups) . ")";
}
if (!empty($_GET['location'])) {
    $loc = addslashes($_GET['location']);
    $filter_conditions[] = "location LIKE '%$loc%'";
}
if (!empty($_GET['last_donation_range'])) {
    $months = $_GET['last_donation_range'];
    if ($months === 'over12') {
        $filter_conditions[] = "donor_id NOT IN (SELECT donor_id FROM donations WHERE DATEDIFF(CURDATE(), donation_date) <= 365)";
    } else {
        $days = intval($months) * 30;
        $filter_conditions[] = "donor_id IN (SELECT donor_id FROM donations WHERE DATEDIFF(CURDATE(), donation_date) <= $days)";
    }
}
if ($filter_conditions) {
    $filter_sql .= ' AND ' . implode(' AND ', $filter_conditions);
}
$filter_sql .= ' ORDER BY donor_id ASC';
?>
<script>
// Strict filter modal: no fade, no backdrop, no animation
const filterBtn = document.querySelector('[data-bs-target="#filterModal"]');
const filterModal = document.getElementById('filterModal');
const closeFilterModal = document.getElementById('closeFilterModal');
if (filterBtn && filterModal && closeFilterModal) {
  filterBtn.addEventListener('click', function(e) {
    e.preventDefault();
    filterModal.style.display = 'block';
    filterModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  });
  closeFilterModal.addEventListener('click', function() {
    filterModal.style.display = 'none';
    filterModal.classList.remove('show');
    document.body.style.overflow = '';
  });
  // Close on outside click
  window.addEventListener('mousedown', function(e) {
    if (filterModal.classList.contains('show') && !filterModal.querySelector('.modal-content').contains(e.target) && e.target !== filterBtn) {
      filterModal.style.display = 'none';
      filterModal.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
}
// Make fields editable only after clicking Edit
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = btn.getAttribute('data-id');
            var nameCell = document.getElementById('name-' + id);
            var phoneCell = document.getElementById('phone-' + id);
            var groupCell = document.getElementById('group-' + id);
            var lastDonCell = document.getElementById('lastdon-' + id);
            var name = nameCell.innerText;
            var phone = phoneCell.innerText;
            var group = groupCell.innerText;
            var lastdon = lastDonCell.innerText;
            var locationCell = document.getElementById('location-' + id);
            var locationName = locationCell ? locationCell.innerText : '';
            nameCell.innerHTML = '';
            phoneCell.innerHTML = '';
            groupCell.innerHTML = '';
            lastDonCell.innerHTML = '';
            if (locationCell) locationCell.innerHTML = '';
            // Create a single form spanning the row
            var form = document.createElement('form');
            form.method = 'post';
            form.className = 'd-flex gap-1 align-items-center mb-0';
            form.innerHTML = '<input type="hidden" name="donor_id" value="' + id + '">';

            var nameInput = document.createElement('input');
            nameInput.type = 'text';
            nameInput.name = 'full_name';
            nameInput.value = name;
            nameInput.className = 'form-control form-control-sm';
            nameInput.required = true;
            nameInput.placeholder = 'Full Name';
            nameInput.style.maxWidth = '110px';
            form.appendChild(nameInput);

            var phoneInput = document.createElement('input');
            phoneInput.type = 'text';
            phoneInput.name = 'phone';
            phoneInput.value = phone;
            phoneInput.className = 'form-control form-control-sm';
            phoneInput.required = true;
            phoneInput.placeholder = 'Phone';
            phoneInput.style.maxWidth = '110px';
            form.appendChild(phoneInput);

            var groupInput = document.createElement('input');
            groupInput.type = 'text';
            groupInput.name = 'blood_group';
            groupInput.value = group;
            groupInput.className = 'form-control form-control-sm';
            groupInput.required = true;
            groupInput.placeholder = 'Blood Group';
            groupInput.style.maxWidth = '80px';
            form.appendChild(groupInput);

            var lastDonInput = document.createElement('input');
            lastDonInput.type = 'date';
            lastDonInput.name = 'last_donation';
            lastDonInput.value = lastdon;
            lastDonInput.className = 'form-control form-control-sm';
            lastDonInput.required = true;
            lastDonInput.style.maxWidth = '120px';
            form.appendChild(lastDonInput);

            var locationInput = document.createElement('input');
            locationInput.type = 'text';
            locationInput.name = 'location';
            locationInput.value = locationName;
            locationInput.className = 'form-control form-control-sm';
            locationInput.required = true;
            locationInput.placeholder = 'Type location (e.g. Dhaka, Khulna)';
            locationInput.style.maxWidth = '140px';
            form.appendChild(locationInput);

            var saveBtn = document.createElement('button');
            saveBtn.type = 'submit';
            saveBtn.name = 'update';
            saveBtn.className = 'btn btn-success btn-sm';
            saveBtn.title = 'Save';
            saveBtn.innerHTML = '<i class="bi bi-check"></i>';
            form.appendChild(saveBtn);

            // Clear all cells and append form spanning all fields
            nameCell.innerHTML = '';
            phoneCell.innerHTML = '';
            groupCell.innerHTML = '';
            lastDonCell.innerHTML = '';
            locationCell.innerHTML = '';
            nameCell.appendChild(form);
        });
    });
});
function updateFilterSummary() {
  const summary = [];
  const checkedGroups = Array.from(document.querySelectorAll('#filterModal input[name="blood_group[]"]:checked')).map(cb => cb.value);
  if (checkedGroups.length) summary.push('Blood Group: ' + checkedGroups.join(', '));
  const loc = document.querySelector('#filterModal input[name="location"]').value;
  if (loc) summary.push('Location: ' + loc);
  const lastDon = document.querySelector('#filterModal input[name="last_donation_range"]:checked');
  if (lastDon) {
    let label = lastDon.parentElement.querySelector('label').innerText;
    summary.push('Last Donation: ' + label);
  }
  const summaryDiv = document.getElementById('filter-summary');
  if (summary.length) {
    summaryDiv.innerText = 'Active Filters: ' + summary.join(' | ');
    summaryDiv.style.display = 'block';
  } else {
    summaryDiv.innerText = '';
    summaryDiv.style.display = 'none';
  }
}
document.querySelectorAll('#filterModal input, #filterModal select').forEach(el => {
  el.addEventListener('change', updateFilterSummary);
});
updateFilterSummary();
// Clear button resets modal and reloads page
const clearBtn = document.querySelector('#filterModal .btn-link.text-danger');
if (clearBtn) {
  clearBtn.addEventListener('click', function(e) {
    e.preventDefault();
    window.location = window.location.pathname;
  });
}
</script>
    </div>
    <?php include '_footer.php'; ?>
