<?php
/**
 * iCareDonate — Deterministic Resource Matching Engine
 * includes/matching_engine.php
 *
 * Evaluates eligible approved donations against open assistance requests
 * using mandatory compatibility gates and weighted scoring (0-100).
 * Never fabricates records; operates strictly on existing MySQL rows.
 */

declare(strict_types=1);

/**
 * Normalizes text into meaningful tokens for item compatibility comparison.
 *
 * @return array<string>
 */
function icd_tokenize_item(string $text): array {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text) ?? '';
    $parts = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

    $stopWords = [
        'the', 'and', 'for', 'with', 'from', 'that', 'this', 'have', 'need',
        'needed', 'item', 'items', 'pcs', 'pack', 'packs', 'set', 'sets',
        'box', 'boxes', 'community', 'assistance', 'resource', 'request',
        'donation', 'good', 'clean', 'used', 'new', 'please', 'help', 'any',
    ];

    $synonyms = [
        'notebooks' => 'notebook',
        'backpacks' => 'backpack',
        'bags'      => 'backpack',
        'bag'       => 'backpack',
        'books'     => 'book',
        'textbooks' => 'book',
        'pencils'   => 'pencil',
        'pens'      => 'pen',
        'canned'    => 'food',
        'groceries' => 'food',
        'meals'     => 'food',
        'rice'      => 'rice',
        'clothes'   => 'clothing',
        'shirts'    => 'clothing',
        'shirt'     => 'clothing',
        'jackets'   => 'clothing',
        'shoes'     => 'shoe',
        'sneakers'  => 'shoe',
        'medicines' => 'medicine',
        'vitamins'  => 'medicine',
        'kits'      => 'kit',
        'blankets'  => 'blanket',
        'towels'    => 'towel',
        'laptops'   => 'laptop',
        'tablets'   => 'tablet',
        'phones'    => 'phone',
    ];

    $tokens = [];
    foreach ($parts as $w) {
        if (strlen($w) < 2 || in_array($w, $stopWords, true)) {
            continue;
        }
        if (isset($synonyms[$w])) {
            $w = $synonyms[$w];
        } elseif (strlen($w) > 4 && str_ends_with($w, 's')) {
            $w = substr($w, 0, -1);
        }
        $tokens[$w] = true;
    }

    return array_keys($tokens);
}

/**
 * Normalizes category strings to a canonical slug.
 */
function icd_normalize_category(?string $cat): string {
    $c = strtolower(trim((string)$cat));
    $c = str_replace([' & ', ' ', '_'], ['-', '-', '-'], $c);
    $c = preg_replace('/-+/', '-', $c) ?? '';
    return $c;
}

/**
 * Evaluates a single (donation, request) pair.
 * Returns null if mandatory eligibility gates fail or score < 40.
 *
 * @param array<string, mixed> $donation
 * @param array<string, mixed> $request
 * @return array<string, mixed>|null
 */
function icd_evaluate_match_pair(array $donation, array $request): ?array {
    // Gate 1: Prevent self-matching
    if ((int)($donation['donor_id'] ?? 0) === (int)($request['requester_id'] ?? 0)) {
        return null;
    }

    // Gate 2: Availability & Remaining Quantities
    $donQtyTotal = (int)($donation['quantity'] ?? 0);
    $donAllocated = (int)($donation['allocated_quantity'] ?? 0);
    $donRemaining = max(0, $donQtyTotal - $donAllocated);

    $reqQtyTotal = (int)($request['quantity'] ?? 0);
    $reqFulfilled = (int)($request['fulfilled_quantity'] ?? 0);
    $reqRemaining = max(0, $reqQtyTotal - $reqFulfilled);

    if ($donRemaining <= 0 || $reqRemaining <= 0) {
        return null;
    }

    // Gate 3: Category Compatibility
    $donCat = icd_normalize_category((string)($donation['category'] ?? ''));
    $reqCat = icd_normalize_category((string)($request['category'] ?? ''));
    $reqLinkedDonId = (int)($request['donation_id'] ?? 0);

    $catScore = 0;
    $reasons = [];

    if ($reqLinkedDonId > 0 && $reqLinkedDonId === (int)$donation['id']) {
        $catScore = 25;
        $reasons[] = 'Direct request for this specific donation';
    } elseif ($reqCat !== '' && $donCat !== '') {
        if ($reqCat === $donCat) {
            $catScore = 25;
            $reasons[] = 'Same resource category (' . ($donation['category'] ?? $donCat) . ')';
        } else {
            // Incompatible categories are strictly excluded
            return null;
        }
    } else {
        // Request did not specify a category (general aid request)
        $catScore = 15;
        $reasons[] = 'Open category compatibility';
    }

    // Gate 4: Condition Compatibility
    $cond = strtolower((string)($donation['condition'] ?? 'good'));
    $reqTextLower = strtolower((string)($request['item_title'] ?? '') . ' ' . (string)($request['message'] ?? ''));
    if ($cond === 'poor' && (str_contains($reqTextLower, 'new') || str_contains($reqTextLower, 'sealed') || str_contains($reqTextLower, 'medical'))) {
        return null;
    }

    $condScore = match ($cond) {
        'new', 'like_new' => 15,
        'good'            => 12,
        'fair'            => 8,
        default           => 2,
    };
    if ($condScore >= 12) {
        $reasons[] = 'Compatible item condition (' . ucfirst(str_replace('_', ' ', $cond)) . ')';
    }

    // Factor: Item Compatibility (30 pts)
    $donTokens = icd_tokenize_item((string)($donation['title'] ?? '') . ' ' . (string)($donation['description'] ?? ''));
    $reqTokens = icd_tokenize_item((string)($request['item_title'] ?? '') . ' ' . (string)($request['message'] ?? ''));

    $itemScore = 0;
    if ($reqLinkedDonId === (int)$donation['id']) {
        $itemScore = 30;
        $reasons[] = 'Exact item match';
    } elseif (!empty($reqTokens) && !empty($donTokens)) {
        $overlap = array_values(array_intersect($donTokens, $reqTokens));
        if (!empty($overlap)) {
            $ratio = count($overlap) / max(1, min(count($reqTokens), 4));
            $itemScore = (int)min(30, round(15 + (15 * min(1.0, $ratio))));
            $reasons[] = 'Matching item keywords (' . implode(', ', array_slice($overlap, 0, 3)) . ')';
        } elseif ($reqCat !== '' && $reqCat === $donCat) {
            // Same category, no direct keyword overlap
            $itemScore = 10;
        } else {
            // Unrelated item with no category match
            return null;
        }
    } else {
        $itemScore = ($reqCat === $donCat && $reqCat !== '') ? 15 : 8;
    }

    // Factor: Location Compatibility (15 pts) — uses only barangay & city (privacy-safe)
    $donCity = strtolower(trim((string)($donation['city'] ?? '')));
    $reqCity = strtolower(trim((string)($request['city'] ?? '')));
    $donBrgy = strtolower(trim((string)($donation['barangay'] ?? '')));
    $reqBrgy = strtolower(trim((string)($request['barangay'] ?? '')));

    $locScore = 2;
    $locLabel = 'Different city';
    if ($donCity !== '' && $reqCity !== '' && $donCity === $reqCity) {
        if ($donBrgy !== '' && $reqBrgy !== '' && $donBrgy === $reqBrgy) {
            $locScore = 15;
            $locLabel = 'Same barangay & city';
            $reasons[] = 'Same barangay & city (' . ($donation['city'] ?? '') . ')';
        } else {
            $locScore = 11;
            $locLabel = 'Same city';
            $reasons[] = 'Same city (' . ($donation['city'] ?? '') . ')';
        }
    } elseif ($donCity !== '' && $reqCity !== '') {
        $locScore = 5;
        $locLabel = 'Regional match';
    }

    // Factor: Urgency Priority (15 pts)
    $urgency = ucfirst(strtolower((string)($request['urgency'] ?? 'Medium')));
    $urgScore = match ($urgency) {
        'Critical' => 15,
        'High'     => 11,
        'Medium'   => 7,
        default    => 3,
    };
    if ($urgency === 'Critical' || $urgency === 'High') {
        $reasons[] = $urgency . ' urgency assistance request';
    }

    // Quantity sufficiency explanation
    $isPartial = $donRemaining < $reqRemaining;
    if ($isPartial) {
        $reasons[] = "Partial fulfillment ({$donRemaining} available of {$reqRemaining} needed)";
    } else {
        $reasons[] = "Sufficient quantity ({$donRemaining} available for {$reqRemaining} needed)";
    }

    $totalScore = min(100, $catScore + $itemScore + $condScore + $locScore + $urgScore);

    if ($totalScore < 40) {
        return null;
    }

    $classification = match (true) {
        $totalScore >= 85 => 'Strong Match',
        $totalScore >= 65 => 'Potential Match',
        default           => 'Weak Match',
    };

    return [
        'donation_id'           => (int)$donation['id'],
        'donation_title'        => (string)$donation['title'],
        'donation_category'     => (string)$donation['category'],
        'donation_condition'    => (string)$donation['condition'],
        'donation_available_qty'=> $donRemaining,
        'donation_unit'         => (string)($donation['unit'] ?? 'items'),
        'donation_city'         => (string)($donation['city'] ?? ''),
        'donation_barangay'     => (string)($donation['barangay'] ?? ''),
        'request_id'            => (int)$request['id'],
        'request_title'         => (string)($request['item_title'] ?? 'Assistance Request'),
        'request_category'      => (string)($request['category'] ?? ''),
        'request_needed_qty'    => $reqRemaining,
        'request_urgency'       => $urgency,
        'request_status'        => (string)$request['status'],
        'request_city'          => (string)($request['city'] ?? ''),
        'request_barangay'      => (string)($request['barangay'] ?? ''),
        'suggested_qty'         => min($donRemaining, $reqRemaining),
        'is_partial'            => $isPartial,
        'location_compatibility'=> $locLabel,
        'match_score'           => $totalScore,
        'classification'        => $classification,
        'reasons'               => $reasons,
        'explanation'           => implode(' • ', $reasons),
    ];
}

/**
 * Computes all ranked match recommendations from live database records.
 * Excludes pairs that already have an active or rejected allocation.
 *
 * @return array<string, mixed>
 */
function icd_compute_live_matches(PDO $pdo, array $filters = []): array {
    $donations = $pdo->query("
        SELECT id, donor_id, title, description, category, quantity, allocated_quantity,
               unit, `condition`, barangay, city, handover_method, status, created_at
        FROM donations
        WHERE status = 'approved' AND (quantity - allocated_quantity) > 0
        ORDER BY created_at DESC
    ")->fetchAll();

    $requests = $pdo->query("
        SELECT r.id, r.requester_id, r.donation_id,
               COALESCE(NULLIF(r.item_title, ''), d.title, 'Assistance Request') AS item_title,
               COALESCE(NULLIF(r.category, ''), d.category, '') AS category,
               r.message, r.quantity, r.fulfilled_quantity, r.unit,
               r.urgency, r.barangay, r.city, r.status, r.created_at
        FROM requests r
        LEFT JOIN donations d ON r.donation_id = d.id
        WHERE r.status IN ('pending', 'under_review', 'approved')
          AND (r.quantity - r.fulfilled_quantity) > 0
        ORDER BY FIELD(r.urgency, 'Critical', 'High', 'Medium', 'Low'), r.created_at ASC
    ")->fetchAll();

    // Load existing non-cancelled allocations so rejected or active pairs aren't re-suggested
    $existingPairs = [];
    try {
        $allocRows = $pdo->query("
            SELECT donation_id, request_id, status
            FROM donation_allocations
            WHERE status IN ('pending_review', 'approved', 'preparing', 'ready_for_handover', 'completed', 'rejected')
        ")->fetchAll();
        foreach ($allocRows as $ar) {
            $existingPairs[$ar['donation_id'] . ':' . $ar['request_id']] = $ar['status'];
        }
    } catch (PDOException $e) {}

    $matches = [];
    $matchedReqIds = [];
    $matchedDonIds = [];

    foreach ($requests as $req) {
        foreach ($donations as $don) {
            $pairKey = $don['id'] . ':' . $req['id'];
            if (isset($existingPairs[$pairKey])) {
                continue;
            }
            $eval = icd_evaluate_match_pair($don, $req);
            if ($eval === null) {
                continue;
            }

            // Apply optional filters
            if (!empty($filters['category']) && icd_normalize_category((string)$filters['category']) !== icd_normalize_category($eval['donation_category'])) {
                continue;
            }
            if (!empty($filters['urgency']) && strtolower((string)$filters['urgency']) !== strtolower($eval['request_urgency'])) {
                continue;
            }
            if (!empty($filters['classification']) && strtolower((string)$filters['classification']) !== strtolower($eval['classification'])) {
                continue;
            }
            if (!empty($filters['location'])) {
                $locNeedle = strtolower(trim((string)$filters['location']));
                $haystack = strtolower($eval['donation_city'] . ' ' . $eval['donation_barangay'] . ' ' . $eval['request_city'] . ' ' . $eval['request_barangay']);
                if (!str_contains($haystack, $locNeedle)) {
                    continue;
                }
            }
            if (!empty($filters['search'])) {
                $s = strtolower(trim((string)$filters['search']));
                $hay = strtolower($eval['donation_title'] . ' ' . $eval['request_title'] . ' ' . $eval['explanation']);
                if (!str_contains($hay, $s)) {
                    continue;
                }
            }

            $matches[] = $eval;
            $matchedReqIds[$req['id']] = true;
            $matchedDonIds[$don['id']] = true;
        }
    }

    usort($matches, static fn(array $a, array $b): int => $b['match_score'] <=> $a['match_score']);

    $strongCount = count(array_filter($matches, static fn(array $m): bool => $m['classification'] === 'Strong Match'));
    $potentialCount = count(array_filter($matches, static fn(array $m): bool => $m['classification'] === 'Potential Match'));
    $partialCount = count(array_filter($matches, static fn(array $m): bool => $m['is_partial']));

    return [
        'matches' => $matches,
        'summary' => [
            'available_donations'    => count($donations),
            'open_requests'          => count($requests),
            'strong_matches'         => $strongCount,
            'potential_matches'      => $potentialCount,
            'partial_matches'        => $partialCount,
            'unmatched_requests'     => max(0, count($requests) - count($matchedReqIds)),
            'unallocated_donations'  => max(0, count($donations) - count($matchedDonIds)),
        ],
    ];
}
