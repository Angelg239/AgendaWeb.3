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
<body>
<div class="card">
    <h1 class="card__title">Crear cuenta</h1>
    <p class="card__meta">AgendaWeb</p>

    <?php if (isset($errores['general'])): ?>
      <div class="alert alert--error">
        <?= e($errores['general']) ?>
      </div>
    <?php endif; ?>

    <!-- action="" = se envía a sí misma -->
    <form method="post" action="">
      <div class="form-group">
        <label class="form-label" for="nombre">Nombre</label>
        <input 
          type="text" 
          name="nombre" 
          id="nombre" 
          class="form-input<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>" 
          value="<?= e($nombre) ?>" 
          required 
          maxlength="80"
        >
        <?php if (isset($errores['nombre'])): ?>
          <p class="form-error"><?= e($errores['nombre']) ?></p>
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
          required
        >
        <?php if (isset($errores['correo'])): ?>
          <p class="form-error"><?= e($errores['correo']) ?></p>
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
          required
        >
        <?php if (isset($errores['clave'])): ?>
          <p class="form-error"><?= e($errores['clave']) ?></p>
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
          required
        >
        <?php if (isset($errores['clave2'])): ?>
          <p class="form-error"><?= e($errores['clave2']) ?></p>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn-primary">Crear cuenta</button>
    </form>

    <p class="card__link"><a href="login.php">¿Ya tengo cuenta? Inicia sesión</a></p>
</div>
</body>
</html>