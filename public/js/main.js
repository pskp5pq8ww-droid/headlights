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

  // ── Render standard packages inside "other plans" ───────────────────────
  function renderOtherPackages() {
    const mount = qs("[data-other-packages]");
    if (!mount) return;
    mount.innerHTML = config.packages
      .map(
        (pkg) => `
          <label class="mini-pkg-card">
            <input type="radio" name="selectedPackage" value="${pkg.name}" />
            <div>
              <div class="mini-pkg-name">${pkg.name}</div>
              <div class="mini-pkg-price">${pkg.price}</div>
            </div>
          </label>`
      )
      .join("");
  }

  // ── Multi-step booking wizard ────────────────────────────────────────────
  function initBookingWizard() {
    const wizard = qs("#bookingWizard");
    if (!wizard) return;

    let currentStep = 1;
    let selectedAddress = "";
    let selectedPackage = "Launch Offer – Full Restore";

    // Step navigation
    function goToStep(n) {
      qsa("[data-panel]", wizard).forEach((p) => {
        p.hidden = parseInt(p.dataset.panel) !== n;
      });
      qsa("[data-step-indicator]", wizard).forEach((s) => {
        const num = parseInt(s.dataset.stepIndicator);
        s.classList.toggle("active", num === n);
        s.classList.toggle("completed", num < n);
      });
      currentStep = n;
      wizard.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    qsa(".step-next-btn", wizard).forEach((btn) => {
      btn.addEventListener("click", () => {
        const next = parseInt(btn.dataset.next);
        if (next === 2 && !selectedAddress) {
          const input = qs("#addressInput");
          if (input) { input.focus(); input.setAttribute("aria-invalid", "true"); }
          return;
        }
        if (next === 3) {
          const hs = qs("#hiddenSuburb");
          const hp = qs("#hiddenPackage");
          if (hs) hs.value = selectedAddress;
          if (hp) hp.value = selectedPackage;
        }
        goToStep(next);
      });
    });

    qsa(".step-back-btn", wizard).forEach((btn) => {
      btn.addEventListener("click", () => goToStep(parseInt(btn.dataset.prev)));
    });

    // Address autocomplete (Nominatim / OpenStreetMap — free, no API key)
    const addressInput = qs("#addressInput");
    const suggestionsList = qs("#addressSuggestions");
    const mapPreview = qs("#mapPreview");
    const mapFrame = qs("#mapFrame");
    const mapLabel = qs("#mapLabel");

    if (addressInput && suggestionsList) {
      let debounce;

      addressInput.addEventListener("input", () => {
        clearTimeout(debounce);
        addressInput.removeAttribute("aria-invalid");
        const q = addressInput.value.trim();
        if (q.length < 3) { suggestionsList.hidden = true; return; }
        debounce = window.setTimeout(() => fetchSuggestions(q), 360);
      });

      addressInput.addEventListener("keydown", (e) => {
        if (e.key === "Escape") suggestionsList.hidden = true;
      });

      document.addEventListener("click", (e) => {
        if (!addressInput.contains(e.target) && !suggestionsList.contains(e.target)) {
          suggestionsList.hidden = true;
        }
      });
    }

    async function fetchSuggestions(query) {
      const mapsConfig = config.maps || {};

      // Hook: swap to Google Maps Places when API key is provided
      if (mapsConfig.googleMapsApiKey) {
        // TODO: loadGooglePlacesAutocomplete(mapsConfig.googleMapsApiKey, addressInput);
        return;
      }

      // Default: Nominatim (OpenStreetMap) — completely free, no key required
      try {
        const city = mapsConfig.defaultCity || "Brisbane, Queensland, Australia";
        const url =
          "https://nominatim.openstreetmap.org/search" +
          "?q=" + encodeURIComponent(query + ", " + city) +
          "&format=json&addressdetails=1&limit=5&countrycodes=au";
        const res = await fetch(url, {
          headers: { "Accept": "application/json", "Accept-Language": "en" }
        });
        if (!res.ok) return;
        const data = await res.json();
        renderSuggestions(data);
      } catch {
        if (suggestionsList) suggestionsList.hidden = true;
      }
    }

    function renderSuggestions(results) {
      if (!results.length || !suggestionsList) { if (suggestionsList) suggestionsList.hidden = true; return; }
      suggestionsList.innerHTML = results
        .map(
          (r) =>
            `<li class="suggestion-item" role="option" tabindex="-1"
                data-lat="${r.lat}" data-lng="${r.lon}"
                data-label="${r.display_name.replace(/"/g, "&quot;")}">
              ${r.display_name}
            </li>`
        )
        .join("");
      suggestionsList.hidden = false;
      qsa(".suggestion-item", suggestionsList).forEach((item) => {
        item.addEventListener("click", () => selectAddress(item));
        item.addEventListener("keydown", (e) => {
          if (e.key === "Enter" || e.key === " ") { e.preventDefault(); selectAddress(item); }
        });
      });
    }

    function selectAddress(item) {
      const lat = item.dataset.lat;
      const lng = item.dataset.lng;
      const label = item.dataset.label;
      if (addressInput) { addressInput.value = label; addressInput.removeAttribute("aria-invalid"); }
      selectedAddress = label;
      if (suggestionsList) suggestionsList.hidden = true;
      showMap(lat, lng, label);
    }

    function showMap(lat, lng, label) {
      if (!mapFrame || !mapPreview) return;
      const d = 0.013;
      const bbox = (parseFloat(lng) - d) + "," + (parseFloat(lat) - d) + "," +
                   (parseFloat(lng) + d) + "," + (parseFloat(lat) + d);
      mapFrame.src =
        "https://www.openstreetmap.org/export/embed.html" +
        "?bbox=" + bbox + "&layer=mapnik&marker=" + lat + "," + lng;
      if (mapLabel) mapLabel.textContent = label;
      mapPreview.hidden = false;
    }

    // Package toggle
    renderOtherPackages();
    const otherToggle = qs("#otherPlansToggle");
    const otherPlans = qs("#otherPlans");
    if (otherToggle && otherPlans) {
      otherToggle.addEventListener("click", () => {
        const opening = otherPlans.hidden;
        otherPlans.hidden = !opening;
        otherToggle.setAttribute("aria-expanded", String(opening));
      });
    }

    wizard.addEventListener("change", (e) => {
      if (e.target.name === "selectedPackage") {
        selectedPackage = e.target.value;
        const hp = qs("#hiddenPackage");
        if (hp) hp.value = selectedPackage;
        // Update .is-selected class for :has() fallback (older browsers)
        qsa(".promo-card", wizard).forEach((c) =>
          c.classList.toggle("is-selected", c.querySelector("input")?.checked)
        );
      }
    });

    // Mark promo card selected on load
    const promoCard = qs(".promo-card", wizard);
    if (promoCard) promoCard.classList.add("is-selected");

    // Form submission (Step 3)
    const form = qs("#bookingForm");
    const status = qs("[data-form-status]");
    if (!form || !status) return;

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      qsa("[aria-invalid]", form).forEach((f) => f.removeAttribute("aria-invalid"));

      const invalid = qsa("[required]", form).filter((f) => !f.value.trim());
      if (invalid.length) {
        invalid.forEach((f) => f.setAttribute("aria-invalid", "true"));
        status.textContent = "Please complete all required fields.";
        invalid[0].focus();
        return;
      }

      const emailField = qs('input[type="email"]', form);
      if (emailField && !emailField.validity.valid) {
        emailField.setAttribute("aria-invalid", "true");
        status.textContent = "Please enter a valid email address.";
        emailField.focus();
        return;
      }

      const button = qs('button[type="submit"]', form);
      button.disabled = true;
      button.querySelector("span").textContent = "Sending…";
      status.textContent = "";

      if (config.contact.bookingEndpoint) {
        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? "";
          const response = await fetch(config.contact.bookingEndpoint, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken },
            body: new FormData(form)
          });
          if (response.status === 419) {
            status.textContent = "Session expired. Please refresh the page and try again.";
          } else {
            const result = await response.json();
            if (result.success) {
              status.textContent = "Thanks! We’ll contact you shortly to confirm your mobile booking.";
              form.reset();
              goToStep(1);
              selectedAddress = "";
              if (mapPreview) mapPreview.hidden = true;
            } else {
              const firstError = result.errors ? Object.values(result.errors)[0]?.[0] : null;
              status.textContent = firstError || result.message || "Something went wrong. Please call us directly.";
            }
          }
        } catch {
          status.textContent = "Something went wrong. Please call us directly.";
        }
      } else {
        await new Promise((r) => window.setTimeout(r, 700));
        status.textContent = "Thanks! We’ll contact you shortly to confirm your mobile booking.";
        form.reset();
        goToStep(1);
        selectedAddress = "";
        if (mapPreview) mapPreview.hidden = true;
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

    document.body.classList.add("animations-ready");

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
  initBookingWizard();
  initContactDetails();
  initRevealAnimations();
})();
