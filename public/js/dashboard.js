/* dashboard.js — live Brisbane time widget (Australia/Brisbane, UTC+10). */
(function () {
  const el = document.querySelector("[data-brisbane]");
  if (el) {
    const timeEl = el.querySelector("[data-bne-time]");
    const dateEl = el.querySelector("[data-bne-date]");

    const timeFmt = new Intl.DateTimeFormat("en-AU", {
      timeZone: "Australia/Brisbane",
      hour: "numeric", minute: "2-digit", second: "2-digit", hour12: true
    });
    const dateFmt = new Intl.DateTimeFormat("en-AU", {
      timeZone: "Australia/Brisbane",
      weekday: "long", day: "numeric", month: "long", year: "numeric"
    });

    function tick() {
      const now = new Date();
      if (timeEl) timeEl.textContent = timeFmt.format(now);
      if (dateEl) dateEl.textContent = dateFmt.format(now);
    }
    tick();
    setInterval(tick, 1000);
  }

  const views = Array.from(document.querySelectorAll("[data-admin-view]"));
  const viewLinks = Array.from(document.querySelectorAll("[data-admin-view-link]"));
  const viewNames = new Set(views.map((view) => view.dataset.adminView));

  function requestedView() {
    const hash = window.location.hash.replace("#", "");
    if (viewNames.has(hash)) return hash;
    if (new URLSearchParams(window.location.search).has("id")) return "detail";
    return "overview";
  }

  function showView(name, shouldFocus = false) {
    const next = viewNames.has(name) ? name : "overview";
    views.forEach((view) => {
      const active = view.dataset.adminView === next;
      view.classList.toggle("is-active", active);
      view.hidden = !active;
    });
    viewLinks.forEach((link) => {
      const active = link.dataset.adminViewLink === next;
      link.classList.toggle("is-active", active);
      if (active) {
        link.setAttribute("aria-current", "page");
      } else {
        link.removeAttribute("aria-current");
      }
    });
    if (shouldFocus) {
      const target = views.find((view) => view.dataset.adminView === next);
      target?.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  viewLinks.forEach((link) => {
    link.addEventListener("click", () => {
      showView(link.dataset.adminViewLink || "overview", true);
    });
  });

  window.addEventListener("hashchange", () => showView(requestedView()));
  showView(requestedView());

  document.querySelectorAll("[data-copy]").forEach((button) => {
    button.addEventListener("click", async () => {
      const value = button.getAttribute("data-copy") || "";
      if (!value.trim()) return;
      try {
        await navigator.clipboard.writeText(value);
        const original = button.textContent;
        button.textContent = "Copied";
        window.setTimeout(() => {
          button.textContent = original;
        }, 1200);
      } catch {
        button.textContent = "Select text";
      }
    });
  });

  // Compact bookings: click a summary row to expand its details card.
  document.querySelectorAll("[data-booking-toggle]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const card = btn.closest("[data-booking-card]");
      const body = card && card.querySelector(".booking-card-body");
      if (!body) return;
      const open = card.classList.toggle("is-expanded");
      btn.setAttribute("aria-expanded", String(open));
      body.hidden = !open;
    });
  });

  document.querySelectorAll("[data-ticket-toggle]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const card = btn.closest("[data-ticket-card]");
      const body = card && card.querySelector(".ticket-body");
      if (!body) return;
      const open = card.classList.toggle("is-expanded");
      btn.setAttribute("aria-expanded", String(open));
      body.hidden = !open;
    });
  });
})();
