const fs = require('fs');
const path = require('path');

const galeriaPath = 'galeria.html';
const galleryOutputPath = 'scratch/gallery_output.html';

// Read original gallery page and generated HTML
let galeriaHtml = fs.readFileSync(galeriaPath, 'utf8');
const generatedGrid = fs.readFileSync(galleryOutputPath, 'utf8');

// Define unique start and end boundaries
const startTag = '<div id="gallery-grid" class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4 space-y-4">';
const endTag = '</div><!-- /gallery-grid -->';

const startIndex = galeriaHtml.indexOf(startTag);
const endIndex = galeriaHtml.indexOf(endTag);

if (startIndex === -1 || endIndex === -1) {
    console.error('Could not find masonry grid wrapper tags in galeria.html!');
    process.exit(1);
}

// Slice and replace content
const beforeGrid = galeriaHtml.substring(0, startIndex + startTag.length);
const afterGrid = galeriaHtml.substring(endIndex);

const updatedHtml = beforeGrid + '\n\n' + generatedGrid.trim() + '\n\n            ' + afterGrid;

// Write updated HTML back to galeria.html
fs.writeFileSync(galeriaPath, updatedHtml, 'utf8');
console.log('Successfully updated galeria.html with 52 actual categorized gallery items!');
