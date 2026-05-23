import os
import json
import base64
import urllib.request
import urllib.error
from PIL import Image

TINIPNG_API_KEY = "0Zyzzv1z4CYLWP96YTyfQzfpdYZNn0PD"

def compress_with_tinypng(file_path, api_key):
    try:
        # Read the image bytes
        with open(file_path, 'rb') as f:
            data = f.read()
            
        # Basic authentication header
        auth_str = f"api:{api_key}"
        auth_encoded = base64.b64encode(auth_str.encode('utf-8')).decode('utf-8')
        
        req = urllib.request.Request(
            'https://api.tinify.com/shrink',
            data=data,
            headers={
                'Authorization': f'Basic {auth_encoded}',
                'Content-Type': 'application/octet-stream'
            },
            method='POST'
        )
        
        # Send post request to TinyPNG shrink endpoint
        with urllib.request.urlopen(req) as response:
            res_data = json.loads(response.read().decode('utf-8'))
            compressed_url = res_data['output']['url']
            
        # Download the compressed image
        req_download = urllib.request.Request(compressed_url)
        with urllib.request.urlopen(req_download) as response_download:
            compressed_data = response_download.read()
            
        # Overwrite the original file with the compressed data
        with open(file_path, 'wb') as f:
            f.write(compressed_data)
            
        print(f"    TinyPNG compressed: {len(data)/1024:.1f} KB -> {len(compressed_data)/1024:.1f} KB (reduced by {(1.0 - len(compressed_data)/len(data))*100:.1f}%)")
        return True
    except Exception as e:
        print(f"    TinyPNG compression failed for {os.path.basename(file_path)}: {str(e)}")
        return False

def slugify(text):
    import re
    # Simple transliteration for Polish characters
    replacements = {
        'ą': 'a', 'ć': 'c', 'ę': 'e', 'ł': 'l', 'ń': 'n', 'ó': 'o', 'ś': 's', 'ź': 'z', 'ż': 'z',
        'Ą': 'A', 'Ć': 'C', 'Ę': 'E', 'Ł': 'L', 'Ń': 'N', 'Ó': 'O', 'Ś': 'S', 'Ź': 'Z', 'Ż': 'Z'
    }
    for k, v in replacements.items():
        text = text.replace(k, v)
    text = text.lower().strip()
    text = re.sub(r'[^a-z0-9\-]+', '-', text)
    text = re.sub(r'-+', '-', text)
    return text.strip('-')

SRC_DIR = '/home/jankuchnia/Desktop/km-bud/fotki_km-bud'
DEST_DIR = '/home/jankuchnia/Desktop/km-bud/images'

os.makedirs(DEST_DIR, exist_ok=True)

# Mappings from directory names to web categories
CATEGORY_MAPPING = {
    'bloczkowe': 'betonowe',
    'bramy i furtki': 'bramy',
    'panelowe': 'panelowe',
    'siatkowe': 'siatka',
    'sprzety': 'sprzety'
}

# Polish titles and descriptions lists for dynamic generation
TITLES = {
    'betonowe': [
        "Ogrodzenie z bloczków łupanych Joniec",
        "Nowoczesne bloczki ogrodzeniowe gładkie",
        "Solidna podmurówka z bloczków betonowych",
        "Ogrodzenie modułowe betonowe",
        "Eleganckie ogrodzenie z bloczków KM-BUD",
        "Bloczki ogrodzeniowe w kolorze grafitowym",
        "Kompleksowa realizacja z bloczków łupanych",
        "Nowoczesne ogrodzenie frontowe z bloczków",
        "Ogrodzenie posesji z daszkami dwuspadowymi",
        "Trwałe ogrodzenie bloczkowe z przęsłami",
        "Estetyczne bloczki betonowe gładkie"
    ],
    'bramy': [
        "Brama przesuwna stalowa KM-BUD",
        "Nowoczesna furtka wejściowa z pochwytem"
    ],
    'panelowe': [
        "Klasyczne ogrodzenie panelowe 3D w kolorze antracytowym"
    ],
    'siatka': [
        "Klasyczne ogrodzenie z siatki plecionej",
        "Siatka ocynkowana powlekana PVC z podmurówką",
        "Trwałe ogrodzenie siatkowe KM-BUD",
        "Ogrodzenie działki siatką zgrzewaną",
        "Siatka ogrodzeniowa z podmurówką prefabrykowaną",
        "Ekonomiczne ogrodzenie posesji z siatki",
        "Solidnie naciągnięta siatka pleciona",
        "Ogrodzenie graniczne z siatki powlekanej"
    ],
    'sprzety': [
        "Minikoparka KM-BUD w trakcie wykopów",
        "Wozidło gąsienicowe podczas transportu kruszywa",
        "Taczka spalinowa do pracy w ciasnych przejściach",
        "Wiertnica glebowa spalinowa pod słupki",
        "Nowoczesna minikoparka KM-BUD",
        "Wozidło gąsienicowe w trudnym terenie",
        "Precyzyjne wiercenie otworów pod słupki"
    ]
}

DESCRIPTIONS = {
    'betonowe': [
        "Precyzyjny montaż ogrodzenia frontowego z bloczków łupanych Joniec w Myślenicach.",
        "Nowoczesna aranżacja ogrodzenia modułowego gładkiego z metalowymi przęsłami. Olszowice.",
        "Solidny mur z pustaków ogrodzeniowych dekoracyjnych z dbałością o detale. Dobczyce.",
        "Estetyczne wykończenie ogrodzenia posesji prywatnej bloczkami betonowymi najwyższej klasy.",
        "Kompleksowe wykonanie ogrodzenia z bloczków łupanych wraz z podmurówką i fundamentem.",
        "Elegancka linia ogrodzenia z bloczków gładkich w kolorze grafitowym. Kraków.",
        "Wytrzymała konstrukcja ogrodzenia modułowego z przęsłami poziomymi w Sułkowicach.",
        "Ogrodzenie frontowe dopasowane do nowoczesnej bryły budynku mieszkalnego. Myślenice.",
        "Dbałość o detale przy fugowaniu i montażu daszków na słupkach ogrodzeniowych. Gdów.",
        "Ogrodzenie łupane z przęsłami żaluzyjnymi i zintegrowaną furtką. Siepraw.",
        "Modułowe ogrodzenie betonowe o podwyższonej odporności na warunki atmosferyczne. Pcim."
    ],
    'bramy': [
        "Brama przesuwna stalowa z automatyką, zsynchronizowana z ogrodzeniem panelowym. Olszowice.",
        "Furtka wejściowa z nowoczesnym zamkiem elektromagnetycznym i pochwytem. Myślenice."
    ],
    'panelowe': [
        "Trwałe i ekonomiczne ogrodzenie panelowe 3D w kolorze antracytowym z prefabrykowaną podmurówką. Kraków."
    ],
    'siatka': [
        "Kompleksowy montaż siatki plecionej ocynkowanej powlekanej PVC z prefabrykowaną podmurówką betonową.",
        "Solidne ogrodzenie działki rekreacyjnej z siatki zgrzewanej na słupkach stalowych. Myślenice.",
        "Ekonomiczne i estetyczne ogrodzenie siatkowe zabezpieczające posesję prywatną. Olszowice.",
        "Montaż siatki leśnej na słupkach drewnianych do ochrony sadu przed dziką zwierzyną. Dobczyce.",
        "Wytrzymałe ogrodzenie z siatki powlekanej zielonej na podmurówce systemowej. Siepraw.",
        "Dopasowana i naciągnięta siatka ogrodzeniowa z dbałością o każdy szczegół napięcia drutu. Gdów.",
        "Ogrodzenie graniczne posesji wykonane z mocnej siatki plecionej o oczku 50x50mm. Pcim.",
        "Szybki i profesjonalny montaż siatki ogrodzeniowej na trudnym, pochyłym terenie. Sułkowice."
    ],
    'sprzety': [
        "Nasza niezawodna minikoparka podczas precyzyjnych wykopów liniowych pod fundamenty ogrodzenia.",
        "Wydajny transport urobku i ziemi za pomocą wozidła gąsienicowego, chroniącego podłoże przed zniszczeniem.",
        "Zwrotna taczka spalinowa ułatwiająca transport betonu w ciasnych przestrzeniach posesji.",
        "Wiertnica glebowa do szybkiego i precyzyjnego wykonywania otworów pod słupki ogrodzeniowe.",
        "Minikoparka wykonująca wykop pod podmurówkę betonową w trudnym terenie.",
        "Transport kruszywa wozidłem gąsienicowym na gęsto zagospodarowanej działce.",
        "Użycie wiertnicy spalinowej gwarantującej idealny pion otworów pod słupki panelowe."
    ]
}

def process_images():
    manifest = []
    
    # Iterate through folders
    for folder_name in sorted(os.listdir(SRC_DIR)):
        folder_path = os.path.join(SRC_DIR, folder_name)
        if not os.path.isdir(folder_path):
            continue
            
        category = CATEGORY_MAPPING.get(folder_name)
        if not category:
            print(f"Skipping folder: {folder_name} (no mapping)")
            continue
            
        print(f"\nProcessing category: {category} (from '{folder_name}')")
        
        # List and sort image files inside
        files = sorted([f for f in os.listdir(folder_path) if f.lower().endswith(('.jpeg', '.jpg', '.png'))])
        
        for idx, file_name in enumerate(files):
            file_path = os.path.join(folder_path, file_name)
            
            # Format destination filename
            prefix = {
                'betonowe': 'ogrodzenie-bloczkowe',
                'bramy': 'brama-furtka',
                'panelowe': 'ogrodzenie-panelowe',
                'siatka': 'ogrodzenie-siatkowe',
                'sprzety': 'sprzet-budowlany'
            }.get(category, 'realizacja')
            
            new_file_name = f"{prefix}-{idx+1:02d}.webp"
            dest_path = os.path.join(DEST_DIR, new_file_name)
            
            try:
                # Open and process image
                with Image.open(file_path) as img:
                    width, height = img.size
                    ratio = width / height
                    
                    # Determine aspect class
                    if ratio > 1.5:
                        aspect_class = "aspect-[16/9]"
                    elif ratio > 1.05:
                        aspect_class = "aspect-[4/3]"
                    elif ratio < 0.7:
                        aspect_class = "aspect-[9/16]"
                    elif ratio < 0.95:
                        aspect_class = "aspect-[3/4]"
                    else:
                        aspect_class = "aspect-square"
                    
                    # Convert to WebP and save with high optimization
                    img.save(dest_path, "WEBP", quality=80, method=6)
                    
                    # Compress further with TinyPNG API
                    if TINIPNG_API_KEY:
                        compress_with_tinypng(dest_path, TINIPNG_API_KEY)
                    
                # Dynamic title and description assignment
                title_list = TITLES.get(category, ["Realizacja KM-BUD"])
                desc_list = DESCRIPTIONS.get(category, ["Kompleksowe wykonanie ogrodzenia przez KM-BUD."])
                
                title = title_list[idx % len(title_list)]
                desc = desc_list[idx % len(desc_list)]
                
                # If there are duplicates in names, append index for visual variety
                if idx >= len(title_list):
                    title = f"{title} (wzór {idx // len(title_list) + 1})"
                
                manifest.append({
                    'original': os.path.join('fotki_km-bud', folder_name, file_name),
                    'filename': f"images/{new_file_name}",
                    'category': category,
                    'title': title,
                    'desc': desc,
                    'width': width,
                    'height': height,
                    'aspect_class': aspect_class
                })
                
                print(f"  Optimized: {file_name} -> images/{new_file_name} ({aspect_class})")
                
            except Exception as e:
                print(f"  Error processing {file_name}: {str(e)}")
                
    # Save manifest file
    manifest_path = os.path.join('/home/jankuchnia/Desktop/km-bud', 'image_metadata.json')
    with open(manifest_path, 'w', encoding='utf-8') as f:
        json.dump(manifest, f, ensure_ascii=False, indent=2)
    print(f"\nSaved metadata manifest for {len(manifest)} images in image_metadata.json")

if __name__ == '__main__':
    process_images()
