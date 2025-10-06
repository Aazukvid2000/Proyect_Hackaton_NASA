-- ============================================================
-- INICIALIZACIÓN DE BASE DE DATOS NASA RESEARCH
-- Base de datos completa con usuarios y artículos
-- ============================================================

CREATE DATABASE IF NOT EXISTS nasa_research_db;
USE nasa_research_db;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ============================================================
-- CREACIÓN DE TABLAS DE SISTEMA
-- ============================================================

-- Tabla de roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, nombre, descripcion) VALUES
(1, 'lector', 'Usuario que solo puede leer investigaciones'),
(2, 'investigador', 'Usuario que puede publicar investigaciones'),
(3, 'admin', 'Administrador con todos los permisos')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    institucion VARCHAR(255),
    afiliacion VARCHAR(255),
    rol_id INT DEFAULT 1,
    estado ENUM('activo', 'pendiente', 'inactivo') DEFAULT 'activo',
    foto_perfil VARCHAR(500),
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    INDEX idx_email (email),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERCIÓN DE USUARIO ADMINISTRADOR POR DEFECTO
-- ============================================================

-- Insertar usuario admin por defecto
-- Email: admin@nasa.gov
-- Password: Admin123!
INSERT INTO usuarios (nombre_completo, email, password_hash, institucion, afiliacion, rol_id, estado) VALUES
('Administrador Sistema', 'admin@nasa.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'NASA', 'Administrador de Sistema', 3, 'activo')
ON DUPLICATE KEY UPDATE nombre_completo=nombre_completo;

-- ============================================================
-- CREACIÓN DE TABLAS DE CONTENIDO
-- ============================================================

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articulos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255),
    resumen TEXT,
    contenido TEXT,
    url_documento VARCHAR(500),
    fecha_publicacion DATE,
    categoria_id INT,
    relevancia_score DECIMAL(3,1) DEFAULT 5.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_categoria (categoria_id),
    INDEX idx_fecha (fecha_publicacion),
    INDEX idx_relevancia (relevancia_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS keywords (
    id INT AUTO_INCREMENT PRIMARY KEY,
    palabra VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articulo_keywords (
    articulo_id INT,
    keyword_id INT,
    PRIMARY KEY (articulo_id, keyword_id),
    FOREIGN KEY (articulo_id) REFERENCES articulos(id) ON DELETE CASCADE,
    FOREIGN KEY (keyword_id) REFERENCES keywords(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERCIÓN DE CATEGORÍAS
-- ============================================================

INSERT INTO categorias (nombre, descripcion) VALUES
('Flora', 'Estudios sobre plantas y organismos vegetales en el espacio'),
('Fauna', 'Estudios sobre animales y organismos en microgravedad')
ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion);

-- ============================================================
-- ARTÍCULOS DE FLORA (15 estudios)
-- ============================================================

INSERT INTO articulos (titulo, autor, resumen, contenido, url_documento, fecha_publicacion, categoria_id, relevancia_score) VALUES
('Arabidopsis (modelo) - Arabidopsis thaliana', 'Anna-Lisa Paul; Robert J. Ferl; Agata K. Zupanska', 'Planta modelo de la familia Brassicaceae estudiada en microgravedad para cuantificar cambios transcriptómicos. Se observaron reprogramaciones en rutas de estrés y pared celular, mostrando plasticidad de desarrollo.', 'Planta modelo de la familia Brassicaceae, ciclo corto y genoma bien anotado; ampliamente usada para estudiar respuestas a estrés.\n\nObjetivo: Cuantificar cambios transcriptómicos de plántulas en microgravedad real frente a 1g.\n\nResultados: Se observaron reprogramaciones en rutas de estrés y pared celular, mostrando plasticidad de desarrollo en microgravedad.\n\nMisión: ISS / BRIC/APEX\nInstitución: University of Florida; NASA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-7', '2020-01-01', 1, 9.5),
('Arabidopsis (raíces, CARA) - A. thaliana (Col-0, Ws, phyD)', 'Paul; Ferl; colaboradores CARA', 'Estudio de raíces de Arabidopsis como sistema sensorial para fototropismo y gravitropismo en el payload CARA. Patrones transcripcionales distintos dependientes de luz y tejido.', 'Raíces de Arabidopsis como sistema sensorial para fototropismo y gravitropismo.\n\nObjetivo: Separar efectos de luz vs microgravedad en el desarrollo radicular (payload CARA).\n\nResultados: Patrones transcripcionales distintos dependientes de luz y tejido en condiciones de vuelo.\n\nMisión: ISS / CARA\nInstitución: University of Florida; NASA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-120', '2020-06-01', 1, 9.0),
('Arabidopsis (ecotipos) - A. thaliana múltiples ecotipos', 'Paul; Ferl; colaboradores BRIC', 'Comparación de respuesta de múltiples ecotipos naturales de Arabidopsis a microgravedad mediante RNA-seq. Variabilidad dependiente del genotipo detectada.', 'Ecotipos naturales de Arabidopsis con variación genética documentada.\n\nObjetivo: Comparar respuesta de múltiples ecotipos a microgravedad mediante RNA-seq.\n\nResultados: Se detectó variabilidad dependiente del genotipo en la respuesta al entorno espacial.\n\nMisión: ISS / BRIC\nInstitución: University of Florida; NASA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-37', '2019-09-01', 1, 8.8),
('Arabidopsis (células, SIMBOX) - A. thaliana cultivo celular', 'Ferl; Paul; Guan; equipo SIMBOX', 'Estudio de cultivos celulares de Arabidopsis en microgravedad mediante experimento SIMBOX. Efectos en pared celular y citosqueleto observados.', 'Cultivos de células en suspensión de Arabidopsis; líneas CC125 y Col-0.\n\nObjetivo: Analizar transducción de señales y reorganización celular sin gravedad (SIMBOX).\n\nResultados: Efectos en pared celular y citosqueleto, respuesta temprana al entorno de vuelo.\n\nMisión: ISS / SIMBOX\nInstitución: University of Florida; NASA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-186', '2019-12-01', 1, 8.5),
('Arabidopsis (epigenética) - A. thaliana metilación', 'Ferl; Paul; Zupanska', 'Análisis epigenético de Arabidopsis en microgravedad, evaluando metilación del ADN y expresión génica. Cambios hereditarios vs ambientales detectados.', 'Análisis de metilación del ADN en líneas transgénicas y tipo silvestre.\n\nObjetivo: Evaluar cambios epigenéticos (metilación) en vuelo espacial.\n\nResultados: Cambios en metilación, posibles mecanismos de adaptación hereditaria vs ambiental.\n\nMisión: ISS\nInstitución: University of Florida; NASA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-186', '2021-03-01', 1, 9.2),
('Lechuga (Veggie) - Lactuca sativa', 'Massa; Stutte; Wheeler; equipo Veggie', 'Estudio de producción de lechuga en la ISS usando sistema Veggie para seguridad alimentaria. Evaluación nutricional y microbiológica exitosa.', 'Lechuga cultivada en cámara de crecimiento Veggie (ISS) con LEDs ajustados.\n\nObjetivo: Validar producción de hoja verde fresca para alimentación tripulada (Veggie).\n\nResultados: Hojas comestibles y con perfil nutricional aceptable, baja carga microbiana.\n\nMisión: ISS / Veggie\nInstitución: NASA Kennedy', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-38', '2016-08-01', 1, 9.8),
('Lechuga (variedad roja, Veggie)', 'Massa; Stutte; colaboradores', 'Lechuga roja cultivada en Veggie para diversidad nutricional y seguridad alimentaria en misiones de larga duración.', 'Variedad roja de lechuga con antocianinas.\n\nObjetivo: Evaluar rendimiento y calidad nutricional de variedades pigmentadas.\n\nResultados: Cultivo exitoso con antioxidantes detectables, validación de crecimiento.\n\nMisión: ISS / Veggie\nInstitución: NASA Kennedy', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-120', '2017-05-01', 1, 9.0),
('Girasol (Heliotropismo)', 'Stutte; Massa; colaboradores', 'Estudio de respuesta heliotrópica del girasol en microgravedad usando LED direccional.', 'Planta de girasol cultivada bajo LED direccional para estudio de heliotropismo.\n\nObjetivo: Determinar si el heliotropismo persiste sin campo gravitacional terrestre.\n\nResultados: Respuesta fototrópica activa, adaptación del tallo en ausencia de gravedad.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov/bio/repo/data/experiments/OS-800', '2018-03-01', 1, 8.3),
('Trigo enano (sistema raíz)', 'Brinckmann; Stutte', 'Análisis del desarrollo de raíces de trigo enano en microgravedad.', 'Trigo enano con sistema radicular compacto.\n\nObjetivo: Estudiar arquitectura de raíz en ausencia de gravedad.\n\nResultados: Raíces con crecimiento aleatorio, sin tropismo gravitatorio dominante.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov', '2019-07-01', 1, 8.0),
('Arabidopsis (respuesta estrés oxidativo)', 'Paul; Ferl', 'Evaluación de mecanismos antioxidantes en Arabidopsis bajo microgravedad.', 'Líneas transgénicas de Arabidopsis con marcadores de estrés oxidativo.\n\nObjetivo: Caracterizar respuesta antioxidante en condiciones espaciales.\n\nResultados: Upregulation de genes antioxidantes, adaptación al estrés.\n\nMisión: ISS\nInstitución: University of Florida', 'https://osdr.nasa.gov', '2020-11-01', 1, 8.7),
('Pimiento (capsicum, Veggie)', 'Massa; colaboradores Veggie', 'Cultivo de pimientos en ISS para diversificar dieta de tripulación.', 'Capsicum annuum cultivado en Veggie.\n\nObjetivo: Ampliar variedad de cultivos frescos disponibles en órbita.\n\nResultados: Fructificación exitosa, flavor y nutrientes preservados.\n\nMisión: ISS / Veggie\nInstitución: NASA Kennedy', 'https://osdr.nasa.gov', '2021-06-01', 1, 9.1),
('Tomate (miniatura, APH)', 'Wheeler; Massa', 'Producción de tomates cherry en Advanced Plant Habitat.', 'Tomate miniatura cultivado en APH con control atmosférico avanzado.\n\nObjetivo: Validar producción de frutos en entorno controlado de larga duración.\n\nResultados: Frutas maduras con sabor, demostración de sistema cerrado.\n\nMisión: ISS / APH\nInstitución: NASA', 'https://osdr.nasa.gov', '2022-01-01', 1, 9.3),
('Zinnia (floración)', 'Massa; Stutte; Morrow', 'Estudio de floración de zinnia en Veggie como modelo ornamental.', 'Zinnia elegans cultivada para estudiar floración en microgravedad.\n\nObjetivo: Evaluar ciclo reproductivo completo de planta ornamental.\n\nResultados: Floración exitosa, polinización manual demostrada.\n\nMisión: ISS / Veggie\nInstitución: NASA', 'https://osdr.nasa.gov', '2016-02-01', 1, 8.2),
('Microverdes mixtos', 'Wheeler; Massa', 'Cultivo de microverdes para cosecha rápida y alta densidad nutricional.', 'Variedades de microverdes (rábano, mostaza) en sustrato mínimo.\n\nObjetivo: Evaluar viabilidad de cultivos ultra-rápidos para suplemento fresco.\n\nResultados: Germinación rápida, biomasa rica en nutrientes en <15 días.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov', '2020-09-01', 1, 8.4),
('Soja (glycine, nódulos)', 'Brinckmann; colaboradores', 'Estudio de nodulación y fijación de nitrógeno en soja cultivada en órbita.', 'Glycine max cultivada para estudiar simbiosis rizobio en microgravedad.\n\nObjetivo: Determinar si la fijación de N2 persiste sin gravedad.\n\nResultados: Nódulos formados, fijación activa detectada en análisis de isótopos.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov', '2019-04-01', 1, 8.6);

-- ============================================================
-- ARTÍCULOS DE FAUNA (15 estudios)
-- ============================================================

INSERT INTO articulos (titulo, autor, resumen, contenido, url_documento, fecha_publicacion, categoria_id, relevancia_score) VALUES
('Mosca de la fruta (corazón) - Drosophila melanogaster', 'Bhattacharya; Ocorr; Bodmer', 'Estudio de efectos de microgravedad prolongada en función cardiaca de Drosophila usando Fruit Fly Lab-02. Cambios fisiológicos evaluados en órbita.', 'Mosca de la fruta, invertebrado modelo con ciclo corto; útil para genética, corazón y envejecimiento.\n\nObjetivo: Medir efectos de microgravedad prolongada en función cardiaca y fisiología.\n\nResultados: La plataforma FFL-02 permitió evaluar cambios fisiológicos y validar el modelo en órbita.\n\nMisión: ISS / Fruit Fly Lab-02\nInstitución: NASA Ames; SBP', 'https://osdr.nasa.gov/bio/repo/data/experiments/OS-801', '2019-01-01', 2, 9.0),
('Nematodo (músculo) - C. elegans', 'Adenle; Johnsen; Szewczyk', 'Investigación de atrofia muscular en C. elegans durante vuelo espacial como modelo de sarcopenia. Pérdida de masa muscular observada.', 'Caenorhabditis elegans, nematodo transparente, genética bien definida, vida corta.\n\nObjetivo: Caracterizar atrofia muscular y evaluar mecanismos moleculares en vuelo espacial.\n\nResultados: Pérdida de masa muscular comparable a sarcopenia terrestre, modelo validado.\n\nMisión: ISS\nInstitución: NASA Ames; ExSPA', 'https://osdr.nasa.gov/bio/repo/data/studies/OSD-37', '2018-05-01', 2, 8.8),
('Ratón (microbioma intestinal)', 'Morrison; NASA Ames', 'Análisis de cambios en microbioma intestinal de ratones durante misión espacial de larga duración.', 'Ratones C57BL/6 para estudio de microbiota en microgravedad.\n\nObjetivo: Evaluar disbiosis y función inmune asociada al microbioma.\n\nResultados: Cambios en diversidad microbiana, impacto en metabolismo.\n\nMisión: ISS / Rodent Research\nInstitución: NASA Ames', 'https://osdr.nasa.gov', '2019-11-01', 2, 9.5),
('Ratón (pérdida ósea)', 'Globus; Morey-Holton', 'Estudio de osteoporosis inducida por microgravedad en modelo murino.', 'Ratones para análisis de desmineralización ósea.\n\nObjetivo: Cuantificar pérdida de densidad mineral y cambios estructurales.\n\nResultados: Pérdida ósea significativa en fémur y vértebras.\n\nMisión: ISS / Rodent Research\nInstitución: NASA Ames', 'https://osdr.nasa.gov', '2020-02-01', 2, 9.3),
('Pez cebra (desarrollo embrionario)', 'Chatani; Kawakami', 'Observación de desarrollo embrionario de pez cebra en microgravedad.', 'Danio rerio, modelo vertebrado con desarrollo externo observable.\n\nObjetivo: Caracterizar morfogénesis y organogénesis en ausencia de gravedad.\n\nResultados: Desarrollo normal con variaciones menores en orientación.\n\nMisión: ISS\nInstitución: JAXA; NASA', 'https://osdr.nasa.gov', '2017-09-01', 2, 8.5),
('Tardigrado (criptobiosis)', 'Jönsson; Guidetti', 'Estudio de supervivencia de tardígrados en condiciones extremas del espacio exterior.', 'Tardigrados en estado criptobiótico expuestos al vacío espacial.\n\nObjetivo: Evaluar límites de supervivencia biológica.\n\nResultados: Supervivencia tras exposición, recuperación al hidratarse.\n\nMisión: Experimento externo ISS\nInstitución: ESA', 'https://osdr.nasa.gov', '2016-04-01', 2, 9.7),
('Medaka (radiación)', 'Shimada; Muratani', 'Análisis de efectos de radiación cósmica en peces medaka cultivados en ISS.', 'Oryzias latipes como modelo para estudios de radiobiología espacial.\n\nObjetivo: Cuantificar daño al ADN por radiación en tejidos vivos.\n\nResultados: Daño genético detectable, reparación celular activa.\n\nMisión: ISS\nInstitución: JAXA', 'https://osdr.nasa.gov', '2018-08-01', 2, 8.9),
('Drosophila (neurobiología)', 'Benguria; Marco', 'Estudio de cambios neurológicos en Drosophila durante exposición prolongada a microgravedad.', 'Moscas para evaluar plasticidad neuronal y comportamiento.\n\nObjetivo: Caracterizar adaptación del sistema nervioso.\n\nResultados: Cambios en conexiones sinápticas, alteraciones conductuales.\n\nMisión: ISS\nInstitución: UPV; ESA', 'https://osdr.nasa.gov', '2019-06-01', 2, 8.4),
('C. elegans (longevidad)', 'Honda; Higashibata', 'Evaluación de longevidad y envejecimiento en C. elegans bajo microgravedad.', 'Nematodos para análisis de factores de longevidad.\n\nObjetivo: Determinar si microgravedad afecta expectativa de vida.\n\nResultados: Reducción de lifespan, estrés oxidativo incrementado.\n\nMisión: ISS\nInstitución: JAXA', 'https://osdr.nasa.gov', '2020-07-01', 2, 8.2),
('Ratón (inmunología)', 'Crucian; Sams', 'Análisis de función inmune adaptativa e innata en ratones espaciales.', 'Ratones para estudio de respuesta inmunológica.\n\nObjetivo: Evaluar competencia inmune en vuelo.\n\nResultados: Supresión parcial de función T-cell, reactivación viral.\n\nMisión: ISS / Rodent Research\nInstitución: NASA JSC', 'https://osdr.nasa.gov', '2021-03-01', 2, 9.0),
('Medusa (gravedad)', 'Spangenberg; Helm', 'Estudio de desarrollo de medusas en microgravedad como modelo de orientación.', 'Aurelia aurita cultivada para estudiar graviceptores.\n\nObjetivo: Analizar desarrollo de estatocistos sin gravedad.\n\nResultados: Desarrollo anormal de órganos sensoriales.\n\nMisión: Space Shuttle\nInstitución: NASA', 'https://osdr.nasa.gov', '1991-05-01', 2, 7.8),
('Hormiga (comportamiento)', 'Debolt; NASA', 'Observación de comportamiento social de hormigas en microgravedad.', 'Colonias de Tetramorium para etología espacial.\n\nObjetivo: Evaluar organización social sin gravedad.\n\nResultados: Comportamiento de túnel alterado, comunicación química intacta.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov', '2014-01-01', 2, 7.5),
('Rotífero (reproducción)', 'Ricci; Caprioli', 'Estudio de reproducción partenogenética de rotíferos en el espacio.', 'Bdelloid rotifers para análisis reproductivo.\n\nObjetivo: Determinar viabilidad reproductiva en microgravedad.\n\nResultados: Reproducción exitosa, viabilidad de descendencia.\n\nMisión: ISS\nInstitución: ESA', 'https://osdr.nasa.gov', '2017-11-01', 2, 8.0),
('Camarón (desarrollo)', 'Barratt; NASA', 'Análisis de desarrollo larvario de camarones de salmuera en microgravedad.', 'Artemia salina como modelo de desarrollo crustáceo.\n\nObjetivo: Observar eclosión y metamorfosis en órbita.\n\nResultados: Eclosión exitosa, desarrollo larval variable.\n\nMisión: ISS\nInstitución: NASA', 'https://osdr.nasa.gov', '2015-06-01', 2, 7.9),
('Pez cebra (regeneración)', 'Chatani; NASA', 'Estudio de capacidad regenerativa de pez cebra en microgravedad.', 'Danio rerio para estudiar regeneración de aletas.\n\nObjetivo: Evaluar si la regeneración persiste en el espacio.\n\nResultados: Regeneración exitosa con variaciones en velocidad.\n\nMisión: ISS\nInstitución: JAXA; NASA', 'https://osdr.nasa.gov', '2018-03-01', 2, 8.3);

-- ============================================================
-- KEYWORDS
-- ============================================================

INSERT INTO keywords (palabra) VALUES
('arabidopsis'), ('transcriptómica'), ('microgravedad'), ('bric'), ('apex'),
('raíces'), ('cara'), ('fototropismo'), ('ecotipos'), ('rna-seq'),
('células'), ('simbox'), ('epigenética'), ('metilación'), ('iss'),
('lechuga'), ('veggie'), ('lactuca'), ('nutrición'), ('seguridad alimentaria'),
('girasol'), ('heliotropismo'), ('trigo'), ('pimiento'), ('capsicum'),
('tomate'), ('aph'), ('zinnia'), ('floración'), ('microverdes'),
('soja'), ('nódulos'), ('fijación nitrógeno'), ('drosophila'), ('corazón'),
('fisiología'), ('fruit fly lab'), ('c elegans'), ('nematodo'), ('músculo'),
('atrofia'), ('sarcopenia'), ('ratón'), ('microbioma'), ('inmunología'),
('pérdida ósea'), ('osteoporosis'), ('pez cebra'), ('desarrollo embrionario'),
('tardigrado'), ('criptobiosis'), ('medaka'), ('radiación'), ('neurobiología'),
('longevidad'), ('medusa'), ('hormiga'), ('comportamiento'), ('rotífero'),
('camarón'), ('artemia'), ('vuelo espacial'), ('nasa'), ('osdr'),
('biología espacial'), ('adaptación'), ('estrés'), ('gravitropismo')
ON DUPLICATE KEY UPDATE id=id;

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================

SELECT 'Base de datos creada correctamente' as status,
       (SELECT COUNT(*) FROM usuarios) as total_usuarios,
       (SELECT COUNT(*) FROM articulos) as total_articulos,
       (SELECT COUNT(*) FROM keywords) as total_keywords;