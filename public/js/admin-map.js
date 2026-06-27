/* admin-map.js — Google map of bookings for the admin panel. */
(function () {
  function statusColor(status, pay) {
    if (pay === "paid") return "#34c98f";
    switch (status) {
      case "confirmed": return "#00d9ff";
      case "completed": return "#4f9dff";
      case "cancelled": return "#f87171";
      case "contacted": return "#f4b63f";
      default: return "#a8f3ff";
    }
  }

  function esc(value) {
    return String(value == null ? "" : value).replace(/[&<>"]/g, (c) => (
      { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]
    ));
  }

  window.initAdminMap = function () {
    const data = window.adminBookings || [];
    const mapEl = document.getElementById("adminMap");
    if (!mapEl || !window.google || !window.google.maps) return;

    const brisbane = { lat: -27.4698, lng: 153.0251 };
    const map = new google.maps.Map(mapEl, {
      center: brisbane,
      zoom: 10,
      mapTypeControl: false,
      streetViewControl: false,
      fullscreenControl: true
    });
    const info = new google.maps.InfoWindow();
    const bounds = new google.maps.LatLngBounds();
    const markers = {};
    let withCoords = 0;

    data.forEach((b) => {
      if (b.lat == null || b.lng == null) return;
      const pos = { lat: Number(b.lat), lng: Number(b.lng) };
      if (!isFinite(pos.lat) || !isFinite(pos.lng)) return;

      const marker = new google.maps.Marker({
        position: pos,
        map,
        title: b.name,
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 8,
          fillColor: statusColor(b.status, b.paymentStatus),
          fillOpacity: 1,
          strokeColor: "#03070b",
          strokeWeight: 2
        }
      });

      const html =
        '<div class="map-info">' +
        "<strong>" + esc(b.name) + "</strong>" +
        (b.address ? "<span>" + esc(b.address) + "</span>" : "") +
        (b.date ? "<span>" + esc(b.date) + "</span>" : "") +
        "<span>" + esc(b.package || "") + (b.price ? " &middot; $" + esc(b.price) : "") + "</span>" +
        '<span class="map-info-status">' + esc(b.status) + " &middot; " + esc(b.paymentStatus) + "</span>" +
        (b.phone ? '<a href="tel:' + esc(b.phone) + '">' + esc(b.phone) + "</a>" : "") +
        '<a href="/dashboard?id=' + encodeURIComponent(b.id) + '#detail">Open details</a>' +
        "</div>";

      marker.addListener("click", () => {
        info.setContent(html);
        info.open(map, marker);
      });

      markers[b.id] = marker;
      bounds.extend(pos);
      withCoords++;
    });

    if (withCoords > 0) {
      map.fitBounds(bounds);
      google.maps.event.addListenerOnce(map, "idle", () => {
        if (map.getZoom() > 14) map.setZoom(14);
      });
    }

    document.querySelectorAll("[data-map-focus]").forEach((el) => {
      el.addEventListener("click", () => {
        const id = el.getAttribute("data-map-focus");
        const marker = markers[id];
        if (!marker) return;
        map.panTo(marker.getPosition());
        map.setZoom(14);
        google.maps.event.trigger(marker, "click");
        mapEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
      });
    });
  };
})();
