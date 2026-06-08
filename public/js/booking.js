/* booking.js — manual address booking wizard. No maps/autocomplete. */
(function () {
  const config = window.siteConfig || {};
  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));

  function initBookingWizard() {
    const wizard = qs("#bookingWizard");
    const form = qs("#bookingForm", wizard || document);
    const status = qs("[data-form-status]", wizard || document);
    if (!wizard || !form || !status) return;

    const fieldLabels = {
      customer_address: "service address or suburb",
      package: "service/package",
      headlight_condition: "headlight condition",
      name: "full name",
      phone: "phone",
      email: "email",
      vehicle: "vehicle make and model",
      date: "preferred date",
      time: "preferred time window"
    };

    function goToStep(step) {
      qsa("[data-panel]", wizard).forEach((panel) => {
        panel.hidden = Number(panel.dataset.panel) !== step;
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

    qsa(".step-next-btn", wizard).forEach((button) => {
      button.addEventListener("click", () => {
        const currentPanel = Number(button.closest("[data-panel]")?.dataset.panel || 1);
        const next = Number(button.dataset.next);
        if (!panelIsValid(currentPanel)) return;
        status.textContent = "";
        goToStep(next);
      });
    });

    qsa(".step-back-btn", wizard).forEach((button) => {
      button.addEventListener("click", () => {
        status.textContent = "";
        goToStep(Number(button.dataset.prev));
      });
    });

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      qsa("[aria-invalid]", form).forEach((field) => field.removeAttribute("aria-invalid"));

      const invalid = invalidRequiredFields(form);
      if (invalid.length) {
        showInvalidField(invalid[0]);
        return;
      }

      const button = qs('button[type="submit"]', form);
      button.disabled = true;
      button.querySelector("span").textContent = "Sending...";
      status.textContent = "";
      window.ShiningGSAP?.playFormSubmittingAnimation(form);

      try {
        const response = await fetch(config.contact?.bookingEndpoint || "/form", {
          method: "POST",
          body: new FormData(form)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          const firstError = result.errors ? Object.values(result.errors)[0] : "";
          status.textContent = firstError || result.message || "Something went wrong. Please try again or contact us directly.";
          const firstErrorField = result.errors ? findFieldByName(Object.keys(result.errors)[0]) : null;
          if (firstErrorField) showInvalidField(firstErrorField);
          window.ShiningGSAP?.playFormErrorAnimation(form);
          return;
        }
        status.textContent = "Thanks! Your booking request has been received. We'll contact you shortly to confirm your mobile service.";
        window.ShiningGSAP?.playFormSuccessAnimation(form);
        form.reset();
        goToStep(1);
      } catch {
        status.textContent = "Something went wrong. Please try again or contact us directly.";
        window.ShiningGSAP?.playFormErrorAnimation(form);
      } finally {
        button.disabled = false;
        button.querySelector("span").textContent = "Request Booking";
      }
    });
  }

  initBookingWizard();
})();
