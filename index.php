<?php
require_once __DIR__ . '/includes/db.php';
$db = getDB();

// Automatic background Google Reviews sync (runs at most once every 24 hours)
$lastSyncFile = __DIR__ . '/config/last_reviews_sync.txt';
$syncInterval = 86400; // 24 hours

if (!file_exists($lastSyncFile) || (time() - filemtime($lastSyncFile) > $syncInterval)) {
    require_once __DIR__ . '/includes/google_reviews_helper.php';
    $config = require __DIR__ . '/config/config.php';
    syncGoogleReviews($db, $config);
    @file_put_contents($lastSyncFile, time());
}

// Fetch 6 carousel photos
$carouselPhotos = $db->query("
    SELECT p.filename, p.title 
    FROM photos p 
    ORDER BY p.sort_order, p.id DESC 
    LIMIT 6
")->fetchAll();

// Fetch categories for services
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Fetch services
$services = $db->query("SELECT * FROM services ORDER BY sort_order")->fetchAll();

// Fetch all service slides
$allSlides = $db->query("
    SELECT ss.*, s.slug AS service_slug 
    FROM service_slides ss
    JOIN services s ON ss.service_id = s.id
    ORDER BY ss.sort_order
")->fetchAll();

// Group slides by service slug for JS
$serviceSlides = [];
foreach ($services as $service) {
    $serviceSlides[$service['slug']] = [];
}
foreach ($allSlides as $slide) {
    $slug = $slide['service_slug'];
    if (isset($serviceSlides[$slug])) {
        if ($slide['image']) {
            $serviceSlides[$slug][] = ['image' => $slide['image']];
        } else {
            $serviceSlides[$slug][] = [
                'gradient' => $slide['gradient'],
                'icon' => $slide['icon']
            ];
        }
    }
}

// Fetch equipment for park maszynowy
$equipment = $db->query("SELECT * FROM equipment ORDER BY sort_order")->fetchAll();

// Fetch 8 photos for homepage grid
$previewPhotos = $db->query("
    SELECT p.filename, p.title 
    FROM photos p 
    ORDER BY p.sort_order, p.id DESC 
    LIMIT 8
")->fetchAll();

$totalPhotos = (int) $db->query("SELECT COUNT(*) FROM photos")->fetchColumn();

// Fetch visible reviews
$reviews = $db->query("SELECT * FROM google_reviews WHERE is_visible = 1 ORDER BY id DESC")->fetchAll();

$currentPage     = 'index';
$pageTitle       = 'KM-BUD Ogrodzenia - Montaż Ogrodzeń Myślenice | Konrad Małucha';
$pageDescription = 'Profesjonalny montaż ogrodzeń w Myślenicach i okolicach. Ogrodzenia panelowe, betonowe, podmurówki. Szybkie terminy, solidne wykonanie. Zadzwoń: lokalna firma budowlana KM-BUD.';
$ogTitle         = 'KM-BUD Ogrodzenia - Solidne Ogrodzenia Myślenice | Montaż od A do Z';
$ogDescription   = 'Profesjonalny montaż ogrodzeń w Myślenicach i okolicach Małopolski. Ogrodzenia panelowe, betonowe, z siatki. Darmowa wycena. Lokalna firma z doświadczeniem.';

$extraHead = '
  <style>
    #service-modal {
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    #service-modal.hidden-modal {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }

    #service-modal.hidden-modal .modal-content-wrapper {
      transform: scale(0.95);
      opacity: 0;
    }

    #service-modal:not(.hidden-modal) .modal-content-wrapper {
      transform: scale(1);
      opacity: 1;
    }

    .modal-content-wrapper {
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease;
    }
  </style>';

require_once __DIR__ . '/includes/header.php';
?>

        <div id="main-content" class="transition-all duration-300">
          <section id=hero class="code-section relative min-h-[90vh] flex items-center">

            <div class="absolute inset-0 z-0">
              <img src="images/ogrodzenie-bloczkowe-01.webp" alt="Kompleksowe Ogrodzenia KM-BUD"
                class="w-full h-full object-cover">
              <div class="absolute inset-0 bg-gradient-to-r from-[#080808]/85 via-[#080808]/60 to-transparent"></div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-20 pb-16">
              <div class=max-w-2xl>

                <div
                  class="inline-flex items-center gap-2 bg-[var(--accent2-color)] text-white px-4 py-2 rounded-full text-sm font-semibold mb-6">
                  <i data-lucide="map-pin"></i>
                  <span>Myślenice i okolice</span>
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-tight mb-6"
                  style=font-family:var(--font-family-heading)>
                  Solidne ogrodzenia na lata – doradzimy, pomierzymy, zamontujemy wszystko od A do Z.
                </h1>

                <p class="text-lg md:text-xl text-gray-200 mb-8 leading-relaxed">
                  Działamy na terenie Myślenic i okolic. Szybkie terminy, solidne
                  wykonanie. Zaufaj doświadczonej firmie z Małopolski.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                  <a href="tel:+48 794 008 854"
                    class="inline-flex items-center justify-center gap-3 bg-[var(--accent2-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-[var(--primary-button-hover-bg-color)] transition-all transform hover:scale-105 shadow-lg">
                    <i data-lucide="phone"></i>
                    Zadzwoń teraz
                  </a>
                  <a href="#contact"
                    class="inline-flex items-center justify-center gap-3 bg-white text-[var(--dark-text-color)] px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                    <i data-lucide="calculator"></i>
                    Darmowa wycena
                  </a>
                </div>

                <div class="mt-10 flex flex-wrap items-center gap-6 text-gray-300 text-sm">
                  <div class="flex items-center gap-2">
                    <i data-lucide="circle-check" class="text-[var(--accent3-color)] text-lg"></i>
                    <span>15 lat doświadczenia</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <i data-lucide="circle-check" class="text-[var(--accent3-color)] text-lg"></i>
                    <span>500+ realizacji</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <i data-lucide="circle-check" class="text-[var(--accent3-color)] text-lg"></i>
                    <span>Gwarancja jakości</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="absolute bottom-0 left-0 right-0">
              <svg viewBox="0 0 1440 100" preserveAspectRatio="none" class="w-full h-12 md:h-20">
                <path d="M0 100V75C360 100 720 50 1080 75C1260 87 1380 95 1440 80V100H0Z" fill="white" />
              </svg>
            </div>
          </section>
          <section id=services-overview class="code-section py-16 md:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

              <div class="text-center mb-12 md:mb-16">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[var(--dark-text-color)] mb-4"
                  style=font-family:var(--font-family-heading)>
                  Nasze usługi
                </h2>
                <p class="text-lg text-[var(--gray-text-color)] max-w-2xl mx-auto">
                  Oferujemy kompleksowe usługi związane z ogrodzeniami – od projektu po
                  montaż. Sprawdź, co możemy dla Ciebie zrobić.
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                <?php
                foreach ($services as $serv):
                    $slug = htmlspecialchars($serv['slug']);
                ?>
                    <div data-service-category="<?= $slug ?>"
                      class="service-card cursor-pointer bg-[var(--light-background-color)] rounded-2xl p-6 md:p-8 hover:shadow-xl hover:border-[var(--primary-color)]/30 transition-all duration-300 group border border-[var(--light-border-color)]">
                      <div
                        class="w-14 h-14 bg-[var(--primary-color)] rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                        <i data-lucide="<?= htmlspecialchars($serv['icon']) ?>" class="text-white text-2xl"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-3">
                        <?= htmlspecialchars($serv['name']) ?>
                      </h3>
                      <p class="text-[var(--gray-text-color)] mb-4">
                        <?= htmlspecialchars($serv['description']) ?>
                      </p>
                      <a href="galeria.php?filter=<?= $slug ?>"
                        class="service-link text-[var(--primary-color)] font-semibold hover:underline inline-flex items-center gap-2">
                        Zobacz zdjęcia <i data-lucide="camera"></i>
                      </a>
                    </div>
                <?php endforeach; ?>
              </div>

              <div class="text-center mt-12">
                <a href="#contact"
                  class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-[var(--primary-button-hover-bg-color)] transition-all shadow-lg hover:shadow-xl">
                  <i data-lucide="phone"></i>
                  Skontaktuj się po darmową wycenę
                </a>
              </div>
            </div>
          </section>

          <section id=why-choose-us class="code-section py-16 md:py-24 bg-[var(--light-background-color)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                <div class="relative w-full h-[400px] md:h-[500px]">
                  <!-- Carousel Container -->
                  <div id="realizations-carousel"
                    class="relative w-full h-full rounded-2xl overflow-hidden shadow-2xl bg-neutral-950">
                    <!-- Slides Container -->
                    <div class="relative w-full h-full">
                      <?php if (!empty($carouselPhotos)): ?>
                        <?php foreach ($carouselPhotos as $index => $cPhoto): ?>
                          <div
                            class="carousel-slide absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out <?= $index === 0 ? 'opacity-100 z-10' : 'opacity-0 pointer-events-none z-0' ?>"
                            data-slide-index="<?= $index ?>">
                            <img src="images/<?= htmlspecialchars($cPhoto['filename']) ?>"
                              alt="<?= htmlspecialchars($cPhoto['title']) ?>"
                              class="w-full h-full object-cover object-center animate-fade-in">
                          </div>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <!-- Fallback static slides -->
                        <div class="carousel-slide absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out opacity-100 z-10" data-slide-index="0">
                          <img src="images/ogrodzenie-bloczkowe-01.webp" alt="Nowoczesne ogrodzenie frontowe z bloczków łupanych Joniec" class="w-full h-full object-cover object-center">
                        </div>
                        <div class="carousel-slide absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out opacity-0 pointer-events-none z-0" data-slide-index="1">
                          <img src="images/ogrodzenie-siatkowe-01.webp" alt="Trwałe i precyzyjne ogrodzenie z siatki plecionej" class="w-full h-full object-cover object-center">
                        </div>
                        <div class="carousel-slide absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out opacity-0 pointer-events-none z-0" data-slide-index="2">
                          <img src="images/brama-furtka-01.webp" alt="Solidna brama przesuwna stalowa z automatyką" class="w-full h-full object-cover object-center">
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Navigation Arrows -->
                    <button id="carousel-prev"
                      class="absolute left-3 top-1/2 -translate-y-1/2 z-20 bg-neutral-950/40 hover:bg-[var(--primary-color)] text-white w-8 h-8 rounded-full flex items-center justify-center backdrop-blur-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]">
                      <i data-lucide="chevron-left" class="w-5 h-5"></i>
                    </button>
                    <button id="carousel-next"
                      class="absolute right-3 top-1/2 -translate-y-1/2 z-20 bg-neutral-950/40 hover:bg-[var(--primary-color)] text-white w-8 h-8 rounded-full flex items-center justify-center backdrop-blur-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]">
                      <i data-lucide="chevron-right" class="w-5 h-5"></i>
                    </button>

                    <!-- Carousel Indicators (Dots) -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                      <?php 
                      $dotCount = !empty($carouselPhotos) ? count($carouselPhotos) : 3;
                      for ($d = 0; $d < $dotCount; $d++): 
                      ?>
                        <button
                          class="carousel-dot w-2 h-2 rounded-full <?= $d === 0 ? 'bg-white' : 'bg-white/40 hover:bg-white' ?> transition-all duration-300"
                          data-dot-index="<?= $d ?>" aria-label="Idź do slajdu <?= $d + 1 ?>"></button>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <!-- 15 lat badge (overlay) -->
                  <div
                    class="absolute -bottom-6 -right-6 bg-white rounded-xl shadow-2xl p-6 max-w-xs hidden md:block z-30">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-[var(--accent3-color)] rounded-full flex items-center justify-center">
                        <i data-lucide="award" class="text-white text-xl"></i>
                      </div>
                      <div>
                        <p class="text-2xl font-bold text-[var(--dark-text-color)]">
                          15 lat
                        </p>
                        <p class="text-sm text-[var(--gray-text-color)]">doświadczenia</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[var(--dark-text-color)] mb-4"
                    style=font-family:var(--font-family-heading)>
                    Dlaczego KM-BUD?
                  </h2>
                  <p class="text-lg text-[var(--gray-text-color)] mb-8">
                    Jesteśmy lokalną firmą z Myślenic z wieloletnim doświadczeniem w
                    branży ogrodzeń. Stawiamy na jakość i zadowolenie klientów.
                  </p>

                  <div class=space-y-5>
                    <div class="flex items-start gap-4">
                      <div
                        class="w-10 h-10 bg-[var(--primary-color)] rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <i data-lucide="check" class="text-white"></i>
                      </div>
                      <div>
                        <h3 class="text-lg font-bold text-[var(--dark-text-color)]">
                          Doświadczenie i profesjonalizm
                        </h3>
                        <p class=text-[var(--gray-text-color)]>
                          500+ zrealizowanych projektów na terenie Małopolski. Znamy się
                          na tym, co robimy.
                        </p>
                      </div>
                    </div>
                    <div class="flex items-start gap-4">
                      <div
                        class="w-10 h-10 bg-[var(--primary-color)] rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <i data-lucide="check" class="text-white"></i>
                      </div>
                      <div>
                        <h3 class="text-lg font-bold text-[var(--dark-text-color)]">
                          Lokalna firma
                        </h3>
                        <p class=text-[var(--gray-text-color)]>
                          Działamy w Myślenicach i okolicach. Znamy lokalne warunki
                          gruntowe i potrzeby klientów.
                        </p>
                      </div>
                    </div>
                    <div class="flex items-start gap-4">
                      <div
                        class="w-10 h-10 bg-[var(--primary-color)] rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <i data-lucide="check" class="text-white"></i>
                      </div>
                      <div>
                        <h3 class="text-lg font-bold text-[var(--dark-text-color)]">
                          Szybkie terminy realizacji
                        </h3>
                        <p class=text-[var(--gray-text-color)]>
                          Rozumiemy, że czas ma znaczenie. Realizujemy zlecenia terminowo
                          i sprawnie.
                        </p>
                      </div>
                    </div>
                    <div class="flex items-start gap-4">
                      <div
                        class="w-10 h-10 bg-[var(--primary-color)] rounded-lg flex items-center justify-center flex-shrink-0 mt-1">
                        <i data-lucide="check" class="text-white"></i>
                      </div>
                      <div>
                        <h3 class="text-lg font-bold text-[var(--dark-text-color)]">
                          Gwarancja jakości
                        </h3>
                        <p class=text-[var(--gray-text-color)]>
                          Używamy najwyższej jakości materiałów. Oferujemy gwarancję na
                          nasze realizacje.
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class=mt-8>
                    <a href="#why-choose-us"
                      class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-[var(--primary-button-hover-bg-color)] transition-all shadow-lg hover:shadow-xl">
                      <i data-lucide="user"></i>
                      Poznaj nas lepiej
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Machinery Park (Park Maszynowy) -->
          <section id="machinery-fleet"
            class="code-section py-16 md:py-24 bg-white border-t border-[var(--light-border-color)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div class="text-center mb-12 md:mb-16">
                <span
                  class="text-sm font-bold uppercase tracking-wider text-[var(--primary-color)] px-4 py-1.5 bg-[var(--primary-color)]/10 rounded-full">Nasz
                  Sprzęt</span>
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[var(--dark-text-color)] mt-4 mb-4"
                  style="font-family: var(--font-family-heading)">
                  Park Maszynowy
                </h2>
                <p class="text-lg text-[var(--gray-text-color)] max-w-2xl mx-auto">
                  Dysponujemy nowoczesnym, profesjonalnym sprzętem, który pozwala nam na samodzielną i sprawną
                  realizację każdego etapu prac – od precyzyjnych wykopów po profesjonalny montaż.
                </p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($equipment as $item): ?>
                <!-- Machine card -->
                <div
                  class="bg-[var(--light-background-color)] rounded-2xl overflow-hidden hover:shadow-2xl hover:-translate-y-3 transition-all duration-500 group border border-[var(--light-border-color)] flex flex-col justify-between relative">
                  <div class="relative h-48 w-full overflow-hidden bg-neutral-900">
                    <div
                      class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-500 z-10">
                    </div>
                    <img src="images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                      class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
                    <?php if (!empty($item['badge'])): ?>
                    <span
                      class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm text-[var(--primary-color)] font-bold text-[10px] uppercase tracking-wider px-3 py-1 rounded-full shadow-sm z-20 border border-[var(--primary-color)]/10"><?= htmlspecialchars($item['badge']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="p-6 pt-8 md:p-8 md:pt-10 flex-grow flex flex-col justify-between relative">
                    <div
                      class="absolute -top-7 left-6 w-14 h-14 bg-white rounded-2xl shadow-lg flex items-center justify-center border border-[var(--light-border-color)] group-hover:border-[var(--primary-color)]/30 group-hover:scale-115 transition-all duration-500 z-30">
                      <div
                        class="w-10 h-10 bg-neutral-950 rounded-xl flex items-center justify-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[var(--primary-color)]"></div>
                        <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="text-white text-xl"></i>
                      </div>
                    </div>
                    <div>
                      <h3
                        class="text-xl font-bold text-[var(--dark-text-color)] mb-3 group-hover:text-[var(--primary-color)] transition-colors duration-300 font-heading">
                        <?= htmlspecialchars($item['name']) ?>
                      </h3>
                      <p class="text-[var(--gray-text-color)] text-sm leading-relaxed mb-6">
                        <?= htmlspecialchars($item['description']) ?>
                      </p>
                    </div>
                    <?php if (!empty($item['spec_1']) || !empty($item['spec_2'])): ?>
                    <div class="border-t border-[var(--light-border-color)] pt-5 mt-auto">
                      <span
                        class="text-[10px] font-bold uppercase tracking-wider text-[var(--gray-text-color)]/60 block mb-3">Specyfikacja
                        techniczna</span>
                      <ul class="space-y-2.5 text-xs text-[var(--gray-text-color)]">
                        <?php if (!empty($item['spec_1'])): ?>
                        <li class="flex items-center gap-2.5">
                          <span
                            class="w-2 h-2 rounded-full bg-[var(--primary-color)] opacity-80 shadow-sm flex-shrink-0"></span>
                          <span class="font-medium"><?= htmlspecialchars($item['spec_1']) ?></span>
                        </li>
                        <?php endif; ?>
                        <?php if (!empty($item['spec_2'])): ?>
                        <li class="flex items-center gap-2.5">
                          <span
                            class="w-2 h-2 rounded-full bg-[var(--primary-color)] opacity-80 shadow-sm flex-shrink-0"></span>
                          <span class="font-medium"><?= htmlspecialchars($item['spec_2']) ?></span>
                        </li>
                        <?php endif; ?>
                      </ul>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>

          <section class="code-section py-16 md:py-24 bg-white">
            <span id="gallery-preview"></span>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div class="text-center mb-12 md:mb-16">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[var(--dark-text-color)] mb-4"
                  style=font-family:var(--font-family-heading)>
                  Nasze realizacje
                </h2>
                <p class="text-lg text-[var(--gray-text-color)] max-w-2xl mx-auto">
                  Zobacz przykłady naszych projektów. Każde ogrodzenie wykonujemy z
                  najwyższą starannością.
                </p>
              </div>
              <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php if (!empty($previewPhotos)): ?>
                  <?php 
                  $count = count($previewPhotos);
                  foreach ($previewPhotos as $index => $photo): 
                      $isLastCard = ($index === 7 && $totalPhotos > 8);
                  ?>
                    <div class="aspect-square rounded-xl overflow-hidden group cursor-pointer relative"
                      onclick="window.location.href='galeria.php'">
                      <img src="images/<?= htmlspecialchars($photo['filename']) ?>" alt="<?= htmlspecialchars($photo['title']) ?>"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                      
                      <?php if ($isLastCard): ?>
                        <!-- Show +X overlay on the last card -->
                        <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center transition-all duration-300">
                          <span class="text-white font-bold text-3xl">+<?= $totalPhotos - 7 ?></span>
                          <span class="text-white/80 text-xs font-semibold mt-1">zobacz więcej</span>
                        </div>
                      <?php else: ?>
                        <!-- Standard hover state -->
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-center justify-center">
                          <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-bold border border-white/40 px-4 py-2 rounded-full text-sm backdrop-blur-sm">Powiększ galerię</span>
                        </div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <!-- Premium Empty-State Container if database is empty -->
                  <div class="col-span-full bg-white rounded-2xl p-8 md:p-12 text-center border border-[var(--light-border-color)] shadow-sm">
                    <div class="w-16 h-16 bg-[var(--primary-color)]/10 text-[var(--primary-color)] rounded-full flex items-center justify-center mx-auto mb-4">
                      <i data-lucide="images" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-2">Aktualizacja Galerii</h3>
                    <p class="text-[var(--gray-text-color)] max-w-md mx-auto">
                      Trwają prace nad aktualizacją galerii naszych najnowszych realizacji. Zapraszamy do sprawdzenia nas wkrótce!
                    </p>
                  </div>
                <?php endif; ?>
              </div>

              <div class="text-center mt-10">
                <a href="galeria.php"
                  class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] text-white px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-[var(--primary-button-hover-bg-color)] transition-all shadow-lg hover:shadow-xl">
                  <i data-lucide="images"></i>
                  Zobacz więcej realizacji
                </a>
              </div>
            </div>
          </section>
          <!-- ═══════════════ TESTIMONIALS (GOOGLE REVIEWS) ═══════════════ -->
          <section id="testimonials" class="code-section py-16 md:py-24 bg-[var(--light-background-color)] overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              
              <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 md:mb-16 gap-6">
                <div>
                  <span class="text-sm font-bold uppercase tracking-wider text-[var(--primary-color)] px-4 py-1.5 bg-[var(--primary-color)]/10 rounded-full">
                    Opinie Naszych Klientów
                  </span>
                  <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[var(--dark-text-color)] mt-4 mb-2"
                    style="font-family: var(--font-family-heading)">
                    Co mówią o nas w Google?
                  </h2>
                  <p class="text-lg text-[var(--gray-text-color)] max-w-2xl">
                    Duma z zadowolenia naszych klientów to siła napędowa KM-BUD. Sprawdź zweryfikowane opinie z naszej wizytówki Google.
                  </p>
                </div>
                
                <!-- Sleek Asymmetric Navigation Arrows -->
                <div id="reviews-nav" class="flex gap-3 self-start md:self-end">
                  <button id="reviews-prev" aria-label="Poprzednie opinie" class="w-12 h-12 rounded-xl border border-[var(--light-border-color)] bg-white text-[var(--dark-text-color)] hover:bg-neutral-50 hover:border-neutral-300 active:scale-95 transition-all flex items-center justify-center shadow-sm disabled:opacity-50 disabled:pointer-events-none group">
                    <i data-lucide="chevron-left" class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform"></i>
                  </button>
                  <button id="reviews-next" aria-label="Następne opinie" class="w-12 h-12 rounded-xl border border-[var(--light-border-color)] bg-white text-[var(--dark-text-color)] hover:bg-neutral-50 hover:border-neutral-300 active:scale-95 transition-all flex items-center justify-center shadow-sm disabled:opacity-50 disabled:pointer-events-none group">
                    <i data-lucide="chevron-right" class="w-5 h-5 group-hover:translate-x-0.5 transition-transform"></i>
                  </button>
                </div>
              </div>

              <!-- Reviews Slider Window Container -->
              <div class="relative overflow-hidden w-full -mx-4 px-4 py-2">
                <!-- Reviews Track Container -->
                <div id="reviews-track" class="flex transition-transform duration-500 ease-out select-none cursor-grab active:cursor-grabbing will-change-transform">
                  <?php 
                  $reviewsToDisplay = !empty($reviews) ? array_slice($reviews, 0, 8) : [
                      ['author_name' => 'Marcin K.', 'rating' => 5, 'review_time' => '2 miesiące temu', 'review_text' => 'Firma KM-BUD robiła u mnie ogrodzenie panelowe z podmurówką. Wszystko sprawnie, terminowo i bardzo solidnie. Ekipa z profesjonalnym sprzętem, dbają o porządek. Szczerze polecam!', 'is_manual' => 0],
                      ['author_name' => 'Anna Nowak', 'rating' => 5, 'review_time' => '1 miesiąc temu', 'review_text' => 'Ogrodzenie z bloczków betonowych wykonane z dbałością o najmniejszy szczegół. Bardzo miły kontakt, doradztwo techniczne na najwyższym poziomie. Na pewno wrócimy przy kolejnym etapie prac.', 'is_manual' => 0],
                      ['author_name' => 'Tomasz Myślenice', 'rating' => 5, 'review_time' => '3 tygodnie temu', 'review_text' => 'Błyskawiczny montaż bramy przesuwnej z automatyką. Wszystko działa bez zarzutu, wykonane estetycznie i profesjonalnie. Sprzęt budowlany robi wrażenie, praca idzie błyskawicznie.', 'is_manual' => 0]
                  ];

                  foreach ($reviewsToDisplay as $index => $rev):
                      $colors = ['bg-blue-600 text-white', 'bg-red-600 text-white', 'bg-amber-600 text-white', 'bg-emerald-600 text-white', 'bg-purple-600 text-white', 'bg-slate-600 text-white'];
                      $avatarBg = $colors[$index % count($colors)];
                  ?>
                    <!-- Slide Item wrapper -->
                    <div class="w-full md:w-1/2 lg:w-1/3 flex-shrink-0 px-3 md:px-4 flex">
                      <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-[var(--light-border-color)] hover:shadow-xl transition-all duration-300 flex flex-col justify-between w-full h-full">
                        <div>
                          <!-- Quote indicator and Stars -->
                          <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-0.5 text-amber-400">
                              <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i data-lucide="star" class="w-4 h-4 <?= $s <= $rev['rating'] ? 'fill-amber-400' : 'text-slate-200' ?>"></i>
                              <?php endfor; ?>
                            </div>
                            <i data-lucide="quote" class="w-8 h-8 text-red-600/10"></i>
                          </div>

                          <p class="text-[var(--gray-text-color)] text-sm leading-relaxed italic mb-6">
                            "<?= htmlspecialchars($rev['review_text']) ?>"
                          </p>
                        </div>

                        <!-- Author Details -->
                        <div class="border-t border-[var(--light-border-color)] pt-4 flex items-center justify-between mt-auto">
                          <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full <?= $avatarBg ?> flex items-center justify-center font-bold text-sm shadow-sm overflow-hidden flex-shrink-0">
                              <?php if (!empty($rev['author_photo'])): ?>
                                <img src="<?= htmlspecialchars($rev['author_photo']) ?>" alt="<?= htmlspecialchars($rev['author_name']) ?>" class="w-full h-full object-cover" draggable="false">
                              <?php else: ?>
                                <?= mb_substr(htmlspecialchars($rev['author_name']), 0, 1) ?>
                              <?php endif; ?>
                            </div>
                            <div>
                              <h4 class="font-bold text-[var(--dark-text-color)] text-sm leading-tight"><?= htmlspecialchars($rev['author_name']) ?></h4>
                              <span class="text-[10px] text-[var(--gray-text-color)]/60"><?= htmlspecialchars($rev['review_time']) ?></span>
                            </div>
                          </div>

                          <!-- Verified Badge -->
                          <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full text-[9px] font-bold border border-blue-100 uppercase tracking-wider">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i>
                            Zweryfikowano
                          </span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Call to Action -->
              <div class="text-center mt-12">
                <a href="https://www.google.com/maps/place/KM-BUD+Konrad+Ma%C5%82ucha/@49.8829631,20.0039234,17z/data=!4m8!3m7!1s0x471641d4c849e7b3:0xee14ded7cc4fb7fc!8m2!3d49.8829631!4d20.0039234!9m1!1b1!16s%2Fg%2F11wdfn6j9x?entry=ttu" target="_blank" rel="noopener noreferrer"
                  class="inline-flex items-center justify-center gap-2 bg-white text-[var(--dark-text-color)] border border-[var(--light-border-color)] px-8 py-4 rounded-[var(--button-rounded-radius)] font-bold text-lg hover:bg-gray-50 hover:shadow transition-all shadow-sm">
                  <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#EA4335"/>
                  </svg>
                  Zobacz lub napisz opinię w Google
                </a>
              </div>

            </div>
          </section>

          <section id=contact class="code-section py-16 md:py-24 bg-[var(--dark-background-color)] relative overflow-hidden">

            <div class="absolute inset-0 opacity-5">
              <div class="absolute inset-0"
                style="background-image:url(data:image/svg+xml,%3Csvg\ width=\'60\'\ height=\'60\'\ viewBox=\'0\ 0\ 60\ 60\'\ xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg\ fill=\'none\'\ fill-rule=\'evenodd\'%3E%3Cg\ fill=\'%23ffffff\'\ fill-opacity=\'1\'%3E%3Cpath\ d=\'M36\ 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6\ 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6\ 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E)">
              </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">

                <div>
                  <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4"
                    style=font-family:var(--font-family-heading)>
                    Skontaktuj się z nami
                  </h2>
                  <p class="text-lg text-gray-300 mb-8">
                    Potrzebujesz wyceny ogrodzenia? Zadzwoń do nas lub wypełnij formularz.
                    Odpowiemy najszybciej jak to możliwe.
                  </p>

                  <div class=space-y-6>

                    <div class="flex items-center gap-4">
                      <div class="w-14 h-14 bg-[var(--primary-color)] rounded-xl flex items-center justify-center">
                        <i data-lucide="phone" class="text-white text-xl"></i>
                      </div>
                      <div>
                        <p class="text-sm text-gray-400">Zadzwoń do nas</p>
                        <a href="tel:+48 794 008 854"
                          class="text-xl font-bold text-white hover:text-[var(--accent2-color)] transition-colors">
                          +48 794 008 854
                        </a>
                      </div>
                    </div>

                    <div class="flex items-center gap-4">
                      <div class="w-14 h-14 bg-[var(--primary-color)] rounded-xl flex items-center justify-center">
                        <i data-lucide="mail" class="text-white text-xl"></i>
                      </div>
                      <div>
                        <p class="text-sm text-gray-400">Napisz e-mail</p>
                        <a href=mailto:km-bud.olszowice@wp.pl
                          class="text-xl font-bold text-white hover:text-[var(--accent2-color)] transition-colors">
                          km-bud.olszowice@wp.pl
                        </a>
                      </div>
                    </div>

                    <div class="flex items-center gap-4">
                      <div class="w-14 h-14 bg-[var(--primary-color)] rounded-xl flex items-center justify-center">
                        <i data-lucide="map-pin" class="text-white text-xl"></i>
                      </div>
                      <div>
                        <p class="text-sm text-gray-400">Lokalizacja</p>
                        <p class="text-xl font-bold text-white">
                          Olszowice. Cicha 1
                        </p>
                      </div>
                    </div>

                    <div class="flex items-center gap-4">
                      <div class="w-14 h-14 bg-[var(--primary-color)] rounded-xl flex items-center justify-center">
                        <i data-lucide="clock" class="text-white text-xl"></i>
                      </div>
                      <div>
                        <p class="text-sm text-gray-400">Godziny pracy</p>
                        <p class="text-xl font-bold text-white">Pon - Pt: 8:00 - 18:00</p>
                      </div>
                    </div>
                  </div>

                  <div class="mt-8 p-4 bg-white/10 rounded-xl backdrop-blur-sm">
                    <p class="text-white font-semibold mb-2">
                      <i data-lucide="zap" class="text-[var(--accent2-color)] mr-2"></i>
                      Potrzebujesz szybkiej wyceny?
                    </p>
                    <a href="tel:+48 794 008 854" class="text-[var(--accent2-color)] font-bold hover:underline">
                      Zadzwoń teraz: +48 794 008 854
                    </a>
                  </div>
                </div>

                <div class="bg-white rounded-2xl p-3 md:p-4 shadow-2xl overflow-hidden">
                  <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d164767.886712533!2d19.831215376487254!3d49.81430035941124!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47166944282277e9%3A0xc5766a0c557c53a0!2sKM-BUD%20OGRODZENIA!5e0!3m2!1spl!2spl!4v1773869189053!5m2!1spl!2spl"
                    width="100%" height="600" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade" class="rounded-xl w-full"></iframe>
                </div>
              </div>
            </div>
          </section>
        </div> <!-- Close #main-content -->
      </div> <!-- Close the [font-family:var(--font-family-body)] opened in header.php -->
      <div> <!-- Wrapper for footer -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
      </div> <!-- Close wrapper for footer -->
    </div> <!-- Close frame-content opened in header.php -->
    <div class="frame-content"></div>
  </div> <!-- Close frame-root opened in header.php -->

  <!-- ═══════════════ SERVICE DETAILS MODAL ═══════════════ -->
  <div id="service-modal"
    class="hidden-modal fixed inset-0 z-[200] bg-black/90 backdrop-blur-md flex items-center justify-center p-4"
    role="dialog" aria-modal="true" aria-label="Szczegóły usługi">

    <!-- Close button -->
    <button id="modal-close"
      class="absolute top-4 right-4 text-white/70 hover:text-white w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-[210]"
      aria-label="Zamknij">
      <i data-lucide="x" class="w-6 h-6"></i>
    </button>

    <!-- Prev -->
    <button id="modal-prev"
      class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white w-12 h-12 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-[210]"
      aria-label="Poprzednie zdjęcie">
      <i data-lucide="chevron-left" class="w-7 h-7"></i>
    </button>

    <!-- Next -->
    <button id="modal-next"
      class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white w-12 h-12 flex items-center justify-center rounded-full hover:bg-white/10 transition-colors z-[210]"
      aria-label="Następne zdjęcie">
      <i data-lucide="chevron-right" class="w-7 h-7"></i>
    </button>

    <!-- Modal Content Wrapper -->
    <div class="modal-content-wrapper max-w-4xl w-full flex flex-col items-center justify-center relative">
      <!-- Slide Preview Area (Tylko zdjęcie / gradient i ikona) -->
      <div id="modal-slide-preview"
        class="w-full aspect-[4/3] sm:aspect-[16/10] md:aspect-[16/9] rounded-2xl overflow-hidden shadow-2xl relative bg-neutral-900 flex items-center justify-center select-none">
      </div>
      <!-- Indicators -->
      <div id="modal-dots" class="mt-4 flex gap-2 justify-center z-[210]"></div>

      <!-- Gallery link button (Option A) -->
      <div class="mt-8 text-center z-[210]">
        <a id="modal-gallery-btn" href="#"
          class="inline-flex items-center justify-center gap-2 bg-[var(--primary-color)] text-white px-8 py-3.5 rounded-[var(--button-rounded-radius)] font-bold text-base hover:bg-[var(--primary-button-hover-bg-color)] transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 active:translate-y-0 duration-200">
          Przejdź do galerii <i data-lucide="images"></i>
        </a>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

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
    navLinks.forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMenu(); });

    // ═══════════════ CAROUSEL LOGIC ═══════════════
    const carouselSection = document.getElementById('realizations-carousel');
    const slides = document.querySelectorAll('.carousel-slide');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;
    let slideInterval;
    const intervalTime = 5000;

    function goToSlide(index) {
      if (!slides.length) return;
      slides[currentSlide].classList.remove('opacity-100', 'z-10');
      slides[currentSlide].classList.add('opacity-0', 'pointer-events-none', 'z-0');
      if (dots[currentSlide]) {
        dots[currentSlide].classList.remove('bg-white');
        dots[currentSlide].classList.add('bg-white/40');
      }

      currentSlide = (index + slides.length) % slides.length;

      slides[currentSlide].classList.remove('opacity-0', 'pointer-events-none', 'z-0');
      slides[currentSlide].classList.add('opacity-100', 'z-10');
      if (dots[currentSlide]) {
        dots[currentSlide].classList.remove('bg-white/40');
        dots[currentSlide].classList.add('bg-white');
      }
    }

    function nextSlide() { goToSlide(currentSlide + 1); }
    function prevSlide() { goToSlide(currentSlide - 1); }

    function startSlideShow() {
      slideInterval = setInterval(nextSlide, intervalTime);
    }
    function stopSlideShow() {
      clearInterval(slideInterval);
    }

    if (nextBtn) nextBtn.addEventListener('click', () => { stopSlideShow(); nextSlide(); startSlideShow(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { stopSlideShow(); prevSlide(); startSlideShow(); });
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => { stopSlideShow(); goToSlide(index); startSlideShow(); });
    });

    if (carouselSection) {
      carouselSection.addEventListener('mouseenter', stopSlideShow);
      carouselSection.addEventListener('mouseleave', startSlideShow);

      // Swipe logic for carousel
      let touchStartX = 0;
      let touchEndX = 0;
      carouselSection.addEventListener('touchstart', e => touchStartX = e.changedTouches[0].screenX);
      carouselSection.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 50) { stopSlideShow(); nextSlide(); startSlideShow(); }
        if (touchEndX - touchStartX > 50) { stopSlideShow(); prevSlide(); startSlideShow(); }
      });
    }

    // Init carousel
    if (slides.length > 0) {
      slides.forEach(s => s.classList.add('opacity-0', 'pointer-events-none', 'z-0'));
      slides[currentSlide].classList.remove('opacity-0', 'pointer-events-none', 'z-0');
      slides[currentSlide].classList.add('opacity-100', 'z-10');
      dots.forEach(d => d.classList.remove('bg-white'));
      if (dots[currentSlide]) dots[currentSlide].classList.add('bg-white');
      startSlideShow();
    }

    // ═══════════════ SERVICE MODAL LOGIC ═══════════════
    const serviceModal = document.getElementById('service-modal');
    const modalCloseBtn = document.getElementById('modal-close');
    const modalPrevBtn = document.getElementById('modal-prev');
    const modalNextBtn = document.getElementById('modal-next');
    const modalSlidePreview = document.getElementById('modal-slide-preview');
    const modalDotsContainer = document.getElementById('modal-dots');

    const serviceSlides = <?= json_encode($serviceSlides, JSON_UNESCAPED_SLASHES) ?>;

    let activeModalSlides = [];
    let currentModalSlide = 0;

    function renderModalSlide() {
      if (!activeModalSlides || activeModalSlides.length === 0) return;
      const slide = activeModalSlides[currentModalSlide];

      if (slide.image) {
        modalSlidePreview.className = "w-full aspect-[4/3] sm:aspect-[16/10] md:aspect-[16/9] rounded-2xl overflow-hidden shadow-2xl relative select-none bg-neutral-900";
        modalSlidePreview.innerHTML = `<img src="${slide.image}" alt="Realizacja KM-BUD" class="w-full h-full object-cover animate-fade-in">`;
      } else {
        modalSlidePreview.className = `w-full aspect-[4/3] sm:aspect-[16/10] md:aspect-[16/9] rounded-2xl overflow-hidden shadow-2xl relative flex items-center justify-center select-none ${slide.gradient}`;
        modalSlidePreview.innerHTML = `<i data-lucide="${slide.icon}" class="w-32 h-32 md:w-48 md:h-48 text-white/20"></i>`;
        lucide.createIcons();
      }

      // Update dots
      Array.from(modalDotsContainer.children).forEach((dot, index) => {
        dot.className = index === currentModalSlide
          ? 'w-2.5 h-2.5 rounded-full bg-white transition-colors'
          : 'w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white/60 transition-colors';
      });
    }

    function openServiceModal(category) {
      if (!serviceSlides[category]) return;
      activeModalSlides = serviceSlides[category];
      currentModalSlide = 0;

      // Update gallery button href dynamically
      const galleryBtn = document.getElementById('modal-gallery-btn');
      if (galleryBtn) {
        galleryBtn.href = `galeria.php?filter=${category}`;
      }

      // Generate dots
      modalDotsContainer.innerHTML = '';
      activeModalSlides.forEach((_, idx) => {
        const dot = document.createElement('button');
        dot.setAttribute('aria-label', `Idź do slajdu ${idx + 1}`);
        dot.className = 'w-2.5 h-2.5 rounded-full bg-white/40 hover:bg-white/60 transition-colors';
        dot.onclick = () => { currentModalSlide = idx; renderModalSlide(); };
        modalDotsContainer.appendChild(dot);
      });

      renderModalSlide();
      serviceModal.classList.remove('hidden-modal');
      document.body.style.overflow = 'hidden';
    }

    function closeServiceModal() {
      serviceModal.classList.add('hidden-modal');
      document.body.style.overflow = '';
    }

    function nextModalSlide() {
      if (!activeModalSlides || activeModalSlides.length === 0) return;
      currentModalSlide = (currentModalSlide + 1) % activeModalSlides.length;
      renderModalSlide();
    }

    function prevModalSlide() {
      if (!activeModalSlides || activeModalSlides.length === 0) return;
      currentModalSlide = (currentModalSlide - 1 + activeModalSlides.length) % activeModalSlides.length;
      renderModalSlide();
    }

    // Bind cards and links to open modal
    document.querySelectorAll('.service-card').forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.closest('.service-link')) {
          e.preventDefault(); // Prevent direct navigation, open modal instead
        }
        const category = card.getAttribute('data-service-category');
        openServiceModal(category);
      });
    });

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeServiceModal);
    if (modalNextBtn) modalNextBtn.addEventListener('click', nextModalSlide);
    if (modalPrevBtn) modalPrevBtn.addEventListener('click', prevModalSlide);
    if (serviceModal) serviceModal.addEventListener('click', (e) => {
      if (e.target === serviceModal) closeServiceModal();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (!serviceModal || serviceModal.classList.contains('hidden-modal')) return;
      if (e.key === 'Escape') closeServiceModal();
      if (e.key === 'ArrowRight') nextModalSlide();
      if (e.key === 'ArrowLeft') prevModalSlide();
    });

    // Swipe logic for modal
    let modalTouchStartX = 0;
    let modalTouchEndX = 0;
    if (serviceModal) {
      serviceModal.addEventListener('touchstart', e => modalTouchStartX = e.changedTouches[0].screenX);
      serviceModal.addEventListener('touchend', e => {
        modalTouchEndX = e.changedTouches[0].screenX;
        if (modalTouchStartX - modalTouchEndX > 50) nextModalSlide();
        if (modalTouchEndX - modalTouchStartX > 50) prevModalSlide();
      });
    }

    // ═══════════════ REVIEWS SLIDER LOGIC ═══════════════
    const reviewsTrack = document.getElementById('reviews-track');
    const reviewsPrevBtn = document.getElementById('reviews-prev');
    const reviewsNextBtn = document.getElementById('reviews-next');
    
    if (reviewsTrack && reviewsPrevBtn && reviewsNextBtn) {
      const slides = Array.from(reviewsTrack.children);
      const totalSlides = slides.length;
      let currentSlide = 0;
      
      let isDragging = false;
      let startX = 0;
      let currentTranslate = 0;
      let prevTranslate = 0;
      let dragOffset = 0;
      
      function getVisibleCards() {
        if (window.innerWidth >= 1024) return 3; // Desktop
        if (window.innerWidth >= 768) return 2;  // Tablet
        return 1;                                // Mobile
      }
      
      function getSlideWidthPercent() {
        return 100 / getVisibleCards();
      }
      
      function getMaxSlideIndex() {
        return Math.max(0, totalSlides - getVisibleCards());
      }
      
      function updateSliderPosition(withTransition = true) {
        const slideWidthPercent = getSlideWidthPercent();
        
        // Boundaries checks
        if (currentSlide < 0) currentSlide = 0;
        const maxIndex = getMaxSlideIndex();
        if (currentSlide > maxIndex) currentSlide = maxIndex;
        
        const translatePercent = -currentSlide * slideWidthPercent;
        currentTranslate = translatePercent;
        prevTranslate = translatePercent;
        
        if (withTransition) {
          reviewsTrack.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
        } else {
          reviewsTrack.style.transition = 'none';
        }
        
        reviewsTrack.style.transform = `translateX(${translatePercent}%)`;
        
        // Update navigation button disabled states
        reviewsPrevBtn.disabled = currentSlide === 0;
        reviewsNextBtn.disabled = currentSlide >= maxIndex;
      }
      
      // Arrow navigation
      reviewsNextBtn.addEventListener('click', () => {
        const maxIndex = getMaxSlideIndex();
        if (currentSlide < maxIndex) {
          currentSlide++;
          updateSliderPosition();
        }
      });
      
      reviewsPrevBtn.addEventListener('click', () => {
        if (currentSlide > 0) {
          currentSlide--;
          updateSliderPosition();
        }
      });
      
      // Swipe and drag gestures
      function touchStart(event) {
        isDragging = true;
        startX = getPositionX(event);
        reviewsTrack.style.transition = 'none';
        reviewsTrack.style.cursor = 'grabbing';
      }
      
      function touchMove(event) {
        if (!isDragging) return;
        const currentX = getPositionX(event);
        const diffX = currentX - startX;
        
        // Calculate dynamic translation in relative percentage
        const trackWidth = reviewsTrack.offsetWidth;
        if (trackWidth === 0) return;
        
        const diffPercent = (diffX / trackWidth) * 100;
        
        // Add elastic drag resistance at margins
        let nextTranslate = prevTranslate + diffPercent;
        const maxIndex = getMaxSlideIndex();
        const slideWidthPercent = getSlideWidthPercent();
        const minBound = 0;
        const maxBound = -maxIndex * slideWidthPercent;
        
        if (nextTranslate > minBound) {
          nextTranslate = minBound + (nextTranslate - minBound) * 0.3;
        } else if (nextTranslate < maxBound) {
          nextTranslate = maxBound + (nextTranslate - maxBound) * 0.3;
        }
        
        reviewsTrack.style.transform = `translateX(${nextTranslate}%)`;
        dragOffset = diffX;
      }
      
      function touchEnd() {
        if (!isDragging) return;
        isDragging = false;
        reviewsTrack.style.cursor = 'grab';
        
        const threshold = 50; // swipe threshold in px
        
        if (Math.abs(dragOffset) > threshold) {
          if (dragOffset < 0) {
            // Swiped left -> Next
            const maxIndex = getMaxSlideIndex();
            if (currentSlide < maxIndex) {
              currentSlide++;
            }
          } else {
            // Swiped right -> Prev
            if (currentSlide > 0) {
              currentSlide--;
            }
          }
        }
        
        dragOffset = 0;
        updateSliderPosition();
      }
      
      function getPositionX(event) {
        return event.type.includes('mouse') ? event.clientX : event.touches[0].clientX;
      }
      
      // Event bindings for Touch
      reviewsTrack.addEventListener('touchstart', touchStart, { passive: true });
      reviewsTrack.addEventListener('touchmove', touchMove, { passive: true });
      reviewsTrack.addEventListener('touchend', touchEnd);
      
      // Event bindings for Mouse drag (Desktop experience)
      reviewsTrack.addEventListener('mousedown', touchStart);
      reviewsTrack.addEventListener('mousemove', touchMove);
      reviewsTrack.addEventListener('mouseup', touchEnd);
      reviewsTrack.addEventListener('mouseleave', touchEnd);
      
      // Prevent browser default behaviors
      reviewsTrack.addEventListener('dragstart', (e) => e.preventDefault());
      
      // Resize support
      window.addEventListener('resize', () => {
        updateSliderPosition(false);
      });
      
      // Initialize layout
      updateSliderPosition(false);
    }
  </script>
</body>

</html>
