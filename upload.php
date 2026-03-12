<?php
// This file handles the upload logic when included by index.php
if(!isset($_SESSION['user_id'])) {
    exit("Unauthorized access");
}

$upload_error = '';
$upload_success = '';

// Process upload if form submitted
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $topic = trim(htmlspecialchars($_POST['topic']));
    $description = trim(htmlspecialchars($_POST['description']));
    $file = $_FILES['file'];
    
    // Validation settings
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
    $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Check required fields
    if(empty($topic)) {
        $upload_error = "Topic is required";
    } elseif(empty($file['name'])) {
        $upload_error = "Please select a file to upload";
    } elseif($file['size'] > $max_size) {
        $upload_error = "File size must be less than 5MB";
    } elseif($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = "Upload error occurred. Please try again.";
    } else {
        // Get file extension
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate extension
        if(!in_array($file_extension, $allowed_extensions)) {
            $upload_error = "Invalid file type. Allowed: JPG, PNG, PDF, DOCX";
        } else {
            // Create uploads directory if it doesn't exist
            if(!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Generate unique filename to prevent overwriting
            $new_filename = uniqid() . '_' . basename($file['name']);
            $upload_path = 'uploads/' . $new_filename;
            
            // Move uploaded file
            if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Save to database using prepared statement
                $stmt = $pdo->prepare("INSERT INTO notes (topic, description, filename, user_id) VALUES (?, ?, ?, ?)");
                
                if($stmt->execute([$topic, $description, $new_filename, $_SESSION['user_id']])) {
                    $upload_success = "Note uploaded successfully!";
                    // Redirect to avoid form resubmission
                    header("Location: index.php?uploaded=1");
                    exit();
                } else {
                    // Remove file if database insert fails
                    unlink($upload_path);
                    $upload_error = "Failed to save note information";
                }
            } else {
                $upload_error = "Failed to move uploaded file";
            }
        }
    }
}
?>