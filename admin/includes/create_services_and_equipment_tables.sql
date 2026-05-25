-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'image',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create service_slides table
CREATE TABLE IF NOT EXISTS service_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    gradient VARCHAR(100) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create equipment table
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) NOT NULL,
    icon VARCHAR(50) NOT NULL DEFAULT 'wrench',
    description TEXT NOT NULL,
    badge VARCHAR(50) DEFAULT NULL,
    spec_1 VARCHAR(255) DEFAULT NULL,
    spec_2 VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean existing data to avoid duplicates during migrate/re-run
DELETE FROM service_slides;
DELETE FROM services;
DELETE FROM equipment;

-- Seed services
INSERT INTO services (id, slug, name, description, icon, sort_order) VALUES
(1, 'panelowe', 'Ogrodzenia panelowe', 'Trwałe i uniwersalne systemy panelowe. Idealne do nowoczesnych posesji prywatnych oraz obiektów przemysłowych.', 'scan', 1),
(2, 'siatka', 'Ogrodzenia z siatki', 'Ekonomiczne i solidne ogrodzenia z siatki zgrzewanej lub plecionej. Doskonałe na działki i tereny leśne.', 'grid-3x3', 2),
(3, 'betonowe', 'Ogrodzenia z bloczków', 'Reprezentacyjne ogrodzenia z bloczków betonowych gładkich i łupanych. Ponadczasowa solidność i design.', 'brick-wall', 3),
(4, 'gabionowe', 'Ogrodzenia gabionowe', 'Nowoczesne kosze stalowe wypełnione kamieniem naturalnym. Zapewniają prestiżowy wygląd i świetną izolację akustyczną.', 'layers', 4),
(5, 'sztachetowe', 'Ogrodzenia sztachetowe', 'Klasyczne i nowoczesne ogrodzenia ze sztachet metalowych lub kompozytowych. Estetyka połączona z trwałością.', 'align-justify', 5),
(6, 'przemysłowe', 'Ogrodzenia przemysłowe', 'Wytrzymałe systemy ogrodzeniowe dla firm, magazynów i fabryk. Gwarancja najwyższego poziomu zabezpieczenia.', 'shield', 6),
(7, 'tymczasowe', 'Ogrodzenia tymczasowe', 'Mobilne ogrodzenia budowlane i ażurowe. Szybki montaż, stabilność i pełne bezpieczeństwo placu budowy.', 'construction', 7),
(8, 'bramy', 'Bramy, furtki, przęsła', 'Kompleksowe systemy wjazdowe z pełną automatyką. Perfekcyjne dopasowanie do każdego stylu ogrodzenia.', 'door-closed', 8),
(9, 'podmurówka', 'Mury oporowe', 'Prefabrykowane i wylewane konstrukcje stabilizujące grunt. Solidny fundament pod podmurówki ogrodzeniowe.', 'mountain', 9);

-- Seed service slides (associated with services above)
-- 1. Ogrodzenia panelowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(1, 'images/ogrodzenie-panelowe-01.webp', NULL, NULL, 1),
(1, 'images/ogrodzenie-siatkowe-04.webp', NULL, NULL, 2);

-- 2. Ogrodzenia z siatki
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(2, 'images/ogrodzenie-siatkowe-01.webp', NULL, NULL, 1),
(2, 'images/ogrodzenie-siatkowe-02.webp', NULL, NULL, 2),
(2, 'images/ogrodzenie-siatkowe-03.webp', NULL, NULL, 3);

-- 3. Ogrodzenia z bloczków
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(3, 'images/ogrodzenie-bloczkowe-01.webp', NULL, NULL, 1),
(3, 'images/ogrodzenie-bloczkowe-02.webp', NULL, NULL, 2),
(3, 'images/ogrodzenie-bloczkowe-03.webp', NULL, NULL, 3);

-- 4. Ogrodzenia gabionowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(4, 'images/ogrodzenie-bloczkowe-06.webp', NULL, NULL, 1),
(4, NULL, 'bg-gradient-to-br from-slate-700 to-slate-900', 'layers', 2);

-- 5. Ogrodzenia sztachetowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(5, 'images/ogrodzenie-bloczkowe-09.webp', NULL, NULL, 1),
(5, NULL, 'bg-gradient-to-br from-amber-800 to-yellow-950', 'align-justify', 2);

-- 6. Ogrodzenia przemysłowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(6, 'images/ogrodzenie-panelowe-01.webp', NULL, NULL, 1),
(6, 'images/ogrodzenie-siatkowe-09.webp', NULL, NULL, 2);

-- 7. Ogrodzenia tymczasowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(7, NULL, 'bg-gradient-to-br from-yellow-600 to-orange-800', 'construction', 1);

-- 8. Bramy, furtki, przęsła
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(8, 'images/brama-furtka-01.webp', NULL, NULL, 1),
(8, 'images/brama-furtka-02.webp', NULL, NULL, 2);

-- 9. Mury oporowe
INSERT INTO service_slides (service_id, image, gradient, icon, sort_order) VALUES
(9, 'images/ogrodzenie-bloczkowe-05.webp', NULL, NULL, 1),
(9, 'images/ogrodzenie-siatkowe-04.webp', NULL, NULL, 2);

-- Seed equipment
INSERT INTO equipment (name, image, icon, description, badge, spec_1, spec_2, sort_order) VALUES
('Minikoparka', 'sprzet-budowlany-01.webp', 'shovel', 'Precyzyjne i sprawne wykopy pod fundamenty ogrodzeń, słupki oraz murki oporowe w każdych warunkach glebowych.', 'Wykopy', 'Praca w trudnodostępnych miejscach', 'Precyzyjne i równe wykopy liniowe', 1),
('Wozidło Gąsienicowe', 'wozidlo_gasienicowe.webp', 'tractor', 'Błyskawiczny transport urobku, ziemi oraz betonu. Szerokie gąsienice chronią trawnik i podłoże przed zniszczeniem.', 'Transport', 'Zminimalizowany nacisk na podłoże', 'Wydajny transport ciężkich materiałów', 2),
('Taczka Spalinowa', 'taczka_spalinowa.webp', 'truck', 'Niezastąpiona przy przewożeniu kruszywa, betonu i zaprawy murarskiej na ciasnych i gęsto zagospodarowanych posesjach.', 'Wąskie przejścia', 'Wyjątkowa zwrotność i kompaktowość', 'Szybki załadunek i rozładunek', 3),
('Wiertnica Glebowa', 'wiertnica_glebowa.webp', 'drill', 'Szybkie, mechaniczne wiercenie precyzyjnych otworów w ziemi pod słupki ogrodzeniowe. Gwarancja idealnego pionu.', 'Odwierty', 'Równomierna, idealna głębokość wiercenia', 'Drastyczne przyspieszenie tempa montażu', 4);
