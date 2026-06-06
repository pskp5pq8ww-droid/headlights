/* admin.js — password show/hide toggle on the admin login screen. */
(function () {
  const input  = document.getElementById("adminPassword");
  const toggle = document.getElementById("pwToggle");
  if (!input || !toggle) return;
  toggle.addEventListener("click", () => {
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    toggle.classList.toggle("is-on", show);
    toggle.setAttribute("aria-label", show ? "Hide password" : "Show password");
  });
})();
