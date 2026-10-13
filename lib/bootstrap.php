<?php
/**
 * S3 Synergy Ltd — application bootstrap.
 * Loads environment, opens the SQLite database, seeds default content,
 * and defines the helpers used by index.php and the templates.
 */

declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
define('TEMPLATES_DIR', BASE_DIR . '/templates');
define('UPLOAD_FOLDER', BASE_DIR . '/static/images/projects');
define('SITE_IMAGE_FOLDER', BASE_DIR . '/static/images/site');
define('BACKUP_FOLDER', BASE_DIR . '/backups');
define('MAX_IMAGE_PIXELS', 25000000);
define('MAX_CONTENT_LENGTH', 8 * 1024 * 1024);

const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// ---------------------------------------------------------------------------
// Environment (.env)
// ---------------------------------------------------------------------------

function load_dotenv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name !== '' && getenv($name) === false) {
            putenv("$name=$value");
        }
    }
}

function env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

load_dotenv(BASE_DIR . '/.env');

// ---------------------------------------------------------------------------
// Database (SQLite via PDO)
// ---------------------------------------------------------------------------

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dbPath = env('DB_PATH', BASE_DIR . '/content.db');
        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        ensure_schema($pdo);
    }
    return $pdo;
}

function ensure_schema(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS content (
        id INTEGER NOT NULL PRIMARY KEY,
        page VARCHAR(50) NOT NULL,
        "key" VARCHAR(100) NOT NULL,
        value_en TEXT NOT NULL DEFAULT \'\',
        value_bg TEXT NOT NULL DEFAULT \'\',
        CONSTRAINT uq_page_key UNIQUE (page, "key")
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS projects (
        id INTEGER NOT NULL PRIMARY KEY,
        tag_en VARCHAR(100) DEFAULT \'\',
        tag_bg VARCHAR(100) DEFAULT \'\',
        title_en VARCHAR(300) DEFAULT \'\',
        title_bg VARCHAR(300) DEFAULT \'\',
        date_en VARCHAR(100) DEFAULT \'\',
        date_bg VARCHAR(100) DEFAULT \'\',
        desc_en TEXT DEFAULT \'\',
        desc_bg TEXT DEFAULT \'\',
        image_filename VARCHAR(300) DEFAULT \'\',
        sort_order INTEGER DEFAULT 0
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS site_images (
        slot VARCHAR(50) NOT NULL PRIMARY KEY,
        filename VARCHAR(300) NOT NULL DEFAULT \'\'
    )');
    seed_projects($pdo);
}

function content_defaults(): array
{
    static $defaults = null;
    if ($defaults === null) {
        $defaults = require BASE_DIR . '/content_defaults.php';
    }
    return $defaults;
}

/**
 * Load all content rows keyed by page/key, inserting any defaults that are
 * missing from the database (mirrors the original seed_defaults()).
 */
function load_content(): array
{
    static $content = null;
    if ($content !== null) {
        return $content;
    }
    $pdo = db();
    $data = [];
    foreach ($pdo->query('SELECT page, "key", value_en, value_bg FROM content') as $row) {
        $data[$row['page']][$row['key']] = ['en' => $row['value_en'], 'bg' => $row['value_bg']];
    }
    $missing = [];
    foreach (content_defaults() as $page => $fields) {
        foreach ($fields as $key => $vals) {
            if (!isset($data[$page][$key])) {
                $missing[] = [$page, $key, $vals['en'] ?? '', $vals['bg'] ?? ''];
            }
        }
    }
    if ($missing) {
        $stmt = $pdo->prepare('INSERT INTO content (page, "key", value_en, value_bg) VALUES (?, ?, ?, ?)');
        $pdo->beginTransaction();
        foreach ($missing as $row) {
            $stmt->execute($row);
            $data[$row[0]][$row[1]] = ['en' => $row[2], 'bg' => $row[3]];
        }
        $pdo->commit();
    }
    $content = $data;
    return $content;
}

function seed_projects(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn() > 0) {
        return;
    }
    $initial = [
        ['Nuclear', 'Ядрена',
         'Drain Tank Design for Plasma Melting at NPP Kozloduy',
         'Резервоар за оттичане, плазмено топене, АЕЦ Козлодуй',
         'Oct 2016 – Present', 'Окт 2016 – До момента',
         'Detailed design with innovative planning and stress calculations to optimize efficiency.',
         'Детайлен проект с иновативно планиране и изчисления за оптимизация на ефективността.',
         'images/placeholder1.jpg', 1],
        ['Nuclear', 'Ядрена',
         'Protection Shields for Pump Shafts at NPP Kozloduy',
         'Защитни екрани за вертикални валове',
         'Jun 2016 – Present', 'Юни 2016 – До момента',
         'Robust design ensuring long-term durability and regulatory compliance.',
         'Здрав проект за осигуряване на издръжливост и съответствие с нормативите.',
         'images/placeholder2.jpg', 2],
        ['Industrial', 'Индустриален',
         'Storage Bunker with Screw Conveying System',
         'Складов резервоар с винтова система',
         'May 2016 – Present', 'Май 2016 – До момента',
         'Detailed mechanical design and stress calculations for a 90 m³ bunker.',
         'Детайлен механичен проект и изчисления за резервоар с вместимост 90 м³.',
         'images/placeholder3.jpg', 3],
        ['Energy', 'Енергетика',
         'Reconstruction of Boilers KA-12 at TPP Maritsa East 2',
         'Реконструкция на котлите KA-12, ТЕЦ Марица Изток 2',
         'Jan 2016 – Present', 'Януари 2016 – До момента',
         'Improving combustion efficiency and reducing NOx emissions with an OFA system design.',
         'Подобряване на горенето и намаляване на NOx емисиите с дизайн на OFA система.',
         'images/placeholder4.jpg', 4],
        ['Energy', 'Енергетика',
         'Reconstruction of Boilers KA-7 & KA-8 at TPP Maritsa East 2',
         'Реконструкция на котлите KA-7 & KA-8, ТЕЦ Марица Изток 2',
         'Sep 2015 – Present', 'Сеп 2015 – До момента',
         'Enhanced design for fuel efficiency and significant emission reduction.',
         'Подобрен проект за ефективност на горивото и значително намаляване на емисиите.',
         'images/placeholder5.jpg', 5],
        ['Industrial', 'Индустриален',
         'Rectangular Shutter Valve for Flue Gases',
         'Правоъгълен клапан за фумни газове',
         'May 2015 – Present', 'Май 2015 – До момента',
         'Innovative valve design for improved control and regulation of flue gas flow.',
         'Иновативен дизайн на клапан за по-добър контрол и регулиране на потока на фумни газове.',
         'images/placeholder6.jpg', 6],
        ['Nuclear', 'Ядрена',
         'Reserved Suction Pipe for Decarbonized Water Pumps at NPP Kozloduy',
         'Резервна тръба за засмукване, АЕЦ Козлодуй',
         'Aug 2014 – Present', 'Авг 2014 – До момента',
         'Thermal stress calculations integrated into dedicated pipe designs for critical pump systems.',
         'Интегрирани изчисления за топлинен стрес при проектиране на тръби за критични помпени системи.',
         'images/placeholder7.jpg', 7],
        ['Nuclear', 'Ядрена',
         'Design, Delivery, and Installation of Service Water Filters',
         'Проектиране и монтаж на филтри за технологична вода',
         'Aug 2014 – Present', 'Авг 2014 – До момента',
         'Mechanical design for water filter projects in association with Atomtoploproekt Ltd.',
         'Механична част от проекти за филтри за вода, в сътрудничество с Atomtoploproekt Ltd.',
         'images/placeholder8.jpg', 8],
        ['Nuclear', 'Ядрена',
         'Process Water Filters Replacement for Units 5 & 6 at NPP Kozloduy',
         'Замяна на филтри за технологична вода, блокове 5 и 6, АЕЦ Козлодуй',
         'Mar 2014 – Present', 'Март 2014 – До момента',
         'Replacement design with updated stress calculations and specifications, implemented by Hydac GmbH.',
         'Проект за замяна на филтри с нови изчисления за якост, изпълнен от Hydac GmbH.',
         'images/placeholder9.jpg', 9],
    ];
    $stmt = $pdo->prepare('INSERT INTO projects
        (tag_en, tag_bg, title_en, title_bg, date_en, date_bg, desc_en, desc_bg, image_filename, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $pdo->beginTransaction();
    foreach ($initial as $row) {
        $stmt->execute($row);
    }
    $pdo->commit();
}

// ---------------------------------------------------------------------------
// Templating helpers
// ---------------------------------------------------------------------------

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** UTF-8 character count; falls back to byte count if mbstring is unavailable. */
function char_len(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/** Content value: DB row first, defaults as fallback. HTML-escaped. */
function cv(string $page, string $key, string $lang = 'en'): string
{
    $content = load_content();
    $value = $content[$page][$key][$lang]
        ?? content_defaults()[$page][$key][$lang]
        ?? '';
    return e($value);
}

const ROUTE_PATHS = [
    'home' => '/',
    'services' => '/services',
    'contact' => '/contact',
    'terms' => '/terms',
    'cookie_policy' => '/cookie-policy',
    'conceptual_technical_working' => '/services/conceptual-technical-working',
    'modeling_simulation' => '/services/modeling-simulation',
    'strength_analysis' => '/services/strength-analysis',
    'design_services' => '/services/design-services',
    'projects' => '/projects',
    'admin_login' => '/admin/login',
    'admin_logout' => '/admin/logout',
    'admin_dashboard' => '/admin',
    'admin_projects' => '/admin/projects',
    'admin_projects_add' => '/admin/projects/add',
    'admin_images' => '/admin/images',
];

function url_for(string $endpoint, array $params = []): string
{
    switch ($endpoint) {
        case 'static':
            return '/static/' . ltrim((string) ($params['filename'] ?? ''), '/');
        case 'admin_save':
            return '/admin/save/' . rawurlencode((string) ($params['page'] ?? ''));
        case 'admin_projects_edit':
            return '/admin/projects/' . (int) ($params['project_id'] ?? 0) . '/edit';
        case 'admin_projects_delete':
            return '/admin/projects/' . (int) ($params['project_id'] ?? 0) . '/delete';
    }
    if (!isset(ROUTE_PATHS[$endpoint])) {
        throw new InvalidArgumentException("Unknown endpoint: $endpoint");
    }
    return ROUTE_PATHS[$endpoint];
}

function render(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require TEMPLATES_DIR . '/' . $template . '.php';
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 302);
    exit;
}

// ---------------------------------------------------------------------------
// Session, CSRF, flash
// ---------------------------------------------------------------------------

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('s3session');
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid(): bool
{
    start_session();
    $sent = (string) ($_POST['csrf_token'] ?? '');
    $known = (string) ($_SESSION['csrf_token'] ?? '');
    return $known !== '' && $sent !== '' && hash_equals($known, $sent);
}

function flash(string $message): void
{
    start_session();
    $_SESSION['_flashes'][] = $message;
}

function get_flashed_messages_peek(): array
{
    start_session();
    return $_SESSION['_flashes'] ?? [];
}

function get_flashed_messages(): array
{
    start_session();
    $messages = $_SESSION['_flashes'] ?? [];
    unset($_SESSION['_flashes']);
    return $messages;
}

function is_admin(): bool
{
    start_session();
    return !empty($_SESSION['admin_logged_in']);
}

function admin_required(): void
{
    if (!is_admin()) {
        redirect(url_for('admin_login'));
    }
}

// ---------------------------------------------------------------------------
// Rate limiting (per-IP, fixed window, file-backed)
// ---------------------------------------------------------------------------

function rate_limit_exceeded(string $bucket, int $maxAttempts, int $windowSeconds): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = sys_get_temp_dir() . '/s3synergy_rl_' . md5($bucket . '|' . $ip) . '.json';
    $now = time();
    $fp = fopen($file, 'c+');
    if ($fp === false) {
        return false; // fail open rather than lock everyone out
    }
    try {
        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $timestamps = $raw ? (json_decode($raw, true) ?: []) : [];
        $timestamps = array_values(array_filter(
            $timestamps,
            static fn($ts) => is_int($ts) && $ts > $now - $windowSeconds
        ));
        if (count($timestamps) >= $maxAttempts) {
            return true;
        }
        $timestamps[] = $now;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($timestamps));
        return false;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

// ---------------------------------------------------------------------------
// Database backups
// ---------------------------------------------------------------------------

function backup_db(): void
{
    $dbPath = env('DB_PATH', BASE_DIR . '/content.db');
    if (!is_file($dbPath)) {
        return;
    }
    if (!is_dir(BACKUP_FOLDER)) {
        @mkdir(BACKUP_FOLDER, 0755, true);
    }
    if (!is_dir(BACKUP_FOLDER)) {
        return;
    }
    $htaccess = BACKUP_FOLDER . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
    }
    $existing = glob(BACKUP_FOLDER . '/content-*.db') ?: [];
    sort($existing);
    $last = $existing ? end($existing) : false;
    if ($last !== false && (time() - (int) filemtime($last)) < 120) {
        return;
    }
    @copy($dbPath, BACKUP_FOLDER . '/content-' . date('Ymd-His') . '.db');
    $existing = glob(BACKUP_FOLDER . '/content-*.db') ?: [];
    sort($existing);
    while (count($existing) > 40) {
        @unlink(array_shift($existing));
    }
}

// ---------------------------------------------------------------------------
// Site images (replaceable slots with safe fallback to the shipped defaults)
// ---------------------------------------------------------------------------

function site_image_slots(): array
{
    return [
        'logo' => ['label' => 'Logo', 'where' => 'Header of every page', 'default' => 'images/logo_S3.png', 'max_w' => 600, 'hint' => 'PNG with transparent or white background, about 600 px wide'],
        'hero_bg' => ['label' => 'Home hero background', 'where' => 'Large background behind the home page title', 'default' => 'images/img.jpg', 'max_w' => 2000, 'hint' => 'Landscape, about 1920x1080 px'],
        'services_bg' => ['label' => 'Services hero background', 'where' => 'Top banner of Services, the four service pages and Projects', 'default' => 'images/services.jpg', 'max_w' => 2000, 'hint' => 'Landscape, about 1920x600 px'],
        'home_card_1' => ['label' => 'Home card 1: Conceptual and Technical Design', 'where' => 'Home page, services row', 'default' => 'images/placeholder1.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_card_2' => ['label' => 'Home card 2: Simulation and Analysis', 'where' => 'Home page, services row', 'default' => 'images/placeholder2.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_card_3' => ['label' => 'Home card 3: Structural and Strength Analysis', 'where' => 'Home page, services row', 'default' => 'images/placeholder3.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_card_4' => ['label' => 'Home card 4: Custom Mechanical Design', 'where' => 'Home page, services row', 'default' => 'images/placeholder4.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_project_1' => ['label' => 'Home featured project 1', 'where' => 'Home page, projects row', 'default' => 'images/placeholder5.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_project_2' => ['label' => 'Home featured project 2', 'where' => 'Home page, projects row', 'default' => 'images/placeholder6.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_project_3' => ['label' => 'Home featured project 3', 'where' => 'Home page, projects row', 'default' => 'images/placeholder7.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
        'home_project_4' => ['label' => 'Home featured project 4', 'where' => 'Home page, projects row', 'default' => 'images/placeholder8.jpg', 'max_w' => 1200, 'hint' => 'About 800x600 px'],
    ];
}

function site_image_overrides(): array
{
    static $map = null;
    if ($map === null) {
        $map = [];
        try {
            foreach (db()->query('SELECT slot, filename FROM site_images') as $row) {
                $map[$row['slot']] = $row['filename'];
            }
        } catch (Throwable) {
            $map = [];
        }
    }
    return $map;
}

function site_image_override_path(string $slot): string
{
    $file = site_image_overrides()[$slot] ?? '';
    if ($file !== '' && str_starts_with($file, 'images/site/') && is_file(BASE_DIR . '/static/' . $file)) {
        return $file;
    }
    return '';
}

function site_img(string $slot): string
{
    $slots = site_image_slots();
    if (!isset($slots[$slot])) {
        return '';
    }
    $override = site_image_override_path($slot);
    $relative = $override !== '' ? $override : $slots[$slot]['default'];
    $version = '';
    if ($override !== '') {
        $mtime = @filemtime(BASE_DIR . '/static/' . $override);
        $version = $mtime ? '?v=' . $mtime : '';
    }
    return '/static/' . $relative . $version;
}

function site_css_vars(): string
{
    $override = site_image_override_path('hero_bg');
    if ($override === '') {
        return '';
    }
    return '<style>:root{--hero-bg:url("' . e(site_img('hero_bg')) . '")}</style>';
}

// ---------------------------------------------------------------------------
// Image uploads
// ---------------------------------------------------------------------------

function allowed_file(string $filename): bool
{
    $dot = strrpos($filename, '.');
    if ($dot === false) {
        return false;
    }
    return in_array(strtolower(substr($filename, $dot + 1)), ALLOWED_EXTENSIONS, true);
}

function valid_image_content(string $path): bool
{
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        return false;
    }
    $header = (string) fread($fh, 16);
    fclose($fh);
    $magics = ["\xFF\xD8\xFF", "\x89PNG\r\n\x1a\n", 'GIF87a', 'GIF89a'];
    foreach ($magics as $magic) {
        if (str_starts_with($header, $magic)) {
            return true;
        }
    }
    return str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WEBP';
}

function upload_error_text(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the allowed upload size.',
        UPLOAD_ERR_PARTIAL => 'The image was only partly uploaded. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the upload.',
        default => 'The image upload failed.',
    };
}

function process_image_upload(?array $file, string $destDir, string $relPrefix, int $maxWidth, string $namePrefix = ''): string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        flash('Image not saved: ' . upload_error_text((int) $file['error']));
        return '';
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        flash('Image not saved: invalid upload.');
        return '';
    }
    if (!allowed_file((string) $file['name']) || !valid_image_content($tmp)) {
        flash('Image not saved: only JPG, PNG, GIF and WEBP files are accepted.');
        return '';
    }
    $info = @getimagesize($tmp);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        flash('Image not saved: the file could not be read as an image.');
        return '';
    }
    [$width, $height, $type] = $info;
    if ($width * $height > MAX_IMAGE_PIXELS) {
        flash('Image not saved: the image is too large in pixels (maximum 25 megapixels).');
        return '';
    }
    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    if (!isset($extensions[$type])) {
        flash('Image not saved: unsupported image type.');
        return '';
    }
    $ext = $extensions[$type];
    if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
        flash('Image not saved: the upload folder could not be created.');
        return '';
    }
    $filename = $namePrefix . bin2hex(random_bytes(12)) . '.' . $ext;
    $target = rtrim($destDir, '/\\') . '/' . $filename;

    $stored = false;
    if ($type !== IMAGETYPE_GIF && function_exists('imagecreatefromstring')) {
        $stored = reencode_image($tmp, $target, $type, $width, $height, $maxWidth);
    }
    if (!$stored && !move_uploaded_file($tmp, $target)) {
        flash('Image not saved: the server could not write the file.');
        return '';
    }
    return $relPrefix . $filename;
}

function reencode_image(string $tmp, string $target, int $type, int $width, int $height, int $maxWidth): bool
{
    @ini_set('memory_limit', '384M');
    try {
        $data = file_get_contents($tmp);
        $img = $data === false ? false : @imagecreatefromstring($data);
        if ($img === false) {
            return false;
        }
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($tmp);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0) {
                $rotated = imagerotate($img, $angle, 0);
                if ($rotated !== false) {
                    $img = $rotated;
                    $width = imagesx($img);
                    $height = imagesy($img);
                }
            }
        }
        if ($width > $maxWidth) {
            $scaled = imagescale($img, $maxWidth, -1, IMG_BICUBIC);
            if ($scaled !== false) {
                $img = $scaled;
            }
        }
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($img, $target, 84),
            IMAGETYPE_PNG => imagepng($img, $target, 6),
            IMAGETYPE_WEBP => imagewebp($img, $target, 84),
            default => false,
        };
        return $ok && is_file($target) && filesize($target) > 0;
    } catch (Throwable) {
        if (is_file($target)) {
            @unlink($target);
        }
        return false;
    }
}

function handle_image_upload(?array $file): string
{
    return process_image_upload($file, UPLOAD_FOLDER, 'images/projects/', 1600);
}

function delete_project_image(string $imageFilename): void
{
    if ($imageFilename !== '' && str_starts_with($imageFilename, 'images/projects/') && !str_contains($imageFilename, '..')) {
        $path = BASE_DIR . '/static/' . $imageFilename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function delete_site_image_file(string $imageFilename): void
{
    if ($imageFilename !== '' && str_starts_with($imageFilename, 'images/site/') && !str_contains($imageFilename, '..')) {
        $path = BASE_DIR . '/static/' . $imageFilename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function clean_text(mixed $value, int $max): string
{
    $value = trim((string) $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max, 'UTF-8') : substr($value, 0, $max);
}
