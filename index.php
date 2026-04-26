<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$search = trim((string) ($_GET['q'] ?? ''));
$newsList = [];
$productsByCategory = [
    'Makanan' => [
        'Nugget' => [],
        'Sosis' => [],
        'Bakso' => [],
    ],
    'Minuman' => [
        'Soda' => [],
        'Energy Drink' => [],
        'Susu' => [],
    ],
];
$dangerousByEurope = [];
$errorMessage = '';
$featuredFoodLink = '/produk/nugget-ayam-crispy';

$dummyNews = [
    [
        'judul' => 'Tips Belanja Produk Kemasan',
        'ringkasan' => 'Cek gula, garam, dan komposisi utama sebelum memilih produk harian.',
        'kategori' => 'Tips',
        'sumber' => 'Redaksi Dummy',
        'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=1200&q=80',
        'link_url' => 'https://example.com/news/tips-memilih-produk-harian',
        'tanggal_publish' => '2026-04-01',
    ],
    [
        'judul' => 'Tren Minuman Harian 2026',
        'ringkasan' => 'Produk minuman rendah gula dan tinggi kalsium semakin populer.',
        'kategori' => 'Tren',
        'sumber' => 'Insight Dummy',
        'image_url' => 'https://images.unsplash.com/photo-1505253716362-afaea1d3d1af?w=1200&q=80',
        'link_url' => 'https://example.com/news/update-tren-minuman-2026',
        'tanggal_publish' => '2026-04-05',
    ],
];

$resolveSegment = static function (array $product): string {
    $name = strtolower((string) ($product['nama_produk'] ?? ''));
    $ingredient = strtolower((string) ($product['ingredient'] ?? ''));
    $hashtags = strtolower((string) ($product['hashtags'] ?? ''));

    if (($product['kategori'] ?? '') === 'Makanan') {
        if (str_contains($name, 'sosis') || str_contains($hashtags, 'sosis')) {
            return 'Sosis';
        }
        if (str_contains($name, 'bakso') || str_contains($hashtags, 'sapi')) {
            return 'Bakso';
        }

        return 'Nugget';
    }

    if (str_contains($name, 'energy') || str_contains($ingredient, 'kafein')) {
        return 'Energy Drink';
    }
    if (str_contains($name, 'soda') || str_contains($name, 'jeruk') || str_contains($ingredient, 'berkarbonasi')) {
        return 'Soda';
    }

    return 'Susu';
};

try {
    $pdo = getConnection();

    $newsStmt = $pdo->query(
        'SELECT judul, ringkasan, kategori, sumber, image_url, link_url, tanggal_publish
         FROM berita
         ORDER BY tanggal_publish DESC, id DESC
         LIMIT 6'
    );
    $newsList = $newsStmt->fetchAll();
    if (empty($newsList)) {
        $newsList = $dummyNews;
    }

    $sql = <<<SQL
    SELECT
        p.id,
        p.nama_produk,
        p.merk,
        p.kategori,
        p.deskripsi_singkat,
        p.image_url,
        p.link_beli,
        kg.ingredient,
        GROUP_CONCAT(DISTINCT h.nama_tag ORDER BY h.nama_tag SEPARATOR ',') AS hashtags,
        GROUP_CONCAT(DISTINCT CONCAT(bbe.nama_bahan, '::', bbe.status_eropa, '::', bbe.alasan) SEPARATOR '||') AS detail_bahan_berbahaya
    FROM produk p
    INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
    LEFT JOIN produk_hashtag ph ON ph.produk_id = p.id
    LEFT JOIN hashtag h ON h.id = ph.hashtag_id
    LEFT JOIN produk_bahan_berbahaya pbb ON pbb.produk_id = p.id
    LEFT JOIN bahan_berbahaya_eropa bbe ON bbe.id = pbb.bahan_id
    WHERE p.kategori IN ('Makanan', 'Minuman')
      AND (
          (:search = '')
          OR (p.nama_produk LIKE :keyword)
          OR (p.merk LIKE :keyword)
          OR (kg.ingredient LIKE :keyword)
          OR (h.nama_tag LIKE :keyword)
      )
    GROUP BY p.id, p.nama_produk, p.merk, p.kategori, p.deskripsi_singkat, p.image_url, p.link_beli,
             kg.ingredient
    ORDER BY FIELD(p.kategori, 'Makanan', 'Minuman'), p.nama_produk ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search' => $search,
        ':keyword' => '%' . $search . '%',
    ]);

    foreach ($stmt->fetchAll() as $product) {
        $dangerDetailsRaw = array_filter(explode('||', (string) $product['detail_bahan_berbahaya']));

        foreach ($dangerDetailsRaw as $item) {
            [$nama, $status, $alasan] = array_pad(explode('::', $item, 3), 3, '');
            if ($nama !== '') {
                $dangerousByEurope[$nama] = [
                    'status' => $status,
                    'alasan' => $alasan,
                ];
            }
        }

        $segment = $resolveSegment($product);
        $product['segment'] = $segment;
        $product['hashtags'] = array_values(array_filter(explode(',', (string) $product['hashtags'])));

        if (isset($productsByCategory[$product['kategori']][$segment])) {
            $productsByCategory[$product['kategori']][$segment][] = $product;
        }
    }
} catch (Throwable $e) {
    $errorMessage = 'Koneksi database gagal. Cek config.php dan import database.sql.';
    $newsList = $dummyNews;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/index_3.css?v=<?= urlencode((string) filemtime(__DIR__ . '/assets/css/index_3.css')); ?>">
</head>
<body>
<header class="topbar">
    <div class="shell nav">
        <a class="brand" href="/">RealFoodKu</a>
        <nav>
            <a href="#berita">Berita</a>
            <a href="#produk">Produk</a>
        </nav>
    </div>
</header>

<?php if (!empty($dangerousByEurope)): ?>
<section class="risk-banner">
    <div class="shell">
        <strong>⚠️ Bahan berisiko menurut regulasi Eropa:</strong>
        <div class="risk-list">
            <?php foreach ($dangerousByEurope as $name => $info): ?>
                <span><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($info['status'], ENT_QUOTES, 'UTF-8'); ?>)</span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="hero shell">
    <h1>Katalog Gaya Marketplace Produk Harian</h1>
    <p>Tampilan depan fokus ke merk dan kategori. Detail gizi lengkap tersedia saat produk dibuka.</p>
    <form method="get" action="/" class="search">
        <input type="text" name="q" id="searchInput" placeholder="Cari produk, merk, hashtag..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        <button id="searchBtn" type="submit">Cari</button>
    </form>
    <div class="hero-actions">
        <a class="hero-link" href="<?= htmlspecialchars($featuredFoodLink, ENT_QUOTES, 'UTF-8'); ?>">Lihat Kumpulan Produk Nugget</a>
    </div>
</section>

<main class="shell">
    <?php if ($errorMessage !== ''): ?>
        <div class="alert error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section id="berita" class="block">
        <div class="head">
            <h2>Berita Dummy</h2>
            <p>Konten contoh untuk halaman depan.</p>
        </div>
        <div class="news-grid carousel" data-carousel="news">
            <?php foreach ($newsList as $news): ?>
                <article class="news-card">
                    <a class="news-link" href="<?= htmlspecialchars($news['link_url'] ?? '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                        <img class="news-image" src="<?= htmlspecialchars($news['image_url'] ?? 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&q=80', ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($news['judul'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="chip"><?= htmlspecialchars($news['kategori'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><?= htmlspecialchars($news['judul'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?= htmlspecialchars($news['ringkasan'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <small><?= htmlspecialchars($news['sumber'], ENT_QUOTES, 'UTF-8'); ?> • <?= htmlspecialchars($news['tanggal_publish'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="carousel-dots" data-dots-for="news"></div>
    </section>

    <section id="produk" class="block">
        <div class="head">
            <h2>Kategori Produk</h2>
            <p>Masing-masing kategori dibagi menjadi 3 grup produk agar mirip katalog marketplace.</p>
        </div>

        <?php foreach ($productsByCategory as $category => $segments): ?>
            <h3 class="category-title"><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php
            $flatProducts = [];
            foreach ($segments as $segmentName => $segmentProducts) {
                foreach ($segmentProducts as $segmentProduct) {
                    $segmentProduct['segment'] = $segmentName;
                    $flatProducts[] = $segmentProduct;
                }
            }
            ?>

            <?php if (empty($flatProducts)): ?>
                <div class="alert">Belum ada produk untuk kategori <?= strtolower($category); ?>.</div>
            <?php else: ?>
                <div class="product-grid carousel" data-carousel="<?= htmlspecialchars(strtolower($category), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php foreach ($flatProducts as $product): ?>
                        <?php
                        $drinkImageByName = [
                            'Soda Jeruk X' => '/assets/img/drink_soda.svg',
                            'Energy Drink Max' => '/assets/img/drink_energy.svg',
                        ];
                        $productFallbackImage = $product['kategori'] === 'Minuman'
                            ? ($drinkImageByName[$product['nama_produk']] ?? '/assets/img/drink_default.svg')
                            : 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&q=80';
                        $productImageSrc = $product['image_url'] ?: $productFallbackImage;
                        $detailUrl = $product['nama_produk'] === 'Nugget Ayam Crispy'
                            ? '/produk/nugget-ayam-crispy'
                            : '/produk/' . (int) $product['id'];
                        ?>
                        <article class="product-card">
                            <img class="product-image" src="<?= htmlspecialchars($productImageSrc, ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($productFallbackImage, ENT_QUOTES, 'UTF-8'); ?>';" alt="<?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="meta-row">
                                <span class="chip"><?= htmlspecialchars($product['segment'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="brand-name"><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <h4><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h4>
                            <p class="desc"><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>

                            <?php if (!empty($product['hashtags'])): ?>
                                <div class="tags">
                                    <?php foreach ($product['hashtags'] as $tag): ?>
                                        <span>#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn">Lihat Detail</a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-dots" data-dots-for="<?= htmlspecialchars(strtolower($category), ENT_QUOTES, 'UTF-8'); ?>"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
</main>

<footer class="footer-dark">
    <div class="shell footer-grid">
        <div>
            <h4>RealFoodKu</h4>
            <p>Portal data pangan dan minuman berbasis database.</p>
        </div>
        <div>
            <h4>Link</h4>
            <ul>
                <li><a href="#">Source</a></li>
                <li><a href="#">Github</a></li>
                <li><a href="#">Instagram</a></li>
            </ul>
        </div>
        <div>
            <h4>Navigasi</h4>
            <ul>
                <li><a href="#berita">Berita</a></li>
                <li><a href="#produk">Produk</a></li>
            </ul>
        </div>
    </div>
</footer>

<script src="/assets/js/index.js"></script>
</body>
</html>
