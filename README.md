# Money Changer Dashboard

Aplikasi PHP 8.1+ dan MySQL/MariaDB untuk operasional Money Changer. Tidak membutuhkan framework atau dependency Composer. Database harus sudah tersedia; aplikasi hanya membuat tabel aplikasi yang belum ada, mata uang awal, role/permission, dan akun administrator bila diperlukan.

## Fitur

- Dashboard KPI dan grafik tren transaksi 7 hari terakhir serta perbandingan beli/jual.
- Input transaksi manual dengan TransDate yang mencatat tanggal dan jam transaksi.
- Jenis transaksi Beli Valas (`BUY`) dan Jual Valas (`SELL`).
- Tabel transaksi bergaya Excel untuk rentang tanggal, filter, subtotal, dan edit transaksi.
- Profit otomatis per mata uang: `(kurs jual - kurs beli) × nominal valas`, selisih rate, buffer nominal, filter laporan tanggal dan jam, serta rekap Sales by User (qty, nominal per valas, IDR, profit).
- Master pelanggan yang bisa ditambah dan dipilih kembali pada input transaksi.
- Ikon avatar default untuk akun baru dan animasi loading logo Money Changer saat berpindah menu.
- Struk transaksi MONEY CHANGER T3 untuk roll thermal 58 mm dengan area cetak 48 mm; proses cetak terbuka otomatis setelah penyimpanan transaksi.
- Login/logout berbasis session, password hash, CSRF token, status akun, dan kewajiban ganti password awal.
- Role-based access control (RBAC) dan pengaturan toggle TRUE/FALSE.
- Pengelolaan pengguna, status aktif/nonaktif, konfigurasi `config/configrules.php`, dan audit log.
- PDO prepared statements, output escaping, validasi server-side, dan perlindungan dasar dari formula injection saat ekspor.

## Struktur folder

```text
money-changer-dashboard/
├── app/
│   ├── Auth.php
│   ├── Database.php
│   ├── Rbac.php
│   ├── ReportService.php
│   ├── TransactionService.php
│   ├── bootstrap.php
│   ├── domain.php
│   └── helpers.php
├── config/
│   ├── config.php
│   ├── configrules.php
│   └── database.php
├── database/
│   └── schema.sql
├── public/
│   ├── assets/css/app.css
│   ├── assets/js/app.js
│   ├── .htaccess
│   └── index.php
├── views/
│   ├── dashboard.php
│   ├── transaction_form.php
│   ├── transactions.php
│   ├── sheet.php
│   ├── reports.php
│   ├── settings.php
│   ├── roles.php
│   ├── users.php
│   ├── print.php
│   ├── receipt.php
│   ├── customers.php
│   ├── login.php
│   ├── change_password.php
│   ├── 403.php
│   └── layout.php
├── storage/
├── tests/run.php
├── .env.example
├── .gitignore
├── .htaccess
└── index.php
```

## Instalasi lokal (XAMPP)

1. Ekstrak folder `money-changer-dashboard` ke `htdocs`.
2. Pastikan Apache dan MySQL aktif di XAMPP.
3. Salin `.env.example` menjadi `.env`.
4. Sesuaikan `MC_DB_HOST`, `MC_DB_PORT`, `MC_DB_NAME`, `MC_DB_USER`, dan `MC_DB_PASS` dengan MySQL lokal.
5. Pastikan database tujuan sudah dibuat terlebih dahulu melalui phpMyAdmin/MySQL, lalu atur `MC_DB_NAME` dengan nama yang tepat. Aplikasi tidak pernah menjalankan `CREATE DATABASE`; aplikasi hanya membuat tabel aplikasi yang belum ada setelah koneksi berhasil.
6. Buka `http://localhost/money-changer-dashboard/`.
7. Pastikan `MC_ADMIN_PASSWORD` pada `.env` minimal 3 karakter. Jika tabel pengguna kosong, akun admin awal dibuat otomatis. Database yang sudah ada akan digunakan; aplikasi tidak membuat atau menghapus database.
8. Login awal (jika nilai di `.env` tidak diubah):
   - Username: `admin`
   - Password: `ChangeMe123!`
9. Sistem mewajibkan admin mengganti password saat login pertama.
10. Setelah berhasil, buka menu **Pengaturan** untuk mengatur nama aplikasi, grafik, ekspor, cetak, dan toggle RBAC.

## Troubleshooting koneksi MySQL (XAMPP)

Jika halaman setup menampilkan `Access denied for user` walaupun `.env` terlihat benar, versi ini menampilkan diagnostik lokal yang aman: lokasi `.env`, host/port/database/username yang benar-benar dibaca aplikasi, apakah password kosong atau terisi (nilainya tidak pernah ditampilkan), dan key environment server yang mengalahkan nilai `.env`.

- Pastikan `.env` berada tepat di folder `money-changer-dashboard/`, sejajar dengan `index.php` dan folder `config/` — bukan di dalam `config/` atau folder proyek bersarang.
- `MC_DB_PASS=` berarti password MySQL kosong. Itu hanya benar jika akun MySQL tersebut memang tidak memakai password.
- Jika `MC_DB_USER` atau `MC_DB_PASS` muncul pada daftar key yang dilewati, berarti environment Windows/Apache sudah menyediakan nilai dengan nama tersebut dan nilainya mengalahkan `.env`. Hapus/perbaiki environment variable itu, lalu restart Apache.
- Pastikan `MC_DB_HOST` dan `MC_DB_PORT` sesuai dengan instance MySQL yang aktif di XAMPP. Jika MySQL memakai port `3307`, jangan biarkan aplikasi mencoba `3306`.
- Diagnostik tidak menampilkan password. Jangan aktifkan `MC_DEBUG=true` di server publik; untuk debugging lokal, kembalikan ke `false` setelah selesai.

## Instalasi hosting / cPanel

- Gunakan PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql` aktif.
- Gunakan MySQL/MariaDB dan atur file `.env` dengan kredensial database.
- Idealnya jadikan folder `public/` sebagai document root. Jika tidak memungkinkan, akses melalui `index.php` di root; jangan menghapus aturan `.htaccess` yang melindungi folder konfigurasi.
- Buat/pilih database terlebih dahulu melalui cPanel atau phpMyAdmin, lalu berikan akses database tersebut ke akun MySQL. Aplikasi tidak memerlukan izin `CREATE DATABASE`; akun hanya perlu izin koneksi dan pembuatan/perubahan tabel aplikasi sesuai migrasi.
- Setelah login awal, segera ganti password admin. Jangan gunakan password default di server produksi.

## RBAC dan `configrules.php`

File `config/configrules.php` berisi role, label izin, filter rentang tanggal, jenis transaksi, metode pembayaran, serta nilai default toggle RBAC.

- `RBAC = TRUE`: izin modul diperiksa berdasarkan role di tabel `role_permissions`.
- `RBAC = FALSE`: pengguna yang sudah login dapat membuka modul operasional umum; halaman sensitif pengelolaan pengguna dan pengaturan tetap khusus admin. Login tetap wajib.
- Matriks izin awal dapat disesuaikan melalui tabel `role_permissions`.
- Role yang disediakan: `admin`, `admin_money_changer`, `user_money_changer`, `supervisor`, `pic`, `kolektor`, dan `teller`. Akun Teller dipakai untuk input transaksi; nama pembuat pada struk ditampilkan sebagai Teller.

## Ekspor Excel dan PDF

- **Ekspor Excel** menghasilkan file `.xls` kompatibel yang dapat dibuka di Microsoft Excel/LibreOffice, tanpa dependency tambahan.
- **Cetak / simpan PDF** membuka laporan dengan layout cetak. Pilih **Save as PDF / Simpan sebagai PDF** pada dialog cetak browser.
- Ekspor mengikuti filter tanggal, jam mulai/jam selesai, jenis transaksi, dan mata uang. Laporan menyertakan kurs beli/jual, selisih rate, profit, dan buffer.

## Tes

Jalankan dari folder proyek:

```bash
php tests/run.php
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

Tes unit tidak memerlukan database. Uji alur login dan transaksi secara penuh pada server dengan `pdo_mysql` dan MySQL/MariaDB aktif.


Pembaruan ini tidak mengubah file `.env` yang sudah ada. Jangan mengganti file konfigurasi lokal saat menyalin versi terbaru.

## Profit dan TransDate

Setiap transaksi menyimpan `transaction_datetime` sebagai TransDate (tanggal + jam). Nilai IDR transaksi memakai kurs beli untuk transaksi BUY dan kurs jual untuk transaksi SELL. Profit dihitung dari `(sell_rate - buy_rate) * amount_currency`, sedangkan `spread_rate` adalah `sell_rate - buy_rate`. Buffer disimpan terpisah dan tidak ikut mengubah rumus profit. Migrasi otomatis menambahkan kolom baru dan mempertahankan transaksi lama; profit untuk transaksi historis yang belum memiliki dua kurs akan bernilai nol karena data kurs pembanding sebelumnya tidak tersedia.

## Struk thermal dan auto-cut

Setelah transaksi manual disimpan, halaman struk MONEY CHANGER T3 dibuka dan memanggil dialog cetak otomatis. Layout menggunakan lebar konten sekitar 48 mm untuk roll thermal 58 mm dan menyertakan tulisan TERIMA KASIH di akhir struk. Agar kertas terpotong otomatis, pilih printer thermal 58 mm pada dialog cetak, atur panjang kertas ke mode receipt/continuous, dan aktifkan opsi **Cut paper after each job** di driver printer. Pemotongan fisik memerlukan printer yang memang dilengkapi auto-cutter; browser tidak dapat memaksa cutter pada printer yang tidak mendukungnya.

### Perbaikan tata letak struk thermal

Template struk menggunakan tabel dua kolom agar label dan nilai tidak menempel pada printer yang tidak merender `flex` dengan baik. Nama toko dipisah secara eksplisit menjadi dua baris dan catatan footer dibuat dapat membungkus baris. Saat mencetak, gunakan kertas 58 mm, area cetak 48 mm, margin None/0, skala 100%, dan matikan header/footer browser. Auto-cut tetap bergantung pada fitur cutter dan pengaturan driver printer.


## Cetak struk thermal otomatis (Windows / XAMPP)

Setelah transaksi disimpan, aplikasi mencoba mengirim struk secara langsung ke **printer default Windows** menggunakan raw ESC/POS. Dialog browser tidak dibuka jika Windows spooler menerima pekerjaan cetak. Payload menyertakan perintah full-cut ESC/POS setelah footer `TERIMA KASIH`; printer harus mendukung ESC/POS dan auto-cutter agar kertas benar-benar terpotong.

- Pastikan printer thermal sudah terpasang di Windows dan ditetapkan sebagai **default printer** satu kali. Tidak perlu membuka pengaturan printer setiap transaksi.
- Opsional: isi `MC_THERMAL_PRINTER` di `.env` dengan nama printer Windows yang tepat. Jika kosong, aplikasi memakai printer default Windows.
- Cetak langsung membutuhkan PHP Windows dengan `proc_open` aktif dan Windows PowerShell tersedia. Jika direct print gagal, halaman akan menampilkan alasan dan tombol `Cetak via Browser` sebagai cadangan.
- Beberapa driver printer tidak menerima raw ESC/POS melalui Windows spooler. Untuk printer seperti itu, gunakan mode browser atau utility print khusus vendor.
- File `.env` lokal tidak ditimpa saat memasang update.


### Aturan struk thermal
- Struk menampilkan hanya Kurs Beli untuk transaksi Beli Valas dan hanya Kurs Jual untuk transaksi Jual Valas.
- Struk tidak menampilkan Selisih Rate atau Estimasi Profit.
- Untuk pembayaran Cash, struk menampilkan uang dibayarkan/diterima dan kembalian. Untuk metode non-tunai, rincian uang tunai dan kembalian tidak ditampilkan.
- Pada transaksi Jual Valas + Cash, isi Uang Tunai Diterima; kembalian dihitung otomatis. Kolom `cash_received` ditambahkan melalui migrasi aman pada database yang sudah ada.


## Kurs master dan form transaksi dinamis

Menu Pengaturan menyediakan Kurs Master per Mata Uang. Isi kurs beli dan kurs jual untuk setiap mata uang agar transaksi manual dapat menampilkan hanya kurs yang relevan (Beli Valas menampilkan Kurs Beli; Jual Valas menampilkan Kurs Jual) dan tetap menghitung profit dari kedua sisi kurs. Kolom kurs master ditambahkan dengan migrasi aman pada tabel `currencies` yang sudah ada. Selisih rate tidak ditampilkan di form input, tabel transaksi, atau sheet; nilai tersebut tetap tersedia di menu Laporan.


### Ukuran struk thermal

Buka Pengaturan → Ekspor & Cetak → Ukuran struk thermal. Default tetap 58 × 48 mm (area cetak 48 mm). Pilihan 80 × 80 mm menggunakan roll 80 mm dengan panjang struk otomatis sesuai isi. Pilihan ini berlaku untuk tampilan struk browser dan cetak ESC/POS langsung. Struk tidak mencetak baris uang diterima/dibayarkan atau kembalian.


## Mengganti logo aplikasi

1. Login sebagai admin dan buka menu **Pengaturan**.
2. Pada bagian **Logo Aplikasi**, pilih file PNG, JPG/JPEG, atau WebP (maksimal 3 MB), lalu klik **Upload & Ganti Logo**.
3. Logo baru tampil pada sidebar dan digunakan sebagai favicon browser. Untuk kembali ke logo MC bawaan, klik **Hapus logo & Kembali ke MC**.
4. PHP/Apache harus memiliki izin tulis pada folder `public/uploads/branding`. Upload hanya menerima file gambar yang divalidasi dari MIME dan signature gambar; `.env` tidak perlu diubah.


## Notifikasi pembayaran dan pengumuman

- Setiap transaksi manual yang berhasil disimpan membuat notifikasi pembayaran untuk role `admin` (Administrator) dan `pic`. Header memeriksa notifikasi baru setiap 15 detik selama halaman dashboard terbuka.
- Notifikasi menghilang dari daftar belum dibaca setelah pengguna memilih **Tandai dibaca**; status baca disimpan per pengguna.
- Administrator dan PIC dapat membuka **Kelola Notifikasi** untuk membuat, mengubah, dan menghapus pengumuman. Pengumuman dapat ditujukan khusus kepada Teller atau semua pengguna.
- Logo aplikasi hanya dapat diganti/dihapus oleh role `admin`. Preview logo dibatasi ke 72 × 72 px dan logo sidebar ke 42 × 42 px.
- Sidebar menggunakan ikon berwarna dan animasi ringan; preferensi `prefers-reduced-motion` dihormati.

## Pembaruan UI dan Akun (Oktober 2026)

- Form transaksi: nomor referensi otomatis terisi dan read-only; TransDate juga read-only; kartu estimasi profit dan input Buffer tidak ditampilkan. Nilai Buffer lama tetap dipertahankan ketika transaksi lama diedit, sementara transaksi baru menggunakan Buffer 0.
- Laporan PDF cetak tidak lagi menampilkan kartu ringkasan jumlah transaksi, total beli/jual, profit, atau buffer. Detail transaksi tetap dicetak.
- Sidebar: kartu “Secure workspace” dihapus dan menu Input Transaksi menggunakan ikon mesin kasir.
- Loading: logo berputar lebih cepat di atas overlay transparan tanpa kartu putih.
- Offline: modal koneksi muncul ketika browser mendeteksi status offline dan menghilang ketika status online kembali.
- Pengumuman: pengumuman tetap ada pada lonceng setelah dibaca; statusnya berubah menjadi “Sudah dibaca”. Pengumuman hanya hilang jika Administrator/PIC mengubah atau menghapusnya. Notifikasi pembayaran yang telah dibaca tetap dapat hilang dari daftar belum dibaca.
- Akun Saya: pengguna dapat mengunggah/mengganti foto profil (PNG/JPG/WebP, maksimal 2 MB) dan mengganti password sendiri. Foto profil ditambahkan melalui migrasi kolom `users.profile_photo` yang aman untuk database lama.

Pemasangan tetap menggunakan database yang sudah dipilih di `.env`; aplikasi tidak membuat database baru. Backup folder aplikasi sebelum menimpa source code, pertahankan `.env` milik sendiri, dan pastikan akun database memiliki izin `CREATE TABLE`/`ALTER TABLE` untuk migrasi tabel/kolom aplikasi yang belum tersedia.

## Dashboard UI updates

- Dashboard KPI cards use distinct, soft color treatments for quicker visual scanning.
- Application copyright and base-currency metadata are displayed in the sticky top header instead of a scrolling footer.
- The Settings page includes a global interface language option: Bahasa Indonesia or English (International). The selection is stored in `app_settings` under `ui_language`; no database recreation is required.
- The login page uses a refined responsive layout with staggered text/card motion and respects the operating system's reduced-motion preference.
- The `.env` file remains local configuration and must not be overwritten during an update. Keep the existing `.env` and apply only the source files from the release archive.


## Master Kurs dan Brankas

- Menu **Master Kurs / Exchange Rates** menyimpan kurs referensi dari Frankfurter/ECB di tabel `market_rates` dan perubahan historis di `market_rate_history`. Halaman memeriksa pembaruan tiap lima menit. Sumber tersebut menerbitkan kurs referensi harian, bukan data trading tick-by-tick. Kurs beli/jual operasional di `currencies` tidak ditimpa otomatis.
- Menu **Brankas / Vault** menggunakan tabel ledger `vault_movements`. Tambahkan saldo awal untuk merekam saldo yang sudah tersedia. Penambahan IDR menambah kas; penambahan mata uang asing menambah stok valas.
- Transaksi **Beli Valas** mengurangi saldo IDR dan menambah stok valas. Transaksi **Jual Valas** mengurangi stok valas dan menambah saldo IDR. Transaksi yang kekurangan kas atau stok valas ditolak agar saldo tidak negatif. Pengeditan/penghapusan transaksi memperbarui mutasi terkait.
- Hanya Administrator atau akun yang dipilih secara khusus oleh Administrator pada bagian **Hak Penambahan Saldo** yang dapat melakukan top up. Setiap top up mencatat mutasi dan mengirim notifikasi ke Administrator serta PIC.
- Tabel baru `market_rates`, `market_rate_history`, `vault_movements`, dan `vault_deposit_permissions` dibuat secara aditif oleh migrasi aplikasi. Aplikasi tidak membuat atau menghapus database.
- Struk thermal/browser menggunakan kolom **QTY · RATE · SUBTOTAL**, total IDR, dan nama Pelanggan/Teller. Form transaksi yang tersedia saat ini tetap satu mata uang per transaksi; untuk satu struk berisi beberapa mata uang sekaligus, aplikasi memerlukan fitur input transaksi multi-item terpisah.

## PWA dan instalasi mobile

- Manifest PWA berada di `public/manifest.webmanifest`, service worker di `public/sw.js`, ikon instalasi di `public/assets/icons/`, dan halaman fallback offline di `public/offline.html`.
- Service worker hanya menyimpan aset statis dan tidak menyimpan halaman transaksi atau data akun ke cache. Fitur transaksi tetap memerlukan koneksi ke server.
- Untuk instalasi di ponsel, akses melalui `localhost` atau HTTPS yang dipercaya browser. Jangan mengganti file `.env` saat memperbarui source code.

## Multi-currency transaction lines

A single transaction reference can now contain up to 20 distinct foreign-currency lines (for example USD, AUD, and CNY). The transaction header stores the aggregate IDR total and aggregate profit for compatibility with existing reports; detailed amounts and applied rates are stored in `transaction_items`. Existing transactions are copied into one line item on first migration without changing the original transaction rows. The receipt prints every line and a single summed IDR total.

The application adds `transaction_items` to the currently selected database. It does not create a database and does not require replacing the local `.env` file.
