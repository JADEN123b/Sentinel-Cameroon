<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

// Separate partners by tier
$gold    = $db->fetchAll("SELECT * FROM partners WHERE sponsor_tier='gold' AND is_active=1 ORDER BY name ASC");
$silver  = $db->fetchAll("SELECT * FROM partners WHERE sponsor_tier='silver' AND is_active=1 ORDER BY name ASC");
$listed  = $db->fetchAll("SELECT * FROM partners WHERE (sponsor_tier='listed' OR sponsor_tier IS NULL) AND is_active=1 ORDER BY name ASC");

$totalPartners = count($gold) + count($silver) + count($listed);
$sponsoredCount = count($gold) + count($silver);

// Plans for the "Become a Partner" section
$plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_fcfa DESC");

function partnerIcon($type) {
    return match($type) {
        'police','security'   => 'shield',
        'medical'             => 'medical_services',
        'fire'                => 'local_fire_department',
        'government'          => 'account_balance',
        'community'           => 'groups',
        default               => 'handshake',
    };
}
?>

<style>
/* ── Tier Cards ── */
.partner-card {
    border-radius: 20px;
    padding: 2rem;
    border: 1.5px solid var(--rs-border);
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: white;
}
.partner-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,0.1); }

/* Gold */
.partner-card.gold {
    border: 2px solid #f59e0b;
    background: linear-gradient(145deg, #fffbeb 0%, #ffffff 60%);
    box-shadow: 0 4px 20px rgba(245,158,11,0.15);
}
.partner-card.gold:hover { box-shadow: 0 16px 40px rgba(245,158,11,0.25); }

/* Silver */
.partner-card.silver {
    border: 2px solid #94a3b8;
    background: linear-gradient(145deg, #f8fafc 0%, #ffffff 60%);
    box-shadow: 0 4px 16px rgba(148,163,184,0.12);
}

.tier-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    font-size: 0.6rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
}
.badge-gold   { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; }
.badge-silver { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

.plan-card {
    border: 2px solid var(--rs-border);
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.2s;
    background: white;
    text-align: center;
}
.plan-card.gold-plan  { border-color: #f59e0b; background: linear-gradient(145deg,#fffbeb,#fff); }
.plan-card.silver-plan{ border-color: #94a3b8; }
.plan-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); }

.cta-badge {
    display: inline-block;
    font-size: 0.6rem;
    font-weight: 900;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
    margin-bottom: 1rem;
}

.section-label {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #94a3b8;
    margin: 2.5rem 0 1.25rem;
}
.section-label::before, .section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}
</style>

<!-- Header -->
<div class="rs-card animate-rs" style="margin-bottom: 2rem; border-left: 6px solid var(--rs-secondary);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="font-size:2.25rem; margin-bottom:5px;">Emergency Partner Network</h1>
            <p style="color:#64748b; font-weight:600;">Verified agencies and community responders across Cameroon.</p>
        </div>
        <div style="display:flex; gap:2rem;">
            <div style="text-align:center;">
                <div style="font-size:1.75rem; font-weight:900; color:var(--rs-primary);"><?= $totalPartners ?></div>
                <div style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase;">Partners</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.75rem; font-weight:900; color:#f59e0b;">★ <?= $sponsoredCount ?></div>
                <div style="font-size:0.65rem; font-weight:800; color:#94a3b8; text-transform:uppercase;">Sponsored</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($gold)): ?>
<!-- ══ GOLD PARTNERS ══ -->
<div class="section-label">
    <span>★ Gold Partners</span>
</div>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
    <?php foreach ($gold as $p): ?>
    <div class="partner-card gold animate-rs">
        <span class="tier-badge badge-gold">★ Gold Sponsor</span>
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="background:#fef3c7; color:#b45309; padding:12px; border-radius:14px; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:1.75rem;"><?= partnerIcon($p['partner_type']) ?></span>
            </div>
            <div>
                <h4 style="font-size:1.1rem; margin:0 0 2px;"><?= htmlspecialchars($p['name']) ?></h4>
                <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; background:#fef9c3; color:#92400e; padding:2px 8px; border-radius:6px;"><?= htmlspecialchars($p['partner_type']) ?></span>
            </div>
        </div>
        <p style="font-size:0.9rem; color:#64748b; line-height:1.6; margin:0;"><?= htmlspecialchars($p['description'] ?? '') ?></p>
        <div style="display:flex; flex-direction:column; gap:8px; margin-top:auto;">
            <a href="tel:<?= htmlspecialchars($p['contact_phone']) ?>" class="btn-rs btn-rs-primary" style="justify-content:center; padding:0.9rem; background:#f59e0b; border:none;">
                <span class="material-symbols-outlined">call</span> Call Now — Priority Line
            </a>
            <?php if (!empty($p['website'])): ?>
            <a href="<?= htmlspecialchars($p['website']) ?>" target="_blank" class="btn-rs" style="justify-content:center; background:#fffbeb; color:#92400e; border:1px solid #fcd34d; padding:0.75rem;">
                <span class="material-symbols-outlined">public</span> Official Website
            </a>
            <?php endif; ?>
        </div>
        <?php if (!empty($p['address'])): ?>
        <div style="font-size:0.78rem; color:#94a3b8; display:flex; align-items:center; gap:6px; font-weight:600;">
            <span class="material-symbols-outlined" style="font-size:1rem;">location_on</span>
            <?= htmlspecialchars($p['address']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($silver)): ?>
<!-- ══ SILVER PARTNERS ══ -->
<div class="section-label">
    <span>◈ Silver Partners</span>
</div>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.25rem; margin-bottom:2rem;">
    <?php foreach ($silver as $p): ?>
    <div class="partner-card silver animate-rs">
        <span class="tier-badge badge-silver">◈ Silver</span>
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="background:#f1f5f9; color:#475569; padding:10px; border-radius:12px; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:1.5rem;"><?= partnerIcon($p['partner_type']) ?></span>
            </div>
            <div>
                <h4 style="font-size:1rem; margin:0 0 2px;"><?= htmlspecialchars($p['name']) ?></h4>
                <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:6px;"><?= htmlspecialchars($p['partner_type']) ?></span>
            </div>
        </div>
        <p style="font-size:0.875rem; color:#64748b; line-height:1.6; margin:0;"><?= htmlspecialchars($p['description'] ?? '') ?></p>
        <a href="tel:<?= htmlspecialchars($p['contact_phone']) ?>" class="btn-rs btn-rs-primary" style="justify-content:center; padding:0.85rem;">
            <span class="material-symbols-outlined">call</span> Call Unit
        </a>
        <?php if (!empty($p['website'])): ?>
        <a href="<?= htmlspecialchars($p['website']) ?>" target="_blank" class="btn-rs" style="justify-content:center; background:#f8fafc; color:#64748b; padding:0.7rem; font-size:0.82rem;">
            <span class="material-symbols-outlined">public</span> Website
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($listed)): ?>
<!-- ══ LISTED PARTNERS ══ -->
<div class="section-label">
    <span>Listed Partners</span>
</div>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1rem; margin-bottom:3rem;">
    <?php foreach ($listed as $p): ?>
    <div class="partner-card animate-rs" style="padding:1.5rem;">
        <div style="display:flex; align-items:center; gap:12px;">
            <div style="background:#f8fafc; color:var(--rs-primary); padding:10px; border-radius:12px; flex-shrink:0;">
                <span class="material-symbols-outlined" style="font-size:1.4rem;"><?= partnerIcon($p['partner_type']) ?></span>
            </div>
            <div>
                <h4 style="font-size:0.95rem; margin:0 0 2px; font-weight:800;"><?= htmlspecialchars($p['name']) ?></h4>
                <span style="font-size:0.62rem; font-weight:800; text-transform:uppercase; color:#94a3b8;"><?= htmlspecialchars($p['partner_type']) ?></span>
            </div>
        </div>
        <p style="font-size:0.82rem; color:#64748b; line-height:1.5; margin:0;"><?= htmlspecialchars(substr($p['description'] ?? '', 0, 120)) ?></p>
        <a href="tel:<?= htmlspecialchars($p['contact_phone']) ?>" class="btn-rs" style="justify-content:center; background:#f1f5f9; color:var(--rs-primary); padding:0.7rem; font-size:0.82rem;">
            <span class="material-symbols-outlined">call</span> <?= htmlspecialchars($p['contact_phone']) ?>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($gold) && empty($silver) && empty($listed)): ?>
<div class="rs-card" style="text-align:center; padding:4rem; border-style:dashed; background:transparent;">
    <span class="material-symbols-outlined" style="font-size:3rem; color:#cbd5e1;">handshake</span>
    <p style="margin-top:1rem; color:#64748b; font-weight:600;">No partners listed yet. Be the first to join the network!</p>
</div>
<?php endif; ?>

<!-- ══ BECOME A PARTNER ══ -->
<div class="rs-card animate-rs" style="background:var(--rs-primary); color:white; border:none; padding:3.5rem; text-align:center; margin-bottom:2rem;">
    <h2 style="font-size:2rem; color:white; margin-bottom:0.75rem;">Join the Sentinel Partner Network</h2>
    <p style="opacity:0.8; max-width:580px; margin:0 auto 3rem; line-height:1.7;">
        Boost your visibility to thousands of citizens and responders across Cameroon. Choose a plan that fits your organization.
    </p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.5rem; max-width:800px; margin:0 auto;">
        <?php foreach ($plans as $plan): ?>
        <div class="plan-card <?= $plan['tier'] === 'gold' ? 'gold-plan' : ($plan['tier'] === 'silver' ? 'silver-plan' : '') ?>">
            <?php if ($plan['tier'] === 'gold'): ?>
                <span class="cta-badge" style="background:#fef3c7; color:#b45309;">★ Most Visible</span>
            <?php elseif ($plan['tier'] === 'silver'): ?>
                <span class="cta-badge" style="background:#f1f5f9; color:#475569;">◈ Popular</span>
            <?php else: ?>
                <span class="cta-badge" style="background:#e0f2fe; color:#0369a1;">Free</span>
            <?php endif; ?>
            <div style="font-weight:900; font-size:1.1rem; color:var(--rs-primary); margin-bottom:4px;"><?= htmlspecialchars($plan['name']) ?></div>
            <div style="font-size:1.6rem; font-weight:900; color:<?= $plan['tier'] === 'gold' ? '#b45309' : ($plan['tier'] === 'silver' ? '#475569' : '#0f172a') ?>; margin-bottom:8px;">
                <?= $plan['price_fcfa'] > 0 ? number_format($plan['price_fcfa']) . ' <span style="font-size:0.75rem;font-weight:600;color:#94a3b8;">FCFA/mo</span>' : '<span style="color:#0369a1">Free</span>' ?>
            </div>
            <p style="font-size:0.78rem; color:#64748b; line-height:1.5; margin-bottom:1.25rem;"><?= htmlspecialchars($plan['features']) ?></p>
            <a href="mailto:partners@sentinel.cm?subject=<?= urlencode($plan['name'] . ' Application') ?>" 
               style="display:block; background:<?= $plan['tier'] === 'gold' ? '#f59e0b' : 'var(--rs-primary)' ?>; color:white; padding:0.75rem; border-radius:10px; font-weight:700; text-decoration:none; font-size:0.85rem;">
                Apply Now →
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
