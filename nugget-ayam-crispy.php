<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = getConnection();

$stmt = $pdo->prepare(
    'SELECT p.id, p.nama_produk, p.merk, p.kategori, p.deskripsi_singkat, p.image_url, p.rating_grade,
            p.harga_rentang_rupiah, kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g,
            kg.takaran_saji
     FROM produk p
     INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
     WHERE p.nama_produk = :nama
     LIMIT 1'
);
$stmt->execute([':nama' => 'Nugget Ayam Crispy']);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
}

$stores = [];
if ($product) {
    $storeStmt = $pdo->prepare('SELECT nama_toko, logo_url, link_beli FROM produk_toko WHERE produk_id = :id');
    $storeStmt->execute([':id' => $product['id']]);
    $stores = $storeStmt->fetchAll();
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nugget Ayam Crispy - Detail</title>
    <style>
        body { font-family: Inter, Arial, sans-serif; margin:0; background:#eef6fb; color:#0f172a; }
        .wrap { max-width:900px; margin:0 auto; padding:24px; }
        .panel { background:#fff; border-radius:14px; border:1px solid #dbe7f0; padding:18px; }
        .image { width:100%; max-height:360px; object-fit:cover; border-radius:12px; margin:12px 0; }
        .rating { display:inline-block; padding:4px 10px; border-radius:999px; font-size:13px; font-weight:700; }
        .grade-A{background:#22c55e;color:#fff;} .grade-B{background:#65a30d;color:#fff;} .grade-C{background:#eab308;color:#111827;}
        .grade-D{background:#f97316;color:#fff;} .grade-E{background:#ef4444;color:#fff;} .grade-F{background:#7f1d1d;color:#fff;}
        .meta { color:#475569; margin:6px 0; }
        .nutri { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; background:#f8fbff; border:1px solid #dbe7f0; border-radius:10px; padding:10px; }
        .buy-btn { margin-top:14px; border:none; background:#0284c7; color:#fff; padding:10px 14px; border-radius:10px; cursor:pointer; font-weight:700; }
        .stores { margin-top:12px; display:none; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:10px; }
        .stores.show { display:grid; }
        .store { display:flex; align-items:center; gap:8px; border:1px solid #cbd5e1; border-radius:10px; padding:8px; text-decoration:none; color:#0f172a; background:#fff; }
        .store img { width:28px; height:28px; object-fit:contain; }
    </style>
</head>
<body>
<div class="wrap">
    <a href="/" style="text-decoration:none;">&larr; Kembali</a>
    <div class="panel">
        <?php if (!$product): ?>
            <h1>Produk tidak ditemukan</h1>
        <?php else: ?>
            <h1><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="meta">Merk: <b><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></b></div>
            <span class="rating grade-<?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?>">Rating <?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?></span>
            <img class="image" src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
            <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><b>Daftar bahan:</b> <?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><b>Range harga:</b> <?= htmlspecialchars($product['harga_rentang_rupiah'], ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="nutri">
                <div>Takaran: <?= htmlspecialchars($product['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div>Kalori: <?= number_format((float) $product['energi_kkal'], 0); ?> kkal</div>
                <div>Gula: <?= number_format((float) $product['gula_g'], 1); ?> g</div>
                <div>Garam: <?= number_format((float) $product['garam_mg'], 0); ?> mg</div>
                <div>Lemak: <?= number_format((float) $product['lemak_g'], 1); ?> g</div>
                <div>Protein: <?= number_format((float) $product['protein_g'], 1); ?> g</div>
            </div>

            <button class="buy-btn" id="buyBtn">Beli Sekarang</button>
            <div class="stores" id="storeList">
                <?php foreach ($stores as $store): ?>
                    <a class="store" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($store['link_beli'], ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?= htmlspecialchars($store['logo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?>">
                        <span><?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    const buyBtn = document.getElementById('buyBtn');
    const storeList = document.getElementById('storeList');
    if (buyBtn && storeList) {
        buyBtn.addEventListener('click', () => {
            storeList.classList.toggle('show');
        });
    }
</script>
</body>
</html>
