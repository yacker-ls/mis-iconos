<?php
// --- INICIO DE LA LÓGICA PHP (SIN NINGÚN HTML O ECHO) ---

// 1. Configuración y Autenticación
$target_dir = "icons/"; 
$file_key = 'iconFile'; 
$repo_path = __DIR__;
$branch = 'master'; 
$max_file_size = 20 * 1024 * 1024; // 20MB
$TOKEN_SECRETO = "jose13"; // He actualizado a jose13

// Buffer de salida para mensajes de error
$error_messages = [];

if (!isset($_POST['auth_token']) || $_POST['auth_token'] !== $TOKEN_SECRETO) {
    $error_messages[] = "❌ Acceso Denegado: El token de autenticación no es válido.";
}

// 2. Validación del Archivo
if (empty($error_messages)) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        $error_messages[] = "❌ Error: No se recibió el archivo o hubo un error de subida.";
    } else {
        $file = $_FILES[$file_key];
        $file_name = basename($file["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
            $error_messages[] = "❌ Error: Solo se permiten archivos JPG, JPEG y PNG.";
        }
        if ($file["size"] > $max_file_size) {
            $error_messages[] = "❌ Error: El archivo es demasiado grande.";
        }
    }
}

// 3. Guardado del Archivo
if (empty($error_messages)) {
    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        $error_messages[] = "❌ Error al mover el archivo. Revisa los permisos de la carpeta `$target_dir`.";
    }
}

// 4. Sincronización con Git
$git_output = '';
if (empty($error_messages)) {
    $commands = [
        "cd $repo_path",
        "git add " . escapeshellarg($target_file) . " 2>&1",
        "git commit -m \"feat(icons): Se agregó $file_name via web\" 2>&1",
        "git push origin $branch 2>&1"
    ];

    $push_success = true;
    foreach ($commands as $command) {
        $current_output = shell_exec($command);
        $git_output .= "<strong>$ " . htmlspecialchars($command) . "</strong>\n" . htmlspecialchars($current_output) . "\n\n";
        if (strpos(strtolower($current_output), 'error') !== false || strpos(strtolower($current_output), 'fatal') !== false) {
            $push_success = false;
        }
    }

    if (!$push_success) {
        $error_messages[] = "⚠️ Fallo en la automatización de Git. El archivo se subió al servidor, pero no se pudo sincronizar con GitHub.";
    }
}


// --- FIN DE LA LÓGICA ---
// --- AHORA, DECIDIMOS QUÉ MOSTRAR AL USUARIO ---

if (empty($error_messages)) {
    // ÉXITO TOTAL: Redirigir al visor con un mensaje de éxito.
    header("Location: index.php?upload=success");
    exit(); // Detiene la ejecución del script aquí.
} else {
    // HUBO ERRORES: Ahora sí, mostramos una página HTML con los detalles.
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html lang='es'><head><title>Error en la Subida</title>";
    echo "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>";
    echo "<style>
        body { font-family: 'Inter', sans-serif; background: #fde2e2; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .container { max-width: 700px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-left: 5px solid #e53e3e; }
        h2 { color: #c53030; margin-bottom: 20px; }
        .error-list p { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-weight: 500; }
        pre { background: #2d3748; color: #f7fafc; padding: 15px; border-radius: 8px; white-space: pre-wrap; font-family: 'Monaco', monospace; font-size: 13px; margin-top: 20px; max-height: 300px; overflow-y: auto; }
        a { display: inline-block; margin-top: 20px; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; font-weight: 500; }
    </style></head><body>";
    echo "<div class='container'>";
    echo "<h2>Houston, tenemos un problema...</h2>";
    echo "<div class='error-list'>";
    foreach ($error_messages as $msg) {
        echo "<p>$msg</p>";
    }
    echo "</div>";
    if (!empty($git_output)) {
        echo "<h4>Salida de Git:</h4>";
        echo "<pre>$git_output</pre>";
    }
    echo "<a href='upload.html'>← Intentar de nuevo</a>";
    echo "</div></body></html>";
    exit();
}
?>