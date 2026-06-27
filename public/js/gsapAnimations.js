(function () {
  if (typeof window === "undefined" || typeof document === "undefined") return;

  const ISOTYPE_SRC = "/assets/shining-headlights-isotype.svg";
  const STAR_PATH = "M50 2 58.2 41.8 98 50 58.2 58.2 50 98 41.8 58.2 2 50 41.8 41.8 50 2Z";
  const SELECTORS = {
    cta: ".button-primary, .button-secondary, .mobile-sticky-cta, .promo-cta, .eofy-main-cta",
    reveal:
      ".offer-card, .promo-hero-card, .comparison-card, .booking-form, .final-panel, .final-band, .landing-feature-card, .landing-review-card, .premium-comparison, .final-eofy-panel"
  };

  const qs = (selector, parent = document) => parent.querySelector(selector);
  const qsa = (selector, parent = document) => Array.from(parent.querySelectorAll(selector));
  const getGsap = () => window.gsap || null;
  const reduceMotion = () => window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;

  function ready(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback, { once: true });
    } else {
      callback();
    }
  }

  function starSvg(className = "brand-star-svg") {
    return `
      <svg class="${className}" viewBox="0 0 100 100" aria-hidden="true" focusable="false">
        <path class="shine-star" d="${STAR_PATH}"></path>
      </svg>
    `;
  }

  function isotypeMarkup(extraClass = "") {
    return `
      <span class="brand-isotype-frame ${extraClass}" aria-hidden="true">
        <img class="brand-isotype-img" src="${ISOTYPE_SRC}" alt="" loading="eager" />
        ${starSvg("brand-star-svg isotype-shine-star")}
      </span>
    `;
  }

  function ensureLoader() {
    let loader = qs("[data-brand-loader]");
    if (loader) return loader;

    loader = document.createElement("div");
    loader.className = "loader-screen";
    loader.dataset.brandLoader = "";
    loader.setAttribute("aria-hidden", "true");
    loader.innerHTML = `
      <div class="loader-content">
        ${isotypeMarkup("loader-isotype")}
        <p>Shining Headlights Australia</p>
      </div>
    `;
    document.body.prepend(loader);
    return loader;
  }

  function ensureTransitionLayer() {
    let layer = qs("[data-star-transition]");
    if (layer) return layer;

    layer = document.createElement("div");
    layer.className = "star-transition-layer";
    layer.dataset.starTransition = "";
    layer.setAttribute("aria-hidden", "true");
    layer.innerHTML = starSvg("transition-star-svg");
    document.body.append(layer);
    return layer;
  }

  function ensureFormLoader(form) {
    let loader = qs("[data-form-loader]", form);
    if (loader) return loader;

    loader = document.createElement("div");
    loader.className = "form-loader";
    loader.dataset.formLoader = "";
    loader.hidden = true;
    loader.innerHTML = `
      ${isotypeMarkup("form-isotype")}
      <span class="form-loader-text">Sending...</span>
    `;
    form.prepend(loader);
    return loader;
  }

  function initBrandLoader() {
    const gsap = getGsap();
    const loader = ensureLoader();
    if (!loader || loader.dataset.played) return;
    loader.dataset.played = "true";

    if (!gsap || reduceMotion()) {
      loader.remove();
      document.body.classList.remove("brand-loader-active");
      return;
    }

    const isotype = qs(".loader-isotype", loader);
    const star = qs(".shine-star", loader);
    const label = qs("p", loader);

    document.body.classList.add("brand-loader-active");

    gsap
      .timeline({
        defaults: { ease: "power3.out" },
        onComplete: () => {
          document.body.classList.remove("brand-loader-active");
          loader.remove();
        }
      })
      .set(loader, { display: "grid", autoAlpha: 0 })
      .set(star, { transformOrigin: "50% 50%" })
      .fromTo(loader, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.28 })
      .fromTo(isotype, { autoAlpha: 0, scale: 0.86, y: 12 }, { autoAlpha: 1, scale: 1, y: 0, duration: 0.72 }, "-=0.05")
      .fromTo(label, { autoAlpha: 0, y: 8 }, { autoAlpha: 1, y: 0, duration: 0.36 }, "-=0.22")
      .fromTo(star, { autoAlpha: 0, scale: 0.38, rotation: -24 }, { autoAlpha: 1, scale: 1, rotation: 0, duration: 0.42, ease: "back.out(2)" }, "-=0.2")
      .to(star, { scale: 1.22, duration: 0.3, yoyo: true, repeat: 1, ease: "sine.inOut" })
      .to(loader, { autoAlpha: 0, duration: 0.55, ease: "power2.inOut" }, "+=0.1");
  }

  function playStarTransition(callback) {
    const gsap = getGsap();
    if (!gsap || reduceMotion()) {
      if (typeof callback === "function") callback();
      return null;
    }

    const layer = ensureTransitionLayer();
    const star = qs(".transition-star-svg", layer);

    // Always leave the overlay hidden, even if the timeline is interrupted
    // (e.g. the click navigates away on mobile) so the giant star can never
    // get stuck covering the page.
    const cleanup = () => {
      gsap.killTweensOf(star);
      gsap.set(layer, { display: "none", autoAlpha: 0 });
      gsap.set(star, { scale: 0 });
    };

    const tl = gsap
      .timeline({ defaults: { ease: "power4.inOut" }, onInterrupt: cleanup })
      .set(layer, { display: "grid", autoAlpha: 1 })
      .set(star, { scale: 0, rotation: -18, transformOrigin: "50% 50%" })
      .to(star, { scale: 0.95, rotation: 0, duration: 0.2, ease: "back.out(2)" })
      .to(star, { scale: 34, duration: 0.56 }, "-=0.02")
      .add(() => {
        if (typeof callback === "function") callback();
      })
      .to(layer, { autoAlpha: 0, duration: 0.36, ease: "power2.out" })
      .set(layer, { display: "none" })
      .set(star, { scale: 0 });

    // Hard safety net: force-hide after a max duration no matter what.
    gsap.delayedCall(1.8, cleanup);
    return tl;
  }

  function decorateCTAButtons(root = document) {
    const gsap = getGsap();
    qsa(SELECTORS.cta, root).forEach((button) => {
      if (button.dataset.brandButtonReady) return;
      button.dataset.brandButtonReady = "true";
      button.classList.add("brand-button");

      if (!qs(".button-light-sweep", button)) {
        const sweep = document.createElement("span");
        sweep.className = "button-light-sweep";
        sweep.setAttribute("aria-hidden", "true");
        button.append(sweep);
      }

      if (!qs(".button-star", button)) {
        const starWrap = document.createElement("span");
        starWrap.className = "button-star";
        starWrap.setAttribute("aria-hidden", "true");
        starWrap.innerHTML = starSvg("button-star-svg");
        button.append(starWrap);
      }

      if (!gsap || reduceMotion()) return;

      const star = qs(".button-star", button);
      const sweep = qs(".button-light-sweep", button);
      const enter = () => {
        gsap.to(star, { autoAlpha: 1, scale: 1, rotation: 90, duration: 0.32, ease: "back.out(2)", overwrite: "auto" });
        gsap.fromTo(sweep, { xPercent: -120, autoAlpha: 0 }, { xPercent: 140, autoAlpha: 1, duration: 0.68, ease: "power3.out", overwrite: "auto" });
      };
      const leave = () => {
        gsap.to(star, { autoAlpha: 0, scale: 0.45, rotation: 0, duration: 0.24, ease: "power2.out", overwrite: "auto" });
      };

      button.addEventListener("mouseenter", enter);
      button.addEventListener("focus", enter);
      button.addEventListener("mouseleave", leave);
      button.addEventListener("blur", leave);
    });
  }

  function initCTAFlash() {
    document.addEventListener("click", (event) => {
      const button = event.target.closest(".button-primary, .mobile-sticky-cta, .promo-cta");
      if (!button || button.disabled || button.type === "submit") return;
      playStarTransition();
    });
  }

  function playFormSubmittingAnimation(form) {
    const gsap = getGsap();
    const loader = ensureFormLoader(form);
    const star = qs(".shine-star", loader);

    loader.hidden = false;
    form.classList.remove("form-state-success", "form-state-error");
    form.classList.add("form-state-submitting");

    if (!gsap || reduceMotion()) return;

    gsap.killTweensOf(star);
    gsap.fromTo(loader, { autoAlpha: 0, y: 8 }, { autoAlpha: 1, y: 0, duration: 0.28, ease: "power2.out" });
    gsap.to(star, {
      scale: 1.22,
      rotation: 90,
      transformOrigin: "50% 50%",
      repeat: -1,
      yoyo: true,
      duration: 0.58,
      ease: "sine.inOut",
      overwrite: "auto"
    });
  }

  function playFormSuccessAnimation(form) {
    const gsap = getGsap();
    const loader = ensureFormLoader(form);
    const star = qs(".shine-star", loader);

    form.classList.remove("form-state-submitting", "form-state-error");
    form.classList.add("form-state-success");

    if (!gsap || reduceMotion()) {
      loader.hidden = true;
      return;
    }

    gsap.killTweensOf(star);
    gsap
      .timeline()
      .to(star, { scale: 1.8, autoAlpha: 1, duration: 0.28, ease: "back.out(2)", overwrite: "auto" })
      .to(loader, { autoAlpha: 0, y: -8, duration: 0.3, ease: "power2.out" }, "+=0.08")
      .add(() => {
        loader.hidden = true;
      })
      .set(star, { scale: 1, rotation: 0 });
  }

  function playFormErrorAnimation(form) {
    const gsap = getGsap();
    const loader = ensureFormLoader(form);
    const isotype = qs(".form-isotype", loader);
    const star = qs(".shine-star", loader);

    form.classList.remove("form-state-submitting", "form-state-success");
    form.classList.add("form-state-error");

    if (!gsap || reduceMotion()) {
      loader.hidden = true;
      return;
    }

    gsap.killTweensOf(star);
    gsap.fromTo(isotype, { x: -4 }, { x: 4, duration: 0.07, repeat: 5, yoyo: true, ease: "power1.inOut", clearProps: "x" });
    gsap.to(loader, { autoAlpha: 0, y: -6, duration: 0.28, delay: 0.45, ease: "power2.out", onComplete: () => { loader.hidden = true; } });
  }

  function initSectionReveals() {
    const gsap = getGsap();
    if (!gsap || reduceMotion() || !("IntersectionObserver" in window)) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          gsap.fromTo(entry.target, { autoAlpha: 0, y: 18 }, { autoAlpha: 1, y: 0, duration: 0.58, ease: "power3.out", overwrite: "auto" });
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.18, rootMargin: "0px 0px -40px" }
    );

    qsa(SELECTORS.reveal).forEach((element) => observer.observe(element));
  }

  function initLandingHero() {
    const gsap = getGsap();
    const hero = qs(".eofy-landing-hero");
    if (!hero || !gsap || reduceMotion()) return;

    const headlineItems = qsa(".sale-pill, .eofy-landing-hero h1, .hero-direct, .eofy-countdown-card, .eofy-price, .eofy-main-cta", hero);
    gsap.fromTo(
      headlineItems,
      { autoAlpha: 0, y: 24 },
      { autoAlpha: 1, y: 0, duration: 0.78, stagger: 0.08, ease: "power3.out", overwrite: "auto" }
    );

    const product = qs(".hero-product-light", hero);
    if (product) {
      gsap.fromTo(product, { autoAlpha: 0, y: 24, scale: 0.98 }, { autoAlpha: 0.72, y: 0, scale: 1, duration: 1.1, ease: "power3.out" });

      const yTo = gsap.quickTo(product, "y", { duration: 0.55, ease: "power3.out" });
      window.addEventListener(
        "scroll",
        () => {
          if (window.scrollY < window.innerHeight * 1.4) yTo(window.scrollY * 0.045);
        },
        { passive: true }
      );
    }
  }

  function initLandingCountdownMotion() {
    const gsap = getGsap();
    if (!gsap || reduceMotion()) return;

    qsa(".eofy-countdown-card").forEach((card) => {
      gsap.to(card, {
        scale: 1.012,
        duration: 2.8,
        repeat: -1,
        yoyo: true,
        ease: "sine.inOut",
        transformOrigin: "50% 50%"
      });
    });

    document.addEventListener("countdown:tick", (event) => {
      const el = event.detail?.element;
      if (!el) return;
      el.classList.add("is-ticking");
      gsap.fromTo(el, { y: -8, autoAlpha: 0.55 }, { y: 0, autoAlpha: 1, duration: 0.34, ease: "power2.out", overwrite: "auto" });
      gsap.delayedCall(0.42, () => el.classList.remove("is-ticking"));
    });
  }

  function initMagneticButtons() {
    const gsap = getGsap();
    if (!gsap || reduceMotion()) return;

    qsa(".eofy-main-cta").forEach((button) => {
      if (button.dataset.magneticReady) return;
      button.dataset.magneticReady = "true";
      const xTo = gsap.quickTo(button, "x", { duration: 0.32, ease: "power3.out" });
      const yTo = gsap.quickTo(button, "y", { duration: 0.32, ease: "power3.out" });

      button.addEventListener("pointermove", (event) => {
        const rect = button.getBoundingClientRect();
        xTo((event.clientX - rect.left - rect.width / 2) * 0.14);
        yTo((event.clientY - rect.top - rect.height / 2) * 0.18);
      });

      button.addEventListener("pointerleave", () => {
        xTo(0);
        yTo(0);
      });
    });
  }

  function initCountdownPulse() {
    const gsap = getGsap();
    if (!gsap || reduceMotion()) return;

    qsa("[data-countdown]").forEach((countdown) => {
      if (countdown.dataset.brandCountdownReady) return;
      countdown.dataset.brandCountdownReady = "true";
      const star = document.createElement("span");
      star.className = "countdown-brand-star";
      star.setAttribute("aria-hidden", "true");
      star.innerHTML = starSvg("countdown-star-svg");
      countdown.prepend(star);

      window.setInterval(() => {
        gsap.fromTo(star, { scale: 0.85, autoAlpha: 0.72 }, { scale: 1.18, autoAlpha: 1, duration: 0.32, yoyo: true, repeat: 1, ease: "sine.inOut" });
      }, 9000);
    });
  }

  function clearStuckOverlays(includeLoader) {
    const layer = qs("[data-star-transition]");
    if (layer) { layer.style.display = "none"; layer.style.opacity = "0"; }
    if (includeLoader) {
      qs("[data-brand-loader]")?.remove();
      document.body.classList.remove("brand-loader-active");
    }
  }

  function initOverlayWatchdog() {
    // Returning via back/forward cache, or refocusing the tab, must never leave
    // a half-played full-screen star or loader covering the content.
    window.addEventListener("pageshow", (event) => clearStuckOverlays(event.persisted));
    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) clearStuckOverlays(false);
    });
    // Failsafe: the brand loader should never outlive the first few seconds.
    window.setTimeout(() => clearStuckOverlays(true), 3500);
  }

  function init() {
    initBrandLoader();
    initOverlayWatchdog();
    decorateCTAButtons();
    initCTAFlash();
    initSectionReveals();
    initCountdownPulse();
    initLandingHero();
    initLandingCountdownMotion();
    initMagneticButtons();
  }

  window.ShiningGSAP = {
    init,
    decorateCTAButtons,
    playStarTransition,
    playFormSubmittingAnimation,
    playFormSuccessAnimation,
    playFormErrorAnimation
  };

  ready(init);
})();
