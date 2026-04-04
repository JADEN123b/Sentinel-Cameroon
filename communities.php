<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();

$communities = [
    ['name' => 'Douala Safety Watch', 'members' => 142, 'posts' => 38, 'icon' => 'location_city', 'color' => '#3b82f6', 'region' => 'Littoral', 'desc' => 'A community for Douala residents to report and track safety events across all neighbourhoods.'],
    ['name' => 'Yaoundé Alert Network', 'members' => 217, 'posts' => 61, 'icon' => 'campaign', 'color' => '#8b5cf6', 'region' => 'Centre', 'desc' => 'Connecting citizens and authorities in the capital to respond faster to incidents.'],
    ['name' => 'Northwest Crisis Response', 'members' => 89, 'posts' => 24, 'icon' => 'sos', 'color' => '#ef4444', 'region' => 'Northwest', 'desc' => 'Emergency coordination and community support for the Northwest region.'],
    ['name' => 'Southwest Neighborhood Watch', 'members' => 73, 'posts' => 19, 'icon' => 'visibility', 'color' => '#10b981', 'region' => 'Southwest', 'desc' => 'Residents helping residents stay safe across the Southwest region.'],
    ['name' => 'Medical Emergency Network', 'members' => 105, 'posts' => 44, 'icon' => 'medical_services', 'color' => '#f97316', 'region' => 'National', 'desc' => 'Connecting medical volunteers, first responders, and hospitals across Cameroon.'],
    ['name' => 'Women Safety Circle', 'members' => 190, 'posts' => 52, 'icon' => 'diversity_3', 'color' => '#ec4899', 'region' => 'National', 'desc' => 'A safe space for women to share safety concerns, resources, and support networks.'],
];

$announcements = [
    ['title' => 'New Emergency Hotline Active', 'body' => 'The national emergency coordination centre has activated a new 24/7 hotline: 1515.', 'time' => '2 hours ago', 'icon' => 'phone', 'color' => '#ef4444'],
    ['title' => 'Community Training Workshop', 'body' => 'A free first-aid and emergency response training is scheduled for April 15 in Douala.', 'time' => '1 day ago', 'icon' => 'school', 'color' => '#3b82f6'],
    ['title' => 'Neighbourhood Patrol Initiative', 'body' => 'Join the volunteer neighbourhood patrol programme launching in Yaoundé next week.', 'time' => '3 days ago', 'icon' => 'groups', 'color' => '#10b981'],
];
?>

<style>
    .community-card {
        background: white; border-radius: 16px; padding: 1.5rem;
        border: 1.5px solid var(--rs-border); transition: all 0.25s;
        display: flex; flex-direction: column; gap: 12px;
        position: relative; overflow: hidden;
    }
    .community-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); border-color: var(--rs-primary); }
    .community-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    }
    .comm-join-btn {
        background: var(--rs-primary); color: white; border: none; border-radius: 10px;
        padding: 0.6rem 1.25rem; font-weight: 700; font-size: 0.85rem;
        cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px;
        text-decoration: none; width: fit-content; margin-top: auto;
    }
    .comm-join-btn:hover { opacity: 0.88; transform: scale(1.02); }
    .announcement-item {
        display: flex; gap: 1rem; align-items: flex-start;
        padding: 1rem; background: var(--rs-bg); border-radius: 12px;
        border: 1.5px solid var(--rs-border); transition: background 0.2s;
    }
    .announcement-item:hover { background: white; }
    .region-badge {
        font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 2px 8px; border-radius: 6px;
        background: var(--rs-bg); color: #64748b;
    }
</style>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 900; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-outlined" style="color: var(--rs-secondary);">groups</span>
            Communities
        </h1>
        <p style="color: #64748b; font-size: 0.9rem;">Connect with local safety networks and neighbourhood groups</p>
    </div>
    <button onclick="alert('Community creation coming soon!')" style="background: var(--rs-secondary); color: white; border: none; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined">add</span> Start a Community
    </button>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">

    <!-- Communities Grid -->
    <div>
        <!-- Search Bar -->
        <div class="rs-card reveal" style="padding: 1rem; margin-bottom: 1.5rem;">
            <div style="position: relative;">
                <span class="material-symbols-outlined" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;">search</span>
                <input id="communitySearch" type="text" placeholder="Search communities by name or region..." oninput="filterCommunities()"
                    style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.9rem; outline: none; font-family: inherit; box-sizing: border-box;">
            </div>
        </div>

        <div id="communityGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
            <?php foreach ($communities as $idx => $c): ?>
            <div class="community-card reveal" data-name="<?php echo strtolower($c['name'] . ' ' . $c['region']); ?>" style="border-top-color: <?php echo $c['color']; ?>; border-top-width: 4px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: <?php echo $c['color']; ?>22; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined" style="color: <?php echo $c['color']; ?>; font-size: 1.4rem;"><?php echo $c['icon']; ?></span>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;"><?php echo htmlspecialchars($c['name']); ?></div>
                        <span class="region-badge"><?php echo htmlspecialchars($c['region']); ?></span>
                    </div>
                </div>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.6; margin: 0;"><?php echo htmlspecialchars($c['desc']); ?></p>
                <div style="display: flex; gap: 1.5rem;">
                    <div style="text-align: center;">
                        <div style="font-weight: 900; font-size: 1.2rem; color: var(--rs-primary);"><?php echo $c['members']; ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Members</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-weight: 900; font-size: 1.2rem; color: var(--rs-secondary);"><?php echo $c['posts']; ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Posts</div>
                    </div>
                </div>
                <a href="#" class="comm-join-btn" onclick="alert('Full community features coming soon! You will be able to join, post updates, and share safety tips.')">
                    <span class="material-symbols-outlined" style="font-size: 1rem;">group_add</span> Join Community
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="noResults" style="display:none; text-align:center; padding: 3rem; opacity: 0.5;">
            <span class="material-symbols-outlined" style="font-size: 2.5rem;">search_off</span>
            <p style="margin-top:0.75rem; font-weight: 600;">No communities match your search.</p>
        </div>
    </div>

    <!-- Sidebar: Announcements + Stats -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Platform Stats -->
        <div class="rs-card reveal">
            <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 1.25rem; font-weight: 800;">Network Overview</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: center;">
                <div style="background: var(--rs-bg); border-radius: 12px; padding: 1rem;">
                    <div style="font-weight: 900; font-size: 1.5rem; color: var(--rs-primary);"><?php echo count($communities); ?></div>
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Communities</div>
                </div>
                <div style="background: var(--rs-bg); border-radius: 12px; padding: 1rem;">
                    <div style="font-weight: 900; font-size: 1.5rem; color: var(--rs-secondary);"><?php echo array_sum(array_column($communities, 'members')); ?></div>
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Members</div>
                </div>
            </div>
        </div>

        <!-- Announcements -->
        <div class="rs-card reveal" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1.5px solid var(--rs-border); display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-outlined" style="color: var(--rs-secondary);">notifications</span>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 800;">Announcements</h3>
            </div>
            <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($announcements as $a): ?>
                <div class="announcement-item">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: <?php echo $a['color']; ?>22; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined" style="color: <?php echo $a['color']; ?>; font-size: 1.1rem;"><?php echo $a['icon']; ?></span>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.88rem; margin-bottom: 3px;"><?php echo htmlspecialchars($a['title']); ?></div>
                        <p style="font-size: 0.8rem; color: #64748b; margin: 0 0 4px;"><?php echo htmlspecialchars($a['body']); ?></p>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700;"><?php echo $a['time']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script>
    function filterCommunities() {
        const q = document.getElementById('communitySearch').value.toLowerCase();
        const cards = document.querySelectorAll('#communityGrid .community-card');
        let visible = 0;
        cards.forEach(card => {
            const match = card.dataset.name.includes(q);
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
    }
</script>

<?php require_once 'includes/footer.php'; ?>
