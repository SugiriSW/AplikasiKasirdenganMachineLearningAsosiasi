// Fungsi format rupiah
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// DOM Elements
const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
const cashPayment = document.getElementById('cashPayment');
const debitPayment = document.getElementById('debitPayment');
const qrisPayment = document.getElementById('qrisPayment');
const cashAmountInput = document.getElementById('cashAmount');
const cashChangeDisplay = document.getElementById('cashChange');
const paymentItemsContainer = document.getElementById('paymentItems');
const paymentSubtotal = document.getElementById('paymentSubtotal');
const paymentDiscount = document.getElementById('paymentDiscount');
const paymentTotal = document.getElementById('paymentTotal');
const btnProcessPayment = document.getElementById('btnProcessPayment');

// Data dari localStorage
const cart = JSON.parse(localStorage.getItem('shoppingCart')) || [];
const totalAmount = JSON.parse(localStorage.getItem('totalAmount')) || {
    subtotal: 0,
    discount: 0,
    total: 0
};

// Tampilkan data pembayaran
function renderPaymentDetails() {
    paymentItemsContainer.innerHTML = '';
    
    if (cart.length === 0) {
        paymentItemsContainer.innerHTML = '<p class="empty-message">Tidak ada item dalam keranjang</p>';
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 2000);
        return;
    }
    
    cart.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'payment-item';
        itemElement.innerHTML = `
            <div class="item-info">
                <h4 class="item-name">${item.name}</h4>
                <p class="item-price">${formatRupiah(item.price)} x ${item.quantity}</p>
            </div>
            <div class="item-total">
                ${formatRupiah(item.price * item.quantity)}
            </div>
        `;
        paymentItemsContainer.appendChild(itemElement);
    });
    
    paymentSubtotal.textContent = formatRupiah(totalAmount.subtotal);
    paymentDiscount.textContent = formatRupiah(totalAmount.discount);
    paymentTotal.textContent = formatRupiah(totalAmount.total);
}

// Fungsi untuk menampilkan form pembayaran
function showPaymentMethod(method) {
    cashPayment.style.display = 'none';
    debitPayment.style.display = 'none';
    qrisPayment.style.display = 'none';
    
    if (method === 'tunai') {
        cashPayment.style.display = 'block';
        calculateChange();
    } else if (method === 'debit') {
        debitPayment.style.display = 'block';
    } else if (method === 'qris') {
        qrisPayment.style.display = 'block';
    }
}

// Fungsi untuk menghitung kembalian
function calculateChange() {
    const cashAmount = parseFloat(cashAmountInput.value) || 0;
    const total = totalAmount.total || 0;
    const change = cashAmount - total;
    
    if (isNaN(change)) {
        cashChangeDisplay.textContent = 'Masukkan jumlah uang';
        cashChangeDisplay.className = 'change-info';
        return;
    }
    
    if (change >= 0) {
        cashChangeDisplay.textContent = `Kembalian: ${formatRupiah(change)}`;
        cashChangeDisplay.className = 'change-info positive';
    } else {
        cashChangeDisplay.textContent = `Kurang: ${formatRupiah(Math.abs(change))}`;
        cashChangeDisplay.className = 'change-info negative';
    }
}

// Fungsi untuk copy teks
function setupCopyButtons() {
    document.querySelectorAll('.btn-copy').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const textToCopy = document.getElementById(targetId).textContent;
            
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalIcon = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.color = 'var(--success-color)';
                
                setTimeout(() => {
                    this.innerHTML = originalIcon;
                    this.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Gagal menyalin teks: ', err);
            });
        });
    });
}

// Proses pembayaran - SIMPAN KE DATABASE
async function processPayment() {
    console.log("=== PROCESS PAYMENT STARTED ===");
    
    const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked');
    
    if (!selectedMethod) {
        alert('Pilih metode pembayaran terlebih dahulu!');
        return false;
    }

    const paymentMethod = selectedMethod.value;
    console.log("Payment method:", paymentMethod);
    
    // Validasi Tunai
    if (paymentMethod === 'tunai') {
        const cashAmount = parseFloat(cashAmountInput.value);
        console.log("Cash amount:", cashAmount);
        if (!cashAmount || cashAmount < totalAmount.total) {
            alert('Jumlah uang tidak mencukupi!');
            cashAmountInput.focus();
            return false;
        }
    }
    
    // Validasi QRIS
    if (paymentMethod === 'qris') {
        const proofFile = document.getElementById('proofUpload')?.files[0];
        if (!proofFile) {
            alert('Harap upload bukti pembayaran untuk metode QRIS!');
            return false;
        }
    }

    // Show loading
    btnProcessPayment.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan Transaksi...';
    btnProcessPayment.disabled = true;

    try {
        // Prepare transaction data
        const transactionData = {
            transaction_code: 'INV-' + Date.now().toString().slice(-6),
            subtotal: totalAmount.subtotal,
            discount: totalAmount.discount,
            total: totalAmount.total,
            payment_method: paymentMethod,
            cash_amount: paymentMethod === 'tunai' ? parseFloat(cashAmountInput.value) : 0,
            change_amount: paymentMethod === 'tunai' ? (parseFloat(cashAmountInput.value) - totalAmount.total) : 0
        };

        console.log('Saving transaction:', transactionData);
        console.log('Cart items:', cart);

        // Save transaction to database
        console.log("Sending request to api/save_transaction.php");
        const response = await fetch('api/save_transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                transaction_data: transactionData,
                items: cart
            })
        });

        console.log("Response status:", response.status);
        const result = await response.json();
        console.log("Response result:", result);

        if (result.success) {
            console.log('Transaction saved successfully:', result);
            
            // Show success message
            alert('Transaksi berhasil disimpan! ID Transaksi: ' + result.transaction_code);
            
            // Clear localStorage setelah pembayaran berhasil
            localStorage.removeItem('shoppingCart');
            localStorage.removeItem('totalAmount');
            
            // Redirect ke dashboard
            window.location.href = 'dashboard.php';
            
            return true;
        } else {
            throw new Error(result.message || 'Failed to save transaction');
        }
        
    } catch (error) {
        console.error('Payment error:', error);
        alert('Gagal menyimpan transaksi: ' + error.message);
        
        // Reset button
        btnProcessPayment.innerHTML = '<i class="fas fa-check-circle"></i> Konfirmasi Pembayaran';
        btnProcessPayment.disabled = false;
        
        return false;
    }
}

// Inisialisasi event listeners
function initializeEventListeners() {
    // Event listener untuk metode pembayaran
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            showPaymentMethod(this.value);
        });
    });
    
    // Event listener untuk input uang tunai
    cashAmountInput.addEventListener('input', calculateChange);
    cashAmountInput.addEventListener('change', calculateChange);
    
    // Event listener untuk tombol proses pembayaran
    btnProcessPayment.addEventListener('click', processPayment);
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    renderPaymentDetails();
    showPaymentMethod('tunai');
    setupCopyButtons();
    initializeEventListeners();
    
    console.log("Cart from localStorage:", cart);
    console.log("Total Amount from localStorage:", totalAmount);
});