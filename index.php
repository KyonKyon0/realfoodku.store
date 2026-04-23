<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$search = trim((string) ($_GET['q'] ?? ''));
$products = [];
$errorMessage = '';

try {
    $pdo = getConnection();

    $sql = <<<SQL
    SELECT id, nama_produk, merk, kategori, deskripsi_singkat, rating_data
    FROM produk
    WHERE (:search = '')
       OR (nama_produk LIKE :keyword)
       OR (merk LIKE :keyword)
       OR (kategori LIKE :keyword)
    ORDER BY nama_produk ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search' => $search,
        ':keyword' => '%' . $search . '%',
    ]);

    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $errorMessage = 'Koneksi database gagal. Periksa config.php dan MySQL Anda.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealFoodKu — Data Makanan & Minuman</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/index.css">
</head>
<body>
<header>
    <div class="nav-wrapper">
        <a href="/" class="brand-logo">RealFoodKu</a>
        <div class="nav-links">
            <a href="#">Eksplorasi</a>
        </div>
    </div>
</header>

<section class="hero">
    <h1>Pilih yang Asli.</h1>
    <p>Membantu Anda menemukan produk makanan dan minuman dengan bahan transparan.</p>

    <div class="search-container">
        <form method="get" action="/" class="glass-search">
            <input
                type="text"
                name="q"
                id="searchInput"
                placeholder="Cari susu, mie instan, atau merk..."
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
            >
            <button type="submit" id="searchBtn">Cari Produk</button>
        </form>
    </div>
</section>

<main class="main-content">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php elseif (empty($products) && $search !== ''): ?>
        <div class="alert">Produk "<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" tidak ditemukan.</div>
    <?php elseif (empty($products)): ?>
        <div class="alert">Belum ada data produk. Silakan import `database.sql` terlebih dahulu.</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <span class="category-tag"><?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="rating">⭐ <?= number_format((float) $product['rating_data'], 1); ?> / 10</div>
                    <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <a href="/produk/<?= (int) $product['id']; ?>" class="btn-action">Cek Ingredient</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?= date('Y'); ?> RealFoodKu. Data makanan & minuman.</p>
</footer>

<script src="/assets/js/index.js"></script>
</body>
</html>
