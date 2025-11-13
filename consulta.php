<?php
include 'includes/database.php';
include 'includes/funciones.php';

if ($_POST['dni']) {
    $dni = $_POST['dni'];
    $cliente = consultarDeuda($dni);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <?php if (isset($cliente) && $cliente): ?>
        <!-- Si encontró al cliente -->
        <div class="resultado-deuda">
            <h2>✅ Hola, <?php echo $cliente['nombre']; ?></h2>
            <div class="info-deuda">
                <p><strong>Servicio:</strong> <?php echo $cliente['servicio']; ?></p>
                <p><strong>Deuda Actual:</strong> S/ <?php echo $cliente['deuda_actual']; ?></p>
                <p><strong>Vencimiento:</strong> <?php echo $cliente['fecha_vencimiento']; ?></p>
                <p><strong>Estado:</strong> 
                    <span class="estado <?php echo $cliente['estado']; ?>">
                        <?php echo strtoupper($cliente['estado']); ?>
                    </span>
                </p>

                <?php if ($cliente['deuda_actual'] == 0): ?>
                    <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin: 15px 0; text-align: center;">
                        <h3>🎉 ¡NO TIENES DEUDAS PENDIENTES!</h3>
                        <p>Tu cuenta está al día. ¡Gracias por tu pago!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($cliente['deuda_actual'] > 0): ?>
            <div class="acciones">
                <a href="pago.php?dni=<?php echo $dni; ?>" class="btn btn-pagar">💳 PAGAR AHORA</a>
                <a href="https://wa.me/51<?php echo $cliente['telefono']; ?>?text=Hola, tengo una consulta sobre mi deuda" 
                   class="btn btn-whatsapp" target="_blank">
                   📞 Contactar por WhatsApp
                </a>
            </div>
            <?php endif; ?>
        </div>
    <?php elseif (isset($cliente)): ?>
        <!-- Si NO encontró al cliente -->
        <div class="error">
            <h2>❌ Cliente no encontrado</h2>
            <p>No encontramos deudas asociadas al DNI: <?php echo htmlspecialchars($dni); ?></p>
            <a href="index.php" class="btn btn-secondary">Volver a intentar</a>
        </div>
    <?php else: ?>
        <!-- Si acceden directamente sin consultar -->
        <div class="advertencia">
            <p>Debes realizar una consulta primero</p>
            <a href="index.php" class="btn btn-primary">Consultar Deuda</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
