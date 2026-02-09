<?php
require_once 'config.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if (empty($slug)) {
    header("Location: index.php");
    exit();
}

$sql = "SELECT p.*, c.name as category_name 
        FROM posts p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.slug = '$slug' AND p.status = 'published'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<h1>Post tidak ditemukan</h1><a href='index.php'>Kembali ke Home</a>";
    exit();
}

$post = $result->fetch_assoc();

// Increment Views
$conn->query("UPDATE posts SET views = views + 1 WHERE id = " . $post['id']);

// Get settings for header image/ads
$sql_settings = "SELECT * FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$settings = $result_settings->fetch_assoc();

$hero_bg = !empty($post['image']) ? "uploads/" . $post['image'] : (!empty($settings['header_image']) ? "uploads/" . $settings['header_image'] : "https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1920&q=80");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($post['title']); ?> -
        <?php echo htmlspecialchars($settings['site_title']); ?>
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .post-detail-header {
            height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: flex-end;
            padding-bottom: 4rem;
        }

        .post-detail-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.8) 100%);
        }

        .post-meta {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .content-body {
            font-size: 1.1rem;
            line-height: 1.4;
            color: #374151;
        }

        .content-body p,
        .content-body br {
            margin-bottom: 0.5rem;
            display: block;
            content: "";
        }

        h1 {
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }

        .download-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .detail-content-box {
            padding: 2rem 1cm;
            /* Standard vertical padding, 1cm horizontal as requested */
        }

        @media (max-width: 640px) {
            .detail-content-box {
                padding: 1.5rem 1rem;
                /* Adjust for mobile */
            }
        }
    </style>
</head>

<body>

    <!-- Sticky Wrapper -->
    <div class="sticky-top-wrapper">
        <header>
            <div class="nav-container">
                <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 0.75rem;">
                    <?php if (!empty($settings['site_logo']) && file_exists('uploads/' . $settings['site_logo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Logo"
                            style="height: 40px; width: auto;">
                    <?php else: ?>
                        <i class="fas fa-arrow-left"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($settings['site_title']); ?></span>
                </a>
            </div>
        </header>
    </div>

    <div class="post-detail-header" style="background-image: url('<?php echo $hero_bg; ?>');">
        <!-- Image Banner Only -->
    </div>

    <div class="container" style="margin-top: -5rem; position: relative; z-index: 10;">
        <main class="glass detail-content-box bg-white rounded-xl shadow-xl">

            <!-- Title & Meta Section (Moved Here) -->
            <div class="mb-8 border-b pb-6">
                <div class="flex items-center justify-between mb-4">
                    <span
                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        <?php echo htmlspecialchars($post['category_name']); ?>
                    </span>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span><i class="far fa-calendar mr-1"></i>
                            <?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                        <span><i class="far fa-eye mr-1"></i> <?php echo $post['views']; ?></span>
                    </div>
                </div>

                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4 leading-tight">
                    <?php echo htmlspecialchars($post['title']); ?>
                </h1>
            </div>

            <!-- Media Players -->
            <?php if (!empty($post['video'])): ?>
                <div class="mb-8">
                    <video controls style="width: 100%; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        <source src="uploads/<?php echo htmlspecialchars($post['video']); ?>" type="video/mp4">
                        Browser Anda tidak mendukung pemutar video.
                    </video>
                </div>
            <?php endif; ?>

            <?php if (!empty($post['audio'])): ?>
                <div class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200 flex items-center gap-4">
                    <div class="bg-purple-600 text-white p-3 rounded-full">
                        <i class="fas fa-music fa-lg"></i>
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 class="text-sm font-bold text-gray-700 mb-1">Putar Audio</h4>
                        <audio controls style="width: 100%; height: 40px;">
                            <source src="uploads/<?php echo htmlspecialchars($post['audio']); ?>" type="audio/mpeg">
                            Browser Anda tidak mendukung pemutar audio.
                        </audio>
                    </div>
                </div>
            <?php endif; ?>

            <div class="content-body text-gray-700" style="white-space: pre-line;">
                <?php echo $post['content']; ?>
            </div>

            <?php if (!empty($post['file_attachment'])): ?>
                <div class="download-box">
                    <div>
                        <h4 class="font-bold text-blue-900">Materi Tambahan</h4>
                        <p class="text-sm text-blue-700">File terkait artikel ini tersedia untuk diunduh.</p>
                    </div>
                    <a href="uploads/<?php echo htmlspecialchars($post['file_attachment']); ?>" class="btn btn-primary"
                        download>
                        <i class="fas fa-download"></i> Unduh File
                    </a>
                </div>
            <?php endif; ?>

            <?php
            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $share_text = urlencode($post['title']);
            $share_url = urlencode($current_url);
            ?>
            <div class="mt-8 border-t pt-8">
                <h3 class="font-bold mb-4">Bagikan :</h3>
                <div class="flex gap-4">
                    <!-- Facebook -->
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank"
                        class="text-blue-600 hover:text-blue-800 transition transform hover:scale-110">
                        <i class="fab fa-facebook fa-2x"></i>
                    </a>

                    <!-- WhatsApp -->
                    <a href="https://api.whatsapp.com/send?text=<?php echo $share_text . '%20' . $share_url; ?>"
                        target="_blank"
                        class="text-green-500 hover:text-green-700 transition transform hover:scale-110">
                        <i class="fab fa-whatsapp fa-2x"></i>
                    </a>

                    <!-- Instagram (Copy Link focus) -->
                    <button onclick="copyToClipboard()"
                        class="text-pink-600 hover:text-pink-800 transition transform hover:scale-110 border-none bg-transparent cursor-pointer p-0"
                        title="Salin Link untuk Instagram">
                        <i class="fab fa-instagram fa-2x"></i>
                    </button>

                    <!-- Copy Link Button -->
                    <button onclick="copyToClipboard()"
                        class="text-gray-500 hover:text-gray-700 transition transform hover:scale-110 border-none bg-transparent cursor-pointer p-0"
                        title="Salin Link">
                        <i class="fas fa-link fa-2x"></i>
                    </button>
                </div>
            </div>

            <script>
                function copyToClipboard() {
                    var dummy = document.createElement('input'),
                        text = window.location.href;
                    document.body.appendChild(dummy);
                    dummy.value = text;
                    dummy.select();
                    document.execCommand('copy');
                    document.body.removeChild(dummy);
                    alert('Link berhasil disalin! Silakan bagikan ke Instagram atau media sosial lainnya.');
                }
            </script>
        </main>

        <aside class="sidebar mt-12 md:mt-0">
            <div class="widget">
                <h3 class="font-bold mb-4 border-b pb-2">Iklan Sponsor</h3>
                <div class="ad-space">
                    <?php echo $settings['ad_sidebar'] ?? 'Space Iklan Tersedia'; ?>
                </div>
            </div>

            <div class="widget">
                <h3 class="font-bold mb-4 border-b pb-2">Iklan Sponsor</h3>
                <div class="ad-space" style="border: none; background: transparent;">
                    <?php if (!empty($settings['ad_sidebar_2_image'])): ?>
                        <a href="<?php echo $settings['ad_sidebar_2_link'] ? $settings['ad_sidebar_2_link'] : '#'; ?>"
                            target="_blank">
                            <img src="uploads/<?php echo $settings['ad_sidebar_2_image']; ?>" alt="Iklan Sponsor"
                                style="width: 100%; height: auto; border-radius: 8px;">
                        </a>
                    <?php else: ?>
                        <div class="ad-space">
                            <?php echo $settings['ad_sidebar_2'] ?? 'Space Iklan Tersedia'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>

    <footer>
        <div class="footer-content">
            <div class="mb-4" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;">
                <!-- Footer Ad 1 -->
                <div style="flex: 0 1 728px; min-width: 300px;">
                    <?php if (!empty($settings['ad_footer_image'])): ?>
                        <a href="<?php echo $settings['ad_footer_link'] ? $settings['ad_footer_link'] : '#'; ?>"
                            target="_blank">
                            <img src="uploads/<?php echo $settings['ad_footer_image']; ?>" alt="Iklan Footer 1"
                                style="width: 100%; height: auto; max-height: 90px; border-radius: 4px;">
                        </a>
                    <?php else: ?>
                        <?php echo $settings['ad_footer'] ?? '<div class="ad-space" style="height: 90px; width: 100%;">Space Iklan Footer 1</div>'; ?>
                    <?php endif; ?>
                </div>

                <!-- Footer Ad 2 -->
                <div style="flex: 0 1 728px; min-width: 300px;">
                    <?php if (!empty($settings['ad_footer_2_image'])): ?>
                        <a href="<?php echo $settings['ad_footer_2_link'] ? $settings['ad_footer_2_link'] : '#'; ?>"
                            target="_blank">
                            <img src="uploads/<?php echo $settings['ad_footer_2_image']; ?>" alt="Iklan Footer 2"
                                style="width: 100%; height: auto; max-height: 90px; border-radius: 4px;">
                        </a>
                    <?php else: ?>
                        <?php echo isset($settings['ad_footer_2']) ? $settings['ad_footer_2'] : '<div class="ad-space" style="height: 90px; width: 100%;">Space Iklan Footer 2</div>'; ?>
                    <?php endif; ?>
                </div>
            </div>
            <p>&copy;
                <?php echo date('Y'); ?>
                <?php echo htmlspecialchars($settings['site_title']); ?>. All rights reserved.
            </p>
        </div>
    </footer>

</body>

</html>
