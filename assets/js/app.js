/* ==========================================================================
   کیانی املاک — رفتارهای رابط کاربری (design-only، بدون بک‌اند)
   ========================================================================== */

/* ---------- helpers ---------- */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

const ico = {
  pin: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 1 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
  area: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3h18v18H3z"/><path d="M3 9h6V3"/></svg>',
  bed: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>',
  bath: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6 6.5 3.5a1.5 1.5 0 0 0-1-.5C4.686 3 4 3.686 4 4.5V17a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5H2"/><path d="M4 17h16a4 4 0 0 0-4-4"/></svg>',
  heart: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8Z"/></svg>',
  arrow: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>',
  search: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
  phone: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>',
  chat: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-4-.9L3 21l1.9-4.9A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5Z"/></svg>',
};

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

/* ---------- lead forms (design only, no submit) ---------- */
function initForms() {
  $$("form[data-lead]").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const phone = form.querySelector('input[type="tel"]');
      if (phone && phone.value.trim().length < 6) {
        phone.focus();
        toast("لطفاً شماره تماس معتبر وارد کنید");
        return;
      }
      const success = form.querySelector(".form-success");
      if (success) success.classList.add("is-on");
      form.querySelectorAll("input, select, textarea").forEach((f) => {
        if (f.type !== "submit" && f.type !== "button") f.value = "";
      });
      toast("درخواست شما ثبت شد؛ تا کمتر از ۲۴ ساعت تماس می‌گیرم.");
    });
  });
}

/* ---------- property card ---------- */
function cardHTML(p) {
  const specs = [];
  if (p.rooms) specs.push(`${ico.bed} <b>${p.rooms}</b> خواب`);
  specs.push(`${ico.area} <b>${p.size}</b> متر`);
  if (p.year) specs.push(`<b>${p.year}</b> ساخت`);
  return `
  <article class="prop-card reveal">
    <div class="prop-card__media">
      <img src="${IMG(p.images[0], 900)}" alt="${p.title}" loading="lazy" width="900" height="675">
      <div class="prop-card__badges">
        <span class="pill pill--coral">${p.type}</span>
        ${p.badge ? `<span class="pill">${p.badge}</span>` : ""}
      </div>
      <button class="prop-card__fav" type="button" aria-label="ذخیره ملک">${ico.heart}</button>
    </div>
    <div class="prop-card__body">
      <div class="prop-card__loc">${ico.pin} ${p.area}، ${p.city}</div>
      <h3 class="prop-card__title"><a href="property.html?id=${p.id}">${p.title}</a></h3>
      <div class="prop-card__specs">${specs.join("<span>·</span>")}</div>
      <div class="prop-card__foot">
        <div class="price">${formatPrice(p.price)}<small>قیمت کل</small></div>
        <a class="card-link" href="property.html?id=${p.id}" aria-label="مشاهده جزئیات">${ico.arrow}</a>
      </div>
    </div>
  </article>`;
}

function bindFavs(scope = document) {
  $$(".prop-card__fav", scope).forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      btn.classList.toggle("is-on");
      toast(btn.classList.contains("is-on") ? "ملک به علاقه‌مندی‌ها اضافه شد" : "از علاقه‌مندی‌ها حذف شد");
    });
  });
}

/* ---------- home ---------- */
function initHome() {
  const featured = $("#featured-grid");
  if (featured) {
    featured.innerHTML = PROPERTIES.filter((p) => p.featured).slice(0, 4).map(cardHTML).join("");
    bindFavs(featured);
  }

  const quotes = $("#quotes-grid");
  if (quotes) {
    quotes.innerHTML = TESTIMONIALS.map(
      (t) => `
      <figure class="quote reveal">
        <div class="quote__stars">★★★★★</div>
        <p>«${t.text}»</p>
        <figcaption class="quote__who">
          <img src="${IMG(t.photo, 160)}" alt="${t.name}" loading="lazy" width="46" height="46">
          <div><b>${t.name}</b><span>${t.role}</span></div>
        </figcaption>
      </figure>`
    ).join("");
  }
}

/* ---------- listing page ---------- */
function initListing() {
  const grid = $("#listing-grid");
  if (!grid) return;

  const els = {
    q: $("#f-q"),
    type: $("#f-type"),
    area: $("#f-area"),
    price: $("#f-price"),
    size: $("#f-size"),
    sort: $("#f-sort"),
    count: $("#result-count"),
    empty: $("#empty-state"),
    reset: $("#reset-filters"),
    chips: $("#quick-chips"),
  };

  const priceMatch = (p, v) => {
    if (!v) return true;
    if (v === "lt300") return p.price < 300000;
    if (v === "300-600") return p.price >= 300000 && p.price < 600000;
    if (v === "600-1000") return p.price >= 600000 && p.price < 1000000;
    if (v === "gt1000") return p.price >= 1000000;
    return true;
  };
  const sizeMatch = (p, v) => {
    if (!v) return true;
    if (v === "lt100") return p.size < 100;
    if (v === "100-250") return p.size >= 100 && p.size < 250;
    if (v === "gt250") return p.size >= 250;
    return true;
  };

  function render() {
    const q = (els.q?.value || "").trim();
    const type = els.type?.value || "";
    const area = els.area?.value || "";
    const price = els.price?.value || "";
    const size = els.size?.value || "";
    const sort = els.sort?.value || "default";

    let list = PROPERTIES.filter((p) => {
      const hay = `${p.title} ${p.type} ${p.area} ${p.city}`.toLowerCase();
      return (
        (!q || hay.includes(q.toLowerCase())) &&
        (!type || p.type === type) &&
        (!area || p.area === area) &&
        priceMatch(p, price) &&
        sizeMatch(p, size)
      );
    });

    if (sort === "price-asc") list.sort((a, b) => a.price - b.price);
    if (sort === "price-desc") list.sort((a, b) => b.price - a.price);
    if (sort === "size-desc") list.sort((a, b) => b.size - a.size);

    grid.innerHTML = list.map(cardHTML).join("");
    bindFavs(grid);
    if (els.count) els.count.textContent = list.length.toLocaleString("fa-IR");
    if (els.empty) els.empty.hidden = list.length > 0;
    grid.hidden = list.length === 0;
    $$(".reveal", grid).forEach((el) => el.classList.add("is-in"));

    $$("#quick-chips .chip").forEach((c) => c.classList.toggle("is-active", (c.dataset.type || "") === type));
  }

  ["q", "type", "area", "price", "size", "sort"].forEach((k) => {
    const el = els[k];
    if (!el) return;
    el.addEventListener(el.tagName === "INPUT" ? "input" : "change", render);
  });

  $$("#quick-chips .chip").forEach((chip) =>
    chip.addEventListener("click", () => {
      if (els.type) els.type.value = chip.dataset.type || "";
      render();
    })
  );

  els.reset?.addEventListener("click", () => {
    ["q", "type", "area", "price", "size"].forEach((k) => {
      if (els[k]) els[k].value = "";
    });
    if (els.sort) els.sort.value = "default";
    render();
    toast("فیلترها پاک شد");
  });

  render();
}

/* ---------- property detail ---------- */
function initDetail() {
  const stage = $("#gallery-main");
  if (!stage) return;

  const id = new URLSearchParams(location.search).get("id");
  const p = PROPERTIES.find((x) => x.id === id) || PROPERTIES[0];

  document.title = `${p.title} | ${AGENT.brand}`;

  // gallery
  let idx = 0;
  const mainImg = $("#gallery-main-img");
  const countEl = $("#gallery-count");
  const thumbs = $("#gallery-thumbs");

  const paint = () => {
    mainImg.src = IMG(p.images[idx], 1600);
    mainImg.alt = `${p.title} — تصویر ${idx + 1}`;
    if (countEl) countEl.textContent = `${idx + 1} / ${p.images.length}`;
    $$("button", thumbs).forEach((b, i) => b.classList.toggle("is-active", i === idx));
  };

  thumbs.innerHTML = p.images
    .map(
      (im, i) =>
        `<button type="button" aria-label="تصویر ${i + 1}" class="${i === 0 ? "is-active" : ""}"><img src="${IMG(im, 300)}" alt="" loading="lazy"></button>`
    )
    .join("");
  $$("button", thumbs).forEach((b, i) => b.addEventListener("click", () => ((idx = i), paint())));
  $("#gallery-prev")?.addEventListener("click", () => ((idx = (idx - 1 + p.images.length) % p.images.length), paint()));
  $("#gallery-next")?.addEventListener("click", () => ((idx = (idx + 1) % p.images.length), paint()));
  paint();

  // head + breadcrumb
  $("#d-type").textContent = p.type;
  $("#d-badge").textContent = p.badge || p.status;
  $("#d-title").textContent = p.title;
  $("#d-loc").innerHTML = `${ico.pin} ${p.area}، ${p.city}، ترکیه`;
  $("#d-price").textContent = formatPrice(p.price);
  $("#crumb-prop").textContent = p.title;

  // specs
  const specs = [
    ["متراژ", `${p.size} متر`],
    ["اتاق خواب", p.rooms ? `${p.rooms} خواب` : "—"],
    ["سرویس بهداشتی", p.baths ? `${p.baths} عدد` : "—"],
    ["سال ساخت", p.year || "—"],
    ["نوع ملک", p.type],
  ];
  $("#d-specs").innerHTML = specs.map(([k, v]) => `<div class="spec"><span>${k}</span><b>${v}</b></div>`).join("");

  // description + features
  $("#d-desc").innerHTML = p.desc.map((t) => `<p>${t}</p>`).join("");
  $("#d-features").innerHTML = p.features.map((f) => `<li>${f}</li>`).join("");
  $("#d-map-label").textContent = `${p.area}، ${p.city}`;

  // contact card context
  const hidden = $("#lead-property");
  if (hidden) hidden.value = p.title;
  const agentName = $("#agent-name");
  if (agentName) agentName.textContent = AGENT.name;
  const agentPhoto = $("#agent-photo");
  if (agentPhoto) agentPhoto.src = AGENT.photo;
  const phoneText = $("#agent-phone");
  if (phoneText) phoneText.textContent = AGENT.phone;
  $$("[data-agent-phone]").forEach((a) => (a.href = "tel:" + AGENT.phoneHref));
  $$("[data-agent-whatsapp]").forEach((a) => (a.href = `https://wa.me/${AGENT.whatsapp}`));

  // similar
  const similar = $("#similar-grid");
  if (similar) {
    const others = PROPERTIES.filter((x) => x.id !== p.id && x.type === p.type);
    const rest = PROPERTIES.filter((x) => x.id !== p.id && x.type !== p.type);
    similar.innerHTML = [...others, ...rest].slice(0, 3).map(cardHTML).join("");
    bindFavs(similar);
    $$(".reveal", similar).forEach((el) => el.classList.add("is-in"));
  }
}

/* ---------- admin (visual mock) ---------- */
function initAdmin() {
  const body = $("#admin-table");
  if (!body) return;

  body.innerHTML = PROPERTIES.map(
    (p) => `
    <tr>
      <td>
        <div class="table__item">
          <img src="${IMG(p.images[0], 200)}" alt="" loading="lazy">
          <div><b>${p.title}</b><span>${p.area}، ${p.city}</span></div>
        </div>
      </td>
      <td>${p.type}</td>
      <td class="tnum">${formatPrice(p.price)}</td>
      <td class="tnum">${p.size} متر</td>
      <td><span class="tag ${p.status === "فروش" ? "tag--live" : "tag--hold"}">${p.status}</span></td>
      <td>
        <div class="row-actions">
          <button type="button" title="ویرایش">✎</button>
          <button type="button" title="پیش‌نمایش">◉</button>
          <button type="button" title="حذف">✕</button>
        </div>
      </td>
    </tr>`
  ).join("");

  $$(".row-actions button").forEach((b) =>
    b.addEventListener("click", () => toast("حالت نمایشی — ذخیره‌ای انجام نمی‌شود"))
  );

  const leads = $("#leads-list");
  if (leads) {
    const demo = [
      ["نگار فرهمند", "درخواست بازدید · ویلای کمر", "امروز", "tag--new"],
      ["امیرحسین زمانی", "دریافت مشخصات · پنت‌هاوس آلانیا", "امروز", "tag--new"],
      ["لیلا محمدی", "تماس با من · آپارتمان سیده", "دیروز", "tag--draft"],
      ["سعید نوروزی", "هماهنگی بازدید · زمین کاش", "۲ روز پیش", "tag--draft"],
    ];
    leads.innerHTML = demo
      .map(
        ([name, sub, when, cls]) => `
      <div class="lead-item">
        <div class="lead-item__av">${name.trim().charAt(0)}</div>
        <div><b>${name}</b><span>${sub}</span></div>
        <span class="tag ${cls}">${when}</span>
      </div>`
      )
      .join("");
  }

  $("#admin-form")?.addEventListener("submit", (e) => {
    e.preventDefault();
    toast("حالت نمایشی — ملکی ذخیره نشد");
  });
}

/* ---------- boot ---------- */
document.addEventListener("DOMContentLoaded", () => {
  initHeader();
  initMobileNav();
  initHome();
  initListing();
  initDetail();
  initAdmin();
  initForms();
  initReveal();
  initCounters();
});
