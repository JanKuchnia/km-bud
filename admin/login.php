<?php
/**
 * KM-BUD Admin Panel — Login Form
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$config = require __DIR__ . '/../config/config.php';
$error = '';
$info = '';

if (isset($_GET['logged_out'])) {
    $info = 'Zostałeś pomyślnie wylogowany.';
}

// Rate Limiting init in session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Check lockout
$currentTime = time();
if ($_SESSION['lockout_time'] > 0 && ($currentTime < $_SESSION['lockout_time'])) {
    $remaining = ceil(($_SESSION['lockout_time'] - $currentTime) / 60);
    $error = "Panel zablokowany z powodu zbyt wielu nieudanych logowań. Spróbuj ponownie za {$remaining} min.";
}

// Handle login post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate CSRF
    if (!validateCSRFToken($csrfToken)) {
        $error = 'Błąd weryfikacji tokenu bezpieczeństwa (CSRF).';
    } elseif (empty($password)) {
        $error = 'Wprowadź hasło.';
    } else {
        // Double check configuration password hash has been set
        $hash = $config['admin']['password_hash'] ?? '';
        if (empty($hash) || $hash === '$2y$10$YourHashHere') {
            $error = 'Konfiguracja hasła nie została ukończona. Uruchom install.php, aby ustawić hasło panelu.';
        } else {
            // Verify password
            if (password_verify($password, $hash)) {
                // Success! Reset attempts
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['last_activity'] = time();
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;
                
                header('Location: index.php');
                exit;
            } else {
                // Failure
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['lockout_time'] = time() + (15 * 60); // 15 mins block
                    $error = 'Zbyt wiele nieudanych prób. Panel został zablokowany na 15 minut.';
                } else {
                    $remaining = 5 - $_SESSION['login_attempts'];
                    $error = "Błędne hasło. Pozostało prób: {$remaining}.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaloguj się — Panel Admina KM-BUD</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="../logo.ico">
</head>
<body class="h-full flex items-center justify-center p-4 antialiased selection:bg-red-500 selection:text-white">
    <div class="w-full max-w-md">
        <!-- Logo card container -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl flex flex-col items-center">
            
            <!-- Logo display -->
            <a href="../index.php" class="mb-6 transform hover:scale-105 transition-transform duration-300">
                <img src="../logo_kadr.png" alt="KM-BUD Ogrodzenia" class="h-24">
            </a>

            <h1 class="text-white text-xl font-bold text-center tracking-wide font-heading">
                Panel Administracyjny
            </h1>
            <p class="text-slate-400 text-xs text-center mt-1.5 mb-6">
                Zaloguj się, aby zarządzać zdjęciami w galerii
            </p>

            <?php if (!empty($error)): ?>
                <div class="w-full bg-red-950/40 border border-red-500/30 rounded-xl p-4 mb-5 flex items-start gap-3 animate-pulse">
                    <i data-lucide="alert-triangle" class="text-red-500 w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <p class="text-red-300 text-sm leading-relaxed"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($info)): ?>
                <div class="w-full bg-emerald-950/40 border border-emerald-500/30 rounded-xl p-4 mb-5 flex items-start gap-3">
                    <i data-lucide="check-circle-2" class="text-emerald-400 w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <p class="text-emerald-300 text-sm leading-relaxed"><?= htmlspecialchars($info) ?></p>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="post" class="w-full space-y-5">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCSRFToken()) ?>">

                <div class="relative">
                    <label for="password" class="block text-slate-300 text-xs font-semibold uppercase tracking-wider mb-2">
                        Hasło dostępu
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autofocus
                            placeholder="Wpisz hasło admina"
                            class="w-full bg-slate-950 border border-slate-800 rounded-xl py-3.5 pl-11 pr-4 text-white placeholder-slate-600 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all duration-300">
                        <i data-lucide="lock" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 w-5 h-5"></i>
                    </div>
                </div>

                <button type="submit" 
                    <?php if ($_SESSION['lockout_time'] > 0 && ($currentTime < $_SESSION['lockout_time'])): ?>disabled<?php endif; ?>
                    class="w-full bg-red-600 text-white font-bold py-3.5 rounded-xl hover:bg-red-500 shadow-lg hover:shadow-red-600/20 active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none transition-all duration-300 flex items-center justify-center gap-2">
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                    Zaloguj się
                </button>
            </form>

            <a href="../index.php" class="mt-8 text-xs text-slate-500 hover:text-slate-300 transition-colors flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                Powrót do strony głównej
            </a>

        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
