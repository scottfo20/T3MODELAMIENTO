<?php
session_start();
require_once 'includes/db.php';

// Obtener películas de la base de datos
$stmt = $pdo->query("SELECT id, titulo, imagen_url, genero, descripcion FROM peliculas");
$peliculas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cine XYZ - Cartelera</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Cine GrenMark</h1>
        <nav>
            <ul>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="historial.php">Historial de Compras</a></li>
                    <li><a href="snacks.php">Ver Snacks</a></li> <li><a href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                    <li><a href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="cartelera">
        <h2>Cartelera Actual</h2>
        <div class="peliculas-grid">
            <?php if (!empty($peliculas)): ?>
                <?php foreach ($peliculas as $pelicula): ?>
                    <div class="pelicula-item">
                        <a href="pelicula.php?id=<?php echo $pelicula['id']; ?>">
                            <img src="<?php echo htmlspecialchars($pelicula['imagen_url']); ?>" alt="<?php echo htmlspecialchars($pelicula['titulo']); ?>">
                            <h3><?php echo htmlspecialchars($pelicula['titulo']); ?></h3>
                            <p>Género: <?php echo htmlspecialchars($pelicula['genero']); ?></p>
                        </a>
                        <a href="pelicula.php?id=<?php echo $pelicula['id']; ?>" class="boton-comprar-boletos">Comprar Boletos</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay películas en cartelera por el momento.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>