<!-- sidebar.php -->
<style>
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
        text-align: left;
        font-size: 15px;
        color: white;
        display: block;
        margin: 5px 0;
    }
    .sidebar a:hover {
        background-color: #575757;
    }
</style>

<div class="sidebar">
    <h2 background-color="#111">Menu</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="create_profile.php">profile</a>
    <a href="view_profiles.php">Profile</a>
    <a href="summary.php">Summary Accomplishments</a>
    <a href="add_accomplishment.php">Add Accomplishment</a>
    <a href="view_accomplishments.php">View Accomplishments</a>
    <a href="../logout.php" onclick="return confirm('Are you sure you want to log out?');">Logout</a>
</div>
