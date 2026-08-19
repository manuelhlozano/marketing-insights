
-- 1. Asegurar metadata exacta de Junio 2026 en dashboards
UPDATE dashboards 
SET titulo = 'Informe Ejecutivo de Gestión Mensual | Junio 2026',
    slug = 'junio-2026',
    periodo = 'Junio 2026',
    fecha_inicio = '2026-06-01',
    fecha_fin = '2026-06-30',
    lema = 'Periodo Previo y Transición Operativa',
    descripcion_ejecutiva = 'Línea base previa a la gestión integral de Cibergenios.',
    resumen_aprendizaje = 'En junio se mantuvieron publicaciones regulares en Instagram y TikTok pero con bajo engagement orgánico hacia cartelera (solo 15 clics). La tasa de apertura de Brevo fue del 12.14% y la atención al cliente en Facebook tardaba más de 15 horas.',
    es_publico = 1
WHERE id = 2;

-- 2. Limpiar e insertar Contenidos Populares de Junio 2026 en ugc_posts
DELETE FROM ugc_posts WHERE dashboard_id = 2;

INSERT INTO ugc_posts (dashboard_id, titulo, subtitulo, canal, vistas, compartidos, likes, badge_label, nota_estrategica, orden) VALUES
(2, 'Avance Estreno & Cartelera Fin de Semana', 'Reel de cartelera y estrenos cinematográficos', 'instagram', 17376, 106, 335, 'Top 1 Reels Junio', 'Publicación con mayor alcance orgánico del mes en Instagram (17.3K vistas).', 1),
(2, 'Promo Combos & Experiencia Confitería', 'Video promocional de combos y crispetas', 'instagram', 16560, 88, 280, 'Top 2 Reels Junio', 'Reel de atracción de consumo en confitería.', 2),
(2, 'Humor en Salas & Situacional Cine', 'Clip situacional de clientes en sala', 'instagram', 10280, 64, 195, 'Top 3 Alcance', 'Contenido casual con buena respuesta de engagement.', 3),
(2, '⚡ El hombre más poderoso del universo ya está aquí', 'Clip promocional de estreno', 'tiktok', 3943, 89, 412, 'Top 1 TikTok Junio', 'Video con mayor volumen de reproducciones orgánicas en TikTok en el periodo previo.', 4),
(2, '🐾 ¿Alguna vez te has preguntado qué pasa por la mente...', 'Contenido situacional en salas', 'tiktok', 2276, 54, 265, 'Top 2 TikTok Junio', 'Tráfico impulsado principalmente por búsqueda orgánica (89.2%).', 5),
(2, '👤 Cliente: "¿Qué tiene de dos mil pesos?" 🍿 Confitería', 'Humor en taquilla y combos', 'tiktok', 1112, 38, 184, 'Top 3 TikTok Junio', 'Pieza orgánica con retención en taquilla.', 6);
