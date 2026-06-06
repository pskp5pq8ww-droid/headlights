<?php
require_once __DIR__ . '/includes/config.php';
$current      = 'book';
$page_title   = 'Book Now | ' . $SITE['name'];
$page_desc    = 'Book your EOFY mobile headlight restoration in Brisbane. Enter your address, choose your package and pick a time.';
$page_maps    = true;
$page_scripts = ['booking', 'countdown'];
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-light booking" id="booking" aria-labelledby="booking-title">
        <div class="container booking-grid">
          <div class="section-copy reveal">
            <span class="eofy-badge"><?= $EOFY['badge'] ?></span>
            <h1 id="booking-title">Book your mobile headlight restoration</h1>
            <p>Enter your address, choose your package and pick a time. We confirm by phone.</p>

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

            <div class="contact-card">
              <span>Prefer to call?</span>
              <strong>We'll confirm your booking by phone.</strong>
              <a href="<?= htmlspecialchars($SITE['phone_href']) ?>">Call <?= htmlspecialchars($SITE['phone_label']) ?></a>
            </div>
          </div>

          <div class="booking-form reveal" id="bookingWizard">
            <div class="form-steps" aria-label="Booking steps">
              <div class="form-step active" data-step-indicator="1"><span class="step-num">1</span><span class="step-label">Location</span></div>
              <span class="step-line" aria-hidden="true"></span>
              <div class="form-step" data-step-indicator="2"><span class="step-num">2</span><span class="step-label">Package</span></div>
              <span class="step-line" aria-hidden="true"></span>
              <div class="form-step" data-step-indicator="3"><span class="step-num">3</span><span class="step-label">Details</span></div>
            </div>

            <!-- Step 1: address + map -->
            <div class="form-panel" data-panel="1">
              <h3 class="panel-title">Where is your car?</h3>
              <p class="panel-hint">Enter your service address and select a suggestion to confirm.</p>
              <div class="address-wrap">
                <input id="addressInput" name="customer_address" class="address-input" type="text"
                       placeholder="Enter your service address" autocomplete="off" aria-label="Service address" />
              </div>
              <div class="map-preview" id="mapPreview" hidden>
                <div id="googleMapContainer"></div>
                <p class="map-label" id="mapLabel"></p>
              </div>
              <p class="address-error" id="addressError" hidden>Please select a valid address from the suggestions.</p>
              <button class="button button-primary step-next-btn" type="button" data-next="2"><span>Continue</span>
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg></button>
            </div>

            <!-- Step 2: package -->
            <div class="form-panel" data-panel="2" hidden>
              <h3 class="panel-title">Choose your package</h3>
              <p class="panel-hint">One vehicle &middot; Mobile service included &middot; No hidden fees</p>
              <label class="promo-card" for="pkg-launch">
                <input type="radio" id="pkg-launch" name="selectedPackage" value="EOFY Launch Offer – $99" checked />
                <div class="promo-inner">
                  <span class="promo-badge">EOFY Launch Price</span>
                  <div class="promo-prices"><span class="promo-was">Was $<?= $EOFY['was'] ?></span><span class="promo-now">$<?= $EOFY['now'] ?> <small>all-in</small></span></div>
                  <ul class="promo-list">
                    <li>Full multi-stage headlight restoration</li>
                    <li>Crystal clear glossy finish</li>
                    <li>UV protection coating</li>
                    <li>Mobile service at your location</li>
                  </ul>
                  <span class="promo-select-indicator">&#10003; Selected</span>
                </div>
              </label>
              <button class="other-plans-toggle" type="button" id="otherPlansToggle" aria-expanded="false">See standard packages
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" /></svg></button>
              <div class="other-plans" id="otherPlans" hidden><div data-other-packages></div></div>
              <div class="step-nav">
                <button class="button button-secondary step-back-btn" type="button" data-prev="1"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6" /></svg><span>Back</span></button>
                <button class="button button-primary step-next-btn" type="button" data-next="3"><span>Continue</span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg></button>
              </div>
            </div>

            <!-- Step 3: details -->
            <div class="form-panel" data-panel="3" hidden>
              <h3 class="panel-title">Your details</h3>
              <form id="bookingForm" novalidate>
                <input type="hidden" name="suburb"        id="hiddenSuburb" />
                <input type="hidden" name="package"       id="hiddenPackage"      value="EOFY Launch Offer – $99" />
                <input type="hidden" name="full_address"  id="hiddenFullAddress" />
                <input type="hidden" name="place_id"      id="hiddenPlaceId" />
                <input type="hidden" name="latitude"      id="hiddenLat" />
                <input type="hidden" name="longitude"     id="hiddenLng" />
                <input type="hidden" name="street_number" id="hiddenStreetNumber" />
                <input type="hidden" name="street_name"   id="hiddenStreetName" />
                <input type="hidden" name="state"         id="hiddenState" />
                <input type="hidden" name="postcode"      id="hiddenPostcode" />
                <input type="hidden" name="country"       id="hiddenCountry" />
                <div class="form-row">
                  <label>Full name<input name="name" autocomplete="name" required /></label>
                  <label>Phone<input name="phone" type="tel" autocomplete="tel" required /></label>
                </div>
                <label>Email<input name="email" type="email" autocomplete="email" required /></label>
                <label>Vehicle<input name="vehicle" placeholder="Toyota Corolla, Mazda 3&hellip;" required /></label>
                <div class="form-row">
                  <label>Preferred date<input name="date" type="date" required /></label>
                  <label>Time window
                    <select name="time" required>
                      <option value="">Select a time</option>
                      <option>Morning</option><option>Midday</option><option>Afternoon</option><option>After work</option>
                    </select>
                  </label>
                </div>
                <label class="label-optional">Headlight photos <small>(optional)</small><input name="photos[]" type="file" accept="image/*" multiple /></label>
                <label class="label-optional">Notes <small>(optional)</small><textarea name="message" rows="3" placeholder="Access, parking, headlight condition&hellip;"></textarea></label>
                <div class="step-nav">
                  <button class="button button-secondary step-back-btn" type="button" data-prev="2"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M19 12H5m6 6-6-6 6-6" /></svg><span>Back</span></button>
                  <button class="button button-primary form-submit" type="submit"><span>Request Booking</span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6" /></svg></button>
                </div>
                <p class="form-status" role="status" aria-live="polite" data-form-status></p>
              </form>
            </div>
          </div>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
