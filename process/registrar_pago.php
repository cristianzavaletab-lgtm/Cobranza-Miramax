<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener datos
$dni = $_POST['dni'] ?? '';
$metodo_pago = $_POST['metodo_pago'] ?? '';
$numero_operacion = $_POST['numero_operacion'] ?? '';
$monto = $_POST['monto'] ?? '';

// Validar campos obligatorios
if (empty($dni) || empty($metodo_pago) || empty($numero_operacion) || empty($monto)) {
    $_SESSION['error_pago'] = "Todos los campos son obligatorios";
    header('Location: ../pago.php?dni=' . $dni);
    exit;
}

// Buscar cliente
$cliente = consultarDeuda($dni);
if (!$cliente) {
    $_SESSION['error_pago'] = "Cliente no encontrado";
    header('Location: ../pago.php?dni=' . $dni);
    exit;
}

try {
    // 🔹 1. Validar número de operación duplicado
    $query = "SELECT id FROM pagos WHERE numero_operacion = ? AND metodo_pago = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$numero_operacion, $metodo_pago]);
    if ($stmt->fetch()) {
        $_SESSION['error_pago'] = "❌ Este número de operación ya fue registrado anteriormente";
        header('Location: ../pago.php?dni=' . $dni);
        exit;
    }

    // 🔹 2. Límite de tiempo entre pagos (mínimo 5 minutos)
    $query = "SELECT fecha_pago FROM pagos WHERE cliente_id = ? ORDER BY fecha_pago DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$cliente['id']]);
    $ultimo_pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ultimo_pago && (time() - strtotime($ultimo_pago['fecha_pago'])) < 300) { // 5 minutos
        $_SESSION['error_pago'] = "❌ Espera 5 minutos antes de registrar otro pago";
        header('Location: ../pago.php?dni=' . $dni);
        exit;
    }

    // 🔹 Procesar comprobante
    $comprobante = '';
    $hash_imagen = null;

    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
        $archivo_tmp = $_FILES['comprobante']['tmp_name'];
        $nombre_archivo = $_FILES['comprobante']['name'];
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

        // Validar extensión
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        if (!in_array($extension, $extensiones_permitidas)) {
            $_SESSION['error_pago'] = "Solo se permiten archivos JPG, PNG, GIF o PDF";
            header('Location: ../pago.php?dni=' . $dni);
            exit;
        }

        if ($_FILES['comprobante']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error_pago'] = "El archivo no puede ser mayor a 2MB";
            header('Location: ../pago.php?dni=' . $dni);
            exit;
        }

        // 🔹 3. Calcular hash de la imagen (si no es PDF)
        if ($extension !== 'pdf') {
            $hash_imagen = md5_file($archivo_tmp);

            // Validar que no se haya subido antes el mismo comprobante
            $query = "SELECT id FROM pagos WHERE hash_imagen = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$hash_imagen]);

            if ($stmt->fetch()) {
                $_SESSION['error_pago'] = "❌ Este comprobante ya fue registrado anteriormente";
                header('Location: ../pago.php?dni=' . $dni);
                exit;
            }
        }

        // Generar nombre único
        $comprobante = uniqid() . '_' . $dni . '.' . $extension;
        $ruta_destino = '../uploads/' . $comprobante;

        if (!move_uploaded_file($archivo_tmp, $ruta_destino)) {
            $_SESSION['error_pago'] = "Error al subir el comprobante";
            header('Location: ../pago.php?dni=' . $dni);
            exit;
        }
    }

    // 🔹 Iniciar transacción
    $db->beginTransaction();

    // Registrar pago
    $query = "INSERT INTO pagos (cliente_id, monto, metodo_pago, comprobante, numero_operacion, hash_imagen, estado) 
              VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $cliente['id'],
        $monto,
        $metodo_pago,
        $comprobante,
        $numero_operacion,
        $hash_imagen
    ]);

    // Actualizar deuda del cliente
    $query = "UPDATE clientes SET deuda_actual = 0, estado = 'al día' WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$cliente['id']]);

    // Confirmar transacción
    $db->commit();

    $_SESSION['exito_pago'] = "✅ Pago registrado correctamente. Tu deuda ha sido liquidada.";
    header('Location: ../consulta.php?dni=' . $dni);
    exit;

} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error_pago'] = "❌ Error al procesar el pago: " . $e->getMessage();
    header('Location: ../pago.php?dni=' . $dni);
    exit;
}
?>
