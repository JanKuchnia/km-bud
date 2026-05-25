<?php
/**
 * Shared header include
 * 
 * Required variables before include:
 *   $pageTitle       - <title> tag content
 *   $pageDescription - meta description
 *   $ogTitle         - Open Graph title
 *   $ogDescription   - Open Graph description
 *   $currentPage     - 'index' or 'galeria' (for active nav state)
 * 
 * Optional:
 *   $extraHead       - additional <head> content (styles, etc.)
 */

$currentPage     = $currentPage ?? 'index';
$pageTitle       = $pageTitle ?? 'KM-BUD Ogrodzenia';
$pageDescription = $pageDescription ?? '';
$ogTitle         = $ogTitle ?? $pageTitle;
$ogDescription   = $ogDescription ?? $pageDescription;
$extraHead       = $extraHead ?? '';
?>
<!DOCTYPE html>
<html class="min-h-screen scroll-smooth" lang="pl">

<head>
  <meta charset="utf-8">
  <link rel="icon" href="logo.ico">
  <link rel="stylesheet" href="style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.tailwindcss.com"></script>

  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description"
    content="<?= htmlspecialchars($pageDescription) ?>">
<?php if ($currentPage === 'index'): ?>
  <meta name=language content=pl>
<?php endif; ?>
  <meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
  <meta property="og:description"
    content="<?= htmlspecialchars($ogDescription) ?>">
<?php if ($currentPage === 'index'): ?>
  <meta name=twitter:card content=summary>
  <meta name=twitter:title content="<?= htmlspecialchars($pageTitle) ?>">
  <meta name=twitter:description
    content="<?= htmlspecialchars($pageDescription) ?>">
<?php endif; ?>

<?= $extraHead ?>
</head>

<body class="min-h-screen bg-white antialiased [font-family:var(--font-family-body)]">
  <div class="frame-root">
    <div class="frame-content">
<?php if ($currentPage === 'index'): ?>
      <div class=[font-family:var(--font-family-body)]>
<?php endif; ?>
      <!-- ═══════════════ HEADER ═══════════════ -->
      <header id="global-header"
        class="code-section bg-black/95 backdrop-blur-md shadow-[0_4px_30px_rgba(0,0,0,0.2)] border-b border-white/5 sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center h-28 lg:h-36 transition-all duration-300">

            <a href="index.php" class="flex items-center transition-transform hover:scale-105 duration-300">
              <img src="logo_kadr.png" alt="KM-BUD Ogrodzenia" class="h-24 lg:h-32 py-2">
            </a>

            <nav class="hidden lg:flex items-center space-x-2">
              <a href="index.php#services-overview"
                class="text-white/90 hover:text-white px-4 py-2 rounded-full font-medium transition-all duration-300 hover:bg-white/10">Usługi</a>
              <a href="galeria.php"
                class="<?= $currentPage === 'galeria' ? 'bg-white/10 text-white' : 'text-white/90 hover:text-white hover:bg-white/10' ?> px-4 py-2 rounded-full font-medium transition-all duration-300">Galeria</a>
              <a href="index.php#why-choose-us"
                class="text-white/90 hover:text-white px-4 py-2 rounded-full font-medium transition-all duration-300 hover:bg-white/10">O
                Nas</a>
              <a href="index.php#contact"
                class="text-white/90 hover:text-white px-4 py-2 rounded-full font-medium transition-all duration-300 hover:bg-white/10">Kontakt</a>
              <a href="index.php#contact"
                class="ml-4 bg-[var(--accent2-color)] text-white px-6 py-2.5 rounded-[var(--button-rounded-radius)] font-semibold hover:bg-[var(--primary-button-hover-bg-color)] shadow-[0_4px_14px_rgba(220,38,38,0.3)] hover:shadow-[0_6px_20px_rgba(220,38,38,0.4)] hover:-translate-y-0.5 transition-all duration-300">Otrzymaj
                Wycenę</a>
            </nav>

            <button id="mobile-menu-button"
              class="lg:hidden flex items-center justify-center w-10 h-10 rounded-lg text-white hover:bg-white/10 transition-colors"
              aria-label="Otwórz menu" aria-expanded="false" aria-controls="mobile-menu">
              <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
          </div>
        </div>
      </header>

      <!-- Mobile menu backdrop -->
      <div id="mobile-menu-backdrop"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[90] opacity-0 pointer-events-none transition-opacity duration-300"
        aria-hidden="true"></div>

      <!-- Mobile menu drawer -->
      <div id="mobile-menu" role="dialog" aria-modal="true" aria-label="Menu nawigacyjne" class="fixed top-0 right-0 h-full w-[85%] max-w-sm z-[100] flex flex-col
                bg-[var(--dark-background-color)] shadow-2xl
                transform translate-x-full transition-transform duration-300 ease-in-out">
        <div class="flex items-center justify-between px-6 py-5 border-b border-white/10">
          <img src="logo_kadr.png" alt="KM-BUD" class="h-14">
          <button id="mobile-menu-close" aria-label="Zamknij menu"
            class="flex items-center justify-center w-10 h-10 rounded-lg text-white hover:bg-white/10 transition-colors">
            <i data-lucide="x" class="w-6 h-6"></i>
          </button>
        </div>
        <nav class="flex flex-col flex-1 px-4 py-6 space-y-1 overflow-y-auto">
          <a href="index.php#services-overview"
            class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-white/90 font-medium text-base hover:bg-white/10 hover:text-white transition-colors">
            <i data-lucide="grid-2x2" class="w-5 h-5 text-[var(--accent2-color)]"></i> Usługi
          </a>
          <a href="galeria.php"
            class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl <?= $currentPage === 'galeria' ? 'text-white font-medium text-base bg-white/10' : 'text-white/90 font-medium text-base hover:bg-white/10 hover:text-white' ?> transition-colors">
            <i data-lucide="image" class="w-5 h-5 text-[var(--accent2-color)]"></i> Galeria
          </a>
          <a href="index.php#why-choose-us"
            class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-white/90 font-medium text-base hover:bg-white/10 hover:text-white transition-colors">
            <i data-lucide="users" class="w-5 h-5 text-[var(--accent2-color)]"></i> O Nas
          </a>
          <a href="index.php#contact"
            class="mobile-nav-link flex items-center gap-3 px-4 py-3.5 rounded-xl text-white/90 font-medium text-base hover:bg-white/10 hover:text-white transition-colors">
            <i data-lucide="mail" class="w-5 h-5 text-[var(--accent2-color)]"></i> Kontakt
          </a>
        </nav>
        <div class="px-4 pb-8 pt-4 border-t border-white/10 space-y-3">
          <a href="index.php#contact"
            class="mobile-nav-link flex items-center justify-center gap-2 w-full bg-[var(--primary-color)] text-white px-6 py-4 rounded-xl font-bold text-base hover:bg-[var(--primary-button-hover-bg-color)] transition-colors">
            <i data-lucide="calculator" class="w-5 h-5"></i> Otrzymaj Wycenę
          </a>
          <a href="tel:+48 794 008 854"
            class="mobile-nav-link flex items-center justify-center gap-2 w-full border border-white/20 text-white px-6 py-3.5 rounded-xl font-medium text-sm hover:bg-white/10 transition-colors">
            <i data-lucide="phone" class="w-4 h-4"></i> Zadzwoń teraz
          </a>
        </div>
      </div>
