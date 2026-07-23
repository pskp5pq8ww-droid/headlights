<?php
require_once __DIR__ . '/includes/config.php';
$current    = 'terms';
$page_title = 'Terms & Conditions | ' . $SITE['name'];
$page_desc  = 'Terms and conditions for Shining Headlights Australia mobile headlight restoration bookings.';
include __DIR__ . '/includes/header.php';
?>
      <section class="section section-dark page-hero">
        <div class="hero-bg" aria-hidden="true"></div>
        <div class="container narrow center">
          <p class="eyebrow">Terms &amp; Conditions</p>
          <h1 class="reveal"><?= htmlspecialchars($SITE['name']) ?></h1>
          <p class="hero-lede reveal">Mobile Headlight Restoration</p>
        </div>
      </section>

      <section class="section section-light">
        <div class="container narrow legal-content">
          <p class="legal-intro">By booking a service with <?= htmlspecialchars($SITE['name']) ?>, the customer agrees to the following Terms &amp; Conditions.</p>

          <h2>1. Service Description</h2>
          <p><?= htmlspecialchars($SITE['name']) ?> provides mobile headlight restoration services for vehicle headlights affected by external oxidation, yellowing, cloudiness, UV damage, and surface-level fading.</p>
          <p>The service is designed to improve the appearance and clarity of plastic/polycarbonate headlight lenses. Results may vary depending on the age, condition, material, previous treatments, depth of damage, and overall condition of the headlights.</p>
          <p>This service does not replace damaged headlight assemblies, electrical components, bulbs, seals, internal reflectors, or wiring.</p>

          <h2>2. Special Limited-Time Offer</h2>
          <p>The special promotional price of $99 applies to a standard mobile restoration service for a standard pair of front headlights, within selected Brisbane service areas.</p>
          <p>The regular price is $220, and the special promotional price is available for a limited time only.</p>
          <p>The $99 offer may not apply where headlights require additional work due to severe oxidation, deep scratches, peeling factory coating, cracked lenses, internal moisture, previous failed restoration attempts, oversized headlights, commercial vehicles, or non-standard vehicle conditions.</p>
          <p><?= htmlspecialchars($SITE['name']) ?> reserves the right to inspect the headlights before starting and confirm whether the promotional price applies.</p>

          <h2>3. Mobile Service Area</h2>
          <p>Mobile service is included within selected Brisbane suburbs and surrounding service areas.</p>
          <p>Bookings outside the regular service area may be declined or may require an additional travel fee, which will be confirmed before the booking proceeds.</p>
          <p>The customer must provide an accurate service address at the time of booking.</p>

          <h2>4. Customer Responsibilities</h2>
          <p>The customer must provide a safe, legal, and suitable workspace for the service to be completed.</p>
          <p>The workspace must be safe, flat, accessible, away from moving traffic where possible, large enough for the technician to work around the front of the vehicle, free from children, pets, pedestrians, and unnecessary people during the service, and legally available for the vehicle to be parked during the appointment.</p>
          <p>If the location is unsafe, illegal, too narrow, exposed to dangerous traffic, or unsuitable for the service, <?= htmlspecialchars($SITE['name']) ?> may refuse, reschedule, or cancel the booking.</p>

          <h2>5. Safety During Service</h2>
          <p>For safety reasons, the customer, children, pets, and other people must remain clear of the work area while the service is being performed.</p>
          <p>The customer agrees not to touch tools, chemicals, equipment, headlights, or treated surfaces during the service unless instructed by the technician.</p>
          <p><?= htmlspecialchars($SITE['name']) ?> is not responsible for injuries, interruptions, contamination, or poor results caused by the customer or third parties entering or interfering with the work area.</p>

          <h2>6. Weather Conditions</h2>
          <p>As this is a mobile service, weather may affect the appointment.</p>
          <p>Rain, extreme heat, strong wind, dust, unsafe weather, or poor lighting may require the booking to be rescheduled.</p>
          <p>If weather conditions make it unsafe or unsuitable to complete the service properly, <?= htmlspecialchars($SITE['name']) ?> may reschedule the appointment at no extra charge.</p>

          <h2>7. Vehicle Condition &amp; Limitations</h2>
          <p>The customer understands that some headlights may not be fully restorable.</p>
          <p>The service may not achieve perfect or "brand new" results if the headlights have internal fogging or condensation, cracks, holes, broken seals, water inside the lens, burn marks, internal reflector damage, deep scratches, stone damage, severe UV damage below the surface, previous coatings, sprays, failed restoration products, aftermarket or non-standard headlight materials, or damage on the inside of the lens.</p>
          <p>In these cases, restoration may improve the headlights but may not completely remove all defects.</p>

          <h2>8. Results Disclaimer</h2>
          <p>Before-and-after photos shown in advertising are examples of previous work and are not a guarantee of identical results.</p>
          <p>Results vary depending on lens condition, vehicle age, previous damage, and maintenance after the service.</p>
          <p><?= htmlspecialchars($SITE['name']) ?> will perform the service with reasonable care and skill, but does not guarantee that every headlight will look new or achieve a specific level of clarity.</p>

          <h2>9. Aftercare</h2>
          <p>After the service, the customer must follow any aftercare instructions provided by the technician.</p>
          <p>The customer should avoid washing, touching, polishing, applying chemicals, or exposing the treated headlights to unnecessary contamination during the curing period advised by the technician.</p>
          <p>Failure to follow aftercare instructions may affect the durability and final result of the restoration.</p>

          <h2>10. Bookings, Cancellations &amp; Rescheduling</h2>
          <p>Customers should provide reasonable notice if they need to cancel or reschedule.</p>
          <p><?= htmlspecialchars($SITE['name']) ?> may reschedule a booking due to weather, unsafe location, vehicle condition, illness, traffic delays, equipment issues, or circumstances outside reasonable control.</p>
          <p>If the customer is not present, the vehicle is unavailable, the location is unsafe, or access is not provided at the scheduled time, the booking may be cancelled or rescheduled.</p>

          <h2>11. Payment</h2>
          <p>Payment is due upon completion of the service unless otherwise agreed in writing.</p>
          <p>Accepted payment methods may include cash, card, bank transfer, or online payment, depending on availability.</p>
          <p>The customer agrees to pay any confirmed additional charges before extra work is carried out.</p>

          <h2>12. Refunds &amp; Consumer Rights</h2>
          <p><?= htmlspecialchars($SITE['name']) ?> complies with Australian Consumer Law.</p>
          <p>Nothing in these Terms &amp; Conditions excludes, restricts, or modifies any consumer guarantee, right, or remedy that cannot be excluded under Australian Consumer Law.</p>
          <p>If there is an issue with the service, the customer should contact <?= htmlspecialchars($SITE['name']) ?> as soon as possible so the matter can be reviewed and, where appropriate, corrected.</p>
          <p>Refunds, rework, or remedies will be assessed based on the nature of the issue, the condition of the headlights, the service performed, and applicable consumer rights.</p>

          <h2>13. Photos &amp; Marketing</h2>
          <p><?= htmlspecialchars($SITE['name']) ?> may take before-and-after photos of headlights for service records, quality control, and marketing purposes.</p>
          <p>Vehicle number plates and personal identifying details may be blurred or removed where appropriate.</p>
          <p>If the customer does not want their vehicle photos used for marketing, they must notify <?= htmlspecialchars($SITE['name']) ?> before or during the appointment.</p>

          <h2>14. Liability</h2>
          <p><?= htmlspecialchars($SITE['name']) ?> is not responsible for pre-existing damage, including cracks, broken clips, loose trims, moisture inside headlights, faulty seals, paint damage, previous sanding marks, previous coating damage, electrical issues, or worn headlight assemblies.</p>
          <p><?= htmlspecialchars($SITE['name']) ?> is not liable for loss, damage, delay, or poor results caused by unsafe work areas, customer interference, third-party interference, weather, hidden defects, or failure to follow aftercare instructions.</p>

          <h2>15. Right to Refuse Service</h2>
          <p><?= htmlspecialchars($SITE['name']) ?> reserves the right to refuse, stop, cancel, or reschedule a service if the work area is unsafe, the vehicle is not accessible, the customer behaves aggressively or disrespectfully, children, pets, or other people enter the work area, the headlights are not suitable for restoration, weather conditions make the service unsafe or unsuitable, or the customer refuses to accept these Terms &amp; Conditions.</p>

          <h2>16. Agreement</h2>
          <p>By booking a service, the customer confirms that they have read, understood, and accepted these Terms &amp; Conditions.</p>
        </div>
      </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
