<?php
/* ════════════════════════════════════════════════════════
    registro.php · AgendaWeb
    ════════════════════════════════════════════════════════ */

// Función de ayuda: limpia un texto antes de mostrarlo (evita XSS)
function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

session_start();
if (isset($_SESSION['uid'])) {          // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}

require __DIR__ . '/conexion.php';

$nombre = '';
$correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));   // Ana@x.com = ana@x.com
    $clave  = $_POST['clave']  ?? '';                           // la contraseña NO se recorta
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        $hash = password_hash($clave, PASSWORD_DEFAULT);   // se guarda el HASH, nunca la clave
        try {
            $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $nombre, $correo, $hash);
            $stmt->execute();
            $stmt->close();
            $mysqli->close();
            header('Location: login.php?registrado=1');
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {               // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
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
    <link href="css/estilos.css" rel="stylesheet">
    <title>Crear cuenta · AgendaWeb</title>
</head>
<body class="auth-page">
<main class="card auth-card">
    <header class="auth-header">
        <div class="auth-brand">
            <span class="auth-brand__icon" aria-hidden="true">📅</span>
            <span>AgendaWeb</span>
        </div>
        <p class="auth-header__eyebrow">Empieza hoy</p>
        <h1 class="card__title">Crear cuenta</h1>
        <p class="card__meta">Regístrate para empezar a organizar tus eventos.</p>
    </header>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error" role="alert">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <!-- action="" = se envía a sí misma -->
    <form class="auth-form" method="post" action="">
      <div class="form-group">
        <label class="form-label" for="nombre">Nombre</label>
        <input 
          type="text" 
          name="nombre" 
          id="nombre" 
          class="form-input<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>" 
          value="<?= e($nombre) ?>" 
          placeholder="Tu nombre"
          autocomplete="name"
          aria-invalid="<?= isset($errores['nombre']) ? 'true' : 'false' ?>"
          <?php if (isset($errores['nombre'])): ?>aria-describedby="error-nombre"<?php endif; ?>
          required 
          maxlength="80"
        >
        <?php if (isset($errores['nombre'])): ?>
          <p class="form-error" id="error-nombre"><?= e($errores['nombre']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="correo">Correo</label>
        <input 
          type="email" 
          name="correo" 
          id="correo" 
          class="form-input<?= isset($errores['correo']) ? ' is-invalid' : '' ?>" 
          value="<?= e($correo) ?>" 
          placeholder="tu@correo.com"
          autocomplete="email"
          aria-invalid="<?= isset($errores['correo']) ? 'true' : 'false' ?>"
          <?php if (isset($errores['correo'])): ?>aria-describedby="error-correo"<?php endif; ?>
          required
        >
        <?php if (isset($errores['correo'])): ?>
          <p class="form-error" id="error-correo"><?= e($errores['correo']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="clave">Contraseña</label>
        <input 
          type="password" 
          name="clave" 
          id="clave" 
          class="form-input<?= isset($errores['clave']) ? ' is-invalid' : '' ?>" 
          minlength="8" 
          autocomplete="new-password" 
          aria-invalid="<?= isset($errores['clave']) ? 'true' : 'false' ?>"
          <?php if (isset($errores['clave'])): ?>aria-describedby="error-clave"<?php endif; ?>
          required
        >
        <p class="auth-helper">Usa al menos 8 caracteres.</p>
        <?php if (isset($errores['clave'])): ?>
          <p class="form-error" id="error-clave"><?= e($errores['clave']) ?></p>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="clave2">Repítela</label>
        <input 
          type="password" 
          name="clave2" 
          id="clave2" 
          class="form-input<?= isset($errores['clave2']) ? ' is-invalid' : '' ?>" 
          minlength="8" 
          autocomplete="new-password" 
          aria-invalid="<?= isset($errores['clave2']) ? 'true' : 'false' ?>"
          <?php if (isset($errores['clave2'])): ?>aria-describedby="error-clave2"<?php endif; ?>
          required
        >
        <?php if (isset($errores['clave2'])): ?>
          <p class="form-error" id="error-clave2"><?= e($errores['clave2']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-primary">
        <span>Crear mi cuenta</span>
        <span aria-hidden="true">→</span>
      </button>
    </form>

    <p class="auth-footer">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</main>
</body>
</html>