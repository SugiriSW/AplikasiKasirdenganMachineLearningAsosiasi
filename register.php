<?php
require_once 'config/database.php';
require_once 'models/User.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $user = new User($db);
    
    // Ambil data dari form
    $name = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validasi
    if (empty($name)) {
        $errors[] = "Nama lengkap harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    if (empty($phone)) {
        $errors[] = "Nomor HP harus diisi";
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    // Cek apakah email sudah terdaftar
    if (empty($errors)) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email sudah terdaftar";
        }
    }
    
    // Jika tidak ada error, simpan user
    if (empty($errors)) {
        $query = "INSERT INTO users (name, email, phone, password, role) 
                  VALUES (:name, :email, :phone, :password, 'kasir')";
        
        $stmt = $db->prepare($query);
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $hashed_password);
        
        if ($stmt->execute()) {
            $success = "Pendaftaran berhasil! Silakan login.";
            // Redirect ke login setelah 2 detik
            header("refresh:2;url=login.php");
        } else {
            $errors[] = "Terjadi kesalahan saat mendaftar. Silakan coba lagi.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GrosirMart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <i class="fas fa-store-alt"></i>
            <h1>Grosir<span>Mart</span></h1>
        </div>

        <div class="register-form">
            <h2>Buat Akun Baru</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div style="background-color: rgba(76, 201, 240, 0.1); color: var(--success-color); padding: 0.75rem; border-radius: var(--border-radius); margin-bottom: 1rem; text-align: center;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label for="fullname">Nama Lengkap</label>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="fullname" name="fullname" placeholder="Masukkan nama lengkap" 
                               value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="email">Email</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="contoh@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="phone">Nomor HP</label>
                    <div class="input-field">
                        <i class="fas fa-phone"></i>
                        <input type="tel" id="phone" name="phone" placeholder="08123456789"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                    <div class="password-strength">
                        <span class="strength-bar"></span>
                        <span class="strength-bar"></span>
                        <span class="strength-bar"></span>
                        <span class="strength-text">Kekuatan password</span>
                    </div>
                </div>
                
                <div class="input-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ketik ulang password" required>
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>
                
                <div class="terms-agreement">
                    <label class="terms-checkbox">
                        <input type="checkbox" name="terms" required>
                        <span>Saya setuju dengan <a href="#">Syarat & Ketentuan</a> dan <a href="#">Kebijakan Privasi</a></span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-register">Daftar Sekarang</button>
                    <a href="login.php" class="btn-back-to-login">
                        <i class="fas fa-arrow-left"></i> Kembali ke Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBars = document.querySelectorAll('.strength-bar');
        const strengthText = document.querySelector('.strength-text');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Kriteria kekuatan password
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            
            // Batasi maksimal 4
            strength = Math.min(strength, 4);
            
            // Update tampilan strength bar
            strengthBars.forEach((bar, index) => {
                if (index < strength) {
                    if (strength < 2) {
                        bar.style.backgroundColor = 'var(--danger-color)';
                    } else if (strength < 4) {
                        bar.style.backgroundColor = 'var(--warning-color)';
                    } else {
                        bar.style.backgroundColor = 'var(--success-color)';
                    }
                } else {
                    bar.style.backgroundColor = '#eee';
                }
            });
            
            // Update teks
            if (password.length === 0) {
                strengthText.textContent = 'Kekuatan password';
                strengthText.style.color = 'var(--gray-color)';
            } else if (strength < 2) {
                strengthText.textContent = 'Lemah';
                strengthText.style.color = 'var(--danger-color)';
            } else if (strength < 4) {
                strengthText.textContent = 'Sedang';
                strengthText.style.color = 'var(--warning-color)';
            } else {
                strengthText.textContent = 'Kuat';
                strengthText.style.color = 'var(--success-color)';
            }
        });

        // Validasi konfirmasi password real-time
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordInput.style.borderColor = 'var(--danger-color)';
            } else if (confirmPassword) {
                confirmPasswordInput.style.borderColor = 'var(--success-color)';
            } else {
                confirmPasswordInput.style.borderColor = 'var(--gray-color)';
            }
        }

        passwordInput.addEventListener('input', validatePasswordMatch);
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);

        // Validasi form sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const termsChecked = document.querySelector('input[name="terms"]').checked;
            
            if (!termsChecked) {
                e.preventDefault();
                alert('Anda harus menyetujui Syarat & Ketentuan dan Kebijakan Privasi');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return;
            }
        });
    </script>
</body>
</html>