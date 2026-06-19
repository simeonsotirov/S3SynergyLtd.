import os
import secrets
import hmac
import time
import gzip as _gzip
from functools import wraps
from flask import Flask, render_template, request, redirect, url_for, session, flash
from dotenv import load_dotenv
from sqlalchemy import create_engine, Column, Integer, String, Text, UniqueConstraint
from sqlalchemy.orm import declarative_base, Session as DBSession
from flask_wtf.csrf import CSRFProtect
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from content_defaults import DEFAULTS

load_dotenv()

app = Flask(__name__)
app.secret_key = os.getenv('SECRET_KEY', secrets.token_hex(32))
app.config['WTF_CSRF_ENABLED'] = True
app.config['SESSION_COOKIE_SECURE'] = True
app.config['SESSION_COOKIE_HTTPONLY'] = True
app.config['SESSION_COOKIE_SAMESITE'] = 'Lax'
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 2592000
app.config['MAX_CONTENT_LENGTH'] = 8 * 1024 * 1024

csrf = CSRFProtect(app)
limiter = Limiter(key_func=get_remote_address, app=app, default_limits=[], storage_uri="memory://")

DATABASE_URL = os.getenv('DATABASE_URL', 'sqlite:///content.db')
engine = create_engine(DATABASE_URL)
Base = declarative_base()

UPLOAD_FOLDER = os.path.join(os.path.dirname(__file__), 'static', 'images', 'projects')
ALLOWED_EXTENSIONS = {'jpg', 'jpeg', 'png', 'gif', 'webp'}


_IMAGE_MAGIC = [
    (b'\xff\xd8\xff', 'jpeg'),
    (b'\x89PNG\r\n\x1a\n', 'png'),
    (b'GIF87a', 'gif'),
    (b'GIF89a', 'gif'),
]


def allowed_file(filename):
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS


def valid_image_content(file_obj):
    header = file_obj.read(16)
    file_obj.seek(0)
    for magic, _ in _IMAGE_MAGIC:
        if header[:len(magic)] == magic:
            return True
    if header[:4] == b'RIFF' and header[8:12] == b'WEBP':
        return True
    return False


class Content(Base):
    __tablename__ = 'content'
    id = Column(Integer, primary_key=True)
    page = Column(String(50), nullable=False)
    key = Column(String(100), nullable=False)
    value_en = Column(Text, nullable=False, default='')
    value_bg = Column(Text, nullable=False, default='')
    __table_args__ = (UniqueConstraint('page', 'key', name='uq_page_key'),)


class Project(Base):
    __tablename__ = 'projects'
    id = Column(Integer, primary_key=True)
    tag_en = Column(String(100), default='')
    tag_bg = Column(String(100), default='')
    title_en = Column(String(300), default='')
    title_bg = Column(String(300), default='')
    date_en = Column(String(100), default='')
    date_bg = Column(String(100), default='')
    desc_en = Column(Text, default='')
    desc_bg = Column(Text, default='')
    image_filename = Column(String(300), default='')
    sort_order = Column(Integer, default=0)


Base.metadata.create_all(engine)


def seed_defaults():
    with DBSession(engine) as db:
        for page, fields in DEFAULTS.items():
            for key, vals in fields.items():
                row = db.query(Content).filter_by(page=page, key=key).first()
                if row is None:
                    db.add(Content(
                        page=page,
                        key=key,
                        value_en=vals.get('en', ''),
                        value_bg=vals.get('bg', '')
                    ))
        db.commit()


seed_defaults()


def seed_projects():
    with DBSession(engine) as db:
        if db.query(Project).count() == 0:
            initial = [
                {'tag_en': 'Nuclear', 'tag_bg': 'Ядрена',
                 'title_en': 'Drain Tank Design for Plasma Melting at NPP Kozloduy',
                 'title_bg': 'Резервоар за оттичане, плазмено топене, АЕЦ Козлодуй',
                 'date_en': 'Oct 2016 – Present', 'date_bg': 'Окт 2016 – До момента',
                 'desc_en': 'Detailed design with innovative planning and stress calculations to optimize efficiency.',
                 'desc_bg': 'Детайлен проект с иновативно планиране и изчисления за оптимизация на ефективността.',
                 'image_filename': 'images/placeholder1.jpg', 'sort_order': 1},
                {'tag_en': 'Nuclear', 'tag_bg': 'Ядрена',
                 'title_en': 'Protection Shields for Pump Shafts at NPP Kozloduy',
                 'title_bg': 'Защитни екрани за вертикални валове',
                 'date_en': 'Jun 2016 – Present', 'date_bg': 'Юни 2016 – До момента',
                 'desc_en': 'Robust design ensuring long-term durability and regulatory compliance.',
                 'desc_bg': 'Здрав проект за осигуряване на издръжливост и съответствие с нормативите.',
                 'image_filename': 'images/placeholder2.jpg', 'sort_order': 2},
                {'tag_en': 'Industrial', 'tag_bg': 'Индустриален',
                 'title_en': 'Storage Bunker with Screw Conveying System',
                 'title_bg': 'Складов резервоар с винтова система',
                 'date_en': 'May 2016 – Present', 'date_bg': 'Май 2016 – До момента',
                 'desc_en': 'Detailed mechanical design and stress calculations for a 90 m³ bunker.',
                 'desc_bg': 'Детайлен механичен проект и изчисления за резервоар с вместимост 90 м³.',
                 'image_filename': 'images/placeholder3.jpg', 'sort_order': 3},
                {'tag_en': 'Energy', 'tag_bg': 'Енергетика',
                 'title_en': 'Reconstruction of Boilers KA-12 at TPP Maritsa East 2',
                 'title_bg': 'Реконструкция на котлите KA-12, ТЕЦ Марица Изток 2',
                 'date_en': 'Jan 2016 – Present', 'date_bg': 'Януари 2016 – До момента',
                 'desc_en': 'Improving combustion efficiency and reducing NOx emissions with an OFA system design.',
                 'desc_bg': 'Подобряване на горенето и намаляване на NOx емисиите с дизайн на OFA система.',
                 'image_filename': 'images/placeholder4.jpg', 'sort_order': 4},
                {'tag_en': 'Energy', 'tag_bg': 'Енергетика',
                 'title_en': 'Reconstruction of Boilers KA-7 & KA-8 at TPP Maritsa East 2',
                 'title_bg': 'Реконструкция на котлите KA-7 & KA-8, ТЕЦ Марица Изток 2',
                 'date_en': 'Sep 2015 – Present', 'date_bg': 'Сеп 2015 – До момента',
                 'desc_en': 'Enhanced design for fuel efficiency and significant emission reduction.',
                 'desc_bg': 'Подобрен проект за ефективност на горивото и значително намаляване на емисиите.',
                 'image_filename': 'images/placeholder5.jpg', 'sort_order': 5},
                {'tag_en': 'Industrial', 'tag_bg': 'Индустриален',
                 'title_en': 'Rectangular Shutter Valve for Flue Gases',
                 'title_bg': 'Правоъгълен клапан за фумни газове',
                 'date_en': 'May 2015 – Present', 'date_bg': 'Май 2015 – До момента',
                 'desc_en': 'Innovative valve design for improved control and regulation of flue gas flow.',
                 'desc_bg': 'Иновативен дизайн на клапан за по-добър контрол и регулиране на потока на фумни газове.',
                 'image_filename': 'images/placeholder6.jpg', 'sort_order': 6},
                {'tag_en': 'Nuclear', 'tag_bg': 'Ядрена',
                 'title_en': 'Reserved Suction Pipe for Decarbonized Water Pumps at NPP Kozloduy',
                 'title_bg': 'Резервна тръба за засмукване, АЕЦ Козлодуй',
                 'date_en': 'Aug 2014 – Present', 'date_bg': 'Авг 2014 – До момента',
                 'desc_en': 'Thermal stress calculations integrated into dedicated pipe designs for critical pump systems.',
                 'desc_bg': 'Интегрирани изчисления за топлинен стрес при проектиране на тръби за критични помпени системи.',
                 'image_filename': 'images/placeholder7.jpg', 'sort_order': 7},
                {'tag_en': 'Nuclear', 'tag_bg': 'Ядрена',
                 'title_en': 'Design, Delivery, and Installation of Service Water Filters',
                 'title_bg': 'Проектиране и монтаж на филтри за технологична вода',
                 'date_en': 'Aug 2014 – Present', 'date_bg': 'Авг 2014 – До момента',
                 'desc_en': 'Mechanical design for water filter projects in association with Atomtoploproekt Ltd.',
                 'desc_bg': 'Механична част от проекти за филтри за вода, в сътрудничество с Atomtoploproekt Ltd.',
                 'image_filename': 'images/placeholder8.jpg', 'sort_order': 8},
                {'tag_en': 'Nuclear', 'tag_bg': 'Ядрена',
                 'title_en': 'Process Water Filters Replacement for Units 5 & 6 at NPP Kozloduy',
                 'title_bg': 'Замяна на филтри за технологична вода, блокове 5 и 6, АЕЦ Козлодуй',
                 'date_en': 'Mar 2014 – Present', 'date_bg': 'Март 2014 – До момента',
                 'desc_en': 'Replacement design with updated stress calculations and specifications, implemented by Hydac GmbH.',
                 'desc_bg': 'Проект за замяна на филтри с нови изчисления за якост, изпълнен от Hydac GmbH.',
                 'image_filename': 'images/placeholder9.jpg', 'sort_order': 9},
            ]
            for i, p in enumerate(initial):
                db.add(Project(**p))
            db.commit()


seed_projects()


def _delete_project_image(image_filename: str):
    if image_filename and image_filename.startswith('images/projects/'):
        path = os.path.join(os.path.dirname(__file__), 'static', image_filename)
        try:
            os.remove(path)
        except OSError:
            pass


_content_cache: dict = {}
_content_cache_ts: float = 0.0
_CONTENT_CACHE_TTL = 60


def _invalidate_content_cache():
    global _content_cache_ts
    _content_cache_ts = 0.0


def load_content():
    global _content_cache, _content_cache_ts
    now = time.monotonic()
    if _content_cache and now - _content_cache_ts < _CONTENT_CACHE_TTL:
        return _content_cache
    data: dict = {}
    with DBSession(engine) as db:
        for row in db.query(Content).all():
            if row.page not in data:
                data[row.page] = {}
            data[row.page][row.key] = {'en': row.value_en, 'bg': row.value_bg}
    _content_cache = data
    _content_cache_ts = now
    return data


_COMPRESSIBLE = frozenset({
    'text/html', 'text/css', 'text/plain',
    'application/javascript', 'application/json', 'image/svg+xml',
})
_COMPRESS_MIN_BYTES = 500


@app.after_request
def compress_response(response):
    if (response.direct_passthrough
            or response.status_code < 200
            or response.status_code >= 300
            or 'Content-Encoding' in response.headers):
        return response
    if 'gzip' not in request.headers.get('Accept-Encoding', ''):
        return response
    mimetype = response.content_type.split(';')[0].strip()
    if mimetype not in _COMPRESSIBLE:
        return response
    data = response.get_data()
    if len(data) < _COMPRESS_MIN_BYTES:
        return response
    compressed = _gzip.compress(data, compresslevel=6)
    response.set_data(compressed)
    response.headers['Content-Encoding'] = 'gzip'
    response.headers['Vary'] = 'Accept-Encoding'
    response.headers['Content-Length'] = len(compressed)
    return response


@app.after_request
def set_security_headers(response):
    response.headers['X-Frame-Options'] = 'SAMEORIGIN'
    response.headers['X-Content-Type-Options'] = 'nosniff'
    response.headers['Referrer-Policy'] = 'strict-origin-when-cross-origin'
    response.headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains'
    response.headers['Content-Security-Policy'] = (
        "default-src 'self'; "
        "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
        "font-src 'self' https://fonts.gstatic.com; "
        "img-src 'self' data:; "
        "connect-src 'self';"
    )
    return response


@app.context_processor
def inject_cv():
    content = load_content()

    def cv(page, key, lang='en'):
        try:
            return content[page][key][lang]
        except KeyError:
            try:
                return DEFAULTS[page][key][lang]
            except KeyError:
                return ''

    return dict(cv=cv)


def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if not session.get('admin_logged_in'):
            return redirect(url_for('admin_login'))
        return f(*args, **kwargs)
    return decorated


@app.route('/admin/login', methods=['GET', 'POST'])
@limiter.limit("5 per minute")
def admin_login():
    if request.method == 'POST':
        username = request.form.get('username', '')
        password = request.form.get('password', '')
        expected_user = os.getenv('ADMIN_USERNAME', '')
        expected_pass = os.getenv('ADMIN_PASSWORD', '')
        user_ok = hmac.compare_digest(username.encode(), expected_user.encode())
        pass_ok = hmac.compare_digest(password.encode(), expected_pass.encode())
        if user_ok and pass_ok:
            session['admin_logged_in'] = True
            return redirect(url_for('admin_dashboard'))
        flash('Invalid credentials.')
    return render_template('admin_login.html')


@app.route('/admin/logout', methods=['POST'])
@admin_required
def admin_logout():
    session.pop('admin_logged_in', None)
    return redirect(url_for('admin_login'))


@app.route('/admin')
@admin_required
def admin_dashboard():
    content = load_content()
    pages = list(DEFAULTS.keys())
    active_page = request.args.get('page', pages[0])
    if active_page not in DEFAULTS:
        active_page = pages[0]
    return render_template('admin.html', content=content, defaults=DEFAULTS, pages=pages, active_page=active_page)


@app.route('/admin/save/<page>', methods=['POST'])
@admin_required
def admin_save(page):
    if page not in DEFAULTS:
        return redirect(url_for('admin_dashboard'))
    with DBSession(engine) as db:
        for key in DEFAULTS[page]:
            value_en = request.form.get(f'{key}__en', '')
            value_bg = request.form.get(f'{key}__bg', '')
            row = db.query(Content).filter_by(page=page, key=key).first()
            if row:
                row.value_en = value_en
                row.value_bg = value_bg
            else:
                db.add(Content(page=page, key=key, value_en=value_en, value_bg=value_bg))
        db.commit()
    _invalidate_content_cache()
    flash(f'Saved changes for {page}.')
    return redirect(url_for('admin_dashboard') + f'?page={page}')


@app.route('/admin/projects')
@admin_required
def admin_projects():
    with DBSession(engine) as db:
        projects = db.query(Project).order_by(Project.sort_order, Project.id).all()
        projects = [{'id': p.id, 'tag_en': p.tag_en, 'tag_bg': p.tag_bg,
                     'title_en': p.title_en, 'title_bg': p.title_bg,
                     'date_en': p.date_en, 'date_bg': p.date_bg,
                     'desc_en': p.desc_en, 'desc_bg': p.desc_bg,
                     'image_filename': p.image_filename, 'sort_order': p.sort_order} for p in projects]
    return render_template('admin_projects.html', projects=projects)


@app.route('/admin/projects/add', methods=['POST'])
@admin_required
def admin_projects_add():
    image_filename = ''
    file = request.files.get('image')
    if file and file.filename and allowed_file(file.filename) and valid_image_content(file):
        filename = secrets.token_hex(16) + '.' + file.filename.rsplit('.', 1)[1].lower()
        os.makedirs(UPLOAD_FOLDER, exist_ok=True)
        file.save(os.path.join(UPLOAD_FOLDER, filename))
        image_filename = f'images/projects/{filename}'
    with DBSession(engine) as db:
        max_order = db.query(Project).count()
        db.add(Project(
            tag_en=request.form.get('tag_en', ''),
            tag_bg=request.form.get('tag_bg', ''),
            title_en=request.form.get('title_en', ''),
            title_bg=request.form.get('title_bg', ''),
            date_en=request.form.get('date_en', ''),
            date_bg=request.form.get('date_bg', ''),
            desc_en=request.form.get('desc_en', ''),
            desc_bg=request.form.get('desc_bg', ''),
            image_filename=image_filename,
            sort_order=max_order + 1,
        ))
        db.commit()
    flash('Project added.')
    return redirect(url_for('admin_projects'))


@app.route('/admin/projects/<int:project_id>/edit', methods=['GET', 'POST'])
@admin_required
def admin_projects_edit(project_id):
    with DBSession(engine) as db:
        p = db.query(Project).filter_by(id=project_id).first()
        if not p:
            return redirect(url_for('admin_projects'))
        if request.method == 'POST':
            file = request.files.get('image')
            if file and file.filename and allowed_file(file.filename) and valid_image_content(file):
                old_image = p.image_filename
                filename = secrets.token_hex(16) + '.' + file.filename.rsplit('.', 1)[1].lower()
                os.makedirs(UPLOAD_FOLDER, exist_ok=True)
                file.save(os.path.join(UPLOAD_FOLDER, filename))
                p.image_filename = f'images/projects/{filename}'
                _delete_project_image(old_image)
            p.tag_en = request.form.get('tag_en', '')
            p.tag_bg = request.form.get('tag_bg', '')
            p.title_en = request.form.get('title_en', '')
            p.title_bg = request.form.get('title_bg', '')
            p.date_en = request.form.get('date_en', '')
            p.date_bg = request.form.get('date_bg', '')
            p.desc_en = request.form.get('desc_en', '')
            p.desc_bg = request.form.get('desc_bg', '')
            try:
                p.sort_order = int(request.form.get('sort_order', p.sort_order))
            except (ValueError, TypeError):
                pass
            db.commit()
            flash('Project updated.')
            return redirect(url_for('admin_projects'))
        proj = {'id': p.id, 'tag_en': p.tag_en, 'tag_bg': p.tag_bg,
                'title_en': p.title_en, 'title_bg': p.title_bg,
                'date_en': p.date_en, 'date_bg': p.date_bg,
                'desc_en': p.desc_en, 'desc_bg': p.desc_bg,
                'image_filename': p.image_filename, 'sort_order': p.sort_order}
    return render_template('admin_projects_edit.html', project=proj)


@app.route('/admin/projects/<int:project_id>/delete', methods=['POST'])
@admin_required
def admin_projects_delete(project_id):
    with DBSession(engine) as db:
        p = db.query(Project).filter_by(id=project_id).first()
        if p:
            image_filename = p.image_filename
            db.delete(p)
            db.commit()
            _delete_project_image(image_filename)
    flash('Project deleted.')
    return redirect(url_for('admin_projects'))


@app.route('/')
def home():
    return render_template('home.html')


@app.route('/services')
def services():
    return render_template('services.html')


@app.route('/contact')
def contact():
    return render_template('contact.html')


@app.route('/terms')
def terms():
    return render_template('terms.html')


@app.route('/cookie-policy')
def cookie_policy():
    return render_template('cookie_policy.html')


@app.route('/services/conceptual-technical-working')
def conceptual_technical_working():
    return render_template('conceptual_technical_working.html')


@app.route('/services/modeling-simulation')
def modeling_simulation():
    return render_template('modeling_simulation.html')


@app.route('/services/strength-analysis')
def strength_analysis():
    return render_template('strength_analysis.html')


@app.route('/services/design-services')
def design_services():
    return render_template('design_services.html')


@app.route('/projects')
def projects():
    with DBSession(engine) as db:
        proj_list = db.query(Project).order_by(Project.sort_order, Project.id).all()
        proj_list = [{'tag_en': p.tag_en, 'tag_bg': p.tag_bg,
                      'title_en': p.title_en, 'title_bg': p.title_bg,
                      'date_en': p.date_en, 'date_bg': p.date_bg,
                      'desc_en': p.desc_en, 'desc_bg': p.desc_bg,
                      'image_filename': p.image_filename} for p in proj_list]
    return render_template('projects.html', projects=proj_list)


@app.errorhandler(404)
def not_found(e):
    return render_template('404.html'), 404


@app.errorhandler(413)
def too_large(e):
    return render_template('413.html'), 413


@app.errorhandler(500)
def server_error(e):
    return render_template('500.html'), 500


if __name__ == '__main__':
    app.run(debug=False, port=5001)
