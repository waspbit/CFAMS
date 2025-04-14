<?php
session_start();
include '../db.php';

// Check if user is logged in and has USER role
if (!isset($_SESSION['id']) || (isset($_SESSION['role']) && strtoupper($_SESSION['role']) !== 'ADMIN')) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id'];
$error = '';
$profile = null;

try {
    // Fetch the user's profile
    $sql = "SELECT firstname, middlename, lastname, department 
            FROM profiles 
            WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        $error = "No profile found. Please create a profile first.";
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>View Profile</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    <div class="container">
        <h2>My Profile</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($profile): ?>
            <table>
                <tr>
                    <th>First Name</th>
                    <td><?php echo htmlspecialchars($profile['firstname']); ?></td>
                </tr>
                <tr>
                    <th>Middle Name</th>
                    <td><?php echo htmlspecialchars($profile['middlename'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Last Name</th>
                    <td><?php echo htmlspecialchars($profile['lastname']); ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php echo htmlspecialchars($profile['department']); ?></td>
                </tr>
            </table>
        <?php endif; ?>

    </div>
</body>
</html>