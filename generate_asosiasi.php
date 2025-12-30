<?php
require_once 'config/database.php';
require_once 'config/auth.php';

redirectIfNotLoggedIn();
redirectIfNotAdmin();

$database = new Database();
$db = $database->getConnection();

// Fungsi untuk cek Python di Windows
function checkPythonWindows() {
    $commands = [
        'python --version',
        'python3 --version',
        'py --version',
        'where python',
        'where python3'
    ];
    
    foreach ($commands as $cmd) {
        $output = [];
        $returnCode = 0;
        @exec($cmd . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            // Ekstrak path dari output 'where'
            if (strpos($cmd, 'where') === 0 && !empty($output)) {
                foreach ($output as $line) {
                    if (strpos($line, 'python') !== false) {
                        return trim($line);
                    }
                }
            }
            return true;
        }
    }
    
    return false;
}

// Fungsi untuk get Python path
function getPythonPath() {
    // Coba beberapa kemungkinan path Python di Windows
    $possiblePaths = [
        'python',
        'python3',
        'py',
        'C:\\Python39\\python.exe',
        'C:\\Python38\\python.exe',
        'C:\\Python37\\python.exe',
        'C:\\Program Files\\Python39\\python.exe',
        'C:\\Program Files\\Python38\\python.exe',
        'C:\\Program Files\\Python37\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python39\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python38\\python.exe',
        'C:\\Users\\' . get_current_user() . '\\AppData\\Local\\Programs\\Python\\Python37\\python.exe',
    ];
    
    foreach ($possiblePaths as $path) {
        $output = [];
        $returnCode = 0;
        @exec('"' . $path . '" --version 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            return $path;
        }
    }
    
    return 'python'; // Fallback
}

// Fungsi untuk export transaksi ke CSV
function exportTransactionsToCSV($db) {
    $query = "SELECT 
                t.id as id_transaksi,
                p.name as nama_produk,
                ti.quantity,
                t.total,
                t.created_at
              FROM transactions t
              JOIN transaction_items ti ON t.id = ti.transaction_id
              JOIN products p ON ti.product_id = p.id
              WHERE t.status = 'completed'
              ORDER BY t.id, ti.id";
    
    $stmt = $db->query($query);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buat folder dataset jika belum ada
    if (!file_exists('dataset')) {
        mkdir('dataset', 0777, true);
    }
    
    if (!file_exists('ml')) {
        mkdir('ml', 0777, true);
    }
    
    $csvFile = 'dataset/transaksi.csv';
    
    // Tulis ke CSV
    $fp = fopen($csvFile, 'w');
    
    // Header CSV
    fputcsv($fp, ['id_transaksi', 'nama_produk', 'quantity', 'total', 'created_at']);
    
    // Data transaksi
    foreach ($transactions as $transaction) {
        fputcsv($fp, [
            $transaction['id_transaksi'],
            $transaction['nama_produk'],
            $transaction['quantity'],
            $transaction['total'],
            $transaction['created_at']
        ]);
    }
    
    fclose($fp);
    
    return [
        'success' => true,
        'file' => $csvFile,
        'count' => count($transactions)
    ];
}

// Fungsi untuk menjalankan analisis ML
function runAssociationAnalysis($db) {
    // Export data ke CSV dulu
    $exportResult = exportTransactionsToCSV($db);
    
    if (!$exportResult['success']) {
        return [
            'success' => false,
            'message' => 'Gagal export data ke CSV'
        ];
    }
    
    // Jika transaksi kurang dari 2
    if ($exportResult['count'] < 2) {
        return [
            'success' => false,
            'message' => 'Data transaksi terlalu sedikit (minimal 2 transaksi)',
            'count' => $exportResult['count']
        ];
    }
    
    // Dapatkan path Python
    $pythonPath = getPythonPath();
    
    // Path ke script Python (gunakan absolute path)
    $pythonScript = realpath('ml/association_analysis.py');
    
    if (!$pythonScript) {
        $pythonScript = __DIR__ . '/ml/association_analysis.py';
    }
    
    // Debug info
    $debugInfo = [
        'python_path' => $pythonPath,
        'python_script' => $pythonScript,
        'csv_file' => realpath($exportResult['file']),
        'working_dir' => getcwd(),
        'os' => PHP_OS,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];
    
    // Simpan debug info
    file_put_contents('ml/debug_info.json', json_encode($debugInfo, JSON_PRETTY_PRINT));
    
    // Build command untuk Windows
    $command = '"' . $pythonPath . '" "' . $pythonScript . '"';
    
    // Execute Python script
    $output = [];
    $returnCode = 0;
    
    // Untuk Windows, gunakan shell_exec atau proc_open
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows command
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes, null, null);
        
        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $returnCode = proc_close($process);
            $output = array_filter([$stdout, $stderr]);
        }
    } else {
        // Linux/Unix command
        exec($command . ' 2>&1', $output, $returnCode);
    }
    
    // Simpan output ke log
    file_put_contents('ml/python_output.log', implode("\n", $output));
    
    if ($returnCode === 0) {
        // Baca hasil JSON
        $jsonFile = 'ml/hasil_asosiasi.json';
        if (file_exists($jsonFile)) {
            $data = file_get_contents($jsonFile);
            $results = json_decode($data, true);
            
            return [
                'success' => true,
                'message' => 'Analisis berhasil!',
                'count' => count($results),
                'transactions_count' => $exportResult['count'],
                'results' => $results,
                'python_output' => implode("\n", array_slice($output, 0, 10)), // Ambil 10 baris pertama
                'debug' => $debugInfo
            ];
        } else {
            return [
                'success' => false,
                'message' => 'File hasil tidak ditemukan',
                'output' => $output,
                'debug' => $debugInfo
            ];
        }
    } else {
        // Coba dengan approach alternatif
        return tryAlternativeApproach($exportResult['file'], $debugInfo, $exportResult['count']);
    }
}

// Alternative approach jika Python gagal
function tryAlternativeApproach($csvFile, $debugInfo, $transactionCount) {
    // Coba gunakan PHP fallback
    require_once 'ml/association_php.php';
    
    $result = runAssociationAnalysisPHP($csvFile);
    
    if ($result['success']) {
        // Simpan hasil ke JSON
        $jsonFile = 'ml/hasil_asosiasi.json';
        $jsonResults = [];
        
        foreach ($result['results'] as $rule) {
            $jsonResults[] = [
                'if_buy' => $rule['if_buy'],
                'then_buy' => $rule['then_buy'],
                'confidence' => $rule['confidence'],
                'support' => $rule['support']
            ];
        }
        
        file_put_contents($jsonFile, json_encode($jsonResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return [
            'success' => true,
            'message' => 'Analisis berhasil (menggunakan PHP Engine)',
            'count' => $result['rules_found'],
            'transactions_count' => $transactionCount,
            'results' => $jsonResults,
            'engine' => 'php'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Gagal menjalankan analisis ML',
        'debug' => $debugInfo,
        'transaction_count' => $transactionCount
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = runAssociationAnalysis($db);
    
    // Simpan result ke session
    $_SESSION['ml_result'] = $result;
    
    // Redirect ke dashboard
    header('Location: dashboardadmin.php');
    exit();
}

// Cek status transaksi
$countQuery = "SELECT COUNT(*) as total FROM transactions WHERE status = 'completed'";
$countResult = $db->query($countQuery)->fetch(PDO::FETCH_ASSOC);
$transactionCount = $countResult['total'];

// Cek Python
$pythonAvailable = checkPythonWindows();
$pythonPath = getPythonPath();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Analisis Asosiasi - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/adminstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .system-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .system-info h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            margin: 5px 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .python-status {
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        
        .python-status.available {
            background: #d4edda;
            color: #155724;
        }
        
        .python-status.unavailable {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-generate {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.3s ease;
            margin: 20px 0;
        }
        
        .btn-generate:hover:not(:disabled) {
            background: #5a4bdc;
            transform: translateY(-2px);
        }
        
        .btn-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .transaction-list {
            margin: 20px 0;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-brain"></i> Generate Analisis Asosiasi</h1>
                <p>Analisis pola pembelian produk menggunakan Machine Learning</p>
            </div>
            
            <div class="system-info">
                <h4><i class="fas fa-server"></i> System Information</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">OS:</span>
                        <span class="info-value"><?php echo PHP_OS; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Python Status:</span>
                        <span class="python-status <?php echo $pythonAvailable ? 'available' : 'unavailable'; ?>">
                            <?php echo $pythonAvailable ? '✅ Tersedia' : '❌ Tidak ditemukan'; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Python Path:</span>
                        <span class="info-value"><?php echo $pythonPath; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Transaksi Completed:</span>
                        <span class="info-value"><?php echo $transactionCount; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">PHP Version:</span>
                        <span class="info-value"><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Web Server:</span>
                        <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($transactionCount < 2): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0;">
                <h4><i class="fas fa-exclamation-triangle"></i> Data Tidak Cukup</h4>
                <p>Minimal diperlukan 2 transaksi completed untuk analisis.</p>
                <p>Saat ini: <strong><?php echo $transactionCount; ?> transaksi</strong></p>
            </div>
            <?php endif; ?>
            
            <!-- Tampilkan sample transaksi -->
            <?php
            $sampleQuery = "SELECT t.id, t.transaction_code, COUNT(ti.id) as items 
                           FROM transactions t 
                           LEFT JOIN transaction_items ti ON t.id = ti.transaction_id 
                           WHERE t.status = 'completed'
                           GROUP BY t.id 
                           ORDER BY t.created_at DESC 
                           LIMIT 5";
            $samples = $db->query($sampleQuery)->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (count($samples) > 0): ?>
            <div class="transaction-list">
                <h4><i class="fas fa-receipt"></i> 5 Transaksi Terakhir</h4>
                <?php foreach ($samples as $sample): ?>
                <div style="padding: 8px; border-bottom: 1px solid #eee;">
                    <strong>#<?php echo $sample['transaction_code']; ?></strong> 
                    - ID: <?php echo $sample['id']; ?> 
                    - Items: <?php echo $sample['items']; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="mlForm">
                <button type="submit" class="btn-generate" id="generateBtn" <?php echo $transactionCount < 2 ? 'disabled' : ''; ?>>
                    <i class="fas fa-play"></i> 
                    <?php echo $pythonAvailable ? 'Jalankan Analisis ML (Python)' : 'Jalankan Analisis ML (PHP)'; ?>
                </button>
            </form>
            
            <div style="text-align: center;">
                <a href="dashboardadmin.php" style="color: #666; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <a href="?debug=1" style="margin-left: 20px; color: #666; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-code"></i> Debug Info
                </a>
            </div>
            
            <?php if (isset($_GET['debug'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4><i class="fas fa-bug"></i> Debug Information</h4>
                <pre><?php 
                echo "PHP OS: " . PHP_OS . "\n";
                echo "Python Available: " . ($pythonAvailable ? 'Yes' : 'No') . "\n";
                echo "Python Path: " . $pythonPath . "\n";
                echo "Current Dir: " . getcwd() . "\n";
                echo "Script Dir: " . __DIR__ . "\n";
                
                // Test exec
                echo "\n--- Test exec() ---\n";
                $testOutput = [];
                $testCode = 0;
                @exec('echo "test" 2>&1', $testOutput, $testCode);
                echo "exec() test return code: " . $testCode . "\n";
                echo "exec() test output: " . implode("\n", $testOutput) . "\n";
                
                // Test shell_exec
                echo "\n--- Test shell_exec() ---\n";
                $shellTest = @shell_exec('echo "shell_test"');
                echo "shell_exec() result: " . ($shellTest ? $shellTest : 'NULL') . "\n";
                ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('mlForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Add progress indicator
            const progress = document.createElement('div');
            progress.innerHTML = `
                <div style="text-align: center; padding: 20px;">
                    <div style="display: inline-block; width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                    <p style="margin-top: 10px; color: #666;">Sedang menjalankan analisis ML...</p>
                    <p style="font-size: 0.9em; color: #999;">Proses ini membutuhkan waktu beberapa detik</p>
                </div>
            `;
            this.parentNode.insertBefore(progress, this.nextSibling);
            
            // Add CSS for spinner
            const style = document.createElement('style');
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        });
        
        // Check for session message
        <?php if (isset($_SESSION['ml_result'])): ?>
        window.onload = function() {
            const result = <?php echo json_encode($_SESSION['ml_result']); ?>;
            
            if (result.success) {
                alert('✅ ' + result.message + '\n\n' +
                      '📊 Ditemukan ' + result.count + ' pola asosiasi\n' +
                      '💳 Dari ' + result.transactions_count + ' transaksi\n' +
                      (result.engine ? '⚙️ Engine: ' + result.engine : ''));
            } else {
                let errorMsg = '❌ ' + result.message;
                if (result.debug) {
                    errorMsg += '\n\nDebug Info:\n';
                    errorMsg += 'Python Path: ' + (result.debug.python_path || 'N/A') + '\n';
                    errorMsg += 'OS: ' + (result.debug.os || 'N/A');
                }
                alert(errorMsg);
            }
            
            // Clear session
            <?php unset($_SESSION['ml_result']); ?>
        };
        <?php endif; ?>
    </script>
</body>
</html>