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

  // ── Lazy-load Google Maps JS only when booking section is visible ────────
  function loadGoogleMapsLazy() {
    const section = document.getElementById("booking");
    if (!section) return;
    let loaded = false;
    function load() {
      if (loaded) return;
      loaded = true;
      const key = document.querySelector('meta[name="gmap-key"]')?.content || "";
      if (!key) {
        const err = document.getElementById("addressError");
        if (err) {
          err.textContent = "Address autocomplete is not configured yet. Please call us or try again later.";
          err.hidden = false;
        }
        return;
      }
      if (document.getElementById("gmap-script")) return;
      const s = document.createElement("script");
      s.id    = "gmap-script";
      s.src   = "https://maps.googleapis.com/maps/api/js?key=" +
                encodeURIComponent(key) +
                "&libraries=places&callback=_initGooglePlaces&loading=async";
      s.async = true;
      s.defer = true;
      document.head.appendChild(s);
    }
    if ("IntersectionObserver" in window) {
      const obs = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) { load(); obs.disconnect(); }
      }, { rootMargin: "300px" });
      obs.observe(section);
    } else {
      load();
    }
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
    let googleAddressSelected = false;

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
        if (next === 2 && !googleAddressSelected) {
          const input = qs("#addressInput");
          const err   = qs("#addressError");
          if (input) { input.focus(); input.setAttribute("aria-invalid", "true"); }
          if (err) err.hidden = false;
          return;
        }
        if (next === 3) {
          const hp = qs("#hiddenPackage");
          if (hp) hp.value = selectedPackage;
          // suburb + address coords already filled by Google Places callback
        }
        goToStep(next);
      });
    });

    qsa(".step-back-btn", wizard).forEach((btn) => {
      btn.addEventListener("click", () => goToStep(parseInt(btn.dataset.prev)));
    });

    // Address — Google Places Autocomplete
    // Callbacks on window so the global _initGooglePlaces can update wizard-scoped state
    window._onAddressSelected = (addr) => {
      selectedAddress     = addr;
      googleAddressSelected = true;
    };
    window._onAddressCleared = () => {
      googleAddressSelected = false;
    };
    // Lazy-load Maps JS API only when the booking section scrolls into view
    loadGoogleMapsLazy();

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

      // Require a Google address to have been selected
      if (!googleAddressSelected || !qs("#hiddenLat", form)?.value) {
        status.textContent = "Please go back to Step 1 and select a valid address from the suggestions.";
        goToStep(1);
        const ai = qs("#addressInput");
        if (ai) { ai.focus(); ai.setAttribute("aria-invalid", "true"); }
        const ae = qs("#addressError");
        if (ae) ae.hidden = false;
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
              googleAddressSelected = false;
              const ai = qs("#addressInput");
              if (ai) ai.value = "";
              const mp = qs("#mapPreview");
              if (mp) mp.hidden = true;
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

// ── Google Places callback (invoked by Maps JS API after loading) ────────────
// Defined outside the IIFE so the API script can call it as a global.
window._initGooglePlaces = function () {
  const input = document.getElementById("addressInput");
  if (!input) return;

  const autocomplete = new google.maps.places.Autocomplete(input, {
    componentRestrictions: { country: "au" },
    // Request only the fields we need to minimise billing
    fields: ["address_components", "formatted_address", "geometry", "place_id"],
    // Bias toward Brisbane / SE Queensland — not strict, so regional AU still works
    bounds: new google.maps.LatLngBounds(
      { lat: -28.5, lng: 151.5 },
      { lat: -25.5, lng: 154.0 }
    ),
    strictBounds: false
  });

  let gMap = null, gMarker = null;

  // Prevent Enter key from submitting the page while the PAC dropdown is open
  input.addEventListener("keydown", (e) => { if (e.key === "Enter") e.preventDefault(); });

  // Clear Google selection state when the user edits the field manually
  input.addEventListener("input", () => {
    if (window._onAddressCleared) window._onAddressCleared();
    _clearFields();
    const err = document.getElementById("addressError");
    const mp  = document.getElementById("mapPreview");
    if (err) err.hidden = true;
    if (mp)  mp.hidden  = true;
    input.removeAttribute("aria-invalid");
  });

  autocomplete.addListener("place_changed", () => {
    const place = autocomplete.getPlace();
    if (!place.geometry?.location) {
      if (window._onAddressCleared) window._onAddressCleared();
      _clearFields();
      return;
    }

    const c   = _parseComponents(place.address_components || []);
    const lat = place.geometry.location.lat();
    const lng = place.geometry.location.lng();

    _set("hiddenFullAddress",  place.formatted_address);
    _set("hiddenPlaceId",      place.place_id);
    _set("hiddenLat",          lat);
    _set("hiddenLng",          lng);
    _set("hiddenStreetNumber", c.street_number);
    _set("hiddenStreetName",   c.street_name);
    _set("hiddenSuburb",       c.suburb);
    _set("hiddenState",        c.state);
    _set("hiddenPostcode",     c.postcode);
    _set("hiddenCountry",      c.country);

    if (window._onAddressSelected) window._onAddressSelected(place.formatted_address);
    input.removeAttribute("aria-invalid");
    const err = document.getElementById("addressError");
    if (err) err.hidden = true;

    _showMap(place.geometry.location, place.formatted_address);
  });

  function _set(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? "";
  }

  function _clearFields() {
    ["hiddenFullAddress","hiddenPlaceId","hiddenLat","hiddenLng",
     "hiddenStreetNumber","hiddenStreetName","hiddenSuburb","hiddenState",
     "hiddenPostcode","hiddenCountry"].forEach((id) => _set(id, ""));
  }

  function _parseComponents(comps) {
    const r = { street_number:"", street_name:"", suburb:"", state:"", postcode:"", country:"" };
    comps.forEach((c) => {
      if (c.types.includes("street_number"))              r.street_number = c.long_name;
      if (c.types.includes("route"))                       r.street_name   = c.long_name;
      if (c.types.includes("locality"))                    r.suburb        = c.long_name;
      if (!r.suburb && c.types.includes("administrative_area_level_2")) r.suburb = c.long_name;
      if (c.types.includes("administrative_area_level_1")) r.state         = c.short_name;
      if (c.types.includes("postal_code"))                 r.postcode      = c.long_name;
      if (c.types.includes("country"))                     r.country       = c.long_name;
    });
    return r;
  }

  function _showMap(location, label) {
    const mp   = document.getElementById("mapPreview");
    const cont = document.getElementById("googleMapContainer");
    const lbl  = document.getElementById("mapLabel");
    if (!mp || !cont) return;
    mp.hidden = false;

    if (!gMap) {
      gMap = new google.maps.Map(cont, {
        center: location, zoom: 16,
        mapTypeControl:    false,
        streetViewControl: false,
        fullscreenControl: false,
        styles: [
          { elementType: "geometry",                              stylers: [{ color: "#f0f8fc" }] },
          { featureType: "road",  elementType: "geometry",       stylers: [{ color: "#d4edf7" }] },
          { featureType: "road",  elementType: "labels.text.fill",stylers: [{ color: "#546e7a" }] },
          { featureType: "water", elementType: "geometry",       stylers: [{ color: "#b8ebf8" }] },
          { featureType: "poi",                                   stylers: [{ visibility: "off" }] },
          { featureType: "transit",                               stylers: [{ visibility: "off" }] }
        ]
      });
      gMarker = new google.maps.Marker({
        map: gMap, position: location, title: label,
        animation: google.maps.Animation.DROP
      });
    } else {
      gMap.setCenter(location);
      gMap.setZoom(16);
      gMarker.setPosition(location);
      gMarker.setAnimation(google.maps.Animation.DROP);
    }
    if (lbl) lbl.textContent = label;
  }
};
