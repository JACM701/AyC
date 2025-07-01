document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', e => {
            const inputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault(); // Stop form submission
                // Mostrar alerta Bootstrap en lugar de alert nativo
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle"></i>
                    Por favor, completa todos los campos requeridos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.insertBefore(alertDiv, form.firstChild);
            }
        });
    });

    // Confirmation before deleting items
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                e.preventDefault();
            }
        });
    });

    // Sidebar dinámico
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleSidebar');
    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            // Ajustar main-content
            const main = document.querySelector('.main-content');
            if (sidebar.classList.contains('collapsed')) {
                main.style.marginLeft = '70px';
            } else {
                main.style.marginLeft = '250px';
            }
        });
    }
});