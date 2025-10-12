<?php
// --- CONFIGURACIÓN ---
$icons_dir = "icons/";
$repo_path = __DIR__;
$branch = 'master';
$message = ''; // Variable para guardar mensajes

// --- PROCESO DE ELIMINACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $file_to_delete = basename($_POST['delete_file']);
    $full_path = $icons_dir . $file_to_delete;
    if (file_exists($full_path)) {
        $commands = [
            "cd $repo_path",
            "git rm " . escapeshellarg($full_path) . " 2>&1",
            "git commit -m \"chore(icons): Se eliminó $file_to_delete desde el visor web\" 2>&1",
            "git push origin $branch 2>&1"
        ];
        $output = "";
        foreach ($commands as $command) { $output .= shell_exec($command); }
        $message = "<div class='message success'>✅ Icono <strong>$file_to_delete</strong> eliminado y sincronizado.</div>";
    } else {
        $message = "<div class='message error'>❌ Error: El archivo <strong>$file_to_delete</strong> no fue encontrado.</div>";
    }
}

// --- MENSAJE DE SUBIDA EXITOSA ---
if (isset($_GET['upload']) && $_GET['upload'] === 'success') {
    $message = "<div class='message success'>🚀 ¡Icono subido y sincronizado con GitHub exitosamente!</div>";
}

// --- LECTURA DE LA GALERÍA ---
$image_files = [];
$allowed_types = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
$files = scandir($icons_dir);
foreach ($files as $file) {
    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_types)) {
        $image_files[] = $file;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Iconos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        h1 { color: #2d3748; font-weight: 700; }
        .nav-links { margin-top: 15px; }
        .nav-links a { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
        .nav-links a:hover { background: #5a67d8; }
        .message { padding: 15px; border-radius: 10px; font-weight: 500; margin-bottom: 20px; border-left: 4px solid; }
        .success { background-color: #c6f6d5; color: #22543d; border-left-color: #38a169; }
        .error { background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e; }
        .icon-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 25px; }
        .icon-card { background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; text-align: center; }
        .icon-card img { max-width: 100%; height: 80px; object-fit: contain; margin-bottom: 15px; }
        .icon-card .filename { font-size: 13px; color: #4a5568; word-wrap: break-word; font-weight: 500; margin-bottom: 15px; }
        .delete-btn { background: #e53e3e; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 12px; }
        .empty-gallery { text-align: center; padding: 50px; background-color: #f7fafc; border-radius: 10px; border: 2px dashed #e2e8f0; grid-column: 1 / -1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Gestor de Iconos</h1>
            <div class="nav-links">
                <a href="upload.html">Subir Nuevo Icono</a>
            </div>
        </div>
        
        <?php echo $message; // Muestra mensajes de éxito/error aquí ?>

        <div class="icon-gallery">
            <?php if (!empty($image_files)): ?>
                <?php foreach ($image_files as $icon): ?>
                    <div class="icon-card">
                        <img src="<?= $icons_dir . htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($icon) ?>">
                        <div class="filename"><?= htmlspecialchars($icon) ?></div>
                        <form method="POST" action="index.php" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este icono?');">
                            <input type="hidden" name="delete_file" value="<?= htmlspecialchars($icon) ?>">
                            <button type="submit" class="delete-btn">🗑️ Eliminar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-gallery">
                    <h2>Galería Vacía</h2>
                    <p>No hay iconos para mostrar. <a href="upload.html">Sube uno ahora</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>