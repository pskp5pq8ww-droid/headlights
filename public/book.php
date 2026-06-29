<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/bookings.php';
require_once __DIR__ . '/includes/service-cards.php';
$current      = 'book';
$page_title   = 'Book Now | ' . $SITE['name'];
$page_desc    = 'Book your mobile headlight restoration in Brisbane. Tell us where your car is located and we will confirm your booking.';
$page_scripts = ['booking', 'countdown', 'square'];
$page_maps    = true;
$body_class   = 'booking-page';
include __DIR__ . '/includes/header.php';
$services = read_services(true);
$services[] = test_payment_service(); // TEMP: $1 gateway-test item (remove before launch)
$mainServices = array_values(array_filter($services, fn($service) => ($service['slug'] ?? '') === 'headlight-restoration'));
$extraServices = array_values(array_filter($services, fn($service) => ($service['slug'] ?? '') !== 'headlight-restoration'));
?>
      <section class="section section-light booking" id="booking" aria-labelledby="booking-title">
        <div class="container booking-grid">
          <div class="section-copy reveal">
            <span class="eofy-badge">EOFY LIMITED TIME ONLY</span>
            <h1 id="booking-title">Book your mobile headlight restoration</h1>
            <p>Tell us where your vehicle is located and we'll contact you to confirm your booking.</p>

            <div class="promo-hero-card compact reveal">
              <p class="promo-kicker">Limited EOFY Offer</p>
              <div class="promo-price-row">
                <span class="promo-now">$<?= $EOFY['now'] ?></span>
                <span class="promo-was">Was $<?= $EOFY['was'] ?></span>
                <span class="promo-save">Save $<?= $EOFY['save'] ?></span>
              </div>
              <div class="countdown" data-countdown data-target="<?= htmlspecialchars($EOFY['target']) ?>" aria-label="Offer countdown">
                <span><strong data-days>00</strong><small>Days</small></span>
                <span><strong data-hours>00</strong><small>Hours</small></span>
                <span><strong data-minutes>00</strong><small>Minutes</small></span>
                <span><strong data-seconds>00</strong><small>Seconds</small></span>
              </div>
            </div>
          </div>

          <div class="booking-form reveal" id="bookingWizard">
            <div class="form-steps" aria-label="Booking steps">
              <div class="form-step active" data-step-indicator="1"><span class="step-num">1</span><span class="step-label">Location</span></div>
              <span class="step-line" aria-hidden="true"></span>
              <div class="form-step" data-step-indicator="2"><span class="step-num">2</span><span class="step-label">Service</span></div>
              <span class="step-line" aria-hidden="true"></span>
              <div class="form-step" data-step-indicator="3"><span class="step-num">3</span><span class="step-label">Details</span></div>
              <span class="step-line" aria-hidden="true"></span>
              <div class="form-step" data-step-indicator="4"><span class="step-num">4</span><span class="step-label">Payment</span></div>
            </div>

            <form id="bookingForm" action="/form" method="post" enctype="multipart/form-data" novalidate>
              <div class="form-panel" data-panel="1">
                <h3 class="panel-title">Where is your car?</h3>
                <p class="panel-hint">Start typing your suburb or address and choose the closest Google suggestion.</p>
                <label>Service address or suburb
                  <input id="addressInput" name="customer_address" class="address-input" type="text"
                         placeholder="Example: Newstead, Brisbane or full address if ready" autocomplete="street-address" required />
                </label>
                <p class="address-fallback" id="addressFallback" hidden>Address suggestions are not available right now. Please type the full service address manually.</p>
                <input type="hidden" name="google_place_id" id="googlePlaceId" />
                <input type="hidden" name="formatted_address" id="formattedAddress" />
                <input type="hidden" name="address_suburb" id="addressSuburb" />
                <input type="hidden" name="address_postcode" id="addressPostcode" />
                <input type="hidden" name="address_state" id="addressState" />
                <input type="hidden" name="address_country" id="addressCountry" />
                <input type="hidden" name="address_lat" id="addressLat" />
                <input type="hidden" name="address_lng" id="addressLng" />
                <div class="map-preview" id="bookingMapPreview" hidden>
                  <div id="bookingMap" class="booking-map" aria-label="Selected service location map"></div>
                  <p class="map-label" id="bookingMapLabel"></p>
                </div>
                <label>Vehicle location type
                  <select name="vehicle_location_type">
                    <option>Home</option>
                    <option>Workplace</option>
                    <option>Driveway</option>
                    <option>Car park</option>
                    <option>Other</option>
                  </select>
                </label>
                <button class="button button-primary step-next-btn" type="button" data-next="2">
                  <span>Continue</span>
                  <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                </button>
              </div>

              <div class="form-panel" data-panel="2" hidden>
                <h3 class="panel-title">Choose your service</h3>
                <input type="hidden" name="package" id="hiddenPackage" value="EOFY Launch Offer – $99" required />
                <label>Vehicle size
                  <select name="vehicle_size" id="vehicleSizeSelect" required>
                    <option value="">Select vehicle size</option>
<?php foreach (VEHICLE_SIZE_LABELS as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
<?php endforeach; ?>
                  </select>
                </label>
                <label>Headlight condition
                  <select name="headlight_condition" required>
                    <option value="">Select condition</option>
                    <option>Lightly cloudy</option>
                    <option>Yellow / oxidised</option>
                    <option>Very cloudy</option>
                    <option>Not sure</option>
                  </select>
                </label>
                <label>Number of headlights
                  <select name="number_of_headlights" id="numberOfHeadlights">
                    <option>2</option>
                    <option>1</option>
                  </select>
                </label>
                <div class="booking-services-block">
                  <div class="booking-services-heading">
                    <p class="eyebrow">Service menu</p>
                    <p>Headlight restoration is selected first. Open extra services only if you want to add more shine.</p>
                  </div>
                  <div class="booking-primary-service">
<?php render_service_cards($mainServices ?: array_slice($services, 0, 1), ['selectable' => true, 'compact' => true]); ?>
                  </div>
<?php if ($extraServices): ?>
                  <details class="booking-extras-menu" data-extras-menu>
                    <summary>
                      <span>Add extra shine</span>
                      <small>Wash, interior, trim, glass and protection</small>
                    </summary>
                    <div class="service-card-grid booking-service-grid">
<?php render_service_cards($extraServices, ['selectable' => true, 'compact' => true]); ?>
                    </div>
                  </details>
<?php endif; ?>
                  <aside class="booking-cart" data-booking-cart hidden aria-live="polite">
                    <div class="booking-cart-head">
                      <span>Cart</span>
                      <strong data-cart-count>0 services</strong>
                    </div>
                    <div class="booking-cart-items" data-cart-items></div>
                    <div class="booking-cart-total">
                      <span>Estimated total</span>
                      <strong data-cart-total>—</strong>
                    </div>
                    <p>Final price may change after inspection if vehicle condition is excessive.</p>
                  </aside>
                </div>
                <div class="step-nav">
                  <button class="button button-secondary step-back-btn" type="button" data-prev="1">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6" /></svg><span>Back</span>
                  </button>
                  <button class="button button-primary step-next-btn" type="button" data-next="3">
                    <span>Continue</span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                  </button>
                </div>
              </div>

              <div class="form-panel" data-panel="3" hidden>
                <h3 class="panel-title">Your details</h3>
                <div class="form-row">
                  <label>Full name<input name="name" autocomplete="name" required /></label>
                  <label>Phone<input name="phone" type="tel" autocomplete="tel" required /></label>
                </div>
                <label>Email<input name="email" type="email" autocomplete="email" required /></label>
                <label>Vehicle make and model<input name="vehicle" placeholder="Toyota Corolla, Mazda 3..." required /></label>
                <div class="form-row">
                  <label>Preferred date<input name="date" type="date" required /></label>
                  <label>Preferred time window
                    <select name="time" required>
                      <option value="">Select a time</option>
                      <option>Morning</option>
                      <option>Midday</option>
                      <option>Afternoon</option>
                      <option>After work</option>
                    </select>
                  </label>
                </div>
                <label>Preferred contact method
                  <select name="preferred_contact_method">
                    <option>Phone</option>
                    <option>SMS</option>
                    <option>Email</option>
                    <option>WhatsApp</option>
                  </select>
                </label>
                <label class="label-optional">Headlight photos <small>(optional, images only)</small>
                  <input name="photos[]" type="file" accept="image/*" multiple />
                </label>
                <label class="label-optional">Message / notes <small>(optional)</small>
                  <textarea name="message" rows="3" placeholder="Access, parking, headlight condition..."></textarea>
                </label>
                <div class="step-nav">
                  <button class="button button-secondary step-back-btn" type="button" data-prev="2">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6" /></svg><span>Back</span>
                  </button>
                  <button class="button button-primary step-next-btn" type="button" data-next="4">
                    <span>Review &amp; pay</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                  </button>
                </div>
              </div>

              <div class="form-panel checkout-panel" data-panel="4" hidden>
                <h3 class="panel-title">Review &amp; confirm</h3>
                <div class="pay-summary" id="paySummary" aria-live="polite">
                  <div class="summary-topline">
                    <div>
                      <span>Service</span>
                      <strong data-summary="package">—</strong>
                    </div>
                    <div>
                      <span>Date</span>
                      <strong data-summary="date">—</strong>
                    </div>
                    <div>
                      <span>Time</span>
                      <strong data-summary="time">—</strong>
                    </div>
                  </div>
                  <div class="summary-location-row">
                    <span class="summary-location-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z"/><path d="M12 10.5h.01"/></svg>
                    </span>
                    <div>
                      <span>Location</span>
                      <strong data-summary="location">—</strong>
                    </div>
                    <button class="summary-edit step-back-btn" type="button" data-prev="1">Edit</button>
                  </div>
                  <div class="summary-meta-row">
                    <div>
                      <span>Vehicle</span>
                      <strong data-summary="vehicle">—</strong>
                    </div>
                    <div>
                      <span>Name</span>
                      <strong data-summary="name">—</strong>
                    </div>
                    <div>
                      <span>Vehicle size</span>
                      <strong data-summary="vehicleSize">—</strong>
                    </div>
                    <div>
                      <span>Estimate</span>
                      <strong data-summary="price">—</strong>
                    </div>
                  </div>
                  <div class="summary-services" data-summary-services></div>
                  <p class="summary-note">Final price may change after inspection if vehicle condition is excessive.</p>
                </div>

                <div class="pay-card-block" id="payCardBlock">
                  <label class="square-card-label" for="squareCard">Card details</label>
                  <div class="square-card-shell">
                    <div id="squareCard" class="square-card-field"></div>
                  </div>
                  <p class="pay-secure-note">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 11V8a5 5 0 0 1 10 0v3"/><path d="M6 11h12v10H6z"/></svg>
                    <span><strong>Secure payment.</strong> Your card details are encrypted and processed securely by Square. We never store your card information.</span>
                  </p>
                </div>

                <div class="pay-quote-block" id="payQuoteBlock" hidden>
                  <p class="panel-hint">This option is quote-only. Submit your request and we'll contact you with pricing — no payment needed now.</p>
                </div>

                <div class="checkout-total">
                  <span>Total</span>
                  <strong data-summary="total">—</strong>
                </div>

                <label class="terms-consent" for="termsAccepted">
                  <input id="termsAccepted" name="terms_accepted" type="checkbox" value="1" required />
                  <span>I have read and accept the <a href="/terms" target="_blank" rel="noopener">Terms &amp; Conditions</a>.</span>
                </label>
                <input type="hidden" name="terms_version" value="2026-06-27-eofy" />

                <div class="step-nav">
                  <button class="button button-secondary step-back-btn" type="button" data-prev="3">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6" /></svg><span>Back</span>
                  </button>
                  <button class="button button-primary form-submit" id="payButton" type="submit">
                    <span data-pay-label>Pay &amp; Confirm Booking</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M7 11V8a5 5 0 0 1 10 0v3"/><path d="M6 11h12v10H6z"/></svg>
                  </button>
                </div>
                <p class="terms-short">T&amp;Cs apply. $99 EOFY offer applies to standard front headlight restoration within selected Brisbane service areas. Results vary depending on lens condition. Service does not repair internal moisture, cracks, broken seals, electrical faults, bulbs, or internal damage. Customer must provide a safe, legal, and accessible workspace. Children, pets, and customers must remain clear of the work area during service. Mobile service included in selected suburbs only.</p>
              </div>
              <p class="form-status" role="status" aria-live="polite" data-form-status></p>
            </form>

            <script>
              window.bookingPricing = <?= json_encode(BOOKING_PACKAGE_PRICES, JSON_UNESCAPED_UNICODE) ?>;
              window.bookingServices = <?= json_encode($services, JSON_UNESCAPED_UNICODE) ?>;
              window.bookingVehicleSizes = <?= json_encode(VEHICLE_SIZE_LABELS, JSON_UNESCAPED_UNICODE) ?>;
              window.squareConfigEndpoint = '/api/square-config';
              window.squarePaymentEndpoint = '/api/payments/square';
              window.bookingCurrency = '<?= htmlspecialchars(strtoupper(getenv('SQUARE_CURRENCY') ?: 'AUD')) ?>';
            </script>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
