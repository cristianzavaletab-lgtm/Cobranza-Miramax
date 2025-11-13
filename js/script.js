// Contador de tiempo para vencimientos
function actualizarContador() {
    const contadores = document.querySelectorAll('.contador .tiempo');
    
    contadores.forEach(contador => {
        // Ejemplo de contador - en producción calcularías con fecha real
        let texto = contador.textContent;
        let partes = texto.split(' : ');
        
        if (partes.length === 4) {
            let dias = parseInt(partes[0]);
            let horas = parseInt(partes[1]);
            let minutos = parseInt(partes[2]);
            let segundos = parseInt(partes[3]);
            
            // Decrementar segundos
            segundos--;
            
            if (segundos < 0) {
                segundos = 59;
                minutos--;
                
                if (minutos < 0) {
                    minutos = 59;
                    horas--;
                    
                    if (horas < 0) {
                        horas = 23;
                        dias--;
                        
                        if (dias < 0) {
                            // El tiempo se acabó
                            contador.textContent = "00d : 00h : 00m : 00s";
                            contador.style.color = "#dc3545";
                            return;
                        }
                    }
                }
            }
            
            // Formatear con ceros a la izquierda
            const formato = (num) => num.toString().padStart(2, '0');
            
            contador.textContent = `${formato(dias)}d : ${formato(horas)}h : ${formato(minutos)}m : ${formato(segundos)}s`;
        }
    });
}

// Iniciar contador
setInterval(actualizarContador, 1000);

// Validación de DNI
function validarDNI(dni) {
    if (!dni) return false;
    if (dni.length !== 8) return false;
    if (!/^\d+$/.test(dni)) return false;
    return true;
}

// Validación de formularios
document.addEventListener('DOMContentLoaded', function() {
    // Validar formulario de consulta
    const formConsulta = document.querySelector('form[action="consulta.php"]');
    if (formConsulta) {
        formConsulta.addEventListener('submit', function(e) {
            const dniInput = this.querySelector('input[name="dni"]');
            if (dniInput && !validarDNI(dniInput.value)) {
                e.preventDefault();
                alert('Por favor ingrese un DNI válido (8 dígitos)');
                dniInput.focus();
            }
        });
    }
    
    // Validar formulario de pago
    const formPago = document.querySelector('.form-pago');
    if (formPago) {
        formPago.addEventListener('submit', function(e) {
            const fileInput = this.querySelector('input[type="file"]');
            const metodoSelect = this.querySelector('select[name="metodo_pago"]');
            const operacionInput = this.querySelector('input[name="numero_operacion"]');
            
            let errores = [];
            
            if (!metodoSelect.value) {
                errores.push('Seleccione un método de pago');
            }
            
            if (!operacionInput.value.trim()) {
                errores.push('Ingrese el número de operación');
            }
            
            if (!fileInput.files.length) {
                errores.push('Debe subir un comprobante de pago');
            } else {
                const archivo = fileInput.files[0];
                const extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
                const extension = archivo.name.split('.').pop().toLowerCase();
                
                if (!extensionesPermitidas.includes(extension)) {
                    errores.push('Solo se permiten archivos JPG, PNG, GIF o PDF');
                }
                
                if (archivo.size > 2 * 1024 * 1024) {
                    errores.push('El archivo no puede ser mayor a 2MB');
                }
            }
            
            if (errores.length > 0) {
                e.preventDefault();
                alert('Errores:\n\n• ' + errores.join('\n• '));
            }
        });
    }
    
    // Autoformato para números de teléfono
    const telInputs = document.querySelectorAll('input[type="tel"]');
    telInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let valor = this.value.replace(/\D/g, '');
            if (valor.length > 9) valor = valor.substring(0, 9);
            
            if (valor.length >= 3) {
                valor = valor.substring(0, 3) + ' ' + valor.substring(3);
            }
            if (valor.length >= 7) {
                valor = valor.substring(0, 7) + ' ' + valor.substring(7);
            }
            
            this.value = valor;
        });
    });
});

// Efectos visuales
document.addEventListener('DOMContentLoaded', function() {
    // Efecto de carga en botones
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.type === 'submit' || this.href) {
                this.style.opacity = '0.7';
                this.style.cursor = 'wait';
                
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.cursor = 'pointer';
                }, 2000);
            }
        });
    });
});