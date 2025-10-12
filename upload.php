<?php
// Configuración
$target_dir = "icons/"; // Carpeta donde se guardan los iconos
$file_key = 'iconFile'; // Nombre del campo en el formulario HTML
$repo_path = __DIR__;   // Ruta a la raíz de tu repositorio local (donde está este script)
$branch = 'master';     // Tu rama principal (usamos 'master' basado en tu git init)
$max_file_size = 20 * 1024 * 1024; // 20MB en bytes

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html lang='es'><head><title>Resultado de Subida</title>";
echo "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>";
echo "<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
        font-family: 'Inter', sans-serif; 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .container { 
        max-width: 600px; 
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .header { text-align: center; margin-bottom: 30px; }
    .icon { font-size: 48px; margin-bottom: 15px; display: block; }
    h2 { color: #2d3748; font-weight: 600; font-size: 28px; margin-bottom: 10px; }
    .success { 
        background: #c6f6d5; 
        color: #22543d; 
        padding: 15px; 
        border-radius: 10px; 
        font-weight: 500; 
        border-left: 4px solid #38a169;
        margin: 15px 0;
    }
    .error { 
        background: #fed7d7; 
        color: #c53030; 
        padding: 15px; 
        border-radius: 10px; 
        font-weight: 500; 
        border-left: 4px solid #e53e3e;
        margin: 15px 0;
    }
    .info { 
        background: #bee3f8; 
        color: #2b6cb0; 
        padding: 15px; 
        border-radius: 10px; 
        font-weight: 500; 
        border-left: 4px solid #3182ce;
        margin: 15px 0;
    }
    pre { 
        background: #f7fafc; 
        padding: 15px; 
        border: 1px solid #e2e8f0; 
        border-radius: 8px; 
        white-space: pre-wrap; 
        font-family: 'Monaco', 'Consolas', monospace;
        font-size: 13px;
        color: #4a5568;
        margin: 10px 0;
    }
    .back-btn {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 500;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    .back-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
</style></head><body>";
echo "<div class='container'>";
echo "<div class='header'>";
echo "<span class='icon'>📤</span>";
echo "<h2>Resultado de la Subida</h2>";
echo "</div>";

// --- 1. VALIDACIÓN y RECEPCIÓN ---
if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
    die("<p class='error'>❌ Error: No se recibió el archivo o hubo un error de subida.</p>");
}

$file = $_FILES[$file_key];
$file_name = basename($file["name"]);
$target_file = $target_dir . $file_name;
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

// Validar tipo de archivo
if (!in_array($imageFileType, ["jpg", "jpeg", "png"])) {
    die("<p class='error'>❌ Error: Solo se permiten archivos JPG, JPEG y PNG.</p>");
}

// Validar tamaño de archivo
if ($file["size"] > $max_file_size) {
    $max_size_mb = round($max_file_size / (1024 * 1024), 1);
    $file_size_mb = round($file["size"] / (1024 * 1024), 1);
    die("<p class='error'>❌ Error: El archivo es demasiado grande. Tamaño actual: {$file_size_mb}MB, máximo permitido: {$max_size_mb}MB.</p>");
}

// --- 2. GUARDADO DEL ARCHIVO ---
if (!move_uploaded_file($file["tmp_name"], $target_file)) {
    die("<p class='error'>❌ Error al subir el archivo. Revisa los permisos de la carpeta `$target_dir` (ej: chmod 777 $target_dir).</p>");
}

$file_size_mb = round($file["size"] / (1024 * 1024), 2);
echo "<p class='success'>✅ Archivo <strong>$file_name</strong> subido correctamente ({$file_size_mb}MB).</p>";

// --- 3. AUTOMATIZACIÓN DE GIT/GITHUB ---

echo "<p class='info'>🚀 Iniciando sincronización con GitHub...</p>";

// Definir los comandos de Git
$commands = [
    "cd $repo_path", // Moverse a la raíz del repositorio
    "git add $target_dir 2>&1", // Añadir todos los cambios en la carpeta icons
    "git commit -m \"feat(icons): Se agregó $file_name via sitio web\" 2>&1",
    "git push origin $branch 2>&1" // Enviar el cambio a GitHub
];

$output = "";
$push_success = true;

// Ejecutar los comandos en secuencia
foreach ($commands as $command) {
    echo "<p>Ejecutando: <code>$command</code></p>";
    
    // shell_exec ejecuta el comando y devuelve la salida
    $current_output = shell_exec($command);
    $output .= "Salida: <pre>$current_output</pre>";

    // Simple chequeo para detectar errores comunes de Git
    if (strpos($current_output, 'error:') !== false || strpos($current_output, 'fatal:') !== false) {
        $push_success = false;
    }
}

echo $output;

if ($push_success) {
    echo "<p class='success'>🎉 ¡Éxito! El nuevo icono <strong>$file_name</strong> está disponible en tu repositorio de GitHub.</p>";
    echo "<p class='info'>💡 Puedes verificar la subida visitando tu repositorio en GitHub.</p>";
} else {
    echo "<p class='error'>⚠️ Fallo en la automatización de Git. Revisa la salida de los comandos (puede ser un problema de permisos de ejecución o de autenticación de Git).</p>";
    echo "<p class='info'>💡 El archivo se subió correctamente, pero no se pudo sincronizar con GitHub.</p>";
}

echo "<a href='index.html' class='back-btn'>← Volver a subir otro archivo</a>";
echo "</div></body></html>";
?>