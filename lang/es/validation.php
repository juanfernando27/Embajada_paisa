<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'email' => 'Ingresa un correo electrónico válido.',
    'string' => 'El campo :attribute debe ser texto.', 'integer' => 'El campo :attribute debe ser un número entero.',
    'boolean' => 'Selecciona un valor válido para :attribute.', 'in' => 'La opción seleccionada para :attribute no es válida.',
    'unique' => 'Ya existe un registro con ese :attribute.', 'exists' => 'El registro seleccionado no existe.',
    'regex' => 'El formato de :attribute no es válido.', 'confirmed' => 'La confirmación de :attribute no coincide.',
    'prohibited' => 'El campo :attribute no se permite para este rol.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'max' => ['string' => 'El campo :attribute no puede superar :max caracteres.', 'numeric' => 'El campo :attribute no puede ser mayor que :max.'],
    'password' => ['mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.', 'numbers' => 'La contraseña debe incluir números.', 'letters' => 'La contraseña debe incluir letras.'],
    'attributes' => [],
];
