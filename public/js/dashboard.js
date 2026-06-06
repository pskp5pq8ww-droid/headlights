/* dashboard.js — live Brisbane time widget (Australia/Brisbane, UTC+10). */
(function () {
  const el = document.querySelector("[data-brisbane]");
  if (!el) return;
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
})();
