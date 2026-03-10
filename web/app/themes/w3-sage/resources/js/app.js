import.meta.glob([
  '../images/**',
  '../fonts/**',
]);
import 'fslightbox';
import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import translations from './translations';
// Importer le cœur de highlight.js
import hljs from 'highlight.js/lib/core';
import javascript from 'highlight.js/lib/languages/javascript';
import php from 'highlight.js/lib/languages/php';

// Enregistrer uniquement les langages nécessaires
hljs.registerLanguage('javascript', javascript);
hljs.registerLanguage('php', php);

// Puis initialiser
hljs.highlightAll();
// Importer le style CSS de votre choix (ex: github, monokai, atom-one-dark)
// Les styles se trouvent dans le dossier 'styles' du package npm
import 'highlight.js/styles/github-dark.css';

i18next
  .use(LanguageDetector)
  .init({
    fallbackLng: 'en',
    resources: translations
  });

document.addEventListener('DOMContentLoaded', () => {
  
  const button = document.getElementById('mobile-menu-button');
  const menu = document.getElementById('mobile-menu');
  const iconOpen = document.getElementById('icon-open');
  const iconClose = document.getElementById('icon-close');
  const body = document.body;
  
  if (button && menu) {
    button.addEventListener('click', () => {
      const isOpen = menu.classList.contains('translate-x-0');

      if (isOpen) {
        // Fermeture
        menu.classList.replace('translate-x-0', 'translate-x-full');
        iconOpen.classList.remove('hidden');
        iconClose.classList.add('hidden');
        body.style.overflow = ''; // Réactive le scroll
      } else {
        // Ouverture
        menu.classList.replace('translate-x-full', 'translate-x-0');
        iconOpen.classList.add('hidden');
        iconClose.classList.remove('hidden');
        body.style.overflow = 'hidden'; // Bloque le scroll
      }
    });
  }
  /**
   * Search form on clic
   */
  const searchButton = document.getElementById('search-form-button');
  const searchWrapper = document.getElementById('search-form-wrapper');
  const closeButton = document.getElementById('close-search'); // Optionnel si tu l'ajoutes

  const toggleSearch = (state) => {
      if (state === 'open') {
          searchWrapper.classList.remove('translate-x-full');
          searchWrapper.classList.add('translate-x-0');
          document.body.style.overflow = 'hidden'; // Bloque le scroll
          
          // Focus automatique sur le champ de recherche pour l'utilisateur
          const input = searchWrapper.querySelector('input[type="search"], input[type="text"]');
          if (input) setTimeout(() => input.focus(), 300); 
      } else {
          searchWrapper.classList.add('translate-x-full');
          searchWrapper.classList.remove('translate-x-0');
          document.body.style.overflow = ''; // Libère le scroll
      }
  };

  // Clic sur l'icône loupe
  searchButton.addEventListener('click', (e) => {
      e.preventDefault();
      const isOpen = searchWrapper.classList.contains('translate-x-0');
      toggleSearch(isOpen ? 'close' : 'open');
  });

  // Clic sur un bouton fermer (si présent)
  if (closeButton) {
      closeButton.addEventListener('click', () => toggleSearch('close'));
  }

  // Fermeture avec la touche Échap (Esc)
  document.addEventListener('keydown', (e) => {
      if (e.key === "Escape" && searchWrapper.classList.contains('translate-x-0')) {
          toggleSearch('close');
      }
  });

  // Fermeture en cliquant à côté du formulaire (sur l'overlay)
  searchWrapper.addEventListener('click', (e) => {
      if (e.target === searchWrapper) {
          toggleSearch('close');
      }
  });
  /*fin du content loaded*/
});

function setupCodeBlocks() {
const codeBlocks = document.querySelectorAll('pre.editor');

codeBlocks.forEach((block) => {
  // 1. Coloration des symboles $ (on le fait AVANT highlight.js)
  const rawContent = block.textContent;
  const lines = rawContent.split('\n');
  const processedHTML = lines.map(line => {
    if (line.trim().startsWith('$ ')) {
      return line.replace(/^\$ /, '<span class="text-yellow-500">$ </span>');
    }
    return line;
  }).join('\n');
  
  block.innerHTML = processedHTML;

  // 2. Application de Highlight.js
  hljs.highlightElement(block);

  // 3. Création du bouton (On l'ajoute dans un conteneur parent)
  const wrapper = document.createElement('div');
  wrapper.className = 'code-block-wrapper';
  wrapper.style.position = 'relative';
  
  // On entoure le block par le wrapper
  block.parentNode.insertBefore(wrapper, block);
  wrapper.appendChild(block);

  const button = document.createElement('button');
  const btnIcon = '<span class="mr-2"><svg aria-hidden="true" class="svg-icon iconCopy mr4 text-white h-5 w-5 fill-current" width="17" height="18" viewBox="0 0 17 18"><path d="M5 6c0-1.09.91-2 2-2h4.5L15 7.5V15c0 1.09-.91 2-2 2H7c-1.09 0-2-.91-2-2zm6-1.25V8h3.25z"></path><path d="M10 1a2 2 0 0 1 2 2H6a2 2 0 0 0-2 2v9a2 2 0 0 1-2-2V4a3 3 0 0 1 3-3z" opacity=".4"></path></svg></span>';
  const checkMark = '<span class="mr-2"><svg class="text-green-500 h-5 w-5 fill-current" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"><path d="M20.285 2l-11.285 11.567-5.286-5.011-3.714 3.716 9 8.728 15-15.285z"/></svg></span>';
   
  
  button.innerHTML = btnIcon + ' Copier';
  button.className = 'copy-code-button';
  wrapper.appendChild(button);

  // 4. Logique de copie propre
  button.addEventListener('click', () => {
    // On utilise textContent du bloc original (plus fiable que innerText)
    // On nettoie les lignes pour retirer le "$ " au début
    const cleanCode = rawContent.split('\n').map(line => {
      return line.trim().startsWith('$ ') ? line.trim().substring(2) : line;
    }).join('\n');

    navigator.clipboard.writeText(cleanCode).then(() => {
      button.innerHTML = checkMark + ' Copié !';
      setTimeout(() => {
        button.innerHTML = btnIcon + ' Copier';
      }, 2000);
    });
  });
});
}
document.addEventListener('DOMContentLoaded', setupCodeBlocks);
/**
 * Back to Top Button
 */
const backToTopButton = document.createElement('button');
backToTopButton.id = 'back-to-top';
backToTopButton.innerHTML = `<span class="sr-only">${i18next.t('backToTop')}</span><svg class="w-12 h-12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
 <path d="M16 12L12 8M12 8L8 12M12 8V16M7.8 21H16.2C17.8802 21 18.7202 21 19.362 20.673C19.9265 20.3854 20.3854 19.9265 20.673 19.362C21 18.7202 21 17.8802 21 16.2V7.8C21 6.11984 21 5.27976 20.673 4.63803C20.3854 4.07354 19.9265 3.6146 19.362 3.32698C18.7202 3 17.8802 3 16.2 3H7.8C6.11984 3 5.27976 3 4.63803 3.32698C4.07354 3.6146 3.6146 4.07354 3.32698 4.63803C3 5.27976 3 6.11984 3 7.8V16.2C3 17.8802 3 18.7202 3.32698 19.362C3.6146 19.9265 4.07354 20.3854 4.63803 20.673C5.27976 21 6.11984 21 7.8 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
 </svg>`;
backToTopButton.style.display = 'none';
backToTopButton.style.position = 'fixed';
backToTopButton.style.bottom = '20px';
backToTopButton.style.right = '20px';
backToTopButton.style.zIndex = '1000';
backToTopButton.style.color = 'var(--color-primary)';
backToTopButton.style.border = 'none';
backToTopButton.style.cursor = 'pointer';
backToTopButton.style.opacity = '0';
backToTopButton.style.transition = 'opacity 0.5s ease-in-out';
backToTopButton.style.transform = 'translateY(0)';

document.body.appendChild(backToTopButton);

window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTopButton.style.display = 'block';
        setTimeout(() => {
            backToTopButton.style.opacity = '1';
        }, 10);
    } else {
        backToTopButton.style.opacity = '0';
        setTimeout(() => {
            backToTopButton.style.display = 'none';
        }, 10);
    }
});
backToTopButton.addEventListener('mouseover', () => {
  backToTopButton.style.transform = 'translateY(-5px)';
});

backToTopButton.addEventListener('mouseout', () => {
  backToTopButton.style.transform = 'translateY(0)';
});

backToTopButton.addEventListener('mousedown', () => {
  backToTopButton.style.transform = 'translateY(-10px)';
});

backToTopButton.addEventListener('mouseup', () => {
  backToTopButton.style.transform = 'translateY(0)';
});
backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

/**
 * Search form suggestions
 */
// Fetch tags from WordPress REST API
async function fetchTags() {
    try {
        const response = await fetch('/wp-json/custom/v1/random-tags?number=10');

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Network response was not ok:', errorText);
            throw new Error('Network response was not ok');
        }

        const tags = await response.json();
        return tags.map(tag => {
            const txt = document.createElement("textarea");
            txt.innerHTML = tag.name;
            return txt.value;
        });
    } catch (error) {
        console.error('There was a problem with the fetch operation:', error);
        return [];
    }
}
// Popular search suggestions
let searchSuggestions = '';

// Update search suggestions with tags
async function updateSearchSuggestions() {
    const tags = await fetchTags();
    if (tags.length > 0) {
        // Update the outer searchSuggestions variable with the tags
        searchSuggestions = tags;
        // Initialize search suggestions after the searchSuggestions variable has been updated
        initializeSearchSuggestions();
    } else {
        console.log('No tags found, using default suggestions.');
    }
}

// Call the function to update search suggestions
updateSearchSuggestions();
// Initialize search suggestions
function initializeSearchSuggestions() {
  // On cible tous les inputs de recherche par leur classe
  const searchInputs = document.querySelectorAll(".search-input-field");
  
  if (searchInputs.length === 0) return;

  i18next
    .use(LanguageDetector)
    .init({
      fallbackLng: 'fr',
      resources: translations
    }).then(() => {
      let suggestionIndex = 0;

      // Fonction pour mettre à jour TOUS les placeholders d'un coup
      function updateAllPlaceholders() {
        const currentTag = searchSuggestions[suggestionIndex];
        const placeholderText = `${i18next.t('searchPlaceholder')} ${i18next.t('searchExample', { suggestion: currentTag })}`;
        
        searchInputs.forEach(input => {
          // On ne change le placeholder que si l'utilisateur n'est pas en train d'écrire
          if (input.value === "") {
            input.placeholder = placeholderText;
          }
        });

        suggestionIndex = (suggestionIndex + 1) % searchSuggestions.length;
      }

      // Lancement initial
      updateAllPlaceholders();

      // Rotation toutes les 3 secondes
      setInterval(updateAllPlaceholders, 3000);
    });
}
