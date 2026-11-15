// تست دودی صفحات PHP سایت کیانی املاک — اجرا:
//   node tests/smoke.mjs
// (سرور توسعه باید از قبل بالا باشد: php -S 127.0.0.1:8088)

const BASE = process.env.BASE || 'http://127.0.0.1:8088';

let passed = 0;
let failed = 0;
const cookies = {};

function ok(name, cond, extra = '') {
    if (cond) {
        passed++;
        console.log(`  PASS  ${name}`);
    } else {
        failed++;
        console.log(`  FAIL  ${name} ${extra}`);
    }
}

function cookieHeader() {
    return Object.entries(cookies).map(([k, v]) => `${k}=${v}`).join('; ');
}

function storeCookies(res) {
    const setCookies = res.headers.getSetCookie ? res.headers.getSetCookie() : [];
    for (const sc of setCookies) {
        const pair = sc.split(';')[0];
        const eq = pair.indexOf('=');
        const name = pair.slice(0, eq).trim();
        const value = pair.slice(eq + 1);
        if (value === '') delete cookies[name];
        else cookies[name] = value;
    }
}

async function req(method, path, opts = {}) {
    const headers = {};
    if (opts.form) headers['Content-Type'] = 'application/x-www-form-urlencoded';
    if (Object.keys(cookies).length) headers['Cookie'] = cookieHeader();

    const res = await fetch(BASE + path, {
        method,
        headers,
        body: opts.form ? new URLSearchParams(opts.form) : undefined,
        redirect: opts.follow === false ? 'manual' : 'follow',
    });
    storeCookies(res);
    const text = await res.text();
    return { status: res.status, text, location: res.headers.get('location') || '' };
}

const get = (path) => req('GET', path, { follow: false }); // ریدایرکت‌ها دستی بررسی شوند
const post = (path, form, follow = true) => req('POST', path, { form, follow });
const count = (text, re) => (text.match(re) || []).length;
const csrfOf = (html) => (html.match(/name="csrf" value="([a-f0-9]+)"/) || [])[1] || '';

async function main() {
    /* ================= صفحات عمومی ================= */
    console.log('== صفحات عمومی ==');

    let r = await get('/');
    ok('GET / صفحه اصلی → 200', r.status === 200 && r.text.includes('hero__title'));

    r = await get('/index.php');
    ok('index: ۴ کارت ملک منتخب از دیتابیس', count(r.text, /class="prop-card /g) === 4,
        `count=${count(r.text, /class="prop-card /g)}`);
    ok('index: ۳ نظر مشتری از دیتابیس', count(r.text, /class="quote/g) >= 3);
    ok('index: فقط app.js (بدون data.js)', r.text.includes('assets/js/app.js') && !r.text.includes('data.js'));
    ok('index: لینک‌ها PHP شدند', !/\.html(["'#?])/.test(r.text) && r.text.includes('properties.php'));
    ok('index: فرم درخواست به leads.php', r.text.includes('action="leads.php"'));

    r = await get('/assets/css/style.css');
    ok('استاتیک CSS سرو می‌شود', r.status === 200 && r.text.length > 1000);

    r = await get('/properties.php');
    ok('properties: شمار فایل‌ها ۹', r.text.includes('۹ فایل فعال'), '');
    ok('properties: ۹ کارت + فرم فیلتر', count(r.text, /class="prop-card /g) === 9 && r.text.includes('id="filter-form"'));

    r = await get('/properties.php?type=' + encodeURIComponent('ویلا'));
    ok('فیلتر نوع=ویلا → ۴ کارت', count(r.text, /class="prop-card /g) === 4,
        `count=${count(r.text, /class="prop-card /g)}`);
    ok('ویلاها هستند و آپارتمان نیست', r.text.includes('ویلای استخردار') && !r.text.includes('آپارتمان ساحلی دو خوابه'));

    r = await get('/properties.php?price=lt300');
    ok('فیلتر قیمت<۳۰۰هزار → ۲ کارت', count(r.text, /class="prop-card /g) === 2);
    ok('استودیو هست، ویلای گران نیست', r.text.includes('استودیو مرکز شهر') && !r.text.includes('استخر بی‌نهایت'));

    r = await get('/properties.php?q=' + encodeURIComponent('پنت'));
    ok('جست‌وجوی «پنت» → ۱ نتیجه', count(r.text, /class="prop-card /g) === 1
        && r.text.includes('id="result-count">۱<'))

    r = await get('/properties.php?sort=price-asc');
    const firstTitle = (r.text.match(/prop-card__title"><a href="[^"]*">([^<]+)<\/a>/) || [])[1] || '';
    ok('مرتب‌سازی ارزان‌ترین اول', firstTitle.includes('استودیو مرکز شهر'), `first=${firstTitle}`);

    r = await get('/properties.php?type=' + encodeURIComponent('ویلا') + '&price=lt300');
    ok('حالت خالی: پیام «پیدا نشد»', r.text.includes('ملکی با این فیلترها پیدا نشد') && count(r.text, /class="prop-card /g) === 0);

    r = await get('/property.php?id=side-apartment');
    ok('جزئیات: عنوان + مشخصات + امکانات', r.status === 200
        && r.text.includes('آپارتمان ساحلی دو خوابه در سیده')
        && r.text.includes('استخر روباز') && r.text.includes('id="d-specs"'));
    ok('جزئیات: ۴ بند گالری', count(r.text, /aria-label="تصویر [۰-۹]/g) === 4,
        `count=${count(r.text, /aria-label="تصویر [۰-۹]/g)}`);
    ok('جزئیات: پیشنهادهای مشابه + فرم به leads.php',
        r.text.includes('id="similar-grid"') && r.text.includes('name="property" value="side-apartment"'));

    r = await get('/property.php?id=does-not-exist');
    ok('ملک ناموجود → 404', r.status === 404 && r.text.includes('یافت نشد'));

    /* ================= فرم‌های درخواست ================= */
    console.log('== فرم درخواست (leads) ==');

    r = await post('/leads.php', {
        name: 'سارا محمدی', phone: '09121234567', topic: 'هماهنگی بازدید',
        source: 'home', back: '/index.php', anchor: '#contact',
    }, false);
    ok('درخواست معتبر → 302 با sent=1', r.status === 302 && r.location.includes('/index.php?sent=1#contact'),
        `loc=${r.location}`);

    r = await get(r.location);
    ok('پیام موفقیت نمایش داده شد', r.text.includes('ثبت شد'));

    r = await post('/leads.php', { name: 'بدون شماره', phone: 'abc', back: '/index.php' }, false);
    ok('شماره نامعتبر → 302 با err=1', r.status === 302 && r.location.includes('err=1'), `loc=${r.location}`);

    r = await get(r.location);
    ok('پیام خطا نمایش داده شد', r.text.includes('فرم کامل نشد'));

    r = await post('/leads.php', {
        name: 'کامران رستگار', phone: '+90 552 111 22 33', topic: 'ویدیو تور ملک',
        property: 'side-apartment', source: 'property', back: '/property.php?id=side-apartment', anchor: '#lead',
    }, false);
    ok('درخواست وابسته به ملک → 302', r.status === 302 && r.location.includes('sent=1'));

    /* ================= ورود به پنل ================= */
    console.log('== احراز هویت ==');

    r = await get('/admin.php');
    ok('بدون ورود → هدایت به login', r.status === 302 && r.location.includes('login.php'), `loc=${r.location}`);

    r = await get('/login.php');
    ok('صفحه ورود با توکن CSRF', r.status === 200 && csrfOf(r.text) !== '');
    const loginCsrf = csrfOf(r.text);

    r = await post('/login.php', { csrf: loginCsrf, username: 'admin', password: 'wrong' });
    ok('رمز اشتباه → پیام خطا', r.status === 200 && r.text.includes('اشتباه است'));

    r = await post('/login.php', { csrf: loginCsrf, username: 'admin', password: 'admin123' }, false);
    ok('ورود درست → 302 به پنل', r.status === 302 && r.location.includes('admin.php'), `loc=${r.location}`);
    ok('کوکی نشست ذخیره شد', 'kiani_admin' in cookies);

    /* ================= پنل مدیریت ================= */
    console.log('== پنل مدیریت ==');

    r = await get('/admin.php');
    ok('داشبورد با آمار', r.status === 200 && r.text.includes('داشبورد')
        && r.text.includes('ملک‌های فعال') && r.text.includes('۹'));

    r = await get('/admin.php?view=properties');
    ok('جدول املاک: ۹ ردیف', count(r.text, /class="table__item"/g) === 9,
        `count=${count(r.text, /class="table__item"/g)}`);
    ok('دکمه‌های ویرایش/حذف موجود', r.text.includes('title="ویرایش"') && r.text.includes('title="حذف"'));

    r = await get('/admin.php?view=form');
    const newCsrf = csrfOf(r.text);
    ok('فرم افزودن ملک', r.status === 200 && r.text.includes('id="a-title"') && newCsrf !== '');

    r = await get('/admin.php?view=leads');
    ok('فهرست درخواست‌ها شامل ثبت‌شده‌ها', r.text.includes('سارا محمدی') && r.text.includes('کامران رستگار'));

    r = await get('/admin.php?view=settings');
    ok('فرم محتوای سایت', r.status === 200 && r.text.includes('id="s-name"'));
    const settingsCsrf = csrfOf(r.text);

    /* ---------- افزودن ملک (با ارقام فارسی در فرم) ---------- */
    r = await post('/admin.php', {
        csrf: newCsrf, action: 'save_property', id: '0', intent: 'publish',
        title: 'زمین تست خصوصی در کاش', type: 'زمین', area: 'کاش',
        price: '۶۵۰٬۰۰۰', size: '۷۰۰', rooms: '۰', status: 'فروش',
        description: 'پاراگراف اول تست.\n\nپاراگراف دوم.',
        images: 'https://images.unsplash.com/photo-1449844908441-8829872d2607?auto=format&fit=crop&w=1200&q=80',
        features: 'سند آزاد\nبر جاده اصلی',
        seo_description: 'توضیح سئوی تست.',
    }, false);
    ok('ذخیره ملک جدید → 302 saved', r.status === 302 && r.location.includes('saved=1'), `loc=${r.location}`);

    r = await get('/properties.php?type=' + encodeURIComponent('زمین'));
    ok('ملک جدید در سایت عمومی (۲ زمین)', r.text.includes('زمین تست خصوصی در کاش')
        && r.text.includes('۲ فایل فعال'), `count=${count(r.text, /class="prop-card /g)}`);

    const newSlug = (r.text.match(/property\.php\?id=([^"]+)">زمین تست خصوصی/) || [])[1] || '';
    ok('اسلاگ خودکار ملک جدید', /^p-[a-f0-9]+$/.test(newSlug), `slug=${newSlug}`);

    r = await get('/property.php?id=' + newSlug);
    ok('جزئیات ملک جدید: قیمت ذخیره‌شده', r.status === 200
        && r.text.includes('$۶۵۰٬۰۰۰') && r.text.includes('سند آزاد'),
        `status=${r.status}`);

    /* ---------- ویرایش ملک ---------- */
    r = await get('/admin.php?view=properties');
    const rowIdx = r.text.indexOf('زمین تست خصوصی در کاش');
    const rowSlice = rowIdx >= 0 ? r.text.slice(rowIdx, rowIdx + 600) : '';
    const editId = (rowSlice.match(/name="id" value="(\d+)"/) || [])[1] || '';
    ok('شناسه ملک جدید در جدول', editId !== '', `id=${editId}`);

    r = await post('/admin.php', {
        csrf: newCsrf, action: 'save_property', id: editId, intent: 'publish',
        title: 'زمین تست خصوصی در کاش', type: 'زمین', area: 'کاش',
        price: '۷۰۰٬۰۰۰', size: '۷۰۰', rooms: '۰', status: 'رزرو شده',
        description: 'ویرایش شد.', images: '', features: 'سند آزاد', seo_description: '',
    }, false);
    ok('ویرایش ملک → 302', r.status === 302 && r.location.includes('saved=1'), `loc=${r.location}`);

    r = await get('/property.php?id=' + newSlug);
    ok('ویرایش اعمال شد (قیمت + وضعیت)', r.text.includes('$۷۰۰٬۰۰۰') && r.text.includes('رزرو شده'));

    r = await get('/properties.php?type=' + encodeURIComponent('زمین'));
    ok('ترتیب پیش‌فرض با طراحی (کاش اول)', r.text.indexOf('زمین ۹۰۰ متری ساحلی در کاش') < r.text.indexOf('زمین تست خصوصی'),
        'order mismatch');

    /* ---------- رد شدن بدون CSRF ---------- */
    r = await post('/admin.php', { action: 'delete_property', id: editId }, false);
    ok('POST بدون توکن CSRF → 403', r.status === 403, `status=${r.status}`);

    /* ---------- تغییر وضعیت درخواست ---------- */
    r = await get('/admin.php?view=leads');
    const leadIdx = r.text.indexOf('کامران رستگار');
    const leadSlice = leadIdx >= 0 ? r.text.slice(leadIdx, leadIdx + 1500) : '';
    const leadId = (leadSlice.match(/name="id" value="(\d+)"/) || [])[1] || '';
    ok('شناسه درخواست در فهرست', leadId !== '', `id=${leadId}`);

    r = await post('/admin.php', {
        csrf: newCsrf, action: 'update_lead', id: leadId, status: 'در حال پیگیری',
    }, false);
    ok('تغییر وضعیت درخواست → 302', r.status === 302 && r.location.includes('lead_updated=1'));

    r = await get('/admin.php?view=leads');
    ok('وضعیت جدید در فهرست اعمال شد', r.text.includes('value="در حال پیگیری" selected'));

    /* ---------- محتوای سایت ---------- */
    r = await post('/admin.php', {
        csrf: settingsCsrf, action: 'save_settings',
        name: 'آرش کیانی (تست)', role: 'مشاور تخصصی املاک ساحلی',
        phone: '+90 552 000 00 00', phoneHref: '+905520000000',
        whatsapp: '905520000000', email: 'info@kiani-estate.com',
        address: 'چیلیوسلو، آنتالیا، ترکیه', photo: '',
    }, false);
    ok('ذخیره محتوای سایت → 302', r.status === 302 && r.location.includes('settings_saved=1'),
        `loc=${r.location}`);

    r = await get('/index.php');
    ok('تغییر محتوا در صفحه اصلی اعمال شد', r.text.includes('آرش کیانی (تست)'));

    r = await post('/admin.php', {
        csrf: settingsCsrf, action: 'save_settings',
        name: 'آرش کیانی', role: 'مشاور تخصصی املاک ساحلی',
        phone: '+90 552 000 00 00', phoneHref: '+905520000000',
        whatsapp: '905520000000', email: 'info@kiani-estate.com',
        address: 'چیلیوسلو، آنتالیا، ترکیه', photo: '',
    }, false);
    r = await get('/index.php');
    ok('بازگردانی محتوا به حالت اصلی', !r.text.includes('(تست)'));

    /* ---------- حذف ملک ---------- */
    r = await post('/admin.php', { csrf: newCsrf, action: 'delete_property', id: editId }, false);
    ok('حذف ملک → 302', r.status === 302 && r.location.includes('deleted=1'), `loc=${r.location}`);

    r = await get('/properties.php?type=' + encodeURIComponent('زمین'));
    ok('بعد از حذف: فقط ۱ زمین', count(r.text, /class="prop-card /g) === 1);

    r = await get('/property.php?id=' + newSlug);
    ok('بعد از حذف: جزئیات → 404', r.status === 404);

    /* ---------- خروج ---------- */
    r = await get('/logout.php');
    ok('خروج → 302 به login', r.status === 302 && r.location.includes('login.php'), `loc=${r.location}`);

    r = await get('/admin.php');
    ok('بعد از خروج: پنل قفل است', r.status === 302 && r.location.includes('login.php'));

    console.log(`\nنتیجه: ${passed} موفق · ${failed} ناموفق`);
    process.exit(failed === 0 ? 0 : 1);
}

main().catch((e) => {
    console.error('خطای اجرای تست:', e.message);
    process.exit(1);
});
