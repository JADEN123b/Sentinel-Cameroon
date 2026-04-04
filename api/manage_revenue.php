<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn() || getUserRole() !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$db = new Database();
$adminId = getCurrentUserId();

if ($action === 'confirm_payment') {
    $paymentId = (int)($data['payment_id'] ?? 0);
    $listingId = (int)($data['listing_id'] ?? 0);
    if (!$paymentId || !$listingId) {
        echo json_encode(['success'=>false,'message'=>'Missing IDs']); exit;
    }
    $db->query("UPDATE marketplace_payments SET status='confirmed', confirmed_by=?, confirmed_at=NOW() WHERE id=?", [$adminId, $paymentId]);
    $db->query("UPDATE marketplace_listings SET is_paid=1, paid_at=NOW() WHERE id=?", [$listingId]);
    $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?,?,?)",
        [$adminId, "Confirmed marketplace payment for listing #$listingId", 'revenue']);
    echo json_encode(['success'=>true,'message'=>'Payment confirmed. Listing is now live.']);

} elseif ($action === 'reject_payment') {
    $paymentId = (int)($data['payment_id'] ?? 0);
    $listingId = (int)($data['listing_id'] ?? 0);
    $db->query("UPDATE marketplace_payments SET status='failed', confirmed_by=?, confirmed_at=NOW() WHERE id=?", [$adminId, $paymentId]);
    $db->query("UPDATE marketplace_listings SET status='removed' WHERE id=?", [$listingId]);
    echo json_encode(['success'=>true,'message'=>'Payment rejected. Listing removed.']);

} elseif ($action === 'set_partner_tier') {
    $partnerId = (int)($data['partner_id'] ?? 0);
    $tier      = $data['tier'] ?? 'listed';
    $amount    = (int)($data['amount'] ?? 0);
    $months    = (int)($data['months'] ?? 1);
    $phone     = trim($data['payment_phone'] ?? '');
    $ref       = trim($data['payment_reference'] ?? '');

    if (!in_array($tier, ['gold','silver','listed'])) {
        echo json_encode(['success'=>false,'message'=>'Invalid tier']); exit;
    }

    $isSponsored = ($tier !== 'listed') ? 1 : 0;
    $validUntil  = date('Y-m-d', strtotime("+{$months} months"));
    $validFrom   = date('Y-m-d');

    $db->query(
        "UPDATE partners SET sponsor_tier=?, is_sponsored=?, monthly_fee=?, sponsor_expires_at=? WHERE id=?",
        [$tier, $isSponsored, $amount, $isSponsored ? $validUntil : null, $partnerId]
    );

    if ($isSponsored && $amount > 0) {
        $db->query(
            "INSERT INTO partner_sponsorships (partner_id, tier, amount_fcfa, payment_phone, payment_reference, valid_from, valid_until, recorded_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [$partnerId, $tier, $amount, $phone ?: null, $ref ?: null, $validFrom, $validUntil, $adminId]
        );
    }

    $partner = $db->fetch("SELECT name FROM partners WHERE id=?", [$partnerId]);
    $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?,?,?)",
        [$adminId, "Set {$partner['name']} as " . strtoupper($tier) . " partner (valid until $validUntil)", 'revenue']);

    echo json_encode(['success'=>true,'message'=>"Partner tier updated to " . strtoupper($tier) . " until $validUntil"]);

} elseif ($action === 'set_authority_subscription') {
    $userId = (int)($data['user_id'] ?? 0);
    $tier   = $data['tier'] ?? 'basic';
    $expiry = $data['expiry'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success'=>false,'message'=>'Missing User ID']); exit;
    }
    
    $status = ($tier === 'premium') ? 'active' : 'none';
    
    $db->query(
        "UPDATE users SET subscription_tier=?, subscription_expires_at=?, subscription_status=? WHERE id=?",
        [$tier, $expiry, $status, $userId]
    );
    
    $user = $db->fetch("SELECT full_name FROM users WHERE id=?", [$userId]);
    $db->query("INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?,?,?)",
        [$adminId, "Set {$user['full_name']} as " . strtoupper($tier) . " authority subscriber (expires $expiry)", 'revenue']);
        
    echo json_encode(['success'=>true,'message'=>"Authority subscription updated to " . strtoupper($tier)]);

} else {
    echo json_encode(['success'=>false,'message'=>'Unknown action']);
}
