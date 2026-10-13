# S3 Synergy Ltd Website

The company website of S3 Synergy Ltd, an engineering firm. The site is bilingual (English and Bulgarian) and comes with an admin panel, so all texts, projects and pictures can be changed without touching any code.

Live site: https://s3-synergy.com

## What is on the site

- Home page
- Services overview and four service pages: Conceptual and Technical Design, Modeling and Simulation, Strength Analysis, Design Services
- Projects portfolio
- Contact page
- Terms and Conditions
- Cookie Policy

Visitors switch between English and Bulgarian with the button in the menu. The chosen language is remembered on every page. The site works on phones, tablets and desktops.

## Admin panel

Open `/admin/login` and sign in. The admin panel has three sections:

- **Content Pages**: edit every text on the site in English and Bulgarian
- **Manage Projects**: add, edit, delete and reorder projects, and set or remove each project picture
- **Site Images**: replace the logo, the main banners and the pictures on the home page, with a button to restore the original

Uploaded pictures can be JPG, PNG, GIF or WEBP up to 8 MB. They are resized and optimised automatically. Before each change the database is copied to the `backups` folder. The last 40 copies are kept.

## Specifications

| | |
|---|---|
| Language | PHP 8.2 or newer |
| Database | SQLite, a single file created automatically |
| Required PHP extensions | `pdo_sqlite`, `mbstring` |
| Recommended extensions | `gd` for picture resizing, `exif` for photo rotation |
| Frameworks and build step | None |
| Web server | Apache (works out of the box) or Nginx (see Deployment) |

## Run it locally

1. Install PHP 8.2 or newer and enable the extensions above.
2. Create a file named `.env` in the project folder:

```
ADMIN_USERNAME=your-username
ADMIN_PASSWORD=a-long-password
```

3. Start the site from the project folder:

```
php -d upload_max_filesize=10M -d post_max_size=10M -S localhost:8000 index.php
```

4. Open http://localhost:8000 and the admin panel at http://localhost:8000/admin/login

The database file `content.db` is created on the first visit. Default texts come from `content_defaults.php`.

## Settings

All settings live in `.env`:

| Name | Meaning |
|---|---|
| `ADMIN_USERNAME` | Admin login name |
| `ADMIN_PASSWORD` | Admin password |
| `DB_PATH` | Optional. Full path to the database file. Use it to keep the database outside the website folder |

## Deployment

- Point the web server at the project folder and send every request that is not a real file to `index.php`.
- Use PHP-FPM so that `.user.ini` is applied (it sets the 10 MB upload limit).
- Create `.env` on the server. Never commit it.
- The web server must be able to write to `static/images/projects`, `static/images/site`, `backups` and to the folder that holds the database.
- Apache reads the included `.htaccess`. Nginx does not, so add rules that deny access to dotfiles, `.db`, `.json` and `.ini` files and to the folders `lib`, `templates` and `backups`, and to `content_defaults.php`.
- Serve the site over HTTPS.
- To update: pull the new code and reload PHP-FPM. The database and uploaded pictures are not part of the repository and stay untouched.

After deployment these addresses must return 403 or 404: `/.env`, `/content.db`, `/lib/bootstrap.php`, `/.git/config`, `/backups/`.

## Security

- Admin pages require a login. Login attempts are limited to 5 per minute.
- All forms are protected against forged requests.
- Uploaded pictures are checked and re-encoded. Other file types are rejected.
- Secrets, the database and the source folders are blocked from direct download.

## Project layout

```
index.php            entry point and routes
lib/bootstrap.php    settings, database, helpers
templates/           page templates
static/              styles, scripts, images
content_defaults.php default texts in both languages
.env                 secrets, created by hand
content.db           database, created automatically
backups/             automatic database copies
```

## Backups

Download `content.db` and the folders `static/images/projects` and `static/images/site` regularly. The automatic copies in `backups` live on the same disk as the site and do not protect against losing the server.

## License

Copyright S3 Synergy Ltd. All rights reserved.
