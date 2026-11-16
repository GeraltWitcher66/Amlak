/* ==========================================================================
   رفتارهای مخصوص نسخه استاتیک (GitHub Pages)
   چون سرور و دیتابیس وجود ندارد:
   - فرم‌های درخواست، پیام آماده واتس‌اپ می‌سازند
   - فیلترهای فهرست املاک در مرورگر روی کارت‌های آماده اجرا می‌شوند
   ========================================================================== */

(function () {
  const cfg = window.STATIC_SITE || {};
  const toFa = (v) => String(v).replace(/\d/g, (d) => "۰۱۲۳۴۵۶۷۸۹"[d]);

  /* ---------- فرم‌های درخواست → واتس‌اپ ---------- */
  document.querySelectorAll('form[action="leads.php"]').forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();

      const val = (name) => (form.querySelector(`[name="${name}"]`)?.value || "").trim();
      const propertyTitle = document.getElementById("d-title")?.textContent?.trim() || "";

      const lines = ["سلام، درخواست مشاوره از سایت:"];
      if (val("name")) lines.push("نام: " + val("name"));
      if (val("phone")) lines.push("تلفن: " + val("phone"));
      if (val("topic")) lines.push("موضوع: " + val("topic"));
      if (val("budget")) lines.push("بودجه: " + val("budget"));
      if (propertyTitle) lines.push("ملک: " + propertyTitle);
      if (val("message")) lines.push("توضیح: " + val("message"));

      const url = "https://wa.me/" + (cfg.whatsapp || "") + "?text=" + encodeURIComponent(lines.join("\n"));
      window.open(url, "_blank", "noopener");

      const ok = form.querySelector(".form-success");
      if (ok) {
        ok.textContent = "✓ پیام شما در واتس‌اپ آماده شد؛ فقط ارسال را تأیید کنید.";
        ok.classList.add("is-on");
      }
    });
  });

  /* ---------- فیلتر سمت مرورگر برای فهرست املاک ---------- */
  const grid = document.getElementById("listing-grid");
  const form = document.getElementById("filter-form");
  if (!grid || !form) return;

  const cards = Array.from(grid.querySelectorAll(".prop-card"));
  if (!cards.length) return;

  cards.forEach((el, i) => {
    el.dataset.order = String(i);
  });

  const field = (name) => form.querySelector(`[name="${name}"]`);
  const priceBuckets = {
    lt300: [null, 300000],
    "300-600": [300000, 600000],
    "600-1000": [600000, 1000000],
    gt1000: [1000000, null],
  };
  const sizeBuckets = { lt100: [null, 100], "100-250": [100, 250], gt250: [250, null] };

  const inRange = (value, bucket) => {
    if (!bucket) return true;
    const [min, max] = bucket;
    if (min !== null && value < min) return false;
    if (max !== null && value >= max) return false;
    return true;
  };

  const current = () => ({
    q: (field("q")?.value || "").trim().toLowerCase(),
    type: field("type")?.value || "",
    area: field("area")?.value || "",
    price: field("price")?.value || "",
    size: field("size")?.value || "",
    sort: field("sort")?.value || "default",
  });

  const apply = () => {
    const s = current();
    let visible = 0;

    cards.forEach((el) => {
      const price = Number(el.dataset.price || 0);
      const size = Number(el.dataset.size || 0);
      const match =
        (!s.type || el.dataset.type === s.type) &&
        (!s.area || el.dataset.area === s.area) &&
        inRange(price, priceBuckets[s.price]) &&
        inRange(size, sizeBuckets[s.size]) &&
        (!s.q || (el.dataset.search || "").includes(s.q));

      el.hidden = !match;
      if (match) visible++;
    });

    [...cards]
      .sort((a, b) => {
        if (s.sort === "price-asc") return Number(a.dataset.price) - Number(b.dataset.price);
        if (s.sort === "price-desc") return Number(b.dataset.price) - Number(a.dataset.price);
        if (s.sort === "size-desc") return Number(b.dataset.size) - Number(a.dataset.size);
        return Number(a.dataset.order) - Number(b.dataset.order);
      })
      .forEach((el) => grid.appendChild(el));

    grid.hidden = visible === 0;
    const empty = document.getElementById("empty-state");
    if (empty) empty.hidden = visible !== 0;

    const count = document.getElementById("result-count");
    if (count) count.textContent = toFa(visible);

    // آدرس قابل اشتراک با همان فیلترها
    const params = new URLSearchParams();
    Object.entries(s).forEach(([key, value]) => {
      if (value) params.set(key, value);
    });
    const query = params.toString();
    history.replaceState(null, "", query ? "?" + query : location.pathname);
  };

  // اعمال فیلترهای موجود در آدرس (لینک‌های اشتراکی)
  const params = new URLSearchParams(location.search);
  ["q", "type", "area", "price", "size", "sort"].forEach((name) => {
    const el = field(name);
    if (el && params.has(name)) el.value = params.get(name);
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    apply();
  });

  form.querySelectorAll("select").forEach((sel) => sel.addEventListener("change", apply));

  document.querySelectorAll("#quick-chips .chip").forEach((chip) => {
    chip.addEventListener("click", () => {
      const typeField = field("type");
      if (typeField) typeField.value = chip.dataset.type || "";
      apply();
    });
  });

  apply();
})();
