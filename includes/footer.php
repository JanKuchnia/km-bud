<?php
/**
 * Shared footer include
 * 
 * Uses $currentPage from header.php context
 * On index page: footer links use #anchors
 * On other pages: footer links use index.php#anchors
 */
$currentPage = $currentPage ?? 'index';
$indexPrefix = ($currentPage === 'index') ? '' : 'index.php';
?>
      <!-- ═══════════════ FOOTER ═══════════════ -->
      <footer id=global-footer class="code-section bg-[var(--dark-background-color)] text-white pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mb-12">

            <div class=lg:col-span-1>
              <a href="index.php" class="inline-block mb-4">
                <img src="logo_kadr.png" alt="KM-BUD Ogrodzenia" class="h-28">
              </a>
              <p class="text-gray-400 text-sm leading-relaxed">
                Lokalna firma z Myślenic specjalizująca się w kompleksowym
                wykonawstwie ogrodzeń. Solidne wykonanie i terminowość to nasze
                priorytety.
              </p>
            </div>

            <div>
              <h4 class="text-lg font-bold mb-4 text-white">Na skróty</h4>
              <ul class=space-y-3>
                <li>
                  <a href="index.php" class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Strona
                    główna</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#services-overview"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Usługi</a>
                </li>
                <li>
                  <a href="galeria.php"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Galeria</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#why-choose-us"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">O
                    nas</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Kontakt</a>
                </li>
              </ul>
            </div>

            <div>
              <h4 class="text-lg font-bold mb-4 text-white">Usługi</h4>
              <ul class=space-y-3>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Ogrodzenia
                    panelowe</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Ogrodzenia z
                    siatki</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Fundamenty i
                    podmurówki</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Ogrodzenia Joniec</a>
                </li>
                <li>
                  <a href="<?= $indexPrefix ?>#contact"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">Mury
                    oporowe</a>
                </li>
              </ul>
            </div>

            <div>
              <h4 class="text-lg font-bold mb-4 text-white">Kontakt</h4>
              <ul class=space-y-3>
                <li class="flex items-start gap-3">
                  <i data-lucide="phone" class="text-[var(--accent2-color)] mt-1"></i>
                  <a href="tel:+48 794 008 854"
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">+48 794 008 854</a>
                </li>
                <li class="flex items-start gap-3">
                  <i data-lucide="mail" class="text-[var(--accent2-color)] mt-1"></i>
                  <a href=mailto:km-bud.olszowice@wp.pl
                    class="text-gray-400 hover:text-[var(--accent2-color)] transition-colors">km-bud.olszowice@wp.pl</a>
                </li>
                <li class="flex items-start gap-3">
                  <i data-lucide="map-pin" class="text-[var(--accent2-color)] mt-1"></i>
                  <span class=text-gray-400><?= $currentPage === 'galeria' ? 'Z okolic Myślenic<br>Olszowice. Cicha 1' : 'Olszowice. Cicha 1' ?></span>
                </li>
              </ul>

              <div class="mt-6 flex gap-4">
                <a href="https://www.facebook.com/ogrodzenia.olszowice/" target="_blank" rel="noopener noreferrer"
                  class="w-10 h-10 bg-white/10 rounded-full flex items-center justify-center hover:bg-[var(--primary-color)] transition-colors"
                  aria-label="Facebook (KM-BUD)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="w-5 h-5">
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <div class="border-t border-white/10 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
              <p class="text-gray-500 text-sm">
                © <?= date('Y') ?> KM-BUD Ogrodzenia. Wszelkie prawa zastrzeżone.
              </p>
              <div class="flex gap-6 text-sm">
                <a href=# class="text-gray-500 hover:text-[var(--accent2-color)] transition-colors">Polityka
                  prywatności</a>
                <a href=# class="text-gray-500 hover:text-[var(--accent2-color)] transition-colors">Regulamin</a>
              </div>
            </div>
          </div>
        </div>
      </footer>
