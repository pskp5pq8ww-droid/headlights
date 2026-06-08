<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'book';
$page_title   = 'Book Now | ' . $SITE['name'];
$page_desc    = 'Book your mobile headlight restoration in Brisbane. Tell us where your car is located and we will confirm your booking.';
$page_scripts = ['booking', 'countdown'];
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-light booking" id="booking" aria-labelledby="booking-title">
        <div class="container booking-grid">
          <div class="section-copy reveal">
            <span class="eofy-badge"><?= htmlspecialchars($EOFY['badge']) ?></span>
            <h1 id="booking-title">Book your mobile headlight restoration</h1>
            <p>Tell us where your car is located and we'll contact you to confirm your booking.</p>

            <div class="promo-hero-card compact reveal">
              <div class="promo-price-row">
                <span class="promo-was">Was $<?= $EOFY['was'] ?></span>
                <span class="promo-now">$<?= $EOFY['now'] ?></span>
                <span class="promo-save">Save $<?= $EOFY['save'] ?></span>
              </div>
              <div class="countdown" data-countdown data-target="<?= htmlspecialchars($EOFY['target']) ?>" aria-label="EOFY offer countdown">
                <span><strong data-days>00</strong><small>Days</small></span>
                <span><strong data-hours>00</strong><small>Hrs</small></span>
                <span><strong data-minutes>00</strong><small>Min</small></span>
                <span><strong data-seconds>00</strong><small>Sec</small></span>
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
            </div>

            <form id="bookingForm" action="/form" method="post" enctype="multipart/form-data" novalidate>
              <div class="form-panel" data-panel="1">
                <h3 class="panel-title">Where is your car?</h3>
                <p class="panel-hint">Enter the suburb or service address where the car will be available.</p>
                <label>Service address or suburb
                  <input id="addressInput" name="customer_address" class="address-input" type="text"
                         placeholder="Example: Newstead, Brisbane or full address if ready" autocomplete="street-address" required />
                </label>
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
                <label>Service/package selected
                  <select name="package" id="hiddenPackage" required>
                    <option value="EOFY Launch Offer – $99">EOFY Launch Offer - $99</option>
                    <option>Basic Restore</option>
                    <option>Crystal Restore</option>
                    <option>Premium Protection Restore</option>
                    <option>Not sure / Quote</option>
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
                  <select name="number_of_headlights">
                    <option>2</option>
                    <option>1</option>
                  </select>
                </label>
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
                  <button class="button button-primary form-submit" type="submit">
                    <span>Request Booking</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                  </button>
                </div>
              </div>
              <p class="form-status" role="status" aria-live="polite" data-form-status></p>
            </form>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
