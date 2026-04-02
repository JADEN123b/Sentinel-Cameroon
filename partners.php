<?php
require_once 'includes/header.php';
require_once 'database/config.php';

// Get partners from database
$db = new Database();
$partners = $db->query("
    SELECT * FROM partners 
    WHERE is_verified = 1 
    ORDER BY partner_type, name
")->fetchAll();

// Group by type
$partners_by_type = [];
foreach ($partners as $partner) {
    $partners_by_type[$partner['partner_type']][] = $partner;
}
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Community Partners</h1>
    <a href="authority_register.php" class="btn btn-primary">
        <span class="material-symbols-outlined">add_business</span>
        Register as Agency
    </a>
</div>

<div class="grid grid-cols-1 gap-8">
    <?php foreach ($partners_by_type as $type => $type_partners): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="flex items-center gap-2">
                    <span class="material-symbols-outlined"><?php
                        switch($type) {
                            case 'security': echo 'security'; break;
                            case 'medical': echo 'medical_services'; break;
                            case 'government': echo 'account_balance'; break;
                            case 'community': echo 'groups'; break;
                            case 'business': echo 'store'; break;
                            default: echo 'handshake';
                        }
                    ?></span>
                    <?php echo ucfirst($type); ?> Partners
                </h2>
                <span class="text-sm text-gray-600"><?php echo count($type_partners); ?> verified</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($type_partners as $partner): ?>
                    <div class="border border-surface-container-low rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="font-bold"><?php echo htmlspecialchars($partner['name']); ?></h3>
                            <span class="bg-success text-white text-xs px-2 py-1 rounded">Verified</span>
                        </div>
                        
                        <p class="text-sm text-gray-700 mb-4"><?php echo htmlspecialchars($partner['description']); ?></p>
                        
                        <div class="space-y-2 text-sm">
                            <?php if ($partner['contact_email']): ?>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-500">email</span>
                                    <a href="mailto:<?php echo htmlspecialchars($partner['contact_email']); ?>" class="text-primary hover:underline">
                                        <?php echo htmlspecialchars($partner['contact_email']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($partner['contact_phone']): ?>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-500">phone</span>
                                    <a href="tel:<?php echo htmlspecialchars($partner['contact_phone']); ?>" class="text-primary hover:underline">
                                        <?php echo htmlspecialchars($partner['contact_phone']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($partner['address']): ?>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-gray-500">location_on</span>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($partner['address']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-surface-container-low">
                            <button onclick="contactPartner(<?php echo $partner['id']; ?>)" class="btn btn-outline w-full">
                                <span class="material-symbols-outlined">contact_support</span>
                                Contact Partner
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-surface rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Contact Partner</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form id="contactForm" class="space-y-4">
            <input type="hidden" id="partnerId" name="partner_id">
            
            <div class="form-group">
                <label for="contactName" class="form-label">Your Name</label>
                <input type="text" id="contactName" name="name" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="contactEmail" class="form-label">Your Email</label>
                <input type="email" id="contactEmail" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="contactMessage" class="form-label">Message</label>
                <textarea id="contactMessage" name="message" class="form-input form-textarea" rows="4" required placeholder="How can this partner help you?"></textarea>
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined">send</span>
                    Send Message
                </button>
                <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function contactPartner(partnerId) {
        document.getElementById('partnerId').value = partnerId;
        document.getElementById('contactModal').classList.remove('hidden');
    }
    
    function closeModal() {
        document.getElementById('contactModal').classList.add('hidden');
    }
    
    // Handle contact form submission
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'contact_partner');
        
        fetch('api/contact.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Message sent successfully!');
                closeModal();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error sending message. Please try again.');
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('contactModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
