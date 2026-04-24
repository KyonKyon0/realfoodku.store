<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$search = trim((string) ($_GET['q'] ?? ''));
$products = [];
$dangerousByEurope = [];
$errorMessage = '';

try {
    $pdo = getConnection();

    $sql = <<<SQL
    SELECT
        p.id,
        p.nama_produk,
        p.merk,
        p.kategori,
        p.deskripsi_singkat,
        p.link_beli,
        kg.ingredient,
        kg.energi_kkal,
        kg.gula_g,
        kg.garam_mg,
        kg.lemak_g,
        kg.protein_g,
        kg.takaran_saji,
        GROUP_CONCAT(DISTINCT bbe.nama_bahan SEPARATOR '||') AS bahan_berbahaya,
        GROUP_CONCAT(DISTINCT CONCAT(bbe.nama_bahan, '::', bbe.status_eropa, '::', bbe.alasan) SEPARATOR '||') AS detail_bahan_berbahaya
    FROM produk p
    INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
    LEFT JOIN produk_bahan_berbahaya pbb ON pbb.produk_id = p.id
    LEFT JOIN bahan_berbahaya_eropa bbe ON bbe.id = pbb.bahan_id
    WHERE (:search = '')
       OR (p.nama_produk LIKE :keyword)
       OR (p.merk LIKE :keyword)
       OR (kg.ingredient LIKE :keyword)
    GROUP BY p.id, p.nama_produk, p.merk, p.kategori, p.deskripsi_singkat, p.link_beli,
             kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g, kg.takaran_saji
    ORDER BY p.nama_produk ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':search' => $search,
        ':keyword' => '%' . $search . '%',
    ]);

    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $dangerNames = array_filter(explode('||', (string) $product['bahan_berbahaya']));
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

        $product['danger_names'] = $dangerNames;
        $product['danger_details'] = $dangerDetails;
    }
    unset($product);
} catch (Throwable $e) {
    $errorMessage = 'Koneksi database gagal. Periksa config.php dan impor database.sql.';
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
        <div class="nav-links"><a href="#">Eksplorasi</a></div>
    </div>
</header>

<?php if (!empty($dangerousByEurope)): ?>
<section class="top-warning">
    <div class="warning-inner">
        <strong>⚠️ Bahan yang dianggap berisiko menurut regulasi Eropa:</strong>
        <ul>
            <?php foreach ($dangerousByEurope as $name => $info): ?>
                <li>
                    <b><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></b>
                    (<?= htmlspecialchars($info['status'], ENT_QUOTES, 'UTF-8'); ?>) —
                    <?= htmlspecialchars($info['alasan'], ENT_QUOTES, 'UTF-8'); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<section class="hero">
    <h1>Pilih yang Asli.</h1>
    <p>Cari produk lalu lihat merk, ingredient, kandungan gizi, dan bahan berbahaya.</p>

    <div class="search-container">
        <form method="get" action="/" class="glass-search">
            <input type="text" name="q" id="searchInput" placeholder="Cari produk, merk, ingredient..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
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
        <div class="alert">Belum ada data produk. Silakan import `database.sql` dahulu.</div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <span class="category-tag"><?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h3><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h3>
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

                    <?php if (!empty($product['danger_names'])): ?>
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
</main>

<footer>
    <p>&copy; <?= date('Y'); ?> RealFoodKu. Data makanan & minuman.</p>
</footer>
<script src="/assets/js/index.js"></script>
</body>
</html>
