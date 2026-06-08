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

    function panelIsValid(step) {
      const panel = qs(`[data-panel="${step}"]`, wizard);
      if (!panel) return true;
      const invalid = qsa("[required]", panel).filter((field) => !field.value.trim() || !field.validity.valid);
      qsa("[aria-invalid]", panel).forEach((field) => field.removeAttribute("aria-invalid"));
      if (!invalid.length) return true;
      invalid.forEach((field) => field.setAttribute("aria-invalid", "true"));
      invalid[0].focus();
      status.textContent = invalid[0].type === "email" ? "Please enter a valid email address." : "Please complete all required fields.";
      window.ShiningGSAP?.playFormErrorAnimation(form);
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

      const invalid = qsa("[required]", form).filter((field) => !field.value.trim() || !field.validity.valid);
      if (invalid.length) {
        invalid.forEach((field) => field.setAttribute("aria-invalid", "true"));
        status.textContent = invalid[0].type === "email" ? "Please enter a valid email address." : "Please complete all required fields.";
        window.ShiningGSAP?.playFormErrorAnimation(form);
        invalid[0].focus();
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
          status.textContent = result.message || "Something went wrong. Please try again or contact us directly.";
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
