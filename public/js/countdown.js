(function () {
  const countdowns = Array.from(document.querySelectorAll("[data-countdown]"));
  if (!countdowns.length) return;

  const pad = (value) => String(value).padStart(2, "0");
  const brisbaneTime = new Intl.DateTimeFormat("en-AU", {
    timeZone: "Australia/Brisbane",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
    hour12: false
  });

  function secondsUntilBrisbaneMidnight() {
    const parts = Object.fromEntries(
      brisbaneTime.formatToParts(new Date()).map((part) => [part.type, part.value])
    );
    const hour = Number(parts.hour === "24" ? 0 : parts.hour);
    const minute = Number(parts.minute || 0);
    const second = Number(parts.second || 0);
    const elapsed = hour * 3600 + minute * 60 + second;
    return Math.max(0, 86400 - elapsed);
  }

  function setPart(element, value) {
    if (!element) return;
    const next = pad(value);
    if (element.textContent === next) return;
    element.textContent = next;
    document.dispatchEvent(new CustomEvent("countdown:tick", { detail: { element } }));
  }

  countdowns.forEach((countdown) => {
    const isDaily = countdown.dataset.mode === "daily-brisbane";
    const target = isDaily ? null : new Date(countdown.dataset.target || "");
    const daysEl = countdown.querySelector("[data-days]");
    const hoursEl = countdown.querySelector("[data-hours]");
    const minutesEl = countdown.querySelector("[data-minutes]");
    const secondsEl = countdown.querySelector("[data-seconds]");

    if (!isDaily && Number.isNaN(target.getTime())) return;

    function tick() {
      const totalSeconds = isDaily
        ? secondsUntilBrisbaneMidnight()
        : Math.floor(Math.max(0, target.getTime() - Date.now()) / 1000);
      const days = isDaily ? 0 : Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      setPart(daysEl, days);
      setPart(hoursEl, hours);
      setPart(minutesEl, minutes);
      setPart(secondsEl, seconds);
    }

    tick();
    window.setInterval(tick, 1000);
  });
})();
