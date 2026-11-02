<?php
/**
 * ResepApp - PHP Native untuk Azure App Service
 * CRUD resep dengan SQLite (siap deploy, tidak butuh DB eksternal)
 */

session_start();

// ================== KONFIGURASI ==================
$dbFile = __DIR__ . '/data/recipes.db';
$dataDir = __DIR__ . '/data';

// Pastikan folder data writable
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Inisialisasi database
function getDB() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS recipes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        country TEXT NOT NULL,
        ingredients TEXT NOT NULL,
        instructions TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

$pdo = getDB();

// ================== HANDLER ==================
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if ($name && $country && $ingredients && $instructions) {
        $stmt = $pdo->prepare("INSERT INTO recipes (name, country, ingredients, instructions) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $country, $ingredients, $instructions]);
        $_SESSION['flash'] = 'Resep "' . htmlspecialchars($name) . '" berhasil ditambahkan.';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Semua field wajib diisi.';
    }
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update' && $id > 0) {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');

    if ($name && $country && $ingredients && $instructions) {
        $stmt = $pdo->prepare("UPDATE recipes SET name=?, country=?, ingredients=?, instructions=? WHERE id=?");
        $stmt->execute([$name, $country, $ingredients, $instructions, $id]);
        $_SESSION['flash'] = 'Resep berhasil diperbarui.';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Semua field wajib diisi.';
    }
}

// DELETE
if ($action === 'delete' && $id > 0) {
    $stmt = $pdo->prepare("DELETE FROM recipes WHERE id=?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Resep berhasil dihapus.';
    header('Location: index.php');
    exit;
}

// READ (list + search)
$q = trim($_GET['q'] ?? '');
$countryFilter = trim($_GET['country'] ?? '');
$sql = "SELECT * FROM recipes WHERE 1=1";
$params = [];
if ($q !== '') {
    $sql .= " AND (LOWER(name) LIKE ? OR LOWER(ingredients) LIKE ?)";
    $like = '%' . strtolower($q) . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($countryFilter !== '') {
    $sql .= " AND country = ?";
    $params[] = $countryFilter;
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Countries for filter
$countries = $pdo->query("SELECT DISTINCT country FROM recipes ORDER BY country")->fetchAll(PDO::FETCH_COLUMN);
$total = $pdo->query("SELECT COUNT(*) FROM recipes")->fetchColumn();

// Edit data
$editRecipe = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE id=?");
    $stmt->execute([$id]);
    $editRecipe = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function ingredientList($text) {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text))));
}

function stepList($text) {
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)));
    return array_values(array_map(function($l) {
        return preg_replace('/^\d+[\.\)]\s*/', '', $l);
    }, $lines));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ResepApp - Resep Nusantara & Dunia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,800&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --cream: #faf6f1;
            --ink: #1c1917;
            --chili: #c2410c;
            --chili-dark: #9a3412;
            --leaf: #15803d;
            --line: #e7e5e4;
            --muted: #78716c;
            --white: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', system-ui, sans-serif;
            background: var(--cream);
            color: var(--ink);
            line-height: 1.5;
            min-height: 100vh;
        }
        .font-display { font-family: 'Fraunces', Georgia, serif; }
        a { color: inherit; text-decoration: none; }
        .container { max-width: 64rem; margin: 0 auto; padding: 0 1.25rem; }
        header {
            border-bottom: 1px solid var(--line);
            background: var(--white);
        }
        header .inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }
        .logo { font-size: 1.5rem; font-weight: 800; color: var(--chili); }
        .logo span { color: var(--ink); }
        .btn {
            display: inline-block;
            border-radius: 9999px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background 0.15s;
        }
        .btn-primary { background: var(--chili); color: white; }
        .btn-primary:hover { background: var(--chili-dark); }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--line);
            color: var(--ink);
        }
        .btn-outline:hover { background: var(--line); }
        .btn-danger {
            background: transparent;
            color: var(--muted);
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .btn-danger:hover { background: rgba(194,65,12,0.1); color: var(--chili); }
        .flash {
            margin: 1rem 0;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            background: #dcfce7;
            color: #166534;
            font-size: 0.9rem;
        }
        .error {
            margin: 1rem 0;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            background: #fee2e2;
            color: #991b1b;
        }
        .search-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }
        .search-bar input, .search-bar select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            font-size: 0.9rem;
            background: white;
        }
        .search-bar input { flex: 1; min-width: 180px; }
        .stats { color: var(--muted); font-size: 0.9rem; margin-bottom: 1rem; }
        .grid {
            display: grid;
            gap: 1.25rem;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px) {
            .grid { grid-template-columns: 1fr 1fr; }
        }
        @media (min-width: 1024px) {
            .grid { grid-template-columns: 1fr 1fr 1fr; }
        }
        .card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
        }
        .card h3 {
            font-family: 'Fraunces', serif;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .card .country {
            font-size: 0.8rem;
            color: var(--leaf);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .card .meta {
            font-size: 0.85rem;
            color: var(--muted);
            margin: 0.5rem 0;
        }
        .card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
            padding-top: 0.75rem;
        }
        details {
            margin-top: 0.75rem;
            border-top: 1px solid var(--line);
            padding-top: 0.75rem;
        }
        details summary {
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--chili);
            list-style: none;
        }
        details summary::-webkit-details-marker { display: none; }
        details ul, details ol {
            margin: 0.5rem 0 0 1.25rem;
            font-size: 0.9rem;
        }
        details h4 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--leaf);
            margin: 0.75rem 0 0.25rem;
        }
        .form-section {
            margin: 3rem 0;
            background: white;
            border: 1px solid var(--line);
            border-radius: 1rem;
            padding: 1.5rem;
        }
        .form-section h2 {
            font-family: 'Fraunces', serif;
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        .form-section p { color: var(--muted); margin-bottom: 1.25rem; }
        .form-grid {
            display: grid;
            gap: 1rem;
        }
        @media (min-width: 640px) {
            .form-grid { grid-template-columns: 1fr 1fr; }
        }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 0.875rem; font-weight: 600; }
        input[type="text"], textarea, select {
            padding: 0.6rem 0.75rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--cream);
        }
        textarea { min-height: 100px; resize: vertical; }
        .form-actions {
            margin-top: 1.25rem;
            display: flex;
            gap: 0.75rem;
        }
        footer {
            text-align: center;
            padding: 2rem 0;
            color: var(--muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--line);
            margin-top: 3rem;
        }
        .empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
<header>
    <div class="container inner">
        <a href="index.php" class="logo font-display">Resep<span>App</span></a>
        <a href="#tambah" class="btn btn-primary">+ Tambah Resep</a>
    </div>
</header>

<main class="container">
    <?php if ($message): ?>
        <div class="flash"><?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="search-bar">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:0.75rem;width:100%;">
            <input type="text" name="q" placeholder="Cari resep atau bahan..." value="<?= e($q) ?>">
            <select name="country">
                <option value="">Semua negara</option>
                <?php foreach ($countries as $c): ?>
                    <option value="<?= e($c) ?>" <?= $countryFilter === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline">Cari</button>
            <?php if ($q || $countryFilter): ?>
                <a href="index.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <p class="stats"><?= (int)$total ?> resep tersimpan</p>

    <?php if (empty($recipes)): ?>
        <div class="empty">
            <p>Belum ada resep. Yuk tambah yang pertama!</p>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($recipes as $r): ?>
                <article class="card">
                    <span class="country"><?= e($r['country']) ?></span>
                    <h3><?= e($r['name']) ?></h3>
                    <p class="meta"><?= count(ingredientList($r['ingredients'])) ?> bahan · <?= count(stepList($r['instructions'])) ?> langkah</p>
                    <div class="card-actions">
                        <a href="?action=edit&id=<?= (int)$r['id'] ?>#edit" class="btn btn-outline" style="font-size:0.8rem;padding:0.3rem 0.7rem;">Edit</a>
                        <a href="?action=delete&id=<?= (int)$r['id'] ?>" class="btn btn-danger" onclick="return confirm('Hapus resep ini?')">Hapus</a>
                    </div>
                    <details>
                        <summary>Lihat resep ↓</summary>
                        <h4>Bahan</h4>
                        <ul>
                            <?php foreach (ingredientList($r['ingredients']) as $item): ?>
                                <li><?= e($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <h4>Langkah</h4>
                        <ol>
                            <?php foreach (stepList($r['instructions']) as $step): ?>
                                <li><?= e($step) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH / EDIT -->
    <section class="form-section" id="<?= $editRecipe ? 'edit' : 'tambah' ?>">
        <h2 class="font-display"><?= $editRecipe ? 'Edit Resep' : 'Tambah resep baru' ?></h2>
        <p>Tulis satu bahan per baris, dan satu langkah per baris.</p>

        <form method="post" action="?action=<?= $editRecipe ? 'update&id=' . (int)$editRecipe['id'] : 'create' ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nama resep</label>
                    <input type="text" id="name" name="name" required maxlength="255"
                           value="<?= e($editRecipe['name'] ?? '') ?>" placeholder="Contoh: Nasi Goreng">
                </div>
                <div class="form-group">
                    <label for="country">Negara / Asal</label>
                    <input type="text" id="country" name="country" required maxlength="255"
                           value="<?= e($editRecipe['country'] ?? '') ?>" placeholder="Contoh: Indonesia">
                </div>
                <div class="form-group full">
                    <label for="ingredients">Bahan (satu baris per bahan)</label>
                    <textarea id="ingredients" name="ingredients" required
                              placeholder="2 piring nasi putih&#10;2 siung bawang putih&#10;1 butir telur"><?= e($editRecipe['ingredients'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <label for="instructions">Langkah (satu baris per langkah)</label>
                    <textarea id="instructions" name="instructions" required
                              placeholder="1. Panaskan minyak&#10;2. Tumis bawang&#10;3. Masukkan nasi"><?= e($editRecipe['instructions'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editRecipe ? 'Simpan Perubahan' : 'Tambah Resep' ?></button>
                <?php if ($editRecipe): ?>
                    <a href="index.php" class="btn btn-outline">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
</main>

<footer>
    <div class="container">
        ResepApp · PHP Native · Siap deploy ke Azure App Service
    </div>
</footer>
</body>
</html>
