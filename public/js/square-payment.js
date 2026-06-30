/* square-payment.js — Square Web Payments SDK wrapper (Production). */
(function () {
  const SquarePayment = {
    config: null,
    payments: null,
    card: null,
    ready: false,
    _loading: null,
    lastError: "",

    /** Fetch public config + mount the card field once. Returns true if ready. */
    init() {
      if (this.ready) return Promise.resolve(true);
      if (this._loading) return this._loading;
      this._loading = (async () => {
        try {
          this.lastError = "";
          const res = await fetch(window.squareConfigEndpoint || "/api/square-config", {
            headers: { Accept: "application/json" }
          });
          if (!res.ok) {
            this.lastError = `Square config failed with HTTP ${res.status}.`;
            return false;
          }
          const data = await res.json();
          this.config = (data && data.config) || null;
          if (!this.config || !this.config.configured) {
            const missing = this.config && Array.isArray(this.config.missing) ? this.config.missing.join(", ") : "";
            this.lastError = missing
              ? `Square is missing: ${missing}.`
              : "Square is not configured.";
            return false;
          }

          await this._loadSdk(this.config.sdkUrl);
          if (!window.Square) {
            this.lastError = "Square SDK loaded but Square was not available.";
            return false;
          }

          this.payments = window.Square.payments(this.config.applicationId, this.config.locationId);
          this.card = await this.payments.card();
          await this.card.attach("#squareCard");
          this.ready = true;
          return true;
        } catch (err) {
          this.lastError = err && err.message ? err.message : "Square payment form could not be loaded.";
          if (window.console && window.console.warn) window.console.warn("[SquarePayment]", err);
          return false;
        }
      })();
      // Cache only a successful init. If it failed (config, SDK or attach),
      // clear the cache so re-entering the payment step can retry instead of
      // being stuck on the cached failure forever.
      this._loading = this._loading.then((ok) => {
        if (!ok) this._loading = null;
        return ok;
      });
      return this._loading;
    },

    _loadSdk(url) {
      return new Promise((resolve, reject) => {
        if (window.Square) return resolve();
        const existing = document.querySelector('script[data-square-sdk]');
        if (existing) {
          existing.addEventListener("load", () => resolve());
          existing.addEventListener("error", () => reject(new Error("Square SDK failed to load")));
          return;
        }
        const script = document.createElement("script");
        script.src = url || "https://web.squarecdn.com/v1/square.js";
        script.async = true;
        script.setAttribute("data-square-sdk", "true");
        script.onload = () => resolve();
        script.onerror = () => reject(new Error("Square SDK failed to load"));
        document.head.appendChild(script);
      });
    },

    /** Tokenize the card → { ok, token } | { ok:false, error }. */
    async tokenize() {
      if (!this.ready || !this.card) return { ok: false, error: "Payment form is not ready yet." };
      try {
        const result = await this.card.tokenize();
        if (result.status === "OK") return { ok: true, token: result.token };
        const message = (result.errors && result.errors[0] && result.errors[0].message) || "Please check your card details.";
        return { ok: false, error: message };
      } catch (err) {
        return { ok: false, error: "We couldn't read your card. Please try again." };
      }
    }
  };

  window.SquarePayment = SquarePayment;
})();
