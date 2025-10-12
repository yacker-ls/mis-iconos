<?php
// --- CONFIGURACIÓN ---
$icons_dir = "icons/";
$repo_path = __DIR__;
$branch = 'master';
$delete_message = ''; // Variable para guardar mensajes de éxito o error

// --- PROCESO DE ELIMINACIÓN (SE EJECUTA SI SE ENVÍA UN FORMULARIO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    
    // Medida de seguridad CRÍTICA para evitar que se borren archivos fuera de la carpeta 'icons'
    $file_to_delete = basename($_POST['delete_file']);
    $full_path = $icons_dir . $file_to_delete;

    if (file_exists($full_path)) {
        // El archivo existe, procedemos a borrarlo y a ejecutar Git

        $commands = [
            "cd $repo_path",
            // git rm borra el archivo y lo añade al "stage" para el commit
            "git rm " . escapeshellarg($full_path) . " 2>&1",
            "git commit -m \"chore(icons): Se eliminó $file_to_delete desde el visor web\" 2>&1",
            "git push origin $branch 2>&1"
        ];
        
        $output = "--- Iniciando eliminación y sincronización para: $file_to_delete ---\n";
        $delete_success = true;

        foreach ($commands as $command) {
            $output .= "Ejecutando: $command\n";
            $current_output = shell_exec($command);
            $output .= "Salida: $current_output\n---\n";

            if (strpos(strtolower($current_output), 'error') !== false || strpos(strtolower($current_output), 'fatal') !== false) {
                $delete_success = false;
            }
        }

        if ($delete_success) {
            $delete_message = "<div class='message success'>✅ Icono <strong>$file_to_delete</strong> eliminado y sincronizado con GitHub correctamente.</div><pre>$output</pre>";
        } else {
            $delete_message = "<div class='message error'>⚠️ Fallo al eliminar con Git. Revisa los permisos o la salida del comando.</div><pre>$output</pre>";
        }

    } else {
        $delete_message = "<div class='message error'>❌ Error: El archivo <strong>$file_to_delete</strong> no fue encontrado.</div>";
    }
}

// --- VISUALIZACIÓN DE LA GALERÍA (SE EJECUTA SIEMPRE) ---
$image_files = [];
$allowed_types = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
$files = scandir($icons_dir);
foreach ($files as $file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($extension, $allowed_types)) {
        $image_files[] = $file;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor y Gestor de Iconos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        h1 { color: #2d3748; font-weight: 700; }
        .nav-links { margin-top: 15px; }
        .nav-links a { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
        .nav-links a:hover { background: #5a67d8; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .message { padding: 15px; border-radius: 10px; font-weight: 500; margin-bottom: 20px; border-left: 4px solid; }
        .success { background-color: #c6f6d5; color: #22543d; border-left-color: #38a169; }
        .error { background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e; }
        pre { background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 8px; white-space: pre-wrap; font-family: 'Monaco', monospace; font-size: 13px; margin-top: 10px; }
        .icon-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 25px; }
        .icon-card { background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 15px; text-align: center; transition: all 0.3s ease; }
        .icon-card:hover { transform: translateY(-5px); box-shadow: 0 12px 20px rgba(0,0,0,0.08); }
        .icon-card img { max-width: 100%; height: 80px; object-fit: contain; margin-bottom: 15px; }
        .icon-card .filename { font-size: 13px; color: #4a5568; word-wrap: break-word; font-weight: 500; margin-bottom: 15px; }
        .delete-btn { background: #e53e3e; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 12px; transition: background 0.2s; }
        .delete-btn:hover { background: #c53030; }
        .empty-gallery { text-align: center; padding: 50px; background-color: #f7fafc; border-radius: 10px; border: 2px dashed #e2e8f0; grid-column: 1 / -1; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>🖼️ Visor y Gestor de Iconos</h1>
            <div class="nav-links">
                <a href="index.html">Subir Nuevo Icono</a>
            </div>
        </div>
        
        <?php echo $delete_message; ?>

        <div class="icon-gallery">
            <?php if (!empty($image_files)): ?>
                <?php foreach ($image_files as $icon): ?>
                    <div class="icon-card">
                        <img src="<?= $icons_dir . htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($icon) ?>">
                        <div class="filename"><?= htmlspecialchars($icon) ?></div>
                        
                        <form method="POST" action="viewer.php" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este icono? Esta acción es PERMANENTE y se sincronizará con GitHub.');">
                            <input type="hidden" name="delete_file" value="<?= htmlspecialchars($icon) ?>">
                            <button type="submit" class="delete-btn">🗑️ Eliminar</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-gallery">
                    <h2>Galería Vacía</h2>
                    <p>No hay iconos para mostrar. <a href="index.html">Sube uno ahora</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>