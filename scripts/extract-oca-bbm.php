<?php

declare(strict_types=1);

// Extracts only the actual barangay boundary polygons from the mixed OCA BBM
// KML folder. Direct polygons with ExtendedData are the primary barangays; the
// two Post Proper polygons live in the folder nested immediately below it.

$source = $argv[1] ?? null;
$target = $argv[2] ?? null;

if (!$source || !$target || !is_file($source)) {
    fwrite(STDERR, "Usage: php scripts/extract-oca-bbm.php <source.kml> <target.geojson>\n");
    exit(1);
}

$document = new DOMDocument();
$document->preserveWhiteSpace = false;
if (!$document->load($source, LIBXML_NONET | LIBXML_COMPACT)) {
    fwrite(STDERR, "Unable to parse KML: {$source}\n");
    exit(1);
}

$xpath = new DOMXPath($document);
$xpath->registerNamespace('k', 'http://www.opengis.net/kml/2.2');
$oca = $xpath->query('//k:Folder[k:name="OCA BBM"]')->item(0);
if (!$oca) {
    fwrite(STDERR, "OCA BBM folder was not found.\n");
    exit(1);
}

$placemarks = [];
foreach ($xpath->query('./k:Placemark[k:ExtendedData and .//k:Polygon]', $oca) as $placemark) {
    $placemarks[] = $placemark;
}
foreach ($xpath->query('./k:Folder[k:name="POST_PROPER_NORTH_SOUTH_SIDE.shp"]//k:Placemark[.//k:Polygon]', $oca) as $placemark) {
    $placemarks[] = $placemark;
}

$parseRing = static function (string $text): array {
    $coordinates = [];
    foreach (preg_split('/\s+/', trim($text)) ?: [] as $tuple) {
        $parts = explode(',', $tuple);
        if (count($parts) >= 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            // GeoJSON requires [longitude, latitude]. KML uses the same order.
            $coordinates[] = [(float) $parts[0], (float) $parts[1]];
        }
    }
    return $coordinates;
};

$features = [];
foreach ($placemarks as $placemark) {
    $name = trim((string) $xpath->evaluate('string(./k:name)', $placemark));
    if ($name === '') {
        continue;
    }
    $polygons = [];
    foreach ($xpath->query('.//k:Polygon', $placemark) as $polygon) {
        $rings = [];
        $outer = $xpath->evaluate('string(./k:outerBoundaryIs/k:LinearRing/k:coordinates)', $polygon);
        if ($outer !== '') {
            $rings[] = $parseRing($outer);
        }
        foreach ($xpath->query('./k:innerBoundaryIs/k:LinearRing/k:coordinates', $polygon) as $inner) {
            $rings[] = $parseRing($inner->textContent);
        }
        if ($rings !== [] && count($rings[0]) >= 4) {
            $polygons[] = $rings;
        }
    }
    if ($polygons === []) {
        continue;
    }
    $features[] = [
        'type' => 'Feature',
        'properties' => ['name' => $name],
        'geometry' => count($polygons) === 1
            ? ['type' => 'Polygon', 'coordinates' => $polygons[0]]
            : ['type' => 'MultiPolygon', 'coordinates' => $polygons],
    ];
}

$geojson = ['type' => 'FeatureCollection', 'name' => 'OCA BBM Barangay Boundaries', 'features' => $features];
// Keep the public payload compact; pretty-printing multiplied this static
// geometry file's transfer size without adding value to the browser.
file_put_contents($target, json_encode($geojson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
fwrite(STDOUT, sprintf("Extracted %d barangay boundaries to %s\n", count($features), $target));
