# Stoqa - Sistem Manajemen Stok Gudang & Katalog Grosir

<p align="center">
  <img src="public/images/stoqa-logo.svg" alt="Stoqa Logo" width="320">
</p>

<p align="center">
  <strong>Sistem Manajemen Inventaris Gudang Multi-Satuan berbasis FIFO (First-In, First-Out), Landed Cost Otomatis, Reservasi Stok Anti-Overselling, dan Katalog Penjualan Grosir B2B / B2C.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%20%7C%208.4%20%7C%208.5-777BB4?logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Laravel-12%20%2F%2013-FF2D20?logo=laravel&logoColor=white" alt="Laravel Framework">
  <img src="https://img.shields.io/badge/PostgreSQL-16%2B-4169E1?logo=postgresql&logoColor=white" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Filament-v5-F59E0B?logo=filament&logoColor=white" alt="Filament Admin">
  <img src="https://img.shields.io/badge/TailwindCSS-v4-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Pest%20Tests-48%20Passed%20(100%25)-10B981?logo=pest&logoColor=white" alt="Pest Tests">
</p>

---

## 🌟 Fitur Utama

### 1. Manajemen Stok FIFO (First-In, First-Out) & Landed Cost
- **Batch Tracking Otomatis**: Setiap penerimaan barang masuk (*Inbound Order*) menghasilkan nomor batch fisik tersendiri dengan mencatat timestamp penerimaan (`received_at`), gudang penyimpanan, jumlah awal, dan sisa stok.
- **Kalkulasi Landed Cost Proporsional**: Ongkos kirim dan biaya operasional penerimaan barang dialokasikan secara proporsional terhadap nilai barang tanpa selisih sen, menghasilkan HPP (*Cost of Goods Sold*) yang akurat.
- **Konsumsi Batch Terurut**: Setiap barang keluar (penjualan / konsumsi) secara otomatis memotong antrean batch fisik paling awal yang masih memiliki kuota.

### 2. Multi-Satuan Kemasan & Konversi Otomatis
- **Satuan Dasar Fisik**: Menggunakan satuan terkecil (misal: *Pcs*, *Pouch*, *Botol*, *Bungkus*) untuk konsistensi perhitungan stok dan FIFO di basis data.
- **Satuan Kemasan Jual**: Mendukung pendaftaran satuan kemasan lebih besar (misal: *Dus*, *Karton*, *Ball*) dengan faktor konversi otomatis ke satuan dasar.

### 3. Multi-Tier Pricing & Harga Khusus Pelanggan
- **Harga Bertingkat (Tier Pricing)**: Penentuan diskon kuantitas bertingkat (misal: Beli 1-5 dus @ Rp100.000, 6-20 dus @ Rp95.000).
- **Harga Khusus Toko / Mitra**: Pengaturan harga eksklusif untuk pelanggan tertentu (`CustomerPrice`) yang memiliki kontrak harga khusus.

### 4. Reservasi Stok Real-Time (Anti-Overselling)
- **Tiga Metrik Stok Presisi**:
  - `on_hand`: Total stok fisik yang berada di rak gudang.
  - `reserved`: Jumlah stok yang sedang ditahan oleh pesanan aktif yang belum dibayar / dalam pengiriman.
  - `available`: Stok bersih siap jual (`on_hand - reserved`).
- **Penguncian Baris Transaksional**: Menggunakan `lockForUpdate()` di PostgreSQL untuk mencegah kondisi balapan (*race condition*) ketika banyak pembeli checkout secara bersamaan.
- **Pembersihan Otomatis**: Reservasi yang kedaluwarsa dilepaskan kembali ke stok tersedia melalui task terjadwal.

### 5. Stock Opname & Penyesuaian Fisik Terintegrasi
- **Opname Kurang**: Memotong sisa batch FIFO tertua secara otomatis.
- **Opname Lebih**: Membuat batch baru dengan mereferensikan landed cost batch terakhir atau harga taksiran.
- **Proteksi Kuota Hold**: Sistem menolak opname yang menurunkan stok fisik di bawah kuota pesanan yang sedang di-hold.
- **Audit Mutasi Append-Only**: Seluruh perubahan stok dicatat di tabel `stock_movements` tanpa overwrite.

### 6. Panel Admin Modern (Filament v5) & Tema Kustom Stoqa
- Tampilan panel admin elegan berpalet Teal Stoqa dengan font Nunito dan mode terang/gelap.
- **Modal Preview Produk Interaktif**: Menampilkan identitas produk, kartu ringkasan stok, tabel pesanan yang sedang menahan stok (lengkap dengan invoice & nama pembeli), antrean batch masuk FIFO, satuan kemasan, dan tombol langsung ke halaman edit.
- Formulir transaksi satu kolom penuh yang lega dan ramah pengguna di berbagai ukuran layar.

### 7. Katalog Belanja Pengguna & Mobile Responsive
- Halaman etalase publik yang cepat berbasis Livewire 3 dan Tailwind CSS 4.
- Bottom navigation bar responsif di perangkat seluler untuk navigasi katalog, keranjang, pesanan, dan akun.

---

## 🛠️ Prasyarat Sistem (Prerequisites)

Sebelum menjalankan proyek, pastikan lingkungan pengembangan Anda telah terpasang:
- **PHP** >= 8.3 (Disarankan PHP 8.4 atau 8.5)
  - Ekstensi PHP wajib: `pdo_pgsql`, `bcmath`, `mbstring`, `xml`, `curl`, `intl`, `fileinfo`
- **Composer** >= 2.x
- **Node.js** >= 20.x & **npm**
- **PostgreSQL** >= 16

---

## 🚀 Panduan Instalasi Langkah Demi Langkah (Step-by-Step)

### 1. Clone Repository
```bash
git clone https://github.com/Fortinenta/Stoqa.git
cd Stoqa
```

### 2. Install Dependensi PHP & JavaScript
```bash
# Install paket backend Composer
composer install

# Install dependensi frontend NPM
npm install
```

### 3. Konfigurasi Lingkungan (`.env`)
Salin berkas template lingkungan `.env.example`:
```bash
cp .env.example .env
```
Buka berkas `.env` dan sesuaikan kredensial koneksi PostgreSQL Anda:
```env
APP_NAME=Stoqa
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=stok_gudang
DB_USERNAME=postgres
DB_PASSWORD=rahasia_anda
```

Generate application key:
```bash
php artisan key:generate
```

### 4. Migrasi Database & Seeding Data Awal
Buat basis data di PostgreSQL (misal bernama `stok_gudang`), kemudian jalankan migrasi dan seeder:
```bash
# Jalankan migrasi seluruh tabel
php artisan migrate

# Isi data master awal & data simulasi toko sembako
php artisan db:seed
```
*Seeder akan membuat Gudang Utama, Kategori Produk, Akun Admin, Staf Gudang, Akun Pelanggan Contoh, Data Pemasok, serta Batch Awal Stok Fisik.*

### 5. Kompilasi Aset Frontend (Tailwind 4 & Filament Theme)
```bash
# Untuk produksi
npm run build

# Atau jalankan dev server saat pengembangan
npm run dev
```

### 6. Jalankan Server Aplikasi
Buka terminal baru dan jalankan server pengembangan Laravel:
```bash
php artisan serve
```
Aplikasi kini dapat diakses di peramban web:
- **Katalog Belanja Publik**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Panel Admin Filament**: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)

---

## 🔑 Akun Bawaan (Default Credentials)

Setelah menjalankan `php artisan db:seed`, Anda dapat login menggunakan akun berikut:

| Peran (Role) | Email | Password | Akses |
|---|---|---|---|
| **Super Admin** | `admin@example.com` | `password` | Panel Admin Filament (`/admin`) |
| **Staf Gudang** | `staf@example.com` | `password` | Panel Admin Filament (`/admin`) |
| **Pelanggan Umum (Budi)** | `budi@example.com` | `password` | Katalog Belanja (`/`) & Pesanan Saya |
| **Pelanggan Grosir (Toko Berkah)** | `toko.berkah@example.com` | `password` | Katalog Belanja (Multi-Tier Pricing) |

---

## 🧪 Menjalankan Automated Testing (Pest PHP)

Proyek ini dilengkapi dengan 48 automated feature & unit tests yang menguji seluruh aspek inti (FIFO, alokasi landed cost, konkurensi, hold kuota, dan antarmuka Filament):

```bash
# Jalankan seluruh rangkaian test
php vendor/bin/pest

# Jalankan pengujian inti persediaan FIFO saja
php vendor/bin/pest tests/Feature/Stock/StockServiceTest.php

# Jalankan pengecekan standar kode (Laravel Pint)
php vendor/bin/pint --test
```

---

## 📁 Struktur Direktori Penting

```text
app/
├── Enums/                 # Enum status order, pembayaran, reservasi, & tipe mutasi
├── Exceptions/            # Custom exceptions (InsufficientStock, InconsistentStock, InvalidAdjustment)
├── Filament/              # Sumber daya Panel Admin Filament v5 (Resource, Page, Table, Schema)
│   └── Resources/
│       ├── Products/      # Form produk, tabel, aksi opname, & modal preview
│       ├── Orders/        # Form pesanan penjualan & pemotongan FIFO otomatis
│       ├── InboundOrders/ # Form penerimaan barang & alokasi landed cost
│       └── ...
├── Models/                # 18 Model Eloquent beserta relasi
└── Services/
    ├── StockService.php          # Core engine pergerakan stok, FIFO, & landed cost
    ├── ReservationService.php    # Manajemen reservasi stok & hold expiry
    └── PricingCalculator.php     # Kalkulator tier pricing & harga kemasan
resources/
├── css/
│   ├── app.css                   # Token tema Tailwind CSS 4 Stoqa
│   └── filament/admin/theme.css  # Styling tema kustom Panel Admin
└── views/
    ├── layouts/app.blade.php     # Layout utama katalog & mobile navigation
    └── filament/resources/products/preview-modal.blade.php # View modal preview produk
```

---

## 📄 Lisensi
Proyek ini dilisensikan di bawah lisensi terbuka [MIT](LICENSE).
