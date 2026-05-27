<?php
/**
 * 404 Not Found — Custom error page
 */
http_response_code(404);

$currentPage     = '404';
$pageTitle       = '404 — Nie znaleziono strony | KM-BUD Ogrodzenia';
$pageDescription = 'Strona o podanym adresie nie została odnaleziona w serwisie KM-BUD.';

require_once __DIR__ . '/includes/header.php';
?>

<div id="main-content" class="transition-all duration-300">
  <section class="py-20 px-4 bg-gray-50 flex items-center justify-center min-h-[60vh]">
    <div class="max-w-2xl w-full text-center">
      
      <!-- Card styling matching service overview blocks -->
      <div class="bg-white rounded-2xl p-8 md:p-12 border border-[var(--light-border-color)] shadow-lg hover:shadow-xl transition-all duration-300">
        
        <!-- Large display code in Outfit font -->
        <span class="font-heading font-black text-8xl md:text-9xl text-[var(--primary-color)]/25 tracking-tighter block mb-4 select-none" style="font-family: var(--font-family-heading);">
          404
        </span>

        <!-- Badge matching category tab icons -->
        <div class="w-16 h-16 bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-2xl flex items-center justify-center mx-auto mb-6 relative">
          <i data-lucide="file-question" class="w-8 h-8"></i>
        </div>

        <h1 class="font-heading text-3xl font-bold text-[var(--dark-text-color)] mb-4 tracking-tight" style="font-family: var(--font-family-heading);">
          Strona nie istnieje
        </h1>
        
        <p class="text-[var(--gray-text-color)] text-base md:text-lg leading-relaxed max-w-md mx-auto mb-8">
          Przepraszamy, ale strona o podanym adresie nie została odnaleziona w naszym serwisie. Sprawdź poprawność linku lub przejdź na stronę główną.
        </p>

        <!-- Dynamic Action Buttons matching the site's primary style -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <a href="index.php"
             class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-[var(--primary-button-hover-bg-color)] transition-all shadow-lg hover:shadow-xl w-full sm:w-auto">
            <i data-lucide="home" class="w-5 h-5"></i>
            Strona główna
          </a>
          <a href="galeria.php"
             class="inline-flex items-center justify-center gap-2 bg-white text-[var(--dark-text-color)] border border-[var(--light-border-color)] px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-gray-50 hover:shadow transition-all shadow-sm w-full sm:w-auto">
            <i data-lucide="images" class="w-5 h-5"></i>
            Zobacz galerię
          </a>
        </div>
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
