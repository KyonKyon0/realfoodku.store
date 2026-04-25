<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

$pdo = getConnection();

// Variabel sertifikasi (true/false)
$isHalal = true;
$isBpom = true;
$halalHref = 'https://example.com/sertifikat-halal-nugget';
$bpomHref = 'https://example.com/sertifikat-bpom-nugget';

$hasPriceRangeColumn = false;
$columnCheck = $pdo->query("SHOW COLUMNS FROM produk LIKE 'harga_rentang_rupiah'");
if ($columnCheck && $columnCheck->fetch()) {
    $hasPriceRangeColumn = true;
}

$hargaSelect = $hasPriceRangeColumn
    ? 'p.harga_rentang_rupiah'
    : "'Rp39.000 - Rp57.000' AS harga_rentang_rupiah";

$sql = "SELECT p.id, p.nama_produk, p.merk, p.kategori, p.deskripsi_singkat, p.image_url, p.rating_grade,
               {$hargaSelect}, kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g,
               kg.takaran_saji
        FROM produk p
        INNER JOIN kandungan_gizi kg ON kg.produk_id = p.id
        WHERE p.nama_produk = :nama
        LIMIT 1";

$stmt = $pdo->prepare($sql);
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
        :root {
            --bg: #edf6ff;
            --card: #ffffff;
            --line: #d9e6f2;
            --txt: #0f172a;
            --muted: #64748b;
            --brand: #0284c7;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: radial-gradient(circle at top left, #f7fbff 0, var(--bg) 45%, #e6f0f8 100%);
            color: var(--txt);
        }

        .wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 18px 40px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 600;
            margin-bottom: 14px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .hero-image {
            width: 100%;
            height: 360px;
            object-fit: cover;
            display: block;
        }

        .card-body {
            padding: 16px;
        }

        .title {
            margin: 0;
            font-size: 1.9rem;
            line-height: 1.2;
        }

        .meta {
            margin-top: 8px;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
        }

        .grade-A{background:#22c55e;color:#fff;} .grade-B{background:#65a30d;color:#fff;} .grade-C{background:#eab308;color:#111827;}
        .grade-D{background:#f97316;color:#fff;} .grade-E{background:#ef4444;color:#fff;} .grade-F{background:#7f1d1d;color:#fff;}

        .certs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .cert-link {
            text-decoration: none;
            font-size: 12px;
            font-weight: 800;
            border-radius: 999px;
            padding: 8px 12px;
            border: 1px solid transparent;
        }

        .cert-link.halal { background:#dcfce7; color:#166534; border-color:#86efac; }
        .cert-link.bpom { background:#dbeafe; color:#1d4ed8; border-color:#93c5fd; }

        .info-box {
            margin-top: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #f8fbff;
            padding: 12px;
        }

        .info-box h3 { margin: 0 0 8px; }

        .nutrition {
            display: grid;
            grid-template-columns: repeat(2, minmax(0,1fr));
            gap: 8px;
            font-size: 14px;
        }

        .buy-main {
            margin-top: 12px;
            width: 100%;
            border: none;
            background: linear-gradient(90deg, #0284c7, #0ea5e9);
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(2,132,199,.3);
        }

        .store-grid {
            margin-top: 12px;
            display: none;
            grid-template-columns: repeat(auto-fit,minmax(150px,1fr));
            gap: 10px;
        }

        .store-grid.show { display: grid; }

        .store {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--txt);
            border: 1px solid var(--line);
            background: #fff;
            border-radius: 10px;
            padding: 9px;
            font-weight: 600;
        }

        .store img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .hero-image { height: 260px; }
            .nutrition { grid-template-columns: 1fr; }
            .title { font-size: 1.55rem; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <a href="/" class="back-link">&larr; Kembali ke homepage</a>

    <?php if (!$product): ?>
        <div class="card"><div class="card-body"><h1>Produk tidak ditemukan</h1></div></div>
    <?php else: ?>
        <div class="layout">
            <article class="card">
                <img class="hero-image" src="<?= htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body">
                    <h1 class="title"><?= htmlspecialchars($product['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <div class="meta">Merk: <strong><?= htmlspecialchars($product['merk'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <span class="badge grade-<?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?>">Rating <?= htmlspecialchars(strtoupper($product['rating_grade']), ENT_QUOTES, 'UTF-8'); ?></span>

                    <div class="certs">
                        <?php if ($isHalal): ?>
                            <a class="cert-link halal" href="<?= htmlspecialchars($halalHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">LOGO HALAL</a>
                        <?php endif; ?>
                        <?php if ($isBpom): ?>
                            <a class="cert-link bpom" href="<?= htmlspecialchars($bpomHref, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">LOGO BPOM</a>
                        <?php endif; ?>
                    </div>

                    <div class="info-box">
                        <h3>Deskripsi & Bahan</h3>
                        <p><?= htmlspecialchars($product['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><b>Daftar bahan:</b> <?= htmlspecialchars($product['ingredient'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><b>Range harga:</b> <?= htmlspecialchars($product['harga_rentang_rupiah'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
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
                        </div>
                    </div>

                    <button class="buy-main" id="buyBtn">Beli Sekarang</button>
                    <div class="store-grid" id="storeList">
                        <?php foreach ($stores as $store): ?>
                            <a class="store" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($store['link_beli'], ENT_QUOTES, 'UTF-8'); ?>">
                                <img src="<?= htmlspecialchars($store['logo_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?>">
                                <span><?= htmlspecialchars($store['nama_toko'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
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
