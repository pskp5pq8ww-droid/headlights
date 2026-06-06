(function () {
  const countdowns = Array.from(document.querySelectorAll("[data-countdown][data-target]"));
  if (!countdowns.length) return;

  const pad = (value) => String(value).padStart(2, "0");

  countdowns.forEach((countdown) => {
    const target = new Date(countdown.dataset.target);
    const daysEl = countdown.querySelector("[data-days]");
    const hoursEl = countdown.querySelector("[data-hours]");
    const minutesEl = countdown.querySelector("[data-minutes]");
    const secondsEl = countdown.querySelector("[data-seconds]");

    if (Number.isNaN(target.getTime())) return;

    function tick() {
      const diff = Math.max(0, target.getTime() - Date.now());
      const totalSeconds = Math.floor(diff / 1000);
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      if (daysEl) daysEl.textContent = pad(days);
      if (hoursEl) hoursEl.textContent = pad(hours);
      if (minutesEl) minutesEl.textContent = pad(minutes);
      if (secondsEl) secondsEl.textContent = pad(seconds);
    }

    tick();
    window.setInterval(tick, 1000);
  });
})();
