<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/funciones.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas REALES
// Total clientes
$query = "SELECT COUNT(*) as total FROM clientes";
$stmt = $db->prepare($query);
$stmt->execute();
$total_clientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Clientes con deuda
$query = "SELECT COUNT(*) as total FROM clientes WHERE deuda_actual > 0";
$stmt = $db->prepare($query);
$stmt->execute();
$clientes_deuda = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagos pendientes
$query = "SELECT COUNT(*) as total FROM pagos WHERE estado = 'pendiente'";
$stmt = $db->prepare($query);
$stmt->execute();
$pagos_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total recaudado ESTE MES
$query = "SELECT SUM(monto) as total FROM pagos WHERE estado = 'verificado' AND MONTH(fecha_pago) = MONTH(CURRENT_DATE()) AND YEAR(fecha_pago) = YEAR(CURRENT_DATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$total_recaudado = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Últimos pagos pendientes
$query = "SELECT p.*, c.nombre, c.dni FROM pagos p 
          JOIN clientes c ON p.cliente_id = c.id 
          WHERE p.estado = 'pendiente' 
          ORDER BY p.fecha_pago DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$ultimos_pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MIRAMAXNET</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #1a2a6c;
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card.recaudado { border-left-color: #28a745; }
        .stat-card.pendientes { border-left-color: #ffc107; }
        .stat-card.deuda { border-left-color: #dc3545; }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #1a2a6c;
            margin: 15px 0;
        }
        .stat-card.recaudado .stat-number { color: #28a745; }
        .stat-card.pendientes .stat-number { color: #ffc107; }
        .stat-card.deuda .stat-number { color: #dc3545; }
        .stat-label {
            color: #6c757d;
            font-size: 16px;
            font-weight: 500;
        }
        .nav-admin {
            background: linear-gradient(135deg, #1a2a6c 0%, #2c3e50 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .nav-admin a {
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.1);
        }
        .nav-admin a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        .nav-admin a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .acciones-rapidas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .accion-btn {
            background: white;
            padding: 25px 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .accion-btn:hover {
            transform: translateY(-3px);
            border-color: #1a2a6c;
            color: #1a2a6c;
        }
        .pagos-recientes {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .pago-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.3s ease;
        }
        .pago-item:hover {
            background: #f8f9fa;
        }
        .pago-item:last-child {
            border-bottom: none;
        }
        .user-info {
            color: #6c757d;
            font-size: 14px;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-state img {
            width: 100px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1 style="color: #1a2a6c; margin-bottom: 10px;">📊 Dashboard MIRAMAXNET</h1>
                <div class="user-info">
                    👤 <?php echo $_SESSION['admin_usuario']; ?> | 
                    🎯 <?php echo $_SESSION['admin_nivel']; ?>
                </div>
            </div>
            <div>
                <a href="logout.php" class="btn" style="background: #dc3545; color: white; padding: 12px 25px;">
                    🚪 Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Menú de Navegación -->
        <div class="nav-admin">
            <a href="dashboard.php" class="active">📊 Dashboard</a>
            <a href="clientes.php">👥 Gestión de Clientes</a>
            <a href="pagos.php">💳 Verificación de Pagos</a>
            <a href="reportes.php">📈 Reportes Avanzados</a>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Clientes Registrados</div>
                <div class="stat-number"><?php echo $total_clientes; ?></div>
                <div style="color: #6c757d; font-size: 14px;">En el sistema</div>
            </div>
            
            <div class="stat-card deuda">
                <div class="stat-label">Clientes con Deuda</div>
                <div class="stat-number"><?php echo $clientes_deuda; ?></div>
                <div style="color: #6c757d; font-size: 14px;">Pendientes de pago</div>
            </div>
            
            <div class="stat-card pendientes">
                <div class="stat-label">Pagos Pendientes</div>
                <div class="stat-number"><?php echo $pagos_pendientes; ?></div>
                <div style="color: #6c757d; font-size: 14px;">Por verificar</div>
            </div>
            
            <div class="stat-card recaudado">
                <div class="stat-label">Recaudado (Este Mes)</div>
                <div class="stat-number">S/ <?php echo number_format($total_recaudado, 2); ?></div>
                <div style="color: #6c757d; font-size: 14px;">Total verificado</div>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <h2 style="color: #1a2a6c; margin-bottom: 20px;">🚀 Acciones Rápidas</h2>
        <div class="acciones-rapidas">
            <a href="clientes.php?estado=pendiente" class="accion-btn">
                <div style="font-size: 32px; margin-bottom: 10px;">💰</div>
                <strong>Ver Deudores</strong>
                <div style="color: #6c757d; font-size: 14px; margin-top: 5px;">Clientes con deuda pendiente</div>
            </a>
            
            <a href="pagos.php" class="accion-btn">
                <div style="font-size: 32px; margin-bottom: 10px;">✅</div>
                <strong>Verificar Pagos</strong>
                <div style="color: #6c757d; font-size: 14px; margin-top: 5px;">Comprobantes pendientes</div>
            </a>
            
            <a href="clientes.php?action=nuevo" class="accion-btn">
                <div style="font-size: 32px; margin-bottom: 10px;">👤</div>
                <strong>Nuevo Cliente</strong>
                <div style="color: #6c757d; font-size: 14px; margin-top: 5px;">Agregar cliente al sistema</div>
            </a>
            
            <a href="reportes.php" class="accion-btn">
                <div style="font-size: 32px; margin-bottom: 10px;">📈</div>
                <strong>Generar Reporte</strong>
                <div style="color: #6c757d; font-size: 14px; margin-top: 5px;">Reportes avanzados</div>
            </a>
        </div>

        <!-- Últimos Pagos Pendientes -->
        <div class="pagos-recientes">
            <h2 style="color: #1a2a6c; margin-bottom: 25px;">🕒 Últimos Pagos Pendientes</h2>
            
            <?php if (empty($ultimos_pagos)): ?>
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">😊</div>
                    <h3 style="color: #6c757d; margin-bottom: 10px;">No hay pagos pendientes</h3>
                    <p style="color: #6c757d;">Cuando los clientes envíen comprobantes, aparecerán aquí.</p>
                </div>
            <?php else: ?>
                <?php foreach ($ultimos_pagos as $pago): ?>
                <div class="pago-item">
                    <div style="flex: 1;">
                        <strong><?php echo htmlspecialchars($pago['nombre']); ?></strong>
                        <div style="color: #6c757d; font-size: 14px;">
                            DNI: <?php echo $pago['dni']; ?> | 
                            S/ <?php echo number_format($pago['monto'], 2); ?> | 
                            <?php echo date('d/m H:i', strtotime($pago['fecha_pago'])); ?>
                        </div>
                    </div>
                    <div>
                        <a href="pagos.php" class="btn" style="background: #28a745; color: white; padding: 8px 15px;">
                            Verificar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="pagos.php" class="btn btn-primary">Ver Todos los Pagos Pendientes</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>