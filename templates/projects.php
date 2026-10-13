<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>S3 Synergy Ltd - Projects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Highlighted project portfolio of S3 Synergy Ltd — engineering works across energy, nuclear, and industrial sectors." />
    <meta name="keywords" content="projects, engineering, design, NPP Kozloduy, TPP Maritsa" />
    <link rel="stylesheet" href="/static/style.css">
    <script src="/static/script.js" defer></script>
    <?= site_css_vars() ?>
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="/" class="logo-link" aria-label="Go to homepage">
                <img src="<?= e(site_img('logo')) ?>" alt="S3 Synergy Ltd Logo" class="logo-img">
                <div class="logo-text">
                    <div class="company-name" data-en="<?= cv('shared', 'company_name') ?>" data-bg="<?= cv('shared', 'company_name', 'bg') ?>"><?= cv('shared', 'company_name') ?></div>
                    <div class="slogan" data-en="<?= cv('shared', 'slogan') ?>" data-bg="<?= cv('shared', 'slogan', 'bg') ?>"><?= cv('shared', 'slogan') ?></div>
                </div>
            </a>
            <button class="hamburger" id="hamburger" aria-label="Toggle navigation"><span></span><span></span><span></span></button>
            <div class="nav-right">
                <ul class="nav-links">
                    <li><a href="/" data-en="<?= cv('shared', 'nav_home') ?>" data-bg="<?= cv('shared', 'nav_home', 'bg') ?>"><?= cv('shared', 'nav_home') ?></a></li>
                    <li><a href="/services" data-en="<?= cv('shared', 'nav_services') ?>" data-bg="<?= cv('shared', 'nav_services', 'bg') ?>"><?= cv('shared', 'nav_services') ?></a></li>
                    <li><a href="/projects" class="active" data-en="<?= cv('shared', 'nav_projects') ?>" data-bg="<?= cv('shared', 'nav_projects', 'bg') ?>"><?= cv('shared', 'nav_projects') ?></a></li>
                    <li><a href="/contact" data-en="<?= cv('shared', 'nav_contact') ?>" data-bg="<?= cv('shared', 'nav_contact', 'bg') ?>"><?= cv('shared', 'nav_contact') ?></a></li>
                </ul>
                <button id="lang-switch" class="lang-btn" style="margin-left: 20px;">BG</button>
            </div>
        </div>
    </nav>

    <section class="svc-hero">
        <div class="svc-hero-bg" style="background-image: url('<?= e(site_img('services_bg')) ?>');"></div>
        <div class="svc-hero-overlay" style="background: rgba(30,45,58,0.62);"></div>
        <div class="svc-hero-content">
            <span class="svc-hero-eyebrow" data-en="<?= cv('projects', 'hero_eyebrow') ?>" data-bg="<?= cv('projects', 'hero_eyebrow', 'bg') ?>"><?= cv('projects', 'hero_eyebrow') ?></span>
            <h1 data-en="<?= cv('projects', 'hero_title') ?>" data-bg="<?= cv('projects', 'hero_title', 'bg') ?>"><?= cv('projects', 'hero_title') ?></h1>
            <p data-en="<?= cv('projects', 'hero_desc') ?>" data-bg="<?= cv('projects', 'hero_desc', 'bg') ?>"><?= cv('projects', 'hero_desc') ?></p>
        </div>
    </section>

    <section class="proj-grid-section">
        <div class="proj-grid">
            <?php foreach ($projects as $p): ?>
            <article class="proj-card">
                <div class="proj-card-img-wrap">
                    <?php if ($p['image_filename']): ?>
                    <img src="<?= e('/static/' . $p['image_filename']) ?>" alt="<?= e($p['title_en']) ?>" class="proj-card-img" loading="lazy">
                    <?php else: ?>
                    <div class="proj-card-img proj-card-img-placeholder"></div>
                    <?php endif; ?>
                </div>
                <div class="proj-card-body">
                    <span class="proj-tag" data-en="<?= e($p['tag_en']) ?>" data-bg="<?= e($p['tag_bg']) ?>"><?= e($p['tag_en']) ?></span>
                    <h2 class="proj-card-title" data-en="<?= e($p['title_en']) ?>" data-bg="<?= e($p['title_bg']) ?>"><?= e($p['title_en']) ?></h2>
                    <p class="proj-card-date" data-en="<?= e($p['date_en']) ?>" data-bg="<?= e($p['date_bg']) ?>"><?= e($p['date_en']) ?></p>
                    <p class="proj-card-desc" data-en="<?= e($p['desc_en']) ?>" data-bg="<?= e($p['desc_bg']) ?>"><?= e($p['desc_en']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="home-cta-section">
        <h2 data-en="<?= cv('projects', 'cta_title') ?>" data-bg="<?= cv('projects', 'cta_title', 'bg') ?>"><?= cv('projects', 'cta_title') ?></h2>
        <p data-en="<?= cv('projects', 'cta_desc') ?>" data-bg="<?= cv('projects', 'cta_desc', 'bg') ?>"><?= cv('projects', 'cta_desc') ?></p>
        <div class="home-cta-actions">
            <a href="/contact" class="svc-cta-btn" data-en="<?= cv('projects', 'cta_btn') ?>" data-bg="<?= cv('projects', 'cta_btn', 'bg') ?>"><?= cv('projects', 'cta_btn') ?></a>
        </div>
    </section>

    <button id="scroll-to-top" aria-label="Scroll to top">↑</button>

    <footer>
        <div class="container">
            <p data-en="<?= cv('shared', 'footer_text') ?>" data-bg="<?= cv('shared', 'footer_text', 'bg') ?>"><?= cv('shared', 'footer_text') ?></p>
            <div style="text-align: center;">
                <a href="/terms">Terms and Conditions</a>
                |
                <a href="/cookie-policy">Cookie Policy</a>
            </div>
        </div>
    </footer>

    <div id="cookie-consent" style="display: none;">
        <span>We use cookies to enhance your experience. By continuing to browse, you consent to our use of cookies.</span>
        <button id="cookie-accept">Accept</button>
    </div>
</body>
</html>
