<?php
declare(strict_types=1);

require_once __DIR__ . '/services.php';

if (!function_exists('service_h')) {
    function service_h(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function service_price_text(array $service): string {
    if ($service['slug'] === 'headlight-restoration') return 'From $' . number_format((float)$service['priceSmall'], 0);
    $from = service_from_price($service);
    return $from > 0 ? 'From $' . number_format($from, 0) : 'Quote required';
}

function render_service_cards(array $services, array $options = []): void {
    $selectable = !empty($options['selectable']);
    $limit = (int)($options['limit'] ?? 0);
    if ($limit > 0) $services = array_slice($services, 0, $limit);
    foreach ($services as $service):
        $isMain = $service['slug'] === 'headlight-restoration';
        $from = service_from_price($service);
        $quoteOnly = $from <= 0;
?>
            <article class="service-card reveal<?= $isMain ? ' service-card-main' : '' ?><?= !empty($service['isFeatured']) ? ' is-featured' : '' ?>" data-service-card data-service-id="<?= service_h($service['id']) ?>" data-service-name="<?= service_h($service['name']) ?>" data-price-small="<?= service_h($service['priceSmall']) ?>" data-price-medium="<?= service_h($service['priceMedium']) ?>" data-price-large="<?= service_h($service['priceLarge']) ?>" data-price-single="<?= service_h($service['priceSingle']) ?>">
              <div class="service-card-top">
                <span class="service-icon" aria-hidden="true"><?= strtoupper(substr((string)$service['name'], 0, 1)) ?></span>
                <div>
                  <p class="service-category"><?= service_h($service['category']) ?></p>
                  <h3><?= service_h($service['name']) ?></h3>
                </div>
              </div>
<?php if ($isMain): ?>
              <p class="eofy-service-promo">EOFY Promo — $<?= number_format((float)$service['priceSmall'], 0) ?> for two front headlights on small cars</p>
<?php endif; ?>
              <p class="service-copy"><?= service_h($service['shortDescription'] ?: $service['longDescription']) ?></p>
              <div class="service-price-row">
                <span><?= service_h(service_price_text($service)) ?></span>
                <?php if (!empty($service['estimatedTime'])): ?><small><?= service_h($service['estimatedTime']) ?></small><?php endif; ?>
              </div>
              <details class="service-details">
                <summary>View pricing and inclusions</summary>
                <div class="service-pricing">
                  <span><b>Small</b>$<?= number_format((float)$service['priceSmall'], 0) ?></span>
                  <span><b>Medium</b>$<?= number_format((float)$service['priceMedium'], 0) ?></span>
                  <span><b>Large</b>$<?= number_format((float)$service['priceLarge'], 0) ?></span>
<?php if ($isMain): ?>
                  <span><b>Single headlight</b>$<?= number_format((float)$service['priceSingle'], 0) ?></span>
                  <span><b>Extra pair</b>+$<?= number_format((float)$service['priceExtraPair'], 0) ?></span>
                  <span><b>Commercial</b>Quote</span>
<?php endif; ?>
                </div>
<?php if (!empty($service['inclusions'])): ?>
                <h4>Included</h4>
                <ul>
<?php foreach ($service['inclusions'] as $item): ?>                  <li><?= service_h($item) ?></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
<?php if (!empty($service['exclusions'])): ?>
                <h4>Not included</h4>
                <ul class="service-exclusions">
<?php foreach ($service['exclusions'] as $item): ?>                  <li><?= service_h($item) ?></li>
<?php endforeach; ?>
                </ul>
<?php endif; ?>
<?php if (!empty($service['termsNote'])): ?>                <p class="service-terms"><?= service_h($service['termsNote']) ?></p>
<?php endif; ?>
              </details>
<?php if ($selectable): ?>
              <input type="checkbox" name="selected_services[]" value="<?= service_h($service['id']) ?>" data-service-select hidden<?= $isMain ? ' checked' : '' ?> />
              <button class="button <?= $quoteOnly ? 'button-secondary' : 'button-primary' ?> service-select-btn" type="button" data-service-button>
                <span><?= $isMain ? 'Main service selected' : ($quoteOnly ? 'Request quote' : 'Add to booking') ?></span>
              </button>
<?php else: ?>
              <a class="button <?= $quoteOnly ? 'button-secondary' : 'button-primary' ?>" href="/book?service=<?= rawurlencode($service['id']) ?>"><span><?= $quoteOnly ? 'Request quote' : 'Add to booking' ?></span></a>
<?php endif; ?>
            </article>
<?php
    endforeach;
}

