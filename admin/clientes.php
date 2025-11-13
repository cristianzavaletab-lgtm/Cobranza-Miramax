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

// Variables para búsqueda y filtros
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? 'todos';
$mensaje = '';

// Procesar eliminación de cliente
if (isset($_GET['eliminar'])) {
    $id_eliminar = $_GET['eliminar'];
    $query = "DELETE FROM clientes WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$id_eliminar])) {
        $mensaje = "✅ Cliente eliminado correctamente";
    } else {
        $mensaje = "❌ Error al eliminar el cliente";
    }
}

// Procesar nuevo cliente
if (isset($_POST['agregar_cliente'])) {
    $dni = $_POST['dni'];
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $servicio = $_POST['servicio'];
    $deuda = $_POST['deuda_actual'];
    $vencimiento = $_POST['fecha_vencimiento'];
    
    // Verificar si el DNI ya existe
    $query = "SELECT id FROM clientes WHERE dni = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$dni]);
    
    if ($stmt->fetch()) {
        $mensaje = "❌ El DNI ya está registrado en el sistema";
    } else {
        $query = "INSERT INTO clientes (dni, nombre, telefono, servicio, deuda_actual, fecha_vencimiento, estado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        $estado_cliente = ($deuda > 0) ? 'pendiente' : 'al día';
        
        if ($stmt->execute([$dni, $nombre, $telefono, $servicio, $deuda, $vencimiento, $estado_cliente])) {
            $mensaje = "✅ Cliente agregado correctamente";
        } else {
            $mensaje = "❌ Error al agregar el cliente";
        }
    }
}

// Construir consulta con filtros
$query = "SELECT * FROM clientes WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $query .= " AND (dni LIKE ? OR nombre LIKE ? OR telefono LIKE ?)";
    $like_param = "%$busqueda%";
    $params[] = $like_param;
    $params[] = $like_param;
    $params[] = $like_param;
}

if ($estado != 'todos') {
    $query .= " AND estado = ?";
    $params[] = $estado;
}

$query .= " ORDER BY fecha_vencimiento ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - MIRAMAXNET</title>
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
        
        .clientes-header {
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
        
        .clientes-header h1 {
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
        
        .stats-clientes {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-cliente {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--primary);
            transition: var(--transition);
        }
        
        .stat-cliente:hover {
            transform: translateY(-5px);
        }
        
        .stat-cliente.deuda { 
            border-left-color: var(--danger); 
        }
        
        .stat-cliente.al-dia { 
            border-left-color: var(--success); 
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .stat-cliente .stat-icon { color: var(--primary); }
        .stat-cliente.deuda .stat-icon { color: var(--danger); }
        .stat-cliente.al-dia .stat-icon { color: var(--success); }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .stat-cliente.deuda .stat-number { color: var(--danger); }
        .stat-cliente.al-dia .stat-number { color: var(--success); }
        
        .stat-label {
            color: var(--gray);
            font-size: 14px;
        }
        
        .filtros-clientes {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
        }
        
        .filtros-clientes h3 {
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: 2fr 1fr auto auto;
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
        
        .clientes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .cliente-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--primary);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .cliente-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .cliente-card.pendiente { border-left-color: var(--warning); }
        .cliente-card.vencido { border-left-color: var(--danger); }
        .cliente-card.al-dia { border-left-color: var(--success); }
        
        .estado-cliente {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .estado-pendiente { 
            background: #fff3cd; 
            color: #856404; 
        }
        
        .estado-al-dia { 
            background: #d4edda; 
            color: #155724; 
        }
        
        .estado-vencido { 
            background: #f8d7da; 
            color: #721c24; 
        }
        
        .cliente-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-right: 80px;
        }
        
        .cliente-nombre {
            color: var(--primary);
            margin-bottom: 5px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .cliente-dni {
            color: var(--gray);
            font-size: 14px;
        }
        
        .cliente-info {
            margin-bottom: 15px;
        }
        
        .cliente-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .cliente-info-label {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cliente-info-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .cliente-deuda {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .cliente-deuda-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .cliente-deuda-valor {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .cliente-deuda-estado {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .cliente-acciones {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-accion {
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: var(--transition);
            flex: 1;
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-editar { 
            background: var(--warning); 
            color: black; 
        }
        
        .btn-eliminar { 
            background: var(--danger); 
            color: white; 
        }
        
        .btn-pagar { 
            background: var(--success); 
            color: white; 
        }
        
        .btn-whatsapp { 
            background: #25D366; 
            color: white; 
        }
        
        .btn-accion:hover {
            transform: translateY(-2px);
            opacity: 0.9;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
            animation: modalAppear 0.3s ease;
        }
        
        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .modal-header h2 {
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: var(--gray);
            transition: var(--transition);
        }
        
        .close-modal:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
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
        
        /* Responsive */
        @media (max-width: 1200px) {
            .clientes-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .filtros-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .nav-admin {
                flex-wrap: wrap;
            }
            
            .nav-admin a {
                flex: 1 1 50%;
            }
        }
        
        @media (max-width: 768px) {
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .clientes-grid {
                grid-template-columns: 1fr;
            }
            
            .cliente-acciones {
                flex-direction: column;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-clientes {
                grid-template-columns: 1fr;
            }
            
            .clientes-header {
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
            
            .clientes-header {
                padding: 20px;
            }
            
            .cliente-header {
                flex-direction: column;
                align-items: flex-start;
                padding-right: 0;
            }
            
            .estado-cliente {
                position: static;
                align-self: flex-start;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="clientes-header">
            <div>
                <h1><i class="fas fa-users"></i> Gestión de Clientes</h1>
                <div class="user-info">
                    Total: <?php echo count($clientes); ?> clientes registrados
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <button onclick="mostrarModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Cliente</button>
            </div>
        </div>

        <!-- Menú de Navegación -->
        <div class="nav-admin">
            <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="clientes.php" class="active"><i class="fas fa-users"></i> Clientes</a>
            <a href="pagos.php"><i class="fas fa-credit-card"></i> Pagos</a>
            <a href="reportes.php"><i class="fas fa-chart-bar"></i> Reportes</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?php echo strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas Rápidas -->
        <?php
        $total_clientes = count($clientes);
        $clientes_deuda = array_filter($clientes, function($cliente) {
            return $cliente['deuda_actual'] > 0;
        });
        $clientes_al_dia = array_filter($clientes, function($cliente) {
            return $cliente['deuda_actual'] == 0;
        });
        ?>
        <div class="stats-clientes">
            <div class="stat-cliente">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-number"><?php echo $total_clientes; ?></div>
                <div class="stat-label">Total Clientes</div>
            </div>
            <div class="stat-cliente deuda">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-number"><?php echo count($clientes_deuda); ?></div>
                <div class="stat-label">Con Deuda</div>
            </div>
            <div class="stat-cliente al-dia">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo count($clientes_al_dia); ?></div>
                <div class="stat-label">Al Día</div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="filtros-clientes">
            <h3><i class="fas fa-search"></i> Buscar Clientes</h3>
            <form method="GET">
                <div class="filtros-grid">
                    <div class="form-group">
                        <label>Buscar cliente:</label>
                        <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" 
                               class="form-control" placeholder="DNI, nombre o teléfono...">
                    </div>
                    
                    <div class="form-group">
                        <label>Estado:</label>
                        <select name="estado" class="form-control">
                            <option value="todos" <?php echo $estado == 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="al día" <?php echo $estado == 'al día' ? 'selected' : ''; ?>>Al día</option>
                            <option value="pendiente" <?php echo $estado == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="vencido" <?php echo $estado == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    </div>
                    
                    <div class="form-group">
                        <a href="clientes.php" class="btn btn-secondary"><i class="fas fa-eraser"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Grid de Clientes -->
        <div class="clientes-grid">
            <?php if (empty($clientes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-search"></i></div>
                    <h3>No se encontraron clientes</h3>
                    <p><?php echo !empty($busqueda) ? 'Prueba con otros términos de búsqueda' : 'Agrega el primer cliente al sistema'; ?></p>
                    <?php if (empty($busqueda)): ?>
                        <button onclick="mostrarModal()" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Agregar Primer Cliente
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($clientes as $cliente): ?>
                <div class="cliente-card <?php echo $cliente['estado']; ?>">
                    <span class="estado-cliente estado-<?php echo $cliente['estado']; ?>">
                        <?php echo strtoupper($cliente['estado']); ?>
                    </span>
                    
                    <div class="cliente-header">
                        <div>
                            <div class="cliente-nombre"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
                            <div class="cliente-dni">DNI: <?php echo $cliente['dni']; ?></div>
                        </div>
                    </div>

                    <div class="cliente-info">
                        <div class="cliente-info-item">
                            <span class="cliente-info-label"><i class="fas fa-phone"></i> Teléfono:</span>
                            <span class="cliente-info-value"><?php echo $cliente['telefono']; ?></span>
                        </div>
                        <div class="cliente-info-item">
                            <span class="cliente-info-label"><i class="fas fa-wifi"></i> Servicio:</span>
                            <span class="cliente-info-value"><?php echo htmlspecialchars($cliente['servicio']); ?></span>
                        </div>
                        <div class="cliente-info-item">
                            <span class="cliente-info-label"><i class="fas fa-calendar-alt"></i> Vencimiento:</span>
                            <span class="cliente-info-value"><?php echo $cliente['fecha_vencimiento']; ?></span>
                        </div>
                    </div>

                    <div class="cliente-deuda">
                        <div class="cliente-deuda-label">Deuda Actual</div>
                        <div class="cliente-deuda-valor">S/ <?php echo number_format($cliente['deuda_actual'], 2); ?></div>
                        <div class="cliente-deuda-estado">
                            <?php echo $cliente['deuda_actual'] > 0 ? 'Pendiente de pago' : 'Al día'; ?>
                        </div>
                    </div>

                    <div class="cliente-acciones">
                        <a href="https://wa.me/51<?php echo $cliente['telefono']; ?>?text=Hola <?php echo urlencode($cliente['nombre']); ?>, te contactamos de MIRAMAXNET" 
                           target="_blank" class="btn-accion btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        
                        <?php if ($cliente['deuda_actual'] > 0): ?>
                            <a href="#" class="btn-accion btn-pagar">
                                <i class="fas fa-money-bill-wave"></i> Cobrar
                            </a>
                        <?php endif; ?>
                        
                        <a href="#" class="btn-accion btn-editar">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        
                        <a href="clientes.php?eliminar=<?php echo $cliente['id']; ?>" 
                           class="btn-accion btn-eliminar" 
                           onclick="return confirm('¿Estás seguro de eliminar a <?php echo addslashes($cliente['nombre']); ?>?')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para nuevo cliente -->
    <div id="modalNuevoCliente" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Agregar Nuevo Cliente</h2>
                <button onclick="ocultarModal()" class="close-modal">×</button>
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>DNI *:</label>
                        <input type="text" name="dni" maxlength="8" required class="form-control" 
                               placeholder="Ej: 12345678">
                    </div>
                    
                    <div class="form-group">
                        <label>Teléfono:</label>
                        <input type="text" name="telefono" class="form-control" 
                               placeholder="Ej: 999888777">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label>Nombre completo *:</label>
                        <input type="text" name="nombre" required class="form-control" 
                               placeholder="Ej: Juan Pérez García">
                    </div>
                    
                    <div class="form-group form-group-full">
                        <label>Servicio *:</label>
                        <input type="text" name="servicio" required class="form-control" 
                               placeholder="Ej: Internet 100MB, Cable Full, etc.">
                    </div>
                    
                    <div class="form-group">
                        <label>Deuda actual:</label>
                        <input type="number" name="deuda_actual" step="0.01" class="form-control" value="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de vencimiento:</label>
                        <input type="date" name="fecha_vencimiento" class="form-control">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="ocultarModal()" class="btn btn-secondary" style="flex: 1;">Cancelar</button>
                    <button type="submit" name="agregar_cliente" value="1" class="btn btn-primary" style="flex: 1;">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mostrarModal() {
            document.getElementById('modalNuevoCliente').style.display = 'flex';
        }
        
        function ocultarModal() {
            document.getElementById('modalNuevoCliente').style.display = 'none';
        }
        
        // Cerrar modal al hacer click fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalNuevoCliente');
            if (event.target == modal) {
                ocultarModal();
            }
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                ocultarModal();
            }
        });
        
        // Auto-formato para teléfono
        document.querySelector('input[name="telefono"]')?.addEventListener('input', function(e) {
            let valor = this.value.replace(/\D/g, '');
            if (valor.length > 9) valor = valor.substring(0, 9);
            this.value = valor;
        });
        
        // Mejorar la experiencia de usuario en dispositivos móviles
        if (window.innerWidth <= 768) {
            // Ajustes específicos para móviles
            document.querySelectorAll('.cliente-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (!e.target.closest('.cliente-acciones')) {
                        this.classList.toggle('expanded');
                    }
                });
            });
        }
    </script>
</body>
</html>