<?php
require_once 'includes/config.php';

// Si déjà connecté, rediriger vers le dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ? AND mot_de_passe = MD5(?)");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['nom_utilisateur'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit();
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect";
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Pharmacie Souley-Guirou</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            background: #ffffff;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* ===== CONTENEUR PRINCIPAL ===== */
        .login-wrapper {
            width: 100%;
            max-width: 420px;
        }
        
        /* ===== CARTE DE CONNEXION ===== */
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeUp 0.6s ease;
        }
        
        /* ===== EN-TÊTE ===== */
        .login-header {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #388e3c 100%);
            padding: 35px 30px 25px;
            text-align: center;
            position: relative;
        }
        
        .login-header .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
        }
        
        .login-header .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 12px;
            transition: transform 0.3s;
        }
        
        .login-header .logo-container img:hover {
            transform: scale(1.05);
        }
        
        .login-header .logo-container h4 {
            color: #ffffff;
            font-weight: 700;
            font-size: 22px;
            margin: 0;
            letter-spacing: 0.5px;
        }
        
        .login-header .logo-container h4 i {
            color: #4caf50;
        }
        
        .login-header .logo-container small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 2px;
        }
        
        /* ===== DÉCORATION ===== */
        .login-header .decoration {
            position: absolute;
            bottom: -18px;
            left: 0;
            right: 0;
        }
        
        .login-header .decoration svg {
            display: block;
            width: 100%;
        }
        
        /* ===== CORPS ===== */
        .login-body {
            padding: 35px 30px 30px;
        }
        
        .login-body .welcome-text {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .login-body .welcome-text h5 {
            font-weight: 700;
            color: #1a1a2e;
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .login-body .welcome-text p {
            color: #8a8fa8;
            font-size: 13px;
            margin: 0;
        }
        
        /* ===== CHAMPS ===== */
        .form-floating-custom {
            position: relative;
            margin-bottom: 18px;
        }
        
        .form-floating-custom .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aab0c8;
            font-size: 16px;
            z-index: 5;
            transition: all 0.3s;
        }
        
        .form-floating-custom .form-control {
            padding: 12px 12px 12px 44px;
            border: 2px solid #eef0f5;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            height: 50px;
            background: #f8f9fc;
        }
        
        .form-floating-custom .form-control:focus {
            border-color: #4caf50;
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.12);
            background: #ffffff;
        }
        
        .form-floating-custom .form-control:focus + .input-icon {
            color: #4caf50;
        }
        
        .form-floating-custom .form-control::placeholder {
            color: #aab0c8;
            font-size: 13px;
        }
        
        .form-floating-custom .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #aab0c8;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: color 0.3s;
            z-index: 5;
        }
        
        .form-floating-custom .toggle-password:hover {
            color: #4a4a6a;
        }
        
        /* ===== OPTIONS ===== */
        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .login-options .form-check {
            margin: 0;
        }
        
        .login-options .form-check-input {
            border-color: #d1d5db;
            cursor: pointer;
        }
        
        .login-options .form-check-input:checked {
            background-color: #4caf50;
            border-color: #4caf50;
        }
        
        .login-options .form-check-label {
            color: #4a4a6a;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
        }
        
        .login-options a {
            color: #4caf50;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
        }
        
        .login-options a:hover {
            color: #2e7d32;
            text-decoration: underline;
        }
        
        /* ===== BOUTON ===== */
        .btn-login {
            background: linear-gradient(135deg, #2e7d32, #4caf50);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.35);
            color: white;
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            font-size: 16px;
        }
        
        /* ===== PIED DE PAGE ===== */
        .login-footer {
            text-align: center;
            padding: 15px 30px 20px;
            background: #f8f9fc;
            border-top: 1px solid #eef0f5;
        }
        
        .login-footer p {
            margin: 0;
            font-size: 12px;
            color: #aab0c8;
        }
        
        .login-footer .demo-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 6px;
        }
        
        .login-footer .demo-info .demo-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #8a8fa8;
            background: #f0f2f5;
            padding: 3px 12px;
            border-radius: 6px;
        }
        
        .login-footer .demo-info .demo-item code {
            background: none;
            padding: 0;
            font-weight: 600;
            color: #2e7d32;
            font-size: 12px;
        }
        
        /* ===== ALERTES ===== */
        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .alert-custom i {
            font-size: 18px;
        }
        
        .alert-custom.alert-danger {
            background: #fde8e8;
            color: #c0392b;
        }
        
        .alert-custom.alert-success {
            background: #e8f8f5;
            color: #1abc9c;
        }
        
        /* ===== ANIMATION ===== */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 576px) {
            .login-body {
                padding: 25px 20px 20px;
            }
            
            .login-header {
                padding: 25px 20px 20px;
            }
            
            .login-header .logo-container img {
                width: 60px;
                height: 60px;
            }
            
            .login-header .logo-container h4 {
                font-size: 18px;
            }
            
            .login-options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .login-footer .demo-info {
                flex-direction: column;
                gap: 5px;
                align-items: center;
            }
        }
        
        /* ===== BACKGROUND PARTICULES ===== */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(76, 175, 80, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(76, 175, 80, 0.08) 0%, transparent 50%);
        }
    </style>
</head>
<body>

<!-- ===== BACKGROUND ===== -->
<div class="bg-pattern"></div>

<!-- ===== CONTENEUR ===== -->
<div class="login-wrapper">

    <!-- ===== CARTE DE CONNEXION ===== -->
    <div class="login-card">
        
        <!-- ===== EN-TÊTE ===== -->
        <div class="login-header">
            <a href="#" class="logo-container">
                <img src="assets/images/logo.png" alt="Pharmacie Souley-Guirou">
                <h4><i class="fas fa-heartbeat"></i> Pharmacie Souley-Guirou</h4>
                <small>Système de Gestion Intégré</small>
            </a>
            <div class="decoration">
                <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 40V20C120 35 240 40 360 35C480 30 600 25 720 30C840 35 960 40 1080 30C1200 20 1320 25 1440 30V40H0Z" fill="white"/>
                </svg>
            </div>
        </div>
        
        <!-- ===== CORPS ===== -->
        <div class="login-body">
            
            <!-- Texte de bienvenue -->
            <div class="welcome-text">
                <h5>Bienvenue</h5>
                <p>Connectez-vous à votre espace de gestion</p>
            </div>
            
            <!-- Message d'erreur -->
            <?php if ($error): ?>
                <div class="alert-custom alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire -->
            <form method="POST">
                <!-- Nom d'utilisateur -->
                <div class="form-floating-custom">
                    <input type="text" name="username" class="form-control" id="username" 
                           placeholder="Nom d'utilisateur" required autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
                
                <!-- Mot de passe -->
                <div class="form-floating-custom">
                    <input type="password" name="password" class="form-control" id="password" 
                           placeholder="Mot de passe" required>
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="toggle-password" onclick="togglePassword()" title="Afficher le mot de passe">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                
                <!-- Options -->
                <div class="login-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            Se souvenir de moi
                        </label>
                    </div>
                    <a href="#"><i class="fas fa-key"></i> Mot de passe oublié ?</a>
                </div>
                
                <!-- Bouton -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </form>
        </div>
        
        <!-- ===== PIED DE PAGE ===== -->
        <div class="login-footer">
            <p>
                <i class="fas fa-shield-alt text-success me-1"></i>
                Connexion sécurisée
            </p>

        </div>
        
    </div>
    
    <!-- Copyright -->
    <div class="text-center mt-4" style="color: rgba(255,255,255,0.3); font-size: 11px; letter-spacing: 1px;">
        &copy; <?= date('Y') ?> Pharmacie Souley-Guirou - Tous droits réservés
    </div>
    
</div>

<!-- ============================================ -->
<!-- ===== SCRIPTS ===== -->
<!-- ============================================ -->
<script>
// ===== AFFICHER / CACHER LE MOT DE PASSE =====
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// ===== VALIDATION DU FORMULAIRE =====
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!username || !password) {
        e.preventDefault();
        alert('⚠️ Veuillez remplir tous les champs');
    }
});

// ===== ENTER POUR SE CONNECTER =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const form = document.querySelector('form');
        if (form) {
            form.submit();
        }
    }
});
</script>

</body>
</html>