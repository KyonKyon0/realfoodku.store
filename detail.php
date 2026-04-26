<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$id = (int) ($_GET['id'] ?? 0);
$product = null;
$dangerousIngredients = [];
$stores = [];

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare(
        'SELECT
            p.id,
            p.nama_produk,
            p.merk,
            p.kategori,
            p.is_demo,
            p.deskripsi_singkat,
            p.image_url,
            p.rating_grade,
            p.link_beli,
            p.harga_rentang_rupiah,
            kg.ingredient,
            kg.energi_kkal,
            kg.gula_g,
            kg.garam_mg,
            kg.lemak_g,
            kg.protein_g,
            kg.kalsium_mg,
            kg.komposisi_utama_persen,
            kg.takaran_saji,
            GROUP_CONCAT(DISTINCT h.nama_tag ORDER BY h.nama_tag SEPARATOR ",") AS hashtags
         FROM produk p
         INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
         LEFT JOIN produk_hashtag ph ON ph.produk_id = p.id
         LEFT JOIN hashtag h ON h.id = ph.hashtag_id
         WHERE p.id = :id
         GROUP BY p.id, p.nama_produk, p.merk, p.kategori, p.is_demo, p.deskripsi_singkat, p.image_url, p.rating_grade, p.link_beli, p.harga_rentang_rupiah,
                  kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g, kg.kalsium_mg, kg.komposisi_utama_persen, kg.takaran_saji'
    );
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

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

        $storeStmt = $pdo->prepare('SELECT nama_toko, logo_url, link_beli FROM produk_toko WHERE produk_id = :id');
        $storeStmt->execute([':id' => $id]);
        $stores = $storeStmt->fetchAll();
    } else {
        http_response_code(404);
    }
} catch (Throwable $e) {
    http_response_code(500);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8') : 'Produk tidak ditemukan'; ?></title>
    <style>
        body { margin:0; font-family: Inter, Arial, sans-serif; background: radial-gradient(circle at top left, #f7fbff 0, #edf6ff 45%, #e6f0f8 100%); color:#0f172a; }
        .wrap { max-width: 1100px; margin:0 auto; padding:24px 18px 40px; }
        .back-link { display:inline-flex; align-items:center; gap:6px; text-decoration:none; color:#0f172a; font-weight:600; margin-bottom:14px; }
        .layout { display:grid; grid-template-columns:1.2fr 1fr; gap:18px; }
        .card { background:#fff; border:1px solid #d9e6f2; border-radius:18px; box-shadow:0 10px 28px rgba(15, 23, 42, 0.08); overflow:hidden; }
        .hero-image { width:100%; height:360px; object-fit:cover; display:block; }
        .card-body { padding:16px; }
        .title { margin:0; font-size:1.9rem; line-height:1.2; }
        .meta { margin-top:8px; color:#64748b; }
        .badge { display:inline-flex; padding:4px 11px; border-radius:999px; font-size:12px; font-weight:700; margin-top:10px; }
        .badge.demo { background:#fef3c7; color:#b45309; }
        .badge.live { background:#dcfce7; color:#166534; }
        .rating { display:inline-flex; margin-left:8px; padding:4px 11px; border-radius:999px; font-size:12px; font-weight:700; }
        .grade-A{background:#22c55e;color:#fff;} .grade-B{background:#65a30d;color:#fff;} .grade-C{background:#eab308;color:#111827;}
        .grade-D{background:#f97316;color:#fff;} .grade-E{background:#ef4444;color:#fff;} .grade-F{background:#7f1d1d;color:#fff;}
        .info-box { margin-top:14px; border:1px solid #d9e6f2; border-radius:12px; background:#f8fbff; padding:12px; }
        .info-box h3 { margin:0 0 8px; }
        .nutrition { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; font-size:14px; }
        .hashtags { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .hashtags span { background:#e0f2fe; color:#075985; border-radius:999px; font-size:12px; padding:4px 8px; }
        .danger { background:#fff7ed; border:1px solid #fdba74; border-radius:8px; padding:12px; }
        .danger ul { margin:8px 0 0; padding-left:20px; }
        .buy-main { margin-top:12px; width:100%; border:none; background:linear-gradient(90deg,#0284c7,#0ea5e9); color:#fff; padding:12px 14px; border-radius:12px; cursor:pointer; font-weight:700; }
        .store-grid { margin-top:12px; display:none; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }
        .store-grid.show { display:grid; }
        .store { display:flex; align-items:center; gap:8px; text-decoration:none; color:#0f172a; border:1px solid #d9e6f2; background:#fff; border-radius:10px; padding:9px; font-weight:600; }
        .store img { width:28px; height:28px; object-fit:contain; }
        .buy-link { display:inline-block; margin-top:10px; text-decoration:none; background:#16a34a; color:#fff; padding:10px 14px; border-radius:8px; font-weight:700; }
        @media (max-width: 900px) {
            .layout { grid-template-columns:1fr; }
            .hero-image { height:260px; }
            .nutrition { grid-template-columns:1fr; }
            .title { font-size:1.55rem; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <a href="/" class="back-link">&larr; Kembali ke homepage</a>

    <?php if (!$product): ?>
        <div class="card"><div class="card-body"><h1>Produk tidak ditemukan</h1><p>Data produk tidak tersedia.</p></div></div>
    <?php else: ?>
        <?php $fallbackImage = $product['kategori'] === 'Minuman' ? '/assets/img/drink_default.svg' : 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&q=80'; ?>
        <div class="layout">
            <article class="card">
                <img class="hero-image" src="<?= htmlspecialchars((string) ($product['image_url'] ?: $fallbackImage), ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='<?= htmlspecialchars($fallbackImage, ENT_QUOTES, 'UTF-8'); ?>';" alt="<?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body">
                    <h1 class="title"><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <div class="meta">Kategori: <?= htmlspecialchars($product['kategori'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ((int) $product['is_demo'] === 1): ?>
                        <span class="badge demo">Demo</span>
                    <?php else: ?>
                        <span class="badge live">Live</span>
                    <?php endif; ?>
                    <span class="rating grade-<?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?>">Rating <?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?></span>

                    <div class="info-box">
                        <h3>Deskripsi & Bahan</h3>
                        <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><b>Ingredient:</b> <?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><b>Range harga:</b> <?= htmlspecialchars((string) ($product['harga_rentang_rupiah'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <?php if (!empty($product['hashtags'])): ?>
                        <div class="hashtags">
                            <?php foreach ($product['hashtags'] as $tag): ?>
                                <span>#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <aside class="card">
                <div class="card-body">
                    <div class="info-box">
                        <h3>Informasi Gizi</h3>
                        <div class="nutrition">
                            <div>Takaran: <?= htmlspecialchars($product['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div>Kalori: <?= number_format((float) $product['energi_kkal'], 0); ?> kkal</div>
                            <div>Gula: <?= number_format((float) $product['gula_g'], 1); ?> g</div>
                            <div>Garam: <?= number_format((float) $product['garam_mg'], 0); ?> mg</div>
                            <div>Lemak: <?= number_format((float) $product['lemak_g'], 1); ?> g</div>
                            <div>Protein: <?= number_format((float) $product['protein_g'], 1); ?> g</div>
                            <div>Kalsium: <?= number_format((float) $product['kalsium_mg'], 0); ?> mg</div>
                            <div>Komposisi utama: <?= number_format((float) $product['komposisi_utama_persen'], 0); ?>%</div>
                        </div>
                    </div>

                    <?php if (!empty($dangerousIngredients)): ?>
                        <div class="info-box danger">
                            <h3>Bahan berisiko menurut Eropa</h3>
                            <ul>
                                <?php foreach ($dangerousIngredients as $danger): ?>
                                    <li><b><?= htmlspecialchars($danger['nama_bahan'], ENT_QUOTES, 'UTF-8'); ?></b> (<?= htmlspecialchars($danger['status_eropa'], ENT_QUOTES, 'UTF-8'); ?>) — <?= htmlspecialchars($danger['alasan'], ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($stores)): ?>
                        <button class="buy-main" id="buyBtn">Pilih Marketplace</button>
                        <div class="store-grid" id="storeList">
                            <?php foreach ($stores as $store): ?>
                                <a class="store" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($store['link_beli'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <img src="<?= htmlspecialchars($store['logo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <span><?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <a class="buy-link" href="<?= htmlspecialchars($product['link_beli'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Beli Produk</a>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    <?php endif; ?>
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
