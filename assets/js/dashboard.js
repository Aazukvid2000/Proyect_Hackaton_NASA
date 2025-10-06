/**
 * NASA BIO RESEARCH PLATFORM - DASHBOARD SCRIPT
 * Conexión completa Frontend-Backend con búsqueda por IA
 */

// ============================================================
// VARIABLES GLOBALES
// ============================================================
let currentSearchTerm = '';
let allPublications = [];
let filteredPublications = [];

// ============================================================
// INICIALIZACIÓN
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Verificar primera visita
    if (window.sessionStorage && !sessionStorage.getItem('visited')) {
        document.getElementById('welcomeVideo').style.display = 'block';
        sessionStorage.setItem('visited', 'true');
    } else {
        document.getElementById('welcomeVideo').style.display = 'none';
    }
    
    // Cargar publicaciones iniciales
    loadInitialPublications();
    
    console.log('NASA Bio Research Platform cargado');
});

// ============================================================
// CARGAR PUBLICACIONES DESDE EL BACKEND
// ============================================================
async function loadInitialPublications() {
    try {
        // Cargar más visitadas
        const visitedResponse = await fetch('backend/api/publicaciones.php?action=mas_visitadas&limit=10');
        const visitedData = await visitedResponse.json();
        if (visitedData.success) {
            displayPublications(visitedData.data, 'mostVisited');
        }
        
        // Cargar más recientes
        const recentResponse = await fetch('backend/api/publicaciones.php?action=mas_recientes&limit=10');
        const recentData = await recentResponse.json();
        if (recentData.success) {
            displayPublications(recentData.data, 'mostRecent');
        }
        
        // Cargar más antiguas (fundacionales)
        const oldestResponse = await fetch('backend/api/publicaciones.php?action=mas_antiguas&limit=10');
        const oldestData = await oldestResponse.json();
        if (oldestData.success) {
            displayPublications(oldestData.data, 'oldest');
        }
        
    } catch (error) {
        console.error('Error cargando publicaciones:', error);
        showErrorMessage('No se pudieron cargar las publicaciones');
    }
}

// ============================================================
// MOSTRAR PUBLICACIONES EN CARDS
// ============================================================
function displayPublications(publications, containerId) {
    const container = document.getElementById(containerId);
    
    if (!publications || publications.length === 0) {
        container.innerHTML = '<p style="color: var(--text-gray); padding: 20px;">No hay publicaciones disponibles</p>';
        return;
    }
    
    container.innerHTML = publications.map(pub => `
        <div class="research-card" onclick="viewPublication(${pub.id})">
            <h3>${pub.titulo}</h3>
            <p>${pub.resumen.substring(0, 150)}${pub.resumen.length > 150 ? '...' : ''}</p>
            <div class="research-meta">
                <span>👤 ${pub.autor || 'Anónimo'}</span>
                <span>📅 ${formatDate(pub.fecha_publicacion)}</span>
            </div>
            <div class="research-meta">
                <span>🏷️ ${pub.categoria}</span>
                <span>👁️ ${pub.vistas} vistas</span>
            </div>
        </div>
    `).join('');
}

// ============================================================
// BÚSQUEDA CON IA (CONEXIÓN CON TU ai_search.php)
// ============================================================
async function handleSearch(event) {
    const query = event.target.value.trim();
    
    if (query.length > 2) {
        currentSearchTerm = query;
        document.getElementById('homeView').style.display = 'none';
        document.getElementById('searchResults').classList.add('active');
        
        // Realizar búsqueda con IA
        await performAISearch(query);
    } else if (query.length === 0) {
        document.getElementById('homeView').style.display = 'block';
        document.getElementById('searchResults').classList.remove('active');
    }
}

async function performAISearch(query) {
    try {
        // Mostrar loading
        document.getElementById('resultsList').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="spinner" style="margin: 0 auto; border: 4px solid #f3f3f3; border-top: 4px solid var(--green-light); border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite;"></div>
                <p style="color: var(--text-gray); margin-top: 20px;">Buscando con IA...</p>
            </div>
        `;
        
        // Llamar a tu API de búsqueda con IA
        const response = await fetch('ai_search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `query=${encodeURIComponent(query)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            allPublications = data.results || [];
            filteredPublications = [...allPublications];
            displaySearchResults(filteredPublications);
        } else {
            document.getElementById('resultsList').innerHTML = `
                <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                    No se encontraron resultados para "${query}"
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Error en búsqueda:', error);
        document.getElementById('resultsList').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #ff6b6b;">
                Error al realizar la búsqueda. Por favor intenta de nuevo.
            </div>
        `;
    }
}

// ============================================================
// MOSTRAR RESULTADOS DE BÚSQUEDA
// ============================================================
function displaySearchResults(results) {
    const resultsList = document.getElementById('resultsList');
    const resultCount = document.getElementById('resultCount');
    
    resultCount.textContent = results.length;
    
    if (results.length === 0) {
        resultsList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                No se encontraron resultados. Intenta con otros términos de búsqueda.
            </div>
        `;
        return;
    }
    
    resultsList.innerHTML = results.map(item => `
        <div class="result-item">
            <h3>${item.titulo}</h3>
            <p style="color: var(--text-gray); margin: 10px 0;">${item.resumen}</p>
            ${item.contenido_extraido ? `
                <p style="color: var(--text-gray); font-size: 0.9em; margin: 10px 0; font-style: italic;">
                    ${item.contenido_extraido.substring(0, 200)}...
                </p>
            ` : ''}
            <div class="meta">
                <span>👤 ${item.autor || 'Anónimo'}</span>
                <span>📅 ${formatDate(item.fecha_publicacion)}</span>
                <span>🏷️ ${item.categoria}</span>
                <span>⭐ ${item.relevancia_score}/10</span>
            </div>
            ${item.mision ? `<div class="meta"><span>🚀 ${item.mision}</span></div>` : ''}
            <button class="btn-download" onclick="downloadResearch(${item.id}, '${item.titulo}')">
                📥 Descargar Investigación
            </button>
            <button class="btn-download" onclick="viewPublication(${item.id})" 
                    style="background: var(--purple-main); margin-left: 10px;">
                👁️ Ver Detalles
            </button>
        </div>
    `).join('');
}

// ============================================================
// APLICAR FILTROS
// ============================================================
function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const yearFilter = document.getElementById('yearFilter').value;
    const authorFilter = document.getElementById('authorFilter').value.toLowerCase();
    
    filteredPublications = allPublications.filter(item => {
        let matches = true;
        
        if (categoryFilter && item.categoria_id != categoryFilter) {
            matches = false;
        }
        
        if (yearFilter && new Date(item.fecha_publicacion).getFullYear() != yearFilter) {
            matches = false;
        }
        
        if (authorFilter && !item.autor.toLowerCase().includes(authorFilter)) {
            matches = false;
        }
        
        return matches;
    });
    
    displaySearchResults(filteredPublications);
}

// ============================================================
// ORDENAR RESULTADOS
// ============================================================
function sortResults() {
    const sortBy = document.getElementById('sortBy').value;
    
    switch(sortBy) {
        case 'alphabetical':
            filteredPublications.sort((a, b) => a.titulo.localeCompare(b.titulo));
            break;
        case 'date':
            filteredPublications.sort((a, b) => new Date(b.fecha_publicacion) - new Date(a.fecha_publicacion));
            break;
        case 'author':
            filteredPublications.sort((a, b) => (a.autor || '').localeCompare(b.autor || ''));
            break;
        case 'relevance':
            filteredPublications.sort((a, b) => (b.relevancia_score || 0) - (a.relevancia_score || 0));
            break;
    }
    
    displaySearchResults(filteredPublications);
}

// ============================================================
// FILTRAR POR CATEGORÍA (desde navbar)
// ============================================================
async function filterByCategory(category) {
    try {
        document.getElementById('homeView').style.display = 'none';
        document.getElementById('searchResults').classList.add('active');
        
        let url = 'backend/api/publicaciones.php?action=todas';
        if (category !== 'todas') {
            const catId = category === 'flora' ? 1 : 2;
            url += `&categoria_id=${catId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allPublications = data.data || [];
            filteredPublications = [...allPublications];
            displaySearchResults(filteredPublications);
        }
    } catch (error) {
        console.error('Error filtrando por categoría:', error);
    }
}

// ============================================================
// VER DETALLES DE PUBLICACIÓN
// ============================================================
async function viewPublication(id) {
    try {
        const response = await fetch(`backend/api/publicaciones.php?action=detalle&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            showPublicationModal(data.data);
            
            // Incrementar vistas
            fetch(`backend/api/publicaciones.php?action=incrementar_vista&id=${id}`, {
                method: 'POST'
            });
        }
    } catch (error) {
        console.error('Error cargando detalles:', error);
    }
}

function showPublicationModal(pub) {
    const modalBody = document.getElementById('modalBody');
    modalBody.innerHTML = `
        <h2>${pub.titulo}</h2>
        <div style="margin: 20px 0; padding: 15px; background: rgba(76, 175, 80, 0.1); border-radius: 8px;">
            <p><strong>Autor:</strong> ${pub.autor}</p>
            <p><strong>Categoría:</strong> ${pub.categoria}</p>
            <p><strong>Fecha:</strong> ${formatDate(pub.fecha_publicacion)}</p>
            ${pub.mision ? `<p><strong>Misión:</strong> ${pub.mision}</p>` : ''}
            ${pub.organismo_estudio ? `<p><strong>Organismo:</strong> ${pub.organismo_estudio}</p>` : ''}
            <p><strong>Vistas:</strong> ${pub.vistas}</p>
        </div>
        <h3 style="color: var(--green-light); margin: 20px 0 10px 0;">Resumen</h3>
        <p style="color: var(--text-gray); line-height: 1.8;">${pub.resumen}</p>
        ${pub.contenido_extraido ? `
            <h3 style="color: var(--green-light); margin: 20px 0 10px 0;">Contenido</h3>
            <p style="color: var(--text-gray); line-height: 1.8; max-height: 300px; overflow-y: auto;">
                ${pub.contenido_extraido}
            </p>
        ` : ''}
        ${pub.keywords ? `
            <h3 style="color: var(--green-light); margin: 20px 0 10px 0;">Palabras Clave</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                ${pub.keywords.split(',').map(kw => `
                    <span style="background: var(--purple-main); padding: 5px 15px; border-radius: 15px; font-size: 0.9em;">
                        ${kw.trim()}
                    </span>
                `).join('')}
            </div>
        ` : ''}
        <button class="btn-download" onclick="downloadResearch(${pub.id}, '${pub.titulo}')" style="margin-top: 20px;">
            📥 Descargar Documento
        </button>
    `;
    
    document.getElementById('infoModal').classList.add('show');
}

// ============================================================
// DESCARGAR INVESTIGACIÓN
// ============================================================
async function downloadResearch(id, title) {
    try {
        const response = await fetch(`backend/api/publicaciones.php?action=descargar&id=${id}`);
        const data = await response.json();
        
        if (data.success && data.archivo) {
            // Abrir archivo en nueva ventana
            window.open(data.archivo, '_blank');
            
            // Registrar descarga
            fetch(`backend/api/publicaciones.php?action=incrementar_descarga&id=${id}`, {
                method: 'POST'
            });
        } else {
            alert('No se pudo descargar el archivo');
        }
    } catch (error) {
        console.error('Error descargando:', error);
        alert('Error al descargar el archivo');
    }
}

// ============================================================
// UI FUNCTIONS
// ============================================================
function closeWelcome() {
    document.getElementById('welcomeVideo').style.display = 'none';
}

function toggleMenu() {
    document.getElementById('menuDropdown').classList.toggle('show');
}

function closeModal() {
    document.getElementById('infoModal').classList.remove('show');
}

function showModal(type) {
    const modalBody = document.getElementById('modalBody');
    
    const content = {
        'terminos': `
            <h2>📋 Términos y Condiciones</h2>
            <p><strong>Última actualización:</strong> Octubre 2025</p>
            <p>Al acceder y utilizar NASA Bio Research Platform, usted acepta cumplir con los siguientes términos y condiciones:</p>
            <p><strong>1. Uso de la Plataforma:</strong> Esta plataforma está destinada exclusivamente para fines de investigación científica y educativos.</p>
            <p><strong>2. Propiedad Intelectual:</strong> Todo el contenido publicado en la plataforma es propiedad de sus respectivos autores y está protegido por leyes de derechos de autor.</p>
            <p><strong>3. Responsabilidad del Usuario:</strong> Los investigadores son responsables de la veracidad y originalidad del contenido que publican.</p>
        `,
        'nosotros': `
            <h2>ℹ️ Acerca de Nosotros</h2>
            <p>NASA Bio Research Platform es una iniciativa dedicada a centralizar y compartir investigaciones científicas sobre biología espacial.</p>
            <p><strong>Nuestra Misión:</strong> Facilitar el acceso global a conocimientos sobre flora y fauna en entornos espaciales, promoviendo la colaboración entre investigadores de todo el mundo.</p>
        `,
        'privacidad': `
            <h2>🔒 Política de Privacidad</h2>
            <p><strong>Recopilación de Datos:</strong> Recopilamos información personal necesaria para la gestión de cuentas de investigadores.</p>
            <p><strong>Protección de Datos:</strong> Implementamos medidas de seguridad técnicas y organizativas para proteger su información.</p>
        `,
        'contacto': `
            <h2>✉️ Información de Contacto</h2>
            <div class="contact-info">
                <div class="contact-item">
                    <span>📧</span>
                    <div>
                        <strong>Email:</strong><br>
                        <a href="mailto:santiagogabrielfloresruiz@gmail.com">santiagogabrielfloresruiz@gmail.com</a>
                    </div>
                </div>
            </div>
        `,
        'ayuda': `
            <h2>❓ Centro de Ayuda</h2>
            <p><strong>¿Cómo busco investigaciones?</strong><br>
            Utiliza la barra de búsqueda principal e ingresa palabras clave. El sistema usa IA para encontrar las mejores coincidencias.</p>
            <p><strong>¿Cómo publico mi investigación?</strong><br>
            Debes iniciar sesión como investigador y usar el botón "Subir Publicación".</p>
        `
    };
    
    modalBody.innerHTML = content[type] || '<p>Contenido no disponible</p>';
    document.getElementById('infoModal').classList.add('show');
    document.getElementById('menuDropdown').classList.remove('show');
}

// ============================================================
// UTILIDADES
// ============================================================
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
}

function showErrorMessage(message) {
    console.error(message);
}

// Cerrar dropdown al hacer clic fuera
window.onclick = function(event) {
    if (!event.target.matches('.menu-btn')) {
        const dropdowns = document.getElementsByClassName('dropdown-content');
        for (let i = 0; i < dropdowns.length; i++) {
            dropdowns[i].classList.remove('show');
        }
    }
    
    const modal = document.getElementById('infoModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Búsqueda desde navbar
document.getElementById('navSearch').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('mainSearch').value = this.value;
        handleSearch({ target: document.getElementById('mainSearch') });
    }
});