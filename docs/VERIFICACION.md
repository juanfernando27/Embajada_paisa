# Verificación de la entrega · 2026-10-27

| Comprobación | Resultado |
|---|---|
| ZIP final: instalación limpia y repetida | Correctas; 7 cuentas, 12 fichas, 8 mesas, 12 productos y 4 categorías, sin pedidos ni cobros sembrados. |
| Laravel/PHPUnit | 96 pruebas, 420 aserciones, todas aprobadas. |
| Compatibilidad Sprint 1 | Los 47 casos originales siguen presentes. Un caso se adapta al nuevo soporte de edición/eliminación de fichas; los otros conservan su comportamiento esperado. |
| Nuevo alcance | 48 casos adicionales contando proveedores de datos, sobre catálogo, carrito, pedidos, reservas, caja, roles, auditoría y páginas. |
| Estilo y sintaxis PHP | Laravel Pint y lint de archivos PHP propios. |
| Vistas Blade | Compilación y respuestas HTTP de los módulos. |
| Actualización desde ZIP original | Tablas y filas anteriores conservadas exactamente, hashes de contraseña y `.env` sin cambios; migraciones originales intactas. |
| Respaldo automático | Copia SQLite y `.env` anteriores a la migración verificadas; integridad SQLite correcta. |
| Repetir actualización | Correcto, sin duplicar migraciones ni alterar registros anteriores. |
| Navegador Chromium | Pedido de cliente, modificación de cantidad, reserva/disponibilidad, cocina, descuento, cobro y cierre de caja completados mediante formularios. |
| Protección por HTTP real | Cliente en Productos recibe 403; POST sin CSRF recibe 419. |
| Adaptabilidad | Rutas de 22 pantallas revisadas a 1440, 834 y 390 px; sin desbordamiento horizontal de página. Las tablas desplazan internamente. |
| Paleta | Sin colores verdes detectados en estilos computados de las pantallas verificadas; revisión visual de escritorio y móvil. |
| JavaScript | Sin errores de ejecución en el recorrido probado. |

## Casos normales y de borde

Se verifican registro y duplicados, intento de elevar rol al registrarse, inicio JSON, aislamiento entre clientes, permisos de rutas, producto/categoría inactiva, stock agotado, cantidad negativa/cero/excesiva, eliminación de carrito ajeno, precios manipulados, huella desactualizada, confirmación repetida, competencia secuencial por la última unidad, estados fuera de orden, cancelación repetida, stock devuelto una vez, descuento excesivo, pedido cobrado protegido, reservas cruzadas/contiguas, capacidad, fecha pasada, ficha sin cuenta, reserva creada por personal visible al cliente, registro de ingreso fuera del intervalo, caja ya abierta/cerrada, cobro duplicado/insuficiente, referencia de tarjeta, cierre con diferencia sin motivo, CSV, auditoría sin secretos y protección de mesas/clientes con historial.

La salida de la suite está en RESULTADO_PRUEBAS.txt. El resumen de la prueba de actualización está en RESULTADO_ACTUALIZACION.json. La comprobación CSRF se hizo por HTTP real porque Laravel desactiva ese middleware durante pruebas funcionales convencionales.

## Entorno y límites prácticos

PHP 8.3.6, Laravel 12, SQLite y Chromium. Las pruebas funcionales usan SQLite en memoria; el recorrido HTTP y la actualización usan archivos temporales independientes. No se prueba ni modifica la base que está en el equipo del usuario.

No se ejecutó una auditoría de accesibilidad completa, pruebas de carga/concurrencia entre múltiples procesos, una matriz Safari/Firefox o verificación contra MySQL. El bloqueo de escritura y las restricciones de base protegen operaciones críticas, pero no sustituyen esa evaluación de capacidad.

El alcance RF se verifica contra los títulos/subtareas visibles de las capturas. Los criterios ocultos de ClickUp, el contenido real del restaurante y la aceptación del responsable permanecen por confirmar. El pago es registro operativo; no se enviaron cargos bancarios ni se certifica facturación electrónica.

## Ampliación de imágenes

Carga opcional JPG/PNG/WEBP de hasta 2 MB; guardar, conservar, reemplazar y quitar foto; eliminación del archivo anterior después de confirmar la transacción; rechazo de SVG y acceso sin rol administrador. Se comprueba la entrega de imágenes por HTTP con `nosniff` y 404 para fotos retiradas. La ruta usa nombres acotados, sin enlaces simbólicos en Windows.

Se verificó en navegador el formulario real con selección de archivo, vista previa, guardado y edición posterior, además de carta, productos, formulario, restaurante e inicio a 1440/834/390 px. Las imágenes gastronómicas locales son ilustrativas y fueron generadas para la interfaz. No representan fotografías verificadas del restaurante ni se asignan automáticamente a productos reales.
