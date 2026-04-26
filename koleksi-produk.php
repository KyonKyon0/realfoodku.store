<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$variant = (string) ($_GET['variant'] ?? 'nugget-ayam-crispy');

$variantConfig = [
    'nugget-ayam-crispy' => [
        'title' => 'Kumpulan Produk Makanan',
        'subtitle' => 'Halaman ini menampilkan grup Nugget, Sosis, dan Bakso dengan fitur sorting.',
        'category' => 'Makanan',
        'filters' => [
            ['field' => 'p.nama_produk', 'value' => '%nugget%'],
            ['field' => 'p.nama_produk', 'value' => '%sosis%'],
            ['field' => 'p.nama_produk', 'value' => '%bakso%'],
        ],
        'fallback_image' => 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&q=80',
    ],
    'diary-milk-original' => [
        'title' => 'Kumpulan Produk Minuman',
        'subtitle' => 'Halaman ini menampilkan grup Soda, Energy Drink, dan Susu dengan fitur sorting.',
        'category' => 'Minuman',
        'filters' => [
            ['field' => 'p.nama_produk', 'value' => '%soda%'],
            ['field' => 'p.nama_produk', 'value' => '%energy%'],
            ['field' => 'p.nama_produk', 'value' => '%milk%'],
            ['field' => 'p.merk', 'value' => '%susu%'],
        ],
        'fallback_image' => '/assets/img/drink_default.svg',
    ],
];

if (!isset($variantConfig[$variant])) {
    http_response_code(404);
    $variant = 'nugget-ayam-crispy';
}

$sort = (string) ($_GET['sort'] ?? 'gula_asc');
$allowedSort = [
    'gula_asc' => 'kg.gula_g ASC, p.nama_produk ASC',
    'kalsium_desc' => 'kg.kalsium_mg DESC, p.nama_produk ASC',
    'komposisi_desc' => 'kg.komposisi_utama_persen DESC, p.nama_produk ASC',
    'komposisi_asc' => 'kg.komposisi_utama_persen ASC, p.nama_produk ASC',
];
if (!isset($allowedSort[$sort])) {
    $sort = 'gula_asc';
}

$config = $variantConfig[$variant];
$products = [];
$errorMessage = '';

try {
    $pdo = getConnection();
    $orderBy = $allowedSort[$sort];

    $whereParts = [];
    $params = [':category' => $config['category']];

    foreach ($config['filters'] as $index => $filter) {
        $placeholder = ':filter_' . $index;
        $whereParts[] = $filter['field'] . ' LIKE ' . $placeholder;
        $params[$placeholder] = $filter['value'];
    }

    $whereSql = implode(' OR ', $whereParts);

    $sql = "SELECT p.id, p.nama_produk, p.merk, p.image_url, p.deskripsi_singkat, p.harga_rentang_rupiah,
                   kg.gula_g, kg.kalsium_mg, kg.komposisi_utama_persen, kg.takaran_saji
            FROM produk p
            INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
            WHERE p.kategori = :category AND ({$whereSql})
            ORDER BY {$orderBy}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    $errorMessage = 'Gagal memuat kumpulan produk. Pastikan database sudah diimport.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body { margin:0; font-family: Inter, Arial, sans-serif; background:#eef5fb; color:#0f172a; }
        .wrap { max-width: 1100px; margin:0 auto; padding: 24px 18px 40px; }
        .back { text-decoration:none; font-weight:700; color:#0f172a; }
        .title { margin:6px 0 4px; }
        .subtitle { color:#475569; margin:0; }
        .toolbar { margin-top: 14px; background:#fff; border:1px solid #d6e3ee; border-radius:12px; padding:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        select { border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px; }
        .btn { border:none; background:#0ea5e9; color:#fff; border-radius:8px; padding:8px 12px; font-weight:700; cursor:pointer; }
        .grid { margin-top:16px; display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:14px; }
        .card { background:#fff; border:1px solid #d6e3ee; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; }
        .card img { width:100%; height:170px; object-fit:cover; background:#f8fafc; }
        .body { padding:12px; display:flex; flex-direction:column; gap:8px; }
        .brand { color:#475569; font-size:14px; }
        .facts { background:#f8fbff; border:1px solid #dbe7f0; border-radius:10px; padding:8px; font-size:13px; display:grid; gap:4px; }
        .open { margin-top:auto; text-decoration:none; background:#0284c7; color:#fff; text-align:center; padding:9px 11px; border-radius:9px; font-weight:700; }
        .alert { background:#fff; border:1px dashed #c7d8e5; padding:14px; border-radius:12px; margin-top:12px; }
    </style>
</head>
<body>
<div class="wrap">
    <a class="back" href="/">&larr; Kembali ke homepage</a>
    <h1 class="title"><?= htmlspecialchars($config['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="subtitle"><?= htmlspecialchars($config['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>

    <form method="get" class="toolbar">
        <input type="hidden" name="variant" value="<?= htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?>">
        <label for="sort"><strong>Urutkan:</strong></label>
        <select name="sort" id="sort">
            <option value="gula_asc" <?= $sort === 'gula_asc' ? 'selected' : ''; ?>>Gula terendah</option>
            <option value="kalsium_desc" <?= $sort === 'kalsium_desc' ? 'selected' : ''; ?>>Kalsium tertinggi</option>
            <option value="komposisi_desc" <?= $sort === 'komposisi_desc' ? 'selected' : ''; ?>>Komposisi Utama terbanyak</option>
            <option value="komposisi_asc" <?= $sort === 'komposisi_asc' ? 'selected' : ''; ?>>Komposisi Utama terendah</option>
        </select>
        <button class="btn" type="submit">Terapkan</button>
    </form>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php elseif (empty($products)): ?>
        <div class="alert">Belum ada produk untuk ditampilkan.</div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $product): ?>
                <?php $fallbackImage = $config['fallback_image']; ?>
                <article class="card">
                    <img src="<?= htmlspecialchars($product['image_url'] ?: $fallbackImage, ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($fallbackImage, ENT_QUOTES, 'UTF-8'); ?>';" alt="<?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="body">
                        <strong><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span class="brand">Merk: <?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="brand">Harga: <?= htmlspecialchars((string) ($product['harga_rentang_rupiah'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="facts">
                            <span>Takaran saji: <?= htmlspecialchars($product['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>Gula: <?= number_format((float) $product['gula_g'], 1); ?> g</span>
                            <span>Kalsium: <?= number_format((float) $product['kalsium_mg'], 0); ?> mg</span>
                            <span>Komposisi utama: <?= number_format((float) $product['komposisi_utama_persen'], 0); ?>%</span>
                        </div>
                        <a class="open" href="/produk/<?= (int) $product['id']; ?>">Buka Detail Merk</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
