# realfoodku.store

Website PHP + MySQL bertema data makanan dan minuman.

## Fitur
- URL tanpa `.php` (contoh: `/produk/1`).
- Search produk berdasarkan nama produk, merk, atau ingredient.
- Menampilkan **merk, ingredient, kandungan gizi** di listing dan detail.
- Menampilkan banner kuning paling atas berisi daftar **bahan berisiko menurut Eropa**.
- Tombol link beli untuk masing-masing produk.

## Struktur database terbaru
- `produk`: data utama produk & merk.
- `kandungan_gizi`: ingredient + nilai gizi per produk.
- `bahan_berbahaya_eropa`: master bahan yang dibatasi/dianggap berisiko di Eropa.
- `produk_bahan_berbahaya`: relasi produk dengan bahan berisiko.

## Struktur file utama
- `.htaccess` : rewrite URL agar tanpa ekstensi `.php`.
- `index.php` : daftar produk + pencarian + banner bahan berisiko.
- `detail.php` : detail ingredient, kandungan gizi, bahan berisiko, dan link beli.
- `assets/css/index.css` : style halaman utama.
- `assets/js/index.js` : interaksi UI ringan halaman utama.
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


## Troubleshooting koneksi database
- Error **"Koneksi database gagal"** paling sering terjadi karena nama database tidak sama.
- Di project ini nama DB harus konsisten: `db_realfoodku_jkt_id` (lihat `config.php` dan baris `CREATE DATABASE` / `USE` di `database.sql`).
- Setelah memastikan sama, jalankan lagi:
  ```bash
  mysql -u root -p < database.sql
  ```
- Jika masih gagal, cek `db_user` dan `db_pass` pada `config.php` sesuai akun MySQL server Anda.


## Mapping sesuai panel hosting Anda
Dari screenshot panel DB:
- **Database name**: `db_realfoodku_jkt_id`
- **Username**: `DB_REALFOODKU_JKT_ID`

Nilai tersebut sudah dipakai di `config.php`. Jika password di panel berubah, update `db_pass` di `config.php` sesuai password terbaru.
