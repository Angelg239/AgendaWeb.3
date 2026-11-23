<?php
/* ════════════════════════════════════════════════════════
    registrar.php · AgendaWeb
   ════════════════════════════════════════════════════════ */

// 1. Incluir el guardia de seguridad (protege la página y define $usuarioId y $usuarioNombre)
require __DIR__ . '/partes/sesion.php';

// Función de ayuda: limpia un texto antes de mostrarlo (evita XSS)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

require 'conexion.php';

$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY id')->fetch_all(MYSQLI_ASSOC);

// Inicialización de variables por defecto (Nuevo evento)
$idEvento = null;
$titulo = '';
$fecha = '';
$hora = '';
$categoriaId = 0;
$descripcion = '';
$errores = [];

// 1. Si recibimos un ID por GET, cargamos los datos actuales de la BD asegurando que pertenezcan al usuario
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $idEvento = (int)$_GET['id'];

    try {
        $stmt = $mysqli->prepare("SELECT titulo, fecha, hora, categoria_id, descripcion FROM eventos WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param('ii', $idEvento, $usuarioId);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($evento = $resultado->fetch_assoc()) {
            $titulo = $evento['titulo'];
            $fecha = $evento['fecha'];
            $hora = $evento['hora'] ? substr($evento['hora'], 0, 5) : '';
            $categoriaId = (int) $evento['categoria_id'];
            $descripcion = $evento['descripcion'] ?? '';
        } else {
            // Si el ID no existe o no es de este usuario, redirigir al inicio
            header('Location: index.php');
            exit;
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $errores['general'] = 'No se pudo cargar el evento para editar.';
    }
}

// 2. Procesar solo si el método es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && $_POST['id'] !== '') {
        $idEvento = (int)$_POST['id'];
    }

    // Lectura segura y limpieza de cada campo
    $titulo       = trim($_POST['titulo']       ?? '');
    $fecha        = trim($_POST['fecha']        ?? '');
    $hora         = trim($_POST['hora']         ?? '');
    $categoriaId  = filter_var($_POST['categoria_id'] ?? '', FILTER_VALIDATE_INT);
    $categoriaId  = ($categoriaId === false) ? 0 : $categoriaId;
    $descripcion  = trim($_POST['descripcion'] ?? '');

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
    $categoriasPermitidas = array_map('intval', array_column($categorias, 'id'));
    if (!in_array($categoriaId, $categoriasPermitidas, true)) {
        $errores['categoria'] = 'Debes seleccionar una categoría.';
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
                // Si hay ID, actualizamos el registro existente (UPDATE) validando usuario_id
                $sql = "UPDATE eventos SET titulo = ?, fecha = ?, hora = ?, categoria_id = ?, descripcion = ? WHERE id = ? AND usuario_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('sssisii', $titulo, $fecha, $valorHora, $categoriaId, $valorDescripcion, $idEvento, $usuarioId);
            } else {
                // Si no hay ID, creamos uno nuevo (INSERT) asociándolo al $usuarioId actual
                $sql = "INSERT INTO eventos (usuario_id, titulo, fecha, hora, categoria_id, descripcion) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('isssis', $usuarioId, $titulo, $fecha, $valorHora, $categoriaId, $valorDescripcion);
            }

            $stmt->execute();
            $stmt->close();

            header('Location: index.php?ok=1');
            exit;
        } catch (mysqli_sql_exception $e) {
            // AQUí ESTÁ EL CAMBIO: Muestra el error exacto de MySQL en pantalla para depurar
            $errores['general'] = 'Error de base de datos: ' . $e->getMessage();
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
<div class="card event-form-card">
    <header class="event-form-header">
      <span class="event-form-header__icon" aria-hidden="true">📅</span>
      <div>
        <p class="event-form-header__eyebrow"><?= $idEvento ? 'Editar agenda' : 'Organiza tu tiempo' ?></p>
        <h1 class="card__title"><?= $idEvento ? 'Editar evento' : 'Nuevo evento' ?></h1>
        <p class="card__meta">Hola, <?= e($usuarioNombre) ?>. Completa los detalles de tu evento.</p>
      </div>
    </header>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <!-- action="" = se envía a sí misma -->
    <form class="event-form" method="post" action="">
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
          placeholder="Ej. Entregar proyecto"
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
          <?php foreach ($categorias as $categoria): ?>
            <?php $idCategoria = (int) $categoria['id']; ?>
            <option value="<?= $idCategoria ?>" <?= $categoriaId === $idCategoria ? 'selected' : '' ?>><?= e($categoria['nombre']) ?></option>
          <?php endforeach; ?>
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
          placeholder="Agrega notas o detalles que quieras recordar..."
          maxlength="500"
          rows="4"
        ><?= e($descripcion) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
          <p class="form-error"><?= e($errores['descripcion']) ?></p>
        <?php endif; ?>
      </div>

      <div class="event-form-actions">
        <a href="index.php" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary"><?= $idEvento ? 'Guardar cambios' : 'Guardar evento' ?></button>
      </div>
    </form>
</div>
</body>
</html>