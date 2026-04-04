<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$categories = [
    ['icon' => 'security', 'name' => 'Safety Equipment', 'count' => 24, 'color' => '#ef4444'],
    ['icon' => 'medical_services', 'name' => 'Medical Supplies', 'count' => 18, 'color' => '#3b82f6'],
    ['icon' => 'radio', 'name' => 'Communication', 'count' => 11, 'color' => '#8b5cf6'],
    ['icon' => 'construction', 'name' => 'Emergency Tools', 'count' => 9, 'color' => '#f97316'],
    ['icon' => 'local_fire_department', 'name' => 'Fire Safety', 'count' => 7, 'color' => '#ef4444'],
    ['icon' => 'lightbulb', 'name' => 'Power & Lighting', 'count' => 15, 'color' => '#eab308'],
];

$listings = [
    ['title' => 'Professional First Aid Kit', 'price' => 15000, 'condition' => 'New', 'seller' => 'MedShop Douala', 'region' => 'Douala', 'icon' => 'medical_services', 'color' => '#3b82f6', 'verified' => true, 'category' => 'Medical Supplies', 'desc' => 'Complete 50-piece professional first aid kit. Suitable for home, vehicle, and workplace.'],
    ['title' => 'Safety Helmet & Vest Combo', 'price' => 8500, 'condition' => 'New', 'seller' => 'Secure Pro', 'region' => 'Yaoundé', 'icon' => 'construction', 'color' => '#f97316', 'verified' => true, 'category' => 'Safety Equipment', 'desc' => 'High-visibility vest with hard hat. Approved for construction and emergency response.'],
    ['title' => 'Walkie-Talkie Set (2 units)', 'price' => 22000, 'condition' => 'Excellent', 'seller' => 'TechAlert CM', 'region' => 'Bafoussam', 'icon' => 'radio', 'color' => '#8b5cf6', 'verified' => false, 'category' => 'Communication', 'desc' => '5km range two-way radio pair. Ideal for neighbourhood patrol and emergency coordination.'],
    ['title' => 'Fire Extinguisher 6kg', 'price' => 18000, 'condition' => 'New', 'seller' => 'Cameroon Safety Store', 'region' => 'Douala', 'icon' => 'local_fire_department', 'color' => '#ef4444', 'verified' => true, 'category' => 'Fire Safety', 'desc' => 'Certified dry-powder fire extinguisher. Suitable for home and commercial use.'],
    ['title' => 'Solar Emergency Lantern', 'price' => 6500, 'condition' => 'New', 'seller' => 'SolarTech CM', 'region' => 'Garoua', 'icon' => 'lightbulb', 'color' => '#eab308', 'verified' => false, 'category' => 'Power & Lighting', 'desc' => 'Solar-powered LED lantern with USB charging port. 12-hour battery life.'],
    ['title' => 'Personal Safety Alarm', 'price' => 3500, 'condition' => 'New', 'seller' => 'SheSafe Cameroon', 'region' => 'Yaoundé', 'icon' => 'alarm', 'color' => '#ec4899', 'verified' => true, 'category' => 'Safety Equipment', 'desc' => '130dB personal alarm with built-in LED. Recommended for women and night workers.'],
    ['title' => 'Emergency Water Purifier', 'price' => 12000, 'condition' => 'New', 'seller' => 'AquaSafe CM', 'region' => 'Bamenda', 'icon' => 'water_drop', 'color' => '#06b6d4', 'verified' => true, 'category' => 'Emergency Tools', 'desc' => 'Portable water filter capable of purifying up to 1500L. Essential for crisis situations.'],
    ['title' => 'Heavy-Duty Flashlight', 'price' => 4200, 'condition' => 'Used - Good', 'seller' => 'Kwanga Tools', 'region' => 'Douala', 'icon' => 'flashlight_on', 'color' => '#64748b', 'verified' => false, 'category' => 'Power & Lighting', 'desc' => '1000-lumen tactical flashlight. Waterproof and impact-resistant.'],
];
?>

<style>
    .market-listing {
        background: white; border-radius: 16px;
        border: 1.5px solid var(--rs-border); transition: all 0.25s;
        overflow: hidden; display: flex; flex-direction: column;
    }
    .market-listing:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(0,0,0,0.1); border-color: var(--rs-primary); }
    .category-pill {
        display: flex; align-items: center; gap: 8px;
        padding: 0.6rem 1rem; border-radius: 10px; border: 1.5px solid var(--rs-border);
        cursor: pointer; background: white; transition: all 0.2s; font-weight: 700; font-size: 0.82rem;
    }
    .category-pill:hover, .category-pill.active { border-color: var(--rs-primary); background: var(--rs-primary); color: white; }
    .category-pill.active span { color: white !important; }
    .contact-btn {
        background: var(--rs-primary); color: white; border: none;
        border-radius: 8px; padding: 0.55rem 1rem; font-weight: 700;
        font-size: 0.8rem; cursor: pointer; width: 100%;
        display: flex; align-items: center; justify-content: center; gap: 6px; transition: opacity 0.2s;
    }
    .contact-btn:hover { opacity: 0.85; }
    .condition-tag {
        font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
        padding: 2px 8px; border-radius: 6px;
    }
</style>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 900; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-outlined" style="color: var(--rs-secondary);">shopping_bag</span>
            Safety Marketplace
        </h1>
        <p style="color: #64748b; font-size: 0.9rem;">Buy and sell safety equipment within the Sentinel community</p>
    </div>
    <button onclick="alert('Listing creation coming soon!')" style="background: var(--rs-secondary); color: white; border: none; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined">add</span> Post a Listing
    </button>
</div>

<!-- Search + Filters -->
<div class="rs-card reveal" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="position: relative; flex: 1; min-width: 200px;">
            <span class="material-symbols-outlined" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;">search</span>
            <input id="marketSearch" type="text" placeholder="Search listings..." oninput="filterListings()"
                style="width: 100%; padding: 0.7rem 1rem 0.7rem 2.5rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.88rem; outline: none; font-family: inherit; box-sizing: border-box;">
        </div>
        <select id="regionFilter" onchange="filterListings()" style="padding: 0.7rem 1rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.88rem; font-weight: 600; background: var(--rs-bg); font-family: inherit;">
            <option value="all">All Regions</option>
            <option value="douala">Douala</option>
            <option value="yaoundé">Yaoundé</option>
            <option value="bafoussam">Bafoussam</option>
            <option value="bamenda">Bamenda</option>
            <option value="garoua">Garoua</option>
        </select>
        <select id="conditionFilter" onchange="filterListings()" style="padding: 0.7rem 1rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.88rem; font-weight: 600; background: var(--rs-bg); font-family: inherit;">
            <option value="all">Any Condition</option>
            <option value="new">New</option>
            <option value="excellent">Excellent</option>
            <option value="used">Used</option>
        </select>
    </div>
</div>

<!-- Category Pills -->
<div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
    <button class="category-pill active" onclick="filterByCategory('all', this)">
        <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--rs-primary);">apps</span> All Categories
    </button>
    <?php foreach ($categories as $cat): ?>
    <button class="category-pill" onclick="filterByCategory('<?php echo strtolower($cat['name']); ?>', this)">
        <span class="material-symbols-outlined" style="font-size: 1rem; color: <?php echo $cat['color']; ?>;"><?php echo $cat['icon']; ?></span>
        <?php echo $cat['name']; ?>
        <span style="background: var(--rs-bg); padding: 1px 6px; border-radius: 6px; font-size: 0.7rem;"><?php echo $cat['count']; ?></span>
    </button>
    <?php endforeach; ?>
</div>

<!-- Listings Grid -->
<div id="listingsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1.25rem;">
    <?php foreach ($listings as $l): ?>
    <div class="market-listing reveal"
        data-title="<?php echo strtolower($l['title']); ?>"
        data-region="<?php echo strtolower($l['region']); ?>"
        data-condition="<?php echo strtolower(explode(' ', $l['condition'])[0]); ?>"
        data-category="<?php echo strtolower($l['category']); ?>">

        <!-- Icon Banner -->
        <div style="background: <?php echo $l['color']; ?>18; padding: 1.5rem; display: flex; align-items: center; justify-content: center; border-bottom: 1.5px solid <?php echo $l['color']; ?>22;">
            <div style="width: 56px; height: 56px; border-radius: 16px; background: <?php echo $l['color']; ?>22; display: flex; align-items: center; justify-content: center;">
                <span class="material-symbols-outlined" style="color: <?php echo $l['color']; ?>; font-size: 2rem;"><?php echo $l['icon']; ?></span>
            </div>
        </div>

        <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 10px; flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                <h3 style="font-weight: 800; font-size: 0.95rem; margin: 0; line-height: 1.3;"><?php echo htmlspecialchars($l['title']); ?></h3>
                <span class="condition-tag" style="background: <?php echo $l['condition'] === 'New' ? '#dcfce7' : ($l['condition'] === 'Excellent' ? '#dbeafe' : '#fef9c3'); ?>; color: <?php echo $l['condition'] === 'New' ? '#16a34a' : ($l['condition'] === 'Excellent' ? '#1d4ed8' : '#a16207'); ?>; flex-shrink: 0;">
                    <?php echo htmlspecialchars($l['condition']); ?>
                </span>
            </div>

            <p style="font-size: 0.82rem; color: #64748b; line-height: 1.5; margin: 0;"><?php echo htmlspecialchars($l['desc']); ?></p>

            <div style="font-size: 1.4rem; font-weight: 900; color: var(--rs-primary);">
                <?php echo number_format($l['price']); ?> <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">FCFA</span>
            </div>

            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.78rem; color: #64748b;">
                <span class="material-symbols-outlined" style="font-size: 0.9rem;">person</span>
                <?php echo htmlspecialchars($l['seller']); ?>
                <?php if ($l['verified']): ?>
                <span class="material-symbols-outlined" style="font-size: 0.9rem; color: #3b82f6;" title="Verified Seller">verified</span>
                <?php endif; ?>
                <span style="margin-left: auto; display: flex; align-items: center; gap: 4px;">
                    <span class="material-symbols-outlined" style="font-size: 0.9rem;">location_on</span>
                    <?php echo htmlspecialchars($l['region']); ?>
                </span>
            </div>

            <button class="contact-btn" onclick="alert('Contact feature coming soon! Messaging system is being built.')">
                <span class="material-symbols-outlined" style="font-size: 1rem;">chat</span> Contact Seller
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="noListings" style="display:none; text-align:center; padding: 4rem; opacity: 0.5;">
    <span class="material-symbols-outlined" style="font-size: 3rem;">shopping_cart_off</span>
    <p style="margin-top: 0.75rem; font-weight: 600;">No listings match your search.</p>
</div>

<script>
    let activeCategoryFilter = 'all';

    function filterByCategory(cat, btn) {
        activeCategoryFilter = cat;
        document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        filterListings();
    }

    function filterListings() {
        const q = document.getElementById('marketSearch').value.toLowerCase();
        const region = document.getElementById('regionFilter').value;
        const cond = document.getElementById('conditionFilter').value;
        const cards = document.querySelectorAll('#listingsGrid .market-listing');
        let visible = 0;

        cards.forEach(card => {
            const matchTitle = card.dataset.title.includes(q);
            const matchRegion = region === 'all' || card.dataset.region === region;
            const matchCond = cond === 'all' || card.dataset.condition.startsWith(cond);
            const matchCat = activeCategoryFilter === 'all' || card.dataset.category.includes(activeCategoryFilter);
            const show = matchTitle && matchRegion && matchCond && matchCat;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        document.getElementById('noListings').style.display = visible === 0 ? 'block' : 'none';
    }
</script>

<?php require_once 'includes/footer.php'; ?>
