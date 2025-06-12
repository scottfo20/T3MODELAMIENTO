<?php
session_start();
require_once 'includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php"); // Redirige si no hay ID de película válido
    exit();
}

$pelicula_id = $_GET['id'];

// Obtener información de la película
$stmt_pelicula = $pdo->prepare("SELECT * FROM peliculas WHERE id = ?");
$stmt_pelicula->execute([$pelicula_id]);
$pelicula = $stmt_pelicula->fetch(PDO::FETCH_ASSOC);

if (!$pelicula) {
    echo "Película no encontrada.";
    exit();
}

// Obtener funciones disponibles para esta película
$stmt_funciones = $pdo->prepare("SELECT id, fecha, hora, sala, precio FROM funciones WHERE pelicula_id = ? ORDER BY fecha ASC, hora ASC");
$stmt_funciones->execute([$pelicula_id]);
$funciones = $stmt_funciones->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pelicula['titulo']); ?> - Cine XYZ</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos específicos para la página de película */
        .pelicula-detalle {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            align-items: flex-start;
        }
        .pelicula-detalle img {
            max-width: 300px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .pelicula-info {
            flex-grow: 1;
        }
        .pelicula-info h2 {
            margin-top: 0;
            color: #333;
        }
        .pelicula-info p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .funciones-disponibles {
            margin-top: 30px;
        }
        .funciones-disponibles h3 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .funcion-item {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .funcion-item .details {
            flex-grow: 1;
        }
        .funcion-item button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .funcion-item button:hover {
            background-color: #0056b3;
        }
        .regresar-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #6c757d;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .regresar-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <header>
        <h1>Cine GrenMark</h1>
        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="historial.php">Historial de Compras</a></li>
                    <li><a href="snacks.php">Snacks</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                    <li><a href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="pelicula-detalle">
            <img src="<?php echo htmlspecialchars($pelicula['imagen_url']); ?>" alt="<?php echo htmlspecialchars($pelicula['titulo']); ?>">
            <div class="pelicula-info">
                <h2><?php echo htmlspecialchars($pelicula['titulo']); ?></h2>
                <p><strong>Género:</strong> <?php echo htmlspecialchars($pelicula['genero']); ?></p>
                <p><strong>Duración:</strong> <?php echo htmlspecialchars($pelicula['duracion']); ?> minutos</p>
                <p><strong>Sinopsis:</strong> <?php echo nl2br(htmlspecialchars($pelicula['descripcion'])); ?></p>
            </div>
        </div>

        <div class="funciones-disponibles">
            <h3>Funciones Disponibles</h3>
            <?php if (!empty($funciones)): ?>
                <?php foreach ($funciones as $funcion): ?>
                    <div class="funcion-item">
                        <div class="details">
                            <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($funcion['fecha'])); ?></p>
                            <p><strong>Hora:</strong> <?php echo date('H:i', strtotime($funcion['hora'])); ?></p>
                            <p><strong>Sala:</strong> <?php echo htmlspecialchars($funcion['sala']); ?></p>
                            <p><strong>Precio:</strong> $<?php echo htmlspecialchars(number_format($funcion['precio'], 2)); ?></p>
                        </div>
                        <a href="asientos.php?funcion_id=<?php echo $funcion['id']; ?>" class="button">Seleccionar Asientos</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay funciones programadas para esta película por el momento.</p>
            <?php endif; ?>
        </div>

        <a href="index.php" class="regresar-btn">Regresar al Inicio</a>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>