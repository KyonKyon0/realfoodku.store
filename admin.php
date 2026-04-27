<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/db.php';

const ADMIN_ID = '51424063';
const ADMIN_PASSWORD = '0$Car070311';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['csrf_token'];
$errorMessage = '';
$successMessage = '';
$isLoggedIn = (bool) ($_SESSION['is_admin_logged_in'] ?? false);
$editId = (int) ($_GET['edit'] ?? 0);

$productForm = [
    'id' => 0,
    'nama_produk' => '',
    'merk' => '',
    'kategori' => 'Makanan',
    'deskripsi_singkat' => '',
    'image_url' => '',
    'rating_grade' => 'B',
    'harga_rentang_rupiah' => '',
    'link_beli' => '',
    'ingredient' => '',
    'energi_kkal' => '0',
    'gula_g' => '0',
    'garam_mg' => '0',
    'lemak_g' => '0',
    'protein_g' => '0',
    'kalsium_mg' => '0',
    'komposisi_utama_persen' => '0',
    'takaran_saji' => '',
];

$validateCsrf = static function (): bool {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token']);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if (!$validateCsrf()) {
        $errorMessage = 'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif ($action === 'login') {
        $id = trim((string) ($_POST['admin_id'] ?? ''));
        $password = (string) ($_POST['admin_password'] ?? '');

        if ($id === ADMIN_ID && hash_equals(ADMIN_PASSWORD, $password)) {
            $_SESSION['is_admin_logged_in'] = true;
            $isLoggedIn = true;
            $successMessage = 'Login admin berhasil.';
        } else {
            $errorMessage = 'ID admin atau password salah.';
        }
    } elseif ($action === 'logout') {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        $csrfToken = $_SESSION['csrf_token'];
        $isLoggedIn = false;
        $successMessage = 'Anda telah logout.';
    } elseif ($isLoggedIn) {
        try {
            $pdo = getConnection();

            if ($action === 'save') {
                $productForm['id'] = (int) ($_POST['id'] ?? 0);
                $productForm['nama_produk'] = trim((string) ($_POST['nama_produk'] ?? ''));
                $productForm['merk'] = trim((string) ($_POST['merk'] ?? ''));
                $productForm['kategori'] = (string) ($_POST['kategori'] ?? 'Makanan');
                $productForm['deskripsi_singkat'] = trim((string) ($_POST['deskripsi_singkat'] ?? ''));
                $productForm['image_url'] = trim((string) ($_POST['image_url'] ?? ''));
                $productForm['rating_grade'] = strtoupper(trim((string) ($_POST['rating_grade'] ?? 'B')));
                $productForm['harga_rentang_rupiah'] = trim((string) ($_POST['harga_rentang_rupiah'] ?? ''));
                $productForm['link_beli'] = trim((string) ($_POST['link_beli'] ?? ''));
                $productForm['ingredient'] = trim((string) ($_POST['ingredient'] ?? ''));
                $productForm['energi_kkal'] = (string) ($_POST['energi_kkal'] ?? '0');
                $productForm['gula_g'] = (string) ($_POST['gula_g'] ?? '0');
                $productForm['garam_mg'] = (string) ($_POST['garam_mg'] ?? '0');
                $productForm['lemak_g'] = (string) ($_POST['lemak_g'] ?? '0');
                $productForm['protein_g'] = (string) ($_POST['protein_g'] ?? '0');
                $productForm['kalsium_mg'] = (string) ($_POST['kalsium_mg'] ?? '0');
                $productForm['komposisi_utama_persen'] = (string) ($_POST['komposisi_utama_persen'] ?? '0');
                $productForm['takaran_saji'] = trim((string) ($_POST['takaran_saji'] ?? ''));

                if ($productForm['nama_produk'] === '' || $productForm['merk'] === '' || $productForm['takaran_saji'] === '') {
                    throw new RuntimeException('Nama produk, merk, dan takaran saji wajib diisi.');
                }

                if (!in_array($productForm['kategori'], ['Makanan', 'Minuman', 'Kosmetik'], true)) {
                    throw new RuntimeException('Kategori produk tidak valid.');
                }

                if (!preg_match('/^[A-F]$/', $productForm['rating_grade'])) {
                    throw new RuntimeException('Rating grade harus A sampai F.');
                }

                $pdo->beginTransaction();

                if ($productForm['id'] > 0) {
                    $updateProduct = $pdo->prepare(
                        'UPDATE produk
                         SET nama_produk = :nama_produk,
                             merk = :merk,
                             kategori = :kategori,
                             deskripsi_singkat = :deskripsi,
                             image_url = :image_url,
                             rating_grade = :rating_grade,
                             harga_rentang_rupiah = :harga,
                             link_beli = :link_beli
                         WHERE id = :id'
                    );
                    $updateProduct->execute([
                        ':nama_produk' => $productForm['nama_produk'],
                        ':merk' => $productForm['merk'],
                        ':kategori' => $productForm['kategori'],
                        ':deskripsi' => $productForm['deskripsi_singkat'],
                        ':image_url' => $productForm['image_url'],
                        ':rating_grade' => $productForm['rating_grade'],
                        ':harga' => $productForm['harga_rentang_rupiah'],
                        ':link_beli' => $productForm['link_beli'],
                        ':id' => $productForm['id'],
                    ]);

                    $checkNutrition = $pdo->prepare('SELECT id FROM kandungan_gizi WHERE produk_id = :id LIMIT 1');
                    $checkNutrition->execute([':id' => $productForm['id']]);

                    if ($checkNutrition->fetch()) {
                        $updateNutrition = $pdo->prepare(
                            'UPDATE kandungan_gizi
                             SET ingredient = :ingredient,
                                 energi_kkal = :energi,
                                 gula_g = :gula,
                                 garam_mg = :garam,
                                 lemak_g = :lemak,
                                 protein_g = :protein,
                                 kalsium_mg = :kalsium,
                                 komposisi_utama_persen = :komposisi,
                                 takaran_saji = :takaran
                             WHERE produk_id = :id'
                        );
                        $updateNutrition->execute([
                            ':ingredient' => $productForm['ingredient'],
                            ':energi' => (float) $productForm['energi_kkal'],
                            ':gula' => (float) $productForm['gula_g'],
                            ':garam' => (float) $productForm['garam_mg'],
                            ':lemak' => (float) $productForm['lemak_g'],
                            ':protein' => (float) $productForm['protein_g'],
                            ':kalsium' => (float) $productForm['kalsium_mg'],
                            ':komposisi' => (float) $productForm['komposisi_utama_persen'],
                            ':takaran' => $productForm['takaran_saji'],
                            ':id' => $productForm['id'],
                        ]);
                    } else {
                        $insertNutrition = $pdo->prepare(
                            'INSERT INTO kandungan_gizi
                             (produk_id, ingredient, energi_kkal, gula_g, garam_mg, lemak_g, protein_g, kalsium_mg, komposisi_utama_persen, takaran_saji)
                             VALUES
                             (:produk_id, :ingredient, :energi, :gula, :garam, :lemak, :protein, :kalsium, :komposisi, :takaran)'
                        );
                        $insertNutrition->execute([
                            ':produk_id' => $productForm['id'],
                            ':ingredient' => $productForm['ingredient'],
                            ':energi' => (float) $productForm['energi_kkal'],
                            ':gula' => (float) $productForm['gula_g'],
                            ':garam' => (float) $productForm['garam_mg'],
                            ':lemak' => (float) $productForm['lemak_g'],
                            ':protein' => (float) $productForm['protein_g'],
                            ':kalsium' => (float) $productForm['kalsium_mg'],
                            ':komposisi' => (float) $productForm['komposisi_utama_persen'],
                            ':takaran' => $productForm['takaran_saji'],
                        ]);
                    }

                    $pdo->commit();
                    $successMessage = 'Produk berhasil diperbarui.';
                } else {
                    $insertProduct = $pdo->prepare(
                        'INSERT INTO produk
                         (nama_produk, merk, kategori, is_demo, deskripsi_singkat, image_url, rating_grade, harga_rentang_rupiah, link_beli)
                         VALUES
                         (:nama_produk, :merk, :kategori, 0, :deskripsi, :image_url, :rating_grade, :harga, :link_beli)'
                    );
                    $insertProduct->execute([
                        ':nama_produk' => $productForm['nama_produk'],
                        ':merk' => $productForm['merk'],
                        ':kategori' => $productForm['kategori'],
                        ':deskripsi' => $productForm['deskripsi_singkat'],
                        ':image_url' => $productForm['image_url'],
                        ':rating_grade' => $productForm['rating_grade'],
                        ':harga' => $productForm['harga_rentang_rupiah'],
                        ':link_beli' => $productForm['link_beli'],
                    ]);
                    $newId = (int) $pdo->lastInsertId();

                    $insertNutrition = $pdo->prepare(
                        'INSERT INTO kandungan_gizi
                         (produk_id, ingredient, energi_kkal, gula_g, garam_mg, lemak_g, protein_g, kalsium_mg, komposisi_utama_persen, takaran_saji)
                         VALUES
                         (:produk_id, :ingredient, :energi, :gula, :garam, :lemak, :protein, :kalsium, :komposisi, :takaran)'
                    );
                    $insertNutrition->execute([
                        ':produk_id' => $newId,
                        ':ingredient' => $productForm['ingredient'],
                        ':energi' => (float) $productForm['energi_kkal'],
                        ':gula' => (float) $productForm['gula_g'],
                        ':garam' => (float) $productForm['garam_mg'],
                        ':lemak' => (float) $productForm['lemak_g'],
                        ':protein' => (float) $productForm['protein_g'],
                        ':kalsium' => (float) $productForm['kalsium_mg'],
                        ':komposisi' => (float) $productForm['komposisi_utama_persen'],
                        ':takaran' => $productForm['takaran_saji'],
                    ]);

                    $pdo->commit();
                    $successMessage = 'Produk baru berhasil ditambahkan.';
                }

                $productForm['id'] = 0;
                $editId = 0;
            } elseif ($action === 'delete') {
                $deleteId = (int) ($_POST['id'] ?? 0);
                if ($deleteId <= 0) {
                    throw new RuntimeException('ID produk tidak valid.');
                }

                $deleteStmt = $pdo->prepare('DELETE FROM produk WHERE id = :id');
                $deleteStmt->execute([':id' => $deleteId]);
                $successMessage = 'Produk berhasil dihapus.';
                if ($editId === $deleteId) {
                    $editId = 0;
                }
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = $e->getMessage();
        }
    }
}

$productRows = [];
if ($isLoggedIn) {
    try {
        $pdo = getConnection();

        if ($editId > 0) {
            $editStmt = $pdo->prepare(
                'SELECT p.id, p.nama_produk, p.merk, p.kategori, p.deskripsi_singkat, p.image_url, p.rating_grade, p.harga_rentang_rupiah, p.link_beli,
                        kg.ingredient, kg.energi_kkal, kg.gula_g, kg.garam_mg, kg.lemak_g, kg.protein_g, kg.kalsium_mg, kg.komposisi_utama_persen, kg.takaran_saji
                 FROM produk p
                 LEFT JOIN kandungan_gizi kg ON kg.produk_id = p.id
                 WHERE p.id = :id'
            );
            $editStmt->execute([':id' => $editId]);
            $editRow = $editStmt->fetch();
            if ($editRow) {
                foreach ($productForm as $key => $value) {
                    if (array_key_exists($key, $editRow) && $editRow[$key] !== null) {
                        $productForm[$key] = (string) $editRow[$key];
                    }
                }
                $productForm['id'] = (int) $editRow['id'];
            }
        }

        $listStmt = $pdo->query(
            'SELECT p.id, p.nama_produk, p.merk, p.kategori, p.rating_grade,
                    kg.gula_g, kg.kalsium_mg, kg.komposisi_utama_persen
             FROM produk p
             LEFT JOIN kandungan_gizi kg ON kg.produk_id = p.id
             ORDER BY p.id DESC'
        );
        $productRows = $listStmt->fetchAll();
    } catch (Throwable $e) {
        $errorMessage = 'Gagal memuat data admin: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin RealFoodKu</title>
    <style>
        body { margin:0; font-family: Inter, Arial, sans-serif; background:#eef5fb; color:#0f172a; }
        .wrap { max-width: 1150px; margin: 0 auto; padding: 22px 18px 36px; }
        .header { display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; }
        .card { background:#fff; border:1px solid #d7e3ee; border-radius:14px; padding:16px; margin-top:14px; }
        .grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .full { grid-column: 1 / -1; }
        label { display:block; font-size:13px; font-weight:700; margin-bottom:5px; }
        input, select, textarea { width:100%; border:1px solid #c5d4e0; border-radius:8px; padding:9px 10px; font:inherit; }
        textarea { min-height:84px; }
        .btn { border:none; border-radius:9px; padding:10px 13px; font-weight:700; cursor:pointer; }
        .btn.primary { background:#0284c7; color:#fff; }
        .btn.danger { background:#ef4444; color:#fff; }
        .btn.gray { background:#e2e8f0; color:#0f172a; }
        .row-btns { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border-bottom:1px solid #e2e8f0; padding:8px; text-align:left; font-size:13px; }
        .alert { margin-top:12px; border-radius:10px; padding:10px 12px; }
        .alert.error { background:#fee2e2; color:#991b1b; }
        .alert.success { background:#dcfce7; color:#166534; }
        .small { color:#475569; font-size:12px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1>Admin RealFoodKu</h1>
        <a href="/" class="btn gray" style="text-decoration:none;">&larr; Kembali ke Homepage</a>
    </div>

    <?php if ($errorMessage !== ''): ?><div class="alert error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if ($successMessage !== ''): ?><div class="alert success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

    <?php if (!$isLoggedIn): ?>
        <section class="card" style="max-width:420px;">
            <h2>Login Admin</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="login">
                <div>
                    <label for="admin_id">ID Admin</label>
                    <input id="admin_id" name="admin_id" required>
                </div>
                <div style="margin-top:10px;">
                    <label for="admin_password">Password</label>
                    <input id="admin_password" name="admin_password" type="password" required>
                </div>
                <div class="row-btns">
                    <button class="btn primary" type="submit">Login</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="card">
            <div class="header">
                <h2 style="margin:0;">Form CRUD Produk</h2>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="logout">
                    <button class="btn gray" type="submit">Logout</button>
                </form>
            </div>

            <form method="post" class="grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) $productForm['id']; ?>">

                <div>
                    <label>Nama Produk</label>
                    <input name="nama_produk" required value="<?= htmlspecialchars((string) $productForm['nama_produk'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Merk</label>
                    <input name="merk" required value="<?= htmlspecialchars((string) $productForm['merk'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Kategori</label>
                    <select name="kategori">
                        <?php foreach (['Makanan', 'Minuman', 'Kosmetik'] as $categoryOption): ?>
                            <option value="<?= $categoryOption; ?>" <?= $productForm['kategori'] === $categoryOption ? 'selected' : ''; ?>><?= $categoryOption; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Rating Grade (A-F)</label>
                    <input name="rating_grade" maxlength="1" value="<?= htmlspecialchars((string) $productForm['rating_grade'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Harga Rentang</label>
                    <input name="harga_rentang_rupiah" value="<?= htmlspecialchars((string) $productForm['harga_rentang_rupiah'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label>Link Beli</label>
                    <input name="link_beli" value="<?= htmlspecialchars((string) $productForm['link_beli'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="full">
                    <label>URL Gambar</label>
                    <input name="image_url" value="<?= htmlspecialchars((string) $productForm['image_url'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="full">
                    <label>Deskripsi Singkat</label>
                    <textarea name="deskripsi_singkat"><?= htmlspecialchars((string) $productForm['deskripsi_singkat'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="full">
                    <label>Ingredient</label>
                    <textarea name="ingredient"><?= htmlspecialchars((string) $productForm['ingredient'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div><label>Takaran Saji</label><input name="takaran_saji" required value="<?= htmlspecialchars((string) $productForm['takaran_saji'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Energi (kkal)</label><input name="energi_kkal" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['energi_kkal'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Gula (g)</label><input name="gula_g" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['gula_g'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Garam (mg)</label><input name="garam_mg" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['garam_mg'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Lemak (g)</label><input name="lemak_g" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['lemak_g'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Protein (g)</label><input name="protein_g" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['protein_g'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Kalsium (mg)</label><input name="kalsium_mg" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['kalsium_mg'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div><label>Komposisi Utama (%)</label><input name="komposisi_utama_persen" type="number" step="0.01" value="<?= htmlspecialchars((string) $productForm['komposisi_utama_persen'], ENT_QUOTES, 'UTF-8'); ?>"></div>

                <div class="full row-btns">
                    <button class="btn primary" type="submit"><?= (int) $productForm['id'] > 0 ? 'Update Produk' : 'Tambah Produk'; ?></button>
                    <a class="btn gray" style="text-decoration:none;" href="/admin">Reset Form</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2>Data Produk</h2>
            <p class="small">Gunakan tombol Edit untuk mengisi form di atas. Hapus akan menghapus produk dan data gizi terkait.</p>
            <table>
                <thead>
                <tr>
                    <th>ID</th><th>Nama</th><th>Merk</th><th>Kategori</th><th>Rating</th><th>Gula</th><th>Kalsium</th><th>Komposisi</th><th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($productRows as $row): ?>
                    <tr>
                        <td><?= (int) $row['id']; ?></td>
                        <td><?= htmlspecialchars($row['nama_produk'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['merk'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($row['rating_grade'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= number_format((float) ($row['gula_g'] ?? 0), 1); ?></td>
                        <td><?= number_format((float) ($row['kalsium_mg'] ?? 0), 0); ?></td>
                        <td><?= number_format((float) ($row['komposisi_utama_persen'] ?? 0), 0); ?>%</td>
                        <td>
                            <a class="btn gray" style="text-decoration:none; padding:6px 9px;" href="/admin?edit=<?= (int) $row['id']; ?>">Edit</a>
                            <form method="post" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('Yakin hapus produk ini?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id']; ?>">
                                <button class="btn danger" style="padding:6px 9px;" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</div>
</body>
</html>
