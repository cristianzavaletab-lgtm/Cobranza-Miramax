<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/funciones.php';

// Verificar login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Variables para filtros
$estado = $_GET['estado'] ?? 'pendiente';
$mensaje = '';

// Procesar cambio de estado de pago
if (isset($_POST['cambiar_estado'])) {
    $pago_id = $_POST['pago_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // VERIFICAR QUE LA COLUMNA EXISTA ANTES DE ACTUALIZAR
    $query = "SHOW COLUMNS FROM pagos LIKE 'observaciones'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columna_existe = $stmt->fetch();
    
    if ($columna_existe) {
        // Si la columna existe, actualizar con observaciones
        $query = "UPDATE pagos SET estado = ?, observaciones = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $resultado = $stmt->execute([$nuevo_estado, $observaciones, $pago_id]);
    } else {
        // Si la columna no existe, actualizar solo el estado
        $query = "UPDATE pagos SET estado = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        $resultado = $stmt->execute([$nuevo_estado, $pago_id]);
    }
    
    if ($resultado) {
        $mensaje = "Estado del pago actualizado correctamente";
        
        // Si se verifica un pago, actualizar la deuda del cliente
        if ($nuevo_estado == 'verificado') {
            $query = "SELECT cliente_id, monto FROM pagos WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$pago_id]);
            $pago_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pago_info) {
                $query = "UPDATE clientes SET deuda_actual = GREATEST(0, deuda_actual - ?) WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$pago_info['monto'], $pago_info['cliente_id']]);
                
                // Actualizar estado del cliente si la deuda llega a 0
                $query = "UPDATE clientes SET estado = 'al día' WHERE id = ? AND deuda_actual = 0";
                $stmt = $db->prepare($query);
                $stmt->execute([$pago_info['cliente_id']]);
            }
        }
    } else {
        $mensaje = "Error al actualizar el estado del pago";
    }
}

// Construir consulta de pagos
$query = "SELECT p.*, c.dni, c.nombre, c.telefono, c.servicio, c.deuda_actual 
          FROM pagos p 
          JOIN clientes c ON p.cliente_id = c.id 
          WHERE 1=1";

$params = [];

if ($estado != 'todos') {
    $query .= " AND p.estado = ?";
    $params[] = $estado;
}

$query .= " ORDER BY p.fecha_pago DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Pagos - MIRAMAXNET</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a2a6c;
            --secondary: #2c3e50;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .pagos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--primary);
        }
        
        .pagos-header h1 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-info {
            color: var(--gray);
            font-size: 16px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #2c3e50);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 42, 108, 0.3);
        }
        
        .btn-secondary {
            background: var(--gray);
            color: white;
        }
        
        .nav-admin {
            display: flex;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .nav-admin a {
            flex: 1;
            text-align: center;
            padding: 15px;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .nav-admin a:hover {
            background: rgba(26, 42, 108, 0.05);
        }
        
        .nav-admin a.active {
            background: rgba(26, 42, 108, 0.1);
            font-weight: bold;
            color: var(--primary);
        }
        
        .filtros-pagos {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .filtros-pagos h3 {
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 42, 108, 0.1);
            outline: none;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .pagos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .pago-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--warning);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .pago-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .pago-card.verificado { 
            border-left-color: var(--success); 
            background: #f8fff9;
        }
        
        .pago-card.rechazado { 
            border-left-color: var(--danger); 
            background: #fff8f8;
        }
        
        .pago-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .pago-info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .pago-info-item:hover {
            background: #e9ecef;
        }
        
        .pago-acciones {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .comprobante-container {
            margin: 15px 0;
            text-align: center;
        }
        
        .comprobante-img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid #e9ecef;
            transition: var(--transition);
        }
        
        .comprobante-img:hover {
            border-color: var(--primary);
            transform: scale(1.02);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-img {
            max-width: 90%;
            max-height: 90vh;
            border-radius: 10px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            animation: modalAppear 0.3s ease;
        }
        
        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .estado-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .estado-pendiente { 
            background: #fff3cd; 
            color: #856404; 
        }
        
        .estado-verificado { 
            background: #d4edda; 
            color: #155724; 
        }
        
        .estado-rechazado { 
            background: #f8d7da; 
            color: #721c24; 
        }
        
        .btn-accion {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: var(--transition);
            flex: 1;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-verificar { 
            background: var(--success); 
            color: white; 
        }
        
        .btn-verificar:hover { 
            background: #218838; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-rechazar { 
            background: var(--danger); 
            color: white; 
        }
        
        .btn-rechazar:hover { 
            background: #c82333; 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .observaciones-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            transition: var(--transition);
        }
        
        .observaciones-input:focus {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
            outline: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
            grid-column: 1 / -1;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: var(--gray);
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--danger);
        }
        
        .observaciones-container {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--danger);
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .pagos-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-admin {
                flex-wrap: wrap;
            }
            
            .nav-admin a {
                flex: 1 1 50%;
            }
        }
        
        @media (max-width: 768px) {
            .pagos-grid {
                grid-template-columns: 1fr;
            }
            
            .pago-info {
                grid-template-columns: 1fr;
            }
            
            .pago-acciones {
                flex-direction: column;
            }
            
            .btn-accion {
                width: 100%;
            }
            
            .pagos-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-admin a {
                flex: 1 1 100%;
            }
        }
        
        @media (max-width: 480px) {
            .admin-container {
                padding: 0 10px;
            }
            
            .pagos-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="pagos-header">
            <div>
                <h1><i class="fas fa-credit-card"></i> Verificación de Pagos</h1>
                <div class="user-info">
                    Total: <?php echo count($pagos); ?> pagos <?php echo $estado != 'todos' ? "($estado)" : ''; ?>
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <!-- Menú de Navegación -->
        <div class="nav-admin">
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="clientes.php"><i class="fas fa-users"></i> Clientes</a>
            <a href="pagos.php" class="active"><i class="fas fa-credit-card"></i> Pagos</a>
            <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?php echo strpos($mensaje, 'Error') === false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filtros-pagos">
            <h3><i class="fas fa-filter"></i> Filtros de Búsqueda</h3>
            <form method="GET">
                <div class="filtros-grid">
                    <div class="form-group">
                        <label>Estado del pago:</label>
                        <select name="estado" class="form-control" onchange="this.form.submit()">
                            <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>
                                <i class="fas fa-clock"></i> Pendientes
                            </option>
                            <option value="verificado" <?php echo $estado == 'verificado' ? 'selected' : ''; ?>>
                                <i class="fas fa-check-circle"></i> Verificados
                            </option>
                            <option value="rechazado" <?php echo $estado == 'rechazado' ? 'selected' : ''; ?>>
                                <i class="fas fa-times-circle"></i> Rechazados
                            </option>
                            <option value="todos" <?php echo $estado == 'todos' ? 'selected' : ''; ?>>
                                <i class="fas fa-list"></i> Todos
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Aplicar Filtros</button>
                    </div>
                    
                    <div class="form-group">
                        <a href="pagos.php" class="btn btn-secondary"><i class="fas fa-eraser"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de Pagos -->
        <div class="pagos-grid">
            <?php if (empty($pagos)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-smile"></i></div>
                    <h3>No hay pagos <?php echo $estado != 'todos' ? $estado : ''; ?></h3>
                    <p>Cuando los clientes envíen comprobantes, aparecerán aquí para verificación.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pagos as $pago): ?>
                <div class="pago-card <?php echo $pago['estado']; ?>">
                    <!-- Información del Cliente -->
                    <div class="pago-info">
                        <div class="pago-info-item">
                            <strong><i class="fas fa-user"></i> Cliente:</strong><br>
                            <?php echo htmlspecialchars($pago['nombre']); ?><br>
                            <small>DNI: <?php echo $pago['dni']; ?></small>
                        </div>
                        
                        <div class="pago-info-item">
                            <strong><i class="fas fa-money-bill-wave"></i> Pago:</strong><br>
                            S/ <?php echo number_format($pago['monto'], 2); ?><br>
                            <small>Método: <?php echo strtoupper($pago['metodo_pago']); ?></small>
                        </div>
                        
                        <div class="pago-info-item">
                            <strong><i class="fas fa-calendar-alt"></i> Información:</strong><br>
                            <?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?><br>
                            <small>Operación: <?php echo $pago['numero_operacion']; ?></small>
                        </div>
                        
                        <div class="pago-info-item">
                            <strong><i class="fas fa-info-circle"></i> Estado:</strong><br>
                            <span class="estado-badge estado-<?php echo $pago['estado']; ?>">
                                <i class="fas fa-<?php 
                                    echo $pago['estado'] == 'pendiente' ? 'clock' : 
                                         ($pago['estado'] == 'verificado' ? 'check-circle' : 'times-circle'); 
                                ?>"></i>
                                <?php echo strtoupper($pago['estado']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Comprobante -->
                    <div class="comprobante-container">
                        <strong><i class="fas fa-paperclip"></i> Comprobante:</strong><br>
                        <?php if ($pago['comprobante'] && file_exists('../uploads/' . $pago['comprobante'])): ?>
                            <?php 
                            $extension = strtolower(pathinfo($pago['comprobante'], PATHINFO_EXTENSION));
                            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                <img src="../uploads/<?php echo $pago['comprobante']; ?>" 
                                     alt="Comprobante de pago" 
                                     class="comprobante-img"
                                     onclick="mostrarImagen(this.src)">
                                <br>
                                <small style="color: var(--gray);">Haz click para ver en grande</small>
                            <?php elseif ($extension == 'pdf'): ?>
                                <a href="../uploads/<?php echo $pago['comprobante']; ?>" 
                                   target="_blank" 
                                   class="btn" 
                                   style="background: var(--danger); color: white; padding: 12px 20px; margin-top: 10px; display: inline-flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-file-pdf"></i> Ver PDF
                                </a>
                            <?php else: ?>
                                <div style="color: var(--gray); padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                    <i class="fas fa-paperclip"></i> Archivo adjunto
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--gray); padding: 20px; background: #f8f9fa; border-radius: 8px;">
                                <i class="fas fa-times-circle"></i> Comprobante no disponible
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Acciones para pagos pendientes -->
                    <?php if ($pago['estado'] == 'pendiente'): ?>
                    <div class="pago-acciones">
                        <!-- Verificar -->
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="verificado">
                            <button type="submit" name="cambiar_estado" class="btn-accion btn-verificar" onclick="return confirm('¿Estás seguro de VERIFICAR este pago?')">
                                <i class="fas fa-check-circle"></i> Verificar Pago
                            </button>
                        </form>
                        
                        <!-- Rechazar -->
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="pago_id" value="<?php echo $pago['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="rechazado">
                            <input type="text" name="observaciones" 
                                   placeholder="Motivo de rechazo..." 
                                   class="observaciones-input"
                                   required>
                            <button type="submit" name="cambiar_estado" class="btn-accion btn-rechazar" style="margin-top: 10px;">
                                <i class="fas fa-times-circle"></i> Rechazar Pago
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Observaciones -->
                    <?php if (!empty($pago['observaciones'])): ?>
                    <div class="observaciones-container">
                        <strong><i class="fas fa-sticky-note"></i> Observaciones:</strong><br>
                        <?php echo htmlspecialchars($pago['observaciones']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para imagen grande -->
    <div id="modalImagen" class="modal" onclick="ocultarImagen()">
        <div style="text-align: center; position: relative;">
            <img id="imagenGrande" class="modal-img">
            <button onclick="ocultarImagen()" style="position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: var(--transition);">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        function mostrarImagen(src) {
            document.getElementById('imagenGrande').src = src;
            document.getElementById('modalImagen').style.display = 'flex';
        }
        
        function ocultarImagen() {
            document.getElementById('modalImagen').style.display = 'none';
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ocultarImagen();
            }
        });
        
        // Confirmación para rechazar
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('input[name="nuevo_estado"][value="rechazado"]')) {
                form.addEventListener('submit', function(e) {
                    const observaciones = this.querySelector('input[name="observaciones"]').value;
                    if (!observaciones.trim()) {
                        e.preventDefault();
                        alert('Por favor ingresa el motivo del rechazo');
                        return;
                    }
                    if (!confirm('¿Estás seguro de RECHAZAR este pago?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>