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

// Generar reporte
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Reporte de pagos
$query = "SELECT p.*, c.nombre, c.dni 
          FROM pagos p 
          JOIN clientes c ON p.cliente_id = c.id 
          WHERE p.fecha_pago BETWEEN ? AND ? 
          ORDER BY p.fecha_pago DESC";
$stmt = $db->prepare($query);
$stmt->execute([$fecha_inicio, $fecha_fin]);
$pagos_reporte = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totales
$query = "SELECT 
            COUNT(*) as total_pagos,
            SUM(CASE WHEN estado = 'verificado' THEN monto ELSE 0 END) as total_verificado,
            SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as total_pendiente
          FROM pagos 
          WHERE fecha_pago BETWEEN ? AND ?";
$stmt = $db->prepare($query);
$stmt->execute([$fecha_inicio, $fecha_fin]);
$totales = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - MIRAMAXNET</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .reportes-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        .filtros-reporte {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .resumen-reporte {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .resumen-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .tabla-reporte {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .tabla-reporte th {
            background: #1a2a6c;
            color: white;
            padding: 15px;
            text-align: left;
        }
        .tabla-reporte td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .tabla-reporte tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="reportes-container">
        <div class="dashboard-header">
            <div>
                <h1 style="color: #1a2a6c;">📈 Reportes Avanzados</h1>
                <p class="user-info">Genera reportes detallados de pagos y clientes</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn" style="background: #6c757d; color: white;">← Dashboard</a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-reporte">
            <h3 style="color: #1a2a6c; margin-bottom: 20px;">🔍 Filtrar Reporte</h3>
            <form method="GET">
                <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label>Fecha inicio:</label>
                        <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>" class="form-control">
                    </div>
                    <div>
                        <label>Fecha fin:</label>
                        <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>" class="form-control">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Generar Reporte</button>
                        <button type="button" onclick="window.print()" class="btn btn-success">🖨️ Imprimir</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Resumen -->
        <div class="resumen-reporte">
            <div class="resumen-item">
                <div style="font-size: 24px; color: #1a2a6c; font-weight: bold;"><?php echo $totales['total_pagos']; ?></div>
                <div style="color: #6c757d;">Total Pagos</div>
            </div>
            <div class="resumen-item">
                <div style="font-size: 24px; color: #28a745; font-weight: bold;">S/ <?php echo number_format($totales['total_verificado'], 2); ?></div>
                <div style="color: #6c757d;">Total Verificado</div>
            </div>
            <div class="resumen-item">
                <div style="font-size: 24px; color: #ffc107; font-weight: bold;">S/ <?php echo number_format($totales['total_pendiente'], 2); ?></div>
                <div style="color: #6c757d;">Total Pendiente</div>
            </div>
        </div>

        <!-- Tabla de Reporte -->
        <div class="tabla-reporte">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>DNI</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos_reporte as $pago): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pago['nombre']); ?></td>
                        <td><?php echo $pago['dni']; ?></td>
                        <td>S/ <?php echo number_format($pago['monto'], 2); ?></td>
                        <td><?php echo strtoupper($pago['metodo_pago']); ?></td>
                        <td>
                            <span class="estado estado-<?php echo $pago['estado']; ?>">
                                <?php echo strtoupper($pago['estado']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>