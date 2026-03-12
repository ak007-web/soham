<?php
session_start();
require_once 'db.php';

// Check authentication - redirect if not logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include upload handling logic
require_once 'upload.php';

// Fetch all notes with user information using JOIN
try {
    $stmt = $pdo->query("SELECT n.*, u.name as username 
                        FROM notes n 
                        JOIN users u ON n.user_id = u.id 
                        ORDER BY n.created_at DESC");
    $notes = $stmt->fetchAll();
} catch(PDOException $e) {
    $notes = [];
    $fetch_error = "Failed to load notes";
}

// Get file icon based on extension
function getFileIcon($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch($ext) {
        case 'pdf': return 'file-text';
        case 'docx': return 'file-type';
        case 'jpg':
        case 'jpeg':
        case 'png': return 'image';
        default: return 'file';
    }
}

function getFileColor($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch($ext) {
        case 'pdf': return 'text-red-500 bg-red-50';
        case 'docx': return 'text-blue-500 bg-blue-50';
        case 'jpg':
        case 'jpeg':
        case 'png': return 'text-green-500 bg-green-50';
        default: return 'text-gray-500 bg-gray-50';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Student Notes Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-indigo-600 p-2 rounded-lg">
                        <i data-lucide="book-open" class="text-white w-6 h-6"></i>
                    </div>
                    <span class="font-bold text-xl text-gray-800">Student Notes Hub</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600 hidden sm:block">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                    <a href="logout.php" class="flex items-center gap-2 text-red-600 hover:text-red-800 font-medium transition">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                        <span class="hidden sm:inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Upload Form Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                    <div class="flex items-center gap-2 mb-6">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-indigo-600"></i>
                        <h2 class="text-xl font-bold text-gray-800">Upload Notes</h2>
                    </div>

                    <?php if(isset($_GET['uploaded'])): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
                            Note uploaded successfully!
                        </div>
                    <?php endif; ?>

                    <?php if($upload_error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
                            <?php echo $upload_error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Topic *</label>
                            <input type="text" name="topic" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition"
                                placeholder="e.g., Calculus Chapter 1">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition resize-none"
                                placeholder="Brief description of the notes..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">File *</label>
                            <div class="relative">
                                <input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf,.docx"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Allowed: JPG, PNG, PDF, DOCX (Max 5MB)</p>
                        </div>

                        <button type="submit" 
                            class="w-full bg-indigo-600 text-white py-2.5 rounded-lg hover:bg-indigo-700 transition duration-200 font-medium shadow-md flex items-center justify-center gap-2">
                            <i data-lucide="upload" class="w-5 h-5"></i>
                            Upload Note
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notes Display Section -->
            <div class="lg:col-span-2">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">All Notes</h2>
                    <span class="text-gray-500 text-sm"><?php echo count($notes); ?> notes available</span>
                </div>

                <?php if(empty($notes)): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="inbox" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-800 mb-2">No notes yet</h3>
                        <p class="text-gray-500">Be the first to upload study notes!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($notes as $note): ?>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition duration-200">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="<?php echo getFileColor($note['filename']); ?> p-2 rounded-lg">
                                            <i data-lucide="<?php echo getFileIcon($note['filename']); ?>" class="w-6 h-6"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($note['topic']); ?></h3>
                                            <p class="text-xs text-gray-500">
                                                <?php echo strtoupper(pathinfo($note['filename'], PATHINFO_EXTENSION)); ?> • 
                                                <?php echo date('M d, Y', strtotime($note['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars($note['description']) ?: 'No description provided.'; ?>
                                </p>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center gap-2 text-sm text-gray-500">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                        <span><?php echo htmlspecialchars($note['username']); ?></span>
                                    </div>
                                    
                                    <a href="uploads/<?php echo $note['filename']; ?>" download 
                                        class="flex items-center gap-2 bg-indigo-50 text-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-100 transition text-sm font-medium">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>