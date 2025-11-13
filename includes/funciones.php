<?php
function consultarDeuda($dni) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM clientes WHERE dni = :dni";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dni', $dni);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registrarPago($cliente_id, $monto, $metodo, $comprobante, $numero_operacion) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO pagos SET cliente_id=:cliente_id, monto=:monto, metodo_pago=:metodo, comprobante=:comprobante, numero_operacion=:numero_operacion";
    $stmt = $db->prepare($query);
    
    return $stmt->execute([
        'cliente_id' => $cliente_id,
        'monto' => $monto,
        'metodo' => $metodo,
        'comprobante' => $comprobante,
        'numero_operacion' => $numero_operacion
    ]);
function getEstadisticasGenerales() {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Total clientes
    $query = "SELECT COUNT(*) as total FROM clientes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_clientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Clientes con deuda
    $query = "SELECT COUNT(*) as total FROM clientes WHERE deuda_actual > 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['clientes_deuda'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pagos pendientes
    $query = "SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pagos_pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

function getClientesConDeuda() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM clientes WHERE deuda_actual > 0 ORDER BY fecha_vencimiento ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);}
}
?>