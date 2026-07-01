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

function service_icon_svg(array $service): string {
    $slug = (string)($service['slug'] ?? '');
    $path = match ($slug) {
        'headlight-restoration' => '<path d="M4 13c0-4 3.2-7 8-7 3.9 0 6.9 2 8 5.2L4 17v-4Z"/><path d="M12 6v9M16 7.6v5.9M8 7.6v8.8"/>',
        'quick-exterior-wash' => '<path d="M5 14c3-4 5.5-7.5 7-11 1.5 3.5 4 7 7 11a7 7 0 0 1-14 0Z"/><path d="M8 18c2 1.6 5.8 1.8 8 0"/>',
        'interior-refresh-detail' => '<path d="M6 17V9a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v8"/><path d="M4 17h16M7 17v3M17 17v3M8 11h8"/>',
        'interior-deep-clean' => '<path d="M5 20h14"/><path d="M8 20l1-9 6-6 2 2-6 6-1 7"/><path d="M14 8l2 2M6 15h5"/>',
        'full-shine-detail' => '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/><path d="M5 20h14"/>',
        'pre-sale-detail' => '<path d="M5 8h14v10H5z"/><path d="M9 8l1.5-2h3L15 8"/><path d="M9 14h6M12 11v6"/>',
        'paint-gloss-enhancement' => '<path d="M6 19h12"/><path d="M8 15c3-5 5.5-8.5 8-10l3 3c-1.5 2.5-5 5-10 8l-3-1Z"/><path d="M15 5l4 4"/>',
        'engine-bay-light-clean' => '<path d="M7 9h10v9H7z"/><path d="M9 9V6h6v3M4 12h3M17 12h3M10 18v3M14 18v3"/><path d="M10 13h4"/>',
        'plastic-trim-restoration' => '<path d="M5 17l9-9 3 3-9 9H5v-3Z"/><path d="M14 4l6 6M4 8h5M4 12h3"/>',
        'glass-water-spot-treatment' => '<path d="M7 4h10l2 16H5L7 4Z"/><path d="M9 8h6M10 12h4M8 16h8"/>',
        'ceramic-spray-protection' => '<path d="M12 3 20 7v5c0 4.8-3.2 7.8-8 9-4.8-1.2-8-4.2-8-9V7l8-4Z"/><path d="M9 12l2 2 4-5"/>',
        default => '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z"/>',
    };
    return '<svg aria-hidden="true" viewBox="0 0 24 24">' . $path . '</svg>';
}

function render_service_cards(array $services, array $options = []): void {
    $selectable = !empty($options['selectable']);
    $compact = !empty($options['compact']);
    $limit = (int)($options['limit'] ?? 0);
    if ($limit > 0) $services = array_slice($services, 0, $limit);
    foreach ($services as $service):
        $isMain = $service['slug'] === 'headlight-restoration';
        $from = service_from_price($service);
        $quoteOnly = $from <= 0;
?>
            <article class="service-card reveal<?= $isMain ? ' service-card-main' : '' ?><?= !empty($service['isFeatured']) ? ' is-featured' : '' ?><?= $compact ? ' service-card-compact' : '' ?>" data-service-card data-service-id="<?= service_h($service['id']) ?>" data-service-name="<?= service_h($service['name']) ?>" data-price-small="<?= service_h($service['priceSmall']) ?>" data-price-medium="<?= service_h($service['priceMedium']) ?>" data-price-large="<?= service_h($service['priceLarge']) ?>" data-price-single="<?= service_h($service['priceSingle']) ?>">
              <div class="service-card-top">
                <span class="service-icon" aria-hidden="true"><?= service_icon_svg($service) ?></span>
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
              <input type="hidden" name="service_quantities[<?= service_h($service['id']) ?>]" value="<?= $isMain ? '1' : '0' ?>" min="0" max="<?= MAX_SERVICE_QUANTITY ?>" data-service-quantity />
              <div class="service-quantity-control" data-service-quantity-control aria-label="Service quantity"<?= $isMain ? '' : ' hidden' ?>>
                <button type="button" data-quantity-action="decrement" aria-label="Decrease <?= service_h($service['name']) ?>">-</button>
                <span data-quantity-label><?= $isMain ? '1' : '0' ?></span>
                <button type="button" data-quantity-action="increment" aria-label="Increase <?= service_h($service['name']) ?>">+</button>
              </div>
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
