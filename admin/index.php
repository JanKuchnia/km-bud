<?php
/**
 * KM-BUD Admin Panel — Main Dashboard
 * Fully responsive premium panel managing photos, categories, drag & drop sorting
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Force authentication
requireAuth();

$db = getDB();

// Fetch categories
$categories = $db->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

// Fetch all photos
$photos = $db->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug 
    FROM photos p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.sort_order, p.id DESC
")->fetchAll();

// Statistics
$totalPhotos = count($photos);
$totalCategories = count($categories);

// Fetch all reviews
$reviews = $db->query("SELECT * FROM google_reviews ORDER BY id DESC")->fetchAll();
$totalReviews = count($reviews);

// Fetch services
$services = $db->query("SELECT * FROM services ORDER BY sort_order")->fetchAll();
$totalServices = count($services);

// Fetch equipment
$equipment = $db->query("SELECT * FROM equipment ORDER BY sort_order")->fetchAll();
$totalEquipment = count($equipment);

// Fetch slides by service_id
$allServiceSlides = $db->query("SELECT * FROM service_slides ORDER BY sort_order")->fetchAll();
$slidesByService = [];
foreach ($allServiceSlides as $slide) {
    $slidesByService[$slide['service_id']][] = $slide;
}

// Automatically fetch photos from gallery and append to corresponding service slides
$galleryPhotos = $db->query("
    SELECT p.id AS photo_id, p.filename, c.slug AS category_slug 
    FROM photos p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.sort_order, p.id DESC
")->fetchAll();

$serviceIdBySlug = [];
foreach ($services as $serv) {
    $serviceIdBySlug[$serv['slug']] = $serv['id'];
}

foreach ($galleryPhotos as $photo) {
    $slug = $photo['category_slug'];
    if (isset($serviceIdBySlug[$slug])) {
        $serviceId = $serviceIdBySlug[$slug];
        $imagePath = 'images/' . $photo['filename'];
        
        // Prevent duplicate entries
        $exists = false;
        if (isset($slidesByService[$serviceId])) {
            foreach ($slidesByService[$serviceId] as $existingSlide) {
                if (isset($existingSlide['image']) && $existingSlide['image'] === $imagePath) {
                    $exists = true;
                    break;
                }
            }
        }
        if (!$exists) {
            $slidesByService[$serviceId][] = [
                'id' => 'gallery_' . $photo['photo_id'],
                'service_id' => $serviceId,
                'image' => $imagePath,
                'gradient' => null,
                'icon' => null,
                'sort_order' => 9999,
                'is_gallery_auto' => true
            ];
        }
    }
}


// Lucide standard icons list for dropdown selection
$lucideIcons = [
    'rectangle-horizontal' => 'Prostokąt',
    'square' => 'Kwadrat',
    'grid-3x3' => 'Siatka',
    'door-open' => 'Brama',
    'truck' => 'Ciężarówka',
    'image' => 'Obraz',
    'layers' => 'Warstwy',
    'align-justify' => 'Sztachety',
    'shield' => 'Tarcza',
    'construction' => 'Budowa',
    'mountain' => 'Góry',
    'drill' => 'Wiertło',
    'shovel' => 'Łopata'
];
?>
<!DOCTYPE html>
<html lang="pl" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora — KM-BUD</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Sortable.js for Drag and Drop sorting -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="icon" href="../logo.ico">
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }
        .sortable-ghost {
            opacity: 0.4;
            border: 2px dashed #ef4444;
        }
    </style>
</head>
<body class="h-full flex overflow-hidden selection:bg-red-500 selection:text-white">

    <!-- ─────────────────────────────────────────────────────────────
    SIDEBAR (DARK THEME)
    ───────────────────────────────────────────────────────────── -->
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-overlay" onclick="closeSidebar()" class="hidden fixed inset-0 bg-black/60 z-35 backdrop-blur-xs lg:hidden transition-opacity duration-300 opacity-0"></div>

    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-64 bg-slate-900 border-r border-slate-800 flex flex-col flex-shrink-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Close Button for Mobile Sidebar -->
        <div class="lg:hidden absolute top-4 right-4 z-50">
            <button onclick="closeSidebar()" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition-all" title="Zamknij menu">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-center border-b border-slate-800 px-6">
            <a href="../index.php" class="flex items-center gap-2">
                <img src="../logo_kadr.png" alt="KM-BUD Logo" class="h-12 py-1">
                <span class="text-white text-base font-bold tracking-wider">KM-BUD</span>
            </a>
        </div>

        <!-- Sidebar Navigation -->
        <nav class="flex-grow py-6 px-4 space-y-2">
            <button onclick="switchTab('photos')" id="tab-btn-photos"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 bg-red-600 text-white active-spring-scale">
                <i data-lucide="image" class="w-5 h-5"></i>
                Zdjęcia
            </button>
            <button onclick="switchTab('categories')" id="tab-btn-categories"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white active-spring-scale">
                <i data-lucide="folder-tree" class="w-5 h-5"></i>
                Kategorie
            </button>
            <button onclick="switchTab('reviews')" id="tab-btn-reviews"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white active-spring-scale">
                <i data-lucide="star" class="w-5 h-5"></i>
                Opinie Google
            </button>
            <button onclick="switchTab('services')" id="tab-btn-services"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white active-spring-scale">
                <i data-lucide="grid-2x2" class="w-5 h-5"></i>
                Usługi
            </button>
            <button onclick="switchTab('equipment')" id="tab-btn-equipment"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white active-spring-scale">
                <i data-lucide="shovel" class="w-5 h-5"></i>
                Park Maszynowy
            </button>
            <a href="../galeria.php" target="_blank"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold text-slate-400 hover:bg-slate-800 hover:text-white transition-all duration-200 active-spring-scale">
                <i data-lucide="external-link" class="w-5 h-5"></i>
                Podgląd strony
            </a>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-slate-800">
            <a href="logout.php"
                class="w-full flex items-center justify-center gap-2 bg-slate-850 border border-slate-800 hover:bg-red-950/20 hover:border-red-500/20 hover:text-red-400 text-slate-400 py-3 rounded-xl text-xs font-bold transition-all duration-200 active-spring-scale">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                Wyloguj panel
            </a>
        </div>
    </aside>

    <!-- ─────────────────────────────────────────────────────────────
    MAIN CONTENT AREA
    ───────────────────────────────────────────────────────────── -->
    <main class="flex-grow flex flex-col min-w-0 overflow-hidden bg-slate-50">
        <!-- Top bar Header -->
        <header class="h-20 border-b border-slate-200 bg-white flex items-center justify-between px-4 sm:px-6 lg:px-8 flex-shrink-0">
            <div class="flex items-center gap-3">
                <!-- Hamburger toggle button for Mobile -->
                <button onclick="openSidebar()" class="lg:hidden p-2 text-slate-500 hover:text-slate-850 hover:bg-slate-100 rounded-xl transition-all active-spring-scale" title="Otwórz menu">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div>
                    <h2 id="tab-title" class="text-lg sm:text-xl font-bold text-slate-850 truncate max-w-[180px] sm:max-w-none">Zdjęcia</h2>
                    <p id="tab-subtitle" class="text-[10px] sm:text-xs text-slate-400 mt-0.5 truncate max-w-[200px] sm:max-w-none">Zarządzaj zdjęciami w galerii realizacji</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden sm:inline-block text-xs font-semibold bg-slate-100 text-slate-600 px-3 py-1.5 rounded-full">
                    Wersja PHP 1.0 (XAMPP local)
                </span>
            </div>
        </header>

        <!-- Dynamic Content Scroll Wrapper -->
        <div class="flex-grow overflow-y-auto p-4 sm:p-6 lg:p-8 custom-scrollbar">

            <!-- ─────────────────────────────────────────────────────────────
            TAB: PHOTOS
            ───────────────────────────────────────────────────────────── -->
            <div id="tab-content-photos" class="space-y-8 animate-fade-in">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 flex-shrink-0">
                            <i data-lucide="images" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800"><?= $totalPhotos ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Wszystkich zdjęć</p>
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 flex-shrink-0">
                            <i data-lucide="folder" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800"><?= $totalCategories ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Kategorii</p>
                        </div>
                    </div>
                    <!-- Action box -->
                    <div class="bg-white border border-slate-200/60 p-3 sm:p-4 rounded-2xl shadow-sm flex items-center justify-between col-span-1">
                        <button onclick="openModal('add-photo')"
                            class="w-full h-full bg-red-600 hover:bg-red-500 text-white font-bold py-3.5 px-6 rounded-xl shadow-md hover:shadow-red-600/10 active-spring-scale transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                            Dodaj nowe zdjęcie
                        </button>
                    </div>
                </div>

                <!-- Photos Grid Container -->
                <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Zdjęcia w galerii</h3>
                            <p class="text-xs text-slate-450 mt-0.5">Przeciągnij karty, aby zmienić kolejność wyświetlania na stronie</p>
                        </div>
                    </div>

                    <?php if ($totalPhotos === 0): ?>
                        <div class="text-center py-20 text-slate-400">
                            <i data-lucide="image-off" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
                            <p class="text-base font-medium">Brak zdjęć w galerii. Kliknij przycisk powyżej, aby dodać pierwsze zdjęcie.</p>
                        </div>
                    <?php else: ?>
                        <!-- Grid with drag handles -->
                        <div id="photos-sortable-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($photos as $photo): ?>
                                <div data-id="<?= $photo['id'] ?>" 
                                     class="photo-card bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col justify-between group relative">
                                    
                                    <!-- Image Preview area with category badge -->
                                    <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                                        <img src="../images/<?= htmlspecialchars($photo['filename']) ?>" 
                                             alt="<?= htmlspecialchars($photo['title']) ?>"
                                             class="w-full h-full object-cover">
                                        <span class="absolute top-3 left-3 bg-black/60 backdrop-blur-sm text-white font-bold text-[9px] uppercase tracking-wider px-2.5 py-1 rounded-full">
                                            <?= htmlspecialchars($photo['category_name']) ?>
                                        </span>
                                        <span class="absolute top-3 right-3 bg-red-650 text-white font-semibold text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-md border border-red-500/20 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <?= htmlspecialchars($photo['aspect_class']) ?>
                                        </span>
                                    </div>

                                    <!-- Content detail -->
                                    <div class="p-4 flex-grow flex flex-col justify-between">
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm truncate"><?= htmlspecialchars($photo['title']) ?></h4>
                                            <p class="text-slate-400 text-xs mt-1.5 line-clamp-2 leading-relaxed">
                                                <?= !empty($photo['description']) ? htmlspecialchars($photo['description']) : 'Brak opisu.' ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Grab Drag Header + Action buttons -->
                                    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                                        <!-- Drag handle -->
                                        <div class="drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 transition-colors p-1"
                                             title="Chwyć, aby przenieść">
                                            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                                        </div>
                                        
                                        <!-- Edit and delete actions -->
                                        <div class="flex items-center gap-1">
                                            <button onclick="openEditPhotoModal(<?= htmlspecialchars(json_encode($photo)) ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all active-spring-scale"
                                                    title="Edytuj">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="confirmDeletePhoto(<?= $photo['id'] ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active-spring-scale"
                                                    title="Usuń">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <!-- ─────────────────────────────────────────────────────────────
            TAB: CATEGORIES
            ───────────────────────────────────────────────────────────── -->
            <div id="tab-content-categories" class="hidden space-y-6 lg:space-y-8 animate-fade-in">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Left: Create Category Form -->
                    <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm h-fit">
                        <h3 class="text-base font-bold text-slate-800 mb-5">Dodaj nową kategorię</h3>
                        
                        <form id="add-category-form" onsubmit="submitCategory(event)" class="space-y-4">
                            <div>
                                <label for="cat-name" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Nazwa kategorii</label>
                                <input type="text" id="cat-name" required placeholder="np. Ogrodzenia Joniec"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-red-500 transition-all duration-300">
                            </div>
                            
                            <div>
                                <label for="cat-slug" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Unikalny identyfikator (slug)</label>
                                <input type="text" id="cat-slug" required placeholder="np. joniec"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-red-500 transition-all duration-300">
                                <p class="text-[10px] text-slate-400 mt-1 leading-normal">Tylko małe litery bez spacji (np. 'panelowe', 'bramy') pasujące do filtrów.</p>
                            </div>

                            <div>
                                <label for="cat-icon" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                                <select id="cat-icon" required
                                    class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                                    <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                                        <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit"
                                class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-md hover:shadow-red-600/10 active-spring-scale flex items-center justify-center gap-2">
                                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                                Dodaj kategorię
                            </button>
                        </form>
                    </div>

                    <!-- Right: Categories List Table -->
                    <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm lg:col-span-2">
                        <h3 class="text-base font-bold text-slate-800 mb-5">Lista istniejących kategorii</h3>

                        <div class="overflow-x-auto responsive-table-container rounded-2xl border border-slate-100/80">
                            <table class="w-full min-w-[500px]">
                                <thead>
                                    <tr class="text-left border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider pb-3">
                                        <th class="pb-3 pl-4">Kategoria</th>
                                        <th class="pb-3">Slug (identyfikator)</th>
                                        <th class="pb-3 text-center">Ikona</th>
                                        <th class="pb-3 text-right pr-4">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($categories as $cat): ?>
                                        <tr class="text-sm text-slate-750">
                                            <td class="py-4 pl-4 font-bold text-slate-800"><?= htmlspecialchars($cat['name']) ?></td>
                                            <td class="py-4"><span class="font-mono text-xs bg-slate-50 px-2.5 py-1 rounded w-fit border border-slate-200/40"><?= htmlspecialchars($cat['slug']) ?></span></td>
                                            <td class="py-4 text-center">
                                                <div class="w-9 h-9 bg-slate-100 rounded-lg flex items-center justify-center text-slate-650 mx-auto"
                                                     title="<?= htmlspecialchars($cat['icon']) ?>">
                                                    <i data-lucide="<?= htmlspecialchars($cat['icon']) ?>" class="w-4 h-4"></i>
                                                </div>
                                            </td>
                                            <td class="py-4 pr-4 text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    <button onclick="openEditCategoryModal(<?= htmlspecialchars(json_encode($cat)) ?>)"
                                                            class="p-2.5 sm:p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all active-spring-scale"
                                                            title="Edytuj">
                                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                    </button>
                                                    <button onclick="confirmDeleteCategory(<?= $cat['id'] ?>)"
                                                            class="p-2.5 sm:p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active-spring-scale"
                                                            title="Usuń">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ─────────────────────────────────────────────────────────────
            TAB: REVIEWS
            ───────────────────────────────────────────────────────────── -->
            <div id="tab-content-reviews" class="hidden space-y-6 lg:space-y-8 animate-fade-in">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 lg:gap-6">
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 flex-shrink-0">
                            <i data-lucide="star" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800"><?= $totalReviews ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Wszystkich opinii</p>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 flex-shrink-0">
                            <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800">
                                <?php
                                $visibleCount = 0;
                                foreach ($reviews as $rev) {
                                    if ($rev['is_visible']) $visibleCount++;
                                }
                                echo $visibleCount;
                                ?>
                            </p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Wyświetlanych na stronie</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white border border-slate-200/60 p-3 sm:p-4 rounded-2xl shadow-sm flex flex-col sm:flex-row items-center justify-center gap-3 col-span-1">
                        <button onclick="fetchGoogleReviews()" id="fetch-btn"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 px-4 rounded-xl shadow-sm active-spring-scale transition-all flex items-center justify-center gap-2 text-xs">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            Pobierz z Google
                        </button>
                        <button onclick="openModal('add-review')"
                            class="w-full bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-4 rounded-xl shadow-md hover:shadow-red-600/10 active-spring-scale transition-all flex items-center justify-center gap-2 text-xs">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            Dodaj ręcznie
                        </button>
                    </div>
                </div>

                <!-- Reviews Grid -->
                <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm">
                    <h3 class="text-base font-bold text-slate-800 mb-6">Opinie i referencje</h3>

                    <?php if ($totalReviews === 0): ?>
                        <div class="text-center py-20 text-slate-400">
                            <i data-lucide="message-square-off" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
                            <p class="text-base font-medium">Brak opinii w bazie danych. Kliknij przycisk powyżej, aby dodać lub pobrać opinie.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 flex flex-col justify-between hover:shadow-md transition-shadow relative">
                                    
                                    <!-- Top author details -->
                                    <div>
                                        <div class="flex items-start justify-between gap-2 mb-4">
                                            <div class="flex items-center gap-3">
                                                <!-- Avatar -->
                                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-650 font-bold overflow-hidden border border-slate-200 flex-shrink-0">
                                                    <?php if (!empty($rev['author_photo'])): ?>
                                                        <img src="<?= htmlspecialchars($rev['author_photo']) ?>" alt="<?= htmlspecialchars($rev['author_name']) ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <?= mb_substr(htmlspecialchars($rev['author_name']), 0, 1) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-800 text-sm truncate max-w-[120px] sm:max-w-[150px]"><?= htmlspecialchars($rev['author_name']) ?></h4>
                                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($rev['review_time']) ?></p>
                                                </div>
                                            </div>

                                            <!-- Source Badge -->
                                            <?php if ($rev['is_manual']): ?>
                                                <span class="bg-slate-100 text-slate-600 font-bold text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-md border border-slate-200 flex items-center gap-1 flex-shrink-0">
                                                    <i data-lucide="edit-2" class="w-2.5 h-2.5"></i>
                                                    Ręczna
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-blue-50 text-blue-600 font-bold text-[9px] uppercase tracking-wider px-2 py-0.5 rounded-md border border-blue-100 flex items-center gap-1 flex-shrink-0">
                                                    <i data-lucide="globe" class="w-2.5 h-2.5"></i>
                                                    Google
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Rating Stars -->
                                        <div class="flex items-center gap-0.5 mb-3 text-amber-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i data-lucide="star" class="w-4 h-4 <?= $i <= $rev['rating'] ? 'fill-amber-400' : 'text-slate-200' ?>"></i>
                                            <?php endfor; ?>
                                        </div>

                                        <!-- Testimonial body -->
                                        <p class="text-slate-600 text-xs leading-relaxed line-clamp-4 italic mb-4">
                                            "<?= htmlspecialchars($rev['review_text']) ?>"
                                        </p>
                                    </div>

                                    <!-- Actions bottom -->
                                    <div class="border-t border-slate-100 pt-4 flex items-center justify-between">
                                        <!-- Visibility Toggle -->
                                        <button onclick="toggleReviewVisibility(<?= $rev['id'] ?>, <?= $rev['is_visible'] ? 0 : 1 ?>)"
                                                class="flex items-center gap-1.5 px-3 py-2 sm:py-1.5 rounded-lg text-xs font-semibold transition-all active-spring-scale <?= $rev['is_visible'] ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200' ?>">
                                            <i data-lucide="<?= $rev['is_visible'] ? 'eye' : 'eye-off' ?>" class="w-4 h-4"></i>
                                            <?= $rev['is_visible'] ? 'Widoczna' : 'Ukryta' ?>
                                        </button>

                                        <!-- Edit and delete actions -->
                                        <div class="flex items-center gap-1">
                                            <button onclick="openEditReviewModal(<?= htmlspecialchars(json_encode($rev)) ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all active-spring-scale"
                                                    title="Edytuj">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="confirmDeleteReview(<?= $rev['id'] ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active-spring-scale"
                                                    title="Usuń">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- ─────────────────────────────────────────────────────────────
            TAB: SERVICES
            ───────────────────────────────────────────────────────────── -->
            <div id="tab-content-services" class="hidden space-y-6 lg:space-y-8 animate-fade-in">
                <!-- Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 flex-shrink-0">
                            <i data-lucide="grid-2x2" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800"><?= $totalServices ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Liczba usług</p>
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/60 p-3 sm:p-4 rounded-2xl shadow-sm flex items-center justify-between col-span-1">
                        <button onclick="openModal('add-service')"
                            class="w-full h-full bg-red-600 hover:bg-red-500 text-white font-bold py-3.5 px-6 rounded-xl shadow-md hover:shadow-red-600/10 active-spring-scale transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                            Dodaj nową usługę
                        </button>
                    </div>
                </div>

                <!-- Services List Grid -->
                <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm">
                    <h3 class="text-base font-bold text-slate-800 mb-2">Zarządzaj usługami</h3>
                    <p class="text-xs text-slate-400 mb-6">Uporządkuj kolejność usług na stronie głównej przy użyciu metody złap i upuść (drag & drop).</p>

                    <div class="overflow-x-auto responsive-table-container rounded-2xl border border-slate-100/80">
                        <table class="w-full min-w-[650px]">
                            <thead>
                                <tr class="text-left border-b border-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider pb-3">
                                    <th class="pb-3 pl-4 w-12"></th>
                                    <th class="pb-3">Usługa</th>
                                    <th class="pb-3">Slug</th>
                                    <th class="pb-3 text-center">Ikona</th>
                                    <th class="pb-3">Opis</th>
                                    <th class="pb-3 text-center">Slajdy modala</th>
                                    <th class="pb-3 text-right pr-4">Akcje</th>
                                </tr>
                            </thead>
                            <tbody id="services-sortable-list" class="divide-y divide-slate-100">
                                <?php foreach ($services as $serv): 
                                    $slideCount = count($slidesByService[$serv['id']] ?? []);
                                ?>
                                    <tr data-id="<?= $serv['id'] ?>" class="text-sm text-slate-750 hover:bg-slate-50/55 transition-colors">
                                        <td class="py-4 pl-4 cursor-grab active:cursor-grabbing text-slate-350 hover:text-slate-550 drag-handle-service">
                                            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                                        </td>
                                        <td class="py-4 font-bold text-slate-800"><?= htmlspecialchars($serv['name']) ?></td>
                                        <td class="py-4"><span class="font-mono text-xs bg-slate-50 px-2.5 py-1 rounded w-fit border border-slate-200/40"><?= htmlspecialchars($serv['slug']) ?></span></td>
                                        <td class="py-4 text-center">
                                            <div class="w-9 h-9 bg-slate-100 rounded-lg flex items-center justify-center text-slate-650 mx-auto"
                                                 title="<?= htmlspecialchars($serv['icon']) ?>">
                                                <i data-lucide="<?= htmlspecialchars($serv['icon']) ?>" class="w-4 h-4"></i>
                                            </div>
                                        </td>
                                        <td class="py-4 max-w-xs truncate" title="<?= htmlspecialchars($serv['description']) ?>"><?= htmlspecialchars($serv['description']) ?></td>
                                        <td class="py-4 text-center">
                                            <span class="inline-flex items-center gap-1 bg-red-50 text-red-600 border border-red-100 font-semibold px-2.5 py-1 rounded-full text-xs">
                                                <i data-lucide="images" class="w-3.5 h-3.5"></i>
                                                <?= $slideCount ?>
                                            </span>
                                        </td>
                                        <td class="py-4 pr-4 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button onclick="openSlideManager(<?= htmlspecialchars(json_encode($serv)) ?>, <?= htmlspecialchars(json_encode($slidesByService[$serv['id']] ?? [])) ?>)"
                                                        class="p-2.5 sm:p-2 bg-slate-50 text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200/60 hover:border-red-200/50 rounded-xl transition-all active-spring-scale flex items-center gap-1.5 text-xs font-bold"
                                                        title="Zarządzaj slajdami galerii usługi">
                                                    <i data-lucide="image-plus" class="w-4 h-4"></i>
                                                    Zarządzaj slajdami
                                                </button>
                                                <button onclick="openEditServiceModal(<?= htmlspecialchars(json_encode($serv)) ?>)"
                                                        class="p-2.5 sm:p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all active-spring-scale"
                                                        title="Edytuj usługę">
                                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="confirmDeleteService(<?= $serv['id'] ?>)"
                                                        class="p-2.5 sm:p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active-spring-scale"
                                                        title="Usuń">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ─────────────────────────────────────────────────────────────
            TAB: EQUIPMENT (PARK MASZYNOWY)
            ───────────────────────────────────────────────────────────── -->
            <div id="tab-content-equipment" class="hidden space-y-6 lg:space-y-8 animate-fade-in">
                <!-- Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-6">
                    <div class="bg-white border border-slate-200/60 p-4 sm:p-6 rounded-2xl shadow-sm flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 flex-shrink-0">
                            <i data-lucide="shovel" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <p class="text-xl sm:text-2xl font-black text-slate-800"><?= $totalEquipment ?></p>
                            <p class="text-[10px] sm:text-xs font-medium text-slate-400 uppercase tracking-wider">Liczba maszyn i pojazdów</p>
                        </div>
                    </div>
                    <div class="bg-white border border-slate-200/60 p-3 sm:p-4 rounded-2xl shadow-sm flex items-center justify-between col-span-1">
                        <button onclick="openModal('add-equipment')"
                            class="w-full h-full bg-red-600 hover:bg-red-500 text-white font-bold py-3.5 px-6 rounded-xl shadow-md hover:shadow-red-600/10 active-spring-scale transition-all flex items-center justify-center gap-2 text-sm sm:text-base">
                            <i data-lucide="plus-circle" class="w-5 h-5"></i>
                            Dodaj nowy sprzęt
                        </button>
                    </div>
                </div>

                <!-- Machinery Grid -->
                <div class="bg-white border border-slate-200/60 rounded-3xl p-4 sm:p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-base font-bold text-slate-800">Sprzęt budowlany i park maszynowy</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Uporządkuj kolejność wyświetlania maszyn na publicznej stronie głównej przy użyciu Drag & Drop.</p>
                    </div>

                    <?php if ($totalEquipment === 0): ?>
                        <div class="text-center py-20 text-slate-400">
                            <i data-lucide="wrench" class="w-12 h-12 mx-auto mb-4 opacity-30"></i>
                            <p class="text-base font-medium">Brak sprzętu w parku maszynowym. Kliknij przycisk powyżej, aby dodać pierwsze wozidło lub koparkę.</p>
                        </div>
                    <?php else: ?>
                        <div id="equipment-sortable-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            <?php foreach ($equipment as $item): ?>
                                <div data-id="<?= $item['id'] ?>"
                                     class="equipment-card bg-white border border-slate-200 rounded-2xl overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col justify-between group relative">
                                    
                                    <!-- Image Preview area with category badge -->
                                    <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                                        <img src="../images/<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             class="w-full h-full object-cover">
                                        <?php if (!empty($item['badge'])): ?>
                                        <span class="absolute top-3 left-3 bg-red-650 text-white font-bold text-[9px] uppercase tracking-wider px-2.5 py-1 rounded-full">
                                            <?= htmlspecialchars($item['badge']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="absolute top-3 right-3 bg-black/60 backdrop-blur-sm text-white font-semibold text-[9px] uppercase tracking-wider px-2 py-1 rounded-md">
                                            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-3.5 h-3.5 inline"></i>
                                        </span>
                                    </div>

                                    <!-- Content detail -->
                                    <div class="p-4 flex-grow flex flex-col justify-between">
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm truncate"><?= htmlspecialchars($item['name']) ?></h4>
                                            <p class="text-slate-400 text-xs mt-1.5 line-clamp-3 leading-relaxed">
                                                <?= htmlspecialchars($item['description']) ?>
                                            </p>
                                            
                                            <?php if (!empty($item['spec_1']) || !empty($item['spec_2'])): ?>
                                            <div class="mt-3 pt-3 border-t border-slate-100 space-y-1">
                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wide block">Specyfikacja:</span>
                                                <?php if (!empty($item['spec_1'])): ?>
                                                    <span class="text-[10px] text-slate-655 flex items-center gap-1.5"><i data-lucide="check" class="w-3 h-3 text-red-500"></i> <?= htmlspecialchars($item['spec_1']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['spec_2'])): ?>
                                                    <span class="text-[10px] text-slate-655 flex items-center gap-1.5"><i data-lucide="check" class="w-3 h-3 text-red-500"></i> <?= htmlspecialchars($item['spec_2']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Grab Drag Header + Action buttons -->
                                    <div class="px-4 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
                                        <!-- Drag handle -->
                                        <div class="drag-handle-equipment cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 transition-colors p-1"
                                             title="Chwyć, aby przenieść">
                                            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                                        </div>
                                        
                                        <!-- Edit and delete actions -->
                                        <div class="flex items-center gap-1">
                                            <button onclick="openEditEquipmentModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all active-spring-scale"
                                                    title="Edytuj maszynę">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="confirmDeleteEquipment(<?= $item['id'] ?>)"
                                                    class="p-2.5 sm:p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all active-spring-scale"
                                                    title="Usuń">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: ADD PHOTO
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-add-photo" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Dodaj nowe zdjęcie do galerii</h3>
                <button onclick="closeModal('add-photo')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="add-photo-form" onsubmit="submitPhoto(event)" class="px-8 py-6 space-y-5">
                
                <!-- Drag and drop zone -->
                <div>
                    <label class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Plik zdjęcia (JPEG, PNG, WebP)</label>
                    <div id="drop-zone" class="border-2 border-dashed border-slate-200 hover:border-red-500/50 rounded-2xl p-6 text-center cursor-pointer transition-all duration-300 relative bg-slate-50">
                        <input type="file" id="photo-input" required accept="image/jpeg, image/png, image/webp" 
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="previewImage(this)">
                        
                        <div id="drop-info" class="space-y-2">
                            <i data-lucide="upload-cloud" class="w-10 h-10 text-slate-400 mx-auto"></i>
                            <p class="text-slate-700 text-sm font-semibold">Chwyć plik lub kliknij, aby wybrać</p>
                            <p class="text-slate-400 text-xs">Maksymalny rozmiar pliku: 10MB</p>
                        </div>
                        <!-- Preview wrapper -->
                        <div id="drop-preview" class="hidden relative rounded-xl overflow-hidden aspect-[4/3] max-h-48 mx-auto shadow bg-white border border-slate-200">
                            <img id="image-preview-el" src="" class="w-full h-full object-cover">
                            <button type="button" onclick="resetFileField(event)" 
                                    class="absolute top-2 right-2 bg-black/60 hover:bg-black text-white p-1.5 rounded-full shadow transition-all">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="photo-title" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Tytuł zdjęcia</label>
                    <input type="text" id="photo-title" required placeholder="np. Ogrodzenie panelowe z podmurówką"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-450 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="photo-category" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Kategoria</label>
                    <select id="photo-category" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <option value="" disabled selected>Wybierz kategorię</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="photo-desc" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Krótki opis (opcjonalny)</label>
                    <textarea id="photo-desc" rows="3" placeholder="Dodatkowy opis widoczny przy powiększeniu zdjęcia..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-450 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('add-photo')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-650 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit" id="upload-submit-btn"
                        class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Zapisz i prześlij
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: EDIT PHOTO
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-edit-photo" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Edycja zdjęcia</h3>
                <button onclick="closeModal('edit-photo')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="edit-photo-form" onsubmit="submitEditPhoto(event)" class="px-8 py-6 space-y-5">
                <input type="hidden" id="edit-photo-id">
                
                <div class="aspect-[16/9] w-full max-h-48 rounded-xl overflow-hidden shadow-sm bg-slate-100 border border-slate-200">
                    <img id="edit-photo-img-preview" src="" class="w-full h-full object-cover">
                </div>

                <div>
                    <label for="edit-photo-title" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Tytuł zdjęcia</label>
                    <input type="text" id="edit-photo-title" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="edit-photo-category" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Kategoria</label>
                    <select id="edit-photo-category" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-photo-aspect" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Format (Aspect Ratio)</label>
                        <select id="edit-photo-aspect" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                            <option value="aspect-[4/3]">Poziomy 4:3 (Zalecany)</option>
                            <option value="aspect-[3/4]">Pionowy 3:4</option>
                            <option value="aspect-square">Kwadrat 1:1</option>
                            <option value="aspect-[16/9]">Szeroki 16:9</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="edit-photo-desc" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Krótki opis</label>
                    <textarea id="edit-photo-desc" rows="3"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-photo')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-650 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Zapisz zmiany
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: EDIT CATEGORY
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-edit-category" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Edycja kategorii</h3>
                <button onclick="closeModal('edit-category')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="edit-category-form" onsubmit="submitEditCategory(event)" class="px-8 py-6 space-y-5">
                <input type="hidden" id="edit-category-id">

                <div>
                    <label for="edit-cat-name" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Nazwa kategorii</label>
                    <input type="text" id="edit-cat-name" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="edit-cat-slug" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Slug (identyfikator)</label>
                    <input type="text" id="edit-cat-slug" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="edit-cat-icon" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                    <select id="edit-cat-icon" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                            <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-category')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-650 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Zapisz zmiany
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: ADD REVIEW
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-add-review" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Dodaj opinię ręcznie</h3>
                <button onclick="closeModal('add-review')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="add-review-form" onsubmit="submitReview(event)" class="px-8 py-6 space-y-5">
                
                <div>
                    <label for="review-author" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Autor opinii (imię i nazwisko)</label>
                    <input type="text" id="review-author" required placeholder="np. Jan Kowalski"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-450 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="review-rating" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Ocena (gwiazdki)</label>
                    <select id="review-rating" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-850 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <option value="5" selected>5 gwiazdek (Znakomita)</option>
                        <option value="4">4 gwiazdki (Bardzo dobra)</option>
                        <option value="3">3 gwiazdki (Przeciętna)</option>
                        <option value="2">2 gwiazdki (Słaba)</option>
                        <option value="1">1 gwiazdka (Bardzo słaba)</option>
                    </select>
                </div>

                <div>
                    <label for="review-time" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Czas dodania opinii</label>
                    <input type="text" id="review-time" required placeholder="np. tydzień temu, miesiąc temu"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-450 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="review-text" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Treść opinii</label>
                    <textarea id="review-text" rows="4" required placeholder="Napisz treść rekomendacji lub skopiuj z SMS/e-mail..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 placeholder-slate-450 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('add-review')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-650 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Zapisz opinię
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: EDIT REVIEW
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-edit-review" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Edycja opinii</h3>
                <button onclick="closeModal('edit-review')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="edit-review-form" onsubmit="submitEditReview(event)" class="px-8 py-6 space-y-5">
                <input type="hidden" id="edit-review-id">

                <div>
                    <label for="edit-review-author" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Autor opinii</label>
                    <input type="text" id="edit-review-author" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="edit-review-rating" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Ocena (gwiazdki)</label>
                    <select id="edit-review-rating" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-855 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <option value="5">5 gwiazdek</option>
                        <option value="4">4 gwiazdki</option>
                        <option value="3">3 gwiazdki</option>
                        <option value="2">2 gwiazdki</option>
                        <option value="1">1 gwiazdka</option>
                    </select>
                </div>

                <div>
                    <label for="edit-review-time" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Czas dodania opinii</label>
                    <input type="text" id="edit-review-time" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>

                <div>
                    <label for="edit-review-text" class="block text-slate-650 text-xs font-bold uppercase tracking-wider mb-2">Treść opinii</label>
                    <textarea id="edit-review-text" rows="4" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-review')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-650 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md flex items-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Zapisz zmiany
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: ADD SERVICE
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-add-service" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Dodaj nową usługę</h3>
                <button onclick="closeModal('add-service')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="add-service-form" onsubmit="submitServiceForm(event)" class="px-8 py-6 space-y-5">
                <div>
                    <label for="serv-name" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Nazwa usługi</label>
                    <input type="text" id="serv-name" required placeholder="np. Ogrodzenia panelowe"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>
                <div>
                    <label for="serv-slug" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Slug (identyfikator)</label>
                    <input type="text" id="serv-slug" required placeholder="np. panelowe"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    <p class="text-[10px] text-slate-400 mt-1">Unikalny slug bez spacji (np. 'panelowe', 'gabionowe').</p>
                </div>
                <div>
                    <label for="serv-icon" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                    <select id="serv-icon" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-855 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                            <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="serv-description" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Opis usługi</label>
                    <textarea id="serv-description" required rows="3" placeholder="Krótki opis usługi wyświetlany w karcie..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('add-service')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-655 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md">
                        Dodaj usługę
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: EDIT SERVICE
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-edit-service" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Edytuj usługę</h3>
                <button onclick="closeModal('edit-service')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="edit-service-form" onsubmit="submitEditServiceForm(event)" class="px-8 py-6 space-y-5">
                <input type="hidden" id="edit-serv-id">
                <div>
                    <label for="edit-serv-name" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Nazwa usługi</label>
                    <input type="text" id="edit-serv-name" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>
                <div>
                    <label for="edit-serv-slug" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Slug (identyfikator)</label>
                    <input type="text" id="edit-serv-slug" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>
                <div>
                    <label for="edit-serv-icon" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                    <select id="edit-serv-icon" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-855 focus:outline-none focus:border-red-500 transition-all duration-300">
                        <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                            <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="edit-serv-description" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Opis usługi</label>
                    <textarea id="edit-serv-description" required rows="3"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-service')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-655 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md">
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: SERVICE SLIDES MANAGER
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-service-slide-manager" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300 my-8">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Zarządzanie slajdami modala</h3>
                    <p id="slide-manager-serv-name" class="text-xs text-slate-400 mt-0.5">Dodawaj, usuwaj i sortuj zdjęcia w modalu szczegółowym</p>
                </div>
                <button onclick="closeModal('service-slide-manager')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <div class="p-8 space-y-6">
                <!-- List of existing slides -->
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wide block mb-3">Istniejące slajdy (Przeciągnij by przenieść)</span>
                    <div id="slide-manager-list" class="space-y-3 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>

                <!-- Add new slide form wrapper -->
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <span class="text-xs font-bold text-slate-750 uppercase tracking-wide block mb-4">Dodaj nowy slajd</span>
                    <form id="add-slide-form" onsubmit="submitAddSlideForm(event)" class="space-y-4">
                        <input type="hidden" id="slide-manager-serv-id">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="slide-type" class="block text-slate-650 text-xs font-semibold mb-1">Typ slajdu</label>
                                <select id="slide-type" onchange="toggleSlideTypeFields(this.value)"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:outline-none focus:border-red-500">
                                    <option value="image">Zdjęcie z galerii</option>
                                    <option value="gradient">Bezgraficzny z ikoną</option>
                                </select>
                            </div>
                        </div>

                        <!-- Image upload fields -->
                        <div id="slide-image-fields" class="space-y-2">
                            <label class="block text-slate-650 text-xs font-semibold">Wybierz zdjęcie</label>
                            <input type="file" id="slide-photo-input" accept="image/jpeg, image/png, image/webp"
                                class="w-full bg-white border border-slate-200 rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-red-500">
                        </div>

                        <!-- Gradient fields -->
                        <div id="slide-gradient-fields" class="hidden grid grid-cols-2 gap-4">
                            <div>
                                <label for="slide-gradient" class="block text-slate-655 text-xs font-semibold mb-1">Gradient tła</label>
                                <select id="slide-gradient"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:outline-none focus:border-red-500">
                                    <option value="bg-gradient-to-br from-slate-700 to-slate-900">Szary/Ciemny (Slate)</option>
                                    <option value="bg-gradient-to-br from-amber-800 to-yellow-950">Brązowy/Złoty (Amber)</option>
                                    <option value="bg-gradient-to-br from-yellow-600 to-orange-800">Pomarańczowy (Tymczasowy)</option>
                                    <option value="bg-gradient-to-br from-blue-700 to-indigo-950">Niebieski (Blue)</option>
                                </select>
                            </div>
                            <div>
                                <label for="slide-icon" class="block text-slate-655 text-xs font-semibold mb-1">Ikona Lucide</label>
                                <select id="slide-icon"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-2.5 px-3 text-sm focus:outline-none focus:border-red-500">
                                    <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                                        <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" id="add-slide-submit"
                                class="bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-5 rounded-xl text-xs active-spring-scale flex items-center gap-1.5 transition-colors">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                Dodaj slajd
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="px-8 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button onclick="closeModal('service-slide-manager')"
                    class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-xl text-sm transition-all active-spring-scale shadow-md">
                    Gotowe i zamknij
                </button>
            </div>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: ADD EQUIPMENT
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-add-equipment" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Dodaj nową maszynę do parku</h3>
                <button onclick="closeModal('add-equipment')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="add-equipment-form" onsubmit="submitEquipmentForm(event)" class="px-8 py-6 space-y-5">
                <div>
                    <label class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Zdjęcie maszyny</label>
                    <input type="file" id="equip-photo" required accept="image/jpeg, image/png, image/webp"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-red-500">
                </div>
                <div>
                    <label for="equip-name" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Nazwa maszyny / pojazdu</label>
                    <input type="text" id="equip-name" required placeholder="np. Minikoparka JCB"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="equip-badge" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Etykieta (Badge)</label>
                        <input type="text" id="equip-badge" placeholder="np. Wykopy, Transport"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                    <div>
                        <label for="equip-icon" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                        <select id="equip-icon" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-855 focus:outline-none focus:border-red-500 transition-all duration-300">
                            <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                                <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="equip-spec-1" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Specyfikacja 1</label>
                        <input type="text" id="equip-spec-1" placeholder="np. Praca w ciasnych miejscach"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                    <div>
                        <label for="equip-spec-2" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Specyfikacja 2</label>
                        <input type="text" id="equip-spec-2" placeholder="np. Głębokość kopania 2.5m"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                </div>
                <div>
                    <label for="equip-description" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Opis zastosowania</label>
                    <textarea id="equip-description" required rows="3" placeholder="Zastosowanie maszyny w terenie..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('add-equipment')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-655 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md">
                        Dodaj sprzęt
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
    MODAL: EDIT EQUIPMENT
    ───────────────────────────────────────────────────────────── -->
    <div id="modal-edit-equipment" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 flex flex-col justify-between transform transition-all duration-300">
            <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-800">Edytuj sprzęt</h3>
                <button onclick="closeModal('edit-equipment')" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form id="edit-equipment-form" onsubmit="submitEditEquipmentForm(event)" class="px-8 py-6 space-y-5">
                <input type="hidden" id="edit-equip-id">
                
                <div class="aspect-[16/9] w-full max-h-48 rounded-xl overflow-hidden shadow-sm bg-slate-100 border border-slate-200">
                    <img id="edit-equip-img-preview" src="" class="w-full h-full object-cover">
                </div>

                <div>
                    <label class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Zmień zdjęcie (Opcjonalnie)</label>
                    <input type="file" id="edit-equip-photo" accept="image/jpeg, image/png, image/webp"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 text-sm focus:outline-none focus:border-red-500">
                </div>
                <div>
                    <label for="edit-equip-name" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Nazwa maszyny</label>
                    <input type="text" id="edit-equip-name" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-equip-badge" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Etykieta (Badge)</label>
                        <input type="text" id="edit-equip-badge"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                    <div>
                        <label for="edit-equip-icon" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Ikona Lucide</label>
                        <select id="edit-equip-icon" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-855 focus:outline-none focus:border-red-500 transition-all duration-300">
                            <?php foreach ($lucideIcons as $iconName => $iconLabel): ?>
                                <option value="<?= $iconName ?>"><?= $iconLabel ?> (<?= $iconName ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-equip-spec-1" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Specyfikacja 1</label>
                        <input type="text" id="edit-equip-spec-1"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                    <div>
                        <label for="edit-equip-spec-2" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Specyfikacja 2</label>
                        <input type="text" id="edit-equip-spec-2"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300">
                    </div>
                </div>
                <div>
                    <label for="edit-equip-description" class="block text-slate-655 text-xs font-bold uppercase tracking-wider mb-2">Opis zastosowania</label>
                    <textarea id="edit-equip-description" required rows="3"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-800 focus:outline-none focus:border-red-500 transition-all duration-300 resize-none"></textarea>
                </div>
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeModal('edit-equipment')"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-655 font-bold py-3 px-6 rounded-xl transition-all">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md">
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CSRF Token -->
    <input type="hidden" id="csrf-token" value="<?= htmlspecialchars(getCSRFToken()) ?>">

    <!-- ─────────────────────────────────────────────────────────────
    PANEL SCRIPTS
    ───────────────────────────────────────────────────────────── -->
    <script>
        lucide.createIcons();

        // ──────────────────────────────────────────
        // Mobile Sidebar functions
        // ──────────────────────────────────────────
        function openSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                overlay.classList.add('opacity-100');
            }, 10);
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300);
        }

        // ──────────────────────────────────────────
        // Tab switching
        // ──────────────────────────────────────────
        function switchTab(tabId) {
            // Close mobile sidebar first
            closeSidebar();

            const tabs = ['photos', 'categories', 'reviews', 'services', 'equipment'];
            tabs.forEach(t => {
                const contentEl = document.getElementById(`tab-content-${t}`);
                const btnEl = document.getElementById(`tab-btn-${t}`);
                if (contentEl) contentEl.classList.add('hidden');
                if (btnEl) btnEl.className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 text-slate-400 hover:bg-slate-800 hover:text-white active-spring-scale";
            });

            const activeContent = document.getElementById(`tab-content-${tabId}`);
            const activeBtn = document.getElementById(`tab-btn-${tabId}`);
            if (activeContent) activeContent.classList.remove('hidden');
            if (activeBtn) activeBtn.className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 bg-red-600 text-white active-spring-scale";

            const title = document.getElementById('tab-title');
            const subtitle = document.getElementById('tab-subtitle');

            if (!title || !subtitle) return;

            if (tabId === 'photos') {
                title.textContent = 'Zdjęcia';
                subtitle.textContent = 'Zarządzaj zdjęciami w galerii realizacji';
            } else if (tabId === 'categories') {
                title.textContent = 'Kategorie';
                subtitle.textContent = 'Organizuj podziały na typy ogrodzeń';
            } else if (tabId === 'reviews') {
                title.textContent = 'Opinie klientów';
                subtitle.textContent = 'Zarządzaj opiniami z Google Maps oraz własnymi referencjami';
            } else if (tabId === 'services') {
                title.textContent = 'Usługi';
                subtitle.textContent = 'Zarządzaj ofertą usług i slajdami modala szczegółowego';
            } else if (tabId === 'equipment') {
                title.textContent = 'Park Maszynowy';
                subtitle.textContent = 'Zarządzaj flotą maszyn i pojazdów budowlanych';
            }
        }

        // ──────────────────────────────────────────
        // Modal management
        // ──────────────────────────────────────────
        function openModal(modalName) {
            const modal = document.getElementById(`modal-${modalName}`);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalName) {
            const modal = document.getElementById(`modal-${modalName}`);
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        // ──────────────────────────────────────────
        // Drag and Drop (Sortable.js)
        // ──────────────────────────────────────────
        const sortableGrid = document.getElementById('photos-sortable-grid');
        if (sortableGrid) {
            Sortable.create(sortableGrid, {
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                animation: 250,
                onEnd: function() {
                    // Extract new IDs sequence
                    const newOrder = Array.from(sortableGrid.children).map(card => card.dataset.id);
                    savePhotoOrder(newOrder);
                }
            });
        }

        async function savePhotoOrder(orderArray) {
            try {
                const response = await fetch('api.php?action=reorder_photos', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        csrf_token: document.getElementById('csrf-token').value,
                        order: orderArray
                    })
                });
                const res = await response.json();
                if (!res.success) {
                    alert(res.message || 'Błąd zapisu kolejności.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci podczas zapisywania kolejności.');
            }
        }

        // ──────────────────────────────────────────
        // Photo upload / form handling
        // ──────────────────────────────────────────
        function previewImage(input) {
            const preview = document.getElementById('drop-preview');
            const info = document.getElementById('drop-info');
            const img = document.getElementById('image-preview-el');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    info.classList.add('hidden');
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function resetFileField(e) {
            if (e) e.stopPropagation();
            const input = document.getElementById('photo-input');
            const preview = document.getElementById('drop-preview');
            const info = document.getElementById('drop-info');
            
            input.value = '';
            info.classList.remove('hidden');
            preview.classList.add('hidden');
        }

        async function submitPhoto(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('upload-submit-btn');
            const originalBtnHtml = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i data-lucide="loader" class="w-5 h-5 animate-spin"></i> Przesyłanie i optymalizacja...`;
            lucide.createIcons();

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('photo', document.getElementById('photo-input').files[0]);
            formData.append('title', document.getElementById('photo-title').value);
            formData.append('category_id', document.getElementById('photo-category').value);
            formData.append('description', document.getElementById('photo-desc').value);

            try {
                const response = await fetch('api.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    alert('Zdjęcie zostało dodane do galerii!');
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas dodawania zdjęcia.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                    lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                lucide.createIcons();
            }
        }

        // ──────────────────────────────────────────
        // Photo Edit
        // ──────────────────────────────────────────
        function openEditPhotoModal(photo) {
            document.getElementById('edit-photo-id').value = photo.id;
            document.getElementById('edit-photo-title').value = photo.title;
            document.getElementById('edit-photo-category').value = photo.category_id;
            document.getElementById('edit-photo-aspect').value = photo.aspect_class;
            document.getElementById('edit-photo-desc').value = photo.description || '';
            document.getElementById('edit-photo-img-preview').src = '../images/' + photo.filename;
            openModal('edit-photo');
        }

        async function submitEditPhoto(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', document.getElementById('edit-photo-id').value);
            formData.append('title', document.getElementById('edit-photo-title').value);
            formData.append('category_id', document.getElementById('edit-photo-category').value);
            formData.append('aspect_class', document.getElementById('edit-photo-aspect').value);
            formData.append('description', document.getElementById('edit-photo-desc').value);

            try {
                const response = await fetch('api.php?action=update_photo', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd zapisu.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        async function confirmDeletePhoto(id) {
            if (!confirm('Czy na pewno chcesz bezpowrotnie usunąć to zdjęcie z galerii? Ta operacja jest nieodwracalna.')) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_photo', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd podczas usuwania zdjęcia.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        // ──────────────────────────────────────────
        // Category CRUD
        // ──────────────────────────────────────────
        async function submitCategory(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('name', document.getElementById('cat-name').value);
            formData.append('slug', document.getElementById('cat-slug').value);
            formData.append('icon', document.getElementById('cat-icon').value);

            try {
                const response = await fetch('api.php?action=add_category', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    alert('Kategoria została pomyślnie utworzona!');
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd tworzenia kategorii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        function openEditCategoryModal(cat) {
            document.getElementById('edit-category-id').value = cat.id;
            document.getElementById('edit-cat-name').value = cat.name;
            document.getElementById('edit-cat-slug').value = cat.slug;
            document.getElementById('edit-cat-icon').value = cat.icon;
            openModal('edit-category');
        }

        async function submitEditCategory(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', document.getElementById('edit-category-id').value);
            formData.append('name', document.getElementById('edit-cat-name').value);
            formData.append('slug', document.getElementById('edit-cat-slug').value);
            formData.append('icon', document.getElementById('edit-cat-icon').value);

            try {
                const response = await fetch('api.php?action=edit_category', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd zapisu kategorii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        async function confirmDeleteCategory(id) {
            if (!confirm('UWAGA: Usunięcie kategorii spowoduje automatyczne usunięcie wszystkich przypisanych do niej zdjęć z bazy i z dysku! Czy na pewno chcesz kontynuować?')) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_category', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd podczas usuwania kategorii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        // ──────────────────────────────────────────
        // Reviews CRUD JS
        // ──────────────────────────────────────────
        async function fetchGoogleReviews() {
            const btn = document.getElementById('fetch-btn');
            const originalHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Pobieranie...`;
            lucide.createIcons();

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);

            try {
                const response = await fetch('api.php?action=fetch_google_reviews', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                alert(res.message);
                if (res.success) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci podczas pobierania opinii Google.');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }

        async function submitReview(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('author_name', document.getElementById('review-author').value);
            formData.append('rating', document.getElementById('review-rating').value);
            formData.append('review_time', document.getElementById('review-time').value);
            formData.append('review_text', document.getElementById('review-text').value);

            try {
                const response = await fetch('api.php?action=add_review', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    alert('Opinia została pomyślnie dodana!');
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd tworzenia opinii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        function openEditReviewModal(rev) {
            document.getElementById('edit-review-id').value = rev.id;
            document.getElementById('edit-review-author').value = rev.author_name;
            document.getElementById('edit-review-rating').value = rev.rating;
            document.getElementById('edit-review-time').value = rev.review_time;
            document.getElementById('edit-review-text').value = rev.review_text;
            openModal('edit-review');
        }

        async function submitEditReview(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', document.getElementById('edit-review-id').value);
            formData.append('author_name', document.getElementById('edit-review-author').value);
            formData.append('rating', document.getElementById('edit-review-rating').value);
            formData.append('review_time', document.getElementById('edit-review-time').value);
            formData.append('review_text', document.getElementById('edit-review-text').value);

            try {
                const response = await fetch('api.php?action=edit_review', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd zapisu opinii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        async function toggleReviewVisibility(id, visibleState) {
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);
            formData.append('is_visible', visibleState);

            try {
                const response = await fetch('api.php?action=toggle_review_visibility', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd zapisu widoczności.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        async function confirmDeleteReview(id) {
            if (!confirm('Czy na pewno chcesz trwale usunąć tę opinię? Ta operacja jest nieodwracalna.')) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_review', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd podczas usuwania opinii.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        // ──────────────────────────────────────────
        // Services CRUD JS
        // ──────────────────────────────────────────
        async function submitServiceForm(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('name', document.getElementById('serv-name').value);
            formData.append('slug', document.getElementById('serv-slug').value);
            formData.append('icon', document.getElementById('serv-icon').value);
            formData.append('description', document.getElementById('serv-description').value);

            try {
                const response = await fetch('api.php?action=add_service', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    alert('Usługa została pomyślnie dodana!');
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas dodawania usługi.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd połączenia z serwerem.');
            }
        }

        function openEditServiceModal(serv) {
            document.getElementById('edit-serv-id').value = serv.id;
            document.getElementById('edit-serv-name').value = serv.name;
            document.getElementById('edit-serv-slug').value = serv.slug;
            document.getElementById('edit-serv-icon').value = serv.icon;
            document.getElementById('edit-serv-description').value = serv.description;
            openModal('edit-service');
        }

        async function submitEditServiceForm(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', document.getElementById('edit-serv-id').value);
            formData.append('name', document.getElementById('edit-serv-name').value);
            formData.append('slug', document.getElementById('edit-serv-slug').value);
            formData.append('icon', document.getElementById('edit-serv-icon').value);
            formData.append('description', document.getElementById('edit-serv-description').value);

            try {
                const response = await fetch('api.php?action=edit_service', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas aktualizacji usługi.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd połączenia z serwerem.');
            }
        }

        async function confirmDeleteService(id) {
            if (!confirm('UWAGA: Czy na pewno chcesz trwale usunąć tę usługę? Spowoduje to bezpowrotne usunięcie usługi oraz wszystkich powiązanych z nią slajdów i ich plików z serwera!')) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_service', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas usuwania usługi.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd połączenia z serwerem.');
            }
        }

        // ──────────────────────────────────────────
        // Service Slides Manager
        // ──────────────────────────────────────────
        let activeSlidesSortable = null;

        function openSlideManager(serv, slides) {
            document.getElementById('slide-manager-serv-id').value = serv.id;
            document.getElementById('slide-manager-serv-name').textContent = serv.name;
            
            const listContainer = document.getElementById('slide-manager-list');
            listContainer.innerHTML = '';
            
            if (slides.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center py-8 text-slate-400 text-xs">
                        <i data-lucide="image-off" class="w-8 h-8 mx-auto mb-2 opacity-35"></i>
                        Brak slajdów dla tej usługi. Dodaj swój pierwszy slajd poniżej.
                    </div>
                `;
            } else {
                slides.forEach(slide => {
                    let previewHtml = '';
                    if (slide.image) {
                        previewHtml = `
                            <div class="w-16 h-12 rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                                <img src="../${slide.image}" class="w-full h-full object-cover">
                            </div>
                        `;
                    } else {
                        previewHtml = `
                            <div class="w-16 h-12 rounded-lg bg-gradient-to-br ${slide.gradient} overflow-hidden border border-slate-700/35 flex-shrink-0 flex items-center justify-center text-white">
                                <i data-lucide="${slide.icon}" class="w-5 h-5"></i>
                            </div>
                        `;
                    }

                    const actionHtml = slide.is_gallery_auto
                        ? `<span class="text-[9px] font-bold text-slate-500 bg-slate-100 border border-slate-200 px-2 py-1 rounded-md" title="Zarządzaj tym zdjęciem w zakładce Zdjęcia">Galeria (Auto)</span>`
                        : `<button type="button" onclick="deleteSlide(${slide.id}, ${serv.id}, event)"
                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Usuń slajd">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>`;

                    const itemHtml = `
                        <div data-id="${slide.id}" class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl hover:shadow-sm transition-all group drag-handle-slide cursor-grab active:cursor-grabbing">
                            <div class="flex items-center gap-3 font-sans">
                                <div class="text-slate-350 hover:text-slate-500">
                                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                                </div>
                                ${previewHtml}
                                <div>
                                    <p class="text-xs font-bold text-slate-800">${slide.image ? (slide.is_gallery_auto ? 'Zdjęcie z galerii (Automatyczne)' : 'Zdjęcie z galerii') : 'Slajd bezgraficzny'}</p>
                                    <p class="text-[9px] font-mono text-slate-400 truncate max-w-[220px]">${slide.image ? slide.image : 'Gradient z ikoną Lucide'}</p>
                                </div>
                            </div>
                            ${actionHtml}
                        </div>
                    `;
                    listContainer.insertAdjacentHTML('beforeend', itemHtml);
                });
            }
            
            lucide.createIcons();
            openModal('service-slide-manager');

            // Initialize Sortable for slide list
            if (activeSlidesSortable) {
                activeSlidesSortable.destroy();
            }
            
            if (slides.length > 0) {
                activeSlidesSortable = Sortable.create(listContainer, {
                    handle: '.drag-handle-slide',
                    ghostClass: 'sortable-ghost',
                    animation: 200,
                    onEnd: async function() {
                        // Filter out virtual gallery slides so reordering only updates actual db service_slides entries
                        const newOrder = Array.from(listContainer.children)
                            .map(item => item.dataset.id)
                            .filter(id => !String(id).startsWith('gallery_'));

                        if (newOrder.length === 0) return; // Nothing to reorder

                        try {
                            const response = await fetch('api.php?action=reorder_service_slides', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    csrf_token: document.getElementById('csrf-token').value,
                                    order: newOrder
                                })
                            });
                            const res = await response.json();
                            if (!res.success) {
                                alert(res.message || 'Błąd zapisu kolejności slajdów.');
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Błąd połączenia sieciowego podczas zapisu kolejności.');
                        }
                    }
                });
            }
        }

        function toggleSlideTypeFields(value) {
            const imgFields = document.getElementById('slide-image-fields');
            const gradFields = document.getElementById('slide-gradient-fields');
            if (value === 'image') {
                imgFields.classList.remove('hidden');
                gradFields.classList.add('hidden');
            } else {
                imgFields.classList.add('hidden');
                gradFields.classList.remove('hidden');
            }
        }

        async function submitAddSlideForm(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('add-slide-submit');
            const originalHtml = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i> Dodawanie...`;
            lucide.createIcons();

            const serviceId = document.getElementById('slide-manager-serv-id').value;
            const slideType = document.getElementById('slide-type').value;

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('service_id', serviceId);
            formData.append('slide_type', slideType);

            if (slideType === 'image') {
                const fileEl = document.getElementById('slide-photo-input');
                if (!fileEl.files || fileEl.files.length === 0) {
                    alert('Proszę wybrać plik zdjęcia do przesłania.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    lucide.createIcons();
                    return;
                }
                formData.append('slide_photo', fileEl.files[0]);
            } else {
                formData.append('gradient', document.getElementById('slide-gradient').value);
                formData.append('icon', document.getElementById('slide-icon').value);
            }

            try {
                const response = await fetch('api.php?action=add_service_slide', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd dodawania slajdu.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                    lucide.createIcons();
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                lucide.createIcons();
            }
        }

        async function deleteSlide(id, serviceId, event) {
            if (event) event.stopPropagation();
            if (!confirm('Czy na pewno chcesz bezpowrotnie usunąć ten slajd? Jeśli zawierał zdjęcie, zostanie ono skasowane z serwera.')) return;

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_service_slide', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd podczas usuwania slajdu.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        // ──────────────────────────────────────────
        // Equipment (Park Maszynowy) CRUD JS
        // ──────────────────────────────────────────
        async function submitEquipmentForm(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('equip-photo');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Dodanie zdjęcia sprzętu jest obowiązkowe.');
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('equipment_photo', fileInput.files[0]);
            formData.append('name', document.getElementById('equip-name').value);
            formData.append('badge', document.getElementById('equip-badge').value);
            formData.append('icon', document.getElementById('equip-icon').value);
            formData.append('spec_1', document.getElementById('equip-spec-1').value);
            formData.append('spec_2', document.getElementById('equip-spec-2').value);
            formData.append('description', document.getElementById('equip-description').value);

            try {
                const response = await fetch('api.php?action=add_equipment', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    alert('Sprzęt został pomyślnie dodany do parku maszynowego!');
                    window.location.reload();
                } else {
                    alert(res.message || 'Błąd zapisu danych sprzętu.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd połączenia z serwerem.');
            }
        }

        function openEditEquipmentModal(item) {
            document.getElementById('edit-equip-id').value = item.id;
            document.getElementById('edit-equip-name').value = item.name;
            document.getElementById('edit-equip-badge').value = item.badge || '';
            document.getElementById('edit-equip-icon').value = item.icon;
            document.getElementById('edit-equip-spec-1').value = item.spec_1 || '';
            document.getElementById('edit-equip-spec-2').value = item.spec_2 || '';
            document.getElementById('edit-equip-description').value = item.description;
            document.getElementById('edit-equip-img-preview').src = '../images/' + item.image;
            openModal('edit-equipment');
        }

        async function submitEditEquipmentForm(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', document.getElementById('edit-equip-id').value);
            formData.append('name', document.getElementById('edit-equip-name').value);
            formData.append('badge', document.getElementById('edit-equip-badge').value);
            formData.append('icon', document.getElementById('edit-equip-icon').value);
            formData.append('spec_1', document.getElementById('edit-equip-spec-1').value);
            formData.append('spec_2', document.getElementById('edit-equip-spec-2').value);
            formData.append('description', document.getElementById('edit-equip-description').value);

            const fileInput = document.getElementById('edit-equip-photo');
            if (fileInput.files && fileInput.files[0]) {
                formData.append('equipment_photo', fileInput.files[0]);
            }

            try {
                const response = await fetch('api.php?action=edit_equipment', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas aktualizacji danych sprzętu.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd połączenia z serwerem.');
            }
        }

        async function confirmDeleteEquipment(id) {
            if (!confirm('UWAGA: Czy na pewno chcesz trwale usunąć ten sprzęt z parku maszynowego? Plik zdjęcia zostanie skasowany z dysku.')) return;
            
            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf-token').value);
            formData.append('id', id);

            try {
                const response = await fetch('api.php?action=delete_equipment', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Wystąpił błąd podczas usuwania sprzętu.');
                }
            } catch (err) {
                console.error(err);
                alert('Błąd sieci.');
            }
        }

        // ──────────────────────────────────────────
        // Drag and Drop (SortableJS) for Services and Equipment
        // ──────────────────────────────────────────
        const servicesSortable = document.getElementById('services-sortable-list');
        if (servicesSortable) {
            Sortable.create(servicesSortable, {
                handle: '.drag-handle-service',
                ghostClass: 'sortable-ghost',
                animation: 250,
                onEnd: async function() {
                    const newOrder = Array.from(servicesSortable.children).map(row => row.dataset.id);
                    try {
                        const response = await fetch('api.php?action=reorder_services', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                csrf_token: document.getElementById('csrf-token').value,
                                order: newOrder
                            })
                        });
                        const res = await response.json();
                        if (!res.success) {
                            alert(res.message || 'Błąd zapisu kolejności usług.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Błąd połączenia sieciowego podczas zapisu kolejności usług.');
                    }
                }
            });
        }

        const equipmentSortable = document.getElementById('equipment-sortable-grid');
        if (equipmentSortable) {
            Sortable.create(equipmentSortable, {
                handle: '.drag-handle-equipment',
                ghostClass: 'sortable-ghost',
                animation: 250,
                onEnd: async function() {
                    const newOrder = Array.from(equipmentSortable.children).map(card => card.dataset.id);
                    try {
                        const response = await fetch('api.php?action=reorder_equipment', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                csrf_token: document.getElementById('csrf-token').value,
                                order: newOrder
                            })
                        });
                        const res = await response.json();
                        if (!res.success) {
                            alert(res.message || 'Błąd zapisu kolejności sprzętu.');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Błąd połączenia sieciowego podczas zapisu kolejności sprzętu.');
                    }
                }
            });
        }
    </script>
</body>
</html>
