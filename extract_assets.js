const fs = require('fs');
const path = require('path');

// Target directory for images
const imgDir = path.join(__dirname, 'images');

// Ensure images directory exists
if (!fs.existsSync(imgDir)) {
    fs.mkdirSync(imgDir);
    console.log(`Created directory: ${imgDir}`);
}

// Function to generate SEO-friendly slug
function slugify(text) {
    if (!text) return '';
    return text
        .toString()
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')           // Replace spaces with -
        .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
        .replace(/\-\-+/g, '-')         // Replace multiple - with single -
        .replace(/^-+/, '')             // Trim - from start of text
        .replace(/-+$/, '');            // Trim - from end of text
}

// -------------------------------------------------------------
// 1. Processing style.css
// -------------------------------------------------------------
console.log('\n--- Processing style.css ---');
const cssPath = path.join(__dirname, 'style.css');
if (fs.existsSync(cssPath)) {
    let cssContent = fs.readFileSync(cssPath, 'utf8');
    const cssSizeBefore = Buffer.byteLength(cssContent, 'utf8');
    
    // Match variables like --sf-img-152: url("data:image/avif;base64,...")
    // Using a regex that captures variable name, format (avif/webp/png/etc), and base64 data
    const cssRegex = /([\w-]+)\s*:\s*url\(\s*['\"]data:image\/(\w+);base64,([^'\"]+)['\"]\s*\)/g;
    
    let match;
    let replacements = [];
    
    while ((match = cssRegex.exec(cssContent)) !== null) {
        const fullMatch = match[0];
        const varName = match[1];
        const format = match[2];
        const base64Data = match[3];
        
        const slug = slugify(varName.replace(/^--/, ''));
        const fileName = `${slug || 'style-img'}.${format}`;
        const targetPath = path.join(imgDir, fileName);
        
        // Decode base64 and write file
        const buffer = Buffer.from(base64Data, 'base64');
        fs.writeFileSync(targetPath, buffer);
        console.log(`Extracted: style.css -> images/${fileName} (${(buffer.length / 1024).toFixed(1)} KB)`);
        
        replacements.push({
            search: fullMatch,
            replace: `${varName}: url("images/${fileName}")`
        });
    }
    
    // Apply replacements
    replacements.forEach(r => {
        cssContent = cssContent.replace(r.search, r.replace);
    });
    
    if (replacements.length > 0) {
        fs.writeFileSync(cssPath, cssContent, 'utf8');
        const cssSizeAfter = Buffer.byteLength(cssContent, 'utf8');
        console.log(`Saved style.css. Size reduced from ${(cssSizeBefore / 1024 / 1024).toFixed(2)} MB to ${(cssSizeAfter / 1024).toFixed(2)} KB!`);
    } else {
        console.log('No base64 images found in style.css.');
    }
} else {
    console.log('style.css not found.');
}

// -------------------------------------------------------------
// 2. Processing index.html
// -------------------------------------------------------------
console.log('\n--- Processing index.html ---');
const htmlPath = path.join(__dirname, 'index.html');
if (fs.existsSync(htmlPath)) {
    let htmlContent = fs.readFileSync(htmlPath, 'utf8');
    const htmlSizeBefore = Buffer.byteLength(htmlContent, 'utf8');
    
    // Matches tag and captures:
    // Group 1: Entire img tag
    // Group 2: The src attribute matching data:image/format;base64,data (handles optional quotes around src)
    // Group 3: The format (avif/webp/png)
    // Group 4: The base64 data
    const imgTagRegex = /(<img[^>]+src=(['\"]?)(data:image\/(\w+);base64,([A-Za-z0-9+/=]+))\2[^>]*>)/gi;
    
    let match;
    let matches = [];
    
    // Find all matches first to avoid regex exec index shifting during inline replacements
    while ((match = imgTagRegex.exec(htmlContent)) !== null) {
        matches.push({
            fullTag: match[1],
            quoteChar: match[2] || '', // could be empty if no quotes around src
            fullSrcData: match[3],
            format: match[4],
            base64Data: match[5]
        });
    }
    
    console.log(`Found ${matches.length} base64 images in index.html.`);
    
    let counter = 1;
    matches.forEach(m => {
        // Extract alt text from the tag to generate an SEO-friendly filename
        const altMatch = m.fullTag.match(/alt=['\"]([^'\"]*)['\"]/i);
        let altText = '';
        if (altMatch && altMatch[1]) {
            altText = altMatch[1];
        }
        
        let slug = slugify(altText);
        if (!slug) {
            slug = `image-${counter}`;
        }
        
        let fileName = `${slug}.${m.format}`;
        
        // Handle name collision
        let targetPath = path.join(imgDir, fileName);
        let suffix = 1;
        while (fs.existsSync(targetPath)) {
            fileName = `${slug}-${suffix}.${m.format}`;
            targetPath = path.join(imgDir, fileName);
            suffix++;
        }
        
        // Decode and save image
        const buffer = Buffer.from(m.base64Data, 'base64');
        fs.writeFileSync(targetPath, buffer);
        console.log(`Extracted: index.html -> images/${fileName} (${(buffer.length / 1024).toFixed(1)} KB) - Alt: "${altText || '(none)'}"`);
        
        // Standardize the replacement to always use double quotes: src="images/filename.ext"
        // Let's replace the raw data:image/... base64 inside the tag
        // We find the tag in the HTML and replace the base64 src with the file path
        const newSrc = `src="images/${fileName}"`;
        
        // To do this safely, we construct the replaced img tag
        // We replace the original src attribute with the new src attribute
        const originalSrcPattern = new RegExp(`src=${m.quoteChar}data:image\\/${m.format};base64,[A-Za-z0-9+/=]+${m.quoteChar}`, 'i');
        const newTag = m.fullTag.replace(originalSrcPattern, newSrc);
        
        htmlContent = htmlContent.replace(m.fullTag, newTag);
        counter++;
    });
    
    if (matches.length > 0) {
        fs.writeFileSync(htmlPath, htmlContent, 'utf8');
        const htmlSizeAfter = Buffer.byteLength(htmlContent, 'utf8');
        console.log(`Saved index.html. Size reduced from ${(htmlSizeBefore / 1024 / 1024).toFixed(2)} MB to ${(htmlSizeAfter / 1024).toFixed(2)} KB!`);
    } else {
        console.log('No base64 images found in index.html.');
    }
} else {
    console.log('index.html not found.');
}

console.log('\nAsset extraction completed successfully!');
