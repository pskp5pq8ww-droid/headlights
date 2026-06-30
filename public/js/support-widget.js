(function () {
  const widget = document.querySelector("[data-support-widget]");
  if (!widget) return;

  const toggle = widget.querySelector("[data-support-toggle]");
  const panel = widget.querySelector(".support-panel");
  const close = widget.querySelector("[data-support-close]");
  const form = widget.querySelector("[data-support-form]");
  const status = widget.querySelector("[data-support-status]");
  const pageInput = widget.querySelector("[data-support-page]");
  const openers = document.querySelectorAll("[data-support-open]");

  function setOpen(open) {
    if (!toggle || !panel) return;
    toggle.setAttribute("aria-expanded", String(open));
    widget.classList.toggle("is-open", open);
    panel.hidden = !open;
    if (open) {
      window.setTimeout(() => {
        panel.querySelector("textarea, input, select")?.focus({ preventScroll: true });
      }, 80);
    }
  }

  toggle?.addEventListener("click", () => setOpen(!widget.classList.contains("is-open")));
  close?.addEventListener("click", () => setOpen(false));
  openers.forEach((button) => button.addEventListener("click", () => setOpen(true)));
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && widget.classList.contains("is-open")) setOpen(false);
  });

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (!form || !status) return;
    if (pageInput) pageInput.value = window.location.pathname;
    status.textContent = "Sending...";
    widget.classList.remove("has-error", "has-success");
    const submit = form.querySelector("button[type='submit']");
    if (submit) submit.disabled = true;

    try {
      const response = await fetch("/api/tickets", {
        method: "POST",
        body: new FormData(form),
        headers: { "Accept": "application/json" }
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) throw new Error(data.message || "Unable to send your message.");
      form.reset();
      status.textContent = "Ticket sent. We will get back to you soon.";
      widget.classList.add("has-success");
    } catch (error) {
      status.textContent = error.message || "Unable to send your message.";
      widget.classList.add("has-error");
    } finally {
      if (submit) submit.disabled = false;
    }
  });
})();
