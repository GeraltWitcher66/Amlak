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

/* ---------- دکمه علاقه‌مندی روی کارت‌ها ---------- */
function bindFavs(scope = document) {
  $$(".prop-card__fav", scope).forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      btn.classList.toggle("is-on");
      toast(btn.classList.contains("is-on") ? "ملک به علاقه‌مندی‌ها اضافه شد" : "از علاقه‌مندی‌ها حذف شد");
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
});
