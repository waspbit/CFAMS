<?php 
include '../login.php';
include '../db.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch all types
$stmt = $pdo->prepare("SELECT type_name FROM types ORDER BY id ASC");
$stmt->execute();
$all_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch accomplishment counts by type for the user
$stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM accomplishments WHERE user_id = ? GROUP BY type");
$stmt->execute([$user_id]);
$type_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize counts for all types (set to 0 if no accomplishments)
$counts_types = array_fill_keys($all_types, 0);
foreach ($type_counts as $row) {
    if (in_array($row['type'], $all_types)) {
        $counts_types[$row['type']] = (int)$row['count'];
    }
}

// Prepare types data for Chart.js
$types_labels = json_encode($all_types);
$types_data = json_encode(array_values($counts_types));

// Fetch distinct remarks values (case-insensitive)
$stmt = $pdo->prepare("SELECT DISTINCT remarks FROM accomplishments WHERE user_id = ? AND remarks IS NOT NULL");
$stmt->execute([$user_id]);
$all_remarks_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Normalize remarks to expected values
$all_remarks = ['Accomplished', 'Continuing'];
$remarks_map = array_combine(
    array_map('strtolower', $all_remarks),
    $all_remarks
);

// Fetch accomplishment counts by remarks for the user
$stmt = $pdo->prepare("SELECT remarks, COUNT(*) as count FROM accomplishments WHERE user_id = ? AND remarks IS NOT NULL GROUP BY remarks");
$stmt->execute([$user_id]);
$remarks_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize counts for all remarks (set to 0 if no accomplishments)
$counts_remarks = array_fill_keys($all_remarks, 0);
foreach ($remarks_counts as $row) {
    $remark = trim($row['remarks']);
    $lower_remark = strtolower($remark);
    if (isset($remarks_map[$lower_remark])) {
        $counts_remarks[$remarks_map[$lower_remark]] = (int)$row['count'];
    }
}

// Debug output (uncomment to check raw data)
// echo "<pre>Raw remarks: " . print_r($all_remarks_raw, true) . "\nCounts: " . print_r($counts_remarks, true) . "</pre>";

 // Prepare remarks data for Chart.js
$remarks_labels = json_encode($all_remarks);
$remarks_data = json_encode(array_values($counts_remarks));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
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
       
        .chart-container {
            max-width: 800px;
            height: 400px;
            margin: 20px auto; /* Centered */
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        canvas {
            width: 100% !important;
            height: 100% !important;
        }
    </style>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h2>Welcome to the Dashboard</h2>
        

        <div class="chart-container">
            <canvas id="typeChart"></canvas>
        </div>

        <div class="chart-container">
            <canvas id="remarksChart"></canvas>
        </div>
    </div>

    <script>
    // Types Chart
    const typeLabels = <?php echo $types_labels; ?>;
    const typeData = <?php echo $types_data; ?>;
    const typeColors = typeLabels.map(() => {
        return `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.6)`;
    });
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: typeLabels,
            datasets: [{
                label: 'Accomplishments',
                data: typeData,
                backgroundColor: typeColors,
                borderColor: typeColors.map(color => color.replace('0.6', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Accomplishments'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Type'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Accomplishments by Type'
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.parsed.y || 0;
                            let type = context.label || '';
                            return `${type}: ${value} ${label}`;
                        }
                    }
                }
            }
        }
    });

    // Remarks Chart
    const remarksLabels = <?php echo $remarks_labels; ?>;
    const remarksData = <?php echo $remarks_data; ?>;
    const remarksColors = ['rgba(76, 175, 80, 0.6)', 'rgba(33, 150, 243, 0.6)']; // Green, Blue
    const remarksCtx = document.getElementById('remarksChart').getContext('2d');
    new Chart(remarksCtx, {
        type: 'bar',
        data: {
            labels: remarksLabels,
            datasets: [{
                label: 'Accomplishments',
                data: remarksData,
                backgroundColor: remarksColors,
                borderColor: remarksColors.map(color => color.replace('0.6', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Accomplishments'
                    },
                    ticks: {
                        stepSize: 1
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Remarks'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Accomplishments by Remarks'
                },
                tooltip: {
                    enabled: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.parsed.y || 0;
                            let remark = context.label || '';
                            return `${remark}: ${value} ${label}`;
                        }
                    }
                }
            }
        }
    });
</script>