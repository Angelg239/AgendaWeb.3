<?php
/* ════════════════════════════════════════════════════════
   editar.php · AgendaWeb
   ════════════════════════════════════════════════════════ */

// Función de ayuda: limpia un texto antes de mostrarlo (evita XSS)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

require 'conexion.php';

// Inicialización de variables
$id = null;
$titulo = '';
$fecha = '';
$hora = '';
$categoriaId = 0;    
$descripcion = '';
$errores = [];

// 1. Validar y recuperar el ID por GET al cargar la página
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = (int)$_GET['id'];

    // Solo cargamos de la BD si el formulario NO se acaba de enviar por POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        try {
            $stmt = $mysqli->prepare("SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($evento = $resultado->fetch_assoc()) {
                $titulo = $evento['titulo'];
                $fecha = $evento['fecha'];
                $hora = $evento['hora'] ? substr($evento['hora'], 0, 5) : ''; // '10:30:00' → '10:30'
                $categoriaId = (int)$evento['categoria_id'];
                $descripcion = $evento['descripcion'] ?? '';
            } else {
                // Si el ID no existe en la base de datos, redirigir al inicio
                header('Location: index.php');
                exit;
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errores['general'] = 'No se pudo cargar el evento para editar.';
        }
    }
} else {
    // Si no viene un ID válido por GET, redirigimos
    header('Location: index.php');
    exit;
}

// 2. Procesar la actualización cuando se envía el formulario por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recuperar el ID del campo oculto
    if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
        $id = (int)$_POST['id'];
    }

    // Lectura segura y limpieza de cada campo
    $titulo      = trim($_POST['titulo']       ?? '');
    $fecha       = trim($_POST['fecha']        ?? '');
    $hora        = trim($_POST['hora']         ?? '');
    $categoriaId = filter_var($_POST['categoria_id'] ?? 0, FILTER_VALIDATE_INT) ? (int)$_POST['categoria_id'] : 0;
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede superar los 120 caracteres.';
    }

    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $objetoFecha = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$objetoFecha || $objetoFecha->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'Introduce una fecha válida en formato AAAA-MM-DD.';
        }
    }

    if ($hora !== '') {
        $objetoHora = DateTime::createFromFormat('H:i', $hora);
        if (!$objetoHora || $objetoHora->format('H:i') !== $hora) {
            $errores['hora'] = 'Introduce una hora válida en formato HH:MM.';
        }
    }

    if ($categoriaId <= 0) {
        $errores['categoria'] = 'Debes seleccionar una categoría válida.';
    }

    if ($descripcion !== '' && mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar los 500 caracteres.';
    }

    // Guardar los cambios en MySQL si no hay errores
    if (empty($errores)) {
        try {
            $horaBD = ($hora === '') ? null : $hora;
            $descBD = ($descripcion === '') ? null : $descripcion;

            $sql = 'UPDATE eventos
                        SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ?
                      WHERE id = ?';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssisi', $titulo, $fecha, $horaBD, $categoriaId, $descBD, $id);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();

            header('Location: index.php?ok=editado');
            exit;
        } catch (mysqli_sql_exception $e) {
            $errores['general'] = 'No se pudo actualizar el evento.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/estilos.css" rel="stylesheet">
    <title>Editar evento · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Editar evento</h1>
    <p class="card__meta">AgendaWeb</p>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <form class="event-form" method="post" action="">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="form-group">
            <label class="form-label" for="titulo">Título</label>
            <input 
              type="text" 
              name="titulo" 
              id="titulo" 
              class="form-input<?= isset($errores['titulo']) ? ' is-invalid' : '' ?>" 
              value="<?= e($titulo) ?>" 
              required 
              maxlength="120"
            >
            <?php if (isset($errores['titulo'])): ?>
              <p class="form-error"><?= e($errores['titulo']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input 
                  type="date" 
                  name="fecha" 
                  id="fecha" 
                  class="form-input<?= isset($errores['fecha']) ? ' is-invalid' : '' ?>" 
                  value="<?= e($fecha) ?>" 
                  required
                >
                <?php if (isset($errores['fecha'])): ?>
                  <p class="form-error"><?= e($errores['fecha']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="hora">Hora (opcional)</label>
                <input 
                  type="time" 
                  name="hora" 
                  id="hora" 
                  class="form-input<?= isset($errores['hora']) ? ' is-invalid' : '' ?>" 
                  value="<?= e($hora) ?>"
                >
                <?php if (isset($errores['hora'])): ?>
                  <p class="form-error"><?= e($errores['hora']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select 
              name="categoria_id" 
              id="categoria_id" 
              class="form-select<?= isset($errores['categoria']) ? ' is-invalid' : '' ?>" 
              required
            >
              <option value="">— Elige una —</option>
              <option value="1" <?= $categoriaId === 1 ? 'selected' : '' ?>>Trabajo</option>
              <option value="2" <?= $categoriaId === 2 ? 'selected' : '' ?>>Personal</option>
              <option value="3" <?= $categoriaId === 3 ? 'selected' : '' ?>>Estudio</option>
              <option value="4" <?= $categoriaId === 4 ? 'selected' : '' ?>>Deporte</option>
              <option value="5" <?= $categoriaId === 5 ? 'selected' : '' ?>>Ocio</option>
            </select>
            <?php if (isset($errores['categoria'])): ?>
              <p class="form-error"><?= e($errores['categoria']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción (opcional)</label>
            <textarea 
              name="descripcion" 
              id="descripcion" 
              class="form-textarea<?= isset($errores['descripcion']) ? ' is-invalid' : '' ?>" 
              maxlength="500"
            ><?= e($descripcion) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?>
              <p class="form-error"><?= e($errores['descripcion']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-primary">Guardar cambios</button>
        <a href="index.php" class="btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>