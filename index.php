<?php
$icons_dir = "icons/";
$repo_path = __DIR__;
$branch = "master";
$delete_pin = getenv("DELETE_PIN") ?: "4249";
$github_raw_base = "https://raw.githubusercontent.com/yacker-ls/mis-iconos/master/icons/";
$per_page = 50;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_file"])) {
    $pin_ingresado = $_POST["delete_pin"] ?? "";
    if ($pin_ingresado !== $delete_pin) {
        $message = "<div class=\"message error\">Codigo de seguridad incorrecto. No se elimino el icono.</div>";
    } else {
        $file_to_delete = basename($_POST["delete_file"]);
        $full_path = $icons_dir . $file_to_delete;
        if (file_exists($full_path)) {
            $commands = [
                "cd $repo_path",
                "git rm " . escapeshellarg($full_path) . " 2>&1",
                "git commit -m \"chore(icons): Se elimino $file_to_delete desde el visor web\" 2>&1",
                "git push origin $branch 2>&1"
            ];
            foreach ($commands as $command) { shell_exec($command); }
            $message = "<div class=\"message success\">Icono <strong>" . htmlspecialchars($file_to_delete) . "</strong> eliminado y sincronizado.</div>";
        } else {
            $message = "<div class=\"message error\">Error: El archivo no fue encontrado.</div>";
        }
    }
}

if (isset($_GET["upload"]) && $_GET["upload"] === "success") {
    $message = "<div class=\"message success\">Icono subido y sincronizado con GitHub exitosamente!</div>";
}

$all_files = [];
$allowed_types = ["png", "jpg", "jpeg", "svg", "gif"];
foreach (scandir($icons_dir) as $file) {
    if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_types)) {
        $all_files[] = $file;
    }
}

$total = count($all_files);
$total_pages = max(1, (int)ceil($total / $per_page));
$page = max(1, min($total_pages, (int)($_GET["page"] ?? 1)));
$offset = ($page - 1) * $per_page;
$image_files = array_slice($all_files, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Iconos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Inter", sans-serif; background-color: #f4f7f9; color: #333; margin: 0; padding: 20px; }
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
        .card-actions { display: flex; gap: 6px; justify-content: center; }
        .delete-btn { background: #e53e3e; color: white; border: none; padding: 7px 10px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 12px; flex: 1; }
        .link-btn { background: #3182ce; color: white; border: none; padding: 7px 10px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 12px; flex: 1; }
        .link-btn:hover { background: #2b6cb0; }
        .delete-btn:hover { background: #c53030; }
        .empty-gallery { text-align: center; padding: 50px; background-color: #f7fafc; border-radius: 10px; border: 2px dashed #e2e8f0; grid-column: 1 / -1; }
        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 30px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: 500; font-size: 14px; }
        .pagination a { background: #e2e8f0; color: #4a5568; transition: all 0.2s; }
        .pagination a:hover { background: #667eea; color: white; }
        .pagination span.current { background: #667eea; color: white; }
        .pagination span.disabled { background: #f7fafc; color: #cbd5e0; }
        .pagination-info { text-align: center; color: #718096; font-size: 14px; margin-top: 10px; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: #fff; border-radius: 12px; padding: 30px; max-width: 360px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; }
        .modal h3 { color: #c53030; margin-bottom: 8px; }
        .modal p { color: #4a5568; font-size: 14px; margin-bottom: 20px; }
        .modal input[type="password"] { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 18px; text-align: center; letter-spacing: 6px; box-sizing: border-box; margin-bottom: 16px; }
        .modal input[type="password"]:focus { outline: none; border-color: #e53e3e; }
        .modal-buttons { display: flex; gap: 10px; }
        .modal-buttons button { flex: 1; padding: 10px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-cancel { background: #e2e8f0; color: #4a5568; }
        .btn-confirm { background: #e53e3e; color: white; }
        .toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(80px); background: #2d3748; color: white; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 500; opacity: 0; transition: all 0.3s ease; z-index: 200; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestor de Iconos</h1>
            <div class="nav-links">
                <a href="upload.php">Subir Nuevo Icono</a>
            </div>
        </div>
        <?php echo $message; ?>
        <div class="icon-gallery">
            <?php if (!empty($image_files)): ?>
                <?php foreach ($image_files as $icon): ?>
                    <div class="icon-card">
                        <img src="<?= $icons_dir . htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($icon) ?>">
                        <div class="filename"><?= htmlspecialchars($icon) ?></div>
                        <div class="card-actions">
                            <button class="link-btn" onclick="copiarLink('<?= htmlspecialchars($icon, ENT_QUOTES) ?>')">Link</button>
                            <button class="delete-btn" onclick="abrirModal('<?= htmlspecialchars($icon, ENT_QUOTES) ?>')">Eliminar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-gallery">
                    <h2>Galeria Vacia</h2>
                    <p>No hay iconos. <a href="upload.php">Sube uno ahora</a>.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=1">&laquo;</a>
                <a href="?page=<?= $page - 1 ?>">&lsaquo; Anterior</a>
            <?php else: ?>
                <span class="disabled">&laquo;</span>
                <span class="disabled">&lsaquo; Anterior</span>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Siguiente &rsaquo;</a>
                <a href="?page=<?= $total_pages ?>">&raquo;</a>
            <?php else: ?>
                <span class="disabled">Siguiente &rsaquo;</span>
                <span class="disabled">&raquo;</span>
            <?php endif; ?>
        </div>
        <p class="pagination-info">
            Mostrando <?= $offset + 1 ?>-<?= min($offset + $per_page, $total) ?> de <?= $total ?> iconos &mdash; Pagina <?= $page ?> de <?= $total_pages ?>
        </p>
        <?php endif; ?>
    </div>

    <div class="toast" id="toast">Link copiado al portapapeles</div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <h3>Confirmar eliminacion</h3>
            <p>Ingresa el codigo de seguridad para eliminar <strong id="modalFileName"></strong></p>
            <form method="POST" action="index.php" id="deleteForm">
                <input type="hidden" name="delete_file" id="deleteFileInput">
                <input type="password" name="delete_pin" id="pinInput" placeholder="" maxlength="10" autofocus>
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-confirm">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        const GITHUB_RAW_BASE = "https://raw.githubusercontent.com/yacker-ls/mis-iconos/master/icons/";

        function copiarLink(filename) {
            const url = GITHUB_RAW_BASE + encodeURIComponent(filename);
            navigator.clipboard.writeText(url).then(function() {
                mostrarToast("Link copiado al portapapeles");
            }).catch(function() {
                // Fallback para navegadores sin clipboard API
                const el = document.createElement("textarea");
                el.value = url;
                document.body.appendChild(el);
                el.select();
                document.execCommand("copy");
                document.body.removeChild(el);
                mostrarToast("Link copiado al portapapeles");
            });
        }

        function mostrarToast(msg) {
            const t = document.getElementById("toast");
            t.textContent = msg;
            t.classList.add("show");
            setTimeout(() => t.classList.remove("show"), 2500);
        }

        function abrirModal(filename) {
            document.getElementById("modalFileName").textContent = filename;
            document.getElementById("deleteFileInput").value = filename;
            document.getElementById("pinInput").value = "";
            document.getElementById("modalOverlay").classList.add("active");
            setTimeout(() => document.getElementById("pinInput").focus(), 100);
        }
        function cerrarModal() { document.getElementById("modalOverlay").classList.remove("active"); }
        document.getElementById("modalOverlay").addEventListener("click", function(e) { if (e.target === this) cerrarModal(); });
    </script>
</body>
</html>
