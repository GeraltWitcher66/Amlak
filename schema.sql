-- اسکیمای دیتابیس بک‌اند کیانی املاک (SQLite)

CREATE TABLE IF NOT EXISTS admins (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    full_name     TEXT NOT NULL DEFAULT '',
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS properties (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    slug            TEXT NOT NULL UNIQUE,
    title           TEXT NOT NULL,
    type            TEXT NOT NULL,               -- ویلا / آپارتمان / پنت‌هاوس / زمین
    area            TEXT NOT NULL,               -- محدوده فعالیت
    city            TEXT NOT NULL DEFAULT 'آنتالیا',
    price           INTEGER NOT NULL DEFAULT 0,  -- قیمت کل به دلار
    size            INTEGER NOT NULL DEFAULT 0,  -- متراژ به متر مربع
    rooms           INTEGER NOT NULL DEFAULT 0,
    baths           INTEGER NOT NULL DEFAULT 0,
    year            INTEGER NOT NULL DEFAULT 0,
    status          TEXT NOT NULL DEFAULT 'فروش', -- فروش / رزرو شده / فروخته شده / پیش‌نویس
    badge           TEXT NOT NULL DEFAULT '',
    featured        INTEGER NOT NULL DEFAULT 0,
    description     TEXT NOT NULL DEFAULT '',
    seo_description TEXT NOT NULL DEFAULT '',
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS property_images (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    url         TEXT NOT NULL,
    position    INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS property_features (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    property_id INTEGER NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    feature     TEXT NOT NULL,
    position    INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS leads (               -- درخواست‌های ثبت‌شده توسط بازدیدکنندگان
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    phone       TEXT NOT NULL,
    topic       TEXT NOT NULL DEFAULT '',
    budget      TEXT NOT NULL DEFAULT '',
    message     TEXT NOT NULL DEFAULT '',
    property_id INTEGER REFERENCES properties(id) ON DELETE SET NULL,
    source      TEXT NOT NULL DEFAULT 'site',     -- home / listing / property / site
    status      TEXT NOT NULL DEFAULT 'جدید',     -- جدید / در حال پیگیری / بسته شده
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS testimonials (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    body     TEXT NOT NULL,
    author   TEXT NOT NULL,
    role     TEXT NOT NULL DEFAULT '',
    photo    TEXT NOT NULL DEFAULT '',
    position INTEGER NOT NULL DEFAULT 0,
    active   INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS settings (            -- اطلاعات عمومی سایت (agent، types، areas)
    name  TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_properties_type   ON properties(type);
CREATE INDEX IF NOT EXISTS idx_properties_area   ON properties(area);
CREATE INDEX IF NOT EXISTS idx_properties_status ON properties(status);
CREATE INDEX IF NOT EXISTS idx_images_property   ON property_images(property_id);
CREATE INDEX IF NOT EXISTS idx_features_property ON property_features(property_id);
CREATE INDEX IF NOT EXISTS idx_leads_created     ON leads(created_at DESC);
