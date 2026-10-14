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
$titulo = '';
$fecha = '';
$hora = '';
$categoria = '';
$descripcion = '';
$errores = [];

// Procesar solo si el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $categoriasPermitidas = ['trabajo', 'personal', 'estudio', 'deporte'];
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
            require 'conexion.php';

            $valorHora = ($hora === '') ? null : $hora;
            $valorDescripcion = ($descripcion === '') ? null : $descripcion;

            $sql = "INSERT INTO eventos (titulo, fecha, hora, categoria, descripcion) VALUES (?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sssss', $titulo, $fecha, $valorHora, $categoria, $valorDescripcion);
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
    <title>Nuevo evento · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Nuevo evento</h1>
    <p class="card__meta">AgendaWeb</p>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <!-- action="" = se envía a sí misma -->
    <form method="post" action="">
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

      <button type="submit" class="btn-primary">Guardar</button>
    </form>

    <p class="card__link"><a href="index.php">Ver mi agenda</a></p>
</div>
</body>
</html>