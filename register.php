<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $role = $_POST['role'];
    $department = $_POST['department'];

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match!";
        header("Location: register.php");
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Email already registered.";
        header("Location: register.php");
        exit;
    }

    // Insert new user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role, department) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $hashedPassword, strtoupper($role), $department]);

    $_SESSION['success'] = "Registered successfully! You can now login.";
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register</title>
    <link href="css/style.css" rel="stylesheet" />
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/responsive.css" rel="stylesheet" />

    <style>
        body{
            margin-top: 50px;
            margin-bottom: 50px;
            margin-left: 10%;
            margin-right: 10%;
            height: 800px;
            width: 80%;
            background-color: whitesmoke;
            border-radius: 20px;
        }
        div.inner_panel_login{
            height: 90%;
            width: 60%;
            margin-left: 20%;
            margin-right: 20%;
            background-color: white;
            border-radius: 20px;
        }
        div.form-group{
            margin-top: 5%;
            margin-left: 10%;
            margin-right: 10%;
        }
        button.btn{
            background-color: #ADD8E6;
            margin-left: 10%;
        }

    </style>
</head>

<body>
    <div class="outer_panel_login">
        <div style="height:5%"></div>
        <div class="inner_panel_login">
            <div class="heading_container" style="margin-left: 10%;">
                <h2 style=" margin-top: 5%;">Register</h2>
            </div>
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>Email: <br></label>
                    <input type="email" class="form-control" name="email" placeholder="Enter Your Email" required>
                </div>
                <div class="form-group">
                    <label>Password: <br></label>
                    <input type="password" class="form-control" name="password" placeholder="Enter Your Password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password: <br></label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Verify Your Password" required>
                </div>
                <div class="form-group">
                    <label>Department: <br></label>
                    <input type="text" class="form-control" name="department" placeholder="Enter Your Department" required>
                </div>
                <div class="form-group">
      				<label>Role: </label>
					  <select name="role" class="form-control" placeholder="Enter Your Role" required>
                        <option value="user">USER</option>
                        <option value="admin">ADMIN</option>
					  </select>
      			</div>

                
                <p><button type="submit" class="btn">Register</button><a href="index.php" style="margin-left: 10%; margin-top: 5%;">Back to Login</a></p>
            </form>

            <?php if (isset($_SESSION['error'])): ?>
                <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <p style="color:green;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            <?php endif; ?>
            
        </div>
    </div>

</body>
</html>
