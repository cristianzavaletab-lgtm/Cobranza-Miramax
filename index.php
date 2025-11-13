<?php include 'includes/header.php'; ?>

<div class="titulo-seccion">
    <h2>Consulta tu Deuda</h2>
    <p>Ingresa tu DNI para consultar tu estado de cuenta</p>
</div>

<form action="consulta.php" method="POST">
    <div class="form-group">
        <input type="text" name="dni" maxlength="8" required 
               class="form-control" placeholder="Ingrese DNI">
    </div>
    
    <button type="submit" class="btn btn-primary btn-pulse">
        🔍 CONSULTAR DEUDA
    </button>
</form>

<!-- CONTACTO WHATSAPP MEJORADO -->
<div class="contacto-whatsapp">
    <p>¿Problemas con tu consulta?</p>
    <strong>Escríbenos al WhatsApp: 918 762 620</strong>
</div>

<!-- CONTADOR MEJORADO -->
<div class="contador">
    <div class="label">PRÓXIMO VENCIMIENTO EN:</div>
    <div class="tiempo">15d : 06h : 45m : 18s</div>
</div>

<?php include 'includes/footer.php'; ?>