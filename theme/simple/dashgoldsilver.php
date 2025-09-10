<?php
if (!defined('IS_IN_SCRIPT')) { die(); exit(); }

// Function to process CSV bulk update
function processBulkCSVUpdate($file, $user_id) {
    $results = array(
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'details' => array()
    );
    
    // Validate file
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $results['details'][] = array('error' => 'File tidak ditemukan');
        return $results;
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        $results['details'][] = array('error' => 'File terlalu besar. Maksimal 2MB');
        return $results;
    }
    
    // Check file extension
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($file_ext !== 'csv') {
        $results['details'][] = array('error' => 'Format file harus CSV');
        return $results;
    }
    
    // Read CSV file
    if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $row_number = 0;
        $header_validated = false;
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $row_number++;
            
            // Validate header row
            if ($row_number == 1) {
                $expected_headers = array('tanggal', 'emas_jual', 'emas_beli', 'perak_jual', 'perak_beli');
                $headers = array_map('strtolower', array_map('trim', $data));
                
                if ($headers !== $expected_headers) {
                    $results['details'][] = array(
                        'row' => $row_number,
                        'error' => 'Header CSV tidak sesuai. Expected: ' . implode(', ', $expected_headers)
                    );
                    break;
                }
                $header_validated = true;
                continue;
            }
            
            if (!$header_validated) continue;
            
            $results['total']++;
            
            // Validate and sanitize data
            $price_date = trim($data[0]);
            $gold_sell = floatval(str_replace(',', '', trim($data[1])));
            $gold_buy = floatval(str_replace(',', '', trim($data[2])));
            $silver_sell = floatval(str_replace(',', '', trim($data[3])));
            $silver_buy = floatval(str_replace(',', '', trim($data[4])));
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $price_date)) {
                $results['failed']++;
                $results['details'][] = array(
                    'row' => $row_number,
                    'date' => $price_date,
                    'error' => 'Format tanggal salah. Gunakan YYYY-MM-DD'
                );
                continue;
            }
            
            // Validate prices
            if ($gold_sell <= 0 || $gold_buy <= 0 || $silver_sell <= 0 || $silver_buy <= 0) {
                $results['failed']++;
                $results['details'][] = array(
                    'row' => $row_number,
                    'date' => $price_date,
                    'error' => 'Harga harus lebih besar dari 0'
                );
                continue;
            }
            
            // Validate logical prices (sell >= buy)
            if ($gold_sell < $gold_buy || $silver_sell < $silver_buy) {
                $results['failed']++;
                $results['details'][] = array(
                    'row' => $row_number,
                    'date' => $price_date,
                    'error' => 'Harga jual harus >= harga beli'
                );
                continue;
            }
            
            // Check if record exists
            $existing = db_row("SELECT * FROM `sa_gold_silver_price` WHERE `price_date`='".cek($price_date)."'");
            
            if ($existing) {
                // Update existing record
                $result = db_query("UPDATE `sa_gold_silver_price` SET 
                    `gold_sell`=".$gold_sell.",
                    `gold_buy`=".$gold_buy.",
                    `silver_sell`=".$silver_sell.",
                    `silver_buy`=".$silver_buy.",
                    `updated_by`=".$user_id.",
                    `updated_at`=NOW()
                    WHERE `price_date`='".cek($price_date)."'");
                    
                if ($result) {
                    $results['success']++;
                    $results['details'][] = array(
                        'row' => $row_number,
                        'date' => $price_date,
                        'status' => 'Updated',
                        'gold_sell' => number_format($gold_sell, 0, ',', '.'),
                        'gold_buy' => number_format($gold_buy, 0, ',', '.'),
                        'silver_sell' => number_format($silver_sell, 0, ',', '.'),
                        'silver_buy' => number_format($silver_buy, 0, ',', '.')
                    );
                } else {
                    $results['failed']++;
                    $results['details'][] = array(
                        'row' => $row_number,
                        'date' => $price_date,
                        'error' => 'Database error: ' . db_error()
                    );
                }
            } else {
                // Insert new record
                $result = db_query("INSERT INTO `sa_gold_silver_price` 
                    (`price_date`, `gold_sell`, `gold_buy`, `silver_sell`, `silver_buy`, `updated_by`) 
                    VALUES ('".cek($price_date)."', ".$gold_sell.", ".$gold_buy.", ".$silver_sell.", ".$silver_buy.", ".$user_id.")");
                    
                if ($result) {
                    $results['success']++;
                    $results['details'][] = array(
                        'row' => $row_number,
                        'date' => $price_date,
                        'status' => 'Inserted',
                        'gold_sell' => number_format($gold_sell, 0, ',', '.'),
                        'gold_buy' => number_format($gold_buy, 0, ',', '.'),
                        'silver_sell' => number_format($silver_sell, 0, ',', '.'),
                        'silver_buy' => number_format($silver_buy, 0, ',', '.')
                    );
                } else {
                    $results['failed']++;
                    $results['details'][] = array(
                        'row' => $row_number,
                        'date' => $price_date,
                        'error' => 'Database error: ' . db_error()
                    );
                }
            }
        }
        fclose($handle);
    } else {
        $results['details'][] = array('error' => 'Tidak dapat membaca file CSV');
    }
    
    return $results;
}

// Handle CSV bulk upload
if (isset($_POST['bulk_upload']) && isset($_FILES['csv_file'])) {
    $bulk_results = processBulkCSVUpdate($_FILES['csv_file'], $datamember['mem_id']);
    
    if ($bulk_results['success'] > 0) {
        $success_message = "Bulk update selesai! Berhasil: {$bulk_results['success']}, Gagal: {$bulk_results['failed']} dari {$bulk_results['total']} record.";
    } else {
        $error_message = "Bulk update gagal. Periksa format file CSV Anda.";
    }
}

// Handle single form submission
if (isset($_POST['update_price'])) {
    $price_date = cek($_POST['price_date']);
    $gold_sell = floatval($_POST['gold_sell']);
    $gold_buy = floatval($_POST['gold_buy']);
    $silver_sell = floatval($_POST['silver_sell']);
    $silver_buy = floatval($_POST['silver_buy']);
    
    // Check if price for this date already exists
    $existing = db_row("SELECT * FROM `sa_gold_silver_price` WHERE `price_date`='".$price_date."'");
    
    if ($existing) {
        // Update existing record
        $result = db_query("UPDATE `sa_gold_silver_price` SET 
            `gold_sell`=".$gold_sell.",
            `gold_buy`=".$gold_buy.",
            `silver_sell`=".$silver_sell.",
            `silver_buy`=".$silver_buy.",
            `updated_by`=".$datamember['mem_id'].",
            `updated_at`=NOW()
            WHERE `price_date`='".$price_date."'");
    } else {
        // Insert new record
        $result = db_query("INSERT INTO `sa_gold_silver_price` 
            (`price_date`, `gold_sell`, `gold_buy`, `silver_sell`, `silver_buy`, `updated_by`) 
            VALUES ('".$price_date."', ".$gold_sell.", ".$gold_buy.", ".$silver_sell.", ".$silver_buy.", ".$datamember['mem_id'].")");
    }
    
    if ($result) {
        $success_message = "Harga berhasil disimpan untuk tanggal ".$price_date;
    } else {
        $error_message = "Error: ".db_error();
    }
}

// Get today's price or latest price
$today_price = db_row("SELECT * FROM `sa_gold_silver_price` WHERE `price_date`=CURDATE()");
if (!$today_price) {
    $today_price = db_row("SELECT * FROM `sa_gold_silver_price` ORDER BY `price_date` DESC LIMIT 1");
}

showheader();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Update Harga Emas & Perak</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Berhasil!</strong> <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs" id="updateTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-update" type="button" role="tab">
                            <i class="fas fa-edit"></i> Update Manual
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk-update" type="button" role="tab">
                            <i class="fas fa-upload"></i> Upload CSV
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="updateTabsContent">
                    <!-- Single Update Tab -->
                    <div class="tab-pane fade show active" id="single-update" role="tabpanel">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" name="price_date" 
                                               value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-warning"><i class="fas fa-coins"></i> Harga Emas (per gram)</h6>
                            <div class="mb-3">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="number" class="form-control" name="gold_sell" 
                                       value="<?= $today_price['gold_sell'] ?? 1150000 ?>" 
                                       step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga Beli/Buyback (Rp)</label>
                                <input type="number" class="form-control" name="gold_buy" 
                                       value="<?= $today_price['gold_buy'] ?? 1100000 ?>" 
                                       step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-secondary"><i class="fas fa-circle"></i> Harga Perak (per gram)</h6>
                            <div class="mb-3">
                                <label class="form-label">Harga Jual (Rp)</label>
                                <input type="number" class="form-control" name="silver_sell" 
                                       value="<?= $today_price['silver_sell'] ?? 15000 ?>" 
                                       step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga Beli/Buyback (Rp)</label>
                                <input type="number" class="form-control" name="silver_buy" 
                                       value="<?= $today_price['silver_buy'] ?? 14500 ?>" 
                                       step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                            <button type="submit" name="update_price" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Harga
                            </button>
                        </form>
                    </div>
                    
                    <!-- Bulk Upload Tab -->
                    <div class="tab-pane fade" id="bulk-update" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Upload File CSV</label>
                                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i> 
                                            Format: tanggal,emas_jual,emas_beli,perak_jual,perak_beli<br>
                                            Contoh: 2024-01-15,1150000,1100000,15000,14500
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="bulk_upload" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Upload & Proses
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Template CSV</h6>
                                        <p class="card-text small">
                                            Download template CSV untuk memudahkan input data.
                                        </p>
                                        <a href="#" class="btn btn-outline-primary btn-sm" onclick="downloadTemplate()">
                                            <i class="fas fa-download"></i> Download Template
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bulk Upload Results -->
                        <?php if (isset($bulk_results) && !empty($bulk_results['details'])): ?>
                        <div class="mt-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-chart-bar"></i> Hasil Upload CSV
                                        <span class="badge bg-success ms-2"><?= $bulk_results['success'] ?> Berhasil</span>
                                        <span class="badge bg-danger ms-1"><?= $bulk_results['failed'] ?> Gagal</span>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                        <table class="table table-sm table-striped">
                                            <thead class="table-dark sticky-top">
                                                <tr>
                                                    <th>Baris</th>
                                                    <th>Tanggal</th>
                                                    <th>Status</th>
                                                    <th>Emas Jual</th>
                                                    <th>Emas Beli</th>
                                                    <th>Perak Jual</th>
                                                    <th>Perak Beli</th>
                                                    <th>Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bulk_results['details'] as $detail): ?>
                                                <tr class="<?= isset($detail['error']) ? 'table-danger' : 'table-success' ?>">
                                                    <td><?= $detail['row'] ?? '-' ?></td>
                                                    <td><?= $detail['date'] ?? '-' ?></td>
                                                    <td>
                                                        <?php if (isset($detail['error'])): ?>
                                                            <span class="badge bg-danger">Error</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success"><?= $detail['status'] ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $detail['gold_sell'] ?? '-' ?></td>
                                                    <td><?= $detail['gold_buy'] ?? '-' ?></td>
                                                    <td><?= $detail['silver_sell'] ?? '-' ?></td>
                                                    <td><?= $detail['silver_buy'] ?? '-' ?></td>
                                                    <td>
                                                        <?php if (isset($detail['error'])): ?>
                                                            <small class="text-danger"><?= $detail['error'] ?></small>
                                                        <?php else: ?>
                                                            <small class="text-success">Berhasil diproses</small>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Harga Terkini</h6>
            </div>
            <div class="card-body">
                <?php if ($today_price): ?>
                    <div class="mb-3">
                        <small class="text-muted">Tanggal: <?= date('d/m/Y', strtotime($today_price['price_date'])) ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <strong class="text-warning">Emas</strong><br>
                        <small>Jual: Rp <?= number_format($today_price['gold_sell'], 0, ',', '.') ?></small><br>
                        <small>Beli: Rp <?= number_format($today_price['gold_buy'], 0, ',', '.') ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <strong class="text-secondary">Perak</strong><br>
                        <small>Jual: Rp <?= number_format($today_price['silver_sell'], 0, ',', '.') ?></small><br>
                        <small>Beli: Rp <?= number_format($today_price['silver_buy'], 0, ',', '.') ?></small>
                    </div>
                    
                    <small class="text-muted">Update: <?= date('d/m/Y H:i', strtotime($today_price['updated_at'])) ?></small>
                <?php else: ?>
                    <p class="text-muted">Belum ada data harga.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi</h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Harga yang diinput akan otomatis tampil di halaman EPI 
                    (<a href="<?= $weburl ?>epi/" target="_blank">lihat halaman</a>).
                </small>
                <hr>
                <small class="text-muted">
                    <i class="fas fa-clock"></i> 
                    Disarankan update harga setiap hari sebelum jam 9 pagi.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Price Chart -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-title mb-0">Grafik Harga</h6>
                    <h5 class="mt-1 mb-0" id="chartTitle" style="color: #D4AF37;">Harga Emas per Gram</h5>
                    <small class="text-muted" id="dateRange">Mingguan</small>
                </div>
                <div class="d-flex gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-warning active" id="goldBtn" onclick="switchMetal('gold')">Emas</button>
                        <button type="button" class="btn btn-outline-secondary" id="silverBtn" onclick="switchMetal('silver')">Perak</button>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" id="periodButtons">
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart(1, 'daily')">Harian</button>
                        <button type="button" class="btn btn-outline-secondary active" onclick="updateChart(7, 'weekly')">Mingguan</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart(30, 'monthly')">Bulanan</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="updateChart(365, 'yearly')">Tahunan</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 400px;">
                    <canvas id="priceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Riwayat Harga (7 Hari Terakhir)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Emas Jual</th>
                                <th>Emas Beli</th>
                                <th>Perak Jual</th>
                                <th>Perak Beli</th>
                                <th>Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $history = db_select("SELECT * FROM `sa_gold_silver_price` ORDER BY `price_date` DESC LIMIT 7");
                            if (count($history) > 0):
                                foreach ($history as $row):
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['price_date'])) ?></td>
                                <td>Rp <?= number_format($row['gold_sell'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['gold_buy'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['silver_sell'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['silver_buy'], 0, ',', '.') ?></td>
                                <td><small><?= date('d/m H:i', strtotime($row['updated_at'])) ?></small></td>
                            </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let priceChart;
let currentMetal = 'gold';
let currentPeriod = 'weekly';
let currentDays = 7;

// Initialize chart
document.addEventListener('DOMContentLoaded', function() {
    initChart();
    loadChartData(7, 'weekly');
});

function initChart() {
    const ctx = document.getElementById('priceChart').getContext('2d');
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Harga Emas',
                    data: [],
                    borderColor: '#D4AF37',
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        if (currentMetal === 'gold') {
                            gradient.addColorStop(0, 'rgba(212, 175, 55, 0.8)');
                            gradient.addColorStop(0.5, 'rgba(212, 175, 55, 0.4)');
                            gradient.addColorStop(1, 'rgba(212, 175, 55, 0.1)');
                        } else {
                            gradient.addColorStop(0, 'rgba(192, 192, 192, 0.8)');
                            gradient.addColorStop(0.5, 'rgba(192, 192, 192, 0.4)');
                            gradient.addColorStop(1, 'rgba(192, 192, 192, 0.1)');
                        }
                        return gradient;
                    },
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#D4AF37',
                    pointHoverBorderColor: '#B8860B',
                    pointHoverBorderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#333',
                    bodyColor: '#333',
                    borderColor: '#D4AF37',
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        title: function(context) {
                            return 'Tanggal: ' + context[0].label;
                        },
                        label: function(context) {
                            return 'Harga: Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#666',
                        font: {
                            size: 11
                        },
                        maxTicksLimit: 8
                    }
                },
                y: {
                    display: true,
                    position: 'right',
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#666',
                        font: {
                            size: 11
                        },
                        callback: function(value) {
                            return new Intl.NumberFormat('id-ID', {
                                notation: 'compact',
                                compactDisplay: 'short'
                            }).format(value);
                        }
                    }
                }
            },
            elements: {
                point: {
                    hoverBorderWidth: 2
                }
            }
        }
    });
}

function loadChartData(days, period) {
    fetch('<?= $weburl ?>api_chart_data.php?days=' + days + '&period=' + period)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                priceChart.data.labels = data.labels;
                if (currentMetal === 'gold') {
                    priceChart.data.datasets[0].data = data.gold_sell;
                    priceChart.data.datasets[0].label = 'Harga Emas';
                    priceChart.data.datasets[0].borderColor = '#D4AF37';
                    priceChart.data.datasets[0].pointHoverBackgroundColor = '#D4AF37';
                    priceChart.data.datasets[0].pointHoverBorderColor = '#B8860B';
                } else {
                    priceChart.data.datasets[0].data = data.silver_sell;
                    priceChart.data.datasets[0].label = 'Harga Perak';
                    priceChart.data.datasets[0].borderColor = '#C0C0C0';
                    priceChart.data.datasets[0].pointHoverBackgroundColor = '#C0C0C0';
                    priceChart.data.datasets[0].pointHoverBorderColor = '#808080';
                }
                priceChart.update('active');
            }
        })
        .catch(error => {
            console.error('Error loading chart data:', error);
        });
}

function switchMetal(metal) {
    currentMetal = metal;
    
    // Update button states
    document.getElementById('goldBtn').classList.remove('active');
    document.getElementById('silverBtn').classList.remove('active');
    
    if (metal === 'gold') {
        document.getElementById('goldBtn').classList.add('active');
        document.getElementById('chartTitle').style.color = '#D4AF37';
    } else {
        document.getElementById('silverBtn').classList.add('active');
        document.getElementById('chartTitle').style.color = '#C0C0C0';
    }
    
    // Update chart with current period
    updateChart(currentDays, currentPeriod);
}

function updateChart(days, period) {
    // Store current state
    currentDays = days;
    currentPeriod = period;
    
    // Update active button for period
    document.querySelectorAll('#periodButtons .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update chart title and date range based on period and metal
    const titleElement = document.getElementById('chartTitle');
    const dateRangeElement = document.getElementById('dateRange');
    let title = '';
    let dateRange = '';
    
    const metalName = currentMetal === 'gold' ? 'Emas' : 'Perak';
    
    const today = new Date();
    const startDate = new Date(today);
    
    switch(period) {
        case 'daily':
            title = `Harga ${metalName} Harian`;
            dateRange = 'Hari ini';
            break;
        case 'weekly':
            title = `Harga ${metalName} Mingguan`;
            startDate.setDate(today.getDate() - 7);
            dateRange = startDate.toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) + ' — ' + 
                       today.toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
            break;
        case 'monthly':
            title = `Harga ${metalName} Bulanan`;
            startDate.setDate(today.getDate() - 30);
            dateRange = startDate.toLocaleDateString('id-ID', {day: 'numeric', month: 'short'}) + ' — ' + 
                       today.toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
            break;
        case 'yearly':
            title = `Harga ${metalName} Tahunan`;
            startDate.setFullYear(today.getFullYear() - 1);
            dateRange = startDate.toLocaleDateString('id-ID', {month: 'short', year: 'numeric'}) + ' — ' + 
                       today.toLocaleDateString('id-ID', {month: 'short', year: 'numeric'});
            break;
    }
    
    titleElement.textContent = title;
    dateRangeElement.textContent = dateRange;
    
    // Load new data
    loadChartData(days, period);
}

// Function to download CSV template
function downloadTemplate() {
    const csvContent = "tanggal,emas_jual,emas_beli,perak_jual,perak_beli\n" +
                      "2024-01-15,1150000,1100000,15000,14500\n" +
                      "2024-01-16,1155000,1105000,15100,14600\n" +
                      "2024-01-17,1160000,1110000,15200,14700";
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'template_harga_emas_perak.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// File upload validation
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="csv_file"]');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (2MB max)
                if (file.size > 2097152) {
                    alert('File terlalu besar. Maksimal 2MB.');
                    e.target.value = '';
                    return;
                }
                
                // Check file extension
                const fileName = file.name.toLowerCase();
                if (!fileName.endsWith('.csv')) {
                    alert('Format file harus CSV.');
                    e.target.value = '';
                    return;
                }
                
                // Show file info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'alert alert-info mt-2';
                fileInfo.innerHTML = `
                    <i class="fas fa-file-csv"></i> 
                    File: ${file.name} (${(file.size/1024).toFixed(1)} KB)
                `;
                
                // Remove existing file info
                const existingInfo = e.target.parentNode.querySelector('.alert-info');
                if (existingInfo) {
                    existingInfo.remove();
                }
                
                e.target.parentNode.appendChild(fileInfo);
            }
        });
    }
});
</script>

<?php showfooter(); ?>