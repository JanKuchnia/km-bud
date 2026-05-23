const fs = require('fs');
const path = require('path');

// Load metadata
const metadata = JSON.parse(fs.readFileSync('image_metadata.json', 'utf8'));

// Polish badge names mapping
const categoryBadges = {
  'panelowe': 'Panelowe',
  'betonowe': 'Z bloczków',
  'siatka': 'Z siatki',
  'bramy': 'Bramy i furtki',
  'sprzety': 'Zaplecze maszynowe'
};

let htmlOut = '';

metadata.forEach((item, index) => {
  const badge = categoryBadges[item.category] || item.category;
  
  // Use lazy loading for all images except the first 4 to optimize Largest Contentful Paint (LCP)
  const loadingAttr = index >= 4 ? ' loading="lazy"' : '';
  
  htmlOut += `            <!-- Realization: ${item.title} -->
            <div class="gallery-item" data-category="${item.category}" data-title="${item.title}"
              data-desc="${item.desc}">
              <div class="relative overflow-hidden rounded-xl bg-gray-200 cursor-zoom-in group shadow-md">
                <img src="${item.filename}" alt="${item.title}" class="w-full ${item.aspect_class} object-cover"${loadingAttr}>
                <div
                  class="absolute inset-0 bg-black/0 group-hover:bg-black/45 transition-all duration-300 flex items-end p-4">
                  <div
                    class="translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                    <span
                      class="inline-block bg-white/20 backdrop-blur text-white text-xs px-2 py-1 rounded-full mb-1">${badge}</span>
                    <p class="text-white font-semibold text-sm">${item.title}</p>
                  </div>
                </div>
                <button onclick="openLightbox(this.closest('.gallery-item'))"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-zoom-in"
                  aria-label="Powiększ zdjęcie"></button>
              </div>
            </div>

`;
});

fs.writeFileSync('scratch/gallery_output.html', htmlOut, 'utf8');
console.log('Successfully generated HTML for', metadata.length, 'gallery items in scratch/gallery_output.html!');
