<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$id = (int) ($_GET['id'] ?? 0);
$pdo = getConnection();

$stmt = $pdo->prepare(
    'SELECT
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
        GROUP_CONCAT(DISTINCT h.nama_tag ORDER BY h.nama_tag SEPARATOR ",") AS hashtags
     FROM produk p
     INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
     LEFT JOIN produk_hashtag ph ON ph.produk_id = p.id
     LEFT JOIN hashtag h ON h.id = ph.hashtag_id
     WHERE p.id = :id
     GROUP BY p.id, p.nama_produk, p.merk, p.kategori, p.is_demo, p.deskripsi_singkat, p.link_beli,
              kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g, kg.takaran_saji'
);
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

$dangerousIngredients = [];
if ($product) {
    $product['hashtags'] = array_filter(explode(',', (string) $product['hashtags']));

    $dangerStmt = $pdo->prepare(
        'SELECT bbe.nama_bahan, bbe.status_eropa, bbe.alasan
         FROM produk_bahan_berbahaya pbb
         INNER JOIN bahan_berbahaya_eropa bbe ON bbe.id = pbb.bahan_id
         WHERE pbb.produk_id = :id'
    );
    $dangerStmt->execute([':id' => $id]);
    $dangerousIngredients = $dangerStmt->fetchAll();
} else {
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
        .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:bold; }
        .badge.demo { background:#fef3c7; color:#b45309; }
        .badge.live { background:#dcfce7; color:#166534; }
        .section { margin-top: 16px; }
        .ingredient { background: #ecfeff; border-left: 4px solid #06b6d4; padding: 12px; border-radius: 8px; white-space: pre-line; }
        .nutrition { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .hashtags { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .hashtags span { background:#e0f2fe; color:#075985; border-radius:999px; font-size:12px; padding:4px 8px; }
        .danger { background: #fff7ed; border: 1px solid #fdba74; border-radius: 8px; padding: 12px; }
        .danger ul { margin: 8px 0 0; padding-left: 20px; }
        .btn { display: inline-block; margin-top: 14px; text-decoration: none; background: #16a34a; color: #fff; padding: 10px 14px; border-radius: 8px; }
        .back { text-decoration: none; display: inline-block; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="container">
    <a href="/" class="back">&larr; Kembali ke halaman depan</a>

    <div class="panel">
        <?php if (!$product): ?>
            <h1>Produk tidak ditemukan</h1>
            <p>Data produk tidak tersedia.</p>
        <?php else: ?>
            <h1><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="meta">Kategori: <?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php if ((int) $product['is_demo'] === 1): ?>
                <span class="badge demo">Demo</span>
            <?php else: ?>
                <span class="badge live">Live</span>
            <?php endif; ?>
            <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>

            <?php if (!empty($product['hashtags'])): ?>
                <div class="hashtags">
                    <?php foreach ($product['hashtags'] as $tag): ?>
                        <span>#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <h3>Ingredient</h3>
                <div class="ingredient"><?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <div class="section">
                <h3>Kandungan Gizi</h3>
                <div class="nutrition">
                    <div><b>Takaran saji:</b> <?= htmlspecialchars($product['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div><b>Energi:</b> <?= number_format((float) $product['energi_kkal'], 0); ?> kkal</div>
                    <div><b>Gula:</b> <?= number_format((float) $product['gula_g'], 1); ?> g</div>
                    <div><b>Garam:</b> <?= number_format((float) $product['garam_mg'], 0); ?> mg</div>
                    <div><b>Lemak:</b> <?= number_format((float) $product['lemak_g'], 1); ?> g</div>
                    <div><b>Protein:</b> <?= number_format((float) $product['protein_g'], 1); ?> g</div>
                </div>
            </div>

            <?php if (!empty($dangerousIngredients)): ?>
                <div class="section danger">
                    <h3>Bahan berisiko menurut Eropa</h3>
                    <ul>
                        <?php foreach ($dangerousIngredients as $danger): ?>
                            <li><b><?= htmlspecialchars($danger['nama_bahan'], ENT_QUOTES, 'UTF-8'); ?></b> (<?= htmlspecialchars($danger['status_eropa'], ENT_QUOTES, 'UTF-8'); ?>) — <?= htmlspecialchars($danger['alasan'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <a class="btn" href="<?= htmlspecialchars($product['link_beli'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Beli Produk</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
