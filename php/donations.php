<?php
require_once 'db_connect.php';

// Fetch donations with donor, camp, and location info
$sql = "SELECT d.donation_id, donor.full_name, camp.title AS camp_title, location.name AS location_name, d.volume_ml, d.donation_time
FROM donation d
JOIN donor ON d.donor_id = donor.donor_id
JOIN camp ON d.camp_id = camp.camp_id
JOIN location ON camp.location_id = location.location_id
ORDER BY d.donation_time DESC";
$donations = $pdo->query($sql)->fetchAll();

// Feature: Donations per camp (analytics/statistics)
$campStats = $pdo->query("SELECT camp.title, COUNT(*) AS total_donations FROM donation d JOIN camp ON d.camp_id = camp.camp_id GROUP BY camp.title")->fetchAll();

// Feature: Donors with more than one donation (advanced analytics)
$multiDonors = $pdo->query("SELECT full_name FROM donor WHERE donor_id IN (SELECT donor_id FROM donation GROUP BY donor_id HAVING COUNT(*) > 1)")->fetchAll();

// Fetch donors and camps for add form
$donors = $pdo->query("SELECT donor_id, full_name FROM donor ORDER BY full_name")->fetchAll();
$camps = $pdo->query("SELECT camp_id, title FROM camp ORDER BY title")->fetchAll();

include '_header.php';
?>
<div class="row g-4 align-items-start">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="mb-0 fw-bold">Donations</h3>
                    <a href="../index.html" class="btn btn-outline-secondary fw-bold"><i class="bi bi-house"></i> Go to Home</a>
                </div>
                
                <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
                    <button id="showCampStats" class="btn btn-outline-primary btn-sm fw-semibold" type="button">Donations per Camp</button>
                    <button id="showMultiDonors" class="btn btn-outline-primary btn-sm fw-semibold" type="button">Donors with More Than One Donation</button>
                    
                    <div class="dropdown ms-auto" style="position:relative;">
                        <button id="sortByBtn" class="btn btn-outline-secondary fw-semibold" type="button" style="border-radius:8px;">
                            Sort by <i class="bi bi-chevron-down"></i>
                        </button>
                        <div id="sortMenu" class="dropdown-menu" style="display:none;position:absolute;top:110%;right:0;min-width:220px;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(16,19,26,0.15);padding:8px 0;z-index:1000;border:1px solid #e5e7eb;">
                            <button class="dropdown-item px-4 py-2" data-sort="date_asc" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">Date Ascending</button>
                            <button class="dropdown-item px-4 py-2" data-sort="date_desc" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">Date Descending</button>
                            <button class="dropdown-item px-4 py-2" data-sort="volume_asc" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">Volume Ascending</button>
                            <button class="dropdown-item px-4 py-2" data-sort="volume_desc" style="background:none;border:none;width:100%;text-align:left;cursor:pointer;transition:background 0.2s;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='none'">Volume Descending</button>
                        </div>
                    </div>
                </div>

                <div id="mainContentPanel">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Donor Name</th>
                                    <th>Camp Name</th>
                                    <th>Location</th>
                                    <th>Volume (ml)</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            if (count($donations) === 0) {
                                echo '<tr><td colspan="6" class="text-center">No data available</td></tr>';
                            } else {
                                foreach ($donations as $row) {
                                    echo '<tr>';
                                    echo '<td>' . $row['donation_id'] . '</td>';
                                    echo '<td>' . htmlspecialchars($row['full_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['camp_title']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['location_name']) . '</td>';
                                    echo '<td>' . $row['volume_ml'] . '</td>';
                                    echo '<td>' . date('Y-m-d', strtotime($row['donation_time'])) . '</td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-lg-4">
        <div style="background:#181f2a;border-radius:20px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.3);position:sticky;top:20px;">
            <div class="d-flex align-items-center mb-4" style="gap:10px;">
                <i class="bi bi-terminal" style="color:#60a5fa;font-size:1.5rem;"></i>
                <span style="color:#fff;font-weight:700;font-size:1.25rem;letter-spacing:0.5px;">SQL Queries</span>
            </div>
            
            <!-- Query #1: JOIN -->
            <div class="mb-4">
                <span style="background:#2563eb;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#1 JOIN</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #2563eb;">
                    <div style="color:#93c5fd;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Main donation list with relations</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">SELECT d.donation_id, donor.full_name, 
       camp.title AS camp_title, 
       location.name AS location_name, 
       d.volume_ml, d.donation_time
FROM donation d
JOIN donor ON d.donor_id = donor.donor_id
JOIN camp ON d.camp_id = camp.camp_id
JOIN location ON camp.location_id = location.location_id
ORDER BY d.donation_time DESC</pre>
                </div>
            </div>

            <!-- Query #2: AGGREGATE -->
            <div class="mb-4">
                <span style="background:#10b981;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#2 AGGREGATE</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #10b981;">
                    <div style="color:#6ee7b7;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Total volume donated</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">SELECT SUM(volume_ml) AS total_volume 
FROM donation</pre>
                </div>
            </div>

            <!-- Query #3: GROUP BY -->
            <div class="mb-4">
                <span style="background:#f59e0b;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#3 GROUP BY</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #f59e0b;">
                    <div style="color:#fcd34d;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Donations per camp</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">SELECT camp.title, COUNT(*) AS total_donations
FROM donation d
JOIN camp ON d.camp_id = camp.camp_id
GROUP BY camp.title</pre>
                </div>
            </div>

            <!-- Query #4: SUBQUERY -->
            <div class="mb-4">
                <span style="background:#8b5cf6;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#4 SUBQUERY</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #8b5cf6;">
                    <div style="color:#c4b5fd;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Multi-donation donors</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">SELECT full_name
FROM donor
WHERE donor_id IN (
    SELECT donor_id FROM donation 
    GROUP BY donor_id 
    HAVING COUNT(*) > 1
)</pre>
                </div>
            </div>

            <!-- Query #5: Sort Date Ascending -->
            <div class="mb-4">
                <span style="background:#ec4899;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#5 SORT ASC</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #ec4899;">
                    <div style="color:#f9a8d4;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Date ascending</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">ORDER BY d.donation_time ASC</pre>
                </div>
            </div>

            <!-- Query #6: Sort Date Descending -->
            <div class="mb-4">
                <span style="background:#06b6d4;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#6 SORT DESC</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #06b6d4;">
                    <div style="color:#67e8f9;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Date descending</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">ORDER BY d.donation_time DESC</pre>
                </div>
            </div>

            <!-- Query #7: Sort Volume Ascending -->
            <div class="mb-4">
                <span style="background:#14b8a6;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#7 SORT VOL ASC</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #14b8a6;">
                    <div style="color:#5eead4;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Volume ascending</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">ORDER BY d.volume_ml ASC</pre>
                </div>
            </div>

            <!-- Query #8: Sort Volume Descending -->
            <div class="mb-3">
                <span style="background:#f97316;color:#fff;font-weight:600;font-size:0.85rem;padding:4px 12px;border-radius:6px;display:inline-block;margin-bottom:10px;">#8 SORT VOL DESC</span>
                <div style="background:#232b3a;border-radius:12px;padding:16px;border-left:3px solid #f97316;">
                    <div style="color:#fdba74;font-size:0.9rem;font-weight:600;margin-bottom:8px;">Volume descending</div>
                    <pre style="background:#1a2332;color:#e0e7ef;font-family:'Courier New',monospace;font-size:12px;padding:12px;margin:0;border-radius:8px;overflow-x:auto;line-height:1.5;">ORDER BY d.volume_ml DESC</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Panels HTML as JS templates
const campStatsHTML = `
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <h5 class="fw-semibold mb-0" style="color:#2563eb;">Donations per Camp (GROUP BY)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0" style="background:#f8fafc;">
                <thead class="table-light">
                    <tr>
                        <th>Camp Name</th>
                        <th>Total Donations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($campStats) === 0): ?>
                        <tr><td colspan="2" class="text-center">No data available</td></tr>
                    <?php else: foreach ($campStats as $camp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($camp['title']); ?></td>
                            <td><?php echo $camp['total_donations']; ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>`;

const multiDonorsHTML = `
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <h5 class="fw-semibold mb-0" style="color:#2563eb;">Donors with More Than One Donation (SUBQUERY)</h5>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (count($multiDonors) === 0): ?>
                <li class="list-group-item text-center" style="background:#f8fafc;">No data available</li>
            <?php else: foreach ($multiDonors as $donor): ?>
                <li class="list-group-item" style="background:#f8fafc;"><?php echo htmlspecialchars($donor['full_name']); ?></li>
            <?php endforeach; endif; ?>
        </ul>
    </div>
</div>`;

const mainContentPanel = document.getElementById('mainContentPanel');

document.getElementById('showCampStats').addEventListener('click', function() {
    mainContentPanel.innerHTML = campStatsHTML;
});

document.getElementById('showMultiDonors').addEventListener('click', function() {
    mainContentPanel.innerHTML = multiDonorsHTML;
});

// Sort By Dropdown Functionality
document.addEventListener('DOMContentLoaded', function() {
    var sortByBtn = document.getElementById('sortByBtn');
    var sortMenu = document.getElementById('sortMenu');
    
    if (sortByBtn && sortMenu) {
        sortByBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            sortMenu.style.display = sortMenu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!sortMenu.contains(e.target) && e.target !== sortByBtn) {
                sortMenu.style.display = 'none';
            }
        });
        
        // Handle sort option clicks
        var sortOptions = sortMenu.querySelectorAll('[data-sort]');
        sortOptions.forEach(function(option) {
            option.addEventListener('click', function() {
                var sortType = this.getAttribute('data-sort');
                window.location.href = 'donations.php?sort=' + sortType;
            });
        });
    }
});
</script>

<?php include '_footer.php'; ?>