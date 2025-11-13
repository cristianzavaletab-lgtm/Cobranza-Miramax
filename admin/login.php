<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    
    // Credenciales temporales para prueba
    $usuarios_validos = [
        'admin' => password_hash('admin123', PASSWORD_DEFAULT),
        'cobrador' => password_hash('cobrador123', PASSWORD_DEFAULT)
    ];
    
    if (array_key_exists($usuario, $usuarios_validos) && password_verify($password, $usuarios_validos[$usuario])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_usuario'] = $usuario;
        $_SESSION['admin_nivel'] = ($usuario == 'admin') ? 'admin' : 'cobrador';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - MIRAMAX</title>
    <link rel="stylesheet" href="../css/estilo.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Panel Administrativo</h1>
            <p style="color: #6c757d;">MIRAMAX</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Ingresar al Sistema</button>
        </form>

        <div style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 12px;">
            <p>Credenciales de prueba:<br>
            Usuario: <strong>admin</strong> | Contraseña: <strong>admin123</strong></p>
        </div>
    </div>
</body>
</html>