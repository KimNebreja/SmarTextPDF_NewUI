<?php
session_start();
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
    <title>SmartTextPDF - Compare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/main.css">
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
                <img src="../assets/SmarText PDF_sidebar-logo.svg" alt="SmarTextPDF Logo" class="sidebar-logo">
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="upload.php">Upload PDF</a></li>
                <li class="active"><a href="comparison.php" aria-current="page">Compare PDFs</a></li>
                <li><a href="#" onclick="logout()" class="logout-link">Logout</a></li>
            </ul>
        </nav>
        
        <main class="main-content">
            <header class="top-bar">
                <h1>Compare PDFs</h1>
                <div class="user-info">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                </div>
            </header>

            <div class="comparison-container">
                <div class="comparison-header">
                    <div class="comparison-title">
                        <h3>Document Comparison</h3>
                        <p class="file-name">sample_document.pdf</p>
                    </div>
                    <div class="comparison-actions">
                        <button class="btn-secondary" id="acceptAllBtn">
                            <span class="button-text">Accept All Changes</span>
                        </button>
                        <button class="btn-primary" id="downloadBtn">
                            <span class="button-text">Download PDF</span>
                        </button>
                    </div>
                </div>

                <div class="comparison-content">
                    <div class="comparison-panel original">
                        <h4>Original Text</h4>
                        <div class="text-content" id="originalText">
                            <p>The <span class="error">quick brown fox jumps</span> over the lazy dog.</p>
                            <p>This is a <span class="error">sample text</span> with some <span class="error">intentional errors</span>.</p>
                            <p>The <span class="error">weather</span> is beautiful today.</p>
                            <p>Please <span class="error">review</span> this document carefully.</p>
                            <p>We need to <span class="error">meet</span> tomorrow to discuss the project.</p>
                        </div>
                    </div>

                    <div class="comparison-panel revised">
                        <h4>Revised Text</h4>
                        <div class="text-content" id="revisedText">
                            <p>The <span class="suggestion">swift brown fox leaps</span> over the lazy dog.</p>
                            <p>This is a <span class="suggestion">demonstration text</span> with some <span class="suggestion">deliberate mistakes</span>.</p>
                            <p>The <span class="suggestion">climate</span> is beautiful today.</p>
                            <p>Please <span class="suggestion">examine</span> this document carefully.</p>
                            <p>We need to <span class="suggestion">convene</span> tomorrow to discuss the project.</p>
                        </div>
                    </div>
                </div>

                <div class="tts-controls">
                    <div class="tts-header">
                        <h4>Text-to-Speech</h4>
                        <div class="tts-actions">
                            <button class="btn-secondary" id="ttsPlayBtn" aria-label="Play text">
                                <span class="button-text">Play</span>
                            </button>
                            <button class="btn-secondary" id="ttsPauseBtn" disabled aria-label="Pause text">
                                <span class="button-text">Pause</span>
                            </button>
                            <button class="btn-secondary" id="ttsStopBtn" disabled aria-label="Stop text">
                                <span class="button-text">Stop</span>
                            </button>
                        </div>
                    </div>
                    <div class="tts-settings">
                        <div class="speed-control">
                            <label for="ttsSpeed">Speed:</label>
                            <input type="range" id="ttsSpeed" min="0.5" max="2" step="0.1" value="1" aria-label="Speech speed">
                            <span id="speedValue">1x</span>
                        </div>
                        <div class="voice-select">
                            <label for="ttsVoice">Voice:</label>
                            <select id="ttsVoice" aria-label="Select voice"></select>
                        </div>
                    </div>
                    <div class="tts-progress">
                        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress" id="ttsProgress"></div>
                        </div>
                        <div class="time-display">
                            <span id="currentTime">0:00</span> / <span id="totalTime">0:00</span>
                        </div>
                    </div>
                </div>

                <div class="comparison-legend">
                    <div class="legend-item">
                        <span class="legend-color error"></span>
                        <span>Error</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color suggestion"></span>
                        <span>Suggestion</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color final"></span>
                        <span>Accepted</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
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
    </script>
    <script src="../scripts/auth.js"></script>
    <script src="../scripts/comparison.js"></script>
    <script src="../scripts/tts.js"></script>
</body>
</html> 