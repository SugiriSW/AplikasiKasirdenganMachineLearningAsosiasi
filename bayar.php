<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'models/product.php';

redirectIfNotLoggedIn();

// Pastikan session sudah start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - GSG</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-store-alt"></i>
                <h1>GSG - <span>Access</span></h1>
            </div>
            <div class="page-title">
                <h2>Pembayaran</h2>
            </div>
            <div class="user-actions">
                <button class="btn-logout" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </header>

        <div class="payment-content">
            <section class="payment-details">
                <h3>Detail Pembayaran</h3>
                <div class="payment-items" id="paymentItems">
                    <!-- Item akan diisi oleh JavaScript -->
                    <p class="empty-message">Memuat data pembayaran...</p>
                </div>
                <div class="payment-summary">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="paymentSubtotal">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Diskon</span>
                        <span id="paymentDiscount">Rp 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Pembayaran</span>
                        <span id="paymentTotal">Rp 0</span>
                    </div>
                </div>
            </section>

            <section class="payment-methods">
                <h3>Metode Pembayaran</h3>
                
                <div class="method-options">
                    <label class="method-option">
                        <input type="radio" name="paymentMethod" value="tunai" checked>
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Tunai</span>
                    </label>
                    <label class="method-option">
                        <input type="radio" name="paymentMethod" value="debit">
                        <i class="fas fa-credit-card"></i>
                        <span>Kartu Debit</span>
                    </label>
                    <label class="method-option">
                        <input type="radio" name="paymentMethod" value="qris">
                        <i class="fas fa-qrcode"></i>
                        <span>QRIS</span>
                    </label>
                </div>

                <div id="paymentDetails">
                    <!-- Form tunai -->
                    <div class="cash-payment" id="cashPayment">
                        <div class="input-groupp">
                            <label for="cashAmount">Uang Diterima</label>
                            <div class="input-fieldd">
                                <span>Rp</span>
                                <input type="number" id="cashAmount" placeholder="Masukkan jumlah uang" min="0">
                            </div>
                            <div id="cashChange" class="change-info"></div>
                        </div>
                    </div>

                    <!-- Form debit -->
                    <div class="debit-payment" id="debitPayment" style="display: none;">
                        <div class="bank-info">
                            <h4>Transfer ke Rekening:</h4>
                            <div class="account-info">
                                <div class="account-detail">
                                    <span>Bank:</span>
                                    <span id="bankName">Mandiri</span>
                                    <button class="btn-copy" data-target="bankName">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                                <div class="account-detail">
                                    <span>Nomor Rekening:</span>
                                    <span id="accountNumber">1310019219205</span>
                                    <button class="btn-copy" data-target="accountNumber">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                                <div class="account-detail">
                                    <span>Atas Nama:</span>
                                    <span id="accountHolder">Sugiri Satrio Wicak</span>
                                    <button class="btn-copy" data-target="accountHolder">
                                        <i class="far fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="info-text">Harap transfer sesuai total pembayaran</p>
                        </div>
                    </div>

                    <!-- Form QRIS -->
                    <div class="qris-payment" id="qrisPayment" style="display: none;">
                        <div class="qris-image">
                            <div style="background: #f8f9fa; padding: 2rem; text-align: center; border-radius: 8px; border: 2px dashed #ddd;">
                                <i class="fas fa-qrcode" style="font-size: 3rem; color: #4361ee; margin-bottom: 1rem;"></i>
                                <p style="color: #666; margin-bottom: 1rem;">QR Code akan muncul di sini</p>
                                <p style="font-size: 0.9rem; color: #888;">Scan QR code untuk melakukan pembayaran</p>
                            </div>
                        </div>
                        <div class="upload-proof">
                            <label for="proofUpload">Upload Bukti Pembayaran</label>
                            <input type="file" id="proofUpload" accept="image/*,.pdf">
                        </div>
                    </div>
                </div>

                <div class="payment-actions">
                    <button id="btnProcessPayment" class="btn-pay">
                        <i class="fas fa-check-circle"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </section>
        </div>
    </div>

    <script src="assets/js/payment.js"></script>
</body>
</html>