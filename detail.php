<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$id = (int) ($_GET['id'] ?? 0);
$pdo = getConnection();

$stmt = $pdo->prepare(
    'SELECT id, nama_produk, merk, kategori, deskripsi_singkat, ingredient, link_beli, rating_data
     FROM produk
     WHERE id = :id'
);
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8') : 'Produk tidak ditemukan'; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f8; color: #1f2937; }
        .container { max-width: 760px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .meta { color: #6b7280; margin: 6px 0; }
        .ingredient { background: #ecfeff; border-left: 4px solid #06b6d4; padding: 12px; border-radius: 8px; white-space: pre-line; }
        .btn { display: inline-block; margin-top: 14px; text-decoration: none; background: #16a34a; color: #fff; padding: 10px 14px; border-radius: 8px; }
        .back { text-decoration: none; display: inline-block; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <a href="/" class="back">&larr; Kembali ke daftar</a>

    <div class="panel">
        <?php if (!$product): ?>
            <h1>Produk tidak ditemukan</h1>
            <p>Data produk tidak tersedia.</p>
        <?php else: ?>
            <h1><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="meta">Kategori: <?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="meta">Skor data: <?= number_format((float) $product['rating_data'], 1); ?>/10</div>
            <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>

            <h3>Ingredient</h3>
            <div class="ingredient"><?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></div>

            <a class="btn" href="<?= htmlspecialchars($product['link_beli'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Beli Produk</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
