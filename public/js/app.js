document.querySelectorAll('[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});
document.querySelectorAll('[data-password]').forEach(button => {
    button.addEventListener('click', () => {
        const field = document.getElementById(button.dataset.password);
        const visible = field.type === 'password';
        field.type = visible ? 'text' : 'password';
        button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.setAttribute('aria-pressed', String(visible));
    });
});
const roleSelect = document.querySelector('[data-role-select]');
if (roleSelect) {
    const update = () => {
        const container = document.querySelector('[data-client-association]');
        const isClient = roleSelect.value === 'cliente';
        container.hidden = !isClient;
        container.querySelector('select').disabled = !isClient;
    };
    roleSelect.addEventListener('change', update);
    update();
}

// Navegación compacta y accesible en móvil.
const navToggle=document.querySelector('[data-nav-toggle]');
navToggle?.addEventListener('click',()=>{const open=document.body.classList.toggle('nav-open');navToggle.setAttribute('aria-expanded',String(open));navToggle.setAttribute('aria-label',open?'Cerrar menú':'Abrir menú');});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&document.body.classList.contains('nav-open')){document.body.classList.remove('nav-open');navToggle?.setAttribute('aria-expanded','false');navToggle?.setAttribute('aria-label','Abrir menú');navToggle?.focus();}});
document.querySelector('[data-print]')?.addEventListener('click',()=>window.print());
const method=document.querySelector('[data-payment-method]');
if(method){const update=()=>{document.querySelector('#referencia').required=method.selectedOptions[0]?.dataset.codigo!=='efectivo';};method.addEventListener('change',update);update();}
const reservation=document.querySelector('[data-reservation-form]');
if(reservation){let pending;const button=reservation.querySelector('[data-check-availability]'),message=reservation.querySelector('[data-availability-message]');button.addEventListener('click',async()=>{const params=new URLSearchParams();for(const n of ['fecha','hora','duracion','comensales']){const field=reservation.elements[n];if(!field.reportValidity())return;params.set(n,field.value);}if(reservation.dataset.reservaId)params.set('reserva_id',reservation.dataset.reservaId);pending?.abort();pending=new AbortController();button.disabled=true;message.textContent='Consultando disponibilidad…';try{const response=await fetch(reservation.dataset.availability+'?'+params,{headers:{Accept:'application/json'},credentials:'same-origin',signal:pending.signal});const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors??{}).flat()[0]??data.message??'No fue posible consultar.');const select=reservation.elements.mesa_id,previous=select.value;select.replaceChildren(new Option('Selecciona una mesa',''));for(const m of data)select.add(new Option(`Mesa ${m.numero} · ${m.ubicacion} · ${m.capacidad} puestos`,m.id));if([...select.options].some(o=>o.value===previous))select.value=previous;message.textContent=data.length?`${data.length} mesas disponibles. Selecciona una y confirma.`:'No hay mesas para ese horario. Prueba otra hora.';}catch(error){if(error.name!=='AbortError')message.textContent=error.message;}finally{button.disabled=false;}});}
const board=document.querySelector('[data-live-board]');
if(board){setInterval(async()=>{if(document.hidden)return;try{const r=await fetch(board.dataset.liveBoard,{headers:{Accept:'application/json'},credentials:'same-origin'});if(!r.ok)return;const rows=await r.json();if(JSON.stringify(rows.map(p=>[p.id,p.estado]))!==board.dataset.signature&&!['INPUT','SELECT','TEXTAREA'].includes(document.activeElement?.tagName))location.reload();}catch{const m=document.querySelector('[data-live-message]');if(m)m.textContent='Sin conexión. Intentaremos actualizar nuevamente.';}},15000);}
const liveOrder=document.querySelector('[data-live-order]');
if(liveOrder){setInterval(async()=>{if(document.hidden||['INPUT','SELECT','TEXTAREA'].includes(document.activeElement?.tagName))return;try{const r=await fetch(liveOrder.dataset.liveOrder,{headers:{Accept:'application/json'},credentials:'same-origin'});if(!r.ok)return;const p=await r.json();if(p.estado+'|'+p.estado_pago+'|'+p.total_centavos!==liveOrder.dataset.signature)location.reload();}catch{}},15000);}

// La confirmación se comprueba también en el navegador; el servidor es la autoridad final.
document.querySelectorAll('form').forEach(form => {
    const password = form.querySelector('[name="password"]');
    const confirmation = form.querySelector('[name="password_confirmation"]');
    if (!password || !confirmation) return;
    const validate = () => confirmation.setCustomValidity(
        confirmation.value === password.value ? '' : 'Las contraseñas deben coincidir.'
    );
    password.addEventListener('input', validate);
    confirmation.addEventListener('input', validate);
});

// Vista previa local; la validación del servidor comprueba el contenido del archivo.
const photoInput = document.querySelector('[data-image-upload]');
if (photoInput) {
    const preview = document.querySelector('[data-upload-preview]');
    const message = document.querySelector('[data-upload-message]');
    const initial = preview.innerHTML;
    let previewURL;
    photoInput.addEventListener('change', () => {
        if (previewURL) URL.revokeObjectURL(previewURL);
        photoInput.setCustomValidity('');
        const file = photoInput.files[0];
        if (!file) { preview.innerHTML = initial; message.textContent = ''; return; }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            const error = 'Elige una imagen JPG, PNG o WEBP de máximo 2 MB.';
            photoInput.setCustomValidity(error);
            message.textContent = error;
            preview.innerHTML = initial;
            photoInput.reportValidity();
            return;
        }
        previewURL = URL.createObjectURL(file);
        const image = document.createElement('img');
        image.src = previewURL;
        image.alt = 'Vista previa de la imagen seleccionada';
        preview.replaceChildren(image);
        message.textContent = file.name;
        const remove = document.querySelector('[name="quitar_imagen"]');
        if (remove) remove.checked = false;
    });
}
