<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin — Site Images</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #1e2d3a; }
        .admin-header { background: #1e2d3a; color: #fff; padding: 0 32px; height: 56px; display: flex; align-items: center; justify-content: space-between; }
        .admin-header h1 { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.04em; margin: 0; }
        .admin-header a { color: #8ab4d4; font-size: 0.88rem; text-decoration: none; }
        .admin-header a:hover { color: #fff; }
        .admin-body { display: flex; min-height: calc(100vh - 56px); }
        .sidebar { width: 200px; background: #fff; border-right: 1px solid #d8dde4; padding: 24px 0; flex-shrink: 0; }
        .sidebar a { display: block; padding: 10px 24px; font-size: 0.92rem; color: #3a4a5c; text-decoration: none; border-left: 3px solid transparent; transition: background 0.15s, border-color 0.15s; }
        .sidebar a:hover { background: #f0f2f5; }
        .sidebar a.active { background: #e8f0fe; border-left-color: #2a7ae2; color: #2a7ae2; font-weight: 600; }
        .main { flex: 1; padding: 32px 40px; }
        .main h2 { font-size: 1.25rem; margin: 0 0 6px; }
        .page-hint { color: #6b7a8d; font-size: 0.88rem; margin-bottom: 28px; }
        .flash-ok { background: #e6f9ee; border: 1px solid #7dce9d; color: #1a6636; border-radius: 6px; padding: 10px 16px; margin-bottom: 20px; font-size: 0.93rem; }
        .add-form { background: #fff; border: 1px solid #d8dde4; border-radius: 8px; padding: 24px; margin-bottom: 32px; }
        .add-form h3 { margin: 0 0 18px; font-size: 1rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: #3a4a5c; }
        .form-group input, .form-group textarea { padding: 8px 10px; border: 1.5px solid #d1d9e0; border-radius: 5px; font-size: 0.93rem; font-family: inherit; outline: none; transition: border-color 0.2s; }
        .form-group input:focus, .form-group textarea:focus { border-color: #2a7ae2; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .form-actions { margin-top: 16px; }
        .btn-primary { background: #2a7ae2; color: #fff; border: none; border-radius: 6px; padding: 9px 28px; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-primary:hover { background: #1b5fbe; }
        .btn-danger { background: #e53935; color: #fff; border: none; border-radius: 5px; padding: 6px 14px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-edit { background: #fff; color: #2a7ae2; border: 1.5px solid #2a7ae2; border-radius: 5px; padding: 5px 13px; font-size: 0.85rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-edit:hover { background: #e8f0fe; }
        .projects-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #d8dde4; }
        .projects-table th { background: #f0f2f5; padding: 10px 14px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7a8d; text-align: left; border-bottom: 1px solid #d8dde4; }
        .projects-table td { padding: 12px 14px; font-size: 0.91rem; border-bottom: 1px solid #eef0f3; vertical-align: middle; }
        .projects-table tr:last-child td { border-bottom: none; }
        .proj-thumb { width: 60px; height: 42px; object-fit: cover; border-radius: 4px; background: #d8dde4; display: block; }
        .proj-thumb-placeholder { width: 60px; height: 42px; background: #d8dde4; border-radius: 4px; }
        .proj-tag-badge { display: inline-block; background: #e8f0fe; color: #2a7ae2; border-radius: 4px; padding: 2px 8px; font-size: 0.78rem; font-weight: 600; }
        .actions-cell { display: flex; gap: 8px; align-items: center; }
        @media (max-width: 700px) {
            .admin-body { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid #d8dde4; padding: 10px 0; display: flex; flex-wrap: wrap; }
            .sidebar a { padding: 8px 14px; border-left: none; border-bottom: 3px solid transparent; }
            .sidebar a.active { border-bottom-color: #2a7ae2; border-left-color: transparent; }
            .main { padding: 20px 16px; }
            .form-grid { grid-template-columns: 1fr; }
        }
        .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .img-card { background: #fff; border: 1px solid #d8dde4; border-radius: 8px; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
        .img-card h3 { margin: 0; font-size: 0.98rem; }
        .img-card .where { color: #6b7a8d; font-size: 0.8rem; margin: 0; }
        .img-card .hint { color: #8a9ab0; font-size: 0.78rem; margin: 0; }
        .img-preview { width: 100%; height: 170px; object-fit: contain; background: #e9edf2; border-radius: 6px; border: 1px solid #d8dde4; display: block; }
        .img-badge { display: inline-block; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: #eef0f3; color: #6b7a8d; align-self: flex-start; }
        .img-badge.custom { background: #e6f9ee; color: #1a6636; }
        .img-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .img-actions input[type=file] { flex: 1 1 100%; font-size: 0.82rem; }
        .btn-secondary { background: #fff; color: #3a4a5c; border: 1.5px solid #d1d9e0; border-radius: 6px; padding: 7px 16px; font-size: 0.88rem; font-weight: 600; cursor: pointer; }
        .btn-secondary:hover { background: #f0f2f5; }
    </style>
    <script src="/static/admin.js" defer></script>
</head>
<body>
    <div class="admin-header">
        <h1>S3 Synergy — Admin</h1>
        <form method="POST" action="/admin/logout" style="display:inline;margin:0">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" style="background:none;border:none;color:#8ab4d4;font-size:0.88rem;cursor:pointer;padding:0">Sign out</button>
        </form>
    </div>
    <div class="admin-body">
        <nav class="sidebar">
            <a href="/admin">Content Pages</a>
            <a href="/admin/projects" style="margin-top: 12px; border-top: 1px solid #d8dde4; padding-top: 14px;">Manage Projects</a>
            <a href="/admin/images" class="active">Site Images</a>
        </nav>
        <div class="main">
            <?php foreach (get_flashed_messages() as $msg): ?>
                <div class="flash-ok"><?= e($msg) ?></div>
            <?php endforeach; ?>

            <h2>Site Images</h2>
            <p class="page-hint">Replace the logo, banners and home page pictures. Uploads are resized and optimised automatically. Use "Restore original" to go back to the picture that shipped with the site.</p>

            <div class="img-grid">
                <?php foreach ($slots as $slotKey => $slot): ?>
                <?php $isCustom = site_image_override_path($slotKey) !== ''; ?>
                <div class="img-card">
                    <h3><?= e($slot['label']) ?></h3>
                    <p class="where"><?= e($slot['where']) ?></p>
                    <img class="img-preview" id="preview-<?= e($slotKey) ?>" src="<?= e(site_img($slotKey)) ?>" alt="">
                    <span class="img-badge <?= $isCustom ? 'custom' : '' ?>"><?= $isCustom ? 'Custom image' : 'Original image' ?></span>
                    <p class="hint"><?= e($slot['hint']) ?>. JPG, PNG, GIF or WEBP, up to 8 MB.</p>
                    <form method="POST" action="/admin/images/save/<?= e($slotKey) ?>" enctype="multipart/form-data" class="img-actions">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" data-preview="preview-<?= e($slotKey) ?>" required>
                        <button type="submit" class="btn-primary">Upload</button>
                    </form>
                    <?php if ($isCustom): ?>
                    <form method="POST" action="/admin/images/reset/<?= e($slotKey) ?>" data-confirm="Restore the original image for this slot?">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn-secondary">Restore original</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
