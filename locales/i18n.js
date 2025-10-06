// i18n.js - Sistema de traducción completo

class I18n {
  constructor() {
    this.currentLang = localStorage.getItem('language') || 'en';
    this.translations = {};
    this.observers = [];
  }

  // Cargar traducciones
  async loadTranslations() {
    try {
      const [es, en] = await Promise.all([
        fetch('./locales/es.json').then(r => r.json()),
        fetch('./locales/en.json').then(r => r.json())
      ]);
      
      this.translations = { es, en };
      return true;
    } catch (error) {
      console.error('Error loading translations:', error);
      return false;
    }
  }

  // Obtener traducción por clave
  t(key) {
    const keys = key.split('.');
    let value = this.translations[this.currentLang];
    
    for (const k of keys) {
      value = value?.[k];
    }
    
    return value || key;
  }

  // Cambiar idioma
  setLanguage(lang) {
    if (lang !== 'en' && lang !== 'es') return;
    
    this.currentLang = lang;
    localStorage.setItem('language', lang);
    this.notifyObservers();
  }

  // Obtener idioma actual
  getLanguage() {
    return this.currentLang;
  }

  // Suscribirse a cambios de idioma
  subscribe(callback) {
    this.observers.push(callback);
  }

  // Notificar cambios
  notifyObservers() {
    this.observers.forEach(callback => callback(this.currentLang));
  }

  // Traducir todo el DOM
  translateDOM() {
    // Traducir por atributo data-i18n
    document.querySelectorAll('[data-i18n]').forEach(element => {
      const key = element.getAttribute('data-i18n');
      element.textContent = this.t(key);
    });

    // Traducir placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
      const key = element.getAttribute('data-i18n-placeholder');
      element.placeholder = this.t(key);
    });

    // Traducir titles
    document.querySelectorAll('[data-i18n-title]').forEach(element => {
      const key = element.getAttribute('data-i18n-title');
      element.title = this.t(key);
    });

    // Traducir valores de input
    document.querySelectorAll('[data-i18n-value]').forEach(element => {
      const key = element.getAttribute('data-i18n-value');
      element.value = this.t(key);
    });

    // Traducir contenido HTML (para elementos con HTML interno)
    document.querySelectorAll('[data-i18n-html]').forEach(element => {
      const key = element.getAttribute('data-i18n-html');
      element.innerHTML = this.t(key);
    });
  }
}

// Instancia global
const i18n = new I18n();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', async () => {
  await i18n.loadTranslations();
  i18n.translateDOM();
  
  // Configurar botón de cambio de idioma
  const langToggle = document.getElementById('langToggle');
  if (langToggle) {
    // Actualizar texto del botón
    const updateButton = () => {
      const currentLang = i18n.getLanguage();
      langToggle.textContent = currentLang === 'en' ? '🌐 Español' : '🌐 English';
    };
    
    updateButton();
    
    langToggle.addEventListener('click', () => {
      const newLang = i18n.getLanguage() === 'en' ? 'es' : 'en';
      i18n.setLanguage(newLang);
      i18n.translateDOM();
      updateButton();
    });
  }
  
  // Suscribirse a cambios de idioma para actualizar dinámicamente
  i18n.subscribe((lang) => {
    console.log('Language changed to:', lang);
    // Aquí puedes agregar lógica adicional cuando cambie el idioma
  });
});

// Exportar para uso global
window.i18n = i18n;