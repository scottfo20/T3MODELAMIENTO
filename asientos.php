<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['funcion_id']) || !is_numeric($_GET['funcion_id'])) {
    header("Location: index.php");
    exit();
}

$funcion_id = $_GET['funcion_id'];

// Obtener detalles de la función
$stmt_funcion = $pdo->prepare("
    SELECT f.id, f.fecha, f.hora, f.sala, f.precio, p.titulo, p.imagen_url
    FROM funciones f
    JOIN peliculas p ON f.pelicula_id = p.id
    WHERE f.id = ?
");
$stmt_funcion->execute([$funcion_id]);
$funcion = $stmt_funcion->fetch(PDO::FETCH_ASSOC);

if (!$funcion) {
    echo "Función no encontrada.";
    exit();
}

// Obtener estado de los asientos para esta función
$stmt_asientos = $pdo->prepare("SELECT id, fila, numero, estado FROM asientos WHERE funcion_id = ? ORDER BY fila, numero");
$stmt_asientos->execute([$funcion_id]);
$asientos = $stmt_asientos->fetchAll(PDO::FETCH_ASSOC);

// Simulación de un mapa de sala (podrías generar esto dinámicamente o de forma fija)
// Para el ejemplo, asumimos 5 filas (A-E) y 10 asientos por fila
$filas = ['A', 'B', 'C', 'D', 'E'];
$num_asientos_por_fila = 10;

// Convertir los asientos de la DB a un formato fácil de usar
$asientos_map = [];
foreach ($asientos as $asiento) {
    $asientos_map[$asiento['fila']][$asiento['numero']] = [
        'id' => $asiento['id'],
        'estado' => $asiento['estado']
    ];
}

// Si la tabla de asientos está vacía para esta función, generarlos (una sola vez)
if (empty($asientos)) {
    // Esto es un ejemplo, en un sistema real, los asientos se deberían generar al crear la función
    foreach ($filas as $fila) {
        for ($i = 1; $i <= $num_asientos_por_fila; $i++) {
            $stmt_insert_asiento = $pdo->prepare("INSERT INTO asientos (funcion_id, fila, numero, estado) VALUES (?, ?, ?, 'disponible')");
            $stmt_insert_asiento->execute([$funcion_id, $fila, $i]);
            // Re-obtener los asientos después de insertarlos
            $stmt_asientos->execute([$funcion_id]);
            $asientos = $stmt_asientos->fetchAll(PDO::FETCH_ASSOC);
            foreach ($asientos as $asiento) {
                $asientos_map[$asiento['fila']][$asiento['numero']] = [
                    'id' => $asiento['id'],
                    'estado' => $asiento['estado']
                ];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Asientos - <?php echo htmlspecialchars($funcion['titulo']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .asientos-container {
            margin-top: 30px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .asientos-grid {
            display: grid;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .fila {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .fila-label {
            font-weight: bold;
            width: 20px; /* Para alinear etiquetas de fila */
            text-align: right;
        }
        .asiento {
            width: 40px;
            height: 40px;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 0.8em;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .asiento.disponible {
            background-color: #d4edda; /* Verde claro */
            border-color: #28a745;
        }
        .asiento.ocupado {
            background-color: #f8d7da; /* Rojo claro */
            border-color: #dc3545;
            cursor: not-allowed;
        }
        .asiento.reservado {
            background-color: #fff3cd; /* Amarillo claro */
            border-color: #ffc107;
            cursor: not-allowed;
        }
        .asiento.seleccionado {
            background-color: #007bff; /* Azul */
            color: #fff;
            border-color: #007bff;
        }
        .asiento.ocupado::after, .asiento.reservado::after {
            content: 'X'; /* Marcar los asientos no disponibles */
            font-weight: bold;
            color: rgba(0,0,0,0.5);
        }
        .screen {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        .total-compra {
            margin-top: 20px;
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
        }
        .acciones {
            text-align: center;
            margin-top: 30px;
        }
        .acciones button {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin: 0 10px;
        }
        .acciones button:hover {
            background-color: #218838;
        }
        .acciones .cancelar-btn {
            background-color: #6c757d;
        }
        .acciones .cancelar-btn:hover {
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
        <div class="asientos-container">
            <h2>Seleccionar Asientos para: <?php echo htmlspecialchars($funcion['titulo']); ?></h2>
            <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($funcion['fecha'])); ?> |
               <strong>Hora:</strong> <?php echo date('H:i', strtotime($funcion['hora'])); ?> |
               <strong>Sala:</strong> <?php echo htmlspecialchars($funcion['sala']); ?> |
               <strong>Precio por Asiento:</strong> $<?php echo htmlspecialchars(number_format($funcion['precio'], 2)); ?>
            </p>

            <div class="screen">
                Pantalla
            </div>

            <div class="asientos-grid">
                <?php foreach ($filas as $fila_letra): ?>
                    <div class="fila">
                        <span class="fila-label"><?php echo $fila_letra; ?></span>
                        <?php for ($i = 1; $i <= $num_asientos_por_fila; $i++): ?>
                            <?php
                                $asiento_info = $asientos_map[$fila_letra][$i] ?? ['id' => null, 'estado' => 'disponible'];
                                $estado_clase = $asiento_info['estado'];
                                $data_asiento_id = $asiento_info['id'];
                                $disabled = ($estado_clase != 'disponible') ? 'disabled' : '';
                            ?>
                            <div
                                class="asiento <?php echo $estado_clase; ?>"
                                data-asiento-id="<?php echo $data_asiento_id; ?>"
                                data-precio="<?php echo $funcion['precio']; ?>"
                                <?php echo $disabled; ?>
                            >
                                <?php echo $i; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-compra">
                Asientos Seleccionados: <span id="asientos-seleccionados-count">0</span> |
                Total: $<span id="total-precio">0.00</span>
            </div>

            <form action="confirmar_compra.php" method="POST" id="form-compra">
                <input type="hidden" name="funcion_id" value="<?php echo $funcion_id; ?>">
                <input type="hidden" name="asientos_ids" id="asientos_ids_input">
                <div class="acciones">
                    <button type="submit" id="btn-comprar" disabled>Confirmar Compra</button>
                    <a href="pelicula.php?id=<?php echo $funcion['pelicula_id']; ?>" class="cancelar-btn">Regresar a la Película</a>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>