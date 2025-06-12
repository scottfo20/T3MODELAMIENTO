<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$mensaje = '';
$boleta_generada = false;
$datos_boleta = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $funcion_id = $_POST['funcion_id'] ?? null;
    $asientos_ids_json = $_POST['asientos_ids'] ?? '[]';
    $asientos_ids = json_decode($asientos_ids_json, true); // Decodificar el JSON a un array de PHP

    if (empty($funcion_id) || empty($asientos_ids)) {
        $mensaje = "Error: No se proporcionaron datos de función o asientos.";
    } else {
        try {
            $pdo->beginTransaction(); // Iniciar transacción

            // 1. Verificar y actualizar estado de los asientos
            $asientos_actualizados = [];
            foreach ($asientos_ids as $asiento_id) {
                // Verificar que el asiento esté disponible antes de actualizar
                $stmt_check = $pdo->prepare("SELECT estado FROM asientos WHERE id = ? AND funcion_id = ? FOR UPDATE"); // Bloquear fila
                $stmt_check->execute([$asiento_id, $funcion_id]);
                $asiento_estado = $stmt_check->fetchColumn();

                if ($asiento_estado !== 'disponible') {
                    throw new Exception("El asiento ID $asiento_id ya no está disponible.");
                }

                $stmt_update = $pdo->prepare("UPDATE asientos SET estado = 'ocupado' WHERE id = ?");
                $stmt_update->execute([$asiento_id]);
                $asientos_actualizados[] = $asiento_id;
            }

            // 2. Calcular el total de la compra
            $stmt_precio_asiento = $pdo->prepare("SELECT precio FROM funciones WHERE id = ?");
            $stmt_precio_asiento->execute([$funcion_id]);
            $precio_unitario = $stmt_precio_asiento->fetchColumn();
            $total_compra = $precio_unitario * count($asientos_ids);

            // 3. Registrar la compra principal
            $stmt_compra = $pdo->prepare("INSERT INTO compras (usuario_id, funcion_id, total) VALUES (?, ?, ?)");
            $stmt_compra->execute([$_SESSION['usuario_id'], $funcion_id, $total_compra]);
            $compra_id = $pdo->lastInsertId();

            // 4. Registrar el detalle de los asientos comprados
            foreach ($asientos_actualizados as $asiento_id) {
                $stmt_detalle = $pdo->prepare("INSERT INTO detalle_compra (compra_id, asiento_id) VALUES (?, ?)");
                $stmt_detalle->execute([$compra_id, $asiento_id]);
            }

            $pdo->commit(); // Confirmar la transacción

            $mensaje = "¡Tu compra ha sido procesada con éxito!";
            $boleta_generada = true;

            // Obtener datos para la boleta
            $stmt_datos_boleta = $pdo->prepare("
                SELECT
                    p.titulo AS pelicula_titulo,
                    p.genero AS pelicula_genero,
                    p.descripcion AS pelicula_descripcion,
                    f.fecha AS funcion_fecha,
                    f.hora AS funcion_hora,
                    f.sala AS funcion_sala,
                    f.precio AS precio_unitario,
                    u.nombre_usuario AS usuario_nombre,
                    c.fecha_compra AS fecha_compra,
                    c.total AS total_pagado
                FROM compras c
                JOIN funciones f ON c.funcion_id = f.id
                JOIN peliculas p ON f.pelicula_id = p.id
                JOIN usuarios u ON c.usuario_id = u.id
                WHERE c.id = ?
            ");
            $stmt_datos_boleta->execute([$compra_id]);
            $datos_boleta = $stmt_datos_boleta->fetch(PDO::FETCH_ASSOC);

            // Obtener detalles de los asientos para la boleta
            $stmt_asientos_boleta = $pdo->prepare("
                SELECT a.fila, a.numero
                FROM detalle_compra dc
                JOIN asientos a ON dc.asiento_id = a.id
                WHERE dc.compra_id = ?
            ");
            $stmt_asientos_boleta->execute([$compra_id]);
            $asientos_boleta = $stmt_asientos_boleta->fetchAll(PDO::FETCH_ASSOC);
            $datos_boleta['asientos'] = $asientos_boleta;

        } catch (Exception $e) {
            $pdo->rollBack(); // Revertir la transacción en caso de error
            $mensaje = "Error al procesar la compra: " . $e->getMessage();
        }
    }
} else {
    header("Location: index.php"); // Si no es POST, redirige
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Compra y Boleta</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmacion-container {
            width: 80%;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .boleta {
            border: 2px solid #007bff; /* Borde más distintivo */
            padding: 25px;
            margin-top: 30px;
            border-radius: 10px;
            background-color: #e6f7ff; /* Fondo suave para la boleta */
        }
        .boleta h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #007bff;
            font-size: 1.8em;
            border-bottom: 1px dashed #007bff;
            padding-bottom: 10px;
        }
        .boleta p {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        .boleta strong {
            color: #333;
        }
        .asientos-comprados {
            margin-top: 20px;
            border-top: 1px dashed #007bff;
            padding-top: 20px;
        }
        .asientos-comprados span {
            display: inline-block;
            background-color: #cceeff;
            padding: 8px 12px;
            margin: 5px;
            border-radius: 5px;
            font-weight: bold;
            border: 1px solid #99ddff;
        }
        .total-boleta {
            text-align: right;
            font-size: 1.8em;
            font-weight: bold;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #007bff;
            color: #28a745; /* Color para el total */
        }
        .mensaje {
            text-align: center;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje.exito {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .acciones-finales {
            text-align: center;
            margin-top: 30px;
        }
        .acciones-finales a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .acciones-finales a:hover {
            background-color: #0056b3;
        }
        .acciones-finales .historial-btn {
            background-color: #6c757d;
        }
        .acciones-finales .historial-btn:hover {
            background-color: #5a6268;
        }
        .print-button {
            background-color: #ffc107; /* Amarillo */
            color: #333;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
        }
        .print-button:hover {
            background-color: #e0a800;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .boleta, .boleta * {
                visibility: visible;
            }
            .boleta {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                border: none;
                box-shadow: none;
                background-color: #fff;
            }
            .acciones-finales, header, footer, .mensaje {
                display: none;
            }
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
        <div class="confirmacion-container">
            <h2>Detalles de tu Compra</h2>
            <?php if (!empty($mensaje)): ?>
                <p class="mensaje <?php echo ($boleta_generada) ? 'exito' : 'error'; ?>">
                    <?php echo $mensaje; ?>
                </p>
            <?php endif; ?>

            <?php if ($boleta_generada && !empty($datos_boleta)): ?>
                <div class="boleta" id="boleta-imprimir">
                    <h3>Boleta de Compra</h3>
                    <p><strong>Número de Compra:</strong> <?php echo htmlspecialchars($compra_id); ?></p>
                    <p><strong>Comprador:</strong> <?php echo htmlspecialchars($datos_boleta['usuario_nombre']); ?></p>
                    <p><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y H:i:s', strtotime($datos_boleta['fecha_compra'])); ?></p>
                    <hr>
                    <p><strong>Película:</strong> <?php echo htmlspecialchars($datos_boleta['pelicula_titulo']); ?></p>
                    <p><strong>Género:</strong> <?php echo htmlspecialchars($datos_boleta['pelicula_genero']); ?></p>
                    <p><strong>Duración:</strong> <?php echo htmlspecialchars($datos_boleta['pelicula_duracion'] ?? 'N/A'); ?> minutos</p>
                    <hr>
                    <h4>Información de la Función:</h4>
                    <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($datos_boleta['funcion_fecha'])); ?></p>
                    <p><strong>Hora:</strong> <?php echo date('H:i', strtotime($datos_boleta['funcion_hora'])); ?></p>
                    <p><strong>Sala:</strong> <?php echo htmlspecialchars($datos_boleta['funcion_sala']); ?></p>
                    <p><strong>Precio por Asiento:</strong> $<?php echo htmlspecialchars(number_format($datos_boleta['precio_unitario'], 2)); ?></p>

                    <div class="asientos-comprados">
                        <p><strong>Asientos Comprados:</strong></p>
                        <?php if (!empty($datos_boleta['asientos'])): ?>
                            <?php foreach ($datos_boleta['asientos'] as $asiento): ?>
                                <span><?php echo htmlspecialchars($asiento['fila'] . $asiento['numero']); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span>No hay asientos registrados para esta compra.</span>
                        <?php endif; ?>
                    </div>

                    <div class="total-boleta">
                        Total Pagado: $<?php echo htmlspecialchars(number_format($datos_boleta['total_pagado'], 2)); ?>
                    </div>
                </div>
                <div class="acciones-finales">
                    <button class="print-button" onclick="window.print()">Imprimir Boleta</button>
                    <a href="index.php">Volver a la Cartelera</a>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <a href="historial.php" class="historial-btn">Ver Historial de Compras</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Cine XYZ. Todos los derechos reservados.</p>
    </footer>
</body>
</html>