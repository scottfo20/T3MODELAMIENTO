document.addEventListener('DOMContentLoaded', function() {
    const asientos = document.querySelectorAll('.asiento.disponible');
    const asientosSeleccionadosContainer = document.getElementById('asientos-seleccionados-count');
    const totalPrecioSpan = document.getElementById('total-precio');
    const asientosIdsInput = document.getElementById('asientos_ids_input');
    const btnComprar = document.getElementById('btn-comprar');

    let asientosSeleccionados = []; // Almacena los IDs de los asientos seleccionados

    asientos.forEach(asiento => {
        asiento.addEventListener('click', function() {
            const asientoId = this.dataset.asientoId;
            const precio = parseFloat(this.dataset.precio);

            if (this.classList.contains('seleccionado')) {
                // Deseleccionar asiento
                this.classList.remove('seleccionado');
                asientosSeleccionados = asientosSeleccionados.filter(id => id !== asientoId);
            } else {
                // Seleccionar asiento
                this.classList.add('seleccionado');
                asientosSeleccionados.push(asientoId);
            }
            actualizarResumenCompra();
        });
    });

    function actualizarResumenCompra() {
        let total = 0;
        asientosSeleccionados.forEach(id => {
            const asientoElement = document.querySelector(`.asiento[data-asiento-id="${id}"]`);
            if (asientoElement) {
                total += parseFloat(asientoElement.dataset.precio);
            }
        });

        asientosSeleccionadosContainer.textContent = asientosSeleccionados.length;
        totalPrecioSpan.textContent = total.toFixed(2);
        asientosIdsInput.value = JSON.stringify(asientosSeleccionados); // Guardar IDs como JSON

        // Habilitar/deshabilitar botón de comprar
        if (asientosSeleccionados.length > 0) {
            btnComprar.removeAttribute('disabled');
        } else {
            btnComprar.setAttribute('disabled', 'disabled');
        }
    }
});