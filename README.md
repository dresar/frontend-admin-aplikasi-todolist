# Admin Panel untuk API Todolist

## Deskripsi
Panel admin berbasis PHP untuk mengelola API Todolist. Panel ini memungkinkan administrator untuk memantau jumlah pengguna, menambahkan admin baru, melihat daftar pengguna dan admin, serta mengelola token login yang digunakan untuk autentikasi di Postman.

## Fitur Utama
- Dashboard untuk monitoring jumlah pengguna dan admin
- Manajemen admin (tambah, edit, hapus)
- Daftar pengguna dan admin dengan informasi detail
- Tampilan token login yang digunakan di Postman
- Pengelolaan kategori dan tugas
- Log aktivitas sistem

## Teknologi yang Digunakan
- PHP (tanpa framework)
- MySQL (sesuai dengan konfigurasi database yang ada)
- HTML/CSS/JavaScript
- Bootstrap 5 untuk UI
- Font Awesome untuk ikon
- DataTables untuk tabel interaktif
- Chart.js untuk grafik
- AJAX untuk komunikasi dengan API

## Struktur Folder
```
/admin
  /assets         # CSS, JS, dan gambar
  /config         # File konfigurasi
  /includes       # File PHP yang digunakan di beberapa halaman
  /models         # Model untuk interaksi dengan database
  index.php       # Halaman utama admin
  login.php       # Halaman login admin
  logout.php      # Proses logout
  dashboard.php   # Dashboard admin
  users.php       # Manajemen pengguna
  admins.php      # Manajemen admin
  tokens.php      # Manajemen token
  categories.php  # Manajemen kategori
  tasks.php       # Manajemen tugas
  logs.php        # Log sistem
```

## Instalasi
1. Pastikan server web (Apache) dan MySQL sudah berjalan
2. Letakkan semua file di direktori web server (misalnya: `/htdocs/ADMIN/`)
3. Pastikan konfigurasi database di `config/database.php` sudah benar
4. Akses panel admin melalui browser (misalnya: `http://localhost/ADMIN/`)

## Konfigurasi
Ubah pengaturan di file `config/config.php` sesuai kebutuhan:
- `ADMIN_SECRET_CODE`: Kode rahasia untuk verifikasi admin
- `APP_NAME`: Nama aplikasi
- `BASE_URL`: URL dasar panel admin

## Autentikasi
Untuk mengakses panel admin, pengguna harus login dengan kredensial admin. Sistem menggunakan kode rahasia admin untuk verifikasi.

## Keamanan
- Validasi input untuk semua form
- Prepared statements untuk query database
- Proteksi halaman admin dengan autentikasi
- Enkripsi password dan data sensitif
- Pencatatan semua aktivitas admin dalam log sistem

## Penggunaan
1. Login menggunakan kredensial admin
2. Gunakan menu navigasi untuk mengakses berbagai fitur
3. Dashboard menampilkan statistik dan grafik
4. Halaman pengguna, admin, token, kategori, dan tugas menyediakan fungsi CRUD
5. Halaman log menampilkan semua aktivitas sistem

## Catatan Penting
- Panel admin hanya boleh diakses oleh pengguna dengan peran admin
- Semua aktivitas admin dicatat dalam log sistem
- Pastikan keamanan server web dan database