# Arquitectura y contratos

## Continuidad

Se mantienen PHP 8.2+, Laravel 12, Blade/Eloquent, el driver SQLite y los roles `administrador`, `trabajador`, `cliente`. “Personal” en las capturas corresponde al rol existente `trabajador`; no se cambia el enum. No hay dependencia nueva de npm ni un backend paralelo.

Las migraciones originales permanecen intactas. `2026_10_27_000001_add_pos_modules.php` añade solo tablas necesarias para catálogo, compra, reservas, caja y trazabilidad. No altera las columnas de usuarios/clientes/mesas existentes.

| Tabla nueva | Responsabilidad / relación |
|---|---|
| categorias | Agrupación y activación del catálogo. |
| productos | Categoría, descripción, precio entero en centavos COP, alérgenos, stock opcional. |
| carrito_items | Usuario + producto únicos; cantidad persistida. |
| metodos_pago | Efectivo, tarjeta, transferencia. |
| pedidos | Propietario, ficha opcional, mesa opcional, totales, método y estados. |
| pedido_items | Instantánea del nombre y precio de cada artículo. |
| pedido_estados | Actor, fecha, estado anterior/nuevo y motivo. |
| reservas | Ficha del cliente, creador, mesa, intervalo, comensales y estado. |
| caja_turnos | Apertura/cierre, base, efectivo esperado/contado y diferencia. |
| pagos | Un cobro por pedido, turno, operador, método, cambio y referencia. |
| auditorias | Actor, entidad, acción, antes/después y motivo. |
| restaurante_config | Información pública y coordenadas del restaurante. |
| pos_locks | Fila de exclusión de escrituras para operaciones POS en SQLite. |

## Reglas y consistencia

`CartService`, `OrderService`, `ReservationService` y `CashService` concentran las reglas. `PosTransaction` inicia la transacción tomando primero un bloqueo de escritura; evita que dos operaciones lean el mismo stock, horario o caja y lo confirmen simultáneamente. Se configura espera SQLite de 5 segundos y se usan reintentos de transacción del framework. Es una política adecuada para el tamaño local del proyecto; no se presenta como una prueba de carga.

Los importes no usan punto flotante: `Money`/`MoneyValue` convierten entradas con hasta dos decimales a enteros de centavos. Sin separadores de miles; punto o coma decimal permitidos. Precios y totales siempre se calculan en el servidor. Stock `null` significa sin límite controlado; cero impide comprar.

El carrito entrega una `huella` SHA-256 de líneas, cantidades, precios, stock y activación. Confirmar con una huella desactualizada se rechaza para que el usuario revise el importe. El UUID `token` por usuario hace idempotente el reenvío de confirmación. Un pedido guarda una instantánea, de modo que editar un producto no altera ventas anteriores.

Reservas: hora local America/Bogota, fecha futura dentro de un año, duración de 30–240 minutos, 1–30 personas y capacidad real de la mesa. El intervalo es `[inicio, fin)`, permitiendo reservas contiguas. Solo reservas futuras confirmadas son editables por el cliente; se verifica propiedad mediante la ficha asociada incluso cuando reservó el personal. Los horarios publicados son informativos; no se inventó una regla de horarios de atención sin datos reales.

Caja: un turno abierto; cada pedido se cobra una sola vez. Para efectivo se valida suficiencia y se calcula cambio. Para tarjeta/transferencia se exige referencia e importe exacto. No se guardan datos de tarjetas. El arqueo incluye base + cobros netos en efectivo. La diferencia exige motivo. No se permiten descuentos ni anular pedidos ya cobrados; una anulación devuelve stock una sola vez.

La auditoría se registra desde los servicios y desde `LegacyAuditObserver` para las entidades anteriores. No se exponen contraseñas, hashes ni tokens. Se protegen mesas/clientes con historial frente a eliminación. Las cuentas desactivadas pierden acceso y los formularios conservan CSRF y validación Laravel centralizada. Los errores de validación aparecen en el componente común de alertas; 403/404/419/500 tienen pantallas propias.

## Roles

| Acción | Cliente | Personal | Administrador |
|---|---|---|---|
| Menú, detalle e información pública | Sí | Sí | Sí |
| Carrito y creación de pedido | Propio | Propio / ficha elegida | Propio / ficha elegida |
| Consultar pedido | Propio | Operativo | Todos |
| Avanzar preparación | No | Sí | Sí |
| Anular pedido sin cobrar | Propio y pendiente | No | Sí |
| Reservas | Ficha propia | Todas, crear/editar/estados | Todas |
| Clientes | Perfil propio | Alta/consulta/edición | Además eliminar sin vínculos |
| Mesas | Solo selección habilitada | Consultar | CRUD protegido |
| Productos/categorías, usuarios/roles | No | No | Sí |
| Cobrar | No | Sí | Sí |
| Abrir/cerrar caja, descuentos | No | No | Sí |
| Historial administrativo, reportes, auditoría, configuración | No | No | Sí |

## Interfaz HTTP / JSON

Las operaciones usan las mismas rutas web para Blade y JSON; envía `Accept: application/json` para obtener datos. La autenticación es por **cookie de sesión y CSRF**, conservando el contrato del backend existente. No es una API de tokens Bearer. Para mutar desde un cliente HTTP propio, primero inicia una sesión, conserva la cookie y envía el token CSRF vigente (`X-CSRF-TOKEN` o `_token`).

| Método / ruta | Campos o respuesta principal |
|---|---|
| POST `/registro` | nombre, apellido, email, tipo_documento, numero_documento, telefono, password, password_confirmation. Siempre crea rol cliente. |
| POST `/login`, POST `/logout` | Credenciales de sesión / cierre. |
| GET `/menu` | `buscar`, `categoria` opcionales. Catálogo paginado y categorías activas. |
| GET `/menu/{producto}` | Producto visible y categoría. |
| GET `/carrito` | items, total (centavos), huella, token UUID, metodos. |
| POST `/carrito` | producto_id, cantidad. |
| PUT / DELETE `/carrito/{item}` | cantidad / sin cuerpo adicional. |
| POST `/pedidos` | token, huella, metodo_pago_id; mesa_id, cliente_id y notas opcionales. Para cliente se ignora la ficha ajena y se usa la propia. |
| GET `/pedidos`, GET `/pedidos/{pedido}` | Filtros `estado`, `pago`; lista propia para cliente, detalle con historial. |
| PATCH `/pedidos/{pedido}/estado` | estado, estado_esperado, motivo cuando corresponde. |
| GET `/cocina` | Pedidos pendientes, preparando y listos. |
| GET `/disponibilidad` | fecha YYYY-MM-DD, hora HH:mm, duracion, comensales; reserva_id opcional para excluir edición propia. |
| GET / POST `/reservas` | Listado (`fecha`, `estado`) / fecha, hora, duracion, comensales, mesa_id, notas, cliente_id si personal. |
| PUT `/reservas/{reserva}` | Mismos campos de creación, respetando propiedad y reglas. |
| PATCH `/reservas/{reserva}/estado` | estado (en_curso/finalizada/cancelada), motivo. |
| GET / POST `/productos`, PUT `/productos/{producto}` | Listado / nombre, descripcion, alergenos, categoria_id, precio en COP, stock opcional, activo booleano. |
| PATCH `/productos/{producto}/activo` | activo, motivo. |
| GET / POST `/categorias`, PUT `/categorias/{categoria}` | Listado / nombre; al editar, activa booleano. |
| GET / POST `/usuarios`, PUT `/usuarios/{usuario}` | Gestión existente, también admite JSON. Campos detallados en Store/UpdateUsuarioRequest. |
| GET `/caja`, POST `/caja/abrir` | Estado de caja / inicial en COP. |
| POST `/pedidos/{pedido}/pago` | metodo_pago_id, total_esperado en centavos, recibido en COP, referencia para tarjeta/transferencia. |
| POST `/caja/{turno}/cerrar` | contado en COP, notas si hay diferencia. |
| PATCH `/pedidos/{pedido}/descuento` | monto fijo en COP, motivo. |
| GET `/historial-pedidos` | Vista/JSON del historial administrativo. |
| GET `/reportes`, GET `/reportes/ventas.csv` | desde, hasta YYYY-MM-DD, máximo 366 días. CSV usa separador `;` y BOM UTF-8. |
| GET `/auditoria` | Filtro opcional accion; paginado, solo administrador. |
| GET `/restaurante`, PUT `/configuracion` | Información pública / configuración validada de administrador. |

Respuestas de escritura: `{message, data}` con 200/201. Lecturas: objeto/lista o paginación Laravel según el controlador. Validación: 422 con `{message, errors}`; acceso ajeno/sin rol: 403; sin sesión: 401 en JSON; recurso inexistente: 404; CSRF inválido por HTTP: 419. Los campos monetarios `*_centavos` siempre son enteros, mientras que los campos de entrada `precio`, `monto`, `recibido`, `inicial`, `contado` se expresan en COP.

Reportes: el total cobrado usa importes netos de `pagos` según su fecha. Productos vendidos muestra importe bruto antes del descuento global, identificado así en la interfaz. Los conteos de estados usan fecha de creación de pedido; no deben interpretarse como cobros.

## Fotografía de producto

`productos.imagen` almacena la ruta en el disco público. POST/PUT de productos acepta `imagen` como archivo multipart JPG/PNG/WEBP de hasta 2 MB y `quitar_imagen=1` para retirarla. Un archivo nuevo tiene prioridad sobre quitar; sin ninguno se conserva la foto. Los archivos se guardan con nombres aleatorios y la auditoría registra la ruta. La imagen anterior se elimina después de confirmar los cambios de base de datos; se limpia la nueva si la transacción falla.

`GET /imagenes-productos/{archivo}` sirve únicamente archivos de `productos/` con nombres y extensiones acotados. No requiere symlink ni cambios en la configuración de Windows. Incluye `nosniff` y caché de un día; cada sustitución genera otra URL.
