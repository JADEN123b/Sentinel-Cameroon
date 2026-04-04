<?php
require_once 'includes/auth.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }
require_once 'includes/header.php';

$db       = new Database();
$userId   = getCurrentUserId();
$currentUser = getCurrentUser();

$success = '';
$error   = '';

// ── Handle New Listing (with payment capture) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_listing') {
    $title    = trim($_POST['title']    ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $price    = (float)($_POST['price'] ?? 0);
    $category = $_POST['category']  ?? '';
    $cond     = $_POST['condition_type'] ?? 'new';
    $region   = trim($_POST['region'] ?? '');
    $contact  = trim($_POST['contact_info'] ?? $currentUser['phone'] ?? '');
    $payPhone = trim($_POST['payment_phone'] ?? '');
    $payRef   = trim($_POST['payment_reference'] ?? '');
    $listingFee = 500;

    $validCategories = ['safety_equipment','medical_supplies','communication','emergency_tools','fire_safety','power_lighting','other'];
    $validConditions = ['new','excellent','good','used'];

    if (!$title || !$desc || $price <= 0 || !$region || !in_array($category, $validCategories) || !in_array($cond, $validConditions)) {
        $error = 'Please fill in all required fields correctly.';
    } elseif (empty($payPhone)) {
        $error = 'Please provide your mobile money phone number to complete payment.';
    } else {
        // Insert listing as pending payment
        $db->query(
            "INSERT INTO marketplace_listings (user_id, title, description, price, category, condition_type, region, contact_info, is_paid, listing_fee, payment_phone, payment_reference, status)
             VALUES (?,?,?,?,?,?,?,?,0,?,?,?,'active')",
            [$userId, $title, $desc, $price, $category, $cond, $region, $contact, $listingFee, $payPhone, $payRef ?: null]
        );
        $listingId = $db->lastInsertId();

        // Create payment record
        $db->query(
            "INSERT INTO marketplace_payments (listing_id, user_id, amount_fcfa, payment_phone, payment_reference, status)
             VALUES (?,?,500,?,?,'pending')",
            [$listingId, $userId, $payPhone, $payRef ?: null]
        );

        $success = "Your listing is pending payment confirmation. Please send 500 FCFA via Mobile Money to <strong>+237 655 000 000</strong> referencing your listing ID: <strong>#$listingId</strong>. It will go live once confirmed by admin.";
    }
}

// Handle delete own listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_listing') {
    $lid = (int)($_POST['listing_id'] ?? 0);
    $db->query("UPDATE marketplace_listings SET status='removed' WHERE id=? AND user_id=?", [$lid, $userId]);
    $success = 'Listing removed.';
}

// ── Filters ────────────────────────────────────────────────────────────────
$filterCategory = $_GET['category'] ?? 'all';
$filterRegion   = $_GET['region']   ?? 'all';
$filterCond     = $_GET['condition'] ?? 'all';
$search         = trim($_GET['q'] ?? '');

$where  = ["m.status = 'active'"];
$params = [];

if ($filterCategory !== 'all') { $where[] = "m.category = ?";            $params[] = $filterCategory; }
if ($filterRegion   !== 'all') { $where[] = "m.region = ?";              $params[] = $filterRegion; }
if ($filterCond     !== 'all') { $where[] = "m.condition_type = ?";      $params[] = $filterCond; }
if ($search)                   { $where[] = "(m.title LIKE ? OR m.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereClause = implode(' AND ', $where);

$listings = $db->fetchAll("
    SELECT m.*, u.full_name as seller_name, u.is_verified as seller_verified
    FROM marketplace_listings m
    JOIN users u ON m.user_id = u.id
    WHERE $whereClause
    ORDER BY m.is_paid DESC, m.created_at DESC
", $params);

// Pending listings for current user
$myPending = $db->fetchAll(
    "SELECT m.*, mp.status as pay_status FROM marketplace_listings m
     LEFT JOIN marketplace_payments mp ON mp.listing_id = m.id
     WHERE m.user_id = ? AND m.is_paid = 0 AND m.status = 'active'
     ORDER BY m.created_at DESC",
    [$userId]
);

$categoryMeta = [
    'safety_equipment'  => ['icon'=>'security',              'color'=>'#ef4444', 'label'=>'Safety Equipment'],
    'medical_supplies'  => ['icon'=>'medical_services',      'color'=>'#3b82f6', 'label'=>'Medical Supplies'],
    'communication'     => ['icon'=>'radio',                 'color'=>'#8b5cf6', 'label'=>'Communication'],
    'emergency_tools'   => ['icon'=>'construction',          'color'=>'#f97316', 'label'=>'Emergency Tools'],
    'fire_safety'       => ['icon'=>'local_fire_department', 'color'=>'#ef4444', 'label'=>'Fire Safety'],
    'power_lighting'    => ['icon'=>'lightbulb',             'color'=>'#eab308', 'label'=>'Power & Lighting'],
    'other'             => ['icon'=>'category',              'color'=>'#64748b', 'label'=>'Other'],
];

$regions        = $db->fetchAll("SELECT DISTINCT region FROM marketplace_listings WHERE status='active' ORDER BY region");
$categoryCounts = $db->fetchAll("SELECT category, COUNT(*) as cnt FROM marketplace_listings WHERE status='active' GROUP BY category");
$countMap = [];
foreach ($categoryCounts as $row) $countMap[$row['category']] = $row['cnt'];
?>

<style>
.market-listing { background:white; border-radius:16px; border:1.5px solid var(--rs-border); transition:all 0.25s; overflow:hidden; display:flex; flex-direction:column; }
.market-listing:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,0.1); border-color:var(--rs-primary); }
.market-listing.pending-pay { border:2px dashed #f97316; opacity:0.85; }
.cat-pill { display:flex; align-items:center; gap:8px; padding:0.55rem 1rem; border-radius:10px; border:1.5px solid var(--rs-border); cursor:pointer; background:white; transition:all 0.2s; font-weight:700; font-size:0.82rem; text-decoration:none; color:inherit; }
.cat-pill:hover { border-color:var(--rs-primary); }
.cat-pill.active { border-color:var(--rs-primary); background:var(--rs-primary); color:white; }
.contact-btn { background:var(--rs-primary); color:white; border:none; border-radius:8px; padding:0.55rem 1rem; font-weight:700; font-size:0.8rem; cursor:pointer; width:100%; display:flex; align-items:center; justify-content:center; gap:6px; transition:opacity 0.2s; }
.contact-btn:hover { opacity:0.85; }
.modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:9000; display:flex; align-items:center; justify-content:center; }
.modal-box { background:white; border-radius:20px; padding:2rem; width:90%; max-width:540px; max-height:90vh; overflow-y:auto; box-shadow:0 25px 50px rgba(0,0,0,0.2); }
.alert-box { padding:0.85rem 1.25rem; border-radius:10px; font-weight:600; font-size:0.88rem; margin-bottom:1.25rem; display:flex; align-items:flex-start; gap:10px; }
.alert-success { background:#dcfce7; color:#166534; border:1.5px solid #bbf7d0; }
.alert-error   { background:#fee2e2; color:#991b1b; border:1.5px solid #fecaca; }
.alert-warning { background:#fff7ed; color:#9a3412; border:1.5px solid #fed7aa; }
.form-field { width:100%; padding:0.75rem; border:1.5px solid var(--rs-border); border-radius:10px; font-size:0.9rem; font-family:inherit; box-sizing:border-box; outline:none; }
.form-field:focus { border-color:var(--rs-primary); }
.payment-box { background:#fff7ed; border:2px solid #fed7aa; border-radius:14px; padding:1.25rem; }
</style>

<?php if ($success): ?>
<div class="alert-box alert-success" style="margin-bottom:1.5rem;">
    <span class="material-symbols-outlined" style="flex-shrink:0;">check_circle</span>
    <div><?= $success ?></div>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-box alert-error" style="margin-bottom:1.5rem;">
    <span class="material-symbols-outlined" style="flex-shrink:0;">error</span>
    <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- Pending payment notices for current user -->
<?php if (!empty($myPending)): ?>
<div class="alert-box alert-warning" style="margin-bottom:1.5rem; flex-direction:column; align-items:stretch;">
    <div style="display:flex; align-items:center; gap:8px; font-weight:800; margin-bottom:0.75rem;">
        <span class="material-symbols-outlined">pending_actions</span>
        You have <?= count($myPending) ?> listing(s) awaiting payment confirmation
    </div>
    <?php foreach ($myPending as $pl): ?>
    <div style="background:white; border-radius:10px; padding:0.75rem 1rem; margin-bottom:6px; display:flex; justify-content:space-between; align-items:center; font-size:0.85rem;">
        <div>
            <strong>#<?= $pl['id'] ?></strong> — <?= htmlspecialchars($pl['title']) ?>
        </div>
        <span style="background:#fed7aa; color:#9a3412; font-size:0.65rem; font-weight:900; padding:3px 8px; border-radius:6px; text-transform:uppercase;">Pending</span>
    </div>
    <?php endforeach; ?>
    <p style="font-size:0.78rem; color:#9a3412; margin-top:6px;">Send <strong>500 FCFA</strong> via MTN Mobile Money or Orange Money to <strong>+237 655 000 000</strong> with your listing ID. Admin will activate it within 24hrs.</p>
</div>
<?php endif; ?>

<!-- Header -->
<div style="margin-bottom:2rem; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1rem;">
    <div>
        <h1 style="font-size:1.75rem; font-weight:900; margin-bottom:0.25rem; display:flex; align-items:center; gap:10px;">
            <span class="material-symbols-outlined" style="color:var(--rs-secondary);">shopping_bag</span>
            Safety Marketplace
        </h1>
        <p style="color:#64748b; font-size:0.9rem;">Buy & sell safety equipment within the Sentinel community</p>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:10px; padding:0.5rem 1rem; font-size:0.78rem; font-weight:700; color:#9a3412; display:flex; align-items:center; gap:6px;">
            <span class="material-symbols-outlined" style="font-size:1rem;">payments</span>
            500 FCFA listing fee
        </div>
        <button onclick="document.getElementById('createModal').style.display='flex'"
            style="background:var(--rs-secondary); color:white; border:none; border-radius:10px; padding:0.75rem 1.5rem; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined">add</span> Post a Listing
        </button>
    </div>
</div>

<!-- Search + Filters -->
<form method="GET" class="rs-card reveal" style="padding:1.25rem; margin-bottom:1.5rem;">
    <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
        <div style="position:relative; flex:1; min-width:200px;">
            <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1.1rem;">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search listings..." class="form-field" style="padding-left:2.5rem;">
        </div>
        <select name="region" class="form-field" style="width:auto;">
            <option value="all">All Regions</option>
            <?php foreach ($regions as $r): ?>
            <option value="<?= htmlspecialchars($r['region']) ?>" <?= $filterRegion === $r['region'] ? 'selected' : '' ?>><?= htmlspecialchars($r['region']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="condition" class="form-field" style="width:auto;">
            <option value="all">Any Condition</option>
            <option value="new"       <?= $filterCond==='new'       ? 'selected':'' ?>>New</option>
            <option value="excellent" <?= $filterCond==='excellent' ? 'selected':'' ?>>Excellent</option>
            <option value="good"      <?= $filterCond==='good'      ? 'selected':'' ?>>Good</option>
            <option value="used"      <?= $filterCond==='used'      ? 'selected':'' ?>>Used</option>
        </select>
        <button type="submit" class="contact-btn" style="width:auto; padding:0.7rem 1.5rem;">
            <span class="material-symbols-outlined" style="font-size:1rem;">filter_list</span> Filter
        </button>
        <?php if ($search || $filterCategory !== 'all' || $filterRegion !== 'all' || $filterCond !== 'all'): ?>
        <a href="market.php" style="color:#94a3b8; font-size:0.8rem; font-weight:600; text-decoration:none;">✕ Clear</a>
        <?php endif; ?>
    </div>
</form>

<!-- Category Pills -->
<div style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <a href="market.php?<?= http_build_query(['q'=>$search,'region'=>$filterRegion,'condition'=>$filterCond,'category'=>'all']) ?>"
        class="cat-pill <?= $filterCategory==='all' ? 'active' : '' ?>">
        <span class="material-symbols-outlined" style="font-size:1rem;">apps</span> All
        <span style="background:rgba(0,0,0,0.1); padding:1px 6px; border-radius:6px; font-size:0.7rem;"><?= array_sum($countMap) ?></span>
    </a>
    <?php foreach ($categoryMeta as $key => $meta): ?>
    <a href="market.php?<?= http_build_query(['q'=>$search,'region'=>$filterRegion,'condition'=>$filterCond,'category'=>$key]) ?>"
        class="cat-pill <?= $filterCategory===$key ? 'active' : '' ?>">
        <span class="material-symbols-outlined" style="font-size:1rem; <?= $filterCategory!==$key ? "color:{$meta['color']};" : '' ?>"><?= $meta['icon'] ?></span>
        <?= $meta['label'] ?>
        <span style="background:rgba(0,0,0,0.1); padding:1px 6px; border-radius:6px; font-size:0.7rem;"><?= $countMap[$key] ?? 0 ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Listings Grid -->
<?php if (empty($listings)): ?>
<div class="rs-card reveal" style="text-align:center; padding:4rem;">
    <span class="material-symbols-outlined" style="font-size:3rem; opacity:0.4;">shopping_cart_off</span>
    <p style="margin-top:1rem; color:#64748b; font-weight:600;">No listings found. Be the first to post one!</p>
    <button onclick="document.getElementById('createModal').style.display='flex'"
        style="margin-top:1rem; background:var(--rs-secondary); color:white; border:none; border-radius:10px; padding:0.75rem 1.5rem; font-weight:700; cursor:pointer;">
        Post First Listing
    </button>
</div>
<?php else: ?>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(270px, 1fr)); gap:1.25rem;">
    <?php foreach ($listings as $l):
        $meta   = $categoryMeta[$l['category']] ?? $categoryMeta['other'];
        $color  = $meta['color'];
        $isOwner = $l['user_id'] == $userId;
        $isPaid  = (bool)($l['is_paid'] ?? true);
        $condColors = ['new'=>['#dcfce7','#16a34a'],'excellent'=>['#dbeafe','#1d4ed8'],'good'=>['#fef9c3','#a16207'],'used'=>['#f1f5f9','#475569']];
        [$condBg, $condText] = $condColors[$l['condition_type']] ?? ['#f1f5f9','#475569'];
    ?>
    <div class="market-listing reveal <?= !$isPaid ? 'pending-pay' : '' ?>" style="border-top:4px solid <?= $color ?>;<?= !$isPaid ? 'background:#fffbf5;' : '' ?>">
        <!-- Banner -->
        <div style="background:<?= $color ?>12; padding:1.5rem; display:flex; align-items:center; justify-content:center; border-bottom:1.5px solid <?= $color ?>18; position:relative;">
            <div style="width:56px; height:56px; border-radius:16px; background:<?= $color ?>22; display:flex; align-items:center; justify-content:center;">
                <span class="material-symbols-outlined" style="color:<?= $color ?>; font-size:2rem;"><?= $meta['icon'] ?></span>
            </div>
            <?php if (!$isPaid): ?>
            <div style="position:absolute; top:10px; right:10px; background:#f97316; color:white; font-size:0.6rem; font-weight:900; padding:3px 8px; border-radius:6px; text-transform:uppercase;">
                ⏳ Pending Payment
            </div>
            <?php endif; ?>
        </div>

        <div style="padding:1.25rem; display:flex; flex-direction:column; gap:10px; flex:1;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px;">
                <h3 style="font-weight:800; font-size:0.95rem; margin:0; line-height:1.3;"><?= htmlspecialchars($l['title']) ?></h3>
                <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; padding:2px 8px; border-radius:6px; background:<?= $condBg ?>; color:<?= $condText ?>; flex-shrink:0;">
                    <?= ucfirst($l['condition_type']) ?>
                </span>
            </div>
            <p style="font-size:0.82rem; color:#64748b; line-height:1.5; margin:0;">
                <?= htmlspecialchars(substr($l['description'], 0, 100)) ?><?= strlen($l['description']) > 100 ? '...' : '' ?>
            </p>
            <div style="font-size:1.4rem; font-weight:900; color:var(--rs-primary);">
                <?= number_format($l['price']) ?> <span style="font-size:0.75rem; color:#94a3b8; font-weight:700;">FCFA</span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; font-size:0.78rem; color:#64748b; flex-wrap:wrap;">
                <span class="material-symbols-outlined" style="font-size:0.9rem;">person</span>
                <?= htmlspecialchars($l['seller_name']) ?>
                <?php if ($l['seller_verified']): ?>
                <span class="material-symbols-outlined" style="font-size:0.9rem; color:#3b82f6;" title="Verified User">verified</span>
                <?php endif; ?>
                <span style="margin-left:auto; display:flex; align-items:center; gap:4px;">
                    <span class="material-symbols-outlined" style="font-size:0.9rem;">location_on</span>
                    <?= htmlspecialchars($l['region']) ?>
                </span>
            </div>
            <div style="font-size:0.72rem; color:#94a3b8;">Posted: <?= date('M j, Y', strtotime($l['created_at'])) ?></div>

            <?php if ($isOwner): ?>
            <form method="POST" onsubmit="return confirm('Remove this listing?')">
                <input type="hidden" name="action" value="delete_listing">
                <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                <button type="submit" style="background:#fee2e2; color:#991b1b; border:none; border-radius:8px; padding:0.5rem 1rem; font-weight:700; font-size:0.8rem; cursor:pointer; width:100%;">
                    <span class="material-symbols-outlined" style="font-size:0.9rem; vertical-align:middle;">delete</span> Remove My Listing
                </button>
            </form>
            <?php elseif ($isPaid): ?>
            <button class="contact-btn" onclick="openContactModal('<?= htmlspecialchars(addslashes($l['seller_name'])) ?>','<?= htmlspecialchars(addslashes($l['contact_info'] ?? '')) ?>','<?= htmlspecialchars(addslashes($l['title'])) ?>')">
                <span class="material-symbols-outlined" style="font-size:1rem;">chat</span> Contact Seller
            </button>
            <?php else: ?>
            <div style="background:#f1f5f9; border-radius:8px; padding:0.6rem; text-align:center; font-size:0.78rem; color:#64748b; font-weight:600;">
                🔒 Available after payment confirmation
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Post Listing Modal ──────────────────────────────────────────────────── -->
<div id="createModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="font-size:1.2rem; font-weight:900; margin:0;">Post a Listing</h2>
            <button onclick="document.getElementById('createModal').style.display='none'" style="background:none; border:none; cursor:pointer;">
                <span class="material-symbols-outlined" style="color:#94a3b8;">close</span>
            </button>
        </div>

        <!-- Fee notice -->
        <div class="payment-box" style="margin-bottom:1.25rem;">
            <div style="font-weight:800; color:#9a3412; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:1.1rem;">payments</span>
                Listing Fee: 500 FCFA
            </div>
            <p style="font-size:0.8rem; color:#9a3412; margin:0; line-height:1.5;">
                Send <strong>500 FCFA</strong> to <strong>+237 655 000 000</strong> via MTN MoMo or Orange Money, then enter your phone & reference below. Your listing goes live within 24hrs after admin confirms payment.
            </p>
        </div>

        <form method="POST" style="display:flex; flex-direction:column; gap:1rem;">
            <input type="hidden" name="action" value="create_listing">
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Item Title *</label>
                <input type="text" name="title" required placeholder="e.g. Professional First Aid Kit" class="form-field">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Price (FCFA) *</label>
                    <input type="number" name="price" required min="1" placeholder="15000" class="form-field">
                </div>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Region *</label>
                    <select name="region" required class="form-field" style="background:white;">
                        <option value="">Select...</option>
                        <option>Douala</option><option>Yaoundé</option><option>Bafoussam</option>
                        <option>Bamenda</option><option>Garoua</option><option>Maroua</option>
                        <option>Ngaoundéré</option><option>Buea</option><option>Limbe</option><option>Other</option>
                    </select>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Category *</label>
                    <select name="category" required class="form-field" style="background:white;">
                        <option value="">Select...</option>
                        <?php foreach ($categoryMeta as $key => $meta): ?>
                        <option value="<?= $key ?>"><?= $meta['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Condition *</label>
                    <select name="condition_type" required class="form-field" style="background:white;">
                        <option value="new">New</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="used">Used</option>
                    </select>
                </div>
            </div>
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Description *</label>
                <textarea name="description" rows="3" required placeholder="Describe the item..." class="form-field" style="resize:vertical;"></textarea>
            </div>
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Your Contact (Phone / WhatsApp)</label>
                <input type="text" name="contact_info" value="<?= htmlspecialchars($currentUser['phone'] ?? '') ?>" placeholder="+237 6XX XXX XXX" class="form-field">
            </div>

            <!-- Payment fields -->
            <hr style="border:none; border-top:1.5px dashed #e2e8f0; margin:0.25rem 0;">
            <div style="font-weight:800; font-size:0.85rem; color:#9a3412; margin-bottom:-4px; display:flex; align-items:center; gap:6px;">
                <span class="material-symbols-outlined" style="font-size:1rem;">account_balance_wallet</span>
                Payment Details
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">MoMo Phone Number *</label>
                    <input type="tel" name="payment_phone" required placeholder="+237 6XX XXX XXX" class="form-field">
                </div>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; margin-bottom:6px;">Transaction Reference</label>
                    <input type="text" name="payment_reference" placeholder="e.g. TXN123456" class="form-field">
                </div>
            </div>

            <button type="submit" style="background:var(--rs-secondary); color:white; border:none; border-radius:10px; padding:0.9rem; font-weight:700; font-size:0.95rem; cursor:pointer;">
                <span class="material-symbols-outlined" style="vertical-align:middle;">upload</span> Submit Listing (500 FCFA)
            </button>
        </form>
    </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:400px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="font-size:1.2rem; font-weight:900; margin:0;">Seller Contact</h2>
            <button onclick="document.getElementById('contactModal').style.display='none'" style="background:none; border:none; cursor:pointer;">
                <span class="material-symbols-outlined" style="color:#94a3b8;">close</span>
            </button>
        </div>
        <div id="contactInfo" style="background:var(--rs-bg); border-radius:12px; padding:1.5rem; text-align:center;">
            <span class="material-symbols-outlined" style="font-size:2.5rem; color:var(--rs-primary);">person</span>
            <div id="contactSellerName" style="font-weight:800; font-size:1.1rem; margin:0.5rem 0;"></div>
            <div id="contactItemName" style="font-size:0.82rem; color:#64748b; margin-bottom:1rem;"></div>
            <div id="contactPhone" style="font-weight:700; font-size:1rem; color:var(--rs-primary);"></div>
        </div>
        <p style="font-size:0.82rem; color:#94a3b8; text-align:center; margin-top:1rem;">Sentinel is not responsible for transactions. Verify listings before paying.</p>
    </div>
</div>

<script>
function openContactModal(seller, contact, item) {
    document.getElementById('contactSellerName').textContent = seller;
    document.getElementById('contactItemName').textContent = 'Re: ' + item;
    document.getElementById('contactPhone').textContent = contact || 'No contact provided';
    document.getElementById('contactModal').style.display = 'flex';
}
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});
</script>

<?php require_once 'includes/footer.php'; ?>
