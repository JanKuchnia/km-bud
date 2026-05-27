<?php
/**
 * 500 Internal Server Error — Custom error page
 */
if (!headers_sent()) {
    http_response_code(500);
}

// Safely load environment if not already loaded
if (!function_exists('loadEnv')) {
    if (is_file(__DIR__ . '/config/env_loader.php')) {
        require_once __DIR__ . '/config/env_loader.php';
        loadEnv(__DIR__ . '/.env');
    }
}

// Only show technical details in development
$isDev = (getenv('APP_ENV') === 'development');

$currentPage     = '500';
$pageTitle       = '500 — Błąd serwera | KM-BUD Ogrodzenia';
$pageDescription = 'Napotkaliśmy nieoczekiwany problem po stronie serwera KM-BUD.';

require_once __DIR__ . '/includes/header.php';
?>

<div id="main-content" class="transition-all duration-300">
  <section class="py-20 px-4 bg-gray-50 flex items-center justify-center min-h-[60vh]">
    <div class="max-w-2xl w-full text-center">
      
      <!-- Warning styled card matching service overview blocks -->
      <div class="bg-white rounded-2xl p-8 md:p-12 border border-[var(--light-border-color)] shadow-lg hover:shadow-xl transition-all duration-300">
        
        <!-- Large warning code "500" in Outfit font -->
        <span class="font-heading font-black text-8xl md:text-9xl text-[var(--primary-color)]/25 tracking-tighter block mb-4 select-none" style="font-family: var(--font-family-heading);">
          500
        </span>

        <!-- Red badge matching 404 page style -->
        <div class="w-16 h-16 bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-2xl flex items-center justify-center mx-auto mb-6 relative">
          <i data-lucide="server-crash" class="w-8 h-8"></i>
        </div>

        <h1 class="font-heading text-3xl font-bold text-[var(--dark-text-color)] mb-4 tracking-tight" style="font-family: var(--font-family-heading);">
          Napotkaliśmy błąd serwera
        </h1>
        
        <p class="text-[var(--gray-text-color)] text-base md:text-lg leading-relaxed max-w-md mx-auto mb-8">
          Coś poszło nie tak po naszej stronie. Nasz zespół techniczny pracuje już nad rozwiązaniem problemu. Spróbuj odświeżyć stronę za chwilę.
        </p>

        <!-- Dynamic Action Buttons matching the site's primary style -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center <?php echo $isDev ? 'mb-8' : ''; ?>">
          <a href="javascript:location.reload()"
             class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] hover:bg-[var(--primary-button-hover-bg-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg transition-all shadow-lg hover:shadow-xl w-full sm:w-auto">
            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
            Odśwież stronę
          </a>
          <a href="index.php"
             class="inline-flex items-center justify-center gap-2 bg-white text-[var(--dark-text-color)] border border-[var(--light-border-color)] px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-gray-50 hover:shadow transition-all shadow-sm w-full sm:w-auto">
            <i data-lucide="home" class="w-5 h-5"></i>
            Strona główna
          </a>
        </div>

        <!-- Dev Mode Diagnostics Panel matching the website style -->
        <?php if ($isDev): 
            $error = error_get_last();
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            
            $hasDbError = isset($GLOBALS['DB_CONNECTION_ERROR']) ? $GLOBALS['DB_CONNECTION_ERROR'] : null;
            
            if ($hasDbError || ($error && in_array($error['type'], $fatalTypes, true))):
                $errMsg  = $hasDbError ? $hasDbError->getMessage() : $error['message'];
                $errFile = $hasDbError ? $hasDbError->getFile() : $error['file'];
                $errLine = $hasDbError ? $hasDbError->getLine() : $error['line'];
        ?>
          <div class="text-left bg-red-50/50 border border-red-200 rounded-xl p-6 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-[var(--primary-color)]"></div>
            
            <div class="flex items-center gap-2 mb-3">
              <i data-lucide="terminal" class="w-5 h-5 text-[var(--primary-color)]"></i>
              <span class="text-red-700 font-heading font-bold text-xs uppercase tracking-wider">Tryb deweloperski &mdash; diagnostyka</span>
            </div>

            <div class="space-y-3 text-xs text-gray-700">
              <div class="bg-white/80 rounded-lg p-3 border border-gray-200/50 shadow-sm">
                <span class="text-gray-400 block mb-1 uppercase font-bold tracking-widest text-[9px]">Komunikat</span>
                <span class="font-mono break-words leading-relaxed text-red-600"><?= htmlspecialchars($errMsg) ?></span>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                <div class="sm:col-span-3 bg-white/80 rounded-lg p-3 border border-gray-200/50 shadow-sm">
                  <span class="text-gray-400 block mb-1 uppercase font-bold tracking-widest text-[9px]">Plik</span>
                  <span class="font-mono break-all text-gray-600"><?= htmlspecialchars($errFile) ?></span>
                </div>
                <div class="bg-white/80 rounded-lg p-3 border border-gray-200/50 shadow-sm">
                  <span class="text-gray-400 block mb-1 uppercase font-bold tracking-widest text-[9px]">Linia</span>
                  <span class="font-mono font-bold text-[var(--primary-color)]"><?= htmlspecialchars($errLine) ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endif; endif; ?>

      </div>

    </div>
  </section>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

  <!-- ═══════════════ SCRIPTS ═══════════════ -->
  <script>
    lucide.createIcons();

    /* ── Mobile menu ── */
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenu = document.getElementById('mobile-menu');
    const backdrop = document.getElementById('mobile-menu-backdrop');
    const navLinks = document.querySelectorAll('.mobile-nav-link');

    function openMenu() {
      mobileMenu.classList.remove('translate-x-full');
      mobileMenu.classList.add('translate-x-0');
      backdrop.classList.remove('opacity-0', 'pointer-events-none');
      backdrop.classList.add('opacity-100');
      document.body.style.overflow = 'hidden';
      mobileMenuButton.setAttribute('aria-expanded', 'true');
    }
    function closeMenu() {
      mobileMenu.classList.add('translate-x-full');
      mobileMenu.classList.remove('translate-x-0');
      backdrop.classList.add('opacity-0', 'pointer-events-none');
      backdrop.classList.remove('opacity-100');
      document.body.style.overflow = '';
      mobileMenuButton.setAttribute('aria-expanded', 'false');
    }
    mobileMenuButton.addEventListener('click', openMenu);
    mobileMenuClose.addEventListener('click', closeMenu);
    backdrop.addEventListener('click', closeMenu);
    navLinks.forEach(l => l.addEventListener('click', closeMenu));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeMenu(); } });
  </script>
