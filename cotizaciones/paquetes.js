// Paquetes inteligentes para cotizaciones
// Guardado en localStorage

const PAQUETES_KEY = 'cotiz_paquetes';

function getPaquetes() {
    return JSON.parse(localStorage.getItem(PAQUETES_KEY) || '[]');
}

function savePaquetes(paquetes) {
    localStorage.setItem(PAQUETES_KEY, JSON.stringify(paquetes));
}

function addPaquete(paquete) {
    const paquetes = getPaquetes();
    paquetes.push(paquete);
    savePaquetes(paquetes);
}

function updatePaquete(index, paquete) {
    const paquetes = getPaquetes();
    paquetes[index] = paquete;
    savePaquetes(paquetes);
}

function deletePaquete(index) {
    const paquetes = getPaquetes();
    paquetes.splice(index, 1);
    savePaquetes(paquetes);
}

// Estructura de un paquete:
// {
//   nombre: 'Kit Cámaras',
//   items: [
//     { product_id: 1, nombre: 'Cámara', tipo: 'principal', factor: 1 },
//     { product_id: 2, nombre: 'Conector', tipo: 'relacionado', factor: 2 },
//     { product_id: 3, nombre: 'Fuente', tipo: 'relacionado', factor: 1 },
//     ...
//   ]
// }

// Aplica la lógica de sincronización de cantidades
function sincronizarPaquete(paquete, cantidades) {
    // cantidades: { product_id: cantidad }
    const principal = paquete.items.find(i => i.tipo === 'principal');
    if (!principal) return cantidades;
    const cantidadPrincipal = cantidades[principal.product_id] || 1;
    const nuevasCantidades = { ...cantidades };
    paquete.items.forEach(item => {
        if (item.tipo === 'relacionado') {
            nuevasCantidades[item.product_id] = cantidadPrincipal * item.factor;
        }
    });
    return nuevasCantidades;
}

// Exporta funciones para usar en el frontend
window.PaquetesCotizacion = {
    getPaquetes,
    savePaquetes,
    addPaquete,
    updatePaquete,
    deletePaquete,
    sincronizarPaquete
};

// Renderiza el formulario de creación/edición de paquete
window.renderPaqueteForm = function({productos, paquete, onSave, onCancel}) {
    // productos: [{product_id, product_name}]
    // paquete: {nombre, items: [{product_id, nombre, tipo, factor}]}
    // onSave(paquete), onCancel()
    let html = '';
    html += `<div class='mb-3'>
        <label class='form-label'>Nombre del paquete</label>
        <input type='text' class='form-control' id='paqNombre' value='${paquete?.nombre ? paquete.nombre.replace(/'/g, "&#39;") : ''}' placeholder='Ej: Kit cámaras 8ch'>
    </div>`;
    html += `<div class='mb-2'><b>Productos del paquete</b></div>`;
    html += `<table class='table table-bordered'><thead><tr><th>Producto</th><th>Tipo</th><th>Factor</th><th></th></tr></thead><tbody id='paqItemsTbody'>`;
    (paquete?.items || []).forEach((item, idx) => {
        html += `<tr>
            <td>${item.nombre}</td>
            <td>
                <select class='form-select paq-tipo' data-idx='${idx}'>
                    <option value='principal' ${item.tipo==='principal'?'selected':''}>Principal</option>
                    <option value='relacionado' ${item.tipo==='relacionado'?'selected':''}>Relacionado</option>
                </select>
            </td>
            <td><input type='number' class='form-control paq-factor' data-idx='${idx}' value='${item.factor}' min='0.01' step='0.01' style='width:80px;'></td>
            <td><button class='btn btn-sm btn-danger' onclick='window.removePaqueteItem(${idx})'><i class='bi bi-trash'></i></button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    html += `<div class='mb-2'>
        <select class='form-select' id='paqProductoSelect'>
            <option value=''>Agregar producto...</option>`;
    productos.forEach(p => {
        // Prevenir duplicados
        if (!(paquete.items || []).some(i => i.product_id == p.product_id)) {
            html += `<option value='${p.product_id}'>${p.product_name}</option>`;
        }
    });
    html += `</select>
    </div>`;
    html += `<div class='d-flex justify-content-end gap-2'>
        <button class='btn btn-success' id='btnGuardarPaquete'><i class='bi bi-check-circle'></i> Guardar</button>
        <button class='btn btn-secondary' id='btnCancelarPaquete'><i class='bi bi-x-circle'></i> Cancelar</button>
    </div>`;
    return html;
};

window.removePaqueteItem = function(idx) {
    if (window._paqEdit && window._paqEdit.items) {
        window._paqEdit.items.splice(idx, 1);
        window._paqRender();
    }
};
// --- Mantener el nombre al agregar producto ---
// Sobrescribe la función de nuevoPaquete en crear.php para guardar el nombre antes de renderizar
if (window._paqRender) {
    const oldRender = window._paqRender;
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        oldRender();
    };
} 

// Hacer funciones disponibles globalmente
window.editarPaquete = function(idx) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    window._paqEdit = { 
        nombre: paquete.nombre, 
        items: [...paquete.items] 
    };
    window._paqEditIndex = idx;
    
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        const panel = document.getElementById('paquetesPanel');
        panel.innerHTML = window.renderPaqueteForm({
            productos: productosArray.map(p => ({ product_id: p.product_id, product_name: p.product_name })),
            paquete: window._paqEdit,
            onSave: window.guardarPaqueteEditado,
            onCancel: renderPaquetesPanel
        });
        document.getElementById('paqProductoSelect').onchange = function() {
            const pid = this.value;
            if (!pid) return;
            const prod = productosArray.find(p => p.product_id == pid);
            if (!prod) return;
            // Prevenir duplicados
            if (window._paqEdit.items.some(i => i.product_id == prod.product_id)) return;
            window._paqEdit.items.push({ product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
            window._paqRender();
        };
        document.getElementById('btnGuardarPaquete').onclick = window.guardarPaqueteEditado;
        document.getElementById('btnCancelarPaquete').onclick = renderPaquetesPanel;
        document.querySelectorAll('.paq-tipo').forEach(sel => {
            sel.onchange = function() {
                window._paqEdit.items[this.dataset.idx].tipo = this.value;
            };
        });
        document.querySelectorAll('.paq-factor').forEach(inp => {
            inp.oninput = function() {
                window._paqEdit.items[this.dataset.idx].factor = parseFloat(this.value) || 1;
            };
        });
    };
    window._paqRender();
};

window.guardarPaqueteEditado = function() {
    window._paqEdit.nombre = document.getElementById('paqNombre').value.trim();
    if (!window._paqEdit.nombre || window._paqEdit.items.length === 0) {
        mostrarNotificacion('Ponle nombre y al menos un producto al paquete.', 'warning');
        return;
    }
    window.PaquetesCotizacion.updatePaquete(window._paqEditIndex, window._paqEdit);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete actualizado correctamente.', 'success');
};

window.eliminarPaquete = function(idx) {
    if (confirm('¿Eliminar este paquete?')) {
        window.PaquetesCotizacion.deletePaquete(idx);
        renderPaquetesPanel();
        mostrarNotificacion('Paquete eliminado correctamente.', 'info');
    }
}; 