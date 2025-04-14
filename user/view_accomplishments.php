<?php
include '../login.php';
include '../db.php';

// Check if the user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $accomplishment_id = $_POST['edit_id'];
    $stmt = $pdo->prepare("SELECT user_id FROM accomplishments WHERE id = ?");
    $stmt->execute([$accomplishment_id]);
    $accomplishment = $stmt->fetch();

    if ($accomplishment && $accomplishment['user_id'] == $user_id) {
        $title = $_POST['title'];
        $quantity = $_POST['quantity'];
        $quality = $_POST['quality'];
        $timeliness = $_POST['timeliness'];
        $remarks = $_POST['remarks'];
        $month = $_POST['month'];
        $type = strtoupper(trim($_POST['type']));

        $stmt = $pdo->prepare("SELECT * FROM types WHERE type_name = ?");
        $stmt->execute([$type]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO types (type_name) VALUES (?)");
            $stmt->execute([$type]);
        }

        $stmt = $pdo->prepare("UPDATE accomplishments SET title = ?, quantity = ?, quality = ?, timeliness = ?, remarks = ?, month = ?, type = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $quantity, $quality, $timeliness, $remarks, $month, $type, $accomplishment_id, $user_id]);
        $_SESSION['success'] = "Accomplishment updated successfully.";
    } else {
        $_SESSION['error'] = "You are not authorized to edit this accomplishment.";
    }
    header("Location: view_accomplishments.php");
    exit();
}

// Determine filtering parameters
$filter_type = $_GET['filter_type'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';

// Fetch available types for filter dropdown
$stmt = $pdo->prepare("SELECT DISTINCT type_name FROM types ORDER BY type_name");
$stmt->execute();
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Define valid months
$valid_months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// Fetch accomplishments
$query = "SELECT a.* FROM accomplishments a";
$where_conditions = [];
$params = [];

if ($filter_type) {
    $where_conditions[] = "a.type = ?";
    $params[] = $filter_type;
}
if ($filter_month) {
    $where_conditions[] = "a.month = ?";
    $params[] = $filter_month;
}
$where_conditions[] = "a.user_id = ?";
$params[] = $user_id;

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}
$query .= " ORDER BY FIELD(a.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), a.id DESC";

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
            text-align: center;
        }
        .sidebar a {
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
            margin: 5px 0;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .content {
            margin-left: 210px;
            padding: 20px;
        }
        h2 {
            color: #333;
        }
        .sort-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
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
        th {
            background-color: #D1E8FF; /* Light blue background */
            color: #004080; /* Dark blue text */
            font-weight: bold;
            cursor: pointer;
        }
        th:hover {
            background-color: #B3D4FF; /* Darker blue on hover */
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
            font-size: 14px;
        }
        .actions a.edit {
            color: #2196F3;
        }
        .actions a:hover {
            text-decoration: underline;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }
        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
        .close:hover {
            color: #f44336;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #4CAF50;
        }
        .modal-content label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .modal-content button {
            padding: 10px 20px;
            margin: 10px 5px 0 0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-content button[type="submit"] {
            background-color: #4CAF50;
            color: white;
        }
        .modal-content button[type="button"] {
            background-color: #f44336;
            color: white;
        }
        .modal-content button:hover {
            opacity: 0.9;
        }
        .error {
            color: #f44336;
            margin-bottom: 10px;
        }
        .success {
            color: #4CAF50;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h2>Accomplishments List</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <div class="sort-controls">
            <select id="filterType" onchange="updateFilter()">
                <option value="">All Types</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="filterMonth" onchange="updateFilter()">
                <option value="">All Months</option>
                <?php foreach ($valid_months as $month): ?>
                    <option value="<?php echo htmlspecialchars($month); ?>" <?php echo $filter_month === $month ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($month); ?>
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
                    <th>Month</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($accomplishments as $accomplishment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($accomplishment['title']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['quality']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['timeliness']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['remarks']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['month']); ?></td>
                        <td><?php echo htmlspecialchars($accomplishment['type']); ?></td>
                        <td class="actions">
                            <a href="#" class="edit" onclick='openEditModal(<?php echo json_encode($accomplishment); ?>)'>Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No accomplishments found.</p>
        <?php endif; ?>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">×</span>
                <h2>Edit Accomplishment</h2>
                <form method="POST" id="editForm">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <label>Title:</label>
                    <input type="text" name="title" id="edit_title" required>
                    <label>Quantity:</label>
                    <input type="text" name="quantity" id="edit_quantity" required>
                    <label>Quality:</label>
                    <input type="text" name="quality" id="edit_quality" required>
                    <label>Timeliness:</label>
                    <input type="text" name="timeliness" id="edit_timeliness" required>
                    <label>Remarks:</label>
                    <select name="remarks" id="edit_remarks" required>
                        <option value="Accomplished">Accomplished</option>
                        <option value="Continuing">Continuing</option>
                    </select>
                    <label>Month:</label>
                    <select name="month" id="edit_month" required>
                        <?php foreach ($valid_months as $month): ?>
                            <option value="<?php echo htmlspecialchars($month); ?>">
                                <?php echo htmlspecialchars($month); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Type:</label>
                    <input type="text" name="type" id="edit_type" required>
                    <button type="submit">Save</button>
                    <button type="button" onclick="closeEditModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(accomplishment) {
            document.getElementById('edit_id').value = accomplishment.id;
            document.getElementById('edit_title').value = accomplishment.title;
            document.getElementById('edit_quantity').value = accomplishment.quantity;
            document.getElementById('edit_quality').value = accomplishment.quality;
            document.getElementById('edit_timeliness').value = accomplishment.timeliness;
            document.getElementById('edit_remarks').value = accomplishment.remarks;
            document.getElementById('edit_month').value = accomplishment.month;
            document.getElementById('edit_type').value = accomplishment.type;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function updateFilter() {
            const filterType = document.getElementById('filterType').value;
            const filterMonth = document.getElementById('filterMonth').value;
            let url = `view_accomplishments.php`;
            let params = [];
            if (filterType) {
                params.push(`filter_type=${encodeURIComponent(filterType)}`);
            }
            if (filterMonth) {
                params.push(`filter_month=${encodeURIComponent(filterMonth)}`);
            }
            if (params.length > 0) {
                url += `?${params.join('&')}`;
            }
            window.location.href = url;
        }

        window.onclick = function(event) {
            var modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        };
    </script>
</body>
</html>