<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$compras = [];

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id AS compra_id,
            c.fecha_compra,
            c.total,
            p.titulo AS pelicula_titulo,
            f.fecha AS funcion_fecha,
            f.hora AS funcion_hora,
            f.sala AS funcion_sala
        FROM compras c
        JOIN funciones f ON c.funcion_id = f.id
        JOIN peliculas p ON f.pelicula_id = p.id
        WHERE c.usuario_id = ?
        ORDER BY c.fecha_compra DESC
    ");
    $stmt->execute([$usuario_id]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada compra, obtener los detalles de los asientos
    foreach ($compras as &$compra) {
        $stmt_asientos = $pdo->prepare("
            SELECT a.fila, a.numero
            FROM detalle_compra dc
            JOIN asientos a ON dc.asiento_id = a.id
            WHERE dc.compra_id = ?
        ");
        $stmt_asientos->execute([$compra['compra_id']]);
        $compra['asientos'] = $stmt_asientos->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($compra); // Romper la referencia al último elemento
} catch (PDOException $e) {
    echo "Error al cargar el historial: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Compras</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .historial-container {
            width: 80%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .historial-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .historial-item h3 {
            margin-top: 0;
            color: #007bff;
        }
        .historial-item p {
            margin-bottom: 5px;
        }
        .historial-item strong {
            color: #555;
        }
        .historial-item .asientos {
            margin-top: 10px;
            font-size: 0.9em;
        }
        .historial-item .asientos span {
            display: inline-block;
            background-color: #e9ecef;
            padding: 3px 8px;
            margin-right: 5px;
            margin-bottom: 5px;
            border-radius: 3px;
        }
        .no-compras {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <header>
        <h1>Cine GrenMark</h1>
        <nav>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="snacks.php">Snacks</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="historial-container">
            <h2>Historial de Compras de <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h2>

            <?php if (!empty($compras)): ?>
                <?php foreach ($compras as $compra): ?>
                    <div class="historial-item">
                        <h3>Película: <?php echo htmlspecialchars($compra['pelicula_titulo']); ?></h3>
                        <p><strong>Fecha de Compra:</strong> <?php echo date('d/m/Y H:i:s', strtotime($compra['fecha_compra'])); ?></p>
                        <p><strong>Función:</strong> <?php echo date('d/m/Y', strtotime($compra['funcion_fecha'])); ?> - <?php echo date('H:i', strtotime($compra['funcion_hora'])); ?> | Sala: <?php echo htmlspecialchars($compra['funcion_sala']); ?></p>
                        <p><strong>Total Pagado:</strong> $<?php echo htmlspecialchars(number_format($compra['total'], 2)); ?></p>
                        <div class="asientos">
                            <strong>Asientos:</strong>
                            <?php if (!empty($compra['asientos'])): ?>
                                <?php foreach ($compra['asientos'] as $asiento): ?>
                                    <span><?php echo htmlspecialchars($asiento['fila'] . $asiento['numero']); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span>No hay asientos registrados para esta compra.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-compras">No tienes compras registradas en tu historial.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>