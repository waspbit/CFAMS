<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <link href="css/style.css" rel="stylesheet" />
    <link href="css/bootstrap.css" rel="stylesheet" />
    <link href="css/responsive.css" rel="stylesheet" />

    <style>
        body{
            margin-top: 50px;
            margin-bottom: 50px;
            margin-left: 10%;
            margin-right: 10%;
            height: 500px;
            width: 80%;
            background-color: whitesmoke;
            border-radius: 20px;
        }
        div.inner_panel_login{
            height: 80%;
            width: 40%;
            margin-left: 30%;
            margin-right: 30%;
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
        <div style="height:10%"></div>
        <div class="inner_panel_login">

            <div class="heading_container" style="margin-left: 10%;">
                <h2 style=" margin-top: 5%;">
                Login
                </h2>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Email: <br></label>
                    <input type="email" class="form-control" name="email" placeholder="Enter Your Email" required>
                </div>
                <div class="form-group">
                    <label>Password: <br></label>
                    <input type="password" class="form-control" name="password" placeholder="Enter Your Password" required>
                </div>
                <button type="submit" class="btn" name="btn_submit">Login</button>

                <p style="margin-left: 10%; margin-top: 5%;">Don't have an account? <a href="register.php">Register here</a></p>
            </form>

            <?php if (isset($_SESSION['error'])): ?>
                <p style="color:red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>
        </div>
    </div>
    </body>

</html>
