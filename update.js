const fs = require('fs');
let content = fs.readFileSync('index.html', 'utf8');

const oldScript = `  <script>
    lucide.createIcons();
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mainContent = document.getElementById('main-content');
    
    mobileMenuButton.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
      mainContent.classList.toggle('blur-sm');
      mainContent.classList.toggle('brightness-75');
    });
  </script>`;

const newScript = `  <script>
    lucide.createIcons();
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mainContent = document.getElementById('main-content');
    
    mobileMenuButton.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
      mainContent.classList.toggle('blur-sm');
      mainContent.classList.toggle('opacity-50');
    });
  </script>`;

content = content.replace(oldScript, newScript);
fs.writeFileSync('index.html', content);
