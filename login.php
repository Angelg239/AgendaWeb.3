<?php
/* ════════════════════════════════════════════════════════
   login.php · AgendaWeb
   ════════════════════════════════════════════════════════ */

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

session_start();
if (isset($_SESSION['uid'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/conexion.php';

$correo = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = mb_strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    $stmt = $mysqli->prepare('SELECT id, nombre, clave_hash FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($clave, $usuario['clave_hash'])) {
        session_regenerate_id(true); // Cambia el ID de sesión por seguridad
        $_SESSION['uid']    = (int) $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        header('Location: index.php');
        exit;
    }

    $error = 'Correo o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/estilos.css" rel="stylesheet">
    <title>Iniciar sesión · AgendaWeb</title>
</head>
<body>
<div class="card">
    <h1 class="card__title">Iniciar sesión</h1>
    <p class="card__meta">AgendaWeb</p>

    <?php if (isset($_GET['registrado'])): ?>
        <div class="alert alert--ok">Cuenta creada. Ya puedes iniciar sesión.</div>
    <?php endif; ?>
    <?php if (isset($_GET['salio'])): ?>
        <div class="alert alert--ok">Cerraste sesión. ¡Hasta pronto!</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label class="form-label" for="correo">Correo</label>
            <input type="email" name="correo" id="correo" class="form-input" value="<?= e($correo) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label" for="clave">Contraseña</label>
            <input type="password" name="clave" id="clave" class="form-input" autocomplete="current-password" required>
        </div>

        <button type="submit" class="btn-primary">Entrar</button>
    </form>

    <p class="card__link"><a href="registro.php">¿No tienes cuenta? Crear cuenta</a></p>
</div>
</body>
</html>