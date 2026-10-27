# Checklist de requerimientos

La fuente de esta ampliación son los títulos y subtareas RF-01–22 visibles en las seis capturas aportadas por el usuario. La numeración **RF** corresponde a esas capturas; no reemplaza la numeración **US** del Sprint 1 anterior. No se afirma haber leído descripciones o criterios adicionales ocultos en ClickUp.

**Estado: 22/22 implementados** conforme a ese alcance visible. “Hecho” describe el código y las comprobaciones de esta entrega; la aceptación funcional final corresponde al responsable del proyecto.

| RF | Requerimiento | Permiso | Acceso | Estado y comportamiento |
|---|---|---|---|---|
| RF-01 | Registro de usuario | Público | `/registro` | **Hecho.** Crea cuenta de cliente y ficha, valida duplicados, contraseña y sesión; impide elegir un rol privilegiado. |
| RF-02 | Inicio de sesión | Todos | `/login`, `/logout` | **Hecho.** Sesión regenerada, contraseña con hash, cuenta activa, bloqueo por intentos y cierre de sesión. |
| RF-03 | Consulta del menú | Público / cliente | `/menu` | **Hecho.** Catálogo persistido; solo categorías y productos activos. |
| RF-04 | Detalle de productos | Público / cliente | `/menu/{producto}` | **Hecho.** Nombre, descripción, alérgenos informados, precio y disponibilidad. |
| RF-05 | Filtrado por categorías | Público / cliente | `/menu?categoria=…` | **Hecho.** Filtro de categorías activas combinado con búsqueda. |
| RF-06 | Agregar al carrito | Autenticados | `POST /carrito` | **Hecho.** Carrito por usuario en base de datos; verifica existencia, activación y stock. |
| RF-07 | Modificar cantidades | Propietario | `PUT /carrito/{item}` | **Hecho.** Cantidades entre 1 y 99 y dentro del stock; recálculo en servidor. |
| RF-08 | Eliminar del carrito | Propietario | `DELETE /carrito/{item}` | **Hecho.** Elimina una línea propia; rechaza accesos a líneas ajenas. |
| RF-09 | Realizar pedido | Autenticados | `POST /pedidos` | **Hecho.** Transacción, token idempotente, precios históricos, stock, mesa, notas y limpieza del carrito. |
| RF-10 | Seleccionar método de pago | Cliente / caja | Carrito y detalle de pedido | **Hecho.** Efectivo, tarjeta o transferencia; selección persistida y confirmación posterior en caja. |
| RF-11 | Consultar estado del pedido | Propietario / personal | `/pedidos`, `/pedidos/{pedido}` | **Hecho.** Seguimiento, historial con actor/fecha y actualización periódica; aislamiento entre clientes. |
| RF-12 | Reserva de mesa | Cliente / personal | `/reservas/crear`, `/disponibilidad` | **Hecho.** Fecha, hora, duración, capacidad y horario sin cruces; comprobación final al guardar. |
| RF-13 | Consultar reservas propias | Cliente | `/reservas` | **Hecho.** Reservas de su ficha, incluidas las creadas por personal; edición/cancelación de futuras confirmadas. |
| RF-14 | Registrar productos | Administrador | `/productos/create` | **Hecho.** Categoría, nombre, descripción, alérgenos, precio, stock opcional y activación. |
| RF-15 | Modificar productos | Administrador | `/productos/{producto}/edit` | **Hecho.** Edición validada y auditada; conserva nombres y precios de pedidos históricos. |
| RF-16 | Desactivar productos | Administrador | Activación en `/productos` | **Hecho.** Acción reversible con motivo; bloquea nuevas compras y revisa carritos previos al confirmar. |
| RF-17 | Consultar pedidos | Personal / administrador | `/pedidos`, `/cocina` | **Hecho.** Panel operativo y cocina por estados con actualización periódica. |
| RF-18 | Actualizar estado de pedidos | Personal / administrador | `PATCH /pedidos/{pedido}/estado` | **Hecho.** Pendiente → preparando → listo → entregado; estado esperado, historial y auditoría. |
| RF-19 | Consultar reservas | Personal / administrador | `/reservas` | **Hecho.** Filtro por fecha/estado, ficha, mesa y registro de ingreso/finalización. |
| RF-20 | Gestionar usuarios | Administrador | `/usuarios` | **Hecho.** Altas, edición, roles, activación y restablecimiento; evita autoexclusión y pérdida del último administrador. |
| RF-21 | Historial de pedidos | Administrador | `/historial-pedidos` | **Hecho.** Consulta paginada, filtros de estado y pago, detalle y trazabilidad. |
| RF-22 | Información y ubicación | Público / administrador | `/restaurante`, `/configuracion` | **Hecho.** Datos persistidos editables, coordenadas validadas y enlace de mapa cuando están configuradas. |

## Continuidad y módulos adicionales

| Alcance | Estado | Resultado |
|---|---|---|
| US-01, US-02: fichas de clientes | Hecho | Registro, búsqueda y consulta conservados. |
| US-03: modificar/eliminar cliente | Hecho | Personal edita; solo administrador elimina si no hay cuenta asociada ni historial. |
| US-04: administrar mesas | Hecho | CRUD conservado; protege mesas con historial o compromisos activos. |
| US-11, US-12, US-14: usuarios, roles y autenticación | Hecho | Permisos del servidor y controles existentes conservados. |
| Identidad vinotinto/dorado | Hecho | Tokens centralizados, login y todos los módulos, fuentes locales, revisión sin verde en las vistas comprobadas. |
| Interfaz adaptable | Hecho | Escritorio, tablet y móvil; navegación compacta y tablas con desplazamiento interno. |
| Caja | Hecho | Un turno abierto, cobro único por pedido, cambio, arqueo y motivo de diferencias. |
| Descuentos/anulaciones | Hecho | Administrador descuenta importes fijos; anulación restaura stock una vez. Operaciones con motivo y auditoría. |
| Reportes y CSV | Hecho | Cobros netos por fecha/método, conteos de estados y productos vendidos. |
| Auditoría | Hecho | Altas/cambios de fichas, usuarios, mesas y módulos nuevos; estado anterior/posterior, actor, fecha y motivo. Sin hashes ni contraseñas. |
| Actualización conservando SQLite | Hecho | Una migración aditiva, respaldo previo y prueba sobre el proyecto original. |
| Regresión | Hecho | Suite de 95 pruebas/396 aserciones y recorrido HTTP real. |

## Pendientes de información o aceptación

| Pendiente | Motivo / lugar para completarlo |
|---|---|
| Dirección, teléfono, horario y coordenadas reales | No se entregaron valores verificados. El administrador puede completarlos en Configuración. |
| Carta, precios, stock y alérgenos reales | El catálogo opcional es ficticio. El administrador puede sustituirlo en Productos y Categorías. |
| Criterios detallados no visibles en las capturas | Comparar con ClickUp completo cuando el responsable facilite su contenido. |
| Aceptación en el equipo del usuario | Ejecutar la instalación/actualización documentada con su PHP y su copia SQLite. |

La conexión de una pasarela, facturación electrónica, devoluciones bancarias y despliegue público no se presentan como implementados: no están definidos en RF-01–22. El POS registra pagos recibidos y bloquea la anulación de pedidos ya cobrados.

## Corrección solicitada: fotografías y presentación

Hecho: subir, previsualizar, mantener, reemplazar y quitar fotografía de producto; mostrarla en lista administrativa, carta, detalle y carrito. Hecho: recursos gastronómicos locales en acceso/carta/restaurante, iconos de categorías, accesos visuales y adaptación móvil.
