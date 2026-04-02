<?php
require_once 'database/config.php';
require_once 'includes/auth.php';

// Auto-protect and process logic before sending any HTML
autoProtect();
require_once 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $incident_type = $_POST['incident_type'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $location_address = $_POST['location_address'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    
    // Validation
    if (empty($title) || empty($description) || empty($incident_type) || empty($severity)) {
        $errors[] = 'Please fill in all required fields.';
    }
    
    if (empty($errors)) {
        $db = new Database();
        
        $stmt = $db->query("
            INSERT INTO incidents (user_id, title, description, incident_type, severity, latitude, longitude, location_address, is_anonymous) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $_SESSION['user_id'] ?? null,
            $title,
            $description,
            $incident_type,
            $severity,
            $latitude ?: null,
            $longitude ?: null,
            $location_address,
            $is_anonymous
        ]);
        
        if ($stmt) {
            $incident_id = $db->lastInsertId();
            
            // Handle file uploads if any
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/incidents/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if ($_FILES['attachments']['error'][$key] === 0) {
                        $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                        $file_path = $upload_dir . time() . '_' . $name;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            $file_type = pathinfo($name, PATHINFO_EXTENSION) === 'pdf' ? 'document' : 'image';
                            
                            $db->query("
                                INSERT INTO incident_attachments (incident_id, file_path, file_type) 
                                VALUES (?, ?, ?)
                            ", [$incident_id, $file_path, $file_type]);
                        }
                    }
                }
            }
            
            $success = 'Incident reported successfully! Our team will review it shortly.';
        } else {
            $errors[] = 'Failed to report incident. Please try again.';
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Report New Incident</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="report_incident.php" class="space-y-6">
        <?php csrfInput(); ?>
        <div class="grid grid-cols-2 gap-6">
            <div class="form-group">
                <label for="title" class="form-label">Incident Title *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="form-input" 
                    required
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    placeholder="Brief description of the incident"
                >
            </div>
            
            <div class="form-group">
                <label for="incident_type" class="form-label">Incident Type *</label>
                <select id="incident_type" name="incident_type" class="form-input" required>
                    <option value="">Select type...</option>
                    <option value="theft" <?php echo ($_POST['incident_type'] ?? '') === 'theft' ? 'selected' : ''; ?>>Theft</option>
                    <option value="assault" <?php echo ($_POST['incident_type'] ?? '') === 'assault' ? 'selected' : ''; ?>>Assault</option>
                    <option value="accident" <?php echo ($_POST['incident_type'] ?? '') === 'accident' ? 'selected' : ''; ?>>Accident</option>
                    <option value="fire" <?php echo ($_POST['incident_type'] ?? '') === 'fire' ? 'selected' : ''; ?>>Fire</option>
                    <option value="medical" <?php echo ($_POST['incident_type'] ?? '') === 'medical' ? 'selected' : ''; ?>>Medical Emergency</option>
                    <option value="other" <?php echo ($_POST['incident_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description" class="form-label">Detailed Description *</label>
            <textarea 
                id="description" 
                name="description" 
                class="form-input form-textarea" 
                required
                placeholder="Provide detailed information about what happened..."
            ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="grid grid-cols-2 gap-6">
            <div class="form-group">
                <label for="severity" class="form-label">Severity Level *</label>
                <select id="severity" name="severity" class="form-input" required>
                    <option value="">Select severity...</option>
                    <option value="low" <?php echo ($_POST['severity'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo ($_POST['severity'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo ($_POST['severity'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo ($_POST['severity'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="location_address" class="form-label">Location Address</label>
                <input 
                    type="text" 
                    id="location_address" 
                    name="location_address" 
                    class="form-input"
                    value="<?php echo htmlspecialchars($_POST['location_address'] ?? ''); ?>"
                    placeholder="Enter address or landmark"
                >
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-6">
            <div class="form-group">
                <label for="latitude" class="form-label">Latitude (Optional)</label>
                <input 
                    type="number" 
                    id="latitude" 
                    name="latitude" 
                    step="any"
                    class="form-input"
                    value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>"
                    placeholder="e.g., 3.8480"
                >
            </div>
            
            <div class="form-group">
                <label for="longitude" class="form-label">Longitude (Optional)</label>
                <input 
                    type="number" 
                    id="longitude" 
                    name="longitude" 
                    step="any"
                    class="form-input"
                    value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>"
                    placeholder="e.g., 11.5021"
                >
            </div>
        </div>
        
        <div class="form-group">
            <label class="flex items-center">
                <input type="checkbox" name="is_anonymous" class="mr-2" <?php echo isset($_POST['is_anonymous']) ? 'checked' : ''; ?>>
                <span class="text-sm">Report anonymously</span>
            </label>
        </div>
        
        <div class="form-group">
            <label for="attachments" class="form-label">Attachments (Optional)</label>
            <input 
                type="file" 
                id="attachments" 
                name="attachments[]" 
                class="form-input"
                multiple
                accept="image/*,video/*,.pdf,.doc,.docx"
            >
            <p class="text-xs text-gray-500 mt-1">You can upload multiple images, videos, or documents</p>
        </div>
        
        <div class="flex gap-4">
            <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined">send</span>
                Submit Report
            </button>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
