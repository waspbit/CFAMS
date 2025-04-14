<?php
session_start();
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = strtoupper($_SESSION['role'] ?? 'USER');

// Determine filtering parameters
$tab_type = $_GET['tab_type'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$sort_department = $_GET['sort_department'] ?? '';
$sort_order = $_GET['sort_order'] ?? 'ASC'; // Default sort order

// Fetch available types for tabs
$stmt = $pdo->prepare("SELECT DISTINCT type_name FROM types ORDER BY type_name");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch available departments for sorting
$stmt = $pdo->prepare("SELECT DISTINCT department FROM profiles ORDER BY department");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Define valid months
$valid_months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Fetch accomplishments
$query = "SELECT a.*, p.department FROM accomplishments a LEFT JOIN profiles p ON a.user_id = p.user_id";
$where_conditions = [];
$params = [];

if ($tab_type) {
    $where_conditions[] = "a.type = ?";
    $params[] = $tab_type;
}
if ($filter_month) {
    $where_conditions[] = "a.month = ?";
    $params[] = $filter_month;
}
if ($sort_department) {
    $where_conditions[] = "p.department = ?";
    $params[] = $sort_department;
}
// Only apply user_id filter for non-admins
if ($user_role !== 'ADMIN') {
    $where_conditions[] = "a.user_id = ?";
    $params[] = $user_id;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}
// Updated ORDER BY clause to handle dynamic sorting
$month_order = "FIELD(a.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";
$query .= " ORDER BY p.department $sort_order, $month_order $sort_order, a.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$accomplishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Accomplishments</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f4f4;
        }
        .sidebar {
            height: 100%;
            width: 200px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #111;
            padding-top: 20px;
            color: white;
        }
        .content {
            margin-left: 210px;
            padding: 20px;
        }
        .tabs {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tabs a {
            padding: 10px 20px;
            background-color: #e0e0e0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .tabs a:hover {
            background-color: #d0d0d0;
        }
        .tabs a.active {
            background-color: #4CAF50;
            color: white;
        }
        .sort-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .sort-controls select, .sort-controls button {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .sort-controls button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .sort-controls button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th.sortable {
            cursor: pointer;
            position: relative;
            padding-right: 20px;
        }
        th.sortable::after {
            content: '';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
        }
        th.sortable.asc::after {
            border-bottom-color: #4CAF50;
        }
        th.sortable.desc::after {
            border-top-color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>
        <div class="content">
            <h2>Accomplishments List</h2>

            <div class="tabs">
                <a href="?tab_type=&filter_month=<?php echo urlencode($filter_month); ?>&sort_department=<?php echo urlencode($sort_department); ?>&sort_order=<?php echo $sort_order; ?>" class="<?php echo $tab_type === '' ? 'active' : ''; ?>">All Types</a>
                <?php foreach ($types as $type): ?>
                    <a href="?tab_type=<?php echo urlencode($type); ?>&filter_month=<?php echo urlencode($filter_month); ?>&sort_department=<?php echo urlencode($sort_department); ?>&sort_order=<?php echo $sort_order; ?>" class="<?php echo $tab_type === $type ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($type); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="sort-controls">
                <select id="filterMonth" onchange="updateFilter()">
                    <option value="">All Months</option>
                    <?php foreach ($valid_months as $month): ?>
                        <option value="<?php echo htmlspecialchars($month); ?>" <?php echo $filter_month === $month ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($month); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="sortDepartment" onchange="updateFilter()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?php echo htmlspecialchars($department); ?>" <?php echo $sort_department === $department ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($department); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button onclick="updateFilter()">Apply</button>
            </div>

            <?php if (count($accomplishments) > 0): ?>
                <table>
                    <tr>
                        <th>Title</th>
                        <th>Quantity</th>
                        <th>Quality</th>
                        <th>Timeliness</th>
                        <th>Remarks</th>
                        <th class="sortable <?php echo $sort_order; ?>" onclick="toggleSort('month')">Month</th>
                        <th>Type</th>
                        <th class="sortable <?php echo $sort_order; ?>" onclick="toggleSort('department')">Department</th>
                        
                    </tr>
                    <?php foreach ($accomplishments as $accomplishment): ?>
                        <?php if ($user_role === 'ADMIN' || $accomplishment['user_id'] == $user_id): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($accomplishment['title']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['quality']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['timeliness']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['remarks']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['month']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['type']); ?></td>
                                <td><?php echo htmlspecialchars($accomplishment['department'] ?? 'N/A'); ?></td>
                                
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No accomplishments found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFilter() {
            const month = document.getElementById('filterMonth').value;
            const department = document.getElementById('sortDepartment').value;
            const params = new URLSearchParams(window.location.search);
            params.set('filter_month', month);
            params.set('sort_department', department);
            window.location.search = params.toString();
        }

        function toggleSort(column) {
            const params = new URLSearchParams(window.location.search);
            const currentOrder = params.get('sort_order') || 'ASC';
            const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            params.set('sort_order', newOrder);
            window.location.search = params.toString();
        }
    </script>
</body>
</html>