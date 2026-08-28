-- Importación histórica del concurso "La Calle Oak" (ya jugado en streetoak) hacia Insights

INSERT INTO concursos (empresa_id, nombre, slug, metodologia, claim_hours, webhook_token, estado, created_at)
VALUES (
  1,
  'Sorteo El Final de la Calle Oak',
  'calle-oak-2026',
  'El sorteo se ejecutó mediante un algoritmo de selección aleatoria criptográficamente seguro (función random_int(), basada en el generador de números aleatorios seguro -CSPRNG- del sistema operativo), ejecutado exclusivamente en el servidor. Ningún participante, operador o dispositivo cliente pudo influir, predecir o manipular el resultado. Antes de cada sorteo se excluyeron automáticamente los ganadores previos del mismo concurso. Cada premio se sorteó una única vez y el resultado quedó bloqueado de forma persistente en el servidor. El proceso se transmitió en vivo a través de una página pública, y cada resultado quedó registrado con fecha, hora y consecutivo para fines de auditoría y transparencia. Concurso realizado originalmente en marketing.cinemultiplex.co e importado a Insights para consolidar el histórico.',
  24,
  SHA2(CONCAT('calle-oak-2026-legacy-', NOW()), 256),
  'completado',
  '2026-08-27 00:00:00'
);

SET @cid = LAST_INSERT_ID();

INSERT INTO concurso_premios (concurso_id, kit, nombre, detalle, orden) VALUES
(@cid, '1', 'Premio 1', 'Maleta, postal y 2 entradas a cine 2D', 1),
(@cid, '2', 'Premio 2', 'Gorra, llavero y 2 entradas a cine 2D', 2),
(@cid, '3', 'Premio 3', 'Cuaderno, linterna y 2 entradas a cine 2D', 3);

INSERT INTO concurso_leads (concurso_id, nombre, apellido, documento, telefono, correo, origen, created_at) VALUES
(@cid, 'Estefania', 'Laguna Daza', '1121882996', '3505045572', 'estefanialaguna11@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Elkin', 'Murillo Mosquera', '1120359042', '3214591475', 'elkinmurillo18@hotmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Robert', 'Bustamante', '86041582', '573202662450', 'robertproducer@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Carlos', 'Peña', '86047749', '3178637903', 'calpeti1980@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Adriana', 'Torres', '40421460', '3145488164', 'adriana.torresr18@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Veruzka Lucia', 'Ruiz Casarrubia', '1192918499', '3212541897', 'verulucia@hotmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Yury Amanda', 'Soler Castro', '1121849603', '573214451803', 'yurysolercastro@hotmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Wilson', 'Romero', '1121821422', '3147041917', 'willyromero24@hotmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Oscar Alexander', 'Gutiérrez Lesmes', '86075388', '3143641892', 'oscaralexandergutierrezlesmes@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Claudia Marcela', 'Martínez Agudelo', '1121828817', '3112451730', 'cayita2306@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Tobias', 'Beltran', '1121861486', '3147606468', 'ing.tobiasbeltran@gmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Natalia', 'Hernández', '40333561', '3013420604', 'nata_hb@hotmail.com', 'form_landing', '2026-08-27 00:00:00'),
(@cid, 'Marisol', 'Serrano Rodriguez', '1121925247', '3155660890', 'solrodriguez9509@gmail.com', 'form_landing', '2026-08-27 00:00:00');

INSERT INTO concurso_sorteos (concurso_id, kit, lead_id, created_at)
SELECT @cid, '1', id, '2026-08-27 19:57:58' FROM concurso_leads WHERE concurso_id = @cid AND documento = '1121925247';

INSERT INTO concurso_sorteos (concurso_id, kit, lead_id, created_at)
SELECT @cid, '2', id, '2026-08-27 19:58:22' FROM concurso_leads WHERE concurso_id = @cid AND documento = '86047749';

INSERT INTO concurso_sorteos (concurso_id, kit, lead_id, created_at)
SELECT @cid, '3', id, '2026-08-27 19:58:46' FROM concurso_leads WHERE concurso_id = @cid AND documento = '86075388';

SELECT @cid AS concurso_id;
