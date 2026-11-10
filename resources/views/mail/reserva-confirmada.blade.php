<!doctype html>
<html lang="es">
<body>
    <h1>Reserva confirmada en La Embajada Paisa</h1>
    <p>Hola, {{ $cliente }}. Te esperamos en el restaurante.</p>
    <ul>
        <li>Mesa {{ $mesa }}</li>
        <li>Inicio: {{ $inicio }}</li>
        <li>Fin: {{ $fin }}</li>
        <li>Personas: {{ $comensales }}</li>
    </ul>
    <p>Horario local de Pasto, Colombia.</p>
</body>
</html>
