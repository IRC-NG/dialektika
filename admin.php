<?php
session_start();
require_once 'config.php';

// Check Auth
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ensure 'Buku' category exists
$check_buku = $conn->query("SELECT id FROM categories WHERE slug='buku'");
if ($check_buku->num_rows == 0) {
    if ($conn->query("INSERT INTO categories (name, slug) VALUES ('Buku', 'buku')")) {
        // Success
    }
}

// Ensure 'publisher', 'purchase_link', 'price' columns exist in posts
$check_cols = $conn->query("SHOW COLUMNS FROM posts LIKE 'publisher'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE posts ADD COLUMN publisher VARCHAR(100) DEFAULT NULL");
    $conn->query("ALTER TABLE posts ADD COLUMN purchase_link VARCHAR(255) DEFAULT NULL");
    $conn->query("ALTER TABLE posts ADD COLUMN price DECIMAL(15,2) DEFAULT NULL");
}

// Ensure 'status' column exists in simulations
$check_sim_status = $conn->query("SHOW COLUMNS FROM simulations LIKE 'status'");
if ($check_sim_status->num_rows == 0) {
    $conn->query("ALTER TABLE simulations ADD COLUMN status ENUM('published', 'draft') DEFAULT 'published'");
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$message = '';

// Handle Logout
if ($page == 'logout') {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_settings'])) {
        $site_title = sanitize($_POST['site_title']);
        $tagline = sanitize($_POST['tagline']);
        $running_text = sanitize($_POST['running_text']);
        $ad_sidebar = $_POST['ad_sidebar']; // Allow HTML for ads
        $ad_sidebar_2 = $_POST['ad_sidebar_2']; // Allow HTML for ads
        $ad_sidebar_2 = $_POST['ad_sidebar_2']; // Allow HTML for ads
        $ad_footer = $_POST['ad_footer']; // Allow HTML
        $about_text = sanitize($_POST['about_text']);

        $contact_email = sanitize($_POST['contact_email']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $contact_instagram = sanitize($_POST['contact_instagram']);
        $contact_facebook = sanitize($_POST['contact_facebook']);

        $ad_sidebar_2_link = sanitize($_POST['ad_sidebar_2_link'] ?? '');
        $ad_footer_link = sanitize($_POST['ad_footer_link'] ?? '');
        $ad_footer_2 = $_POST['ad_footer_2'] ?? '';
        $ad_footer_2_link = sanitize($_POST['ad_footer_2_link'] ?? '');

        $img_ads_sql = "";

        if (!empty($_FILES['ad_sidebar_2_image']['name'])) {
            $up_ad2 = uploadFile($_FILES['ad_sidebar_2_image']);
            if ($up_ad2)
                $img_ads_sql .= ", ad_sidebar_2_image='$up_ad2'";
        }

        if (!empty($_FILES['ad_footer_image']['name'])) {
            $up_ad_foot = uploadFile($_FILES['ad_footer_image']);
            if ($up_ad_foot)
                $img_ads_sql .= ", ad_footer_image='$up_ad_foot'";
        }

        if (!empty($_FILES['ad_footer_2_image']['name'])) {
            $up_ad_foot2 = uploadFile($_FILES['ad_footer_2_image']);
            if ($up_ad_foot2)
                $img_ads_sql .= ", ad_footer_2_image='$up_ad_foot2'";
        }

        $header_image_sql = "";
        $header_image_sql = "";

        if (!empty($_FILES['header_image']['name'])) {
            $upload = uploadFile($_FILES['header_image']);
            if ($upload) {
                $header_image_sql = ", header_image='$upload'";
            }
        }

        $site_logo_sql = "";
        if (!empty($_FILES['site_logo']['name'])) {
            $upload_logo = uploadFile($_FILES['site_logo']);
            if ($upload_logo) {
                $site_logo_sql = ", site_logo='$upload_logo'";
            }
        }

        $sql = "UPDATE settings SET site_title='$site_title', tagline='$tagline', running_text='$running_text', ad_sidebar='$ad_sidebar', ad_sidebar_2='$ad_sidebar_2', ad_footer='$ad_footer', ad_footer_2='$ad_footer_2', about_text='$about_text', contact_email='$contact_email', contact_phone='$contact_phone', contact_instagram='$contact_instagram', contact_facebook='$contact_facebook', ad_sidebar_2_link='$ad_sidebar_2_link', ad_footer_link='$ad_footer_link', ad_footer_2_link='$ad_footer_2_link' $img_ads_sql $header_image_sql $site_logo_sql WHERE id=1";
        if ($conn->query($sql)) {
            $message = "Pengaturan berhasil disimpan!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }

    if (isset($_POST['save_post']) || isset($_POST['save_and_publish']) || isset($_POST['save_as_draft'])) {
        $title = sanitize($_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        $category_id = intval($_POST['category_id']);
        $category_id = intval($_POST['category_id']);
        $release_date = !empty($_POST['release_date']) ? "'" . sanitize($_POST['release_date']) . "'" : "NULL";

        // Optional fields for Books
        $publisher = isset($_POST['publisher']) ? sanitize($_POST['publisher']) : '';
        $purchase_link = isset($_POST['purchase_link']) ? sanitize($_POST['purchase_link']) : '';
        $price = isset($_POST['price']) && !empty($_POST['price']) ? floatval($_POST['price']) : 'NULL';

        // Handle file uploads
        $image = null;
        $file_attachment = null;
        $audio = null;
        $video = null;

        if (!empty($_FILES['image']['name'])) {
            $upl_img = uploadFile($_FILES['image']);
            if ($upl_img) {
                $image = $upl_img;
            } else {
                $error_uploads[] = "Gagal mengupload Gambar Utama (cek format atau ukuran file).";
            }
        }

        if (!empty($_FILES['file_attachment']['name'])) {
            $upl_file = uploadFile($_FILES['file_attachment']);
            if ($upl_file) {
                $file_attachment = $upl_file;
            } else {
                $error_uploads[] = "Gagal mengupload File Lampiran.";
            }
        }

        if (!empty($_FILES['audio']['name'])) {
            $upl_audio = uploadFile($_FILES['audio']);
            if ($upl_audio) {
                $audio = $upl_audio;
            } else {
                $error_uploads[] = "Gagal mengupload Audio.";
            }
        }

        if (!empty($_FILES['video']['name'])) {
            $upl_video = uploadFile($_FILES['video']);
            if ($upl_video) {
                $video = $upl_video;
            } else {
                $error_uploads[] = "Gagal mengupload Video.";
            }
        }

        if (!empty($error_uploads)) {
            $message = implode("<br>", $error_uploads);
        } else {
            // Determine status based on which button was clicked
            $status = 'draft'; // default
            if (isset($_POST['save_and_publish'])) {
                $status = 'published';
            } elseif (isset($_POST['save_as_draft'])) {
                $status = 'draft';
            } elseif (isset($_POST['save_post'])) {
                // If just 'save_post', keep existing status or default to published for backward compatibility
                $status = isset($_POST['current_status']) ? $_POST['current_status'] : 'published';
            }

            if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
                // Update existing post
                $id = intval($_POST['post_id']);

                // Only update file fields if new files were uploaded
                $img_sql = $image ? ", image='$image'" : "";
                $file_sql = $file_attachment ? ", file_attachment='$file_attachment'" : "";
                $audio_sql = $audio ? ", audio='$audio'" : "";
                $video_sql = $video ? ", video='$video'" : "";

                $sql = "UPDATE posts SET title='$title', content='$content', category_id=$category_id, release_date=$release_date, status='$status', publisher='$publisher', purchase_link='$purchase_link', price=$price $img_sql $file_sql $audio_sql $video_sql WHERE id=$id";
            } else {
                // Insert new post
                $slug = strtolower(str_replace(' ', '-', $title)) . '-' . time();

                // Convert null to empty string for INSERT
                $image = $image ?? '';
                $file_attachment = $file_attachment ?? '';
                $audio = $audio ?? '';
                $video = $video ?? '';
                
                // For insert, if price is 'NULL' string, use NULL keyword in query, handled by $price variable being set to string 'NULL' or number
                // But for prepared statement safety, let's just put it directly in query string carefully as we sanitize above
                
                $sql = "INSERT INTO posts (title, slug, content, category_id, release_date, status, publisher, purchase_link, price, image, file_attachment, audio, video) VALUES ('$title', '$slug', '$content', $category_id, $release_date, '$status', '$publisher', '$purchase_link', $price, '$image', '$file_attachment', '$audio', '$video')";
            }

            if ($conn->query($sql)) {
                $message = "Post berhasil disimpan!";
                header("Location: admin.php?page=posts&msg=" . urlencode($message));
                exit();
            } else {
                $message = "Error Database: " . $conn->error;
            }
        }
    }
}

// Handle Toggle Status (Publish/Unpublish)
if ($page == 'toggle_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'] == 'published' ? 'published' : 'draft';
    $conn->query("UPDATE posts SET status='$status' WHERE id=$id");
    $msg = $status == 'published' ? 'Post berhasil dipublish!' : 'Post berhasil di-unpublish!';
    header("Location: admin.php?page=posts&msg=" . urlencode($msg));
    exit();
}

// Handle Toggle Simulation Status
if ($page == 'toggle_simulation_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'] == 'published' ? 'published' : 'draft';
    $conn->query("UPDATE simulations SET status='$status' WHERE id=$id");
    $msg = $status == 'published' ? 'Simulasi berhasil dipublish!' : 'Simulasi berhasil di-unpublish!';
    header("Location: admin.php?page=simulations&msg=" . urlencode($msg));
    exit();
}

// Handle Delete Post
if ($page == 'delete_post' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM posts WHERE id=$id");
    header("Location: admin.php?page=posts&msg=Post dihapus");
    exit();
}

// Handle Save User
if (isset($_POST['save_user'])) {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $role = $_POST['role'] == 'admin' ? 'admin' : 'editor';

    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        // Update existing user
        $id = intval($_POST['user_id']);

        // Check if password is being changed
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username='$username', password='$password', email='$email', full_name='$full_name', role='$role' WHERE id=$id";
        } else {
            $sql = "UPDATE users SET username='$username', email='$email', full_name='$full_name', role='$role' WHERE id=$id";
        }

        if ($conn->query($sql)) {
            header("Location: admin.php?page=users&msg=" . urlencode("User berhasil diupdate!"));
            exit();
        } else {
            $message = "Error: " . $conn->error;
        }
    } else {
        // Create new user
        if (empty($_POST['password'])) {
            $message = "Password harus diisi untuk user baru!";
        } else {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, email, full_name, role) VALUES ('$username', '$password', '$email', '$full_name', '$role')";

            if ($conn->query($sql)) {
                header("Location: admin.php?page=users&msg=" . urlencode("User berhasil ditambahkan!"));
                exit();
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}

// Handle Delete User
if ($page == 'delete_user' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Prevent deleting the currently logged in user
    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
        header("Location: admin.php?page=users&msg=" . urlencode("User berhasil dihapus!"));
    } else {
        header("Location: admin.php?page=users&msg=" . urlencode("Tidak bisa menghapus user yang sedang login!"));
    }
    exit();
}

// Handle Save Simulation
if (isset($_POST['save_simulation']) || isset($_POST['save_simulation_draft'])) {
    $name = sanitize($_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = sanitize($_POST['category']);
    $url = sanitize($_POST['url']);
    $release_date = sanitize($_POST['release_date']);
    $status = isset($_POST['save_simulation_draft']) ? 'draft' : 'published';

    // Handle HTML File Upload
    if (!empty($_FILES['html_file']['name'])) {
        $upload_path = "simulasi/";
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        $upl_html = uploadFile($_FILES['html_file'], $upload_path);
        if ($upl_html) {
            $url = $upload_path . $upl_html;
        }
    }

    if (isset($_POST['simulation_id']) && !empty($_POST['simulation_id'])) {
        // Update
        $id = intval($_POST['simulation_id']);
        $sql = "UPDATE simulations SET name='$name', description='$description', category='$category', url='$url', release_date='$release_date', status='$status' WHERE id=$id";
    } else {
        // Insert
        $sql = "INSERT INTO simulations (name, description, category, url, release_date, status) VALUES ('$name', '$description', '$category', '$url', '$release_date', '$status')";
    }

    if ($conn->query($sql)) {
        header("Location: admin.php?page=simulations&msg=" . urlencode("Simulasi berhasil disimpan!"));
        exit();
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Handle Delete Simulation
if ($page == 'delete_simulation' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM simulations WHERE id=$id");
    header("Location: admin.php?page=simulations&msg=" . urlencode("Simulasi berhasil dihapus!"));
    exit();
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: 'textarea[name="content"]',
            plugins: 'image link lists media table help',
            toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image media | table',
            images_upload_url: 'upload_image.php',
            automatic_uploads: true,
            file_picker_types: 'image',
            /* and here's our custom image picker*/
            file_picker_callback: (cb, value, meta) => {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');

                input.addEventListener('change', (e) => {
                    const file = e.target.files[0];

                    const reader = new FileReader();
                    reader.addEventListener('load', () => {
                        /*
                          Note: Now we need to register the blob in TinyMCEs image blob
                          registry. In the next release this part hopefully won't be
                          necessary, as we are looking to handle it internally.
                        */
                        const id = 'blobid' + (new Date()).getTime();
                        const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        const base64 = reader.result.split(',')[1];
                        const blobInfo = blobCache.create(id, file, base64);
                        blobCache.add(blobInfo);

                        /* call the callback and populate the Title field with the file name */
                        cb(blobInfo.blobUri(), { title: file.name });
                    });
                    reader.readAsDataURL(file);
                });

                input.click();
            },
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }'
        });
    </script>
    <style>
        .sidebar {
            background: #1e3a8a;
            color: white;
            min-height: 100vh;
        }

        .sidebar a {
            display: block;
            padding: 1rem;
            color: #bfdbfe;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1e40af;
            color: white;
            border-left: 4px solid #60a5fa;
        }
    </style>
</head>

<body class="bg-gray-100 flex">

    <!-- Sidebar -->
    <aside class="w-64 sidebar hidden md:block">
        <div class="p-6 text-2xl font-bold border-b border-blue-800">Admin Panel</div>
        <nav class="mt-4">
            <a href="admin.php?page=dashboard" class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>"><i
                    class="fas fa-chart-line mr-2"></i> Dashboard</a>
            <a href="admin.php?page=posts" class="<?php echo ($page == 'posts') ? 'active' : ''; ?>"><i
                    class="fas fa-newspaper mr-2"></i> Kelola Post</a>
            <a href="admin.php?page=create_post" class="<?php echo ($page == 'create_post') ? 'active' : ''; ?>"><i
                    class="fas fa-plus-circle mr-2"></i> Tambah Baru</a>
            <a href="admin.php?page=books" class="<?php echo ($page == 'books') ? 'active' : ''; ?>"><i
                    class="fas fa-book mr-2"></i> Kelola Buku</a>
            <a href="admin.php?page=simulations" class="<?php echo ($page == 'simulations') ? 'active' : ''; ?>"><i
                    class="fas fa-vial mr-2"></i> Kelola Simulasi</a>
            <a href="admin.php?page=create_simulation"
                class="<?php echo ($page == 'create_simulation') ? 'active' : ''; ?>"><i
                    class="fas fa-plus-square mr-2"></i> Tambah Simulasi</a>
            <a href="admin.php?page=settings" class="<?php echo ($page == 'settings') ? 'active' : ''; ?>"><i
                    class="fas fa-cogs mr-2"></i> Pengaturan Website</a>
            <a href="admin.php?page=users"
                class="<?php echo ($page == 'users' || $page == 'create_user') ? 'active' : ''; ?>"><i
                    class="fas fa-users mr-2"></i> Kelola User</a>
            <a href="index.php" target="_blank"><i class="fas fa-external-link-alt mr-2"></i> Lihat Website</a>
            <a href="admin.php?page=logout" class="text-red-300 hover:text-red-100 mt-8"><i
                    class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">

        <!-- Topbar -->
        <header class="bg-white shadow p-4 flex justify-between items-center md:hidden">
            <span class="font-bold text-xl">Admin Panel</span>
            <button onclick="document.querySelector('.sidebar').classList.toggle('hidden')"
                class="text-gray-600 focus:outline-none"><i class="fas fa-bars"></i></button>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
            <?php if ($message || isset($_GET['msg'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <span class="block sm:inline">
                        <?php echo $message ?: $_GET['msg']; ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if ($page == 'dashboard'):
                $count_posts = $conn->query("SELECT COUNT(*) as total FROM posts")->fetch_assoc()['total'];
                $count_views = $conn->query("SELECT SUM(views) as total FROM posts")->fetch_assoc()['total'];
                ?>
                <h2 class="text-3xl font-bold mb-6 text-gray-800">Dashboard Overview</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Postingan</p>
                                <h3 class="text-2xl font-bold text-gray-800">
                                    <?php echo $count_posts; ?>
                                </h3>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full text-blue-500">
                                <i class="fas fa-copy fa-lg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Dilihat</p>
                                <h3 class="text-2xl font-bold text-gray-800">
                                    <?php echo $count_views ?: 0; ?>
                                </h3>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full text-green-500">
                                <i class="fas fa-eye fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top 10 Most Viewed Posts Leaderboard -->
                <?php
                $top_posts = $conn->query("SELECT p.*, c.name as category_name 
                                           FROM posts p 
                                           JOIN categories c ON p.category_id = c.id 
                                           ORDER BY p.views DESC 
                                           LIMIT 10");
                ?>
                <div class="mt-8">
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-trophy text-yellow-500"></i> Top 10 Postingan Terpopuler
                    </h3>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-blue-600 to-blue-700 text-white">
                                <tr>
                                    <th class="py-3 px-4 text-center w-16">#</th>
                                    <th class="py-3 px-6 text-left">Judul Postingan</th>
                                    <th class="py-3 px-4 text-center">Kategori</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                    <th class="py-3 px-4 text-center">
                                        <i class="fas fa-eye"></i> Views
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <?php
                                $rank = 1;
                                while ($post = $top_posts->fetch_assoc()):
                                    $rank_color = '';
                                    $rank_icon = '';
                                    if ($rank == 1) {
                                        $rank_color = 'bg-yellow-100 border-l-4 border-yellow-500';
                                        $rank_icon = '<i class="fas fa-crown text-yellow-500"></i>';
                                    } elseif ($rank == 2) {
                                        $rank_color = 'bg-gray-100 border-l-4 border-gray-400';
                                        $rank_icon = '<i class="fas fa-medal text-gray-400"></i>';
                                    } elseif ($rank == 3) {
                                        $rank_color = 'bg-orange-100 border-l-4 border-orange-600';
                                        $rank_icon = '<i class="fas fa-medal text-orange-600"></i>';
                                    }
                                    ?>
                                    <tr class="border-b hover:bg-gray-50 <?php echo $rank_color; ?>">
                                        <td class="py-3 px-4 text-center font-bold text-lg">
                                            <?php echo $rank_icon ? $rank_icon : $rank; ?>
                                        </td>
                                        <td class="py-3 px-6">
                                            <a href="detail.php?slug=<?php echo $post['slug']; ?>" target="_blank"
                                                class="font-medium hover:text-blue-600 transition">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php echo date('d M Y', strtotime($post['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span
                                                class="bg-purple-100 text-purple-700 px-2 py-1 rounded-full text-xs font-semibold">
                                                <?php echo htmlspecialchars($post['category_name']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <?php if ($post['status'] == 'published'): ?>
                                                <span class="text-green-600 text-xs">
                                                    <i class="fas fa-check-circle"></i> Published
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-500 text-xs">
                                                    <i class="fas fa-file"></i> Draft
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="font-bold text-blue-600 text-lg">
                                                <?php echo number_format($post['views']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php
                                    $rank++;
                                endwhile;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>


            <?php elseif ($page == 'books'):
                $sql = "SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id WHERE c.slug = 'buku' ORDER BY p.created_at DESC";
                $result = $conn->query($sql);
                // Get Buku ID for the add button
                $buku_id_res = $conn->query("SELECT id FROM categories WHERE slug='buku'")->fetch_assoc();
                $buku_id = $buku_id_res['id'];
                ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Kelola Buku</h2>
                    <a href="admin.php?page=create_post&category_id=<?php echo $buku_id; ?>"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><i
                            class="fas fa-plus"></i> Tambah Buku</a>
                </div>
                <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Judul Buku</th>
                                <th class="py-3 px-6 text-left">Kategori</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Views</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="font-medium">
                                                <?php echo substr($row['title'], 0, 40); ?>...
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="bg-indigo-200 text-indigo-600 py-1 px-3 rounded-full text-xs">
                                            <?php echo $row['category_name']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php if ($row['status'] == 'published'): ?>
                                            <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-check-circle"></i> Published
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-file"></i> Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php echo $row['views']; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center gap-2">
                                            <a href="admin.php?page=create_post&id=<?php echo $row['id']; ?>"
                                                class="transform hover:text-purple-500 hover:scale-110" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($row['status'] == 'published'): ?>
                                                <a href="admin.php?page=toggle_status&id=<?php echo $row['id']; ?>&status=draft"
                                                    class="transform hover:text-orange-500 hover:scale-110" title="Unpublish"
                                                    onclick="return confirm('Yakin ingin unpublish post ini?')">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="admin.php?page=toggle_status&id=<?php echo $row['id']; ?>&status=published"
                                                    class="transform hover:text-green-500 hover:scale-110" title="Publish"
                                                    onclick="return confirm('Yakin ingin publish post ini?')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="admin.php?page=delete_post&id=<?php echo $row['id']; ?>"
                                                onclick="return confirm('Yakin hapus?')"
                                                class="transform hover:text-red-500 hover:scale-110" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'posts'):
                $sql = "SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
                $result = $conn->query($sql);
                ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Kelola Postingan</h2>
                    <a href="admin.php?page=create_post"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><i
                            class="fas fa-plus"></i> Tambah Post</a>
                </div>
                <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Judul</th>
                                <th class="py-3 px-6 text-left">Kategori</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">Views</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="font-medium">
                                                <?php echo substr($row['title'], 0, 40); ?>...
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <span class="bg-purple-200 text-purple-600 py-1 px-3 rounded-full text-xs">
                                            <?php echo $row['category_name']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php if ($row['status'] == 'published'): ?>
                                            <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-check-circle"></i> Published
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-gray-100 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-file"></i> Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php echo $row['views']; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center gap-2">
                                            <a href="admin.php?page=create_post&id=<?php echo $row['id']; ?>"
                                                class="transform hover:text-purple-500 hover:scale-110" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($row['status'] == 'published'): ?>
                                                <a href="admin.php?page=toggle_status&id=<?php echo $row['id']; ?>&status=draft"
                                                    class="transform hover:text-orange-500 hover:scale-110" title="Unpublish"
                                                    onclick="return confirm('Yakin ingin unpublish post ini?')">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="admin.php?page=toggle_status&id=<?php echo $row['id']; ?>&status=published"
                                                    class="transform hover:text-green-500 hover:scale-110" title="Publish"
                                                    onclick="return confirm('Yakin ingin publish post ini?')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="admin.php?page=delete_post&id=<?php echo $row['id']; ?>"
                                                onclick="return confirm('Yakin hapus?')"
                                                class="transform hover:text-red-500 hover:scale-110" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'create_post'):
                $edit = false;
                $row = [];
                if (isset($_GET['id'])) {
                    $edit = true;
                    $id = intval($_GET['id']);
                    $row = $conn->query("SELECT * FROM posts WHERE id=$id")->fetch_assoc();
                }
                $preselected_cat_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
                ?>
                <?php $preselected_cat_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0; ?>
                <h2 class="text-2xl font-bold mb-6 text-gray-800">
                    <?php echo $edit ? 'Edit Post' : 'Tambah Post Baru'; ?>
                </h2>

                <?php if (isset($message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-6">
                    <?php if ($edit): ?><input type="hidden" name="post_id" value="<?php echo $row['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Judul</label>
                        <input type="text" name="title" value="<?php echo $edit ? $row['title'] : ''; ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <select name="category_id"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <?php
                            $cats = $conn->query("SELECT * FROM categories");
                            while ($c = $cats->fetch_assoc()):
                                $is_selected = ($edit && $row['category_id'] == $c['id']) || (!$edit && $preselected_cat_id == $c['id']);
                                ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                    <?php echo $c['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Book Detail Fields (Optimized for Book Category) -->
                    <div class="mb-4 border p-4 rounded bg-blue-50">
                        <h3 class="font-bold text-gray-700 mb-2">Detail Buku (Opsional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Penerbit</label>
                                <input type="text" name="publisher"
                                    value="<?php echo $edit ? ($row['publisher'] ?? '') : ''; ?>"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Harga (Rp)</label>
                                <input type="number" name="price"
                                    value="<?php echo $edit ? ($row['price'] ?? '') : ''; ?>"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Link Pembelian</label>
                                <input type="url" name="purchase_link"
                                    value="<?php echo $edit ? ($row['purchase_link'] ?? '') : ''; ?>"
                                    placeholder="https://"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Rilis (Opsional - Untuk
                            Buku/Riset)</label>
                        <input type="date" name="release_date"
                            value="<?php echo $edit ? ($row['release_date'] ?? '') : ''; ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Gambar Utama (Optional)</label>
                        <input type="file" name="image"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php if ($edit && $row['image']): ?>
                            <div class="mt-2">
                                <img src="uploads/<?php echo $row['image']; ?>" alt="Preview"
                                    class="h-20 w-auto rounded shadow-sm border">
                                <p class="text-xs text-gray-500 mt-1">File saat ini:
                                    <?php echo $row['image']; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">File Lampiran (PDF/Doc - Optional)</label>
                        <input type="file" name="file_attachment"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php if ($edit && $row['file_attachment']): ?>
                            <p class="text-xs text-gray-500 mt-1">Current:
                                <?php echo $row['file_attachment']; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">File Audio (MP3 - Untuk
                            Musik/Riset)</label>
                        <input type="file" name="audio"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php if ($edit && isset($row['audio']) && $row['audio']): ?>
                            <p class="text-xs text-gray-500 mt-1">Current:
                                <?php echo $row['audio']; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">File Video (MP4 - Untuk
                            Video/Riset)</label>
                        <input type="file" name="video"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php if ($edit && isset($row['video']) && $row['video']): ?>
                            <p class="text-xs text-gray-500 mt-1">Current:
                                <?php echo $row['video']; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Konten</label>
                        <textarea name="content" rows="10"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo $edit ? $row['content'] : ''; ?></textarea>
                    </div>

                    <?php if ($edit): ?>
                        <input type="hidden" name="current_status" value="<?php echo $row['status'] ?? 'published'; ?>">
                    <?php endif; ?>

                    <div class="flex items-center justify-between gap-3">
                        <button type="submit" name="save_as_draft"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-save"></i> Simpan sebagai Draft
                        </button>
                        <button type="submit" name="save_and_publish"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-check-circle"></i> Simpan & Publish
                        </button>
                        <a href="admin.php?page=posts"
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>

            <?php elseif ($page == 'settings'):
                $s = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
                ?>
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Pengaturan Website</h2>
                <form action="" method="POST" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-6">
                    <input type="hidden" name="save_settings" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Judul Website</label>
                            <input type="text" name="site_title" value="<?php echo $s['site_title']; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Tagline/Slogan</label>
                            <input type="text" name="tagline" value="<?php echo $s['tagline']; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Running Text (Info Berjalan)</label>
                        <input type="text" name="running_text" value="<?php echo $s['running_text']; ?>"
                            class="border rounded w-full py-2 px-3">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Header Image (Upload Baru untuk
                            Ganti)</label>
                        <input type="file" name="header_image" class="border rounded w-full py-2 px-3">
                        <?php if ($s['header_image']): ?>
                            <p class="text-xs text-gray-500 mt-1">Current:
                                <?php echo $s['header_image']; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Logo Website (Upload Baru untuk
                            Ganti)</label>
                        <input type="file" name="site_logo" class="border rounded w-full py-2 px-3">
                        <?php if (isset($s['site_logo']) && $s['site_logo']): ?>
                            <p class="text-xs text-gray-500 mt-1">Current: <img src="uploads/<?php echo $s['site_logo']; ?>"
                                    style="height: 30px; display: inline-block; vertical-align: middle;"></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode Iklan Sidebar 1 (HTML
                            Allowed)</label>
                        <textarea name="ad_sidebar"
                            class="border rounded w-full py-2 px-3 font-mono text-sm"><?php echo $s['ad_sidebar']; ?></textarea>
                    </div>

                    <div class="mb-4 border p-4 rounded bg-gray-50">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Iklan Sidebar 2 (Bawah)</label>
                        <div class="mb-2">
                            <label class="block text-xs text-gray-500">Upload Gambar Banner:</label>
                            <input type="file" name="ad_sidebar_2_image" class="border rounded w-full py-2 px-3">
                            <?php if (isset($s['ad_sidebar_2_image']) && $s['ad_sidebar_2_image']): ?>
                                <div class="mt-1">
                                    <img src="uploads/<?php echo $s['ad_sidebar_2_image']; ?>" style="max-height: 100px;">
                                    <p class="text-xs text-gray-500">Current Image</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs text-gray-500">Link Tujuan (URL):</label>
                            <input type="text" name="ad_sidebar_2_link"
                                value="<?php echo isset($s['ad_sidebar_2_link']) ? $s['ad_sidebar_2_link'] : ''; ?>"
                                class="border rounded w-full py-2 px-3" placeholder="https://...">
                        </div>
                        <div class="mt-2">
                            <label class="block text-xs text-gray-500">Atau Gunakan Kode HTML (opsional):</label>
                            <textarea name="ad_sidebar_2"
                                class="border rounded w-full py-2 px-3 font-mono text-sm"><?php echo isset($s['ad_sidebar_2']) ? $s['ad_sidebar_2'] : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4 border p-4 rounded bg-gray-50">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Iklan Footer 1</label>
                            <div class="mb-2">
                                <label class="block text-xs text-gray-500">Upload Gambar Banner:</label>
                                <input type="file" name="ad_footer_image" class="border rounded w-full py-2 px-3">
                                <?php if (isset($s['ad_footer_image']) && $s['ad_footer_image']): ?>
                                    <div class="mt-1">
                                        <img src="uploads/<?php echo $s['ad_footer_image']; ?>" style="max-height: 80px;">
                                        <p class="text-xs text-gray-500">Current Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs text-gray-500">Link Tujuan (URL):</label>
                                <input type="text" name="ad_footer_link"
                                    value="<?php echo isset($s['ad_footer_link']) ? $s['ad_footer_link'] : ''; ?>"
                                    class="border rounded w-full py-2 px-3" placeholder="https://...">
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs text-gray-500">Atau Gunakan Kode HTML (opsional):</label>
                                <textarea name="ad_footer"
                                    class="border rounded w-full py-2 px-3 font-mono text-sm"><?php echo $s['ad_footer']; ?></textarea>
                            </div>
                        </div>

                        <div class="mb-4 border p-4 rounded bg-gray-50">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Iklan Footer 2</label>
                            <div class="mb-2">
                                <label class="block text-xs text-gray-500">Upload Gambar Banner:</label>
                                <input type="file" name="ad_footer_2_image" class="border rounded w-full py-2 px-3">
                                <?php if (isset($s['ad_footer_2_image']) && $s['ad_footer_2_image']): ?>
                                    <div class="mt-1">
                                        <img src="uploads/<?php echo $s['ad_footer_2_image']; ?>" style="max-height: 80px;">
                                        <p class="text-xs text-gray-500">Current Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2">
                                <label class="block text-xs text-gray-500">Link Tujuan (URL):</label>
                                <input type="text" name="ad_footer_2_link"
                                    value="<?php echo isset($s['ad_footer_2_link']) ? $s['ad_footer_2_link'] : ''; ?>"
                                    class="border rounded w-full py-2 px-3" placeholder="https://...">
                            </div>
                            <div class="mt-2">
                                <label class="block text-xs text-gray-500">Atau Gunakan Kode HTML (opsional):</label>
                                <textarea name="ad_footer_2"
                                    class="border rounded w-full py-2 px-3 font-mono text-sm"><?php echo isset($s['ad_footer_2']) ? $s['ad_footer_2'] : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tentang Website</label>
                        <textarea name="about_text"
                            class="border rounded w-full py-2 px-3"><?php echo $s['about_text']; ?></textarea>
                    </div>

                    <h3 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2">Informasi Kontak</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" name="contact_email"
                                value="<?php echo isset($s['contact_email']) ? $s['contact_email'] : ''; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">No. WhatsApp / Telepon</label>
                            <input type="text" name="contact_phone"
                                value="<?php echo isset($s['contact_phone']) ? $s['contact_phone'] : ''; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Instagram (Link Profil)</label>
                            <input type="text" name="contact_instagram"
                                value="<?php echo isset($s['contact_instagram']) ? $s['contact_instagram'] : ''; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Facebook (Link Profil)</label>
                            <input type="text" name="contact_facebook"
                                value="<?php echo isset($s['contact_facebook']) ? $s['contact_facebook'] : ''; ?>"
                                class="border rounded w-full py-2 px-3">
                        </div>
                    </div>

                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Simpan Perubahan
                    </button>
                </form>

            <?php elseif ($page == 'users'):
                $sql = "SELECT * FROM users ORDER BY created_at DESC";
                $result = $conn->query($sql);
                ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Kelola User</h2>
                    <a href="admin.php?page=create_user"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><i
                            class="fas fa-plus"></i> Tambah User</a>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['msg']); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Username</th>
                                <th class="py-3 px-6 text-left">Nama Lengkap</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-center">Role</th>
                                <th class="py-3 px-6 text-center">Dibuat</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-100">
                                    <td class="py-3 px-6 text-left">
                                        <span class="font-medium">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <?php echo htmlspecialchars($row['full_name'] ?? '-'); ?>
                                    </td>
                                    <td class="py-3 px-6 text-left">
                                        <?php echo htmlspecialchars($row['email'] ?? '-'); ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php if ($row['role'] == 'admin'): ?>
                                            <span class="bg-red-100 text-red-700 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-user-shield"></i> Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-blue-100 text-blue-700 py-1 px-3 rounded-full text-xs font-semibold">
                                                <i class="fas fa-user-edit"></i> Editor
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center gap-2">
                                            <a href="admin.php?page=create_user&id=<?php echo $row['id']; ?>"
                                                class="transform hover:text-purple-500 hover:scale-110" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                                <a href="admin.php?page=delete_user&id=<?php echo $row['id']; ?>"
                                                    onclick="return confirm('Yakin hapus user ini?')"
                                                    class="transform hover:text-red-500 hover:scale-110" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'simulations'):
                $sql = "SELECT * FROM simulations ORDER BY release_date DESC";
                $result = $conn->query($sql);
                ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Kelola Simulasi</h2>
                    <a href="admin.php?page=create_simulation"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><i
                            class="fas fa-plus"></i> Tambah Simulasi</a>
                </div>
                <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Nama Simulator</th>
                                <th class="py-3 px-6 text-left">Kategori</th>
                                <th class="py-3 px-6 text-center">Status</th>
                                <th class="py-3 px-6 text-center">URL</th>
                                <th class="py-3 px-6 text-center">Tanggal Rilis</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-6 text-left whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="font-medium">
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-6 text-left">
                                            <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs">
                                                <?php echo htmlspecialchars($row['category']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <?php if (isset($row['status']) && $row['status'] == 'published'): ?>
                                                <span class="bg-green-100 text-green-700 py-1 px-3 rounded-full text-xs font-semibold">
                                                    <i class="fas fa-check-circle"></i> Published
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-gray-100 text-gray-600 py-1 px-3 rounded-full text-xs font-semibold">
                                                    <i class="fas fa-file"></i> Draft
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-6 text-center">
                                            <a href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank"
                                                class="text-blue-500 hover:underline text-xs">Link <i
                                                    class="fas fa-external-link-alt"></i></a>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <?php echo date('d M Y', strtotime($row['release_date'])); ?>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <div class="flex item-center justify-center gap-2">
                                                <a href="admin.php?page=create_simulation&id=<?php echo $row['id']; ?>"
                                                    class="transform hover:text-purple-500 hover:scale-110" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if (isset($row['status']) && $row['status'] == 'published'): ?>
                                                    <a href="admin.php?page=toggle_simulation_status&id=<?php echo $row['id']; ?>&status=draft"
                                                        class="transform hover:text-orange-500 hover:scale-110" title="Unpublish"
                                                        onclick="return confirm('Yakin ingin unpublish simulasi ini?')">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="admin.php?page=toggle_simulation_status&id=<?php echo $row['id']; ?>&status=published"
                                                        class="transform hover:text-green-500 hover:scale-110" title="Publish"
                                                        onclick="return confirm('Yakin ingin publish simulasi ini?')">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="admin.php?page=delete_simulation&id=<?php echo $row['id']; ?>"
                                                    onclick="return confirm('Yakin hapus simulasi ini?')"
                                                    class="transform hover:text-red-500 hover:scale-110" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-center">Belum ada simulasi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'create_simulation'):
                $edit = false;
                $row = [];
                if (isset($_GET['id'])) {
                    $edit = true;
                    $id = intval($_GET['id']);
                    $row = $conn->query("SELECT * FROM simulations WHERE id=$id")->fetch_assoc();
                }
                ?>
                <h2 class="text-2xl font-bold mb-6 text-gray-800">
                    <?php echo $edit ? 'Edit Simulasi' : 'Tambah Simulasi Baru'; ?>
                    <?php if ($edit && isset($row['status'])): ?>
                        <span
                            class="text-sm ml-2 px-2 py-1 rounded-full <?php echo $row['status'] == 'published' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    <?php endif; ?>
                </h2>

                <form action="" method="POST" enctype="multipart/form-data" class="bg-white shadow rounded-lg p-6">
                    <?php if ($edit): ?><input type="hidden" name="simulation_id" value="<?php echo $row['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nama Simulator</label>
                        <input type="text" name="name" value="<?php echo $edit ? htmlspecialchars($row['name']) : ''; ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <select name="category"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="IoT" <?php echo ($edit && $row['category'] == 'IoT') ? 'selected' : ''; ?>>IoT
                            </option>
                            <option value="Kecerdasan Buatan" <?php echo ($edit && $row['category'] == 'Kecerdasan Buatan') ? 'selected' : ''; ?>>Kecerdasan Buatan</option>
                            <option value="Matematika" <?php echo ($edit && $row['category'] == 'Matematika') ? 'selected' : ''; ?>>Matematika</option>
                            <option value="Sains" <?php echo ($edit && $row['category'] == 'Sains') ? 'selected' : ''; ?>>
                                Sains
                            </option>
                            <option value="Teknik" <?php echo ($edit && $row['category'] == 'Teknik') ? 'selected' : ''; ?>>
                                Teknik</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Alamat Simulasi (URL) / Upload File
                            HTML</label>
                        <div class="flex gap-4 items-start">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Opsi 1: Masukkan URL External</label>
                                <input type="url" name="url" id="url_input"
                                    value="<?php echo $edit ? htmlspecialchars($row['url'] ?? '') : ''; ?>"
                                    placeholder="https://example.com/simulator"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs text-gray-500 mb-1">Opsi 2: Upload File HTML</label>
                                <input type="file" name="html_file" accept=".html"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <?php if ($edit && !empty($row['url']) && strpos($row['url'], 'simulasi/') === 0): ?>
                                    <p class="text-xs text-blue-600 mt-1">File saat ini: <?php echo basename($row['url']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">* Jika Anda mengupload file, sistem akan mengabaikan URL
                            manual di atas.</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Rilis</label>
                        <input type="date" name="release_date"
                            value="<?php echo $edit ? $row['release_date'] : date('Y-m-d'); ?>"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Deskripsi</label>
                        <textarea name="description" rows="5"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo $edit ? htmlspecialchars($row['description']) : ''; ?></textarea>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex gap-2">
                            <button type="submit" name="save_simulation_draft"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save"></i> Simpan Draft
                            </button>
                            <button type="submit" name="save_simulation"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-check-circle"></i> Publish
                            </button>
                        </div>
                        <a href="admin.php?page=simulations"
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>

            <?php elseif ($page == 'create_user'):
                $edit = false;
                $row = [];
                if (isset($_GET['id'])) {
                    $edit = true;
                    $id = intval($_GET['id']);
                    $row = $conn->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();
                }
                ?>
                <h2 class="text-2xl font-bold mb-6 text-gray-800">
                    <?php echo $edit ? 'Edit User' : 'Tambah User Baru'; ?>
                </h2>

                <?php if (isset($message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="bg-white shadow rounded-lg p-6">
                    <?php if ($edit): ?><input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                            <input type="text" name="username" value="<?php echo $edit ? $row['username'] : ''; ?>"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" name="email" value="<?php echo $edit ? ($row['email'] ?? '') : ''; ?>"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                            <input type="text" name="full_name"
                                value="<?php echo $edit ? ($row['full_name'] ?? '') : ''; ?>"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                            <select name="role"
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="editor" <?php echo ($edit && $row['role'] == 'editor') ? 'selected' : ''; ?>>
                                    Editor</option>
                                <option value="admin" <?php echo ($edit && $row['role'] == 'admin') ? 'selected' : ''; ?>>
                                    Admin</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                Password
                                <?php echo $edit ? '(Kosongkan jika tidak ingin mengubah)' : '*'; ?>
                            </label>
                            <input type="password" name="password"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                <?php echo !$edit ? 'required' : ''; ?>>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 mt-6">
                        <button type="submit" name="save_user"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-save"></i> Simpan User
                        </button>
                        <a href="admin.php?page=users"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>

            <?php endif; ?>

        </main>
    </div>

</body>

</html>