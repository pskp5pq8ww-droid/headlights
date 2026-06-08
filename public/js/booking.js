/* booking.js — multi-step booking wizard + Google Places autocomplete.
   Loaded only on the /book page. Requires config.js (window.siteConfig). */
(function () {
  const config = window.siteConfig || {};
  const qs  = (s, p = document) => p.querySelector(s);
  const qsa = (s, p = document) => Array.from(p.querySelectorAll(s));

  // Lazy-load Google Maps JS only when the booking section is visible
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
          err.textContent = "Address autocomplete is not configured yet. You can still type your address.";
          err.hidden = false;
        }
        return;
      }
      if (document.getElementById("gmap-script")) return;
      const s = document.createElement("script");
      s.id = "gmap-script";
      s.src = "https://maps.googleapis.com/maps/api/js?key=" + encodeURIComponent(key) +
              "&libraries=places&callback=_initGooglePlaces&loading=async";
      s.async = true; s.defer = true;
      document.head.appendChild(s);
    }
    if ("IntersectionObserver" in window) {
      const obs = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) { load(); obs.disconnect(); }
      }, { rootMargin: "300px" });
      obs.observe(section);
    } else { load(); }
  }

  function renderOtherPackages() {
    const mount = qs("[data-other-packages]");
    if (!mount || !config.packages) return;
    mount.innerHTML = config.packages.map((pkg) => `
      <label class="mini-pkg-card">
        <input type="radio" name="selectedPackage" value="${pkg.name}" />
        <div><div class="mini-pkg-name">${pkg.name}</div><div class="mini-pkg-price">${pkg.price}</div></div>
      </label>`).join("");
  }

  function initBookingWizard() {
    const wizard = qs("#bookingWizard");
    if (!wizard) return;

    let selectedAddress = "";
    let selectedPackage = "EOFY Launch Offer – $99";
    let googleAddressSelected = false;

    function goToStep(n) {
      qsa("[data-panel]", wizard).forEach((p) => { p.hidden = parseInt(p.dataset.panel) !== n; });
      qsa("[data-step-indicator]", wizard).forEach((s) => {
        const num = parseInt(s.dataset.stepIndicator);
        s.classList.toggle("active", num === n);
        s.classList.toggle("completed", num < n);
      });
      wizard.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    qsa(".step-next-btn", wizard).forEach((btn) => {
      btn.addEventListener("click", () => {
        const next = parseInt(btn.dataset.next);
        if (next === 2 && !googleAddressSelected) {
          const input = qs("#addressInput"); const err = qs("#addressError");
          if (input) { input.focus(); input.setAttribute("aria-invalid", "true"); }
          if (err) err.hidden = false;
          return;
        }
        if (next === 3) { const hp = qs("#hiddenPackage"); if (hp) hp.value = selectedPackage; }
        goToStep(next);
      });
    });
    qsa(".step-back-btn", wizard).forEach((btn) => {
      btn.addEventListener("click", () => goToStep(parseInt(btn.dataset.prev)));
    });

    window._onAddressSelected = (addr) => { selectedAddress = addr; googleAddressSelected = true; };
    window._onAddressCleared  = () => { googleAddressSelected = false; };
    loadGoogleMapsLazy();

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
        const hp = qs("#hiddenPackage"); if (hp) hp.value = selectedPackage;
        qsa(".promo-card", wizard).forEach((c) =>
          c.classList.toggle("is-selected", c.querySelector("input")?.checked));
      }
    });
    const promoCard = qs(".promo-card", wizard);
    if (promoCard) promoCard.classList.add("is-selected");

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
      if (!googleAddressSelected || !qs("#hiddenLat", form)?.value) {
        status.textContent = "Please go back to Step 1 and select a valid address.";
        goToStep(1);
        const ai = qs("#addressInput");
        if (ai) { ai.focus(); ai.setAttribute("aria-invalid", "true"); }
        const ae = qs("#addressError"); if (ae) ae.hidden = false;
        return;
      }

      const button = qs('button[type="submit"]', form);
      button.disabled = true;
      button.querySelector("span").textContent = "Sending…";
      status.textContent = "";

      try {
        const response = await fetch(config.contact?.bookingEndpoint || "/form.php", {
          method: "POST",
          body: new FormData(form)
        });
        const result = await response.json();
        if (result.success) {
          // Booking saved — move on to the (simulated) payment step
          const payId = qs("#payBookingId");
          if (payId) payId.value = result.booking_id || "";
          qsa("[data-pay-amount]").forEach((e) => { e.textContent = result.amount || 99; });
          status.textContent = "";
          goToStep(4);
        } else {
          const firstError = result.errors ? Object.values(result.errors)[0]?.[0] : null;
          status.textContent = firstError || result.message || "Something went wrong. Please call us directly.";
        }
      } catch {
        status.textContent = "Something went wrong. Please call us directly.";
      }

      button.disabled = false;
      button.querySelector("span").textContent = "Continue to Payment";
    });

    // ── Simulated payment step ───────────────────────────────────────────────
    const payForm = qs("#paymentForm");
    const payStatus = qs("[data-pay-status]");
    if (payForm && payStatus) {
      payForm.addEventListener("submit", async (event) => {
        event.preventDefault();
        const invalid = qsa("[required]", payForm).filter((f) => !f.value.trim());
        if (invalid.length) {
          invalid.forEach((f) => f.setAttribute("aria-invalid", "true"));
          payStatus.textContent = "Please complete all card fields.";
          invalid[0].focus();
          return;
        }
        const payBtn = qs('button[type="submit"]', payForm);
        payBtn.disabled = true;
        payBtn.querySelector("span").textContent = "Processing…";
        payStatus.textContent = "";

        try {
          const res = await fetch("/pay", { method: "POST", body: new FormData(payForm) });
          const result = await res.json();
          if (result.success) {
            const ref = qs("[data-pay-ref]");
            if (ref) { ref.textContent = "Payment reference: " + result.reference; ref.hidden = false; }
            const msg = qs("[data-done-msg]");
            if (msg) msg.textContent = "Payment received (demo). We'll contact you shortly to confirm your mobile booking.";
            goToStep(5);
          } else {
            payStatus.textContent = result.message || "Payment was declined. Please try another card.";
          }
        } catch {
          payStatus.textContent = "Payment could not be processed. Please try again.";
        }

        payBtn.disabled = false;
        payBtn.querySelector("span").textContent = "Pay (Demo)";
      });
    }
  }

  initBookingWizard();
})();

// ── Google Places callback (global — invoked by the Maps JS API) ─────────────
window._initGooglePlaces = function () {
  const input = document.getElementById("addressInput");
  if (!input) return;

  const autocomplete = new google.maps.places.Autocomplete(input, {
    componentRestrictions: { country: "au" },
    fields: ["address_components", "formatted_address", "geometry", "place_id"],
    bounds: new google.maps.LatLngBounds({ lat: -28.5, lng: 151.5 }, { lat: -25.5, lng: 154.0 }),
    strictBounds: false
  });

  let gMap = null, gMarker = null;
  const setF = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ""; };
  const clearF = () => ["hiddenFullAddress","hiddenPlaceId","hiddenLat","hiddenLng",
    "hiddenStreetNumber","hiddenStreetName","hiddenSuburb","hiddenState","hiddenPostcode","hiddenCountry"]
    .forEach((id) => setF(id, ""));

  input.addEventListener("keydown", (e) => { if (e.key === "Enter") e.preventDefault(); });
  input.addEventListener("input", () => {
    window._onAddressCleared?.();
    clearF();
    const err = document.getElementById("addressError");
    const mp  = document.getElementById("mapPreview");
    if (err) err.hidden = true;
    if (mp)  mp.hidden = true;
    input.removeAttribute("aria-invalid");
  });

  autocomplete.addListener("place_changed", () => {
    const place = autocomplete.getPlace();
    if (!place.geometry?.location) { window._onAddressCleared?.(); clearF(); return; }
    const c = parseComponents(place.address_components || []);
    setF("hiddenFullAddress", place.formatted_address);
    setF("hiddenPlaceId", place.place_id);
    setF("hiddenLat", place.geometry.location.lat());
    setF("hiddenLng", place.geometry.location.lng());
    setF("hiddenStreetNumber", c.street_number);
    setF("hiddenStreetName", c.street_name);
    setF("hiddenSuburb", c.suburb);
    setF("hiddenState", c.state);
    setF("hiddenPostcode", c.postcode);
    setF("hiddenCountry", c.country);
    window._onAddressSelected?.(place.formatted_address);
    input.removeAttribute("aria-invalid");
    const err = document.getElementById("addressError"); if (err) err.hidden = true;
    showMap(place.geometry.location, place.formatted_address);
  });

  function parseComponents(comps) {
    const r = { street_number:"", street_name:"", suburb:"", state:"", postcode:"", country:"" };
    comps.forEach((c) => {
      if (c.types.includes("street_number")) r.street_number = c.long_name;
      if (c.types.includes("route")) r.street_name = c.long_name;
      if (c.types.includes("locality")) r.suburb = c.long_name;
      if (!r.suburb && c.types.includes("administrative_area_level_2")) r.suburb = c.long_name;
      if (c.types.includes("administrative_area_level_1")) r.state = c.short_name;
      if (c.types.includes("postal_code")) r.postcode = c.long_name;
      if (c.types.includes("country")) r.country = c.long_name;
    });
    return r;
  }

  function showMap(location, label) {
    const mp = document.getElementById("mapPreview");
    const cont = document.getElementById("googleMapContainer");
    const lbl = document.getElementById("mapLabel");
    if (!mp || !cont) return;
    mp.hidden = false;
    if (!gMap) {
      gMap = new google.maps.Map(cont, {
        center: location, zoom: 16,
        mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
        styles: [
          { elementType: "geometry", stylers: [{ color: "#f0f8fc" }] },
          { featureType: "road", elementType: "geometry", stylers: [{ color: "#d4edf7" }] },
          { featureType: "water", elementType: "geometry", stylers: [{ color: "#b8ebf8" }] },
          { featureType: "poi", stylers: [{ visibility: "off" }] },
          { featureType: "transit", stylers: [{ visibility: "off" }] }
        ]
      });
      gMarker = new google.maps.Marker({ map: gMap, position: location, title: label, animation: google.maps.Animation.DROP });
    } else {
      gMap.setCenter(location); gMap.setZoom(16);
      gMarker.setPosition(location); gMarker.setAnimation(google.maps.Animation.DROP);
    }
    if (lbl) lbl.textContent = label;
  }
};
