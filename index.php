<?php
/**
 * S3 Synergy Ltd — front controller.
 * All requests are rewritten here by .htaccess (see RewriteRule).
 */

declare(strict_types=1);

// PHP built-in dev server only: let it serve /static/* directly, since it
// doesn't apply .htaccess rewrite/deny rules the way Apache does. Scoped to
// /static/ so .env, content.db, and PHP sources are never handed out raw,
// even if this dev server were ever mistakenly used to serve the site.
if (PHP_SAPI === 'cli-server') {
    $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if (str_starts_with($urlPath, '/static/')) {
        $real = realpath(__DIR__ . $urlPath);
        $staticRoot = realpath(__DIR__ . '/static') . DIRECTORY_SEPARATOR;
        if ($real !== false && is_file($real) && str_starts_with($real, $staticRoot)) {
            return false;
        }
    }
}

require __DIR__ . '/lib/bootstrap.php';

set_exception_handler(static function (Throwable $e): void {
    error_log((string) $e);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code(500);
    try {
        render('500');
    } catch (Throwable) {
        echo 'Internal Server Error';
    }
    exit;
});

ob_start('ob_gzhandler');

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
    . "font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self';");

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Request body too large (mirrors Flask MAX_CONTENT_LENGTH -> 413).
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_CONTENT_LENGTH) {
    http_response_code(413);
    render('413');
    exit;
}

// CSRF protection for every POST request (mirrors Flask-WTF CSRFProtect).
if ($method === 'POST' && !csrf_valid()) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Bad Request: the CSRF token is missing or invalid.';
    exit;
}

function abort_not_found(): never
{
    http_response_code(404);
    render('404');
    exit;
}

function fetch_projects(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM projects ORDER BY sort_order, id')->fetchAll();
}

// ---------------------------------------------------------------------------
// Public pages
// ---------------------------------------------------------------------------

$publicPages = [
    '/' => 'home',
    '/services' => 'services',
    '/contact' => 'contact',
    '/terms' => 'terms',
    '/cookie-policy' => 'cookie_policy',
    '/services/conceptual-technical-working' => 'conceptual_technical_working',
    '/services/modeling-simulation' => 'modeling_simulation',
    '/services/strength-analysis' => 'strength_analysis',
    '/services/design-services' => 'design_services',
];

if ($method === 'GET' && isset($publicPages[$path])) {
    render($publicPages[$path]);
    exit;
}

if ($method === 'GET' && $path === '/projects') {
    render('projects', ['projects' => fetch_projects(db())]);
    exit;
}

// ---------------------------------------------------------------------------
// Admin
// ---------------------------------------------------------------------------

if ($path === '/admin/login') {
    if ($method === 'POST') {
        if (rate_limit_exceeded('admin_login', 5, 60)) {
            http_response_code(429);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Too Many Requests: 5 per 1 minute';
            exit;
        }
        $username = (string) ($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $userOk = hash_equals(env('ADMIN_USERNAME'), $username);
        $passOk = hash_equals(env('ADMIN_PASSWORD'), $password);
        if ($userOk && $passOk && env('ADMIN_USERNAME') !== '') {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            redirect(url_for('admin_dashboard'));
        }
        flash('Invalid credentials.');
    }
    render('admin_login');
    exit;
}

if ($path === '/admin/logout' && $method === 'POST') {
    admin_required();
    unset($_SESSION['admin_logged_in']);
    redirect(url_for('admin_login'));
}

if ($path === '/admin' && $method === 'GET') {
    admin_required();
    $defaults = content_defaults();
    $pages = array_keys($defaults);
    $activePage = (string) ($_GET['page'] ?? $pages[0]);
    if (!isset($defaults[$activePage])) {
        $activePage = $pages[0];
    }
    render('admin', [
        'content' => load_content(),
        'defaults' => $defaults,
        'pages' => $pages,
        'active_page' => $activePage,
    ]);
    exit;
}

if ($method === 'POST' && preg_match('#^/admin/save/([A-Za-z0-9_-]+)$#', $path, $m)) {
    admin_required();
    $page = $m[1];
    $defaults = content_defaults();
    if (!isset($defaults[$page])) {
        redirect(url_for('admin_dashboard'));
    }
    $pdo = db();
    backup_db();
    $update = $pdo->prepare('UPDATE content SET value_en = ?, value_bg = ? WHERE page = ? AND "key" = ?');
    $insert = $pdo->prepare('INSERT INTO content (page, "key", value_en, value_bg) VALUES (?, ?, ?, ?)');
    $pdo->beginTransaction();
    foreach (array_keys($defaults[$page]) as $key) {
        $valueEn = (string) ($_POST[$key . '__en'] ?? '');
        $valueBg = (string) ($_POST[$key . '__bg'] ?? '');
        $update->execute([$valueEn, $valueBg, $page, $key]);
        if ($update->rowCount() === 0) {
            $insert->execute([$page, $key, $valueEn, $valueBg]);
        }
    }
    $pdo->commit();
    flash("Saved changes for $page.");
    redirect(url_for('admin_dashboard') . '?page=' . rawurlencode($page));
}

if ($path === '/admin/images' && $method === 'GET') {
    admin_required();
    render('admin_images', ['slots' => site_image_slots()]);
    exit;
}

if ($method === 'POST' && preg_match('#^/admin/images/(save|reset)/([a-z0-9_]+)$#', $path, $m)) {
    admin_required();
    $action = $m[1];
    $slot = $m[2];
    $slots = site_image_slots();
    if (!isset($slots[$slot])) {
        redirect(url_for('admin_images'));
    }
    $pdo = db();
    $oldFile = (string) ($pdo->query('SELECT filename FROM site_images WHERE slot = ' . $pdo->quote($slot))->fetchColumn() ?: '');
    if ($action === 'reset') {
        if ($oldFile !== '') {
            backup_db();
            $pdo->prepare('DELETE FROM site_images WHERE slot = ?')->execute([$slot]);
            delete_site_image_file($oldFile);
            flash($slots[$slot]['label'] . ': restored the original image.');
        }
        redirect(url_for('admin_images'));
    }
    backup_db();
    $newFile = process_image_upload($_FILES['image'] ?? null, SITE_IMAGE_FOLDER, 'images/site/', (int) $slots[$slot]['max_w'], $slot . '-');
    if ($newFile === '') {
        if (empty(get_flashed_messages_peek())) {
            flash('No image selected.');
        }
        redirect(url_for('admin_images'));
    }
    $pdo->prepare('INSERT INTO site_images (slot, filename) VALUES (?, ?)
        ON CONFLICT(slot) DO UPDATE SET filename = excluded.filename')->execute([$slot, $newFile]);
    if ($oldFile !== '' && $oldFile !== $newFile) {
        delete_site_image_file($oldFile);
    }
    flash($slots[$slot]['label'] . ': image updated.');
    redirect(url_for('admin_images'));
}

if ($path === '/admin/projects' && $method === 'GET') {
    admin_required();
    render('admin_projects', ['projects' => fetch_projects(db())]);
    exit;
}

if ($path === '/admin/projects/add' && $method === 'POST') {
    admin_required();
    $titleEn = clean_text($_POST['title_en'] ?? '', 300);
    $titleBg = clean_text($_POST['title_bg'] ?? '', 300);
    if ($titleEn === '' && $titleBg === '') {
        flash('Project not added: please enter a title in English or Bulgarian.');
        redirect(url_for('admin_projects'));
    }
    $pdo = db();
    backup_db();
    $imageFilename = handle_image_upload($_FILES['image'] ?? null);
    $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM projects')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO projects
        (tag_en, tag_bg, title_en, title_bg, date_en, date_bg, desc_en, desc_bg, image_filename, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        clean_text($_POST['tag_en'] ?? '', 100),
        clean_text($_POST['tag_bg'] ?? '', 100),
        $titleEn,
        $titleBg,
        clean_text($_POST['date_en'] ?? '', 100),
        clean_text($_POST['date_bg'] ?? '', 100),
        clean_text($_POST['desc_en'] ?? '', 5000),
        clean_text($_POST['desc_bg'] ?? '', 5000),
        $imageFilename,
        $maxOrder + 1,
    ]);
    flash('Project added.');
    redirect(url_for('admin_projects'));
}

if ($method === 'POST' && preg_match('#^/admin/projects/(\d+)/move$#', $path, $m)) {
    admin_required();
    $projectId = (int) $m[1];
    $direction = (string) ($_POST['direction'] ?? '');
    $pdo = db();
    $ids = array_map('intval', $pdo->query('SELECT id FROM projects ORDER BY sort_order, id')->fetchAll(PDO::FETCH_COLUMN));
    $index = array_search($projectId, $ids, true);
    if ($index !== false && in_array($direction, ['up', 'down'], true)) {
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if (isset($ids[$swap])) {
            backup_db();
            [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];
            $set = $pdo->prepare('UPDATE projects SET sort_order = ? WHERE id = ?');
            $pdo->beginTransaction();
            foreach ($ids as $position => $id) {
                $set->execute([$position + 1, $id]);
            }
            $pdo->commit();
            flash('Project order updated.');
        }
    }
    redirect(url_for('admin_projects'));
}

if (preg_match('#^/admin/projects/(\d+)/edit$#', $path, $m)) {
    admin_required();
    $projectId = (int) $m[1];
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    if (!$project) {
        redirect(url_for('admin_projects'));
    }
    if ($method === 'POST') {
        $titleEn = clean_text($_POST['title_en'] ?? '', 300);
        $titleBg = clean_text($_POST['title_bg'] ?? '', 300);
        if ($titleEn === '' && $titleBg === '') {
            flash('Project not saved: please enter a title in English or Bulgarian.');
            redirect(url_for('admin_projects_edit', ['project_id' => $projectId]));
        }
        backup_db();
        $newImage = handle_image_upload($_FILES['image'] ?? null);
        $oldImage = (string) $project['image_filename'];
        $imageFilename = $oldImage;
        if ($newImage !== '') {
            $imageFilename = $newImage;
        } elseif (($_POST['remove_image'] ?? '') === '1') {
            $imageFilename = '';
        }
        $sortOrder = $_POST['sort_order'] ?? '';
        $sortOrder = is_numeric($sortOrder) ? max(0, (int) $sortOrder) : (int) $project['sort_order'];
        $update = $pdo->prepare('UPDATE projects SET
            tag_en = ?, tag_bg = ?, title_en = ?, title_bg = ?, date_en = ?, date_bg = ?,
            desc_en = ?, desc_bg = ?, image_filename = ?, sort_order = ? WHERE id = ?');
        $update->execute([
            clean_text($_POST['tag_en'] ?? '', 100),
            clean_text($_POST['tag_bg'] ?? '', 100),
            $titleEn,
            $titleBg,
            clean_text($_POST['date_en'] ?? '', 100),
            clean_text($_POST['date_bg'] ?? '', 100),
            clean_text($_POST['desc_en'] ?? '', 5000),
            clean_text($_POST['desc_bg'] ?? '', 5000),
            $imageFilename,
            $sortOrder,
            $projectId,
        ]);
        if ($imageFilename !== $oldImage) {
            delete_project_image($oldImage);
        }
        flash('Project updated.');
        redirect(url_for('admin_projects'));
    }
    render('admin_projects_edit', ['project' => $project]);
    exit;
}

if ($method === 'POST' && preg_match('#^/admin/projects/(\d+)/delete$#', $path, $m)) {
    admin_required();
    $projectId = (int) $m[1];
    $pdo = db();
    $stmt = $pdo->prepare('SELECT image_filename FROM projects WHERE id = ?');
    $stmt->execute([$projectId]);
    $imageFilename = $stmt->fetchColumn();
    if ($imageFilename !== false) {
        backup_db();
        $pdo->prepare('DELETE FROM projects WHERE id = ?')->execute([$projectId]);
        delete_project_image((string) $imageFilename);
    }
    flash('Project deleted.');
    redirect(url_for('admin_projects'));
}

abort_not_found();
