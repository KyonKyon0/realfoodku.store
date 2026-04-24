CREATE DATABASE IF NOT EXISTS db_realfoodku_jkt_id CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_realfoodku_jkt_id;

DROP TABLE IF EXISTS produk_bahan_berbahaya;
DROP TABLE IF EXISTS kandungan_gizi;
DROP TABLE IF EXISTS bahan_berbahaya_eropa;
DROP TABLE IF EXISTS produk;

CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    merk VARCHAR(100) NOT NULL,
    kategori ENUM('Makanan', 'Minuman') NOT NULL,
    deskripsi_singkat TEXT NOT NULL,
    link_beli VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

INSERT INTO produk (nama_produk, merk, kategori, deskripsi_singkat, link_beli) VALUES
('Soda Jeruk X', 'Merk A', 'Minuman', 'Minuman berkarbonasi rasa jeruk.', 'https://www.tokopedia.com/search?st=product&q=soda%20jeruk%20x'),
('Sosis Siap Saji Y', 'Merk B', 'Makanan', 'Sosis instan untuk camilan cepat.', 'https://www.tokopedia.com/search?st=product&q=sosis%20siap%20saji%20y'),
('Permen Warna Mix', 'Merk C', 'Makanan', 'Permen buah berwarna-warni.', 'https://www.tokopedia.com/search?st=product&q=permen%20warna%20mix');

INSERT INTO kandungan_gizi (produk_id, ingredient, energi_kkal, gula_g, garam_mg, lemak_g, protein_g, takaran_saji) VALUES
(1, 'Air berkarbonasi, gula, perisa jeruk, Sunset Yellow FCF (E110), sodium benzoate', 140, 35, 35, 0, 0, '330 ml'),
(2, 'Daging ayam, pati tapioka, garam, penguat rasa, sodium nitrite (E250)', 190, 2, 760, 13, 9, '75 g'),
(3, 'Gula, sirup glukosa, pewarna Allura Red AC (E129), perisa sintetis', 160, 28, 15, 0, 0, '40 g');

INSERT INTO bahan_berbahaya_eropa (nama_bahan, status_eropa, alasan) VALUES
('Sunset Yellow FCF (E110)', 'Dibatasi ketat', 'Diduga terkait hiperaktivitas pada anak jika dikonsumsi berlebih.'),
('Sodium nitrite (E250)', 'Dibatasi ketat', 'Dapat membentuk nitrosamin pada kondisi tertentu.'),
('Allura Red AC (E129)', 'Dibatasi ketat', 'Perlu peringatan khusus pada label di beberapa negara Eropa.');

INSERT INTO produk_bahan_berbahaya (produk_id, bahan_id, catatan) VALUES
(1, 1, 'Terdeteksi pada komposisi minuman.'),
(2, 2, 'Digunakan sebagai pengawet produk olahan daging.'),
(3, 3, 'Digunakan sebagai pewarna sintetis.');
