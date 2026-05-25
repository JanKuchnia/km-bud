<?php
/**
 * KM-BUD Galeria — Dynamic gallery from MySQL
 * Visual output IDENTICAL to galeria.html
 */
require_once __DIR__ . '/includes/db.php';

$db = getDB();

// Fetch categories ordered by sort_order
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Fetch all photos with category info
$photos = $db->query("
    SELECT p.*, c.slug AS category_slug, c.name AS category_name 
    FROM photos p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.sort_order, p.id
")->fetchAll();

// Build category label map for badge display
$categoryLabels = [
    'betonowe' => 'Z bloczków',
    'panelowe' => 'Panelowe',
    'siatka'   => 'Z siatki',
    'bramy'    => 'Bramy i furtki',
    'sprzety'  => 'Zaplecze maszynowe',
];

// Page meta
$currentPage     = 'galeria';
$pageTitle       = 'Galeria – KM-BUD Ogrodzenia | Myślenice';
$pageDescription = 'Zobacz nasze realizacje ogrodzeń panelowych, betonowych i z siatki w Myślenicach i okolicach. Galeria zdjęć montaży wykonanych przez KM-BUD.';
$ogTitle         = 'Galeria realizacji – KM-BUD Ogrodzenia';
$ogDescription   = 'Galeria naszych realizacji ogrodzeń panelowych, betonowych i z siatki. Profesjonalny montaż w Małopolsce.';

$extraHead = '
  <style>
    /* ── Lightbox ── */
    #lightbox {
      transition: opacity 0.25s ease, visibility 0.25s ease;
    }

    #lightbox.hidden-lb {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    #lightbox img {
      transition: transform 0.3s ease;
    }

    /* ── Gallery grid items ── */
    .gallery-item {
      break-inside: avoid;
      transition: transform 0.25s ease, opacity 0.3s ease;
    }

    .gallery-item.hidden-item {
      display: none;
    }

    .gallery-item img {
      transition: transform 0.4s ease;
    }

    .gallery-item:hover img {
      transform: scale(1.04);
    }

    /* ── Filter buttons ── */
    .filter-btn {
      transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
    }

    .filter-btn.active {
      background-color: var(--primary-color, #e53e3e) !important;
      color: #fff !important;
      border-color: var(--primary-color, #e53e3e) !important;
    }
  </style>';

require_once __DIR__ . '/includes/header.php';
?>

      <!-- ═══════════════ PAGE HERO ═══════════════ -->
      <section class="bg-[var(--dark-background-color)] py-16 px-4">
        <div class="max-w-7xl mx-auto text-center">
          <p class="text-[var(--accent2-color)] text-sm font-semibold tracking-widest uppercase mb-3">Nasze prace</p>
          <h1 class="text-white text-4xl sm:text-5xl font-bold mb-4">Galeria Realizacji</h1>
          <p class="text-white/60 text-lg max-w-2xl mx-auto">
            Każde ogrodzenie to osobna historia. Przeglądaj nasze dotychczasowe montaże – panelowe, betonowe, z siatki i
            podmurówki.
          </p>
        </div>
      </section>

      <!-- ═══════════════ GALLERY GRID ═══════════════ -->
      <section class="py-12 px-4 bg-gray-50 min-h-[60vh]">
        <div class="max-w-7xl mx-auto">

          <!-- ═══════════════ FILTER TABS ═══════════════ -->
          <div id="gallery-filters" class="scroll-mt-24 lg:scroll-mt-32 mb-10 flex flex-wrap gap-2.5 justify-center">
            <button
              class="filter-btn active flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="all">
              <i data-lucide="layout-grid" class="w-4 h-4"></i> Wszystkie
            </button>
<?php foreach ($categories as $cat): ?>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="<?= htmlspecialchars($cat['slug']) ?>">
              <i data-lucide="<?= htmlspecialchars($cat['icon']) ?>" class="w-4 h-4"></i> <?= htmlspecialchars($cat['name']) ?>
            </button>
<?php endforeach; ?>
          </div>

          <!-- Stats row -->
          <div class="flex flex-wrap justify-center gap-8 mb-10 text-center">
            <div>
              <p class="text-3xl font-bold text-[var(--dark-background-color)]">200+</p>
              <p class="text-gray-500 text-sm">Zrealizowanych projektów</p>
            </div>
            <div>
              <p class="text-3xl font-bold text-[var(--dark-background-color)]">5+</p>
              <p class="text-gray-500 text-sm">Lat doświadczenia</p>
            </div>
            <div>
              <p class="text-3xl font-bold text-[var(--dark-background-color)]">100%</p>
              <p class="text-gray-500 text-sm">Zadowolonych klientów</p>
            </div>
          </div>

          <!-- Masonry grid -->
          <div id="gallery-grid" class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-4">

<?php
$isFirst = true;
foreach ($photos as $photo):
    $catSlug  = htmlspecialchars($photo['category_slug']);
    $title    = htmlspecialchars($photo['title']);
    $desc     = htmlspecialchars($photo['description'] ?? '');
    $filename = htmlspecialchars($photo['filename']);
    $aspect   = htmlspecialchars($photo['aspect_class']);
    $label    = htmlspecialchars($categoryLabels[$photo['category_slug']] ?? $photo['category_name']);
    $loading  = $isFirst ? '' : ' loading="lazy"';
    $isFirst  = false;
?>
            <!-- Realization: <?= $photo['title'] ?> -->
            <div class="gallery-item" data-category="<?= $catSlug ?>" data-title="<?= $title ?>"
              data-desc="<?= $desc ?>">
              <div class="relative overflow-hidden rounded-xl bg-gray-200 cursor-zoom-in group shadow-md">
                <img src="images/<?= $filename ?>" alt="<?= $title ?>"
                  class="w-full <?= $aspect ?> object-cover"<?= $loading ?>>
                <div
                  class="absolute inset-0 bg-black/0 group-hover:bg-black/45 transition-all duration-300 flex items-end p-4">
                  <div
                    class="translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                    <span
                      class="inline-block bg-white/20 backdrop-blur text-white text-xs px-2 py-1 rounded-full mb-1"><?= $label ?></span>
                    <p class="text-white font-semibold text-sm"><?= $title ?></p>
                  </div>
                </div>
                <button onclick="openLightbox(this.closest('.gallery-item'))"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-zoom-in"
                  aria-label="Powiększ zdjęcie"></button>
              </div>
            </div>

<?php endforeach; ?>
          </div><!-- /gallery-grid -->

          <!-- Empty state -->
          <div id="empty-state" class="hidden text-center py-20 text-gray-400">
            <i data-lucide="image-off" class="w-12 h-12 mx-auto mb-4 opacity-40"></i>
            <p class="text-lg font-medium">Brak zdjęć w tej kategorii</p>
          </div>

        </div>
      </section>

      <!-- ═══════════════ CTA SECTION ═══════════════ -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>

    </div><!-- /frame-content -->
  </div><!-- /frame-root -->

  <!-- ═══════════════ LIGHTBOX ═══════════════ -->
  <div id="lightbox"
    class="hidden-lb fixed inset-0 z-[200] bg-black/90 backdrop-blur-sm flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-label="Podgląd zdjęcia">

    <!-- Close -->
    <button id="lb-close"
      class="absolute top-4 right-4 text-white/70 hover:text-white w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors"
      aria-label="Zamknij">
      <i data-lucide="x" class="w-6 h-6"></i>
    </button>

    <!-- Prev -->
    <button id="lb-prev"
      class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white w-12 h-12 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors"
      aria-label="Poprzednie zdjęcie">
      <i data-lucide="chevron-left" class="w-7 h-7"></i>
    </button>

    <!-- Next -->
    <button id="lb-next"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white w-12 h-12 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors"
      aria-label="Następne zdjęcie">
      <i data-lucide="chevron-right" class="w-7 h-7"></i>
    </button>

    <!-- Content -->
    <div class="max-w-3xl w-full">
      <!-- Placeholder panel (swaps to real <img> when real photos exist) -->
      <div id="lb-preview"
        class="w-full rounded-xl overflow-hidden bg-gray-900 flex items-center justify-center min-h-[300px]">
        <i data-lucide="image" class="w-16 h-16 text-white/20"></i>
      </div>
      <div id="lb-info" class="mt-4 text-center">
        <p id="lb-title" class="text-white font-semibold text-lg"></p>
        <p id="lb-desc" class="text-white/50 text-sm mt-1"></p>
      </div>
      <p id="lb-counter" class="text-white/30 text-xs text-center mt-2"></p>
    </div>
  </div>

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
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLightbox(); closeMenu(); } });

    /* ── Filter ── */
    const filterBtns = document.querySelectorAll('.filter-btn');
    const items = document.querySelectorAll('.gallery-item');
    const emptyState = document.getElementById('empty-state');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        let visible = 0;
        items.forEach(item => {
          const match = filter === 'all' || item.dataset.category === filter;
          item.classList.toggle('hidden-item', !match);
          if (match) visible++;
        });
        emptyState.classList.toggle('hidden', visible > 0);
      });
    });

    // Apply filter from URL query parameter if present
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    if (filterParam) {
      const targetBtn = document.querySelector(`.filter-btn[data-filter="${CSS.escape(filterParam)}"]`);
      if (targetBtn) {
        targetBtn.click();
        // Smooth scroll to gallery filters after loading
        setTimeout(() => {
          const galleryFilters = document.getElementById('gallery-filters');
          if (galleryFilters) {
            galleryFilters.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }, 100);
      }
    }

    /* ── Lightbox ── */
    const lightbox = document.getElementById('lightbox');
    const lbPreview = document.getElementById('lb-preview');
    const lbTitle = document.getElementById('lb-title');
    const lbDesc = document.getElementById('lb-desc');
    const lbCounter = document.getElementById('lb-counter');

    let currentIndex = 0;
    let visibleItems = [];

    function getVisible() {
      return [...items].filter(i => !i.classList.contains('hidden-item'));
    }

    function openLightbox(el) {
      visibleItems = getVisible();
      currentIndex = visibleItems.indexOf(el);
      showItem(currentIndex);
      lightbox.classList.remove('hidden-lb');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.add('hidden-lb');
      document.body.style.overflow = '';
    }

    function showItem(idx) {
      const item = visibleItems[idx];
      if (!item) return;
      lbTitle.textContent = item.dataset.title || '';
      lbDesc.textContent = item.dataset.desc || '';
      lbCounter.textContent = `${idx + 1} / ${visibleItems.length}`;
      // Dynamically load the real <img> element from the gallery item card
      const imgEl = item.querySelector('img');
      if (imgEl) {
        lbPreview.innerHTML = `<img src="${imgEl.getAttribute('src')}" alt="${imgEl.getAttribute('alt')}" class="max-h-[75vh] max-w-full mx-auto object-contain rounded-xl shadow-2xl animate-fade-in">`;
      } else {
        lbPreview.innerHTML = `<div class="w-full aspect-video bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center rounded-xl min-h-[300px]">
          <i data-lucide="image" style="width:4rem;height:4rem;color:rgba(255,255,255,0.15)"></i>
        </div>`;
      }
      lucide.createIcons();
    }

    document.getElementById('lb-close').addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', e => { if (e.target === lightbox) closeLightbox(); });

    document.getElementById('lb-prev').addEventListener('click', () => {
      currentIndex = (currentIndex - 1 + visibleItems.length) % visibleItems.length;
      showItem(currentIndex);
    });
    document.getElementById('lb-next').addEventListener('click', () => {
      currentIndex = (currentIndex + 1) % visibleItems.length;
      showItem(currentIndex);
    });
    document.addEventListener('keydown', e => {
      if (lightbox.classList.contains('hidden-lb')) return;
      if (e.key === 'ArrowLeft') { currentIndex = (currentIndex - 1 + visibleItems.length) % visibleItems.length; showItem(currentIndex); }
      if (e.key === 'ArrowRight') { currentIndex = (currentIndex + 1) % visibleItems.length; showItem(currentIndex); }
    });

    // Touch Swipe Support
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartY = 0;
    let touchEndY = 0;

    lightbox.addEventListener('touchstart', e => {
      touchStartX = e.changedTouches[0].screenX;
      touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    lightbox.addEventListener('touchend', e => {
      touchEndX = e.changedTouches[0].screenX;
      touchEndY = e.changedTouches[0].screenY;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const diffX = touchEndX - touchStartX;
      const diffY = touchEndY - touchStartY;

      // Standard sensitivity threshold of 50px, dominant horizontal movement
      if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
        if (diffX > 0) {
          // Swipe Right -> Show Prev
          currentIndex = (currentIndex - 1 + visibleItems.length) % visibleItems.length;
          showItem(currentIndex);
        } else {
          // Swipe Left -> Show Next
          currentIndex = (currentIndex + 1) % visibleItems.length;
          showItem(currentIndex);
        }
      }
    }
  </script>
</body>

</html>
