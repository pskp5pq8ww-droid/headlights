/* booking.js — booking wizard with Square payment step. */
(function () {
  const config = window.siteConfig || {};
  const pricing = window.bookingPricing || {};
  const services = window.bookingServices || [];
  const vehicleSizes = window.bookingVehicleSizes || {};
  const currency = window.bookingCurrency || "AUD";
  const maxServiceQuantity = Math.max(1, Number(window.bookingMaxServiceQuantity || 2));
  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  const money = (amount) =>
    new Intl.NumberFormat("en-AU", { style: "currency", currency }).format(Number(amount) || 0);

  function initBookingWizard() {
    const wizard = qs("#bookingWizard");
    const form = qs("#bookingForm", wizard || document);
    const status = qs("[data-form-status]", wizard || document);
    if (!wizard || !form || !status) return;

    const payButton = qs("#payButton", wizard);
    const payLabel = qs("[data-pay-label]", wizard);
    const cardBlock = qs("#payCardBlock", wizard);
    const quoteBlock = qs("#payQuoteBlock", wizard);
    const cashBlock = qs("#payCashBlock", wizard);
    const payMethodWrap = qs("#payMethod", wizard);
    const payMethodRadios = qsa("[data-pay-method]", wizard);
    let squareReady = false;
    let paymentFallback = false; // true when Square is unavailable → use free request flow

    const fieldLabels = {
      customer_address: "service address or suburb",
      package: "service/package",
      vehicle_size: "vehicle size",
      headlight_condition: "headlight condition",
      name: "full name",
      phone: "phone",
      email: "email",
      vehicle: "vehicle make and model",
      date: "preferred date",
      time: "preferred time window",
      terms_accepted: "Terms & Conditions acceptance"
    };

    const servicesById = new Map(services.map((service) => [service.id, service]));

    function selectedPackage() {
      const el = findFieldByName("package");
      return el ? el.value : "";
    }

    function packagePrice(pkg) {
      return Number(pricing[pkg] || 0);
    }

    function selectedVehicleSize() {
      return fieldValue("vehicle_size");
    }

    function numberOfHeadlights() {
      return fieldValue("number_of_headlights") || "2";
    }

    function servicePrice(service, size = selectedVehicleSize()) {
      if (!service || size === "commercial" || !size) return 0;
      if (service.slug === "headlight-restoration" && numberOfHeadlights() === "1" && Number(service.priceSingle) > 0) {
        return Number(service.priceSingle);
      }
      const key = size === "medium" ? "priceMedium" : size === "large" ? "priceLarge" : "priceSmall";
      return Number(service[key] || 0);
    }

    function clampServiceQuantity(value) {
      return Math.max(0, Math.min(maxServiceQuantity, Number.parseInt(value, 10) || 0));
    }

    function quantityInputForService(serviceId) {
      const card = qsa("[data-service-card]", form).find((item) => item.dataset.serviceId === serviceId);
      return card ? qs("[data-service-quantity]", card) : null;
    }

    function selectInputForService(serviceId) {
      return qsa("[data-service-select]", form).find((input) => input.value === serviceId) || null;
    }

    function serviceQuantity(input) {
      if (!input?.checked) return 0;
      const qtyInput = quantityInputForService(input.value);
      const qty = clampServiceQuantity(qtyInput?.value || 1);
      return qty > 0 ? qty : 1;
    }

    function setServiceQuantity(input, quantity) {
      if (!input) return;
      const qty = clampServiceQuantity(quantity);
      const qtyInput = quantityInputForService(input.value);
      input.checked = qty > 0;
      if (qtyInput) qtyInput.value = String(qty);
    }

    function selectedServiceCount(items = selectedServiceItems()) {
      return items.reduce((sum, item) => sum + item.quantity, 0);
    }

    function syncPackageField(items = selectedServiceItems()) {
      const field = findFieldByName("package");
      if (!field) return;
      if (!items.length) {
        field.value = "Not sure / Quote";
        return;
      }
      const first = items[0].service.name;
      const count = selectedServiceCount(items);
      field.value = count > 1 ? `${first} + ${count - 1} extra` : first;
    }

    function selectedServiceItems() {
      return qsa("[data-service-select]", form)
        .filter((input) => input.checked)
        .map((input) => {
          const service = servicesById.get(input.value);
          const quantity = serviceQuantity(input);
          return service && quantity > 0 ? { service, quantity } : null;
        })
        .filter(Boolean)
        .map((item) => ({
          ...item,
          price: servicePrice(item.service),
          total: servicePrice(item.service) * item.quantity
        }));
    }

    function selectedServicesTotal() {
      return selectedServiceItems().reduce((sum, item) => sum + item.total, 0);
    }

    function isQuoteSelection() {
      return selectedVehicleSize() === "commercial" || selectedServiceItems().some((item) => item.price <= 0);
    }

    function refreshCart() {
      const cart = qs("[data-booking-cart]", form);
      if (!cart) return;
      const items = selectedServiceItems();
      syncPackageField(items);
      const showCart = items.length > 0;
      cart.hidden = !showCart;
      if (!showCart) return;

      const quote = isQuoteSelection();
      const cartCount = qs("[data-cart-count]", cart);
      const cartItems = qs("[data-cart-items]", cart);
      const cartTotal = qs("[data-cart-total]", cart);

      const count = selectedServiceCount(items);
      if (cartCount) cartCount.textContent = `${count} ${count === 1 ? "service" : "services"}`;
      if (cartItems) {
        cartItems.replaceChildren();
        items.forEach((item) => {
          const row = document.createElement("div");
          const name = document.createElement("span");
          const amount = document.createElement("strong");
          name.textContent = item.quantity > 1 ? `${item.service.name} x${item.quantity}` : item.service.name;
          amount.textContent = quote || item.price <= 0 ? "Quote" : money(item.total);
          row.append(name, amount);
          cartItems.append(row);
        });
      }
      if (cartTotal) cartTotal.textContent = quote ? "Quote on request" : money(selectedServicesTotal());
    }

    function refreshServiceCards() {
      qsa("[data-service-card]", form).forEach((card) => {
        const input = qs("[data-service-select]", card);
        const button = qs("[data-service-button]", card);
        const service = input ? servicesById.get(input.value) : null;
        const qtyInput = qs("[data-service-quantity]", card);
        const qtyControl = qs("[data-service-quantity-control]", card);
        const qtyLabel = qs("[data-quantity-label]", card);
        const increment = qs('[data-quantity-action="increment"]', card);
        if (input && qtyInput && input.checked && clampServiceQuantity(qtyInput.value) < 1) {
          qtyInput.value = "1";
        }
        const quantity = input ? serviceQuantity(input) : 0;
        const priceEl = qs(".service-price-row span", card);
        const price = servicePrice(service);
        const quote = selectedVehicleSize() === "commercial" || price <= 0;
        card.classList.toggle("is-selected", !!input?.checked);
        card.classList.toggle("is-quote", quote);
        if (priceEl) priceEl.textContent = quote ? "Quote required" : money(price);
        if (qtyInput) qtyInput.value = String(quantity);
        if (qtyControl) qtyControl.hidden = !input?.checked;
        if (qtyLabel) qtyLabel.textContent = String(quantity);
        if (increment) increment.disabled = quantity >= maxServiceQuantity;
        if (button) {
          const checked = !!input?.checked;
          const label = qs("span", button);
          if (label) {
            if (checked) {
              label.textContent = service?.slug === "headlight-restoration" ? "Remove main service" : "Remove";
            } else {
              label.textContent = quote ? "Request quote" : "Add to booking";
            }
          }
        }
      });
      refreshCart();
    }

    // Scroll-reveal elements (class "reveal") start at opacity:0 once
    // "animations-ready" is on the body and only fade in when an
    // IntersectionObserver sees them. Content inside a hidden wizard panel
    // (display:none) never triggers that observer, so it would stay invisible
    // when the step opens. Force any reveal content in a panel visible.
    function revealPanelContent(panel) {
      if (!panel) return;
      const targets = qsa(".reveal", panel);
      if (panel.classList.contains("reveal")) targets.push(panel);
      targets.forEach((el) => {
        el.classList.add("is-visible");
        el.style.opacity = "";
        el.style.visibility = "";
        el.style.transform = "";
      });
    }

    function goToStep(step) {
      qsa("[data-panel]", wizard).forEach((panel) => {
        const isCurrent = Number(panel.dataset.panel) === step;
        panel.hidden = !isCurrent;
        if (isCurrent) revealPanelContent(panel);
      });
      qsa("[data-step-indicator]", wizard).forEach((indicator) => {
        const number = Number(indicator.dataset.stepIndicator);
        indicator.classList.toggle("active", number === step);
        indicator.classList.toggle("completed", number < step);
      });
      wizard.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function fieldName(field) {
      return fieldLabels[field.name] || field.closest("label")?.childNodes[0]?.textContent?.trim().toLowerCase() || "required field";
    }

    function invalidRequiredFields(parent) {
      return qsa("[required]", parent).filter((field) => {
        const value = field.value || "";
        return !value.trim() || !field.validity.valid;
      });
    }

    function showInvalidField(field) {
      const panel = field.closest("[data-panel]");
      if (panel) goToStep(Number(panel.dataset.panel));
      qsa("[aria-invalid]", form).forEach((item) => item.removeAttribute("aria-invalid"));
      field.setAttribute("aria-invalid", "true");
      status.textContent = field.type === "email" && field.value.trim()
        ? "Please enter a valid email address."
        : `Please complete: ${fieldName(field)}.`;
      window.ShiningGSAP?.playFormErrorAnimation(form);
      window.setTimeout(() => field.focus(), 80);
    }

    function findFieldByName(name) {
      return qsa("[name]", form).find((field) => field.name === name) || null;
    }

    function panelIsValid(step) {
      const panel = qs(`[data-panel="${step}"]`, wizard);
      if (!panel) return true;
      const invalid = invalidRequiredFields(panel);
      if (!invalid.length) return true;
      showInvalidField(invalid[0]);
      return false;
    }

    function fieldValue(name) {
      const el = findFieldByName(name);
      return el ? el.value.trim() : "";
    }

    function buildSummary() {
      const items = selectedServiceItems();
      syncPackageField(items);
      const pkg = selectedPackage();
      const price = selectedServicesTotal();
      const quote = isQuoteSelection();
      const setSummary = (key, value) => {
        const el = qs(`[data-summary="${key}"]`, wizard);
        if (el) el.textContent = value || "—";
      };
      setSummary("package", pkg);
      setSummary("datetime", [fieldValue("date"), fieldValue("time")].filter(Boolean).join(" · "));
      setSummary("date", fieldValue("date"));
      setSummary("time", fieldValue("time"));
      setSummary("location", fieldValue("formatted_address") || fieldValue("customer_address"));
      setSummary("vehicle", fieldValue("vehicle"));
      setSummary("vehicleSize", vehicleSizes[selectedVehicleSize()] || "—");
      setSummary("name", fieldValue("name"));
      setSummary("price", !quote && price > 0 ? money(price) : "Quote");
      setSummary("total", !quote && price > 0 ? money(price) : "Quote on request");
      qsa("[data-summary-services], [data-payment-services]", wizard).forEach((servicesSummary) => {
        const interactive = servicesSummary.matches("[data-summary-services], [data-payment-services]");
        servicesSummary.replaceChildren();
        servicesSummary.hidden = !items.length;
        items.forEach((item) => {
          const row = document.createElement("div");
          const name = document.createElement("span");
          const actions = document.createElement("span");
          const amount = document.createElement("strong");
          name.textContent = item.quantity > 1 ? `${item.service.name} x${item.quantity}` : item.service.name;
          amount.textContent = quote || item.price <= 0 ? "Quote" : money(item.total);
          actions.className = "summary-service-actions";
          if (interactive) {
            const controls = document.createElement("span");
            const decrement = document.createElement("button");
            const label = document.createElement("span");
            const increment = document.createElement("button");
            controls.className = "summary-quantity-control";
            decrement.type = "button";
            decrement.textContent = "-";
            decrement.dataset.quantityAction = "decrement";
            decrement.dataset.serviceId = item.service.id;
            decrement.setAttribute("aria-label", `Decrease ${item.service.name}`);
            label.textContent = String(item.quantity);
            increment.type = "button";
            increment.textContent = "+";
            increment.dataset.quantityAction = "increment";
            increment.dataset.serviceId = item.service.id;
            increment.disabled = item.quantity >= maxServiceQuantity;
            increment.setAttribute("aria-label", `Increase ${item.service.name}`);
            controls.append(decrement, label, increment);
            actions.append(controls);
          }
          actions.append(amount);
          row.append(name, actions);
          servicesSummary.append(row);
        });
      });
      return quote ? 0 : price;
    }

    function useRequestFallback(message) {
      paymentFallback = true;
      squareReady = false;
      if (cardBlock) cardBlock.hidden = true;
      if (quoteBlock) {
        quoteBlock.hidden = false;
        const note = qs(".panel-hint", quoteBlock);
        if (note && message) note.textContent = message;
      }
      if (payLabel) payLabel.textContent = "Request Booking";
    }

    function squareUnavailableMessage() {
      const detail = window.SquarePayment?.lastError || "";
      return detail
        ? `Online payment is temporarily unavailable (${detail}). Submit your booking and we'll confirm and arrange payment with you.`
        : "Online payment is temporarily unavailable. Submit your booking and we'll confirm and arrange payment with you.";
    }

    function selectedPaymentMethod() {
      const checked = payMethodRadios.find((radio) => radio.checked);
      return checked ? checked.value : "card";
    }

    async function enterPaymentStep() {
      paymentFallback = false;
      const price = buildSummary();
      const isQuote = price <= 0;
      const method = selectedPaymentMethod();

      // Quote-only selections (commercial / quote services) can't be pre-paid,
      // so the card/cash picker is irrelevant — hide it.
      if (payMethodWrap) payMethodWrap.hidden = isQuote;

      if (isQuote) {
        squareReady = false;
        if (cardBlock) cardBlock.hidden = true;
        if (cashBlock) cashBlock.hidden = true;
        if (quoteBlock) quoteBlock.hidden = false;
        if (payLabel) payLabel.textContent = "Request Booking";
        status.textContent = "";
        return;
      }

      if (quoteBlock) quoteBlock.hidden = true;

      // Cash: no online payment, customer pays on service via the request flow.
      if (method === "cash") {
        squareReady = false;
        if (cardBlock) cardBlock.hidden = true;
        if (cashBlock) {
          cashBlock.hidden = false;
          const amount = qs("[data-cash-amount]", cashBlock);
          if (amount) amount.textContent = money(price);
        }
        if (payLabel) payLabel.textContent = "Confirm Booking";
        status.textContent = "";
        return;
      }

      // Card: load Square and show the card field.
      if (cashBlock) cashBlock.hidden = true;
      if (cardBlock) cardBlock.hidden = false;
      if (payLabel) payLabel.textContent = `Pay ${money(price)} & Confirm Booking`;
      if (!window.SquarePayment) {
        useRequestFallback(squareUnavailableMessage());
        return;
      }
      status.textContent = "Loading secure payment…";
      squareReady = await window.SquarePayment.init();
      if (squareReady) {
        status.textContent = "";
      } else {
        // Square not configured / failed → keep booking working via the request flow.
        useRequestFallback(squareUnavailableMessage());
        status.textContent = "";
      }
    }

    qsa(".step-next-btn", wizard).forEach((button) => {
      button.addEventListener("click", () => {
        const currentPanel = Number(button.closest("[data-panel]")?.dataset.panel || 1);
        const next = Number(button.dataset.next);
        if (!panelIsValid(currentPanel)) return;
        status.textContent = "";
        goToStep(next);
        if (next === 4) buildSummary();
        if (next === 5) enterPaymentStep();
      });
    });

    qsa(".step-back-btn", wizard).forEach((button) => {
      button.addEventListener("click", () => {
        status.textContent = "";
        goToStep(Number(button.dataset.prev));
      });
    });

    qsa("[data-service-button]", form).forEach((button) => {
      button.addEventListener("click", () => {
        const card = button.closest("[data-service-card]");
        const input = card && qs("[data-service-select]", card);
        if (!input) return;
        setServiceQuantity(input, input.checked ? 0 : 1);
        refreshServiceCards();
        buildSummary();
      });
    });

    wizard.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : event.target?.parentElement;
      const button = target?.closest("[data-quantity-action]");
      if (!button || !wizard.contains(button)) return;
      const serviceId = button.dataset.serviceId
        || button.closest("[data-service-card]")?.dataset.serviceId
        || "";
      const input = serviceId ? selectInputForService(serviceId) : null;
      if (!input) return;
      const current = serviceQuantity(input);
      const next = button.dataset.quantityAction === "increment" ? current + 1 : current - 1;
      setServiceQuantity(input, next);
      refreshServiceCards();
      const activePanel = qsa("[data-panel]", wizard).find((panel) => !panel.hidden);
      if (Number(activePanel?.dataset.panel || 0) === 5) {
        enterPaymentStep();
      } else {
        buildSummary();
      }
    });

    ["vehicle_size", "number_of_headlights"].forEach((name) => {
      const field = findFieldByName(name);
      field?.addEventListener("change", () => {
        refreshServiceCards();
        buildSummary();
      });
    });

    payMethodRadios.forEach((radio) => {
      radio.addEventListener("change", () => {
        status.textContent = "";
        enterPaymentStep();
      });
    });

    const requestedService = new URLSearchParams(window.location.search).get("service");
    if (requestedService) {
      const input = qsa("[data-service-select]", form).find((item) => item.value === requestedService);
      if (input) {
        input.checked = true;
      }
    }
    refreshServiceCards();

    function setSubmitting(isLoading, labelText) {
      payButton.disabled = isLoading;
      if (payLabel && labelText) payLabel.textContent = labelText;
    }

    async function submitQuoteRequest() {
      // Quote-only services keep the original "request booking" flow (no charge).
      try {
        const response = await fetch(config.contact?.bookingEndpoint || "/form", {
          method: "POST",
          body: new FormData(form)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          status.textContent = result.message || "Something went wrong. Please try again or contact us directly.";
          window.ShiningGSAP?.playFormErrorAnimation(form);
          return;
        }
        window.ShiningGSAP?.playFormSuccessAnimation(form);
        window.location.href = result.redirect_url || `/thank-you?booking=${encodeURIComponent(result.booking_id || "")}`;
      } catch {
        status.textContent = "Something went wrong. Please try again or contact us directly.";
        window.ShiningGSAP?.playFormErrorAnimation(form);
      }
    }

    async function submitPayment() {
      if (!squareReady || !window.SquarePayment) {
        status.textContent = "Payment form is not ready. Please wait a moment and try again.";
        return;
      }
      status.textContent = "Processing your payment…";
      const tokenResult = await window.SquarePayment.tokenize();
      if (!tokenResult.ok) {
        status.textContent = tokenResult.error;
        window.ShiningGSAP?.playFormErrorAnimation(form);
        return;
      }

      const fd = new FormData(form);
      const booking = {};
      fd.forEach((value, key) => {
        const quantityMatch = key.match(/^service_quantities\[([^\]]+)\]$/);
        if (quantityMatch) {
          booking.service_quantities = booking.service_quantities || {};
          booking.service_quantities[quantityMatch[1]] = value;
          return;
        }
        const cleanKey = key.endsWith("[]") ? key.slice(0, -2) : key;
        if (key.endsWith("[]")) {
          booking[cleanKey] = booking[cleanKey] || [];
          booking[cleanKey].push(value);
        } else {
          booking[cleanKey] = value;
        }
      });

      const payload = {
        sourceId: tokenResult.token,
        idempotencyKey: (window.crypto?.randomUUID && window.crypto.randomUUID()) ||
          ("idem-" + Date.now() + "-" + Math.random().toString(36).slice(2)),
        booking
      };

      try {
        const response = await fetch(window.squarePaymentEndpoint || "/api/payments/square", {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          const firstError = result.errors ? Object.values(result.errors)[0] : "";
          status.textContent = firstError || result.message || "Payment failed. Please try another card.";
          window.ShiningGSAP?.playFormErrorAnimation(form);
          return;
        }
        status.textContent = "Payment successful! Confirming your booking…";
        window.ShiningGSAP?.playFormSuccessAnimation(form);
        window.location.href = result.redirect_url || `/thank-you?booking=${encodeURIComponent(result.bookingId || "")}`;
      } catch {
        status.textContent = "We couldn't reach the payment server. Please try again.";
        window.ShiningGSAP?.playFormErrorAnimation(form);
      }
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      qsa("[aria-invalid]", form).forEach((field) => field.removeAttribute("aria-invalid"));

      const invalid = invalidRequiredFields(form);
      if (invalid.length) {
        showInvalidField(invalid[0]);
        return;
      }

      const price = buildSummary();
      const method = selectedPaymentMethod();
      const isCash = method === "cash" && price > 0;
      const useRequest = price <= 0 || paymentFallback || isCash;
      setSubmitting(true, useRequest ? "Sending…" : "Processing…");
      window.ShiningGSAP?.playFormSubmittingAnimation(form);

      const idleLabel = isCash
        ? "Confirm Booking"
        : (price <= 0 || paymentFallback ? "Request Booking" : `Pay ${money(price)} & Confirm Booking`);

      try {
        if (useRequest) {
          await submitQuoteRequest();
        } else {
          await submitPayment();
        }
      } finally {
        if (!window.location.href.includes("/thank-you")) {
          setSubmitting(false, idleLabel);
        }
      }
    });
  }

  function addressComponent(components, type, useShort = false) {
    const component = components?.find((item) => item.types?.includes(type));
    return component ? (useShort ? component.short_name : component.long_name) : "";
  }

  function initGoogleBookingAutocomplete() {
    const input = qs("#addressInput");
    const mapEl = qs("#bookingMap");
    const mapWrap = qs("#bookingMapPreview");
    const mapLabel = qs("#bookingMapLabel");
    const fallback = qs("#addressFallback");
    const placeIdInput = qs("#googlePlaceId");
    const formattedInput = qs("#formattedAddress");
    const suburbInput = qs("#addressSuburb");
    const postcodeInput = qs("#addressPostcode");
    const stateInput = qs("#addressState");
    const countryInput = qs("#addressCountry");
    const latInput = qs("#addressLat");
    const lngInput = qs("#addressLng");
    if (!input) return;
    if (!window.google?.maps?.places) {
      if (fallback) fallback.hidden = false;
      return;
    }
    if (fallback) fallback.hidden = true;

    const brisbane = { lat: -27.4698, lng: 153.0251 };
    let map = null;
    let marker = null;
    let geocodeTimer = null;

    function ensureMap(position, label) {
      if (!mapEl || !mapWrap) return;
      mapWrap.hidden = false;
      if (!map) {
        map = new google.maps.Map(mapEl, {
          center: position,
          zoom: 15,
          mapTypeControl: false,
          streetViewControl: false,
          fullscreenControl: false
        });
        marker = new google.maps.Marker({ map, position });
      } else {
        map.setCenter(position);
        map.setZoom(15);
        marker?.setPosition(position);
      }
      if (mapLabel) mapLabel.textContent = label || input.value;
    }

    function setLocation({ placeId = "", address = "", lat = "", lng = "", components = [] }) {
      const suburb = addressComponent(components, "locality")
        || addressComponent(components, "postal_town")
        || addressComponent(components, "sublocality")
        || addressComponent(components, "administrative_area_level_2");
      const postcode = addressComponent(components, "postal_code");
      const state = addressComponent(components, "administrative_area_level_1", true);
      const country = addressComponent(components, "country", true);
      if (address) input.value = address;
      if (placeIdInput) placeIdInput.value = placeId;
      if (formattedInput) formattedInput.value = address || input.value;
      if (suburbInput) suburbInput.value = suburb;
      if (postcodeInput) postcodeInput.value = postcode;
      if (stateInput) stateInput.value = state;
      if (countryInput) countryInput.value = country;
      if (latInput) latInput.value = lat;
      if (lngInput) lngInput.value = lng;
      if (lat !== "" && lng !== "") ensureMap({ lat: Number(lat), lng: Number(lng) }, address || input.value);
    }

    const autocomplete = new google.maps.places.Autocomplete(input, {
      componentRestrictions: { country: "au" },
      fields: ["place_id", "formatted_address", "geometry", "name", "address_components"],
      bounds: new google.maps.LatLngBounds(
        new google.maps.LatLng(-28.45, 152.55),
        new google.maps.LatLng(-26.75, 153.65)
      ),
      strictBounds: false
    });

    autocomplete.addListener("place_changed", () => {
      const place = autocomplete.getPlace();
      const location = place.geometry?.location;
      if (!location) return;
      setLocation({
        placeId: place.place_id || "",
        address: place.formatted_address || place.name || input.value,
        lat: location.lat().toFixed(7),
        lng: location.lng().toFixed(7),
        components: place.address_components || []
      });
    });

    const geocoder = new google.maps.Geocoder();
    input.addEventListener("input", () => {
      if (placeIdInput) placeIdInput.value = "";
      if (formattedInput) formattedInput.value = "";
      if (suburbInput) suburbInput.value = "";
      if (postcodeInput) postcodeInput.value = "";
      if (stateInput) stateInput.value = "";
      if (countryInput) countryInput.value = "";
      if (latInput) latInput.value = "";
      if (lngInput) lngInput.value = "";
      window.clearTimeout(geocodeTimer);
      const query = input.value.trim();
      if (query.length < 4) return;
      geocodeTimer = window.setTimeout(() => {
        geocoder.geocode({
          address: query,
          componentRestrictions: { country: "AU" },
          bounds: {
            north: -26.75,
            south: -28.45,
            east: 153.65,
            west: 152.55
          }
        }, (results, status) => {
          if (status !== "OK" || !results?.[0]) return;
          const result = results[0];
          const location = result.geometry.location;
          setLocation({
            placeId: result.place_id || "",
            address: result.formatted_address || query,
            lat: location.lat().toFixed(7),
            lng: location.lng().toFixed(7),
            components: result.address_components || []
          });
        });
      }, 900);
    });

    ensureMap(brisbane, "Brisbane, Queensland");
  }

  window.initGoogleBookingAutocomplete = initGoogleBookingAutocomplete;
  initBookingWizard();
  window.setTimeout(() => {
    const input = qs("#addressInput");
    const fallback = qs("#addressFallback");
    if (input && fallback && !window.google?.maps?.places) fallback.hidden = false;
  }, 3500);
})();
