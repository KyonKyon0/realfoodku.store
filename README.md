# realfoodku.store

Website PHP + MySQL bertema data makanan dan minuman.

## Fitur
- URL tanpa `.php` (contoh: `/produk/1`).
- Search produk berdasarkan nama produk, merk, atau kategori.
- Halaman detail menampilkan ingredient.
- Tombol link beli untuk masing-masing produk.

## Struktur utama
- `.htaccess` : rewrite URL agar tanpa ekstensi `.php`.
- `index.php` : daftar produk + pencarian.
- `assets/css/index.css` : style halaman utama.
- `assets/js/index.js` : interaksi UI ringan halaman utama.
- `detail.php` : detail ingredient dan link beli.
- `database.sql` : schema + data awal MySQL.
- `config.php` : konfigurasi database.
- `db.php` : koneksi PDO.

## Cara menjalankan
1. Buat database dan isi data:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Ubah kredensial di `config.php` jika perlu.
3. Jalankan server PHP:
   ```bash
   php -S localhost:8000
   ```
4. Buka:
   - Home: `http://localhost:8000/`
   - Detail: `http://localhost:8000/produk/1`

> Catatan: Untuk rewrite `.htaccess` bekerja penuh, gunakan Apache dengan `mod_rewrite` aktif.


## Konfigurasi database yang Anda kirim
File `config.php` sudah diisi dengan:
- `db_name`: `DB_REALFOODKU_JKT_ID`
- `db_pass`: `ab08e5af3bf52_REG_JKT_ID`

Jika username database Anda **bukan** `root`, ubah nilai `db_user` di `config.php`.
