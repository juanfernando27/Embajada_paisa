# Modelo de datos vigente

El esquema se obtiene de `database/migrations/`. Se conservan las seis migraciones originales y se añade `2026_10_27_000001_add_pos_modules.php`.

Consulta la tabla de entidades y las reglas de transacción en ARQUITECTURA_Y_API.md. Las claves foráneas protegen el historial, la combinación usuario/producto hace único cada renglón de carrito, usuario/token evita pedidos duplicados y el pago tiene un único pedido asociado.
