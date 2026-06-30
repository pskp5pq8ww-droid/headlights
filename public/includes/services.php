<?php
declare(strict_types=1);

require_once __DIR__ . '/bookings.php';

const VEHICLE_SIZE_LABELS = [
    'small' => 'Small car',
    'medium' => 'Medium SUV / wagon / ute',
    'large' => 'Large 4WD / van / truck',
    'commercial' => 'Commercial / oversized vehicle',
];

function services_file_path(): string {
    return booking_storage_base() . '/services.json';
}

function default_services(): array {
    $now = booking_now();
    $base = [
        [
            'slug' => 'headlight-restoration',
            'name' => 'Headlight Restoration',
            'category' => 'Main Service',
            'shortDescription' => 'Restore cloudy headlights for a cleaner look and safer night driving.',
            'longDescription' => 'Restore your headlights. Improve the look, clarity and safety of your car. Our mobile headlight restoration service removes yellowing, oxidation and cloudy buildup from the outside of your headlights, helping your car look cleaner and brighter without replacing the full headlight unit.',
            'inclusions' => ['Headlight surface preparation', 'Oxidation and yellowing removal', 'Sanding and refinement process', 'Final clarity restoration', 'Protective finish / UV-style sealant', 'Mobile service in selected Brisbane areas'],
            'exclusions' => ['Internal moisture', 'Broken seals', 'Cracked lenses', 'Electrical faults', 'Internal headlight damage'],
            'priceSmall' => 99, 'priceMedium' => 129, 'priceLarge' => 159, 'priceSingle' => 90, 'priceExtraPair' => 99,
            'estimatedTime' => '45-90 minutes', 'isAddOn' => false, 'isFeatured' => true, 'isActive' => true, 'sortOrder' => 10,
            'imageKey' => 'headlights', 'icon' => 'sparkles',
            'termsNote' => 'EOFY Promo — $99 for two front headlights on small cars. Outside surface only. Does not repair internal condensation, cracked lenses, broken seals, electrical faults or internal headlight damage.',
        ],
        ['slug' => 'quick-exterior-wash', 'name' => 'Quick Exterior Wash', 'category' => 'Add-on', 'shortDescription' => 'A quick exterior refresh after the headlights are restored.', 'longDescription' => 'A quick exterior refresh to make the vehicle look cleaner after the headlights are restored.', 'inclusions' => ['Exterior hand wash', 'Wheel face clean', 'Tyre shine', 'Exterior glass clean', 'Door jamb wipe', 'Quick spray protection'], 'exclusions' => [], 'priceSmall' => 119, 'priceMedium' => 149, 'priceLarge' => 179, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '45-75 minutes', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 20, 'imageKey' => 'wash', 'icon' => 'droplets', 'termsNote' => 'Final price depends on vehicle condition and access.'],
        ['slug' => 'interior-refresh-detail', 'name' => 'Interior Refresh Detail', 'category' => 'Detailing', 'shortDescription' => 'Make the inside feel clean again without a full deep detail.', 'longDescription' => 'For customers who want the inside to feel clean again without doing a full deep detail.', 'inclusions' => ['Vacuum seats, carpets and boot', 'Dashboard and console wipe-down', 'Cup holders and door trims cleaned', 'Interior glass clean', 'Mats cleaned', 'Light deodorising'], 'exclusions' => [], 'priceSmall' => 169, 'priceMedium' => 199, 'priceLarge' => 249, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '1.5-2.5 hours', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 30, 'imageKey' => 'interior', 'icon' => 'seat', 'termsNote' => 'Heavy staining, pet hair, sand or odours may require extra work.'],
        ['slug' => 'interior-deep-clean', 'name' => 'Interior Deep Clean', 'category' => 'Detailing', 'shortDescription' => 'For stains, dirt buildup, smells or family-use interiors.', 'longDescription' => 'For cars with stains, dirt buildup, smells or family use.', 'inclusions' => ['Full interior vacuum', 'Seats shampoo or leather clean', 'Carpet and mat shampoo', 'Door trims, dashboard and centre console cleaned', 'Interior plastics refreshed', 'Interior windows cleaned', 'Light odour treatment'], 'exclusions' => [], 'priceSmall' => 279, 'priceMedium' => 329, 'priceLarge' => 399, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '3-5 hours', 'isAddOn' => false, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 40, 'imageKey' => 'deep-clean', 'icon' => 'brush', 'termsNote' => 'Extreme contamination, mould or biohazard cleaning is quote only.'],
        ['slug' => 'full-shine-detail', 'name' => 'Full Shine Detail', 'category' => 'Detailing', 'shortDescription' => 'Best value package for a whole-car refresh.', 'longDescription' => 'Best value package for customers who want the whole car refreshed.', 'inclusions' => ['Exterior hand wash', 'Wheels and tyres cleaned', 'Interior refresh detail', 'Vacuum seats, carpets and boot', 'Dashboard, trims and console cleaned', 'Interior and exterior glass', 'Spray protection', 'Tyre shine', 'Final presentation check'], 'exclusions' => [], 'priceSmall' => 399, 'priceMedium' => 459, 'priceLarge' => 549, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '4-6 hours', 'isAddOn' => false, 'isFeatured' => true, 'isActive' => true, 'sortOrder' => 50, 'imageKey' => 'full-detail', 'icon' => 'star', 'termsNote' => 'Final price depends on size, condition and access.'],
        ['slug' => 'pre-sale-detail', 'name' => 'Pre-Sale Detail', 'category' => 'Detailing', 'shortDescription' => 'Make your car cleaner, brighter and more attractive before listing.', 'longDescription' => 'Make your car look cleaner, brighter and more attractive before listing it for sale.', 'inclusions' => ['Full exterior wash', 'Interior deep clean', 'Engine bay light clean', 'Glass clean', 'Tyre shine', 'Light paint gloss enhancement', 'Photo-ready final presentation'], 'exclusions' => [], 'priceSmall' => 499, 'priceMedium' => 579, 'priceLarge' => 669, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '5-7 hours', 'isAddOn' => false, 'isFeatured' => true, 'isActive' => true, 'sortOrder' => 60, 'imageKey' => 'pre-sale', 'icon' => 'camera', 'termsNote' => 'Final presentation depends on vehicle condition and existing paint defects.'],
        ['slug' => 'paint-gloss-enhancement', 'name' => 'Paint Gloss Enhancement', 'category' => 'Paint', 'shortDescription' => 'More shine for dull paint without full correction.', 'longDescription' => 'For dull paint that needs more shine, not full paint correction.', 'inclusions' => ['Exterior wash', 'Paint decontamination', 'One-stage machine polish', 'Gloss enhancement', 'Spray sealant protection'], 'exclusions' => ['Deep scratches', 'Clear coat failure', 'Stone chips', 'Peeling paint', 'Full paint correction'], 'priceSmall' => 449, 'priceMedium' => 549, 'priceLarge' => 649, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '4-7 hours', 'isAddOn' => false, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 70, 'imageKey' => 'paint', 'icon' => 'sparkles', 'termsNote' => 'This is a gloss enhancement, not full paint correction.'],
        ['slug' => 'engine-bay-light-clean', 'name' => 'Engine Bay Light Clean', 'category' => 'Add-on', 'shortDescription' => 'A careful light clean of the engine bay area.', 'longDescription' => 'A careful light clean of the engine bay area. Avoid high-pressure cleaning on sensitive electrical areas.', 'inclusions' => ['Light degreasing', 'Careful wipe-down', 'Plastic trim refresh', 'Final inspection'], 'exclusions' => [], 'priceSmall' => 99, 'priceMedium' => 129, 'priceLarge' => 159, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '45-75 minutes', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 80, 'imageKey' => 'engine', 'icon' => 'gauge', 'termsNote' => 'Sensitive or heavily modified engine bays may be quote only.'],
        ['slug' => 'plastic-trim-restoration', 'name' => 'Plastic Trim Restoration', 'category' => 'Add-on', 'shortDescription' => 'Restore faded black exterior trims.', 'longDescription' => 'For faded black exterior trims.', 'inclusions' => ['Exterior trim clean', 'Surface preparation', 'Trim restoration dressing', 'Darker, cleaner finish'], 'exclusions' => [], 'priceSmall' => 79, 'priceMedium' => 129, 'priceLarge' => 179, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '45-90 minutes', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 90, 'imageKey' => 'trim', 'icon' => 'wand', 'termsNote' => 'Results vary on badly aged or damaged plastic.'],
        ['slug' => 'glass-water-spot-treatment', 'name' => 'Glass Water Spot Treatment', 'category' => 'Glass', 'shortDescription' => 'Reduce visible water marks on windscreens or windows.', 'longDescription' => 'For windscreens or windows with visible water marks.', 'inclusions' => ['Glass decontamination', 'Water spot reduction', 'Exterior glass clean', 'Optional rain repellent: +$49'], 'exclusions' => [], 'priceSmall' => 99, 'priceMedium' => 139, 'priceLarge' => 179, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '45-90 minutes', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 100, 'imageKey' => 'glass', 'icon' => 'glass', 'termsNote' => 'Rain repellent available as an optional $49 add-on.'],
        ['slug' => 'ceramic-spray-protection', 'name' => 'Ceramic Spray Protection', 'category' => 'Protection', 'shortDescription' => 'Affordable shine and short-term hydrophobic protection.', 'longDescription' => 'Affordable shine and short-term protection add-on.', 'inclusions' => ['Exterior wash preparation', 'Ceramic spray sealant', 'Gloss boost', 'Hydrophobic finish'], 'exclusions' => ['This is not a professional multi-year ceramic coating.'], 'priceSmall' => 149, 'priceMedium' => 179, 'priceLarge' => 229, 'priceSingle' => 0, 'priceExtraPair' => 0, 'estimatedTime' => '45-75 minutes', 'isAddOn' => true, 'isFeatured' => false, 'isActive' => true, 'sortOrder' => 110, 'imageKey' => 'protection', 'icon' => 'shield', 'termsNote' => 'Short-term ceramic spray protection only.'],
    ];

    foreach ($base as $i => &$service) {
        $service = normalize_service($service + [
            'id' => 'svc_' . str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT),
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }
    unset($service);
    return $base;
}

function ensure_services_store_exists(): void {
    ensureBookingStorageExists();
    $path = services_file_path();
    if (is_file($path)) return;
    write_services(default_services());
}

function normalize_slug(string $slug, string $name = ''): string {
    $source = $slug !== '' ? $slug : $name;
    $slug = strtolower(trim($source));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-') ?: 'service';
}

function normalize_service(array $service): array {
    $now = booking_now();
    $id = clean_text($service['id'] ?? '', 80);
    if ($id === '') $id = 'svc_' . bin2hex(random_bytes(5));
    $name = clean_text($service['name'] ?? '', 140);
    $list = fn($value): array => array_values(array_filter(array_map(
        fn($item) => clean_text($item, 220),
        is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string)$value)
    ), fn($item) => $item !== ''));
    return [
        'id' => $id,
        'slug' => normalize_slug(clean_text($service['slug'] ?? '', 120), $name),
        'name' => $name,
        'category' => clean_text($service['category'] ?? 'Service', 80),
        'shortDescription' => clean_text($service['shortDescription'] ?? '', 240),
        'longDescription' => clean_text($service['longDescription'] ?? '', 1400),
        'inclusions' => $list($service['inclusions'] ?? []),
        'exclusions' => $list($service['exclusions'] ?? []),
        'priceSmall' => max(0, (float)($service['priceSmall'] ?? 0)),
        'priceMedium' => max(0, (float)($service['priceMedium'] ?? 0)),
        'priceLarge' => max(0, (float)($service['priceLarge'] ?? 0)),
        'priceSingle' => max(0, (float)($service['priceSingle'] ?? 0)),
        'priceExtraPair' => max(0, (float)($service['priceExtraPair'] ?? 0)),
        'estimatedTime' => clean_text($service['estimatedTime'] ?? '', 80),
        'isAddOn' => (bool)($service['isAddOn'] ?? false),
        'isFeatured' => (bool)($service['isFeatured'] ?? false),
        'isActive' => (bool)($service['isActive'] ?? true),
        'sortOrder' => (int)($service['sortOrder'] ?? 100),
        'imageKey' => clean_text($service['imageKey'] ?? '', 80),
        'icon' => clean_text($service['icon'] ?? '', 80),
        'termsNote' => clean_text($service['termsNote'] ?? '', 800),
        'createdAt' => clean_text($service['createdAt'] ?? $now, 80),
        'updatedAt' => clean_text($service['updatedAt'] ?? $now, 80),
    ];
}

function read_services(bool $activeOnly = false): array {
    ensure_services_store_exists();
    $data = json_decode((string)@file_get_contents(services_file_path()), true);
    if (!is_array($data)) {
        $data = default_services();
        write_services($data);
    }
    $services = array_map('normalize_service', $data);
    if ($activeOnly) $services = array_values(array_filter($services, fn($s) => !empty($s['isActive'])));
    usort($services, fn($a, $b) => [$a['sortOrder'], $a['name']] <=> [$b['sortOrder'], $b['name']]);
    return $services;
}

function write_services(array $services): void {
    ensureBookingStorageExists();
    $services = array_values(array_map('normalize_service', $services));
    $path = services_file_path();
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) throw new RuntimeException('Unable to encode services JSON');
    if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Unable to write services temp file');
    @chmod($tmp, 0600);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Unable to publish services file');
    }
    @chmod($path, 0600);
}

function get_service_by_id(string $id): ?array {
    foreach (read_services(false) as $service) {
        if ($service['id'] === $id) return $service;
    }
    return null;
}

function service_price_for_size(array $service, string $size, string $headlights = '2'): float {
    if ($service['slug'] === 'headlight-restoration' && $headlights === '1' && (float)$service['priceSingle'] > 0) {
        return (float)$service['priceSingle'];
    }
    return match ($size) {
        'small' => (float)$service['priceSmall'],
        'medium' => (float)$service['priceMedium'],
        'large' => (float)$service['priceLarge'],
        default => 0,
    };
}

function service_from_price(array $service): float {
    $prices = array_filter([(float)$service['priceSmall'], (float)$service['priceMedium'], (float)$service['priceLarge']], fn($p) => $p > 0);
    return $prices ? min($prices) : 0;
}

function build_selected_services_snapshot(mixed $rawIds, string $vehicleSize, string $headlights = '2'): array {
    $ids = is_array($rawIds) ? $rawIds : preg_split('/,/', (string)$rawIds);
    $ids = array_values(array_unique(array_filter(array_map(fn($id) => clean_text($id, 80), $ids))));
    $services = read_services(true);
    $byId = [];
    foreach ($services as $service) $byId[$service['id']] = $service;
    $snapshot = [];
    foreach ($ids as $id) {
        if (!isset($byId[$id])) continue;
        $service = $byId[$id];
        $price = service_price_for_size($service, $vehicleSize, $headlights);
        $snapshot[] = [
            'serviceId' => $service['id'],
            'serviceName' => $service['name'],
            'selectedVehicleSize' => $vehicleSize,
            'priceAtBooking' => $price,
            'quantity' => 1,
        ];
    }
    return $snapshot;
}

function selected_services_total(array $selectedServices): float {
    $total = 0;
    foreach ($selectedServices as $item) {
        $total += (float)($item['priceAtBooking'] ?? 0) * max(1, (int)($item['quantity'] ?? 1));
    }
    return $total;
}

function validate_service_payload(array $payload, array $existing = null): array {
    $service = normalize_service(($existing ?? []) + $payload);
    if (clean_text($payload['name'] ?? $service['name'], 140) === '') throw new InvalidArgumentException('Service name is required.');
    foreach (['priceSmall', 'priceMedium', 'priceLarge', 'priceSingle', 'priceExtraPair'] as $field) {
        if (isset($payload[$field]) && !is_numeric($payload[$field])) throw new InvalidArgumentException('Prices must be numeric.');
    }
    $service['updatedAt'] = booking_now();
    if (!$existing) $service['createdAt'] = $service['updatedAt'];
    return $service;
}
