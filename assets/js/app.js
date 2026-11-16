/* ==========================================================================
   کیانی املاک — رفتارهای رابط کاربری
   محتوا (املاک، نظرات، جزئیات و جدول مدیریت) توسط PHP از دیتابیس رندر می‌شود؛
   این فایل فقط انیمیشن‌ها، منو، گالری و ارسال خودکار فرم‌ها را کنترل می‌کند.
   ========================================================================== */

const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

/* ارقام فارسی برای خروجی‌های ساخته‌شده در جاوااسکریپت */
const faDigits = (v) => String(v).replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[d]);

/* ---------- toast ---------- */
let toastTimer;
function toast(msg) {
  let el = $(".toast");
  if (!el) {
    el = document.createElement("div");
    el.className = "toast";
    document.body.appendChild(el);
  }
  el.textContent = msg;
  requestAnimationFrame(() => el.classList.add("is-on"));
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove("is-on"), 3800);
}

/* ---------- header ---------- */
function initHeader() {
  const header = $(".header");
  if (!header) return;
  const solid = () => header.classList.toggle("is-solid", window.scrollY > 40);
  solid();
  window.addEventListener("scroll", solid, { passive: true });
}

/* ---------- mobile nav ---------- */
function initMobileNav() {
  const drawer = $(".mobile-nav");
  const burger = $(".burger");
  if (!drawer || !burger) return;
  const open = (on) => {
    drawer.classList.toggle("is-open", on);
    document.body.style.overflow = on ? "hidden" : "";
  };
  burger.addEventListener("click", () => open(true));
  $(".mobile-nav__close", drawer)?.addEventListener("click", () => open(false));
  $$("a", drawer).forEach((a) => a.addEventListener("click", () => open(false)));
  document.addEventListener("keydown", (e) => e.key === "Escape" && open(false));
}

/* ---------- reveal on scroll ---------- */
function initReveal() {
  const items = $$(".reveal");
  if (!items.length) return;
  if (!("IntersectionObserver" in window)) {
    items.forEach((el) => el.classList.add("is-in"));
    return;
  }
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add("is-in");
          io.unobserve(e.target);
        }
      });
    },
    { rootMargin: "0px 0px -8% 0px", threshold: 0.08 }
  );
  items.forEach((el) => io.observe(el));
}

/* ---------- count-up ---------- */
function initCounters() {
  const els = $$("[data-count]");
  if (!els.length) return;
  const run = (el) => {
    const target = parseInt(el.dataset.count, 10);
    const suffix = el.dataset.suffix || "";
    const dur = 1400;
    const start = performance.now();
    const tick = (now) => {
      const p = Math.min((now - start) / dur, 1);
      const eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString("fa-IR") + suffix;
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };
  if (!("IntersectionObserver" in window)) return els.forEach(run);
  const io = new IntersectionObserver(
    (entries) => entries.forEach((e) => e.isIntersecting && (run(e.target), io.unobserve(e.target))),
    { threshold: 0.5 }
  );
  els.forEach((el) => io.observe(el));
}

/* ---------- علاقه‌مندی‌ها: ذخیره در مرورگر + همگام‌سازی همه کارت‌های همان ملک ---------- */
const FAV_KEY = "kiani:favs";

function readFavs() {
  try {
    const raw = JSON.parse(localStorage.getItem(FAV_KEY) || "[]");
    return Array.isArray(raw) ? new Set(raw.filter((s) => typeof s === "string" && s !== "")) : new Set();
  } catch {
    return new Set();
  }
}

function writeFavs(set) {
  try {
    localStorage.setItem(FAV_KEY, JSON.stringify([...set]));
  } catch {
    /* حالت مرور خصوصی: تغییر فقط در همین صفحه می‌ماند */
  }
}

function paintFav(btn, on) {
  btn.classList.toggle("is-on", on);
  btn.setAttribute("aria-pressed", on ? "true" : "false");
}

function bindFavs(scope = document) {
  const favs = readFavs();
  $$(".prop-card__fav", scope).forEach((btn) => {
    const slug = btn.dataset.slug || "";
    if (!slug) return;
    paintFav(btn, favs.has(slug));

    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const on = !favs.has(slug);
      if (on) favs.add(slug);
      else favs.delete(slug);
      writeFavs(favs);
      $$(".prop-card__fav").filter((b) => b.dataset.slug === slug).forEach((b) => paintFav(b, on));
      toast(on ? "ملک به علاقه‌مندی‌ها اضافه شد" : "از علاقه‌مندی‌ها حذف شد");
    });
  });
}

/* ---------- گالری تصاویر صفحه جزئیات ---------- */
function initGallery() {
  const stage = $("#gallery-main");
  if (!stage) return;

  const mainImg = $("#gallery-main-img");
  const countEl = $("#gallery-count");
  const thumbs = $$("#gallery-thumbs button");
  if (!mainImg || !thumbs.length) return;

  let idx = 0;
  const paint = () => {
    const btn = thumbs[idx];
    const full = btn.dataset.full || $("img", btn)?.src;
    if (full) mainImg.src = full;
    if (countEl) countEl.textContent = faDigits(`${idx + 1} / ${thumbs.length}`);
    thumbs.forEach((b, i) => b.classList.toggle("is-active", i === idx));
  };

  thumbs.forEach((b, i) => b.addEventListener("click", () => ((idx = i), paint())));
  $("#gallery-prev")?.addEventListener("click", () => ((idx = (idx - 1 + thumbs.length) % thumbs.length), paint()));
  $("#gallery-next")?.addEventListener("click", () => ((idx = (idx + 1) % thumbs.length), paint()));
}

/* ---------- فرم فیلترهای صفحه املاک: ارسال خودکار ---------- */
function initFilters() {
  const form = $("#filter-form");
  if (!form) return;
  // در نسخه استاتیک (GitHub Pages) فیلتر در همان صفحه و بدون سرور انجام می‌شود
  if (form.dataset.static !== undefined) return;

  // تغییر هر فهرست = اعمال فیلتر (سرور نتایج را دوباره می‌سازد)
  $$("select", form).forEach((sel) =>
    sel.addEventListener("change", () => form.submit())
  );

  // چیپ‌های نوع ملک
  const typeSel = form.querySelector('[name="type"]');
  $$("#quick-chips .chip").forEach((chip) =>
    chip.addEventListener("click", () => {
      if (typeSel) typeSel.value = chip.dataset.type || "";
      form.submit();
    })
  );

  // جست‌وجو با Enter (ارسال طبیعی فرم GET)
}

/* ---------- انتخاب وضعیت درخواست در پنل مدیریت: ارسال خودکار ---------- */
function initAutoSubmit() {
  $$("select[data-autosubmit]").forEach((sel) =>
    sel.addEventListener("change", () => sel.form && sel.form.submit())
  );
}

/* ---------- ماشین‌حساب بازده سرمایه‌گذاری ---------- */
function initRoiCalc() {
  const root = $("#roi-calc");
  if (!root) return;
  const price = Number(root.dataset.price || 0);
  if (!price) return;

  const rent = $("#roi-rent");
  const cost = $("#roi-cost");
  const vacancy = $("#roi-vacancy");
  const grossEl = $("#roi-gross");
  const netEl = $("#roi-net");
  const yieldEl = $("#roi-yield");
  if (!rent || !cost || !vacancy || !grossEl || !netEl || !yieldEl) return;

  // ورودی ممکن است با ارقام فارسی/عربی یا جداکننده هزارگان نوشته شود
  const toNumber = (el) => {
    const normalized = String(el.value)
      .replace(/[۰-۹]/g, (d) => String("۰۱۲۳۴۵۶۷۸۹".indexOf(d)))
      .replace(/[٠-٩]/g, (d) => String("٠١٢٣٤٥٦٧٨٩".indexOf(d)))
      .replace(/[^\d.]/g, "");
    const n = Number(normalized);
    return Number.isFinite(n) ? n : 0;
  };

  const money = (v) => "$" + Math.round(v).toLocaleString("fa-IR");
  const percent = (v) => v.toLocaleString("fa-IR", { maximumFractionDigits: 1 }) + "٪";

  const paint = () => {
    const monthlyRent = toNumber(rent);
    if (monthlyRent <= 0 && toNumber(cost) <= 0) {
      grossEl.textContent = "—";
      netEl.textContent = "—";
      yieldEl.textContent = "—";
      return;
    }
    const emptyRate = Math.min(Math.max(toNumber(vacancy), 0), 100);
    const gross = monthlyRent * 12 * (1 - emptyRate / 100);
    const net = gross - toNumber(cost);
    grossEl.textContent = money(gross);
    netEl.textContent = money(net);
    yieldEl.textContent = net > 0 ? percent((net / price) * 100) : percent(0);
  };

  [rent, cost, vacancy].forEach((el) => el.addEventListener("input", paint));
  paint();
}

/* ---------- boot ---------- */
document.addEventListener("DOMContentLoaded", () => {
  initHeader();
  initMobileNav();
  initGallery();
  initFilters();
  initAutoSubmit();
  bindFavs(document);
  initReveal();
  initCounters();
  initRoiCalc();
});
