/* square-payment.js — Square Web Payments SDK wrapper (Sandbox + Production). */
(function () {
  const SquarePayment = {
    config: null,
    payments: null,
    card: null,
    ready: false,
    _loading: null,

    /** Fetch public config + mount the card field once. Returns true if ready. */
    init() {
      if (this.ready) return Promise.resolve(true);
      if (this._loading) return this._loading;
      this._loading = (async () => {
        try {
          const res = await fetch(window.squareConfigEndpoint || "/api/square-config", {
            headers: { Accept: "application/json" }
          });
          const data = await res.json();
          this.config = (data && data.config) || null;
          if (!this.config || !this.config.configured) return false;

          await this._loadSdk(this.config.sdkUrl);
          if (!window.Square) return false;

          this.payments = window.Square.payments(this.config.applicationId, this.config.locationId);
          this.card = await this.payments.card();
          await this.card.attach("#squareCard");
          this.ready = true;
          return true;
        } catch (err) {
          this._loading = null;
          return false;
        }
      })();
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
        script.src = url || "https://sandbox.web.squarecdn.com/v1/square.js";
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
