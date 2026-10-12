<?php
/* ════════════════════════════════════════════════════════
   index.php · Página principal (Mi agenda)
   ════════════════════════════════════════════════════════ */

// Función de ayuda: evita ataques XSS al mostrar texto
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Verificar si viene con parámetros de confirmación
$guardado = isset($_GET['ok']);
$eliminado = isset($_GET['deleted']);

// 2. Procesar eliminación si se solicita
if (isset($_GET['eliminar'])) {
    $idEliminar = (int)$_GET['eliminar'];
    try {
        require 'conexion.php';
        $stmt = $mysqli->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();
        header('Location: index.php?deleted=1');
        exit;
    } catch (mysqli_sql_exception $e) {
        $errorBD = true;
    }
}

// 3. Consultar los eventos guardados en la base de datos
$eventos = [];
$errorBD = false;

try {
    require 'conexion.php';

    // Consultar eventos ordenados por fecha y hora
    $sql = "SELECT id, titulo, fecha, hora, categoria, descripcion FROM eventos ORDER BY fecha ASC, hora ASC";
    $resultado = $mysqli->query($sql);

    if ($resultado) {
        $eventos = $resultado->fetch_all(MYSQLI_ASSOC);
        $resultado->free();
    }
    
    $mysqli->close();
} catch (mysqli_sql_exception $e) {
    $errorBD = true;
}

// Contadores por categoría
$conteoCategorias = ['trabajo' => 0, 'personal' => 0, 'estudio' => 0, 'deporte' => 0];
foreach ($eventos as $ev) {
    $cat = strtolower($ev['categoria']);
    if ($cat === 'ocio') $cat = 'deporte';
    if (isset($conteoCategorias[$cat])) {
        $conteoCategorias[$cat]++;
    }
}

// Mapa de colores e iconos
$estilosCategorias = [
    'trabajo'  => ['bg' => '#E0F2FE', 'color' => '#0369A1', 'icono' => '💼', 'nombre' => 'Trabajo'],
    'personal' => ['bg' => '#FCE7F3', 'color' => '#BE185D', 'icono' => '👤', 'nombre' => 'Personal'],
    'estudio'  => ['bg' => '#FEF3C7', 'color' => '#B45309', 'icono' => '📚', 'nombre' => 'Estudio'],
    'deporte'  => ['bg' => '#DCFCE7', 'color' => '#15803D', 'icono' => '⚽', 'nombre' => 'Deporte'],
    'ocio'     => ['bg' => '#DCFCE7', 'color' => '#15803D', 'icono' => '⚽', 'nombre' => 'Deporte'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/estilos.css" rel="stylesheet">
    <title>Mi agenda · AgendaWeb</title>
</head>
<body>
<div class="card" style="max-width: 650px; margin: 2rem auto; padding: 2rem;">
    
    <!-- Encabezado con Botón en la esquina superior -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 2px solid #F1F5F9; padding-bottom: 1rem;">
        <div>
            <h1 class="card__title" style="margin: 0; font-size: 1.8rem;">Mi agenda</h1>
            <p class="card__meta" style="margin: 0.2rem 0 0 0;">Total: <?= count($eventos) ?> evento(s)</p>
        </div>
        <a href="registrar.php" class="btn-primary" style="text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; white-space: nowrap;">+ Nuevo evento</a>
    </div>

    <!-- Mensajes de Estado -->
    <?php if ($guardado): ?>
        <div class="alert alert--ok" role="status">&#9989; Evento guardado correctamente.</div>
    <?php endif; ?>

    <?php if ($eliminado): ?>
        <div class="alert alert--ok" role="status" style="background-color: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5;">
            🗑️ Evento eliminado de la agenda.
        </div>
    <?php endif; ?>

    <?php if ($errorBD): ?>
        <div class="alert alert--error">No se pudieron procesar los datos de la agenda.</div>
    <?php endif; ?>

    <!-- Resumen rápido de categorías -->
    <?php if (!empty($eventos)): ?>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; background: #F8FAFC; padding: 0.8rem; border-radius: 10px;">
            <span style="font-size: 0.85rem; color: #64748B; width: 100%; font-weight: 600; margin-bottom: 0.2rem;">Resumen por categoría:</span>
            <span style="font-size: 0.8rem; background: #E0F2FE; color: #0369A1; padding: 0.2rem 0.5rem; border-radius: 6px;">💼 Trabajo: <?= $conteoCategorias['trabajo'] ?></span>
            <span style="font-size: 0.8rem; background: #FCE7F3; color: #BE185D; padding: 0.2rem 0.5rem; border-radius: 6px;">👤 Personal: <?= $conteoCategorias['personal'] ?></span>
            <span style="font-size: 0.8rem; background: #FEF3C7; color: #B45309; padding: 0.2rem 0.5rem; border-radius: 6px;">📚 Estudio: <?= $conteoCategorias['estudio'] ?></span>
            <span style="font-size: 0.8rem; background: #DCFCE7; color: #15803D; padding: 0.2rem 0.5rem; border-radius: 6px;">⚽ Deporte: <?= $conteoCategorias['deporte'] ?></span>
        </div>
    <?php endif; ?>

    <!-- Lista de eventos guardados -->
    <div class="eventos-container">
        <?php if (empty($eventos)): ?>
            <div style="padding: 2.5rem 1rem; text-align: center; color: #6E617A; background: #F8FAFC; border-radius: 12px; border: 2px dashed #E2E8F0;">
                <p style="margin: 0; font-size: 1.1rem; font-weight: 500;">📅 No tienes eventos programados.</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #94A3B8;">Haz clic en el botón superior para agregar tu primer evento.</p>
            </div>
        <?php else: ?>
            <div class="eventos-list" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($eventos as $evento): 
                    $catClave = strtolower($evento['categoria']);
                    $catInfo = $estilosCategorias[$catClave] ?? ['bg' => '#E9E1F0', 'color' => '#5B21B6', 'icono' => '📌', 'nombre' => ucfirst($catClave)];
                ?>
                    <div class="evento-item" style="background: #ffffff; border: 1px solid #E2E8F0; border-radius: 12px; padding: 1.2rem; text-align: left; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.6rem;">
                            <h3 style="margin: 0; font-size: 1.15rem; color: #1E293B; font-weight: 600;"><?= e($evento['titulo']) ?></h3>
                            
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="badge" style="background: <?= $catInfo['bg'] ?>; color: <?= $catInfo['color'] ?>; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; white-space: nowrap;">
                                    <?= $catInfo['icono'] ?> <?= e($catInfo['nombre']) ?>
                                </span>
                                <!-- Botón Eliminar -->
                                <a href="index.php?eliminar=<?= $evento['id'] ?>" 
                                   onclick="return confirm('¿Seguro que deseas eliminar este evento?');" 
                                   style="color: #94A3B8; text-decoration: none; font-size: 1.1rem; padding: 0.2rem 0.4rem; border-radius: 4px;" 
                                   title="Eliminar evento">🗑️</a>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.88rem; color: #64748B; margin-bottom: 0.5rem;">
                            <span>📅 <?= e(date('d/m/Y', strtotime($evento['fecha']))) ?></span>
                            <?php if (!empty($evento['hora'])): ?>
                                <span>⏰ <?= e(date('H:i', strtotime($evento['hora']))) ?> hs</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($evento['descripcion'])): ?>
                            <p style="margin: 0.6rem 0 0 0; font-size: 0.9rem; color: #334155; background: #F8FAFC; padding: 0.6rem 0.8rem; border-radius: 8px; border-left: 3px solid #7C3AED;">
                                <?= nl2br(e($evento['descripcion'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($guardado || $eliminado): ?>
<script>
    // Oculta las alertas tras 3 segundos
    setTimeout(function() {
        const alerta = document.querySelector('.alert');
        if (alerta) {
            alerta.style.transition = 'opacity 0.5s ease';
            alerta.style.opacity = '0';
            setTimeout(function() {
                alerta.remove();
            }, 500);
        }
    }, 3000);
</script>
<?php endif; ?>
</body>
</html>