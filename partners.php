<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

// Get all partners
$partners = $db->query("
    SELECT * FROM partners 
    ORDER BY is_sponsored DESC, name ASC
")->fetchAll();

// Get simple statistics
$stats = [
    'total_partners' => count($partners),
    'emergency_units' => $db->query("SELECT COUNT(*) as count FROM partners WHERE partner_type IN ('police', 'medical', 'fire')")->fetch()['count'],
    'community_groups' => $db->query("SELECT COUNT(*) as count FROM partners WHERE partner_type = 'community'")->fetch()['count']
];
?>

<div class="animate-rs">
    
    <!-- Header -->
    <div class="rs-card" style="margin-bottom: 2rem; border-left: 6px solid var(--rs-secondary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.25rem; margin-bottom: 5px;">Our Emergency Network</h1>
                <p style="color: #64748b; font-weight: 600;">Connecting you with the right agencies and community responders.</p>
            </div>
            <div style="display: flex; gap: 15px;">
                <div style="text-align: right;">
                    <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 5px;">Verified Units</div>
                    <div style="font-size: 1.5rem; font-weight: 900; color: var(--rs-primary);"><?php echo $stats['total_partners']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="rs-grid rs-grid-stats" style="margin-bottom: 2.5rem;">
        <div class="rs-card" style="background: rgba(15, 23, 42, 0.02); border: 1px solid #f1f5f9;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--rs-secondary);"><?php echo $stats['emergency_units']; ?></div>
            <div style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Law Enforcement & Meds</div>
        </div>
        <div class="rs-card" style="background: rgba(15, 23, 42, 0.02); border: 1px solid #f1f5f9;">
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--rs-accent);"><?php echo $stats['community_groups']; ?></div>
            <div style="font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Community Watchers</div>
        </div>
    </div>

    <!-- Partners Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
        <?php foreach($partners as $partner): ?>
            <div class="rs-card hover-effect" style="position: relative; border: 1px solid #f1f5f9; padding: 2.5rem; border-top: 4px solid <?php echo $partner['is_sponsored'] ? 'var(--rs-secondary)' : '#e2e8f0'; ?>;">
                
                <?php if ($partner['is_sponsored']): ?>
                    <div style="position: absolute; top: 1.5rem; right: 1.5rem; background: #fff7ed; color: #ea580c; font-size: 0.6rem; font-weight: 950; padding: 4px 10px; border-radius: 6px; letter-spacing: 1px; border: 1px solid #ffedd5;">
                        ★ SPONSORED PARTNER
                    </div>
                <?php endif; ?>

                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 1.5rem;">
                    <div style="background: #f8fafc; color: var(--rs-primary); padding: 12px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 1.75rem;">
                            <?php 
                                echo $partner['partner_type'] === 'police' ? 'shield' : 
                                    ($partner['partner_type'] === 'medical' ? 'medical_services' : 
                                    ($partner['partner_type'] === 'fire' ? 'local_fire_department' : 'group')); 
                            ?>
                        </span>
                    </div>
                    <div>
                        <h4 style="font-size: 1.25rem; margin-bottom: 2px;"><?php echo htmlspecialchars($partner['name']); ?></h4>
                        <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($partner['partner_type']); ?> Unit</p>
                    </div>
                </div>

                <p style="font-size: 0.95rem; color: #64748b; line-height: 1.6; margin-bottom: 2.5rem;">
                    <?php echo htmlspecialchars($partner['description']); ?>
                </p>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="tel:<?php echo htmlspecialchars($partner['contact_phone']); ?>" class="btn-rs btn-rs-primary" style="justify-content: center; padding: 1rem;">
                        <span class="material-symbols-outlined">call</span>
                        Call Unit Now
                    </a>
                    <?php if($partner['website']): ?>
                        <a href="<?php echo htmlspecialchars($partner['website']); ?>" target="_blank" class="btn-rs" style="justify-content: center; background: #f8fafc; color: #64748b; padding: 1rem;">
                            <span class="material-symbols-outlined">public</span>
                            Official Website
                        </a>
                    <?php endif; ?>
                </div>

                <?php if($partner['address']): ?>
                    <div style="margin-top: 1.5rem; display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; color: #94a3b8; font-weight: 600;">
                        <span class="material-symbols-outlined" style="font-size: 1.1rem;">location_on</span>
                        <?php echo htmlspecialchars($partner['address']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 💡 Partner Invite -->
    <div class="rs-card" style="background: var(--rs-primary); color: white; border: none; text-align: center; padding: 4rem;">
        <h2 style="font-size: 2.25rem; margin-bottom: 1rem; color: white;">Become a Partner Unit</h2>
        <p style="font-size: 1.15rem; opacity: 0.8; max-width: 600px; margin: 0 auto 3rem;">Is your organization working for community safety? Join Cameroon's most active emergency response network.</p>
        <a href="mailto:partners@sentinel.cm" class="btn-rs btn-rs-primary" style="background: var(--rs-secondary); padding: 1.25rem 2.5rem; font-size: 1rem;">
            Contact Support for Integration
        </a>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
