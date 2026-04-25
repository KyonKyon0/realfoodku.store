# realfoodku.store

Website PHP + MySQL bertema data makanan dan minuman dengan halaman depan berita dummy.

## Fitur
- URL tanpa `.php` (contoh: `/produk/1`).
- Halaman depan (`index.php`) menampilkan **berita dummy** terlebih dahulu.
- Berita di halaman depan memakai data dari tabel `berita` lengkap dengan gambar card (`image_url`), dan otomatis pakai dummy jika tabel kosong.
- Produk yang ditampilkan di homepage fokus kategori **Makanan** dan **Minuman** (ditarik dari database) dan tiap produk memiliki gambar dummy (`image_url`).
- Di PC, kartu produk tampil 3 item per viewport lalu bisa di-scroll horizontal untuk melihat sisanya.
- Tiap produk memiliki hashtag kategori (contoh: `#sosis`, `#ayam`, `#energy-drink`).
- Banner kuning paling atas untuk bahan berisiko menurut regulasi Eropa.
- Footer hitam gelap dengan link dummy: Source, Github, Instagram, dll.

## Struktur database (harus sesuai SQL)
- `produk`: data produk + merk + kategori + flag demo + `image_url` produk.
- `berita`: berita dummy untuk halaman depan.
- `kandungan_gizi`: ingredient + nilai gizi per produk.
- `bahan_berbahaya_eropa`: master bahan berisiko/dibatasi.
- `produk_bahan_berbahaya`: relasi produk dengan bahan berisiko.
- `hashtag`: master hashtag per kategori.
- `produk_hashtag`: relasi produk dengan hashtag.

## Struktur file utama
- `.htaccess` : rewrite URL tanpa `.php`.
- `index.php` : halaman depan berita + kategori + search + produk.
- `detail.php` : detail ingredient, gizi, hashtag, bahan berisiko.
- `assets/css/index.css` : style halaman utama + footer gelap.
- `assets/js/index.js` : interaksi UI ringan.
- `database.sql` : schema + data awal.
- `config.php` : konfigurasi DB.
- `db.php` : koneksi PDO.

## Cara menjalankan
1. Import database:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Sesuaikan `config.php` jika host/user/password berbeda.
3. Jalankan:
   ```bash
   php -S localhost:8000
   ```
4. Buka:
   - Home: `http://localhost:8000/`
   - Detail: `http://localhost:8000/produk/1`

## Mapping sesuai panel hosting Anda
- **Database name**: `db_realfoodku_jkt_id`
- **Username**: `DB_REALFOODKU_JKT_ID`

Jika password berubah, update `db_pass` pada `config.php`.
