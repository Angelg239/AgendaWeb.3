<?php
require __DIR__ . '/partes/sesion.php';

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

require __DIR__ . '/conexion.php';
$categorias = $mysqli->query('SELECT id, nombre FROM categorias ORDER BY nombre')
                     ->fetch_all(MYSQLI_ASSOC);
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/estilos.css" rel="stylesheet">
    <title>Explorar · AgendaWeb</title>
</head>
<body>
<div class="card" style="max-width: 850px; margin: 2rem auto; padding: 2rem;">
    <header class="explorar-header">
        <div>
            <p class="card__meta" style="margin: 0;">AgendaWeb</p>
            <p class="card__meta" style="margin: 0.2rem 0 0;">Hola, <strong><?= e($usuarioNombre) ?></strong></p>
        </div>
        <nav class="site-nav" aria-label="Navegación principal">
            <a href="index.php" class="nav__link">Mi agenda</a>
            <a href="registrar.php" class="nav__link">Nuevo evento</a>
            <a href="explorar.php" class="nav__link is-active" aria-current="page">Explorar</a>
            <a href="logout.php" class="nav__link">Salir</a>
        </nav>
    </header>

    <main class="contenedor">
        <div class="page__header">
            <div>
                <h1 class="page__title">Explorar</h1>
                <p class="page__subtitle" id="estado" role="status">Cargando eventos…</p>
            </div>
        </div>

        <form class="buscador" id="filtros" role="search">
            <input class="form-input" type="search" id="q" maxlength="60"
                   placeholder="Escribe para buscar…" aria-label="Buscar eventos">
            <select class="form-select" id="categoria" aria-label="Filtrar por categoría">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"><?= e($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <section class="card-list" id="lista" aria-label="Eventos" aria-live="polite"></section>
    </main>

    <footer class="explorar-footer">
        AgendaWeb
    </footer>
</div>
<script src="js/explorar.js"></script>
</body>
</html>
