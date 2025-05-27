<?php
session_start();

// Temporary debugging line
error_log("Dashboard Session Check: user_id = " . ($_SESSION['user_id'] ?? 'NOT SET'));

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmarTextPDF - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <img src="../assets/SmarText PDF_sidebar-logo.svg" alt="SmarTextPDF Logo" class="sidebar-logo">
            </div>
            <ul class="nav-links">
                <li class="active"><a href="dashboard.php" aria-current="page">Dashboard</a></li>
                <li><a href="upload.php">Upload PDF</a></li>
                <li><a href="comparison.php">Compare PDFs</a></li>
                <li><a href="#" onclick="logout()" class="logout-link">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header class="top-bar">
                <div class="header-left">
                    <h1>Dashboard</h1>
                </div>
                <div class="user-info">
                    <span class="user-name">Welcome, <span id="userName"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span></span>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="card-content">
                        <h3>Total Processed</h3>
                        <div class="stat-value" id="totalProcessed">0</div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-calendar-day"></i></div>
                    <div class="card-content">
                        <h3>Today's Processed</h3>
                        <div class="stat-value" id="todayProcessed">0</div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="card-content">
                        <h3>Processing Rate</h3>
                        <div class="stat-value" id="processingRate">0%</div>
                    </div>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                    <div class="card-content">
                        <h3>Average Time</h3>
                        <div class="stat-value" id="avgTime">0s</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent PDFs</h2>
                    <a href="upload.php" class="btn-primary">Upload New</a>
                </div>
                <div class="recent-files">
                    <table class="files-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentFilesList">
                            <!-- Files will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../scripts/auth.js"></script>
    <script>
        // Load dashboard statistics
        function loadDashboardStats() {
            fetch('../api/get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalProcessed').textContent = data.stats.total_processed;
                        document.getElementById('todayProcessed').textContent = data.stats.today_processed;
                        document.getElementById('processingRate').textContent = data.stats.processing_rate + '%';
                        document.getElementById('avgTime').textContent = data.stats.avg_time + 's';
                    }
                })
                .catch(error => console.error('Error loading stats:', error));
        }

        // Load recent files
        function loadRecentFiles() {
            fetch('../api/get_recent_files.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('recentFilesList');
                        tbody.innerHTML = data.files.map(file => `
                            <tr>
                                <td>${file.custom_name || file.original_filename}</td>
                                <td>${new Date(file.upload_date).toLocaleString()}</td>
                                <td><span class="status-badge ${file.status}">${file.status}</span></td>
                                <td>
                                    ${file.status === 'completed' ? `
                                        <button class="btn-icon" onclick="viewFile(${file.upload_id})" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon" onclick="downloadFile(${file.upload_id})" title="Download">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    ` : ''}
                                    <button class="btn-icon" onclick="deleteFile(${file.upload_id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    }
                })
                .catch(error => console.error('Error loading files:', error));
        }

        // Logout function
        function logout() {
            fetch('../api/logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../index.php';
                    }
                })
                .catch(error => console.error('Error logging out:', error));
        }

        // File actions
        function viewFile(uploadId) {
            window.location.href = `view.php?id=${uploadId}`;
        }

        function downloadFile(uploadId) {
            window.location.href = `../api/download.php?id=${uploadId}`;
        }

        function deleteFile(uploadId) {
            if (confirm('Are you sure you want to delete this file?')) {
                fetch('../api/delete_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ upload_id: uploadId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadRecentFiles();
                    } else {
                        alert(data.message || 'Failed to delete file');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete file');
                });
            }
        }

        // Load data on page load
        loadDashboardStats();
        loadRecentFiles();

        // Refresh data every 30 seconds
        setInterval(() => {
            loadDashboardStats();
            loadRecentFiles();
        }, 30000);
    </script>
</body>
</html> 