<?php
require_once 'db_connect.php';

// Handle Insert
if (isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO allocation (request_id, unit_id, allocated_at) VALUES (?, ?, NOW())");
    $stmt->execute([$_POST['request_id'], $_POST['unit_id']]);
}
// Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM allocation WHERE allocation_id=?");
    $stmt->execute([$_POST['allocation_id']]);
}
// List all with join
$stmt = $pdo->query("SELECT a.*, r.hospital_name, b.status as unit_status FROM allocation a JOIN blood_request r ON a.request_id = r.request_id JOIN blood_unit b ON a.unit_id = b.unit_id ORDER BY a.allocated_at DESC");
$allocations = $stmt->fetchAll();
// Aggregate: Count
$count = $pdo->query("SELECT COUNT(*) FROM allocation")->fetchColumn();
?>
<!-- Use $allocations and $count in your HTML -->

 