<?php
session_start();
require_once 'includes/db.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'];
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // Validaciones básicas
    if (empty($nombre_usuario) || empty($correo) || empty($contrasena)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El formato del correo electrónico no es válido.";
    } else {
        // Hashear la contraseña antes de almacenarla
        $contrasena_hasheada = password_hash($contrasena, PASSWORD_DEFAULT);

        try {
            // Verificar si el usuario o correo ya existen
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = ? OR correo = ?");
            $stmt->execute([$nombre_usuario, $correo]);
            if ($stmt->fetchColumn() > 0) {
                $mensaje = "El nombre de usuario o correo electrónico ya están registrados.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, correo, contrasena) VALUES (?, ?, ?)");
                $stmt->execute([$nombre_usuario, $correo, $contrasena_hasheada]);
                $mensaje = "Registro exitoso. ¡Ahora puedes iniciar sesión!";
                header("Location: login.php"); // Redirige al login
                exit();
            }
        } catch (PDOException $e) {
            $mensaje = "Error al registrar el usuario: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos específicos para formularios */
        .form-container {
            width: 300px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        .form-group button:hover {
            background-color: #555;
        }
        .mensaje-error, .mensaje-exito {
            text-align: center;
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .enlace-login {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Cine GrenMark</h1>
        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="form-container">
            <h2>Registrarse</h2>
            <?php if (!empty($mensaje)): ?>
                <p class="<?php echo (strpos($mensaje, 'exitoso') !== false) ? 'mensaje-exito' : 'mensaje-error'; ?>">
                    <?php echo $mensaje; ?>
                </p>
            <?php endif; ?>
            <form action="registro.php" method="POST">
                <div class="form-group">
                    <label for="nombre_usuario">Nombre de Usuario:</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required>
                </div>
                <div class="form-group">
                    <label for="correo">Correo Electrónico:</label>
                    <input type="email" id="correo" name="correo" required>
                </div>
                <div class="form-group">
                    <label for="contrasena">Contraseña:</label>
                    <input type="password" id="contrasena" name="contrasena" required>
                </div>
                <div class="form-group">
                    <button type="submit">Registrarse</button>
                </div>
            </form>
            <p class="enlace-login">¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión aquí</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>