<?php
include '../login.php';
include '../db.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Save accomplishments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titles = $_POST['title'];
    $quantities = $_POST['quantity'];
    $qualities = $_POST['quality'];
    $timelinesses = $_POST['timeliness'];
    $remarks = $_POST['remarks'];
    $months = $_POST['month'];
    $type = strtoupper(trim($_POST['selected_type']));

    try {
        foreach ($titles as $index => $title) {
            if (empty($title)) continue;

            $stmt = $pdo->prepare("INSERT INTO accomplishments (title, quantity, quality, timeliness, remarks, month, type, user_id)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $quantities[$index],
                $qualities[$index],
                $timelinesses[$index],
                $remarks[$index],
                $months[$index],
                $type,
                $user_id
            ]);
        }

        $_SESSION['success'] = "Accomplishments saved successfully.";
        header("Location: view_accomplishments.php");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error saving accomplishments: " . $e->getMessage();
        header("Location: add_accomplishment.php");
        exit;
    }
}

// Get all types with IDs, sorted by id
$types = $pdo->query("SELECT id, type_name FROM types ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Accomplishment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
        }
        .content {
            margin-left: 210px;
            padding: 20px;
        }
        .tabs {
            margin-bottom: 10px;
            width: 90%;
            margin-left: auto;
            margin-right: auto;
            display: flex;
        }
        .tabs button {
            background-color: #e0e0e0;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 12px 24px;
            transition: all 0.3s ease;
            margin-right: 5px;
            font-size: 16px;
            border-radius: 5px 5px 0 0;
            flex: 1;
            text-align: center;
        }
        .tabs button:hover {
            background-color: #b3e5fc;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 1px solid #0288d1;
        }
        .tabs button.active {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
            border-bottom: 3px solid #388E3C;
        }
        .tabcontent {
            display: none;
            padding: 20px;
            border: 1px solid #ccc;
            border-top: none;
            background: #fff;
        }
        .tabcontent.active {
            display: block;
        }
        .form-container {
            width: 90%;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            margin: 10px 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
        }
        button[type="button"] {
            background-color: #2196F3;
            color: white;
        }
        button:hover {
            opacity: 0.9;
        }
        .error { color: red; margin-bottom: 10px; }
        .success { color: green; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h2>Add Accomplishment</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <p class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        <?php endif; ?>

        <div class="tabs">
            <?php foreach ($types as $index => $type): ?>
                <button type="button" class="tab-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                        onclick="openTab(event, 'tab-<?php echo $type['id']; ?>')">
                    <?php echo htmlspecialchars($type['type_name']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="form-container">
            <?php foreach ($types as $index => $type): ?>
                <div id="tab-<?php echo $type['id']; ?>" class="tabcontent <?php echo $index === 0 ? 'active' : ''; ?>">
                    <form method="POST">
                        <input type="hidden" name="selected_type" value="<?php echo htmlspecialchars($type['type_name']); ?>">
                        <table id="table-<?php echo $type['id']; ?>">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Quantity</th>
                                    <th>Quality</th>
                                    <th>Timeliness</th>
                                    <th>Remarks</th>
                                    <th>Month</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="title[]" required></td>
                                    <td><input type="text" name="quantity[]" required></td>
                                    <td><input type="text" name="quality[]" required></td>
                                    <td><input type="text" name="timeliness[]" required></td>
                                    <td>
                                        <select name="remarks[]" required>
                                            <option value="Accomplished">Accomplished</option>
                                            <option value="Continuing">Continuing</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="month[]" value="<?php echo date('F'); ?>" readonly></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" onclick="addRow('table-<?php echo $type['id']; ?>')">Add Another</button>
                        <button type="submit">Save</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tabcontent");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].className = tabcontent[i].className.replace(" active", "");
            }
            tablinks = document.getElementsByClassName("tab-link");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).className += " active";
            evt.currentTarget.className += " active";
        }

        function addRow(tableId) {
            var table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
            var newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="title[]" required></td>
                <td><input type="text" name="quantity[]" required></td>
                <td><input type="text" name="quality[]" required></td>
                <td><input type="text" name="timeliness[]" required></td>
                <td>
                    <select name="remarks[]" required>
                        <option value="Accomplished">Accomplished</option>
                        <option value="Continuing">Continuing</option>
                    </select>
                </td>
                <td><input type="text" name="month[]" value="<?php echo date('F'); ?>" readonly></td>
            `;
        }

        // Open first tab by default
        <?php if (!empty($types)): ?>
            document.getElementById('tab-<?php echo $types[0]['id']; ?>').className += ' active';
        <?php endif; ?>
    </script>
</body>
</html>