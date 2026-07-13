<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Check if user is admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php', 'Access denied. Admin privileges required.', 'error');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Handle slide upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'upload_slide') {
        $title = sanitizeInput($_POST['title']);
        $subtitle = sanitizeInput($_POST['subtitle']);
        $description = sanitizeInput($_POST['description']);
        $button_text = sanitizeInput($_POST['button_text']);
        $button_link = sanitizeInput($_POST['button_link']);
        $display_order = intval($_POST['display_order']);
        
        // Handle file upload
        if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_types)) {
                $new_filename = 'slide_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $target_file)) {
                    // Resize and optimize image
                    optimizeImage($target_file, $file_extension);
                    
                    $stmt = $db->prepare("
                        INSERT INTO hero_slides (image_path, title, subtitle, description, button_text, button_link, display_order, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute(['assets/images/' . $new_filename, $title, $subtitle, $description, $button_text, $button_link, $display_order]);
                    $message = "Slide uploaded successfully!";
                    logAudit($_SESSION['user_id'], 'SLIDE_UPLOADED', 'hero_slides', $db->lastInsertId());
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
            }
        } else {
            $error = "Please select an image to upload.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_slide') {
        $slide_id = intval($_POST['slide_id']);
        $title = sanitizeInput($_POST['title']);
        $subtitle = sanitizeInput($_POST['subtitle']);
        $description = sanitizeInput($_POST['description']);
        $button_text = sanitizeInput($_POST['button_text']);
        $button_link = sanitizeInput($_POST['button_link']);
        $display_order = intval($_POST['display_order']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $db->prepare("
            UPDATE hero_slides 
            SET title = ?, subtitle = ?, description = ?, button_text = ?, button_link = ?, display_order = ?, is_active = ?
            WHERE id = ?
        ");
        if ($stmt->execute([$title, $subtitle, $description, $button_text, $button_link, $display_order, $is_active, $slide_id])) {
            $message = "Slide updated successfully!";
            logAudit($_SESSION['user_id'], 'SLIDE_UPDATED', 'hero_slides', $slide_id);
        } else {
            $error = "Failed to update slide.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete_slide') {
        $slide_id = intval($_POST['slide_id']);
        
        // Get image path to delete file
        $stmt = $db->prepare("SELECT image_path FROM hero_slides WHERE id = ?");
        $stmt->execute([$slide_id]);
        $slide = $stmt->fetch();
        
        if ($slide) {
            $image_path = __DIR__ . '/../' . $slide['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM hero_slides WHERE id = ?");
        if ($stmt->execute([$slide_id])) {
            $message = "Slide deleted successfully!";
            logAudit($_SESSION['user_id'], 'SLIDE_DELETED', 'hero_slides', $slide_id);
        } else {
            $error = "Failed to delete slide.";
        }
    }
}

// Get all slides
$slides = $db->query("SELECT * FROM hero_slides ORDER BY display_order ASC")->fetchAll();

// Image optimization function
function optimizeImage($filepath, $extension) {
    $max_width = 1920;
    $quality = 80;
    
    list($width, $height) = getimagesize($filepath);
    $ratio = $height / $width;
    
    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = $max_width * $ratio;
    } else {
        $new_width = $width;
        $new_height = $height;
    }
    
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($filepath);
            imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($new_image, $filepath, $quality);
            break;
        case 'png':
            $source = imagecreatefrompng($filepath);
            imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagepng($new_image, $filepath, 8);
            break;
        case 'webp':
            $source = imagecreatefromwebp($filepath);
            imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagewebp($new_image, $filepath, $quality);
            break;
    }
    imagedestroy($new_image);
    if (isset($source)) imagedestroy($source);
}

include '../includes/dashboard-header.php';
include '../includes/dashboard-sidebar.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Hero Slideshow Manager</h1>
        <p>Manage the homepage hero slideshow images and content</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Upload New Slide -->
    <div class="recent-section">
        <h2>Upload New Slide</h2>
        <form method="POST" action="" enctype="multipart/form-data" class="slide-form">
            <input type="hidden" name="action" value="upload_slide">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Slide Image *</label>
                    <input type="file" name="slide_image" accept="image/*" required>
                    <small>Recommended size: 1920x800px. Max 5MB</small>
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="<?php echo count($slides) + 1; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Main heading text">
            </div>
            
            <div class="form-group">
                <label>Subtitle</label>
                <input type="text" name="subtitle" placeholder="Subtitle text">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Description text"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Button Text</label>
                    <input type="text" name="button_text" placeholder="e.g., Report a Case">
                </div>
                
                <div class="form-group">
                    <label>Button Link</label>
                    <input type="text" name="button_link" placeholder="e.g., report/report-form.php">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Upload Slide</button>
        </form>
    </div>
    
    <!-- Existing Slides -->
    <div class="recent-section">
        <h2>Existing Slides (<?php echo count($slides); ?>)</h2>
        <div class="slides-grid">
            <?php foreach ($slides as $slide): ?>
            <div class="slide-card">
                <div class="slide-preview">
                    <img src="../<?php echo $slide['image_path']; ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
                    <div class="slide-order">Order: <?php echo $slide['display_order']; ?></div>
                </div>
                <div class="slide-info">
                    <h3><?php echo htmlspecialchars($slide['title'] ?: 'Untitled'); ?></h3>
                    <p class="slide-status <?php echo $slide['is_active'] ? 'active' : 'inactive'; ?>">
                        <?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?>
                    </p>
                </div>
                <div class="slide-actions">
                    <button class="btn btn-sm btn-primary" onclick="editSlide(<?php echo htmlspecialchars(json_encode($slide)); ?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Delete this slide?')">
                        <input type="hidden" name="action" value="delete_slide">
                        <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Edit Slide Modal -->
<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; overflow:auto;">
    <div style="position:relative; max-width:600px; margin:50px auto; background:white; border-radius:10px; padding:30px;">
        <h2>Edit Slide</h2>
        <form method="POST" action="" id="editForm">
            <input type="hidden" name="action" value="update_slide">
            <input type="hidden" name="slide_id" id="edit_slide_id">
            
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit_title" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Subtitle</label>
                <input type="text" name="subtitle" id="edit_subtitle" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description" rows="3" class="form-control"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Button Text</label>
                    <input type="text" name="button_text" id="edit_button_text" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Button Link</label>
                    <input type="text" name="button_link" id="edit_button_link" class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="edit_display_order" class="form-control">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        Active
                    </label>
                </div>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.slides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.slide-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    transition: transform 0.2s;
}

.slide-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.slide-preview {
    position: relative;
    height: 150px;
    overflow: hidden;
}

.slide-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-order {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 11px;
}

.slide-info {
    padding: 15px;
}

.slide-info h3 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.slide-status {
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-block;
}

.slide-status.active {
    background: #e8f5e9;
    color: #388e3c;
}

.slide-status.inactive {
    background: #ffebee;
    color: #d32f2f;
}

.slide-actions {
    padding: 10px 15px 15px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
}

.slide-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .slides-grid {
        grid-template-columns: 1fr;
    }
    
    .slide-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function editSlide(slide) {
    document.getElementById('edit_slide_id').value = slide.id;
    document.getElementById('edit_title').value = slide.title || '';
    document.getElementById('edit_subtitle').value = slide.subtitle || '';
    document.getElementById('edit_description').value = slide.description || '';
    document.getElementById('edit_button_text').value = slide.button_text || '';
    document.getElementById('edit_button_link').value = slide.button_link || '';
    document.getElementById('edit_display_order').value = slide.display_order;
    document.getElementById('edit_is_active').checked = slide.is_active == 1;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include '../includes/dashboard-footer.php'; ?>