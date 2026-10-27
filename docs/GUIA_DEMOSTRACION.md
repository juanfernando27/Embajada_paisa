# Recorrido de demostración

En una instalación nueva ejecuta `php preparar.php --demo` y `php artisan serve`. En una instalación existente utiliza primero ACTUALIZAR.md. Las cuentas de ejemplo se indican en README.

1. **Vista pública:** abre `/menu`; filtra por categoría, busca un plato y revisa su detalle. Abre `/restaurante`.
2. **Cliente:** entra como `cliente@embajada.test` y agrega dos unidades de un producto. En Carrito cambia la cantidad, elimina otra línea y confirma un pedido con método de pago. El importe proviene del servidor.
3. **Seguimiento:** en el detalle verás número, artículos, progreso y fechas. Refresca periódicamente cada 15 segundos mientras la pestaña está visible. Otro cliente no puede consultar ese pedido.
4. **Reserva:** elige un día futuro, hora, duración y personas. Consulta disponibilidad y confirma una mesa. Edita o cancela antes del inicio. Intenta una segunda reserva superpuesta para comprobar el rechazo.
5. **Personal:** en otra sesión entra como `trabajador@embajada.test`; abre Cocina y avanza el pedido pendiente → preparando → listo → entregado. Consulta Reservas. El ingreso a una reserva solo se permite durante su intervalo.
6. **Administrador:** abre Caja con base de `100000`. En el pedido aplica un descuento de `2000` con motivo. Registra un pago en efectivo suficiente y comprueba el cambio. Imprime el comprobante interno si lo deseas.
7. **Arqueo:** vuelve a Caja. El efectivo esperado es la base más los cobros netos en efectivo. Tarjetas y transferencias se muestran aparte. Si el contado difiere, debes explicar el motivo antes de cerrar.
8. **Gestión:** crea un producto en una categoría activa. Modifica el precio y desactívalo con motivo. El pedido anterior conserva el precio con el que se confirmó.
9. **Administración:** consulta Historial, Reportes, CSV y Auditoría. Revisa actor, hora, antes/después y motivos. Completa los datos reales del restaurante en Configuración.
10. **Móvil:** reduce el navegador o abre desde una pantalla pequeña; usa el botón de menú. Las tablas se desplazan dentro de su contenedor.

Para probar tarjeta/transferencia, verifica el pago fuera del sistema y registra su referencia y el importe exacto. Esta demostración no procesa cargos bancarios.

Para probar la anulación y reposición de existencias, configura stock finito antes de crear un pedido. Un cliente solo anula su propio pedido pendiente sin cobrar; el administrador puede anular otros pedidos sin cobrar. Una segunda anulación o la anulación de un pedido pagado se rechazan.
