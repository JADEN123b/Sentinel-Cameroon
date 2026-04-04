<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

require_once 'includes/header.php';

$current_user = getCurrentUser();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['incident_type'] ?? '';
    $severity = $_POST['severity'] ?? 'low';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $address = $_POST['location_address'] ?? '';

    if (empty($title) || empty($description)) {
        $error = 'Please provide at least a title and description for the report.';
    } else {
        $db = new Database();

        $db->beginTransaction();
        try {
            $db->query("
                INSERT INTO incidents (user_id, title, description, incident_type, severity, status, latitude, longitude, location_address, created_at)
                VALUES (?, ?, ?, ?, ?, 'reported', ?, ?, ?, NOW())
            ", [$current_user['id'], $title, $description, $type, $severity, $latitude, $longitude, $address]);

            $incident_id = $db->lastInsertId();

            // Log activity
            $db->query(
                "INSERT INTO activity_logs (user_id, action_text, action_type) VALUES (?, ?, 'incident')",
                [$current_user['id'], "Reported a new incident: " . $title]
            );

            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Report submission failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="animate-rs">

    <!-- Header -->
    <div class="rs-card" style="margin-bottom: 2rem; border-left: 6px solid var(--rs-primary);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 2.25rem; margin-bottom: 5px;">Submit Safety Report</h1>
                <p style="color: #64748b; font-weight: 600;">Provide detailed information to help community responders.
                </p>
            </div>
            <div style="text-align: right;">
                <div
                    style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 5px;">
                    Reporter Status</div>
                <div style="font-size: 0.9rem; font-weight: 800; color: var(--rs-primary);">VERIFIED IDENTITY</div>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="rs-card animate-rs"
            style="background: #dcfce7; border-color: #86efac; color: #166534; padding: 3rem; text-align: center; margin-bottom: 2rem;">
            <span class="material-symbols-outlined" style="font-size: 5rem; margin-bottom: 1rem;">check_circle</span>
            <h2 style="font-size: 2rem; margin-bottom: 1rem;">Report Submitted Successfully</h2>
            <p style="font-size: 1.1rem; opacity: 0.8; margin-bottom: 2.5rem;">Your report has been logged in the system.
                Our community responders and authorities have been notified.</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <a href="incidents.php" class="btn-rs btn-rs-primary" style="padding: 1rem 2.5rem;">View All Reports</a>
                <a href="dashboard.php" class="btn-rs" style="background: #f1f5f9; padding: 1rem 2.5rem;">Back to
                    Dashboard</a>
            </div>
        </div>
    <?php else: ?>

        <?php if ($error): ?>
            <div class="rs-card"
                style="background: #fee2e2; color: #ef4444; border-color: #fecaca; padding: 1rem; margin-bottom: 2rem; font-weight: 700;">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 10px;">error</span>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="rs-grid rs-grid-main">
            <!-- Left Side: Basic Info -->
            <div class="rs-card">
                <h3 style="margin-bottom: 2rem; display: flex; align-items: center; gap: 12px; font-size: 1.25rem;">
                    <span class="material-symbols-outlined" style="color: var(--rs-primary);">description</span>
                    Incident Particulars
                </h3>

                <div style="margin-bottom: 1.5rem;">
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Report
                        Heading</label>
                    <input type="text" name="title"
                        style="width: 100%; padding: 1rem; border-radius: 10px; border: 2px solid #f1f5f9; font-weight: 700; font-size: 1rem;"
                        placeholder="e.g. Traffic Reroute in Douala Center" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Incident
                            Category</label>
                        <select name="incident_type"
                            style="width: 100%; padding: 1rem; border-radius: 10px; border: 2px solid #f1f5f9; font-weight: 700;">
                            <option value="theft">Theft / Robbery</option>
                            <option value="assault">Violence / Assault</option>
                            <option value="accident">Road Accident</option>
                            <option value="fire">Fire / Smoke</option>
                            <option value="medical" selected>Medical / Health Concern</option>
                            <option value="other">Other Concern</option>
                        </select>
                    </div>
                    <div>
                        <label
                            style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Initial
                            Priority</label>
                        <select name="severity"
                            style="width: 100%; padding: 1rem; border-radius: 10px; border: 2px solid #f1f5f9; font-weight: 700;">
                            <option value="low">Low - Informational</option>
                            <option value="medium">Medium - Response Needed</option>
                            <option value="high">High - Serious Danger</option>
                            <option value="critical" style="color: red; font-weight: 900;">CRITICAL - LIFE AT RISK</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label
                        style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Narrative
                        Description</label>
                    <textarea name="description"
                        style="width: 100%; padding: 1rem; border-radius: 10px; border: 2px solid #f1f5f9; font-weight: 500; min-height: 200px; font-size: 0.95rem;"
                        placeholder="Please provide as much detail as possible about the incident..." required></textarea>
                </div>

                <h3 style="margin: 3rem 0 1.5rem; display: flex; align-items: center; gap: 12px; font-size: 1.25rem;">
                    <span class="material-symbols-outlined" style="color: var(--rs-secondary);">attach_file</span>
                    Supporting Evidence
                </h3>
                <div
                    style="padding: 3rem; background: var(--rs-bg); border: 2px dashed #cbd5e1; border-radius: 15px; text-align: center;">
                    <span class="material-symbols-outlined"
                        style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">photo_camera</span>
                    <p style="color: #64748b; font-weight: 700;">Upload Images or Videos</p>
                    <p style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Max size: 5MB per file</p>
                </div>
            </div>

            <!-- Right Side: Location & Submit -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <div class="rs-card">
                    <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; font-size: 1.1rem;">
                        <span class="material-symbols-outlined" style="color: var(--rs-accent);">pin_drop</span>
                        Locate Incident
                    </h3>

                    <div style="margin-bottom: 1.5rem;">
                        <label
                            style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Street
                            Address / Area</label>
                        <input type="text" name="location_address"
                            style="width: 100%; padding: 0.85rem; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600;"
                            placeholder="e.g. Near the Central Market">
                    </div>

                    <div
                        style="background: #f8fafc; height: 300px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; position: relative;">
                        <!-- Mock Map -->
                        <div style="text-align: center;">
                            <span class="material-symbols-outlined"
                                style="font-size: 3rem; color: #cbd5e1; margin-bottom: 0.5rem;">map</span>
                            <p style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">CLICK TO PINPOINT LOCATION</p>
                        </div>

                        <!-- Invisible inputs for lat/long -->
                        <input type="hidden" name="latitude" id="lat">
                        <input type="hidden" name="longitude" id="lng">
                    </div>

                    <button type="submit" class="btn-rs btn-rs-primary"
                        style="width: 100%; justify-content: center; padding: 1.25rem; font-size: 1.1rem; margin-top: 2rem; border-radius: 12px; background: var(--rs-secondary);">
                        <span class="material-symbols-outlined">send</span>
                        Transmit Report
                    </button>
                    <p style="text-align: center; font-size: 0.7rem; color: #94a3b8; font-weight: 600; margin-top: 1.5rem;">
                        Transmission is secured with end-to-end encryption.</p>
                </div>

                <div class="rs-card" style="background: #fdf2f2; border-color: #fee2e2;">
                    <div style="display: flex; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: #ef4444;">warning</span>
                        <div>
                            <div style="font-weight: 800; font-size: 0.85rem; color: #991b1b; margin-bottom: 5px;">Legal
                                Notice</div>
                            <p style="font-size: 0.75rem; color: #b91c1c; line-height: 1.4;">Filing a false report is a
                                criminal offense and leads to permanent suspension and legal action.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>