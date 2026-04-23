CREATE DATABASE IF NOT EXISTS realfoodku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE realfoodku;

DROP TABLE IF EXISTS produk;
CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    merk VARCHAR(100) NOT NULL,
    kategori ENUM('Makanan', 'Minuman') NOT NULL,
    deskripsi_singkat TEXT NOT NULL,
    ingredient TEXT NOT NULL,
    link_beli VARCHAR(255) NOT NULL,
    rating_data DECIMAL(3,1) NOT NULL DEFAULT 7.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO produk (nama_produk, merk, kategori, deskripsi_singkat, ingredient, link_beli, rating_data) VALUES
('Indomie Goreng', 'Indomie', 'Makanan', 'Mi instan goreng populer dengan rasa gurih manis.', 'Tepung terigu, minyak nabati, garam, gula, bumbu perisa, cabai bubuk.', 'https://www.tokopedia.com/search?st=product&q=indomie%20goreng', 8.7),
('Oreo Original', 'Oreo', 'Makanan', 'Biskuit sandwich coklat dengan krim vanila.', 'Tepung terigu, gula, minyak sawit, kakao bubuk, pengembang, lesitin kedelai.', 'https://www.tokopedia.com/search?st=product&q=oreo%20original', 8.1),
('Ultra Milk Full Cream', 'Ultra Milk', 'Minuman', 'Susu UHT full cream siap minum.', 'Susu sapi segar, vitamin A, vitamin D3.', 'https://www.tokopedia.com/search?st=product&q=ultra%20milk%20full%20cream', 8.9),
('Teh Botol Sosro', 'Sosro', 'Minuman', 'Minuman teh melati dalam kemasan botol.', 'Air, gula, ekstrak teh melati.', 'https://www.tokopedia.com/search?st=product&q=teh%20botol%20sosro', 8.3),
('Pocari Sweat', 'Pocari', 'Minuman', 'Minuman ion pengganti cairan tubuh.', 'Air, gula, asam sitrat, natrium klorida, kalium klorida, magnesium karbonat.', 'https://www.tokopedia.com/search?st=product&q=pocari%20sweat', 8.6),
('Chitato Sapi Panggang', 'Chitato', 'Makanan', 'Keripik kentang dengan rasa sapi panggang.', 'Kentang, minyak nabati, bumbu sapi panggang, gula, garam, penyedap rasa.', 'https://www.tokopedia.com/search?st=product&q=chitato%20sapi%20panggang', 7.8);
