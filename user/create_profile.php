<?php
session_start();
include '../db.php';

// Check if user is logged in and has USER role
if (!isset($_SESSION['id']) || (isset($_SESSION['role']) && strtoupper($_SESSION['role']) !== 'USER')) {
    echo 'Debug: Redirecting to index.php due to missing session ID or incorrect role';
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['id'];
$error = '';
$success = '';

try {
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Debug POST data
        echo '<pre>POST Data: ';
        print_r($_POST);
        echo '</pre>';

        $firstname = trim($_POST['firstname'] ?? '');
        $middlename = trim($_POST['middlename'] ?? '') ?: null;
        $lastname = trim($_POST['lastname'] ?? '');
        $department = trim($_POST['department'] ?? '');

        // Validate input
        if (empty($firstname) || empty($lastname) || empty($department)) {
            throw new Exception("Please fill in all required fields");
        }

        // Check if profile already exists
        $check_sql = "SELECT COUNT(*) FROM profiles WHERE user_id = :user_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute(['user_id' => $user_id]);

        if ($check_stmt->fetchColumn() > 0) {
            throw new Exception("A profile already exists for this user");
        }

        // Insert new profile
        $sql = "INSERT INTO profiles (user_id, firstname, middlename, lastname, department) 
                VALUES (:user_id, :firstname, :middlename, :lastname, :department)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'firstname' => $firstname,
            'middlename' => $middlename,
            'lastname' => $lastname,
            'department' => $department
        ]);

        $success = "Profile created successfully!";
        $_POST = array();
        
        header("Location: ../user/dashboard.php");
        exit();
    }
} catch(PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch(Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE HTML Lords Prayer HTML>
<html>
<head>
    <title>Create Profile</title>
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
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffe6e6;
            border-radius: 4px;
        }
        .success {
            color: green;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #e6ffe6;
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
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    <div class="form-container">
        <h2>Create Profile</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="firstname" class="required">First Name</label>
                <input type="text" name="firstname" id="firstname" required
                       value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="middlename">Middle Name</label>
                <input type="text" name="middlename" id="middlename"
                       value="<?php echo isset($_POST['middlename']) ? htmlspecialchars($_POST['middlename']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="lastname" class="required">Last Name</label>
                <input type="text" name="lastname" id="lastname" required
                       value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="department" class="required">Department</label>
                <select name="department" id="department" required>
                    <option value="">Select Department</option>
                    <option value="IT" <?php echo (isset($_POST['department']) && $_POST['department'] == 'IT') ? 'selected' : ''; ?>>IT</option>
                    <option value="HR" <?php echo (isset($_POST['department']) && $_POST['department'] == 'HR') ? 'selected' : ''; ?>>HR</option>
                    <option value="Finance" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Finance') ? 'selected' : ''; ?>>Finance</option>
                    <option value="Marketing" <?php echo (isset($_POST['department']) && $_POST['department'] == 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                </select>
            </div>

            <button type="submit">Create Profile</button>
        </form>

        <a href="../user/dashboard.php" class="back-link">Back to Dashboard</a>
    </div>
</body>
</html>