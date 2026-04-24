<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$search = trim((string) ($_GET['q'] ?? ''));
$products = [];
$newsList = [];
$dangerousByEurope = [];
$errorMessage = '';

// Variabel bisa diubah sesuai kebutuhan demo/non-demo per kategori.
$showDemoByCategory = [
    'Makanan' => false,
    'Minuman' => false,
    'Kosmetik' => true,
];

$categoriesOrder = ['Makanan', 'Minuman', 'Kosmetik'];
$productsByCategory = [
    'Makanan' => [],
    'Minuman' => [],
    'Kosmetik' => [],
];

try {
    $pdo = getConnection();

    $newsStmt = $pdo->query(
        'SELECT judul, ringkasan, kategori, sumber, tanggal_publish
         FROM berita
         ORDER BY tanggal_publish DESC, id DESC
         LIMIT 6'
    );
    $newsList = $newsStmt->fetchAll();

    $sql = <<<SQL
    SELECT
        p.id,
        p.nama_produk,
        p.merk,
        p.kategori,
        p.is_demo,
        p.deskripsi_singkat,
        p.link_beli,
        kg.ingredient,
        kg.energi_kkal,
        kg.gula_g,
        kg.garam_mg,
        kg.lemak_g,
        kg.protein_g,
        kg.takaran_saji,
        GROUP_CONCAT(DISTINCT h.nama_tag ORDER BY h.nama_tag SEPARATOR ',') AS hashtags,
        GROUP_CONCAT(DISTINCT bbe.nama_bahan SEPARATOR '||') AS bahan_berbahaya,
        GROUP_CONCAT(DISTINCT CONCAT(bbe.nama_bahan, '::', bbe.status_eropa, '::', bbe.alasan) SEPARATOR '||') AS detail_bahan_berbahaya
    FROM produk p
    INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
    LEFT JOIN produk_hashtag ph ON ph.produk_id = p.id
    LEFT JOIN hashtag h ON h.id = ph.hashtag_id
    LEFT JOIN produk_bahan_berbahaya pbb ON pbb.produk_id = p.id
    LEFT JOIN bahan_berbahaya_eropa bbe ON bbe.id = pbb.bahan_id
    WHERE (:search = '')
       OR (p.nama_produk LIKE :keyword)
       OR (p.merk LIKE :keyword)
       OR (kg.ingredient LIKE :keyword)
       OR (h.nama_tag LIKE :keyword)
    GROUP BY p.id, p.nama_produk, p.merk, p.kategori, p.is_demo, p.deskripsi_singkat, p.link_beli,
             kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g, kg.takaran_saji
    ORDER BY FIELD(p.kategori, 'Makanan','Minuman','Kosmetik'), p.nama_produk ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search' => $search,
        ':keyword' => '%' . $search . '%',
    ]);
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $category = $product['kategori'];
        $isDemo = (int) $product['is_demo'] === 1;
        $allowDemoInCategory = $showDemoByCategory[$category] ?? false;

        if ($isDemo && !$allowDemoInCategory) {
            continue;
        }

        $dangerDetailsRaw = array_filter(explode('||', (string) $product['detail_bahan_berbahaya']));
        $dangerDetails = [];
        foreach ($dangerDetailsRaw as $item) {
            [$nama, $status, $alasan] = array_pad(explode('::', $item, 3), 3, '');
            if ($nama === '') {
                continue;
            }
            $dangerDetails[] = [
                'nama' => $nama,
                'status' => $status,
                'alasan' => $alasan,
            ];
            $dangerousByEurope[$nama] = [
                'status' => $status,
                'alasan' => $alasan,
            ];
        }

        $product['hashtags'] = array_values(array_filter(explode(',', (string) $product['hashtags'])));
        $product['danger_details'] = $dangerDetails;

        if (isset($productsByCategory[$category])) {
            $productsByCategory[$category][] = $product;
        }
    }
    unset($product);
} catch (Throwable $e) {
    $errorMessage = 'Koneksi database gagal. Cek config.php dan pastikan tabel database.sql sudah diimport.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RealFoodKu — Berita & Kategori Produk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/index.css">
</head>
<body>
<header>
    <div class="nav-wrapper">
        <a href="/" class="brand-logo">RealFoodKu</a>
        <div class="nav-links"><a href="#news">Berita</a><a href="#kategori">Kategori</a></div>
    </div>
</header>

<?php if (!empty($dangerousByEurope)): ?>
<section class="top-warning">
    <div class="warning-inner">
        <strong>⚠️ Bahan berisiko menurut regulasi Eropa:</strong>
        <ul>
            <?php foreach ($dangerousByEurope as $name => $info): ?>
                <li><b><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></b> (<?= htmlspecialchars($info['status'], ENT_QUOTES, 'UTF-8'); ?>) — <?= htmlspecialchars($info['alasan'], ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<section class="hero">
    <h1>Halaman Depan RealFoodKu</h1>
    <p>Berita terbaru dulu, lalu jelajahi kategori makanan, minuman, dan kosmetik.</p>
    <div class="search-container">
        <form method="get" action="/" class="glass-search">
            <input type="text" name="q" id="searchInput" placeholder="Cari produk, merk, ingredient, hashtag..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" id="searchBtn">Cari</button>
        </form>
    </div>
</section>

<main class="main-content">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section id="news">
        <div class="section-head">
            <h2>Berita Dummy</h2>
            <span class="hint">Konten depan sebelum daftar produk</span>
        </div>
        <div class="news-grid">
            <?php foreach ($newsList as $news): ?>
                <article class="news-card">
                    <div class="news-meta"><?= htmlspecialchars($news['kategori'], ENT_QUOTES, 'UTF-8'); ?> • <?= htmlspecialchars($news['tanggal_publish'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <h3><?= htmlspecialchars($news['judul'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?= htmlspecialchars($news['ringkasan'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <small>Sumber: <?= htmlspecialchars($news['sumber'], ENT_QUOTES, 'UTF-8'); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="kategori" class="category-section">
        <div class="section-head">
            <h2>Kategori Produk</h2>
            <span class="hint">Kosmetik ditandai demo, dan mode demo bisa diubah via variabel PHP.</span>
        </div>

        <?php foreach ($categoriesOrder as $category): ?>
            <div class="category-block">
                <h3>
                    <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (($showDemoByCategory[$category] ?? false) === true): ?>
                        <span class="demo-badge">DEMO ON</span>
                    <?php else: ?>
                        <span class="live-badge">DEMO OFF</span>
                    <?php endif; ?>
                </h3>

                <?php if (empty($productsByCategory[$category])): ?>
                    <div class="alert">Belum ada produk tampil pada kategori ini.</div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($productsByCategory[$category] as $product): ?>
                            <article class="product-card">
                                <div class="top-line">
                                    <span class="category-tag"><?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ((int) $product['is_demo'] === 1): ?>
                                        <span class="demo-tag">Demo</span>
                                    <?php endif; ?>
                                </div>
                                <h4><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <p class="ingredient"><b>Ingredient:</b> <?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></p>

                                <div class="nutrition">
                                    <div>Takaran: <?= htmlspecialchars($product['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div>Energi: <?= number_format((float) $product['energi_kkal'], 0); ?> kkal</div>
                                    <div>Gula: <?= number_format((float) $product['gula_g'], 1); ?> g</div>
                                    <div>Garam: <?= number_format((float) $product['garam_mg'], 0); ?> mg</div>
                                    <div>Lemak: <?= number_format((float) $product['lemak_g'], 1); ?> g</div>
                                    <div>Protein: <?= number_format((float) $product['protein_g'], 1); ?> g</div>
                                </div>

                                <?php if (!empty($product['hashtags'])): ?>
                                    <div class="hashtags">
                                        <?php foreach ($product['hashtags'] as $tag): ?>
                                            <span>#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($product['danger_details'])): ?>
                                    <div class="danger-box">
                                        <b>Bahan berisiko (Eropa):</b>
                                        <ul>
                                            <?php foreach ($product['danger_details'] as $danger): ?>
                                                <li><?= htmlspecialchars($danger['nama'], ENT_QUOTES, 'UTF-8'); ?> — <?= htmlspecialchars($danger['status'], ENT_QUOTES, 'UTF-8'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <a href="/produk/<?= (int) $product['id']; ?>" class="btn-action">Detail Lengkap</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>

<footer class="dark-footer">
    <div class="footer-wrap">
        <div>
            <h4>RealFoodKu</h4>
            <p>Portal edukasi pangan dengan data dummy untuk pengembangan.</p>
        </div>
        <div>
            <h4>Links</h4>
            <ul>
                <li><a href="#">Source</a></li>
                <li><a href="#">Github</a></li>
                <li><a href="#">Instagram</a></li>
                <li><a href="#">Dokumentasi</a></li>
            </ul>
        </div>
        <div>
            <h4>Kategori</h4>
            <ul>
                <li><a href="#kategori">Makanan</a></li>
                <li><a href="#kategori">Minuman</a></li>
                <li><a href="#kategori">Kosmetik</a></li>
            </ul>
        </div>
    </div>
</footer>

<script src="/assets/js/index.js"></script>
</body>
</html>
