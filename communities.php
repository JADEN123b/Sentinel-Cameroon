<?php
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$db = new Database();
$userId = getCurrentUserId();

// Handle Actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'join') {
        $cid = (int)($_POST['community_id'] ?? 0);
        $existing = $db->fetch("SELECT id FROM community_members WHERE community_id=? AND user_id=?", [$cid, $userId]);
        if (!$existing) {
            $db->query("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)", [$cid, $userId]);
            $db->query("UPDATE communities SET member_count = member_count + 1 WHERE id=?", [$cid]);
            $success = 'You have joined the community!';
        } else {
            $error = 'You are already a member of this community.';
        }
    }

    if ($action === 'leave') {
        $cid = (int)($_POST['community_id'] ?? 0);
        $db->query("DELETE FROM community_members WHERE community_id=? AND user_id=?", [$cid, $userId]);
        $db->query("UPDATE communities SET member_count = GREATEST(0, member_count-1) WHERE id=?", [$cid]);
        $success = 'You have left the community.';
    }

    if ($action === 'post') {
        $cid = (int)($_POST['community_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        if ($content && $cid) {
            $isMember = $db->fetch("SELECT id FROM community_members WHERE community_id=? AND user_id=?", [$cid, $userId]);
            if ($isMember) {
                $db->query("INSERT INTO community_posts (community_id, user_id, content) VALUES (?,?,?)", [$cid, $userId, $content]);
                $db->query("UPDATE communities SET post_count = post_count + 1 WHERE id=?", [$cid]);
                $success = 'Your post has been shared!';
            } else {
                $error = 'You must be a member to post in this community.';
            }
        } else {
            $error = 'Post content cannot be empty.';
        }
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $region = trim($_POST['region'] ?? '');
        if ($name && $region) {
            $db->query("INSERT INTO communities (name, description, region, created_by) VALUES (?,?,?,?)", [$name, $desc, $region, $userId]);
            $newId = $db->lastInsertId();
            $db->query("INSERT INTO community_members (community_id, user_id) VALUES (?,?)", [$newId, $userId]);
            $success = 'Community created successfully!';
        } else {
            $error = 'Name and region are required.';
        }
    }
}

// Load communities with membership status
$communities = $db->fetchAll("
    SELECT c.*,
        (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id=c.id AND cm.user_id=?) as is_member
    FROM communities c
    WHERE c.is_active=1
    ORDER BY c.member_count DESC
", [$userId]);

// Load recent posts
$recentPosts = $db->fetchAll("
    SELECT cp.*, c.name as community_name, u.full_name as poster_name
    FROM community_posts cp
    JOIN communities c ON cp.community_id = c.id
    JOIN users u ON cp.user_id = u.id
    ORDER BY cp.created_at DESC
    LIMIT 5
");

$totalMembers = array_sum(array_column($communities, 'member_count'));
$totalPosts   = array_sum(array_column($communities, 'post_count'));
?>

<style>
.community-card {
    background: white; border-radius: 16px; padding: 1.5rem;
    border: 1.5px solid var(--rs-border); transition: all 0.25s;
    display: flex; flex-direction: column; gap: 12px; position: relative; overflow: hidden;
}
.community-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.1); border-color: var(--rs-primary); }
.join-btn {
    background: var(--rs-primary); color: white; border: none; border-radius: 10px;
    padding: 0.55rem 1.1rem; font-weight: 700; font-size: 0.82rem; cursor: pointer;
    transition: all 0.2s; display: flex; align-items: center; gap: 6px; width: fit-content;
}
.join-btn.is-member { background: #f1f5f9; color: #64748b; }
.join-btn:hover { opacity: 0.85; }
.post-card {
    background: white; border-radius: 12px; padding: 1rem 1.25rem;
    border: 1.5px solid var(--rs-border); transition: background 0.2s;
}
.post-card:hover { background: #f8fafc; }
.alert-box {
    padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 600;
    font-size: 0.88rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 10px;
}
.alert-success { background: #dcfce7; color: #166534; border: 1.5px solid #bbf7d0; }
.alert-error   { background: #fee2e2; color: #991b1b; border: 1.5px solid #fecaca; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); z-index: 9000; display: flex; align-items: center; justify-content: center; }
.modal-box { background: white; border-radius: 20px; padding: 2rem; width: 90%; max-width: 500px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); }
</style>

<?php if ($success): ?>
<div class="alert-box alert-success"><span class="material-symbols-outlined">check_circle</span><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-box alert-error"><span class="material-symbols-outlined">error</span><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 900; margin-bottom: 0.25rem; display: flex; align-items: center; gap: 10px;">
            <span class="material-symbols-outlined" style="color: var(--rs-secondary);">groups</span>
            Communities
        </h1>
        <p style="color: #64748b; font-size: 0.9rem;">Connect with local safety networks and neighbourhood groups</p>
    </div>
    <button onclick="document.getElementById('createModal').style.display='flex'"
        style="background: var(--rs-secondary); color: white; border: none; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <span class="material-symbols-outlined">add</span> Start a Community
    </button>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">

    <!-- Communities Grid -->
    <div>
        <!-- Search -->
        <div class="rs-card reveal" style="padding: 1rem; margin-bottom: 1.5rem;">
            <div style="position: relative;">
                <span class="material-symbols-outlined" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;">search</span>
                <input id="communitySearch" type="text" placeholder="Search communities..." oninput="filterCommunities()"
                    style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.9rem; outline: none; font-family: inherit; box-sizing: border-box;">
            </div>
        </div>

        <?php if (empty($communities)): ?>
        <div class="rs-card reveal" style="text-align: center; padding: 4rem;">
            <span class="material-symbols-outlined" style="font-size: 3rem; opacity:0.4;">group_off</span>
            <p style="margin-top: 1rem; color: #64748b; font-weight: 600;">No communities yet. Be the first to create one!</p>
        </div>
        <?php else: ?>
        <div id="communityGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem;">
            <?php 
            $iconMap = ['location_city','campaign','sos','visibility','medical_services','diversity_3','groups','shield','apartment'];
            $colorMap = ['#3b82f6','#8b5cf6','#ef4444','#10b981','#f97316','#ec4899'];
            foreach ($communities as $idx => $c): 
                $icon = $c['icon'] ?? $iconMap[$idx % count($iconMap)];
                $color = $c['color'] ?? $colorMap[$idx % count($colorMap)];
            ?>
            <div class="community-card reveal" data-name="<?php echo strtolower(htmlspecialchars($c['name'] . ' ' . $c['region'])); ?>" style="border-top: 4px solid <?php echo htmlspecialchars($color); ?>;">

                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: <?php echo htmlspecialchars($color); ?>22; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <span class="material-symbols-outlined" style="color: <?php echo htmlspecialchars($color); ?>; font-size: 1.4rem;"><?php echo htmlspecialchars($icon); ?></span>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 0.95rem;"><?php echo htmlspecialchars($c['name']); ?></div>
                        <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; background: var(--rs-bg); color: #64748b; padding: 2px 8px; border-radius: 6px;"><?php echo htmlspecialchars($c['region']); ?></span>
                    </div>
                </div>

                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.6; margin: 0;"><?php echo htmlspecialchars($c['description'] ?? ''); ?></p>

                <div style="display: flex; gap: 1.5rem;">
                    <div style="text-align: center;">
                        <div style="font-weight: 900; font-size: 1.2rem; color: var(--rs-primary);"><?php echo (int)$c['member_count']; ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Members</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-weight: 900; font-size: 1.2rem; color: var(--rs-secondary);"><?php echo (int)$c['post_count']; ?></div>
                        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Posts</div>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="<?php echo $c['is_member'] ? 'leave' : 'join'; ?>">
                        <input type="hidden" name="community_id" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="join-btn <?php echo $c['is_member'] ? 'is-member' : ''; ?>">
                            <span class="material-symbols-outlined" style="font-size: 1rem;"><?php echo $c['is_member'] ? 'exit_to_app' : 'group_add'; ?></span>
                            <?php echo $c['is_member'] ? 'Leave' : 'Join'; ?>
                        </button>
                    </form>
                    <?php if ($c['is_member']): ?>
                    <button onclick="openPostModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')"
                        style="background: var(--rs-bg); border: 1.5px solid var(--rs-border); border-radius: 10px; padding: 0.55rem 1rem; font-weight: 700; font-size: 0.82rem; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <span class="material-symbols-outlined" style="font-size: 1rem;">edit_note</span> Post
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="noResults" style="display:none; text-align:center; padding: 3rem; opacity: 0.5;">
            <span class="material-symbols-outlined" style="font-size: 2.5rem;">search_off</span>
            <p style="margin-top:0.75rem; font-weight: 600;">No communities match your search.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Network Stats -->
        <div class="rs-card reveal">
            <h4 style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 1.25rem; font-weight: 800;">Network Overview</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: center;">
                <div style="background: var(--rs-bg); border-radius: 12px; padding: 1rem;">
                    <div style="font-weight: 900; font-size: 1.5rem; color: var(--rs-primary);"><?php echo count($communities); ?></div>
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Communities</div>
                </div>
                <div style="background: var(--rs-bg); border-radius: 12px; padding: 1rem;">
                    <div style="font-weight: 900; font-size: 1.5rem; color: var(--rs-secondary);"><?php echo $totalMembers; ?></div>
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Members</div>
                </div>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="rs-card reveal" style="padding: 0; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1.5px solid var(--rs-border); display: flex; align-items: center; gap: 10px;">
                <span class="material-symbols-outlined" style="color: var(--rs-secondary);">chat</span>
                <h3 style="margin: 0; font-size: 1rem; font-weight: 800;">Recent Posts</h3>
            </div>
            <div style="padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php if (empty($recentPosts)): ?>
                <p style="text-align: center; color: #94a3b8; padding: 1.5rem 0; font-size: 0.85rem;">No posts yet. Join a community and start sharing!</p>
                <?php else: ?>
                <?php foreach ($recentPosts as $post): ?>
                <div class="post-card">
                    <div style="font-weight: 800; font-size: 0.8rem; color: var(--rs-primary); margin-bottom: 4px;"><?php echo htmlspecialchars($post['community_name']); ?></div>
                    <p style="font-size: 0.82rem; color: #475569; margin: 0 0 6px; line-height: 1.5;">
                        <?php echo htmlspecialchars(substr($post['content'], 0, 100)); ?><?php echo strlen($post['content']) > 100 ? '...' : ''; ?>
                    </p>
                    <div style="font-size: 0.72rem; color: #94a3b8; font-weight: 700;">
                        by <?php echo htmlspecialchars($post['poster_name']); ?> · <?php echo date('M j, g:i A', strtotime($post['created_at'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Community Modal -->
<div id="createModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.2rem; font-weight: 900; margin: 0;">Start a Community</h2>
            <button onclick="document.getElementById('createModal').style.display='none'" style="background: none; border: none; cursor: pointer;">
                <span class="material-symbols-outlined" style="color: #94a3b8;">close</span>
            </button>
        </div>
        <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" name="action" value="create">
            <div>
                <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">Community Name *</label>
                <input type="text" name="name" required placeholder="e.g. Douala Safety Watch"
                    style="width: 100%; padding: 0.75rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.9rem; font-family: inherit; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">Region *</label>
                <select name="region" required style="width: 100%; padding: 0.75rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.9rem; font-family: inherit; background: white;">
                    <option value="">Select region...</option>
                    <option>Littoral</option><option>Centre</option><option>Northwest</option>
                    <option>Southwest</option><option>West</option><option>Adamawa</option>
                    <option>Far North</option><option>North</option><option>East</option>
                    <option>South</option><option>National</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">Description</label>
                <textarea name="description" rows="3" placeholder="What is this community about?"
                    style="width: 100%; padding: 0.75rem; border: 1.5px solid var(--rs-border); border-radius: 10px; font-size: 0.9rem; font-family: inherit; resize: vertical; box-sizing: border-box;"></textarea>
            </div>
            <button type="submit" style="background: var(--rs-secondary); color: white; border: none; border-radius: 10px; padding: 0.9rem; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                Create Community
            </button>
        </form>
    </div>
</div>

<!-- Post Modal -->
<div id="postModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.2rem; font-weight: 900; margin: 0;">Post to: <span id="postModalName" style="color: var(--rs-primary);"></span></h2>
            <button onclick="document.getElementById('postModal').style.display='none'" style="background: none; border: none; cursor: pointer;">
                <span class="material-symbols-outlined" style="color: #94a3b8;">close</span>
            </button>
        </div>
        <form method="POST" style="display: flex; flex-direction: column; gap: 1rem;">
            <input type="hidden" name="action" value="post">
            <input type="hidden" name="community_id" id="postCommunityId">
            <textarea name="content" rows="5" required placeholder="Share a safety tip, update, or community message..."
                style="width: 100%; padding: 0.9rem; border: 1.5px solid var(--rs-border); border-radius: 12px; font-size: 0.9rem; font-family: inherit; resize: vertical; box-sizing: border-box; outline: none;"></textarea>
            <button type="submit" style="background: var(--rs-primary); color: white; border: none; border-radius: 10px; padding: 0.9rem; font-weight: 700; font-size: 0.95rem; cursor: pointer;">
                <span class="material-symbols-outlined" style="vertical-align: middle;">send</span> Share Post
            </button>
        </form>
    </div>
</div>

<script>
    function filterCommunities() {
        const q = document.getElementById('communitySearch').value.toLowerCase();
        const cards = document.querySelectorAll('#communityGrid .community-card');
        let visible = 0;
        cards.forEach(card => {
            const show = card.dataset.name.includes(q);
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const noResults = document.getElementById('noResults');
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    function openPostModal(id, name) {
        document.getElementById('postCommunityId').value = id;
        document.getElementById('postModalName').textContent = name;
        document.getElementById('postModal').style.display = 'flex';
    }

    // Close modals on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
