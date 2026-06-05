(function () {
  const config = window.siteConfig;

  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  function closeMobileMenu() {
    document.body.classList.remove("menu-open");
    qs("[data-mobile-nav]")?.classList.remove("is-open");
    qs("[data-menu-toggle]")?.setAttribute("aria-expanded", "false");
    qs("[data-menu-toggle]")?.setAttribute("aria-label", "Open menu");
  }

  function initNavigation() {
    const toggle = qs("[data-menu-toggle]");
    const nav = qs("[data-mobile-nav]");
    if (!toggle || !nav) return;

    toggle.addEventListener("click", () => {
      const isOpen = nav.classList.toggle("is-open");
      document.body.classList.toggle("menu-open", isOpen);
      toggle.setAttribute("aria-expanded", String(isOpen));
      toggle.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
    });

    qsa('a[href^="#"]').forEach((link) => {
      link.addEventListener("click", (event) => {
        const target = qs(link.getAttribute("href"));
        if (!target) return;
        event.preventDefault();
        closeMobileMenu();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  function renderPackages() {
    const mount = qs("[data-packages]");
    if (!mount) return;

    mount.innerHTML = config.packages
      .map(
        (pkg) => `
          <article class="pricing-card reveal${pkg.featured ? " featured" : ""}">
            ${pkg.badge ? `<span class="badge">${pkg.badge}</span>` : ""}
            <h3>${pkg.name}</h3>
            <p class="best-for">${pkg.bestFor}</p>
            <p class="price">${pkg.price}</p>
            <ul>
              ${pkg.inclusions.map((item) => `<li>${item}</li>`).join("")}
            </ul>
            <a class="button ${pkg.featured ? "button-primary" : "button-secondary"}" href="#booking">${pkg.cta}</a>
          </article>
        `
      )
      .join("");
  }

  function renderServiceAreas() {
    const mount = qs("[data-service-areas]");
    if (!mount) return;
    mount.innerHTML = config.serviceAreas.map((area) => `<span>${area}</span>`).join("");
  }

  function renderFaqs() {
    const mount = qs("[data-faqs]");
    if (!mount) return;

    mount.innerHTML = config.faqs
      .map(
        (faq, index) => `
          <article class="faq-item reveal${index === 0 ? " is-open" : ""}">
            <button class="faq-question" type="button" aria-expanded="${index === 0 ? "true" : "false"}">
              <span>${faq.question}</span>
              <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></svg>
            </button>
            <div class="faq-answer">${faq.answer}</div>
          </article>
        `
      )
      .join("");

    qsa(".faq-question", mount).forEach((button) => {
      button.addEventListener("click", () => {
        const item = button.closest(".faq-item");
        const isOpen = item.classList.toggle("is-open");
        button.setAttribute("aria-expanded", String(isOpen));
      });
    });
  }

  function getBrisbaneTimeParts(date) {
    const parts = new Intl.DateTimeFormat("en-AU", {
      timeZone: config.promotionalOffer.timezone,
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false
    }).formatToParts(date);

    return parts.reduce((acc, part) => {
      if (part.type !== "literal") acc[part.type] = Number(part.value);
      return acc;
    }, {});
  }

  function getMillisecondsUntilBrisbaneMidnight() {
    const parts = getBrisbaneTimeParts(new Date());
    const brisbaneNowAsUtc = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, parts.second);
    const nextMidnightAsUtc = Date.UTC(parts.year, parts.month - 1, parts.day + 1, 0, 0, 0);
    return Math.max(0, nextMidnightAsUtc - brisbaneNowAsUtc);
  }

  function initCountdown() {
    const countdown = qs("[data-countdown]");
    if (!countdown) return;

    const hoursEl = qs("[data-hours]", countdown);
    const minutesEl = qs("[data-minutes]", countdown);
    const secondsEl = qs("[data-seconds]", countdown);
    const pad = (value) => String(value).padStart(2, "0");

    function tick() {
      const diff = getMillisecondsUntilBrisbaneMidnight();
      const totalSeconds = Math.floor(diff / 1000);
      const hours = Math.floor(totalSeconds / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      hoursEl.textContent = pad(hours);
      minutesEl.textContent = pad(minutes);
      secondsEl.textContent = pad(seconds);
    }

    tick();
    window.setInterval(tick, 1000);
  }

  function initComparisonSliders() {
    qsa(".comparison-slider").forEach((slider) => {
      const input = qs('input[type="range"]', slider);
      if (!input) return;
      const update = () => slider.style.setProperty("--position", `${input.value}%`);
      input.addEventListener("input", update);
      update();
    });
  }

  function initBookingForm() {
    const form = qs("#bookingForm");
    const status = qs("[data-form-status]");
    if (!form || !status) return;

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      qsa("[aria-invalid]", form).forEach((field) => field.removeAttribute("aria-invalid"));

      const requiredFields = qsa("[required]", form);
      const invalid = requiredFields.filter((field) => !field.value.trim());

      if (invalid.length) {
        invalid.forEach((field) => field.setAttribute("aria-invalid", "true"));
        status.textContent = "Please complete the required fields so we can confirm your booking.";
        invalid[0].focus();
        return;
      }

      const email = qs('input[type="email"]', form);
      if (email && !email.validity.valid) {
        email.setAttribute("aria-invalid", "true");
        status.textContent = "Please enter a valid email address.";
        email.focus();
        return;
      }

      const button = qs('button[type="submit"]', form);
      button.disabled = true;
      button.querySelector("span").textContent = "Sending...";
      status.textContent = "";

      if (config.contact.bookingEndpoint) {
        try {
          const response = await fetch(config.contact.bookingEndpoint, {
            method: "POST",
            body: new FormData(form)
          });
          const result = await response.json();
          if (result.success) {
            status.textContent = "Thanks! We’ll contact you shortly to confirm your mobile booking.";
            form.reset();
          } else {
            status.textContent = result.message || "Something went wrong. Please call us directly.";
          }
        } catch {
          status.textContent = "Something went wrong. Please call us directly.";
        }
      } else {
        await new Promise((resolve) => window.setTimeout(resolve, 650));
        status.textContent = "Thanks! We’ll contact you shortly to confirm your mobile booking.";
        form.reset();
      }

      button.disabled = false;
      button.querySelector("span").textContent = "Request Booking";
    });
  }

  function initContactDetails() {
    qsa("[data-phone-link]").forEach((link) => {
      link.href = config.contact.phoneHref;
      link.textContent = link.textContent.includes("Call") ? `Call ${config.contact.phoneDisplay}` : config.contact.phoneDisplay;
    });

    qsa("[data-email-link]").forEach((link) => {
      link.href = `mailto:${config.contact.email}`;
      link.textContent = config.contact.email;
    });

    qs("[data-year]").textContent = new Date().getFullYear();
  }

  function initRevealAnimations() {
    const items = qsa(".reveal");
    if (!("IntersectionObserver" in window)) {
      items.forEach((item) => item.classList.add("is-visible"));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -30px" }
    );

    items.forEach((item) => observer.observe(item));
  }

  renderPackages();
  renderServiceAreas();
  renderFaqs();
  initNavigation();
  initCountdown();
  initComparisonSliders();
  initBookingForm();
  initContactDetails();
  initRevealAnimations();
})();
