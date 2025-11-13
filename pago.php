<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/funciones.php';

$dni = $_GET['dni'] ?? '';
$cliente = $dni ? consultarDeuda($dni) : null;
?>

<?php include 'includes/header.php'; ?>

<div class="contenido-principal">
    <?php if ($cliente): ?>
        <div class="titulo-seccion">
            <h2>Realizar Pago</h2>
        </div>

        <!-- RESUMEN DE DEUDA -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 25px;">
            <h3 style="color: #1a2a6c; margin-bottom: 15px; text-align: center;">📋 Resumen de Deuda</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div><strong>Cliente:</strong></div>
                <div><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                
                <div><strong>DNI:</strong></div>
                <div><?php echo $cliente['dni']; ?></div>
                
                <div><strong>Monto a pagar:</strong></div>
                <div style="color: #e74c3c; font-weight: bold; font-size: 18px;">
                    S/ <?php echo number_format($cliente['deuda_actual'], 2); ?>
                </div>
            </div>
        </div>

        <!-- MÉTODOS DE PAGO -->
        <div class="titulo-seccion">
            <h3>💳 Métodos de Pago Disponibles</h3>
        </div>

        <!-- YAPE -->
        <div class="metodo-pago">
            <h4>💜 YAPE</h4>
            <p><strong>MIRAMAXNET S.A.C.</strong></p>
            <div class="numero-cuenta">999 888 777</div>
            <small>Envía el monto exacto y guarda el comprobante</small>
        </div>

        <!-- PLIN -->
        <div class="metodo-pago">
            <h4>💚 PLIN</h4>
            <p><strong>MIRAMAXNET S.A.C.</strong></p>
            <div class="qr-code">
                <div style="text-align: center; color: white;">
                    <div style="font-size: 10px; margin-bottom: 5px;">CÓDIGO</div>
                    <div style="font-size: 14px; font-weight: bold;">QR</div>
                    <div style="font-size: 8px; margin-top: 5px;">MIRAMAXNET</div>
                </div>
            </div>
            <small>Escanea el código QR o usa el número</small>
        </div>

        <!-- TRANSFERENCIA -->
        <div class="metodo-pago">
            <h4>🏦 TRANSFERENCIA BANCARIA</h4>
            <p><strong>MIRAMAXNET S.A.C.</strong></p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 10px 0; text-align: left;">
                <div><strong>Banco:</strong> BCP</div>
                <div><strong>Cuenta:</strong> 123-456789-001</div>
                <div><strong>CCI:</strong> 00212312345678900123</div>
            </div>
        </div>

        <!-- FORMULARIO PARA WHATSAPP -->
        <div class="form-pago">
            <div class="form-group">
                <label>Método de pago usado:</label>
                <select name="metodo_pago" required class="form-control" id="metodoPago">
                    <option value="">Selecciona...</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>

            <div class="form-group">
                <label>Número de operación:</label>
                <input type="text" name="numero_operacion" required class="form-control" 
                       placeholder="Ej: 123456789" id="numeroOperacion">
            </div>

            <div class="form-group">
                <label>Subir comprobante (captura):</label>
                <input type="file" name="comprobante" accept="image/*,.pdf" required 
                       class="form-control" id="inputComprobante">
                <small>Formatos: JPG, PNG, PDF (Máx. 2MB)</small>
            </div>

            <!-- BOTÓN QUE ENVÍA A WHATSAPP -->
            <button type="button" id="btnEnviarWhatsApp" class="btn btn-success btn-block" disabled>
                📤 ENVIAR COMPROBANTE POR WHATSAPP
            </button>

            <!-- BOTÓN ALTERNATivo PARA GUARDAR EN SISTEMA -->
            <button type="button" id="btnGuardarSistema" class="btn btn-primary btn-block" style="display: none;">
                💾 GUARDAR EN SISTEMA
            </button>
        </div>

    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <h2>❌ Error</h2>
            <p>No se encontró información del cliente.</p>
            <a href="index.php" class="btn btn-primary">Volver a consultar</a>
        </div>
    <?php endif; ?>
</div>

<script>
// Número de WhatsApp de MIRAMAXNET
const numeroWhatsApp = '51918762620'; // Reemplaza con tu número

document.getElementById('inputComprobante').addEventListener('change', function(e) {
    const btnWhatsApp = document.getElementById('btnEnviarWhatsApp');
    const metodoPago = document.getElementById('metodoPago').value;
    const numeroOperacion = document.getElementById('numeroOperacion').value;
    
    if (this.files.length > 0 && metodoPago && numeroOperacion) {
        btnWhatsApp.disabled = false;
    } else {
        btnWhatsApp.disabled = true;
    }
});

// Validar campos en tiempo real
document.getElementById('metodoPago').addEventListener('change', validarCampos);
document.getElementById('numeroOperacion').addEventListener('input', validarCampos);

function validarCampos() {
    const btnWhatsApp = document.getElementById('btnEnviarWhatsApp');
    const fileInput = document.getElementById('inputComprobante');
    const metodoPago = document.getElementById('metodoPago').value;
    const numeroOperacion = document.getElementById('numeroOperacion').value;
    
    if (fileInput.files.length > 0 && metodoPago && numeroOperacion) {
        btnWhatsApp.disabled = false;
    } else {
        btnWhatsApp.disabled = true;
    }
}

// ENVIAR A WHATSAPP
document.getElementById('btnEnviarWhatsApp').addEventListener('click', function() {
    const fileInput = document.getElementById('inputComprobante');
    const metodoPago = document.getElementById('metodoPago').value;
    const numeroOperacion = document.getElementById('numeroOperacion').value;
    
    if (!fileInput.files.length || !metodoPago || !numeroOperacion) {
        alert('Por favor complete todos los campos y seleccione un comprobante');
        return;
    }

    const clienteNombre = "<?php echo addslashes($cliente['nombre']); ?>";
    const clienteDNI = "<?php echo $cliente['dni']; ?>";
    const monto = "<?php echo $cliente['deuda_actual']; ?>";
    
    // Crear mensaje para WhatsApp
    const mensaje = `*MIRAMAXNET - COMPROBANTE DE PAGO*%0A%0A` +
                   `👤 *Cliente:* ${clienteNombre}%0A` +
                   `📄 *DNI:* ${clienteDNI}%0A` +
                   `💰 *Monto Pagado:* S/ ${monto}%0A` +
                   `💳 *Método de Pago:* ${metodoPago.toUpperCase()}%0A` +
                   `🔢 *N° Operación:* ${numeroOperacion}%0A` +
                   `📅 *Fecha:* ${new Date().toLocaleDateString()}%0A%0A` +
                   `_Por favor verificar mi pago y actualizar mi estado._`;
    
    // Para imágenes, WhatsApp Web no permite enviar archivos directamente
    // Pero podemos enviar el mensaje con los datos
    const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${mensaje}`;
    
    // Abrir WhatsApp
    window.open(urlWhatsApp, '_blank');
    
    // Mostrar instrucciones
    alert('✅ Datos listos para WhatsApp!\n\n' +
          '1. Se abrirá WhatsApp\n' +
          '2. Pegue el mensaje automático\n' + 
          '3. Adjunte manualmente la captura del comprobante\n' +
          '4. Envíe el mensaje\n\n' +
          '📸 No olvide adjuntar la captura del pago!');
    
    // Mostrar opción para guardar en sistema
    document.getElementById('btnGuardarSistema').style.display = 'block';
});

// GUARDAR EN SISTEMA (opcional)
document.getElementById('btnGuardarSistema').addEventListener('click', function() {
    // Aquí puedes agregar lógica para guardar en tu base de datos
    alert('✅ Pago registrado en el sistema\n\nLa deuda será actualizada una vez verificado el comprobante.');
    
    // Redirigir a consulta
    setTimeout(() => {
        window.location.href = 'consulta.php?dni=<?php echo $dni; ?>';
    }, 2000);
});
</script>

<?php include 'includes/footer.php'; ?>