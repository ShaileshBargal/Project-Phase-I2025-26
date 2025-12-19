<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['head_id']) || ($_SESSION['role'] ?? '') !== 'head') {
    header("Location: incharge_login.php");
    exit;
}

$username = $_SESSION['username'] ?? "Head";

/* ---------------------------
   FETCH SUMMARY COUNTS
--------------------------- */

// Total roles (distinct incharge roles assigned)
$total_roles = $conn->query("SELECT COUNT(DISTINCT incharge_role) AS c FROM users WHERE role='incharge'")->fetch_assoc()['c'];

// Total incharges
$total_incharges = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='incharge'")->fetch_assoc()['c'];

// Total reports
$total_reports = $conn->query("SELECT COUNT(*) AS c FROM reports")->fetch_assoc()['c'];

// Pending / Approved / Rejected
$pending = $conn->query("SELECT COUNT(*) AS c FROM reports WHERE status='pending'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) AS c FROM reports WHERE status='approved'")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) AS c FROM reports WHERE status='rejected'")->fetch_assoc()['c'];

/* ---------------------------
   FETCH INCHARGE → ROLE LIST
--------------------------- */

// Fetch all incharges and their assigned roles
$q = $conn->query("
    SELECT incharge_role AS role_name, username
    FROM users
    WHERE role='incharge'
    ORDER BY incharge_role ASC, username ASC
");

$groups = [];
while ($row = $q->fetch_assoc()) {
    $groups[$row['role_name']][] = $row['username'];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Mini Summary Cards */
.summary-row {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.summary-card {
    background: white;
    padding: 12px 18px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    font-size: 14px;
    font-weight: 600;
    color: #1e3a8a;
    border-left: 5px solid #2563eb;
    display: flex;
    align-items: center;
    gap: 8px;
}

.summary-card i {
    color: #2563eb;
    font-size: 15px;
}

/* MAIN SECTION */
.incharge-section {
    margin-top: 25px;
}

.incharge-title {
    font-size: 22px;
    font-weight: 600;
    color: #1e3a8a;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.incharge-title i {
    color: #2563eb;
    font-size: 20px;
}

/* SEARCH BAR */
.incharge-search {
    margin-bottom: 15px;
}

.incharge-search input {
    width: 320px;
    padding: 11px 15px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #f1f5f9;
    font-size: 15px;
}

/* GRID */
.incharge-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
    gap: 22px;
}

/* CARD STYLE */
.incharge-card {
    background: #ffffff;
    padding: 20px 22px;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(0,0,0,0.08);
    border-left: 6px solid #2563eb;
    transition: 0.25s ease;
}

.incharge-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}

/* CARD HEADER */
.role-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
}

.role-name {
    font-size: 18px;
    font-weight: 600;
    color: #1e40af;
    display: flex;
    align-items: center;
    gap: 8px;
}

.role-name i {
    color: #2563eb;
}

.count-badge {
    background: #2563eb;
    color: white;
    padding: 4px 10px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
}

/* PERSON LIST */
.person-list {
    margin-top: 12px;
    display: none;
}

.person {
    font-size: 15px;
    padding: 7px 0;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.person:last-child {
    border-bottom: none;
}

.person i {
    color: #475569;
}
</style>

<!-- SUMMARY SECTION -->
<div class="summary-row">
    <div class="summary-card"><i class="fa-solid fa-layer-group"></i> Roles: <?= $total_roles ?></div>
    <div class="summary-card"><i class="fa-solid fa-user-tie"></i> Incharges: <?= $total_incharges ?></div>
    <div class="summary-card"><i class="fa-solid fa-file-alt"></i> Total Reports: <?= $total_reports ?></div>
    <div class="summary-card"><i class="fa-solid fa-hourglass-half"></i> Pending: <?= $pending ?></div>
    <div class="summary-card"><i class="fa-solid fa-circle-check"></i> Approved: <?= $approved ?></div>
    <div class="summary-card"><i class="fa-solid fa-circle-xmark"></i> Rejected: <?= $rejected ?></div>
</div>

<!-- INCHARGE OVERVIEW -->
<div class="incharge-section">
    <div class="incharge-title">
        <i class="fa-solid fa-users"></i>
        Incharge Overview
    </div>

    <div class="incharge-search">
        <input type="text" id="inchargeSearch" placeholder="Search roles or incharges...">
    </div>

    <div class="incharge-grid" id="inchargeGrid">
        <?php if (empty($groups)): ?>
            <div>No incharge-role data available.</div>
        <?php else: ?>
            <?php foreach ($groups as $role => $names): ?>
                <div class="incharge-card" data-role="<?= strtolower($role) ?>"
                     data-names="<?= strtolower(implode(" ", $names)) ?>">

                    <div class="role-header" onclick="toggleRole(this)">
                        <div class="role-name">
                            <i class="fa-solid fa-user-shield"></i>
                            <?= htmlspecialchars($role) ?>
                        </div>
                        <div class="count-badge"><?= count($names) ?></div>
                    </div>

                    <div class="person-list">
                        <?php foreach ($names as $name): ?>
                            <div class="person">
                                <i class="fa-solid fa-user-circle"></i>
                                <?= htmlspecialchars($name) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Expand / Collapse
function toggleRole(el) {
    let list = el.nextElementSibling;
    list.style.display = (list.style.display === "block") ? "none" : "block";
}

// Live Search
document.getElementById("inchargeSearch").addEventListener("keyup", function () {
    let value = this.value.toLowerCase();
    document.querySelectorAll(".incharge-card").forEach(card => {
        let role = card.dataset.role;
        let names = card.dataset.names;
        card.style.display = (role.includes(value) || names.includes(value)) ? "" : "none";
    });
});
</script>
