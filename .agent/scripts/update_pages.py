import json
import os
import re

MANIFEST_PATH = '/home/jankuchnia/Desktop/km-bud/image_metadata.json'
INDEX_PATH = '/home/jankuchnia/Desktop/km-bud/index.html'
GALERIA_PATH = '/home/jankuchnia/Desktop/km-bud/galeria.html'

def update_pages():
    if not os.path.exists(MANIFEST_PATH):
        print(f"Error: Manifest file {MANIFEST_PATH} not found!")
        return

    with open(MANIFEST_PATH, 'r', encoding='utf-8') as f:
        manifest = json.load(f)

    # Group photos by category
    by_category = {}
    for item in manifest:
        cat = item['category']
        by_category.setdefault(cat, []).append(item)

    print(f"Loaded manifest with {len(manifest)} processed images.")
    for cat, items in by_category.items():
        print(f"  Category '{cat}': {len(items)} images")

    # -------------------------------------------------------------------------
    # 1. UPDATE index.html
    # -------------------------------------------------------------------------
    print("\n--- Updating index.html ---")
    if os.path.exists(INDEX_PATH):
        with open(INDEX_PATH, 'r', encoding='utf-8') as f:
            index_content = f.read()

        # A. Update Hero Slider (6 slides)
        # We need to find 6 premium slides and replace the carousel slides block.
        # Let's hand-pick:
        # 0: bloczkowe-01
        # 1: siatkowe-01
        # 2: bramy-01
        # 3: panelowe-01
        # 4: bloczkowe-05
        # 5: siatkowe-05
        slides_selection = [
            ('images/ogrodzenie-bloczkowe-01.webp', "Nowoczesne ogrodzenie frontowe z bloczków łupanych Joniec"),
            ('images/ogrodzenie-siatkowe-01.webp', "Trwałe i precyzyjne ogrodzenie z siatki plecionej"),
            ('images/brama-furtka-01.webp', "Solidna brama przesuwna stalowa z automatyką"),
            ('images/ogrodzenie-panelowe-01.webp', "Estetyczne i klasyczne ogrodzenie panelowe 3D"),
            ('images/ogrodzenie-bloczkowe-05.webp', "Modułowe ogrodzenie betonowe o podwyższonej trwałości"),
            ('images/ogrodzenie-siatkowe-05.webp', "Ogrodzenie siatkowe z solidną podmurówką systemową")
        ]
        
        slides_html = ""
        for i, (path, alt) in enumerate(slides_selection):
            opacity_class = "opacity-100 z-10" if i == 0 else "opacity-0 pointer-events-none z-0"
            slides_html += f"""                      <!-- Slide {i+1} -->
                      <div
                        class="carousel-slide absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out {opacity_class}"
                        data-slide-index="{i}">
                        <img src="{path}"
                          alt="{alt}" class="w-full h-full object-cover object-center">
                      </div>\n"""
        
        # Replace the realizations carousel block
        # We'll use a regex to capture between <!-- Slides Container --> and <!-- Navigation Arrows -->
        pattern_carousel = r'(<!-- Slides Container -->\s*<div class="relative w-full h-full">)(.*?)(</div>\s*<!-- Navigation Arrows -->)'
        replacement_carousel = f"\\1\n{slides_html.rstrip()}\n      \\3"
        index_content = re.sub(pattern_carousel, replacement_carousel, index_content, flags=re.DOTALL)
        print("  Updated realizations carousel slides.")

        # B. Update Realizations Grid (8 items)
        # We hand-pick 8 items to display on the home grid:
        grid_selection = [
            ('images/ogrodzenie-bloczkowe-02.webp', "Ogrodzenie frontowe z bloczków"),
            ('images/ogrodzenie-siatkowe-02.webp', "Ogrodzenie z siatki plecionej"),
            ('images/brama-furtka-02.webp', "Nowoczesna furtka wejściowa"),
            ('images/ogrodzenie-panelowe-01.webp', "Klasyczny panel ogrodzeniowy 3D"),
            ('images/ogrodzenie-bloczkowe-03.webp', "Ogrodzenie z bloczków gładkich"),
            ('images/ogrodzenie-siatkowe-03.webp', "Solidne ogrodzenie siatkowe"),
            ('images/ogrodzenie-bloczkowe-04.webp', "Ogrodzenie modułowe betonowe"),
            ('images/ogrodzenie-siatkowe-04.webp', "Ogrodzenie z siatki zgrzewanej")
        ]
        
        grid_html = ""
        # The grid items must lead to galeria.html. The last item will have the overlay with +44 więcej
        for i, (path, alt) in enumerate(grid_selection):
            if i == len(grid_selection) - 1:
                # Last item with +44 overlay
                grid_html += f"""                <div class="aspect-square rounded-xl overflow-hidden group cursor-pointer relative" onclick="window.location.href='galeria.html'">
                  <img src="{path}"
                    alt="{alt}"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                  <div
                    class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center transition-all duration-300">
                    <span class="text-white font-bold text-3xl">+44</span>
                    <span class="text-white/80 text-xs font-semibold mt-1">zobacz więcej</span>
                  </div>
                </div>\n"""
            else:
                grid_html += f"""                <div class="aspect-square rounded-xl overflow-hidden group cursor-pointer relative" onclick="window.location.href='galeria.html'">
                  <img src="{path}"
                    alt="{alt}"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-center justify-center">
                    <span class="text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-bold border border-white/40 px-4 py-2 rounded-full text-sm backdrop-blur-sm">Powiększ galerię</span>
                  </div>
                </div>\n"""
        
        # Replace the realizations grid content in index.html
        # Capture between <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"> and </div>\s*</section> (well, actually the grid elements are ended before the CTA link section, let's find the exact block)
        pattern_grid = r'(<!-- Nasze realizacje grid start -->\s*<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">.*?</div>\s*<!-- Nasze realizacje grid end -->)'
        # Let's write markers in HTML if needed, or target the exact container
        # Since there are no markers in the original HTML, we can target:
        # <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">\s*(<div class="aspect-square.*?</div\s*>\s*)+
        # Let's do a strict match based on the images listed
        pattern_grid_strict = r'(<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">)(.*?)(</div>\s*<div class="text-center mt-10">)'
        replacement_grid = f"\\1\n{grid_html.rstrip()}\n              \\3"
        index_content = re.sub(pattern_grid_strict, replacement_grid, index_content, flags=re.DOTALL)
        print("  Updated realizations grid items.")

        # C. Update machinery fleet sections with images
        # We need to insert aspect-video images inside each of the 4 machinery cards.
        # Let's find the card definitions.
        # Card 1: Minikoparka
        # Original:
        # <div\s*class="bg-\[var\(--light-background-color\)\] rounded-2xl p-6 md:p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-\[var\(--light-border-color\)\] flex flex-col justify-between">\s*<div>\s*<div\s*class="w-14 h-14 bg-neutral-950 rounded-xl flex items-center justify-center mb-6...
        # We'll replace it with a rounded-2xl overflow-hidden structure and insert the aspect-video image block at the top.
        
        # Card 1: Minikoparka
        old_card_1 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl p-6 md:p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div>
                    <div
                      class="w-14 h-14 bg-neutral-950 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform relative overflow-hidden">
                      <div class="absolute top-0 left-0 w-1.5 h-full bg-[var(--primary-color)]"></div>
                      <i data-lucide="shovel" class="text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-3">
                      Minikoparka
                    </h3>"""
                    
        new_card_1 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div class="relative aspect-video w-full overflow-hidden bg-neutral-900">
                    <img src="images/sprzet-budowlany-01.webp" alt="Minikoparka KM-BUD" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                  </div>
                  <div class="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                      <div
                        class="w-12 h-12 bg-neutral-950 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[var(--primary-color)]"></div>
                        <i data-lucide="shovel" class="text-white text-xl"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-2">
                        Minikoparka
                      </h3>"""

        # Card 2: Wozidło
        old_card_2 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl p-6 md:p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div>
                    <div
                      class="w-14 h-14 bg-neutral-950 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform relative overflow-hidden">
                      <div class="absolute top-0 left-0 w-1.5 h-full bg-[var(--primary-color)]"></div>
                      <i data-lucide="tractor" class="text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-3">
                      Wozidło Gąsienicowe
                    </h3>"""
                    
        new_card_2 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div class="relative aspect-video w-full overflow-hidden bg-neutral-900">
                    <img src="images/sprzet-budowlany-02.webp" alt="Wozidło Gąsienicowe KM-BUD" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                  </div>
                  <div class="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                      <div
                        class="w-12 h-12 bg-neutral-950 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[var(--primary-color)]"></div>
                        <i data-lucide="tractor" class="text-white text-xl"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-2">
                        Wozidło Gąsienicowe
                      </h3>"""

        # Card 3: Taczka
        old_card_3 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl p-6 md:p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div>
                    <div
                      class="w-14 h-14 bg-neutral-950 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform relative overflow-hidden">
                      <div class="absolute top-0 left-0 w-1.5 h-full bg-[var(--primary-color)]"></div>
                      <i data-lucide="truck" class="text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-3">
                      Taczka Spalinowa
                    </h3>"""
                    
        new_card_3 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div class="relative aspect-video w-full overflow-hidden bg-neutral-900">
                    <img src="images/sprzet-budowlany-03.webp" alt="Taczka Spalinowa KM-BUD" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                  </div>
                  <div class="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                      <div
                        class="w-12 h-12 bg-neutral-950 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[var(--primary-color)]"></div>
                        <i data-lucide="truck" class="text-white text-xl"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-2">
                        Taczka Spalinowa
                      </h3>"""

        # Card 4: Wiertnica
        old_card_4 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl p-6 md:p-8 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div>
                    <div
                      class="w-14 h-14 bg-neutral-950 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform relative overflow-hidden">
                      <div class="absolute top-0 left-0 w-1.5 h-full bg-[var(--primary-color)]"></div>
                      <i data-lucide="drill" class="text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-3">
                      Wiertnica Glebowa
                    </h3>"""
                    
        new_card_4 = """                <div
                  class="bg-[var(--light-background-color)] rounded-2xl overflow-hidden hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group border border-[var(--light-border-color)] flex flex-col justify-between">
                  <div class="relative aspect-video w-full overflow-hidden bg-neutral-900">
                    <img src="images/sprzet-budowlany-04.webp" alt="Wiertnica Glebowa KM-BUD" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                  </div>
                  <div class="p-6 md:p-8 flex-grow flex flex-col justify-between">
                    <div>
                      <div
                        class="w-12 h-12 bg-neutral-950 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[var(--primary-color)]"></div>
                        <i data-lucide="drill" class="text-white text-xl"></i>
                      </div>
                      <h3 class="text-xl font-bold text-[var(--dark-text-color)] mb-2">
                        Wiertnica Glebowa
                      </h3>"""

        # Also, the flex layout cards need a small fix because we wrapped the inner elements inside a padding container:
        # We need to close the wrapped padding container div inside card-body
        # Let's inspect index.html machinery list for details, but we can do simple direct string replacements first
        index_content = index_content.replace(old_card_1, new_card_1)
        index_content = index_content.replace(old_card_2, new_card_2)
        index_content = index_content.replace(old_card_3, new_card_3)
        index_content = index_content.replace(old_card_4, new_card_4)
        
        # Because we added `<div class="p-6 md:p-8 flex-grow flex flex-col justify-between">`, we must make sure the closing tag matches.
        # Let's see: original card has `</div>\s*<div class="border-t border-\[var\(--light-border-color\)\] pt-4 mt-auto">...</div>\s*</div>`
        # Since we changed it, the first closing `</div>` inside the original code closed `<div>` (which was after `flex flex-col justify-between`).
        # We replaced `<div>` with `... </div>\s*<div class="p-6 md:p-8 flex-grow flex flex-col justify-between">\s*<div>`.
        # So we have:
        # <div class="bg-...">
        #   <div class="relative ... aspect-video">...</div>
        #   <div class="p-6 ...">
        #     <div>
        #       <div class="w-12 ...">...</div>
        #       <h3 ...>...</h3>
        #       <p>...</p>  (from original HTML)
        #     </div>       (this will close the inner <div>)
        #     <div class="border-t ... pt-4 mt-auto">...</div> (from original HTML)
        #   </div>         (this closes our <div class="p-6"> padding wrapper)
        # </div>           (this closes the main card wrapper)
        # Wait, the original code had:
        #   </div> (closes the <div> after class="bg...")
        #   <div class="border-t...">...</div>
        # </div> (closes the class="bg...")
        # Yes! So we just need to make sure we append a closing `</div>` to close our padding wrapper.
        # Let's check how the script should do this. In our replacement:
        # `new_card_X` ends after `h3`. The original follows with `<p>...</p>\n                  </div>\n                  <div class="border-t... pt-4 mt-auto">...</div>\n                </div>`.
        # Wait! In `new_card_X` we opened `<div class="p-6 md:p-8 flex-grow flex flex-col justify-between">` and then `<div>`.
        # The original `</div>` closes `<div>`.
        # So we have:
        #   </div> (closes inner `<div>`)
        #   <div class="border-t...">...</div>
        #   (Here, we need a closing `</div>` for `<div class="p-6 ... flex-grow ...">`!)
        #   </div> (closes card wrapper)
        # Yes! So we need to insert `</div>` right before the card's closing `</div>`.
        # Let's write a targeted replacement for the bottom parts of these cards.
        # We can find this:
        # `</div>\n                  <div class="border-t border-[var(--light-border-color)] pt-4 mt-auto">`
        # Actually, let's see how each card is closed:
        # Card 1:
        # ```html
        #                   </div>
        #                   <div class="border-t border-[var(--light-border-color)] pt-4 mt-auto">
        #                     <ul class="space-y-2 text-xs text-[var(--gray-text-color)]">
        #                       <li class="flex items-center gap-2">
        #                         <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
        #                         <span>Praca w trudnodostępnych miejscach</span>
        #                       </li>
        #                       <li class="flex items-center gap-2">
        #                         <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
        #                         <span>Precyzyjne i równe wykopy liniowe</span>
        #                       </li>
        #                     </ul>
        #                   </div>
        #                 </div>
        # ```
        # To close our padding wrapper cleanly, we replace the last section ` </div>\n                </div>` of each card with ` </div>\n                  </div>\n                </div>`!
        # Let's do this safely in python:
        for idx in range(1, 5):
            # We want to replace the closing tags for each card
            # Let's use a regex or string search for each card's bottom.
            # In the original index.html:
            # Card 1 lists "Praca w trudnodostępnych miejscach" and "Precyzyjne i równe wykopy liniowe"
            # Card 2: "Zminimalizowany nacisk na podłoże", "Wydajny transport ciężkich materiałów"
            # Card 3: "Wyjątkowa zwrotność i kompaktowość", "Szybki załadunek i rozładunek"
            # Card 4: "Równomierna, idealna głębokość wiercenia", "Drastyczne przyspieszenie tempa montażu"
            pass

        # Let's do the specific replacements:
        card_1_bottom_old = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Precyzyjne i równe wykopy liniowe</span>
                      </li>
                    </ul>
                  </div>
                </div>"""
        card_1_bottom_new = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Precyzyjne i równe wykopy liniowe</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>"""

        card_2_bottom_old = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Wydajny transport ciężkich materiałów</span>
                      </li>
                    </ul>
                  </div>
                </div>"""
        card_2_bottom_new = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Wydajny transport ciężkich materiałów</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>"""

        card_3_bottom_old = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Szybki załadunek i rozładunek</span>
                      </li>
                    </ul>
                  </div>
                </div>"""
        card_3_bottom_new = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Szybki załadunek i rozładunek</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>"""

        card_4_bottom_old = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Drastyczne przyspieszenie tempa montażu</span>
                      </li>
                    </ul>
                  </div>
                </div>"""
        card_4_bottom_new = """                      <li class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-[var(--primary-color)]"></span>
                        <span>Drastyczne przyspieszenie tempa montażu</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>"""

        index_content = index_content.replace(card_1_bottom_old, card_1_bottom_new)
        index_content = index_content.replace(card_2_bottom_old, card_2_bottom_new)
        index_content = index_content.replace(card_3_bottom_old, card_3_bottom_new)
        index_content = index_content.replace(card_4_bottom_old, card_4_bottom_new)
        print("  Updated machinery fleet cards with image headers and proper flex wrappers.")

        # Save index.html
        with open(INDEX_PATH, 'w', encoding='utf-8') as f:
            f.write(index_content)
        print("Successfully saved index.html!")

    # -------------------------------------------------------------------------
    # 2. UPDATE galeria.html
    # -------------------------------------------------------------------------
    print("\n--- Updating galeria.html ---")
    if os.path.exists(GALERIA_PATH):
        with open(GALERIA_PATH, 'r', encoding='utf-8') as f:
            galeria_content = f.read()

        # A. Update Filter Tabs
        # Prune empty filters, add "Zaplecze Maszynowe" filter.
        # Original:
        # <div id="gallery-filters" class="scroll-mt-24 lg:scroll-mt-32 mb-10 flex flex-wrap gap-2.5 justify-center">
        #   ... (10 buttons) ...
        # </div>
        # We will replace it with the new active filters
        filters_html = """          <div id="gallery-filters" class="scroll-mt-24 lg:scroll-mt-32 mb-10 flex flex-wrap gap-2.5 justify-center">
            <button
              class="filter-btn active flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="all">
              <i data-lucide="layout-grid" class="w-4 h-4"></i> Wszystkie
            </button>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="panelowe">
              <i data-lucide="square" class="w-4 h-4"></i> Ogrodzenia panelowe
            </button>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="betonowe">
              <i data-lucide="rectangle-horizontal" class="w-4 h-4"></i> Ogrodzenia z bloczków
            </button>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="siatka">
              <i data-lucide="grid-3x3" class="w-4 h-4"></i> Ogrodzenia z siatki
            </button>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="bramy">
              <i data-lucide="door-open" class="w-4 h-4"></i> Bramy i furtki
            </button>
            <button
              class="filter-btn flex items-center gap-1.5 border border-gray-200 bg-white shadow-sm text-gray-700 text-sm font-medium px-4 py-2.5 rounded-full hover:border-gray-400 hover:text-black transition-all duration-300"
              data-filter="sprzety">
              <i data-lucide="truck" class="w-4 h-4"></i> Zaplecze maszynowe
            </button>
          </div>"""
          
        pattern_filters = r'(<div id="gallery-filters" class="scroll-mt-24 lg:scroll-mt-32 mb-10 flex flex-wrap gap-2.5 justify-center">.*?</div>)'
        galeria_content = re.sub(pattern_filters, filters_html, galeria_content, flags=re.DOTALL)
        print("  Updated gallery filters container.")

        # B. Rebuild Gallery Grid (52 items)
        # Generate HTML for all 52 gallery cards dynamically!
        grid_items_html = ""
        category_labels = {
            'panelowe': 'Panelowe',
            'betonowe': 'Z bloczków',
            'siatka': 'Z siatki',
            'bramy': 'Bramy i furtki',
            'sprzety': 'Maszyny'
        }
        
        for item in manifest:
            cat = item['category']
            filename = item['filename']
            title = item['title']
            desc = item['desc']
            aspect_class = item['aspect_class']
            cat_label = category_labels.get(cat, 'Ogrodzenie')
            
            grid_items_html += f"""            <!-- {title} -->
            <div class="gallery-item" data-category="{cat}" data-title="{title}" data-desc="{desc}">
              <div class="relative overflow-hidden rounded-xl bg-gray-100 cursor-zoom-in group shadow-md">
                <img src="{filename}" alt="{title}" class="{aspect_class} w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                <div
                  class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-end p-4">
                  <div
                    class="translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
                    <span
                      class="inline-block bg-white/20 backdrop-blur text-white text-xs px-2 py-1 rounded-full mb-1">{cat_label}</span>
                    <p class="text-white font-semibold text-sm">{title}</p>
                  </div>
                </div>
                <button onclick="openLightbox(this.closest('.gallery-item'))"
                  class="absolute inset-0 w-full h-full opacity-0 cursor-zoom-in"
                  aria-label="Powiększ zdjęcie"></button>
              </div>
            </div>\n\n"""

        # Replace the entire old gallery-grid container in galeria.html
        pattern_grid = r'(<div id="gallery-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">)(.*?)(</div><!-- /gallery-grid -->)'
        replacement_grid = f"\\1\n{grid_items_html.rstrip()}\n          \\3"
        galeria_content = re.sub(pattern_grid, replacement_grid, galeria_content, flags=re.DOTALL)
        print("  Injected 52 dynamically calculated gallery cards in masonry grid.")

        # C. Update Lightbox Javascript to load real image tags
        # Find:
        # lbPreview.innerHTML = `<div class="w-full aspect-video bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center rounded-xl min-h-[300px]">
        # <i data-lucide="image" style="width:4rem;height:4rem;color:rgba(255,255,255,0.15)"></i>
        # </div>`;
        # Replace with a real dynamic image tag:
        
        old_lb_preview_js = """      // If you add real <img> tags later, swap lbPreview content here
      lbPreview.innerHTML = `<div class="w-full aspect-video bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center rounded-xl min-h-[300px]">
      <i data-lucide="image" style="width:4rem;height:4rem;color:rgba(255,255,255,0.15)"></i>
    </div>`;"""
    
        new_lb_preview_js = """      // Dynamically load the real <img> element from the gallery item card
      const imgEl = item.querySelector('img');
      if (imgEl) {
        lbPreview.innerHTML = `<img src="${imgEl.getAttribute('src')}" alt="${imgEl.getAttribute('alt')}" class="max-h-[75vh] max-w-full mx-auto object-contain rounded-xl shadow-2xl animate-fade-in">`;
      } else {
        lbPreview.innerHTML = `<div class="w-full aspect-video bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center rounded-xl min-h-[300px]">
          <i data-lucide="image" style="width:4rem;height:4rem;color:rgba(255,255,255,0.15)"></i>
        </div>`;
      }"""
      
        galeria_content = galeria_content.replace(old_lb_preview_js, new_lb_preview_js)
        print("  Updated lightbox script to load and animate real image elements.")

        # Save galeria.html
        with open(GALERIA_PATH, 'w', encoding='utf-8') as f:
            f.write(galeria_content)
        print("Successfully saved galeria.html!")

    print("\nAll pages successfully updated!")

if __name__ == '__main__':
    update_pages()
