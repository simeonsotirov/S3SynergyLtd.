<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin — Projects</title>
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
        .main { min-width: 0; }
        .form-group { min-width: 0; }
        .table-wrap { overflow-x: auto; }
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
        .order-cell { display: flex; gap: 4px; align-items: center; }
        .btn-move { background: #fff; border: 1.5px solid #d1d9e0; border-radius: 4px; width: 28px; height: 28px; cursor: pointer; font-size: 0.8rem; color: #3a4a5c; padding: 0; }
        .btn-move:hover:not(:disabled) { background: #e8f0fe; border-color: #2a7ae2; }
        .btn-move:disabled { opacity: 0.35; cursor: default; }
        .btn-secondary { background: #fff; color: #3a4a5c; border: 1.5px solid #d1d9e0; border-radius: 6px; padding: 8px 20px; font-size: 0.95rem; font-weight: 600; cursor: pointer; }
        @media (max-width: 700px) {
            .admin-body { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid #d8dde4; padding: 10px 0; display: flex; flex-wrap: wrap; }
            .sidebar a { padding: 8px 14px; border-left: none; border-bottom: 3px solid transparent; }
            .sidebar a.active { border-bottom-color: #2a7ae2; border-left-color: transparent; }
            .main { padding: 20px 16px; }
            .form-grid { grid-template-columns: 1fr; }
        }
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
            <a href="/admin/projects" class="active" style="margin-top: 12px; border-top: 1px solid #d8dde4; padding-top: 14px;">Manage Projects</a>
            <a href="/admin/images">Site Images</a>
        </nav>
        <div class="main">
            <?php foreach (get_flashed_messages() as $msg): ?>
                <div class="flash-ok"><?= e($msg) ?></div>
            <?php endforeach; ?>

            <h2>Manage Projects</h2>
            <p class="page-hint">Add, edit, or remove project cards shown on the Projects page.</p>

            <div class="add-form">
                <h3>Add New Project</h3>
                <form method="POST" action="/admin/projects/add" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tag (English)</label>
                            <input type="text" name="tag_en" placeholder="e.g. Nuclear">
                        </div>
                        <div class="form-group">
                            <label>Tag (Bulgarian)</label>
                            <input type="text" name="tag_bg" placeholder="e.g. Ядрена">
                        </div>
                        <div class="form-group">
                            <label>Title (English)</label>
                            <input type="text" name="title_en" placeholder="Project title">
                        </div>
                        <div class="form-group">
                            <label>Title (Bulgarian)</label>
                            <input type="text" name="title_bg" placeholder="Заглавие на проекта">
                        </div>
                        <div class="form-group">
                            <label>Date (English)</label>
                            <input type="text" name="date_en" placeholder="e.g. Jan 2024 – Present">
                        </div>
                        <div class="form-group">
                            <label>Date (Bulgarian)</label>
                            <input type="text" name="date_bg" placeholder="e.g. Яну 2024 – До момента">
                        </div>
                        <div class="form-group full">
                            <label>Description (English)</label>
                            <textarea name="desc_en" rows="3" placeholder="Short project description"></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Description (Bulgarian)</label>
                            <textarea name="desc_bg" rows="3" placeholder="Кратко описание на проекта"></textarea>
                        </div>
                        <div class="form-group full">
                            <label>Project Image</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" data-preview="add-preview">
                            <img id="add-preview" alt="" style="display:none;width:160px;height:112px;object-fit:cover;border-radius:5px;border:1px solid #d8dde4;margin-top:6px">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Add Project</button>
                    </div>
                </form>
            </div>

            <?php if ($projects): ?>
            <div class="table-wrap">
            <table class="projects-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Tag</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_values($projects) as $i => $p): ?>
                    <tr>
                        <td>
                            <?php if ($p['image_filename']): ?>
                            <img src="<?= e('/static/' . $p['image_filename']) ?>" alt="" class="proj-thumb">
                            <?php else: ?>
                            <div class="proj-thumb-placeholder"></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="proj-tag-badge"><?= e($p['tag_en']) ?></span></td>
                        <td><?= e($p['title_en']) ?></td>
                        <td><?= e($p['date_en']) ?></td>
                        <td>
                            <div class="order-cell">
                                <?php foreach (['up' => ['▲', $i === 0], 'down' => ['▼', $i === count($projects) - 1]] as $dir => [$glyph, $disabled]): ?>
                                <form method="POST" action="/admin/projects/<?= (int) $p['id'] ?>/move" style="margin:0">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="direction" value="<?= $dir ?>">
                                    <button type="submit" class="btn-move" <?= $disabled ? 'disabled' : '' ?> aria-label="Move <?= $dir ?>"><?= $glyph ?></button>
                                </form>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <div class="actions-cell">
                                <a href="<?= e(url_for('admin_projects_edit', ['project_id' => $p['id']])) ?>" class="btn-edit">Edit</a>
                                <form method="POST" action="<?= e(url_for('admin_projects_delete', ['project_id' => $p['id']])) ?>" data-confirm="Delete this project and its image? This cannot be undone.">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p style="color: #6b7a8d;">No projects yet. Add one above.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
