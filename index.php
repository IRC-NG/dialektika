<?php
require_once 'config.php';

// Fetch Settings
$sql_settings = "SELECT * FROM settings LIMIT 1";
$result_settings = $conn->query($sql_settings);
$settings = $result_settings->fetch_assoc();

// Default values if settings are missing
$site_title = $settings['site_title'] ?? 'Portal Berita';
$header_image = $settings['header_image'] ?? 'default.jpg';
$hero_bg = !empty($settings['header_image']) ? "uploads/" . $settings['header_image'] : "https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1920&q=80"; // Fallback to Unsplash URL if local not found
$running_text = $settings['running_text'] ?? "Selamat Datang di Portal Berita!";

// Helper function to fetch posts by category
function getPosts($conn, $slug, $limit = 5)
{
    $slug = sanitize($slug);
    $sql = "SELECT p.*, c.name as category_name 
            FROM posts p 
            JOIN categories c ON p.category_id = c.id 
            WHERE c.slug = '$slug' AND p.status = 'published' 
            ORDER BY p.created_at DESC LIMIT $limit";
    return $conn->query($sql);
}

$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : null;

$news_posts = (!$category_filter || $category_filter == 'berita') ? getPosts($conn, 'berita', 4) : null;
$opinion_posts = (!$category_filter || $category_filter == 'opini') ? getPosts($conn, 'opini', 4) : null;
$knowledge_posts = ($category_filter == 'pengetahuan') ? getPosts($conn, 'pengetahuan', 4) : null;
$research_posts = ($category_filter == 'riset') ? getPosts($conn, 'riset', 4) : null;
$book_posts = ($category_filter == 'buku') ? getPosts($conn, 'buku', 4) : null;
$music_posts = ($category_filter == 'musik') ? getPosts($conn, 'musik', 4) : null;
$video_posts = ($category_filter == 'video') ? getPosts($conn, 'video', 4) : null;
$simulation_posts = ($category_filter == 'simulasi') ? $conn->query("SELECT * FROM simulations WHERE status = 'published' ORDER BY release_date DESC") : null;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($site_title); ?>
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/library.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <!-- Sticky Wrapper -->
    <div class="sticky-top-wrapper">
        <!-- Notification Bar / Running Text -->
        <div class="running-text-wrapper"
            style="background: #111827; color: white; overflow: hidden; white-space: nowrap;">
            <div class="running-text-content"
                style="display: inline-block; padding-left: 100%; animation: marquee 25s linear infinite;">
                <?php echo htmlspecialchars($running_text); ?>
            </div>
        </div>

        <header>
            <div class="nav-container">
                <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 0.75rem;">
                    <?php if (!empty($settings['site_logo']) && file_exists('uploads/' . $settings['site_logo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Logo"
                            style="height: 40px; width: auto;">
                    <?php else: ?>
                        <i class="fas fa-newspaper"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($site_title); ?></span>
                </a>
                <nav>
                    <a href="index.php">Beranda</a>
                    <a href="index.php?category=berita">Berita</a>
                    <a href="index.php?category=opini">Opini</a>
                    <a href="index.php?category=pengetahuan">Pengetahuan</a>
                    <a href="index.php?category=riset">Riset</a>
                    <a href="index.php?category=buku">Buku</a>
                    <a href="index.php?category=musik">Musik</a>
                    <a href="index.php?category=video">Video</a>
                    <a href="index.php?category=simulasi">Simulasi</a>
                    <a href="login.php" class="btn btn-sm"
                        style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem; margin-left: 1rem;">Login
                    </a>
                </nav>
            </div>
        </header>
    </div>

    <!-- Hero Section -->
    <div class="hero" style="background-image: url('<?php echo htmlspecialchars($hero_bg); ?>');">
        <div class="hero-content fade-in">
            <h1>
                <?php echo htmlspecialchars($settings['tagline'] ?? 'Informasi Terpercaya'); ?>
            </h1>
            <p>
                <?php echo htmlspecialchars($settings['about_text'] ?? ''); ?>
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">

        <!-- Left Column: Content -->
        <main>

            <!-- Opini Section -->
            <?php if ($opinion_posts): ?>
                <section id="opini" class="mb-12">
                    <h2 class="section-title">Opini & Pemikiran</h2>
                    <div class="post-grid">
                        <?php if ($opinion_posts->num_rows > 0): ?>
                            <?php while ($post = $opinion_posts->fetch_assoc()): ?>
                                <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="post-card glass">
                                    <?php if ($post['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                            <i class="fas fa-comment-dots fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category" style="color: var(--secondary-color);">Opini</span>
                                        <h3 class="post-title">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </div>
                                        <span class="read-more mt-4">Baca Selengkapnya <i class="fas fa-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada opini.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Berita Section -->
            <?php if ($news_posts): ?>
                <section id="berita" class="mt-12">
                    <h2 class="section-title">Berita Terkini</h2>
                    <div class="post-grid">
                        <?php if ($news_posts->num_rows > 0): ?>
                            <?php while ($post = $news_posts->fetch_assoc()): ?>
                                <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="post-card glass">
                                    <?php if ($post['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category">Berita</span>
                                        <h3 class="post-title">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </div>
                                        <span class="read-more mt-4">Baca Selengkapnya <i class="fas fa-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada berita.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Pengetahuan Section -->
            <?php if ($knowledge_posts): ?>
                <section id="pengetahuan" class="mt-12">
                    <h2 class="section-title">Gudang Pengetahuan</h2>
                    <div class="post-grid">
                        <?php if ($knowledge_posts->num_rows > 0): ?>
                            <?php while ($post = $knowledge_posts->fetch_assoc()): ?>
                                <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="post-card glass">
                                    <?php if ($post['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                            <i class="fas fa-book-open fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category" style="color: var(--accent-color);">Pengetahuan</span>
                                        <h3 class="post-title">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </div>
                                        <?php if ($post['file_attachment']): ?>
                                            <div class="mt-2 text-xs text-blue-600">
                                                <i class="fas fa-paperclip"></i> Ada Lampiran
                                            </div>
                                        <?php endif; ?>
                                        <span class="read-more mt-4">Baca Selengkapnya <i class="fas fa-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada materi pengetahuan.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Riset Section -->
            <?php if ($research_posts): ?>
                <section id="riset" class="mt-12">
                    <h2 class="section-title">Hasil Riset</h2>
                    <div class="post-grid">
                        <?php if ($research_posts->num_rows > 0): ?>
                            <?php while ($post = $research_posts->fetch_assoc()): ?>
                                <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="post-card glass">
                                    <?php if ($post['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                            <i class="fas fa-microscope fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category" style="color: #8b5cf6;">Riset</span>
                                        <h3 class="post-title">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                        </div>
                                        <span class="read-more mt-4">Baca Selengkapnya <i class="fas fa-arrow-right"></i></span>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada riset.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Buku Section -->
            <?php if ($book_posts): ?>
                <section id="buku" class="mt-12">
                    <h2 class="section-title">Koleksi Buku</h2>

                    <?php if ($book_posts->num_rows > 0): ?>
                        <div class="library-section">
                            <div class="library-shelf">
                                <?php while ($post = $book_posts->fetch_assoc()): ?>
                                    <div class="book-item">
                                        <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="book-cover-container">
                                            <div class="book-cover">
                                                <?php if ($post['image']): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                                        alt="<?php echo htmlspecialchars($post['title']); ?>">
                                                <?php else: ?>
                                                    <div class="no-cover">
                                                        <i class="fas fa-book-reader"></i>
                                                        <span
                                                            style="font-size: 0.8rem; line-height: 1.2;"><?php echo htmlspecialchars($post['title']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                <!-- Shine effect overlays -->
                                            </div>
                                        </a>
                                        <div class="book-info">
                                            <div class="book-title">
                                                <a href="detail.php?slug=<?php echo $post['slug']; ?>"
                                                    style="text-decoration:none; color:inherit;">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </a>
                                            </div>
                                            <div class="book-meta">
                                                <?php
                                                $meta_parts = [];
                                                if (!empty($post['publisher'])) {
                                                    $meta_parts[] = "<i class='fas fa-building'></i> " . htmlspecialchars($post['publisher']);
                                                }
                                                if (!empty($post['release_date'])) {
                                                    $meta_parts[] = "<i class='fas fa-calendar-alt'></i> " . date('Y', strtotime($post['release_date']));
                                                }
                                                echo implode(" &bull; ", $meta_parts);
                                                ?>
                                            </div>
                                            <?php if (!empty($post['price']) && $post['price'] > 0): ?>
                                                <div class="book-price" style="color: #10b981; font-weight: bold; margin-bottom: 0.5rem;">
                                                    <i class="fas fa-tag"></i> Rp <?php echo number_format($post['price'], 0, ',', '.'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="book-narration">
                                                <?php echo substr(strip_tags($post['content']), 0, 200) . '...'; ?>
                                            </div>

                                            <div class="book-actions">
                                                <a href="detail.php?slug=<?php echo $post['slug']; ?>"
                                                    class="btn-book btn-read">Baca Resensi</a>
                                                <?php if (!empty($post['purchase_link'])): ?>
                                                    <a href="<?php echo htmlspecialchars($post['purchase_link']); ?>"
                                                        target="_blank" class="btn-book" style="background: #f59e0b; color: white;">
                                                        <i class="fas fa-shopping-cart"></i> Beli
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($post['file_attachment']): ?>
                                                    <a href="uploads/<?php echo htmlspecialchars($post['file_attachment']); ?>"
                                                        target="_blank" class="btn-book btn-dl"><i class="fas fa-download"></i>
                                                        PDF</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Belum ada koleksi buku di perpustakaan ini.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <!-- Musik Section -->
            <?php if ($music_posts): ?>
                <section id="musik" class="mt-12">
                    <h2 class="section-title">Koleksi Musik & Audio</h2>
                    <div class="post-grid">
                        <?php if ($music_posts->num_rows > 0): ?>
                            <?php while ($post = $music_posts->fetch_assoc()): ?>
                                <div class="post-card glass">
                                    <!-- Banner for Music -->
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                            alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: linear-gradient(135deg, #ec4899, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white;">
                                            <i class="fas fa-music fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category" style="color: #ec4899;">Musik</span>
                                        <h3 class="post-title">
                                            <a href="detail.php?slug=<?php echo $post['slug']; ?>"
                                                style="text-decoration:none; color:inherit;">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h3>
                                        <?php if ($post['audio']): ?>
                                            <div class="mt-3">
                                                <audio controls style="width: 100%; height: 35px;">
                                                    <source src="uploads/<?php echo htmlspecialchars($post['audio']); ?>"
                                                        type="audio/mpeg">
                                                    Browser Anda tidak mendukung elemen audio.
                                                </audio>
                                            </div>
                                        <?php endif; ?>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 80) . '...'; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada musik.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Video Section -->
            <?php if ($video_posts): ?>
                <section id="video" class="mt-12">
                    <h2 class="section-title">Koleksi Video</h2>
                    <div class="post-grid">
                        <?php if ($video_posts->num_rows > 0): ?>
                            <?php while ($post = $video_posts->fetch_assoc()): ?>
                                <div class="post-card glass">
                                    <?php if (!empty($post['image'])): ?>
                                        <div class="post-image-wrapper" style="position: relative;">
                                            <img src="uploads/<?php echo htmlspecialchars($post['image']); ?>"
                                                alt="<?php echo htmlspecialchars($post['title']); ?>" class="post-image">
                                            <div class="video-overlay"
                                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.2);">
                                                <i class="fas fa-play-circle fa-3x" style="color: white; opacity: 0.8;"></i>
                                            </div>
                                        </div>
                                    <?php elseif ($post['video']): ?>
                                        <div class="post-image" style="height: auto; background: black;">
                                            <video style="width: 100%; display: block;" poster="">
                                                <source src="uploads/<?php echo htmlspecialchars($post['video']); ?>" type="video/mp4">
                                            </video>
                                            <div class="video-overlay"
                                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-play-circle fa-3x" style="color: white; opacity: 0.8;"></i>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="post-image"
                                            style="background: #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                                            <i class="fas fa-video fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-content">
                                        <span class="post-category" style="color: #ef4444;">Video</span>
                                        <h3 class="post-title">
                                            <a href="detail.php?slug=<?php echo $post['slug']; ?>"
                                                style="text-decoration:none; color:inherit;">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </h3>
                                        <div class="post-excerpt text-sm text-gray-500 mt-2">
                                            <?php echo substr(strip_tags($post['content']), 0, 120) . '...'; ?>
                                        </div>
                                        <a href="detail.php?slug=<?php echo $post['slug']; ?>" class="read-more mt-4"
                                            style="color: #ef4444;">
                                            Tonton Video <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada video.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Simulasi Section -->
            <?php if ($simulation_posts): ?>
                <section id="simulasi" class="mt-12">
                    <h2 class="section-title">Daftar Simulasi</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($simulation_posts->num_rows > 0): ?>
                            <?php while ($sim = $simulation_posts->fetch_assoc()): ?>
                                <div class="post-card glass" style="display: flex; flex-direction: column; padding: 1.5rem 1cm;">
                                    <div class="flex justify-between items-start mb-4"
                                        style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold"
                                            style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($sim['category']); ?>
                                        </span>
                                        <span class="text-xs text-gray-500" style="font-size: 0.75rem; color: #6b7280;">
                                            Rilis: <?php echo date('d M Y', strtotime($sim['release_date'])); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-xl font-bold mb-2"
                                        style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem; color: #111827;">
                                        <?php echo htmlspecialchars($sim['name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-4"
                                        style="font-size: 0.875rem; color: #4b5563; margin-bottom: 1rem; flex-grow: 1;">
                                        <?php echo htmlspecialchars($sim['description']); ?>
                                    </p>
                                    <div class="flex justify-end" style="display: flex; justify-content: flex-end;">
                                        <a href="<?php echo htmlspecialchars($sim['url']); ?>" target="_blank" class="btn btn-sm"
                                            style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.875rem; transition: background 0.3s;"
                                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Buka
                                            Simulator <i class="fas fa-play ml-1"></i></a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Belum ada simulasi.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

        </main>

        <!-- Right Column: Sidebar (Ads) -->
        <aside class="sidebar">
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

            <div class="widget">
                <h3 class="font-bold mb-4 border-b pb-2">Profil Redaksional</h3>
                <p class="text-sm text-gray-600">
                    Portal DIALEKTIKA ini adalah sarana berbagi informasi seputar pengetahuan, berita,
                    hobi, dan opini, untuk mendorong diskusi
                    yang
                    sehat, serta menumbuhkan sikap kritis dan semangat belajar berkelanjutan bagi pembaca dari beragam
                    latar belakang.
                </p>
            </div>

            <div class="widget">
                <h3 class="font-bold mb-4 border-b pb-2">Kontak</h3>
                <ul style="list-style: none; padding: 0; margin-top: 10px;">
                    <?php if (!empty($settings['contact_email'])): ?>
                        <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <i class="fas fa-envelope" style="color: #ef4444; width: 20px; text-align: center;"></i>
                            <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>"
                                style="text-decoration: none; color: inherit; transition: color 0.2s;"
                                onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='inherit'">
                                <?php echo htmlspecialchars($settings['contact_email']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_phone'])): ?>
                        <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <i class="fab fa-whatsapp"
                                style="color: #22c55e; width: 20px; text-align: center; font-size: 1.2em;"></i>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['contact_phone']); ?>"
                                target="_blank" style="text-decoration: none; color: inherit; transition: color 0.2s;"
                                onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='inherit'">
                                <?php echo htmlspecialchars($settings['contact_phone']); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_instagram'])): ?>
                        <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <i class="fab fa-instagram"
                                style="color: #ec4899; width: 20px; text-align: center; font-size: 1.2em;"></i>
                            <a href="<?php echo htmlspecialchars($settings['contact_instagram']); ?>" target="_blank"
                                style="text-decoration: none; color: inherit; transition: color 0.2s;"
                                onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='inherit'">
                                Instagram
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_facebook'])): ?>
                        <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <i class="fab fa-facebook"
                                style="color: #2563eb; width: 20px; text-align: center; font-size: 1.2em;"></i>
                            <a href="<?php echo htmlspecialchars($settings['contact_facebook']); ?>" target="_blank"
                                style="text-decoration: none; color: inherit; transition: color 0.2s;"
                                onmouseover="this.style.color='#2563eb'" onmouseout="this.style.color='inherit'">
                                Facebook
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
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
                <?php echo htmlspecialchars($site_title); ?>. All rights reserved.
            </p>
        </div>
    </footer>

</body>

</html>
