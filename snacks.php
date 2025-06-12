<?php
session_start();
require_once 'includes/db.php';

$snacks = [];
try {
    $stmt = $pdo->query("SELECT id, nombre, descripcion, precio, imagen_url FROM snacks ORDER BY nombre");
    $snacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error al cargar los snacks: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú de Snacks - Cine XYZ</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .snacks-container {
            width: 80%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .snacks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
            justify-content: center;
        }
        .snack-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background-color: #f9f9f9;
        }
        .snack-item img {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .snack-item h3 {
            margin-top: 0;
            color: #333;
        }
        .snack-item p {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .snack-item .precio {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }
        .no-snacks {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
        }
        .snacks-message {
            margin-top: 25px;
            padding: 15px;
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
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
                    <li><a href="snacks.php">Ver Snacks</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                    <li><a href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <div class="snacks-container">
            <h2>Menú de Snacks</h2>
            <p class="snacks-message">
                Para comprar tus snacks, por favor, acércate a la caja y recógelos allí. ¡Gracias!
            </p>
            <?php if (!empty($snacks)): ?>
                <div class="snacks-grid">
                    <?php foreach ($snacks as $snack): ?>
                        <div class="snack-item">
                            <img src="<?php echo htmlspecialchars($snack['imagen_url']); ?>" alt="<?php echo htmlspecialchars($snack['nombre']); ?>">
                            <h3><?php echo htmlspecialchars($snack['nombre']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($snack['descripcion'])); ?></p>
                            <p class="precio">$<?php echo htmlspecialchars(number_format($snack['precio'], 2)); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-snacks">No hay snacks disponibles en el menú por el momento.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>