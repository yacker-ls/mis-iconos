<?php $upload_token = getenv("UPLOAD_TOKEN") ?: "jose13"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subida de Iconos Automatizada</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Inter", sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 500px; width: 100%; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .icon { font-size: 48px; margin-bottom: 15px; display: block; }
        h2 { color: #2d3748; font-weight: 600; font-size: 28px; margin-bottom: 10px; }
        .subtitle { color: #718096; font-size: 16px; }
        .file-info { background: #f7fafc; border: 2px dashed #cbd5e0; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; transition: all 0.3s; }
        .file-info:hover { border-color: #667eea; background: #edf2f7; }
        .file-info.has-file { border-color: #48bb78; background: #f0fff4; }
        label { display: block; margin-bottom: 15px; font-weight: 500; color: #2d3748; font-size: 16px; }
        input[type="file"] { display: none; }
        .file-input-label { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s; font-size: 16px; }
        .file-input-label:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.3); }
        .file-details { margin-top: 15px; font-size: 14px; color: #4a5568; }
        .file-size { font-weight: 500; color: #2d3748; }
        button { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 15px 30px; border: none; border-radius: 10px; cursor: pointer; margin-top: 25px; font-size: 16px; font-weight: 600; transition: all 0.3s; width: 100%; }
        button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(72,187,120,0.3); }
        button:disabled { background: #a0aec0; cursor: not-allowed; transform: none; }
        .progress-bar { width: 100%; height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 15px; overflow: hidden; display: none; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); width: 0%; transition: width 0.3s; }
        #mensaje { margin-top: 20px; padding: 15px; border-radius: 10px; font-weight: 500; display: none; border-left: 4px solid; }
        .error { background-color: #fed7d7; color: #c53030; border-left-color: #e53e3e; }
        .info { background-color: #bee3f8; color: #2b6cb0; border-left-color: #3182ce; }
        .success { background-color: #c6f6d5; color: #22543d; border-left-color: #38a169; }
        .requirements { background: #f7fafc; border-radius: 8px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #667eea; }
        .requirements h4 { color: #2d3748; margin-bottom: 8px; font-weight: 600; }
        .requirements ul { color: #4a5568; padding-left: 20px; }
        .requirements li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="icon">&#128640;</span>
            <h2>Subir Icono a Repositorio</h2>
            <p class="subtitle">Sube tus iconos de forma automatica a GitHub</p>
        </div>
        <div style="text-align:center;margin-bottom:20px;">
            <a href="index.php" style="font-weight:500;color:#4a5568;text-decoration:none;background:#e2e8f0;padding:8px 15px;border-radius:8px;">Ver Galeria de Iconos</a>
        </div>
        <div class="requirements">
            <h4>Requisitos del archivo:</h4>
            <ul>
                <li>Formatos permitidos: PNG, JPG, JPEG, SVG</li>
                <li>Tamano maximo: 4 MB</li>
                <li>Se sincronizara automaticamente con GitHub</li>
            </ul>
        </div>
        <form id="uploadForm" action="uploadimg.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="auth_token" value="<?php echo htmlspecialchars($upload_token); ?>">
            <label for="iconFile">Selecciona tu archivo:</label>
            <div class="file-info" id="fileInfo">
                <label for="iconFile" class="file-input-label">Seleccionar archivo</label>
                <input type="file" id="iconFile" name="iconFile" accept=".png,.jpg,.jpeg,.svg" required>
                <div class="file-details" id="fileDetails" style="display:none;">
                    <div id="fileName"></div>
                    <div class="file-size" id="fileSize"></div>
                </div>
            </div>
            <button type="submit" id="submitBtn">
                <span id="btnText">Subir y Sincronizar con GitHub</span>
            </button>
            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </form>
        <div id="mensaje"></div>
    </div>
    <script>
        const MAX_FILE_SIZE = 4 * 1024 * 1024;
        const ALLOWED_EXT = [".png", ".jpg", ".jpeg", ".svg"];
        function formatFileSize(bytes) {
            if (bytes === 0) return "0 Bytes";
            const k = 1024, sizes = ["Bytes","KB","MB","GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
        }
        function showMessage(text, type) {
            const m = document.getElementById("mensaje"); m.textContent = text; m.className = type; m.style.display = "block";
        }
        function hideMessage() {
            const m = document.getElementById("mensaje"); m.style.display = "none"; m.className = "";
        }
        function updateFileInfo(file) {
            const fi = document.getElementById("fileInfo"), fd = document.getElementById("fileDetails");
            if (file) {
                fi.classList.add("has-file"); fd.style.display = "block";
                document.getElementById("fileName").textContent = file.name;
                document.getElementById("fileSize").textContent = formatFileSize(file.size);
            } else { fi.classList.remove("has-file"); fd.style.display = "none"; }
        }
        function isValidExt(name) {
            return ALLOWED_EXT.some(ext => name.toLowerCase().endsWith(ext));
        }
        document.getElementById("iconFile").addEventListener("change", function(e) {
            const file = e.target.files[0]; hideMessage();
            if (file) {
                updateFileInfo(file);
                if (file.size > MAX_FILE_SIZE) { showMessage("El archivo es demasiado grande. Maximo: " + formatFileSize(MAX_FILE_SIZE), "error"); e.target.value = ""; updateFileInfo(null); return; }
                if (!isValidExt(file.name)) { showMessage("Solo se permiten PNG, JPG, JPEG o SVG", "error"); e.target.value = ""; updateFileInfo(null); return; }
                showMessage("Archivo valido: " + file.name + " (" + formatFileSize(file.size) + ")", "success");
            } else { updateFileInfo(null); }
        });
        document.getElementById("uploadForm").addEventListener("submit", function(e) {
            const file = document.getElementById("iconFile").files[0]; hideMessage();
            if (!file) { e.preventDefault(); showMessage("Por favor, selecciona un archivo", "error"); return; }
            if (file.size > MAX_FILE_SIZE) { e.preventDefault(); showMessage("Archivo demasiado grande", "error"); return; }
            if (!isValidExt(file.name)) { e.preventDefault(); showMessage("Solo PNG, JPG, JPEG o SVG", "error"); return; }
            document.getElementById("progressBar").style.display = "block";
            document.getElementById("submitBtn").disabled = true;
            document.getElementById("btnText").textContent = "Subiendo archivo...";
            showMessage("Subiendo y sincronizando con GitHub...", "info");
        });
    </script>
</body>
</html>