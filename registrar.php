<?php
/* ════════════════════════════════════════════════════════
   registrar.php · AgendaWeb
   ════════════════════════════════════════════════════════ */

// Función de ayuda: limpia un texto antes de mostrarlo (evita XSS)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

// Inicialización de variables para el formulario
$idEvento = null;
$titulo = '';
$fecha = '';
$hora = '';
$categoria = '';
$descripcion = '';
$errores = [];

require 'conexion.php';

// 1. SI RECIBIMOS UN ID POR GET, ESTAMOS EDITANDO: Cargar datos actuales de la BD
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $idEvento = (int)$_GET['id'];

    // Solo cargamos los datos si el formulario NO se acaba de enviar por POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        try {
            $stmt = $mysqli->prepare("SELECT titulo, fecha, hora, categoria, descripcion FROM eventos WHERE id = ?");
            $stmt->bind_param('i', $idEvento);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($eventoActual = $resultado->fetch_assoc()) {
                $titulo = $eventoActual['titulo'];
                $fecha = $eventoActual['fecha'];
                $hora = $eventoActual['hora'] ? substr($eventoActual['hora'], 0, 5) : '';
                $categoria = $eventoActual['categoria'];
                $descripcion = $eventoActual['descripcion'] ?? '';
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
}

// 2. Procesar solo si el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si viene un ID oculto en el formulario, lo recuperamos para saber que es edición
    if (isset($_POST['id']) && $_POST['id'] !== '') {
        $idEvento = (int)$_POST['id'];
    }

    // Lectura segura y limpieza de cada campo
    $titulo      = trim($_POST['titulo']      ?? '');
    $fecha       = trim($_POST['fecha']       ?? '');
    $hora        = trim($_POST['hora']        ?? '');
    $categoria   = trim($_POST['categoria']   ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // 1. Validar título
    if ($titulo === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($titulo) > 120) {
        $errores['titulo'] = 'El título no puede superar los 120 caracteres.';
    }

    // 2. Validar fecha
    if ($fecha === '') {
        $errores['fecha'] = 'La fecha es obligatoria.';
    } else {
        $objetoFecha = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$objetoFecha || $objetoFecha->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'Introduce una fecha válida en formato AAAA-MM-DD.';
        }
    }

    // 3. Validar hora
    if ($hora !== '') {
        $objetoHora = DateTime::createFromFormat('H:i', $hora);
        if (!$objetoHora || $objetoHora->format('H:i') !== $hora) {
            $errores['hora'] = 'Introduce una hora válida en formato HH:MM.';
        }
    }

    // 4. Validar categoría
    $categoriasPermitidas = ['trabajo', 'personal', 'estudio', 'deporte', 'ocio'];
    if ($categoria === '') {
        $errores['categoria'] = 'Debes seleccionar una categoría.';
    } elseif (!in_array($categoria, $categoriasPermitidas, true)) {
        $errores['categoria'] = 'La categoría seleccionada no es válida.';
    }

    // 5. Validar descripción
    if ($descripcion !== '' && mb_strlen($descripcion) > 500) {
        $errores['descripcion'] = 'La descripción no puede superar los 500 caracteres.';
    }

    // Guardar en MySQL si no hay errores
    if (empty($errores)) {
        try {
            $valorHora = ($hora === '') ? null : $hora;
            $valorDescripcion = ($descripcion === '') ? null : $descripcion;

            if ($idEvento) {
                // Si hay ID, actualizamos el registro existente (UPDATE)
                $sql = "UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria = ?, descripcion = ? WHERE id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('sssssi', $titulo, $fecha, $valorHora, $categoria, $valorDescripcion, $idEvento);
            } else {
                // Si no hay ID, creamos uno nuevo (INSERT)
                $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria, descripcion) VALUES (?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('sssss', $titulo, $fecha, $valorHora, $categoria, $valorDescripcion);
            }

            $stmt->execute();
            $stmt->close();
            $mysqli->close();

            header('Location: index.php?ok=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            $errores['general'] = 'No se pudo guardar.';
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
    <title><?= $idEvento ? 'Editar evento' : 'Nuevo evento' ?> · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title"><?= $idEvento ? 'Editar evento' : 'Nuevo evento' ?></h1>
    <p class="card__meta">AgendaWeb</p>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <!-- action="" = se envía a sí misma -->
    <form method="post" action="">
      <!-- Campo oculto indispensable para conservar el ID al enviar el POST en modo edición -->
      <?php if ($idEvento): ?>
          <input type="hidden" name="id" value="<?= $idEvento ?>">
      <?php endif; ?>

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
        <label class="form-label" for="categoria">Categoría</label>
        <select 
          name="categoria" 
          id="categoria" 
          class="form-select<?= isset($errores['categoria']) ? ' is-invalid' : '' ?>" 
          required
        >
          <option value="">— Elige una —</option>
          <option value="trabajo" <?= $categoria === 'trabajo' ? 'selected' : '' ?>>Trabajo</option>
          <option value="personal" <?= $categoria === 'personal' ? 'selected' : '' ?>>Personal</option>
          <option value="estudio" <?= $categoria === 'estudio' ? 'selected' : '' ?>>Estudio</option>
          <option value="deporte" <?= $categoria === 'deporte' ? 'selected' : '' ?>>Deporte</option>
          <option value="ocio" <?= $categoria === 'ocio' ? 'selected' : '' ?>>Ocio</option>
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

      <button type="submit" class="btn-primary"><?= $idEvento ? 'Actualizar evento' : 'Guardar' ?></button>
    </form>

    <p class="card__link"><a href="index.php">Ver mi agenda</a></p>
</div>
</body>
</html>