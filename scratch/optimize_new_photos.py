import os
from PIL import Image
import base64
import json
import urllib.request

TINIPNG_API_KEY = "0Zyzzv1z4CYLWP96YTyfQzfpdYZNn0PD"
BASE_DIR = '/home/jankuchnia/Desktop/km-bud'
IMAGES_DIR = os.path.join(BASE_DIR, 'images')

def compress_with_tinypng(file_path, api_key):
    try:
        with open(file_path, 'rb') as f:
            data = f.read()
            
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
        
        with urllib.request.urlopen(req) as response:
            res_data = json.loads(response.read().decode('utf-8'))
            compressed_url = res_data['output']['url']
            
        req_download = urllib.request.Request(compressed_url)
        with urllib.request.urlopen(req_download) as response_download:
            compressed_data = response_download.read()
            
        with open(file_path, 'wb') as f:
            f.write(compressed_data)
            
        print(f"  TinyPNG compressed {os.path.basename(file_path)}: {len(data)/1024:.1f} KB -> {len(compressed_data)/1024:.1f} KB (reduced by {(1.0 - len(compressed_data)/len(data))*100:.1f}%)")
        return True
    except Exception as e:
        print(f"  TinyPNG compression failed for {os.path.basename(file_path)}: {str(e)}")
        return False

def optimize_photo(src_name, dest_name):
    src_path = os.path.join(BASE_DIR, src_name)
    dest_path = os.path.join(IMAGES_DIR, dest_name)
    
    if not os.path.exists(src_path):
        print(f"Source file {src_name} does not exist in root directory.")
        return
        
    try:
        with Image.open(src_path) as img:
            # Convert and save as WebP
            img.save(dest_path, "WEBP", quality=80, method=6)
            print(f"Saved optimized WebP: images/{dest_name}")
            
        # Compress with TinyPNG
        if TINIPNG_API_KEY:
            compress_with_tinypng(dest_path, TINIPNG_API_KEY)
            
    except Exception as e:
        print(f"Error processing {src_name}: {str(e)}")

if __name__ == '__main__':
    print("Optimizing 'taczka-spalinowa.jpg'...")
    optimize_photo('taczka-spalinowa.jpg', 'taczka_spalinowa.webp')
    
    print("\nOptimizing 'wiertnica_glebowa.jpg'...")
    optimize_photo('wiertnica_glebowa.jpg', 'wiertnica_glebowa.webp')
