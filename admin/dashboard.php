<?php 
include '../login.php';
include '../db.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = strtoupper($_SESSION['role'] ?? 'ADMIN');

// Fetch all types
$stmt = $pdo->prepare("SELECT type_name FROM types ORDER BY id ASC");
$stmt->execute();
$all_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch accomplishment counts by type (for all users if admin, else for current user)
$where_clause = $user_role === 'ADMIN' ? '' : 'WHERE user_id = ?';
$params = $user_role === 'ADMIN' ? [] : [$user_id];
$stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM accomplishments $where_clause GROUP BY type");
$stmt->execute($params);
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

// Fetch accomplishment counts by department and type for admin
if ($user_role === 'ADMIN') {
    $stmt = $pdo->prepare("
        SELECT p.department, a.type, COUNT(*) as count 
        FROM accomplishments a 
        LEFT JOIN profiles p ON a.user_id = p.user_id 
        GROUP BY p.department, a.type
    ");
    $stmt->execute();
    $dept_type_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total accomplishments per department
    $stmt = $pdo->prepare("
        SELECT p.department, COUNT(*) as total 
        FROM accomplishments a 
        LEFT JOIN profiles p ON a.user_id = p.user_id 
        GROUP BY p.department
    ");
    $stmt->execute();
    $dept_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare department percentage data
    $departments = array_unique(array_column($dept_type_counts, 'department'));
    $dept_percent_data = [];
    foreach ($departments as $dept) {
        if ($dept) { // Skip null departments
            $dept_percent_data[$dept] = array_fill_keys($all_types, 0);
        }
    }

    // Calculate percentages
    foreach ($dept_type_counts as $row) {
        if ($row['department']) {
            $dept = $row['department'];
            $type = $row['type'];
            $count = (int)$row['count'];
            // Find total for this department
            $total = array_reduce($dept_totals, function($carry, $item) use ($dept) {
                return $item['department'] === $dept ? $item['total'] : $carry;
            }, 0);
            if ($total > 0) {
                $dept_percent_data[$dept][$type] = round(($count / $total) * 100, 1);
            }
        }
    }

    // Prepare data for Chart.js
    $dept_labels = json_encode(array_keys($dept_percent_data));
    $dept_datasets = [];
    foreach ($all_types as $type) {
        $data = array_map(function($dept) use ($dept_percent_data, $type) {
            return $dept_percent_data[$dept][$type] ?? 0;
        }, array_keys($dept_percent_data));
        $dept_datasets[] = [
            'label' => $type,
            'data' => $data,
            'backgroundColor' => 'rgba(' . rand(0,255) . ',' . rand(0,255) . ',' . rand(0,255) . ',0.6)',
            'borderColor' => 'rgba(' . rand(0,255) . ',' . rand(0,255) . ',' . rand(0,255) . ',1)',
            'borderWidth' => 1
        ];
    }
    $dept_datasets_json = json_encode($dept_datasets);
}

// Fetch distinct remarks values
$stmt = $pdo->prepare("SELECT DISTINCT remarks FROM accomplishments WHERE user_id = ? AND remarks IS NOT NULL");
$stmt->execute([$user_id]);
$all_remarks_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Normalize remarks to expected values
$all_remarks = ['Accomplished', 'Continuing'];
$remarks_map = array_combine(
    array_map('strtolower', $all_remarks),
    $all_remarks
);

// Fetch accomplishment counts by remarks
$stmt = $pdo->prepare("SELECT remarks, COUNT(*) as count FROM accomplishments WHERE user_id = ? AND remarks IS NOT NULL GROUP BY remarks");
$stmt->execute([$user_id]);
$remarks_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize counts for all remarks
$counts_remarks = array_fill_keys($all_remarks, 0);
foreach ($remarks_counts as $row) {
    $remark = trim($row['remarks']);
    $lower_remark = strtolower($remark);
    if (isset($remarks_map[$lower_remark])) {
        $counts_remarks[$remarks_map[$lower_remark]] = (int)$row['count'];
    }
}

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
            margin: 20px auto;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <h2>Welcome to the Dashboard</h2>

        <div class="chart-container">
            <canvas id="typeChart"></canvas>
        </div>

        <?php if ($user_role === 'ADMIN'): ?>
            <div class="chart-container">
                <canvas id="deptPercentChart"></canvas>
            </div>
        <?php endif; ?>

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
                    }
                }
            }
        });

        <?php if ($user_role === 'ADMIN'): ?>
        // Department Percentage Chart
        const deptLabels = <?php echo $dept_labels; ?>;
        const deptDatasets = <?php echo $dept_datasets_json; ?>;
        const deptCtx = document.getElementById('deptPercentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: deptDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Percentage of Accomplishments (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Department'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Percentage of Accomplishments by Department and Type'
                    }
                }
            }
        });
        <?php endif; ?>

        // Remarks Chart
        const remarksLabels = <?php echo $remarks_labels; ?>;
        const remarksData = <?php echo $remarks_data; ?>;
        const remarksColors = ['rgba(76, 175, 80, 0.6)', 'rgba(33, 150, 243, 0.6)'];
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
                    }
                }
            }
        });
    </script>
</body>
</html>