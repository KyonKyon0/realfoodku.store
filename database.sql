CREATE DATABASE IF NOT EXISTS db_realfoodku_jkt_id CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_realfoodku_jkt_id;

DROP TABLE IF EXISTS produk_hashtag;
DROP TABLE IF EXISTS hashtag;
DROP TABLE IF EXISTS produk_bahan_berbahaya;
DROP TABLE IF EXISTS kandungan_gizi;
DROP TABLE IF EXISTS bahan_berbahaya_eropa;
DROP TABLE IF EXISTS berita;
DROP TABLE IF EXISTS produk;

CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    merk VARCHAR(100) NOT NULL,
    kategori ENUM('Makanan', 'Minuman', 'Kosmetik') NOT NULL,
    is_demo TINYINT(1) NOT NULL DEFAULT 0,
    deskripsi_singkat TEXT NOT NULL,
    link_beli VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE berita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    ringkasan TEXT NOT NULL,
    kategori VARCHAR(60) NOT NULL,
    sumber VARCHAR(120) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    tanggal_publish DATE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE kandungan_gizi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    ingredient TEXT NOT NULL,
    energi_kkal DECIMAL(6,2) NOT NULL DEFAULT 0,
    gula_g DECIMAL(6,2) NOT NULL DEFAULT 0,
    garam_mg DECIMAL(8,2) NOT NULL DEFAULT 0,
    lemak_g DECIMAL(6,2) NOT NULL DEFAULT 0,
    protein_g DECIMAL(6,2) NOT NULL DEFAULT 0,
    takaran_saji VARCHAR(100) NOT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bahan_berbahaya_eropa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_bahan VARCHAR(150) NOT NULL UNIQUE,
    status_eropa VARCHAR(80) NOT NULL,
    alasan TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE produk_bahan_berbahaya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    bahan_id INT NOT NULL,
    catatan VARCHAR(255) DEFAULT '',
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (bahan_id) REFERENCES bahan_berbahaya_eropa(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_produk_bahan (produk_id, bahan_id)
) ENGINE=InnoDB;

CREATE TABLE hashtag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_tag VARCHAR(60) NOT NULL UNIQUE,
    kategori ENUM('Makanan', 'Minuman', 'Kosmetik') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE produk_hashtag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtag(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_produk_tag (produk_id, hashtag_id)
) ENGINE=InnoDB;

INSERT INTO berita (judul, ringkasan, kategori, sumber, image_url, tanggal_publish) VALUES
('Tren Label Pangan 2026', 'Banyak konsumen mulai membaca label ingredient sebelum membeli produk.', 'Edukasi', 'Tim RealFoodKu', 'https://images.unsplash.com/photo-1498837167922-ddd27525d352?w=1200&q=80', '2026-01-10'),
('Waspada Gula Tersembunyi', 'Produk minuman kemasan sering punya gula tambahan lebih dari rekomendasi harian.', 'Kesehatan', 'Tim Nutrisi Dummy', 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=1200&q=80', '2026-01-15'),
('Cara Cek Bahan Berisiko', 'Pelajari kode aditif seperti E110, E129, dan E250 sebelum checkout.', 'Tips', 'Pusat Info Dummy', 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=1200&q=80', '2026-02-02');

INSERT INTO produk (nama_produk, merk, kategori, is_demo, deskripsi_singkat, link_beli) VALUES
('Sosis Siap Saji Y', 'Merk B', 'Makanan', 0, 'Sosis instan untuk camilan cepat.', 'https://example.com/beli/sosis-y'),
('Nugget Ayam Crispy', 'Merk D', 'Makanan', 0, 'Nugget ayam beku dengan tekstur renyah.', 'https://example.com/beli/nugget-ayam'),
('Soda Jeruk X', 'Merk A', 'Minuman', 0, 'Minuman berkarbonasi rasa jeruk.', 'https://example.com/beli/soda-jeruk-x'),
('Energy Drink Max', 'Merk E', 'Minuman', 0, 'Minuman energi dengan kafein dan taurin.', 'https://example.com/beli/energy-max'),
('Glow Serum C', 'Merk Kos A', 'Kosmetik', 1, 'Serum pencerah wajah (data demo).', 'https://example.com/beli/glow-serum-c');

INSERT INTO kandungan_gizi (produk_id, ingredient, energi_kkal, gula_g, garam_mg, lemak_g, protein_g, takaran_saji) VALUES
(1, 'Daging ayam, pati tapioka, garam, penguat rasa, sodium nitrite (E250)', 190, 2, 760, 13, 9, '75 g'),
(2, 'Daging ayam, tepung roti, minyak nabati, bumbu rempah', 210, 1, 520, 14, 11, '90 g'),
(3, 'Air berkarbonasi, gula, perisa jeruk, Sunset Yellow FCF (E110), sodium benzoate', 140, 35, 35, 0, 0, '330 ml'),
(4, 'Air, gula, kafein, taurin, vitamin B kompleks, Allura Red AC (E129)', 120, 27, 180, 0, 0, '250 ml'),
(5, 'Aqua, niacinamide, fragrance, colorant sintetis', 0, 0, 0, 0, 0, '20 ml');

INSERT INTO bahan_berbahaya_eropa (nama_bahan, status_eropa, alasan) VALUES
('Sunset Yellow FCF (E110)', 'Dibatasi ketat', 'Diduga terkait hiperaktivitas pada anak jika dikonsumsi berlebih.'),
('Sodium nitrite (E250)', 'Dibatasi ketat', 'Dapat membentuk nitrosamin pada kondisi tertentu.'),
('Allura Red AC (E129)', 'Dibatasi ketat', 'Perlu peringatan khusus pada label di beberapa negara Eropa.');

INSERT INTO produk_bahan_berbahaya (produk_id, bahan_id, catatan) VALUES
(1, 2, 'Digunakan sebagai pengawet produk olahan daging.'),
(3, 1, 'Terdeteksi pada komposisi minuman.'),
(4, 3, 'Digunakan sebagai pewarna sintetis.');

INSERT INTO hashtag (nama_tag, kategori) VALUES
('sosis', 'Makanan'),
('ayam', 'Makanan'),
('sapi', 'Makanan'),
('nugget', 'Makanan'),
('jus', 'Minuman'),
('energy-drink', 'Minuman'),
('karbonasi', 'Minuman'),
('serum', 'Kosmetik'),
('skincare', 'Kosmetik'),
('demo', 'Kosmetik');

INSERT INTO produk_hashtag (produk_id, hashtag_id) VALUES
(1, 1), (1, 2),
(2, 2), (2, 4),
(3, 7),
(4, 6),
(5, 8), (5, 9), (5, 10);
