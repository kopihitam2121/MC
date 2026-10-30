(() => {
  const formatIDR = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value) || 0);

  const englishText = {"OPERATIONS PANEL":"OPERATIONS PANEL","MENU UTAMA":"MAIN MENU","Keluar":"Sign Out","Workspace":"Workspace","Notifikasi":"Notifications","belum dibaca":"unread","Kelola":"Manage","Tidak ada notifikasi baru.":"No new notifications.","Sudah dibaca":"Read","Tandai dibaca":"Mark as read","Money operations · IDR base currency":"Money operations · IDR base currency","Money Changer T3":"Money Changer T3","Memuat":"Loading","Instal Aplikasi":"Install App","Koneksi Offline":"Offline Connection","Koneksi jaringan terputus. Periksa Wi-Fi atau kabel jaringan, lalu coba kembali.":"Your network connection is unavailable. Check your Wi-Fi or network cable, then try again.","Coba Lagi":"Try Again","Logo aplikasi":"Application logo","Buka menu":"Open menu","Pengaturan akun sendiri":"Manage your account","Foto profil":"Profile photo","Memuat halaman":"Loading page","Logo Money Changer":"Money Changer logo","Dashboard":"Dashboard","Transaksi":"Transactions","Input Transaksi":"New Transaction","Tabel Transaksi":"Transaction Table","Laporan":"Reports","Master Pelanggan":"Customer Directory","Pengaturan":"Settings","Kelola Notifikasi":"Notification Center","Role & Izin":"Roles & Permissions","Pengguna":"Users","Akun Saya":"My Account","Akses ditolak":"Access denied","Akun Anda tidak memiliki izin untuk membuka halaman atau melakukan tindakan ini. Jika akses seharusnya tersedia, hubungi administrator.":"Your account does not have permission to open this page or perform this action. Contact an administrator if you believe access should be granted.","Kembali ke Dashboard":"Back to Dashboard","RINGKASAN OPERASIONAL":"OPERATIONAL OVERVIEW","Pantau aktivitas money changer dan ringkasan transaksi terbaru.":"Monitor foreign exchange activity and review recent transactions.","Lihat Laporan":"View Reports","HARI INI":"TODAY","Jumlah Transaksi":"Transaction Count","Transaksi tercatat hari ini":"Transactions recorded today","Total Beli Valas":"Total Foreign Currency Purchases","Nilai ekuivalen rupiah":"IDR equivalent value","Total Jual Valas":"Total Foreign Currency Sales","BULAN INI":"THIS MONTH","Akumulasi Transaksi":"Transaction Volume","transaksi bulan ini":"transactions this month","Tren Transaksi":"Transaction Trend","Nilai transaksi per hari · 7 hari terakhir":"Daily transaction value · last 7 days","Grafik dinonaktifkan":"Chart disabled","Aktifkan kembali dari menu Pengaturan.":"Enable it again from Settings.","Komposisi Hari Ini":"Today's Breakdown","Perbandingan nilai beli dan jual valas":"Comparison of foreign currency purchases and sales","Mata Uang Aktif":"Active Currencies","Kontribusi nilai transaksi 30 hari":"Transaction value contribution over 30 days","Detail ↗":"Details ↗","Belum ada transaksi":"No transactions yet","Data mata uang akan muncul setelah transaksi dicatat.":"Currency data will appear after a transaction is recorded.","Transaksi Terbaru":"Recent Transactions","Aktivitas terakhir yang tersimpan di sistem":"Latest activity saved in the system","Semua Transaksi":"All Transactions","Belum ada data transaksi":"No transaction data yet","Mulai dengan menambahkan transaksi pertama.":"Start by adding your first transaction.","Tanggal":"Date","Referensi":"Reference","Jenis":"Type","Mata Uang":"Currency","Nominal Valas":"Foreign Currency Amount","Kurs":"Exchange Rate","Total IDR":"Total (IDR)","Grafik transaksi tujuh hari terakhir":"Transaction chart for the last seven days","Grafik perbandingan transaksi beli dan jual hari ini":"Chart comparing today's purchase and sale transactions","INPUT DATA":"TRANSACTION ENTRY","TransDate wajib diisi. Kurs mengikuti jenis transaksi dan profit dihitung otomatis.":"Transaction date and time are required. The exchange rate follows the transaction type, and profit is calculated automatically.","← Kembali":"← Back","Informasi Transaksi":"Transaction Information","TransDate mencatat tanggal sekaligus jam transaksi.":"TransDate records both the transaction date and time.","Wajib diisi":"Required","TransDate · tanggal & jam":"TransDate · date and time","Waktu transaksi otomatis terisi dan tidak dapat diubah.":"The transaction time is filled automatically and cannot be changed.","Jenis transaksi":"Transaction Type","Beli Valas":"Buy Foreign Currency","Jual Valas":"Sell Foreign Currency","Mata uang":"Currency","Pilih mata uang":"Select a currency","Nama pelanggan":"Customer Name","Nama baru otomatis tersimpan sebagai pilihan setelah transaksi disimpan.":"New names are automatically saved as selectable options after the transaction is saved.","Kelola pelanggan":"Manage customers","Nomor referensi":"Reference Number","Nominal, Kurs & Profit":"Amount, Exchange Rate & Profit","Profit = (kurs jual − kurs beli) × nominal valas.":"Profit = (sell rate − buy rate) × foreign currency amount.","OTOMATIS":"AUTOMATIC","Rincian Mata Uang":"Currency Details","Satu transaksi dapat berisi beberapa mata uang. TransDate otomatis dan total dihitung berdasarkan seluruh baris.":"A single transaction can contain multiple currencies. TransDate is automatic and the total is calculated across all lines.","Kamu dapat menambahkan USD, AUD, CNY, atau mata uang lain dalam satu nomor transaksi.":"You can add USD, AUD, CNY, or other currencies under one transaction reference.","＋ Tambah Mata Uang":"＋ Add Currency","Hapus":"Remove","Subtotal IDR":"Subtotal (IDR)","Total transaksi semua mata uang (IDR)":"Combined transaction total (IDR)","Total seluruh subtotal mata uang":"Sum of all currency line subtotals","Valas":"Currency","Nominal valas":"Foreign Currency Amount","Kurs beli IDR":"Buy Rate (IDR)","Kurs jual IDR":"Sell Rate (IDR)","Kurs pembanding belum lengkap. Atur kurs beli dan kurs jual per mata uang pada menu Pengaturan agar perhitungan profit tetap akurat.":"The comparison rates are incomplete. Set both buy and sell rates for each currency in Settings to ensure accurate profit calculations.","Metode pembayaran":"Payment Method","Nominal uang tunai diterima (Rp)":"Cash received (IDR)","Khusus Jual Valas dengan pembayaran Cash. Nominal harus sama dengan atau lebih besar dari total transaksi.":"Only applies to foreign currency sales paid in cash. The amount must equal or exceed the transaction total.","Total transaksi dalam Rupiah":"Transaction total in IDR","Nominal valas × kurs sesuai jenis transaksi":"Foreign currency amount × rate for the selected transaction type","Catatan":"Notes","Batal":"Cancel","Pilih atau ketik nama pelanggan":"Select or enter a customer name","Contoh: 100":"Example: 100","Contoh: 15800":"Example: 15800","Contoh: 16000":"Example: 16000","Contoh: 2000000":"Example: 2000000","Catatan tambahan, jika ada":"Additional notes, if any","DATA OPERASIONAL":"OPERATIONAL DATA","Cari, tinjau, cetak struk, dan kelola catatan transaksi valuta asing.":"Search, review, print receipts, and manage foreign exchange transaction records.","▦ Tabel Data":"▦ Data Table","Dari tanggal":"From date","Jam mulai":"Start time","Sampai tanggal":"To date","Jam selesai":"End time","Semua Jenis":"All Types","Semua Mata Uang":"All Currencies","Terapkan":"Apply","Reset":"Reset","Daftar Transaksi":"Transaction List","baris ditampilkan · maksimal 500 baris":"rows displayed · maximum 500 rows","⇩ Excel":"⇩ Excel","▤ Cetak PDF":"▤ Print PDF","Transaksi tidak ditemukan":"No transactions found","Coba ubah filter tanggal atau tambahkan transaksi baru.":"Adjust the date filters or add a new transaction.","Pelanggan":"Customer","Kurs transaksi":"Transaction Rate","Profit IDR":"Profit (IDR)","Input oleh":"Entered by","Aksi":"Actions","Cetak struk thermal":"Print thermal receipt","Edit transaksi":"Edit transaction","Hapus transaksi":"Delete transaction","SPREADSHEET VIEW":"SPREADSHEET VIEW","Tampilan grid seperti Excel dengan TransDate, kurs transaksi, profit, dan buffer.":"An Excel-style grid showing TransDate, transaction rates, profit, and buffer.","Tampilkan Data":"Show Data","Jumlah baris":"Row Count","Total beli valas":"Total Foreign Currency Purchases","Total jual valas":"Total Foreign Currency Sales","Data transaksi":"Transaction Data","sampai":"to","▤ PDF":"▤ PDF","Nama Pelanggan":"Customer Name","Belum ada transaksi pada rentang tanggal ini.":"No transactions found in this date range.","TOTAL":"TOTAL","Grid ini merupakan tampilan laporan. Untuk mengubah data, gunakan tombol edit pada kolom aksi.":"This grid is a report view. To change a record, use the Edit button in the Actions column.","Cetak struk":"Print receipt","Edit":"Edit","Laporan Money Changer":"Money Changer Report","Tutup":"Close","▤ Cetak / Simpan PDF":"▤ Print / Save PDF","LAPORAN TRANSAKSI VALUTA ASING":"FOREIGN EXCHANGE TRANSACTION REPORT","Rentang:":"Period:","s.d.":"to","Dicetak pada":"Printed on","Dicetak oleh":"Printed by","Detail Transaksi":"Transaction Details","Valas":"Foreign Currency","Nominal":"Amount","Kurs Beli":"Buy Rate","Kurs Jual":"Sell Rate","Selisih":"Spread","Buffer":"Buffer","Pembayaran":"Payment","User":"User","Tidak ada transaksi untuk filter ini.":"No transactions match these filters.","TOTAL PROFIT":"TOTAL PROFIT","Disiapkan oleh,":"Prepared by,","Diperiksa oleh,":"Reviewed by,","Disetujui oleh,":"Approved by,","Dokumen dihasilkan oleh · Simpan PDF melalui menu cetak browser.":"Document generated by · Save as PDF through the browser print menu.","ANALISIS & REKAP":"ANALYSIS & SUMMARY","Laporan Transaksi":"Transaction Reports","Filter laporan berdasarkan tanggal sekaligus jam TransDate, jenis, dan mata uang.":"Filter reports by TransDate date and time, transaction type, and currency.","⇩ Ekspor Excel":"⇩ Export Excel","▤ Cetak / PDF":"▤ Print / PDF","Semua":"All","Buat Rekap":"Generate Report","Rentang aktif:":"Active period:","Total transaksi":"Total Transactions","Jumlah baris transaksi":"Transaction Row Count","Nilai beli valas":"Foreign Currency Purchase Value","Ekuivalen IDR":"IDR Equivalent","Nilai jual valas":"Foreign Currency Sales Value","Total profit terhitung":"Calculated Total Profit","Σ (kurs jual − kurs beli) × valas":"Σ (sell rate − buy rate) × foreign currency amount","Total buffer":"Total Buffer","Akumulasi buffer yang diinput":"Total buffer entered","Profit per Mata Uang":"Profit by Currency","Profit dihitung dari selisih kurs beli dan jual dikalikan nominal valas.":"Profit is calculated from the difference between the sell and buy rates multiplied by the foreign currency amount.","Belum ada data untuk direkap":"No data to summarize","Silakan ubah rentang tanggal atau filter laporan.":"Adjust the date range or report filters.","Qty transaksi":"Transaction Count","Rata-rata selisih rate":"Average Rate Spread","Beli (IDR)":"Purchases (IDR)","Jual (IDR)":"Sales (IDR)","Profit (IDR)":"Profit (IDR)","Buffer (IDR)":"Buffer (IDR)","Sales by User":"Sales by User","Jumlah transaksi jual, nominal valas, nilai IDR, dan estimasi profit per pengguna.":"Sales transaction count, foreign currency amount, IDR value, and estimated profit per user.","Belum ada transaksi jual pada rentang ini.":"No sales transactions found in this period.","Nama user":"User Name","Qty":"Qty","Nominal sales (IDR)":"Sales Amount (IDR)","baris dalam hasil filter":"rows in filtered results","Tidak ada detail transaksi pada filter ini.":"No transaction details match these filters.","Kurs beli":"Buy Rate","Kurs jual":"Sell Rate","Selisih rate":"Rate Spread","USER MANAGEMENT":"USER MANAGEMENT","Buat akun, tentukan role, dan kelola status akses pengguna.":"Create accounts, assign roles, and manage user access status.","Admin only":"Administrator only","Tambah Pengguna":"Add User","Akun baru wajib mengganti password saat login pertama.":"New users must change their password at first sign-in.","Nama lengkap":"Full Name","Username":"Username","Huruf kecil, angka, titik, underscore, atau tanda hubung.":"Use lowercase letters, numbers, periods, underscores, or hyphens.","Role":"Role","Password awal":"Initial Password","Daftar Pengguna":"User List","akun terdaftar":"registered accounts","Status":"Status","Login terakhir":"Last Sign-in","Menunggu ganti password":"Password change required","Akun Anda":"Your Account","Role menentukan menu dan tindakan yang tersedia ketika RBAC aktif. Untuk mengubah aturan role, edit matriks izin pada tabel":"Roles determine available menus and actions when RBAC is enabled. To change role rules, edit the permission matrix in the table","atau tambahkan antarmuka pengelolaan role pada pengembangan berikutnya.":"or add a role management interface in a future release.","Nama pengguna":"User Name","contoh: teller.mc01":"e.g. teller.mc01","Minimal 3 karakter":"At least 3 characters","Foto":"Photo","Ikon pengguna default":"Default user icon","ACCOUNT SETTINGS":"ACCOUNT SETTINGS","Kelola foto profil dan ubah password akun sendiri kapan saja.":"Manage your profile photo and change your password at any time.","Foto Profil":"Profile Photo","Foto hanya berlaku untuk akun Anda sendiri.":"This photo is used only for your own account.","· @":"· @","Pilih foto baru":"Choose a new photo","PNG, JPG/JPEG, atau WebP. Maksimal 2 MB. Foto akan tampil pada header dashboard.":"PNG, JPG/JPEG, or WebP. Maximum 2 MB. The photo will appear in the dashboard header.","Simpan Foto Profil":"Save Profile Photo","Ubah Password":"Change Password","Masukkan password saat ini untuk mengonfirmasi perubahan.":"Enter your current password to confirm the change.","Password saat ini":"Current Password","Password baru":"New Password","Minimal 3 karakter.":"At least 3 characters.","Konfirmasi password baru":"Confirm New Password","Simpan Password":"Save Password","Foto profil saat ini":"Current profile photo","ACCOUNT SECURITY":"ACCOUNT SECURITY","Ganti Password":"Change Password","Gunakan password yang kuat dan berbeda dari password lainnya.":"Use a strong password that you do not use elsewhere.","Perbarui kredensial":"Update Credentials","Password minimal 3 karakter.":"Password must be at least 3 characters.","Tips password aman":"Password Security Tips","Gabungkan huruf besar, huruf kecil, angka, dan simbol.":"Combine uppercase and lowercase letters, numbers, and symbols.","Jangan gunakan nama atau tanggal lahir.":"Do not use your name or date of birth.","Jangan bagikan password kepada pengguna lain.":"Do not share your password with other users.","KONFIGURASI SISTEM":"SYSTEM CONFIGURATION","Atur perilaku dashboard, ekspor laporan, dan kontrol akses.":"Configure dashboard behavior, report exports, and access controls.","Logo Aplikasi":"Application Logo","Ganti logo di sidebar dan ikon tab browser langsung dari dashboard.":"Change the sidebar logo and browser tab icon directly from the dashboard.","Logo khusus aktif":"Custom logo active","Logo default MC aktif":"Default MC logo active","Format PNG, JPG/JPEG, atau WebP. Maksimal 3 MB.":"PNG, JPG/JPEG, or WebP. Maximum 3 MB.","Disarankan gambar persegi agar tampil rapi di sidebar.":"A square image is recommended for a clean sidebar appearance.","Pilih logo baru":"Choose a new logo","↑ Upload & Ganti Logo":"↑ Upload & Replace Logo","Hapus logo & Kembali ke MC":"Remove Logo & Restore MC","Pengaturan Umum":"General Settings","Nama workspace dan pengalaman dashboard.":"Workspace name and dashboard experience.","Nama aplikasi":"Application Name","Nama yang tampil di sidebar dan laporan cetak.":"Name shown in the sidebar and printed reports.","Bahasa antarmuka":"Interface Language","Pilih bahasa untuk seluruh teks antarmuka dashboard dan halaman login.":"Choose the language for dashboard and login interface text.","Bahasa Indonesia":"Bahasa Indonesia","English (International)":"English (International)","Grafik dashboard":"Dashboard Charts","Tampilkan atau sembunyikan grafik tren transaksi.":"Show or hide the transaction trend chart.","Keamanan & Hak Akses":"Security & Access Control","Toggle RBAC mengatur pemeriksaan izin pada modul aplikasi.":"The RBAC toggle controls permission checks across application modules.","Role Based Access Control (RBAC)":"Role-Based Access Control (RBAC)","TRUE: izin modul diperiksa berdasarkan role. FALSE: akses modul umum dibuka untuk pengguna login; halaman sensitif seperti pengelolaan pengguna dan pengaturan tetap khusus admin.":"TRUE: module permissions are checked by role. FALSE: general modules are available to signed-in users; sensitive pages such as user management and settings remain administrator-only.","configrules.php → rbac_enabled_default":"configrules.php → rbac_enabled_default","Login tetap wajib walaupun RBAC dinonaktifkan. Untuk produksi, disarankan mempertahankan RBAC dalam keadaan TRUE.":"Sign-in is always required, even when RBAC is disabled. For production, keeping RBAC enabled is recommended.","Ekspor & Cetak":"Export & Print","Kontrol tombol ekspor pada halaman laporan dan sheet.":"Control export options on the Reports and Transaction Table pages.","Ekspor Excel":"Excel Export","Izinkan unduhan lembar transaksi yang kompatibel dengan Microsoft Excel.":"Allow transaction spreadsheets compatible with Microsoft Excel to be downloaded.","Cetak / simpan PDF":"Print / Save PDF","Izinkan halaman laporan cetak yang dapat disimpan sebagai PDF melalui browser.":"Allow printable reports to be saved as PDF through the browser.","Ukuran struk thermal":"Thermal Receipt Size","Pilih ukuran roll untuk cetak langsung ESC/POS maupun cetak melalui browser. Panjang struk mengikuti isi transaksi.":"Choose the paper width for direct ESC/POS or browser printing. Receipt length adjusts to its contents.","58 × 48 mm — ukuran saat ini":"58 × 48 mm — current size","80 × 80 mm — roll 80 mm, panjang otomatis":"80 × 80 mm — 80 mm roll, automatic length","Perubahan pengaturan berlaku setelah disimpan.":"Settings take effect after saving.","✓ Simpan Pengaturan":"✓ Save Settings","Config Rules":"Configuration Rules","Aturan inti yang tersedia pada":"Core rules available in","READ ONLY":"READ ONLY","Mata uang dasar":"Base Currency","Total transaksi dikonversi ke Rupiah":"All transaction totals are converted to Indonesian Rupiah","Wajib diganti":"Change Required","Setiap akun baru harus mengganti password":"Every new account must change its password","Kurs Master per Mata Uang":"Master Exchange Rates by Currency","Atur kurs beli dan kurs jual standar. Form transaksi hanya menampilkan kurs sesuai jenis transaksi, sementara kurs pasangannya dipakai untuk menghitung profit.":"Set the standard buy and sell rates. The transaction form displays only the rate for the selected transaction type; the paired rate is used to calculate profit.","KURS DEFAULT":"DEFAULT RATES","Kode":"Code","Nama mata uang":"Currency Name","Aktif":"Active","Nonaktif":"Inactive","Kurs harus diisi untuk kedua sisi agar profit bisa dihitung akurat.":"Both buy and sell rates must be set for accurate profit calculations.","✓ Simpan Kurs Master":"✓ Save Master Rates","Belum diatur":"Not set","COMMUNICATION CENTER":"COMMUNICATION CENTER","Administrator dan PIC dapat membuat, memperbarui, serta menghapus pengumuman untuk Teller atau semua pengguna.":"Administrators and PICs can create, update, and delete announcements for tellers or all users.","Administrator & PIC":"Administrator & PIC","Buat Pengumuman":"Create Announcement","Pengumuman tetap tampil pada lonceng akun penerima sampai Administrator atau PIC mengubah atau menghapusnya.":"Announcements remain in recipients' notification menus until an Administrator or PIC updates or deletes them.","Judul pengumuman":"Announcement Title","Penerima":"Recipients","Teller saja":"Tellers Only","Semua pengguna":"All Users","Isi pengumuman":"Announcement Message","Notifikasi pembayaran berhasil dibuat otomatis untuk Administrator dan PIC.":"Successful payment notifications are generated automatically for Administrators and PICs.","Daftar Pengumuman":"Announcement List","pengumuman tersimpan. Perubahan akan membuat pengumuman kembali belum dibaca.":"announcements saved. Updates mark the announcement as unread again.","CRUD":"MANAGEMENT","Belum ada pengumuman. Gunakan form di atas untuk membuat pengumuman pertama.":"No announcements yet. Use the form above to create the first announcement.","PENGUMUMAN":"ANNOUNCEMENT","Dibuat oleh ·":"Created by ·","Judul":"Title","Simpan Perubahan":"Save Changes","Hapus Pengumuman":"Delete Announcement","Contoh: Pengingat closing transaksi":"Example: Transaction closing reminder","Tulis informasi yang perlu diketahui Teller...":"Enter information tellers need to know...","ACCESS MANAGEMENT":"ACCESS MANAGEMENT","Atur modul dan tindakan yang boleh digunakan setiap role saat RBAC aktif.":"Configure the modules and actions each role can use when RBAC is enabled.","Administrator":"Administrator","selalu memiliki semua izin agar sistem tetap dapat dikelola. Jika RBAC disetel FALSE, izin modul umum tidak diperiksa, tetapi halaman sensitif tetap khusus admin.":"always has all permissions so the system can be managed. When RBAC is FALSE, general module permissions are not checked, but sensitive pages remain administrator-only.","Matriks Hak Akses":"Access Control Matrix","Centang izin untuk mengaktifkan akses role yang dipilih.":"Select permissions to enable access for the chosen role.","Perubahan berlaku setelah disimpan. Role admin dikunci agar tidak kehilangan akses.":"Changes take effect after saving. The administrator role is locked to prevent loss of access.","MASTER DATA":"MASTER DATA","Tambahkan nama pelanggan agar tersedia sebagai pilihan saat input transaksi.":"Add customer names so they are available when entering transactions.","← Kembali ke Input Transaksi":"← Back to New Transaction","Tambah Pelanggan":"Add Customer","Nama pelanggan yang disimpan akan muncul pada dropdown/saran nama di form transaksi.":"Saved customer names appear in the transaction form's dropdown suggestions.","Daftar Pelanggan":"Customer List","nama tersimpan":"saved names","Belum ada master pelanggan.":"No customers have been added yet.","Pelanggan baru juga akan ditambahkan otomatis ketika transaksi disimpan.":"New customers are also added automatically when a transaction is saved.","No.":"No.","Ditambahkan oleh":"Added By","Tanggal tersimpan":"Date Added","Masukkan nama pelanggan":"Enter customer name","FINANCIAL OPERATIONS":"FINANCIAL OPERATIONS","Kelola transaksi valuta asing, pantau pergerakan nominal, dan susun laporan transaksi dalam satu workspace.":"Manage foreign exchange transactions, monitor currency volumes, and prepare transaction reports in one workspace.","Selamat datang":"Welcome back","Masuk untuk melanjutkan ke workspace.":"Sign in to continue to your workspace.","Password":"Password","Masuk ke Dashboard":"Sign In to Dashboard","Login terlindungi · Jangan bagikan kredensial Anda.":"Secure sign-in · Never share your credentials.","Masukkan username":"Enter username","Masukkan password":"Enter password","Tampilkan password":"Show password","Struk dikirim langsung ke printer default Windows. Tidak perlu membuka dialog pengaturan printer setiap transaksi.":"Receipts are sent directly to the default Windows printer. No printer settings dialog is needed for each transaction.","Cetak Struk Thermal":"Print Thermal Receipt","Cetak via Browser":"Print via Browser","Kembali ke Transaksi":"Back to Transactions","VALUTA ASING • RECEIPT":"FOREIGN EXCHANGE • RECEIPT","DETAIL VALAS":"FOREIGN CURRENCY DETAILS","Metode pembayaran:":"Payment method:","CATATAN":"NOTES","TERIMA KASIH":"THANK YOU","Terima kasih telah bertransaksi bersama kami.":"Thank you for your transaction.","Simpan struk ini sebagai bukti transaksi.":"Please keep this receipt as proof of transaction.","Struk transaksi MONEY CHANGER T3":"MONEY CHANGER T3 transaction receipt","Informasi transaksi":"Transaction Information","Detail valas":"Foreign Currency Details","Username atau password salah, atau akun sedang nonaktif.":"The username or password is incorrect, or the account is inactive.","Pengaturan berhasil disimpan.":"Settings saved successfully.","Nama pelanggan wajib diisi.":"Customer name is required.","Pelanggan berhasil ditambahkan ke dropdown transaksi.":"Customer added to the transaction dropdown.","Nama pelanggan tersebut sudah ada.":"That customer name already exists.","Logo khusus dihapus. Logo default MC kembali digunakan.":"Custom logo removed. The default MC logo has been restored.","Pengumuman berhasil diterbitkan.":"Announcement published successfully.","Notifikasi tidak ditemukan.":"Notification not found.","Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba kembali.":"Your form session has expired. Reload the page and try again.","Laporan transaksi valuta asing":"Foreign exchange transaction report","Struk - MONEY CHANGER T3":"Receipt - MONEY CHANGER T3","＋ Input Transaksi":"＋ New Transaction","Total profit":"Total Profit","Kode Valas":"Currency Code","Login · Money Changer":"Sign In · Money Changer","✓ Rekap transaksi otomatis":"✓ Automatic transaction summaries","✓ Laporan Excel & cetak PDF":"✓ Excel reports & PDF printing","✓ Akses pengguna berbasis role":"✓ Role-based user access","＋ Buat Pengguna":"＋ Add User","Foto ":"Photo ","Rentang aktif: sampai .":"Active period: to .","✓ Simpan Matriks Izin":"✓ Save Permission Matrix","＋ Simpan Pelanggan":"＋ Save Customer","config/configrules.php":"config/configrules.php","BUY / SELL":"BUY / SELL","Beli dan jual valuta asing":"Buying and selling foreign currency","Logo aplikasi saat ini":"Current application logo","Akses tidak diizinkan":"Access denied","＋ Terbitkan Pengumuman":"＋ Publish Announcement","Atur kurs master":"Set master rates","Pembayaran berhasil":"Payment successful","PEMBAYARAN":"PAYMENT","Judul, isi pengumuman, atau tujuan penerima tidak valid.":"The announcement title, message, or recipient is invalid.","Pengumuman berhasil diperbarui dan akan tampil kembali sebagai belum dibaca.":"Announcement updated and marked as unread for recipients.","Pengumuman berhasil dihapus.":"Announcement deleted successfully.","Pilih foto profil terlebih dahulu.":"Please select a profile photo first.","Ukuran foto profil maksimal 2 MB.":"The profile photo must not exceed 2 MB.","Foto harus berupa gambar PNG, JPG/JPEG, atau WebP yang valid.":"The photo must be a valid PNG, JPG/JPEG, or WebP image.","Dimensi foto harus antara 16 × 16 hingga 4000 × 4000 piksel.":"Image dimensions must be between 16 × 16 and 4000 × 4000 pixels.","Folder foto profil tidak dapat dibuat. Periksa izin tulis folder public/uploads.":"The profile upload folder could not be created. Check write permissions for public/uploads.","Foto profil gagal disimpan. Periksa izin tulis folder public/uploads.":"The profile photo could not be saved. Check write permissions for public/uploads.","Foto profil berhasil diperbarui.":"Profile photo updated successfully.","Password saat ini tidak sesuai.":"The current password is incorrect.","Password baru minimal 3 karakter.":"The new password must contain at least 3 characters.","Konfirmasi password belum sama.":"The password confirmation does not match.","Password berhasil diperbarui.":"Password updated successfully.","Transaksi berhasil diperbarui.":"Transaction updated successfully.","Transaksi berhasil disimpan.":"Transaction saved successfully.","Transaksi berhasil dihapus.":"Transaction deleted successfully.","Kurs master harus berupa angka nol atau lebih besar. Tidak ada kurs yang diubah.":"Master rates must be zero or greater. No rates were changed.","Kurs master gagal disimpan. Tidak ada perubahan yang diterapkan.":"Master rates could not be saved. No changes were applied.","Kurs master per mata uang berhasil disimpan.":"Master exchange rates saved successfully.","Pilih file logo terlebih dahulu.":"Please select a logo file first.","Ukuran logo maksimal 3 MB.":"The logo file must not exceed 3 MB.","Format logo harus PNG, JPG/JPEG, atau WebP yang valid.":"The logo must be a valid PNG, JPG/JPEG, or WebP image.","Dimensi logo harus antara 16 × 16 hingga 4000 × 4000 piksel.":"Logo dimensions must be between 16 × 16 and 4000 × 4000 pixels.","Folder upload logo tidak dapat dibuat. Periksa izin tulis folder public.":"The logo upload folder could not be created. Check write permissions for the public folder.","Logo gagal disimpan. Periksa izin tulis folder public/uploads.":"The logo could not be saved. Check write permissions for public/uploads.","Logo aplikasi berhasil diganti. Sidebar dan favicon akan menggunakan logo baru.":"Application logo updated. The sidebar and favicon will use the new logo.","Nama lengkap wajib diisi dan maksimal 120 karakter.":"Full name is required and must not exceed 120 characters.","Username harus 3–60 karakter dan hanya boleh berisi huruf kecil, angka, titik, underscore, atau tanda hubung.":"Username must be 3–60 characters and may contain only lowercase letters, numbers, periods, underscores, or hyphens.","Password pengguna minimal 3 karakter.":"User passwords must contain at least 3 characters.","Role pengguna tidak valid.":"The selected user role is invalid.","Pengguna berhasil dibuat. Pengguna diminta mengganti password saat login pertama.":"User created successfully. They must change their password at first sign-in.","Username sudah digunakan atau pengguna tidak dapat dibuat.":"The username is already in use, or the user could not be created.","Matriks role dan izin berhasil diperbarui.":"Role and permission matrix updated successfully.","Akun sendiri tidak dapat dinonaktifkan dari menu ini.":"You cannot deactivate your own account from this menu.","Status pengguna berhasil diperbarui.":"User status updated successfully.","Aksi tidak dikenali.":"Unknown action.","Transaksi untuk struk tidak ditemukan.":"The transaction for this receipt could not be found.","Printer tidak menerima pekerjaan cetak.":"The printer did not accept the print job.","Struk berhasil dikirim ke printer thermal.":"Receipt sent to the thermal printer successfully.","Cetak langsung belum berhasil:":"Direct printing failed:","printer tidak tersedia":"printer unavailable","Pastikan printer thermal menjadi printer default Windows. Jika perlu, gunakan tombol Cetak via Browser.":"Make sure the thermal printer is the default Windows printer. If needed, use Print via Browser.","Notifikasi belum dapat ditandai dibaca.":"The notification could not be marked as read.","Gagal memperbarui notifikasi.":"Failed to update the notification.","Atur kurs master agar profit bisa dihitung.":"Set master rates to calculate profit.","Selamat datang":"Welcome","Masuk untuk melanjutkan ke workspace.":"Sign in to continue to your workspace.","Kelola transaksi valuta asing, pantau pergerakan nominal, dan susun laporan transaksi dalam satu workspace.":"Manage foreign exchange transactions, monitor currency volumes, and prepare transaction reports in one workspace.","Rekap transaksi otomatis":"Automated transaction summaries","Laporan Excel & cetak PDF":"Excel reports and PDF exports","Akses pengguna berbasis role":"Role-based access control","Masukkan username":"Enter username","Masukkan password":"Enter password","Username":"Username","Password":"Password","Masuk ke Dashboard":"Sign in to Dashboard","Tampilkan password":"Show password","Sembunyikan password":"Hide password","Kebijakan Privasi":"Privacy Policy","Syarat & Ketentuan":"Terms & Conditions","Tutup":"Close","Informasi yang dicatat":"Information We Record","Data akun, aktivitas login, detail transaksi valuta asing, dan catatan audit digunakan untuk menjalankan layanan, mencegah penyalahgunaan, dan memenuhi kewajiban operasional atau hukum yang berlaku.":"Account data, sign-in activity, foreign exchange transaction details, and audit records are used to operate the service, prevent misuse, and meet applicable operational or legal obligations.","Penggunaan dan akses":"Use and Access","Data hanya boleh diakses oleh pengguna berwenang sesuai peran. Informasi transaksi digunakan untuk pelaporan, rekonsiliasi, dan audit internal; jangan membagikan kredensial atau data pelanggan kepada pihak yang tidak berwenang.":"Data may only be accessed by authorized users according to their roles. Transaction information is used for reporting, reconciliation, and internal audits. Do not share credentials or customer data with unauthorized parties.","Retensi dan keamanan":"Retention and Security","Catatan disimpan sesuai kebutuhan operasional dan kebijakan penyimpanan organisasi. Hubungi administrator untuk pertanyaan atau permintaan terkait data.":"Records are retained according to operational needs and the organization's retention policy. Contact an administrator with questions or requests concerning data.","Ketentuan penggunaan":"Terms of Use","Gunakan aplikasi hanya untuk transaksi yang sah dan sesuai kewenangan akun. Pastikan jenis transaksi, mata uang, nominal, kurs, dan identitas pelanggan dimasukkan dengan benar.":"Use this application only for lawful transactions within your account permissions. Ensure the transaction type, currency, amount, exchange rate, and customer details are entered correctly.","Kurs dan transaksi":"Rates and Transactions","Kurs dapat berubah sesuai kebijakan operasional. Periksa nilai transaksi sebelum menyimpan. Simpan struk sebagai bukti transaksi dan segera laporkan ketidaksesuaian kepada Administrator atau PIC.":"Exchange rates may change according to operational policy. Review transaction values before saving. Keep the receipt as proof of transaction and promptly report discrepancies to an Administrator or PIC.","Tanggung jawab pengguna":"User Responsibilities","Jaga kerahasiaan akun, jangan menggunakan akun orang lain, dan patuhi prosedur internal serta ketentuan peraturan yang berlaku.":"Keep your account confidential, do not use another person's account, and follow internal procedures and applicable regulations.","Ketentuan ini merupakan aturan penggunaan internal dan tidak menggantikan kewajiban hukum yang berlaku.":"These terms govern internal application use and do not replace applicable legal obligations."}
  const applyTranslations = () => {
    if (document.documentElement.dataset.uiLanguage !== 'en') return;
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, { acceptNode(node) {
      const parent = node.parentElement;
      if (!parent || parent.closest('script,style,textarea,code,[data-no-translate]')) return NodeFilter.FILTER_REJECT;
      return NodeFilter.FILTER_ACCEPT;
    } });
    let node;
    while ((node = walker.nextNode())) {
      const raw = node.nodeValue || '';
      const trimmed = raw.trim();
      if (!trimmed || !Object.prototype.hasOwnProperty.call(englishText, trimmed)) continue;
      const start = raw.match(/^\s*/)?.[0] || '';
      const end = raw.match(/\s*$/)?.[0] || '';
      node.nodeValue = start + englishText[trimmed] + end;
    }
    document.querySelectorAll('[placeholder],[title],[aria-label],[alt]').forEach(element => {
      ['placeholder','title','aria-label','alt'].forEach(attribute => {
        const value = element.getAttribute(attribute);
        if (value && Object.prototype.hasOwnProperty.call(englishText, value)) element.setAttribute(attribute, englishText[value]);
      });
    });
    document.documentElement.lang = 'en';
  };
  applyTranslations();

  const pageLoadingOverlay = document.getElementById('pageLoadingOverlay');
  const showPageLoader = () => {
    if (!pageLoadingOverlay) return;
    pageLoadingOverlay.classList.add('is-active');
    pageLoadingOverlay.setAttribute('aria-hidden', 'false');
  };
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || !pageLoadingOverlay || event.defaultPrevented) return;
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.hasAttribute('download')) return;
    const rawHref = link.getAttribute('href') || '';
    if (rawHref.startsWith('#') || rawHref.startsWith('javascript:')) return;
    let destination;
    try { destination = new URL(link.href, window.location.href); } catch (_) { return; }
    if (destination.origin !== window.location.origin || destination.pathname !== window.location.pathname) return;
    if (!destination.searchParams.has('page')) return;
    if (destination.search === window.location.search && destination.hash === window.location.hash) return;
    showPageLoader();
  });
  window.addEventListener('pageshow', () => {
    if (!pageLoadingOverlay) return;
    pageLoadingOverlay.classList.remove('is-active');
    pageLoadingOverlay.setAttribute('aria-hidden', 'true');
  });

  const offlineModal = document.querySelector('[data-offline-modal]');
  const showOfflineModal = () => { if (offlineModal) offlineModal.hidden = false; };
  const hideOfflineModal = () => { if (offlineModal) offlineModal.hidden = true; };
  window.addEventListener('offline', showOfflineModal);
  window.addEventListener('online', hideOfflineModal);
  offlineModal?.querySelector('[data-offline-retry]')?.addEventListener('click', () => {
    if (navigator.onLine) hideOfflineModal();
    else showOfflineModal();
  });
  if (navigator.onLine === false) showOfflineModal();

  document.querySelectorAll('[data-toggle-password]').forEach(button => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.togglePassword);
      if (!input) return;
      const willShowPassword = input.type === 'password';
      input.type = willShowPassword ? 'text' : 'password';
      button.setAttribute('aria-pressed', willShowPassword ? 'true' : 'false');
      button.setAttribute('aria-label', willShowPassword
        ? (document.documentElement.dataset.uiLanguage === 'en' ? 'Hide password' : 'Sembunyikan password')
        : (document.documentElement.dataset.uiLanguage === 'en' ? 'Show password' : 'Tampilkan password'));
      const visibleEye = button.querySelector('[data-eye-visible]');
      const hiddenEye = button.querySelector('[data-eye-hidden]');
      if (visibleEye) visibleEye.hidden = willShowPassword;
      if (hiddenEye) hiddenEye.hidden = !willShowPassword;
    });
  });

  document.querySelectorAll('[data-open-policy]').forEach(button => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.openPolicy);
      if (!dialog) return;
      if (typeof dialog.showModal === 'function') dialog.showModal();
      else dialog.setAttribute('open', 'open');
    });
  });
  document.querySelectorAll('[data-close-policy]').forEach(button => {
    button.addEventListener('click', () => {
      const dialog = button.closest('dialog');
      if (!dialog) return;
      if (typeof dialog.close === 'function') dialog.close();
      else dialog.removeAttribute('open');
    });
  });
  document.querySelectorAll('.login-policy-dialog').forEach(dialog => {
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      if (typeof dialog.close === 'function') dialog.close();
      else dialog.removeAttribute('open');
    });
  });

  const mobileMenu = document.getElementById('mobileMenu');
  const sidebar = document.getElementById('sidebar');
  if (mobileMenu && sidebar) mobileMenu.addEventListener('click', () => sidebar.classList.toggle('sidebar-open'));

  document.querySelectorAll('.switch-control input[type="checkbox"]').forEach(input => {
    input.addEventListener('change', () => {
      const state = input.closest('.switch-control')?.querySelector('.switch-state');
      if (state) state.textContent = input.checked ? 'TRUE' : 'FALSE';
    });
  });

  const transactionForm = document.getElementById('transactionForm');
  const transactionTypeInput = document.getElementById('transaction_type');
  const resultOutput = document.getElementById('calculatedIdr');
  const appliedRateLabel = document.getElementById('appliedRateLabel');
  const paymentMethodInput = document.getElementById('payment_method');
  const cashReceivedGroup = document.getElementById('cashReceivedGroup');
  const cashReceivedInput = document.getElementById('cash_received');
  const currencyItemsContainer = document.getElementById('currencyItems');
  const currencyItemTemplate = document.getElementById('currencyItemTemplate');
  const rateMasterNotice = document.getElementById('rateMasterNotice');

  if (transactionForm?.dataset.multiCurrency === '1' && currencyItemsContainer) {
    const itemRows = () => Array.from(currencyItemsContainer.querySelectorAll('[data-transaction-item]'));
    const itemField = (row, selector) => row.querySelector(selector);
    const setHidden = (element, hidden) => {
      if (!element) return;
      element.hidden = hidden;
      element.style.display = hidden ? 'none' : '';
    };
    const applyCurrencyRates = (row, overwrite = true) => {
      const select = itemField(row, '[data-item-currency]');
      const option = select?.selectedOptions?.[0];
      const buyInput = itemField(row, '[data-item-buy-rate]');
      const sellInput = itemField(row, '[data-item-sell-rate]');
      if (!option?.value) {
        if (overwrite) { if (buyInput) buyInput.value = ''; if (sellInput) sellInput.value = ''; }
        updateItemVisibility(row);
        updateMultiCurrencyTotals();
        return;
      }
      const masterBuy = Number(option.dataset.buyRate) || 0;
      const masterSell = Number(option.dataset.sellRate) || 0;
      if (buyInput && (overwrite || !buyInput.value) && masterBuy > 0) buyInput.value = String(masterBuy);
      if (sellInput && (overwrite || !sellInput.value) && masterSell > 0) sellInput.value = String(masterSell);
      updateItemVisibility(row);
      updateMultiCurrencyTotals();
    };
    const updateItemVisibility = row => {
      const isBuy = (transactionTypeInput?.value || 'BUY') !== 'SELL';
      const buyGroup = itemField(row, '[data-item-buy-group]');
      const sellGroup = itemField(row, '[data-item-sell-group]');
      const buyInput = itemField(row, '[data-item-buy-rate]');
      const sellInput = itemField(row, '[data-item-sell-rate]');
      setHidden(buyGroup, !isBuy);
      setHidden(sellGroup, isBuy);
      const hasCurrency = Boolean(itemField(row, '[data-item-currency]')?.value);
      if (buyInput) { buyInput.required = isBuy && hasCurrency; buyInput.min = isBuy ? '0.0001' : '0'; }
      if (sellInput) { sellInput.required = !isBuy && hasCurrency; sellInput.min = isBuy ? '0' : '0.0001'; }
    };
    const updateMultiCurrencyTotals = () => {
      const isBuy = (transactionTypeInput?.value || 'BUY') !== 'SELL';
      let total = 0;
      let ratesReady = true;
      let selectedCount = 0;
      itemRows().forEach(row => {
        const select = itemField(row, '[data-item-currency]');
        const amount = Number(itemField(row, '[data-item-amount]')?.value) || 0;
        const buyRate = Number(itemField(row, '[data-item-buy-rate]')?.value) || 0;
        const sellRate = Number(itemField(row, '[data-item-sell-rate]')?.value) || 0;
        const appliedRate = isBuy ? buyRate : sellRate;
        const subtotal = amount * appliedRate;
        total += subtotal;
        if (select?.value) {
          selectedCount++;
          if (buyRate <= 0 || sellRate <= 0) ratesReady = false;
        }
        const subtotalOutput = itemField(row, '[data-item-subtotal]');
        if (subtotalOutput) subtotalOutput.textContent = formatIDR(subtotal);
      });
      if (resultOutput) resultOutput.textContent = formatIDR(total);
      if (appliedRateLabel) {
        const language = document.documentElement.dataset.uiLanguage === 'en' ? 'en-US' : 'id-ID';
        const label = document.documentElement.dataset.uiLanguage === 'en' ? 'currency line(s)' : 'baris mata uang';
        appliedRateLabel.textContent = `${selectedCount} ${label} · ${document.documentElement.dataset.uiLanguage === 'en' ? 'combined transaction total' : 'total gabungan transaksi'}`;
      }
      if (rateMasterNotice) setHidden(rateMasterNotice, selectedCount === 0 || ratesReady);
      updateCashReceivedVisibility();
    };
    const updateCashReceivedVisibility = () => {
      if (!cashReceivedGroup || !cashReceivedInput) return;
      const requiredForCashSale = transactionTypeInput?.value === 'SELL' && paymentMethodInput?.value === 'Cash';
      setHidden(cashReceivedGroup, !requiredForCashSale);
      cashReceivedInput.required = Boolean(requiredForCashSale);
      if (!requiredForCashSale) cashReceivedInput.setCustomValidity('');
    };
    const reindexRows = () => {
      itemRows().forEach((row, index) => {
        const oldIndex = row.dataset.index;
        row.dataset.index = String(index);
        const heading = itemField(row, '[data-item-heading]');
        if (heading) heading.textContent = `${document.documentElement.dataset.uiLanguage === 'en' ? 'Currency' : 'Valas'} ${index + 1}`;
        row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/items\[[^\]]+\]/, `items[${index}]`); });
        row.querySelectorAll('[id]').forEach(element => { element.id = element.id.replace(new RegExp(`_${oldIndex}$`), `_${index}`); });
        row.querySelectorAll('label[for]').forEach(label => { label.htmlFor = label.htmlFor.replace(new RegExp(`_${oldIndex}$`), `_${index}`); });
        const remove = itemField(row, '[data-remove-currency-item]');
        if (remove) { remove.disabled = itemRows().length <= 1; remove.style.visibility = itemRows().length <= 1 ? 'hidden' : 'visible'; }
        updateItemVisibility(row);
      });
    };
    transactionForm.addEventListener('change', event => {
      const target = event.target;
      if (target === transactionTypeInput) {
        itemRows().forEach(row => updateItemVisibility(row));
      } else if (target?.matches('[data-item-currency]')) {
        const row = target.closest('[data-transaction-item]');
        if (row) applyCurrencyRates(row, true);
      }
      if (target === paymentMethodInput) updateCashReceivedVisibility();
      updateMultiCurrencyTotals();
    });
    transactionForm.addEventListener('input', event => {
      if (event.target?.matches('[data-item-amount], [data-item-buy-rate], [data-item-sell-rate]')) updateMultiCurrencyTotals();
    });
    transactionForm.addEventListener('click', event => {
      const addButton = event.target.closest('[data-add-currency-item]');
      const removeButton = event.target.closest('[data-remove-currency-item]');
      if (addButton && currencyItemTemplate && itemRows().length < 20) {
        const nextIndex = itemRows().length;
        const html = currencyItemTemplate.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        currencyItemsContainer.insertAdjacentHTML('beforeend', html);
        const row = itemRows().at(-1);
        if (row) updateItemVisibility(row);
        reindexRows();
        updateMultiCurrencyTotals();
      } else if (removeButton && itemRows().length > 1) {
        removeButton.closest('[data-transaction-item]')?.remove();
        reindexRows();
        updateMultiCurrencyTotals();
      }
    });
    itemRows().forEach(row => {
      updateItemVisibility(row);
      const select = itemField(row, '[data-item-currency]');
      const buyInput = itemField(row, '[data-item-buy-rate]');
      const sellInput = itemField(row, '[data-item-sell-rate]');
      if (select?.value && (!buyInput?.value || !sellInput?.value)) applyCurrencyRates(row, false);
    });
    reindexRows();
    updateMultiCurrencyTotals();
  }

  function prepareCanvas(canvas) {
    const rect = canvas.getBoundingClientRect();
    const ratio = Math.max(1, window.devicePixelRatio || 1);
    canvas.width = Math.max(320, rect.width) * ratio;
    canvas.height = Math.max(220, rect.height || 250) * ratio;
    const ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);
    return { ctx, width: canvas.width / ratio, height: canvas.height / ratio };
  }

  function drawLineChart(canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]').map(Number);
    const { ctx, width, height } = prepareCanvas(canvas);
    const pad = { top: 20, right: 20, bottom: 36, left: 58 };
    const chartW = width - pad.left - pad.right;
    const chartH = height - pad.top - pad.bottom;
    const max = Math.max(1, ...values) * 1.15;
    ctx.clearRect(0, 0, width, height);
    ctx.font = '11px system-ui, sans-serif';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= 4; i++) {
      const y = pad.top + (chartH / 4) * i;
      const value = max * (1 - i / 4);
      ctx.strokeStyle = '#e9eef5'; ctx.lineWidth = 1;
      ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
      ctx.fillStyle = '#8a96a9';
      const label = value >= 1000000 ? `${(value / 1000000).toFixed(1)} jt` : value >= 1000 ? `${Math.round(value / 1000)} rb` : Math.round(value).toString();
      ctx.textAlign = 'right'; ctx.fillText(label, pad.left - 10, y);
    }
    const points = values.map((value, index) => ({
      x: pad.left + (labels.length <= 1 ? chartW / 2 : (chartW / (labels.length - 1)) * index),
      y: pad.top + chartH - (value / max) * chartH,
      value,
    }));
    if (points.length > 0) {
      const gradient = ctx.createLinearGradient(0, pad.top, 0, height - pad.bottom);
      gradient.addColorStop(0, 'rgba(24, 165, 145, .22)'); gradient.addColorStop(1, 'rgba(24, 165, 145, 0)');
      ctx.beginPath(); ctx.moveTo(points[0].x, pad.top + chartH);
      points.forEach(p => ctx.lineTo(p.x, p.y)); ctx.lineTo(points[points.length - 1].x, pad.top + chartH); ctx.closePath(); ctx.fillStyle = gradient; ctx.fill();
      ctx.beginPath(); points.forEach((p, i) => i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y));
      ctx.strokeStyle = '#14a58e'; ctx.lineWidth = 2.8; ctx.lineJoin = 'round'; ctx.lineCap = 'round'; ctx.stroke();
      points.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, 3.8, 0, Math.PI * 2); ctx.fillStyle = '#fff'; ctx.fill(); ctx.strokeStyle = '#14a58e'; ctx.lineWidth = 2.4; ctx.stroke(); });
    }
    labels.forEach((label, index) => {
      const x = pad.left + (labels.length <= 1 ? chartW / 2 : (chartW / (labels.length - 1)) * index);
      ctx.fillStyle = '#8a96a9'; ctx.textAlign = 'center'; ctx.fillText(label, x, height - 14);
    });
  }

  function drawBarChart(canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]').map(Number);
    const { ctx, width, height } = prepareCanvas(canvas);
    const pad = { top: 24, right: 22, bottom: 38, left: 58 };
    const chartW = width - pad.left - pad.right;
    const chartH = height - pad.top - pad.bottom;
    const max = Math.max(1, ...values) * 1.18;
    ctx.clearRect(0, 0, width, height);
    ctx.font = '11px system-ui, sans-serif'; ctx.textBaseline = 'middle';
    for (let i = 0; i <= 4; i++) {
      const y = pad.top + (chartH / 4) * i;
      ctx.strokeStyle = '#e9eef5'; ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
      const v = max * (1 - i / 4);
      ctx.fillStyle = '#8a96a9'; ctx.textAlign = 'right';
      ctx.fillText(v >= 1000000 ? `${(v / 1000000).toFixed(1)} jt` : v >= 1000 ? `${Math.round(v / 1000)} rb` : Math.round(v).toString(), pad.left - 10, y);
    }
    const slot = chartW / Math.max(1, labels.length);
    values.forEach((value, index) => {
      const barH = (value / max) * chartH;
      const x = pad.left + slot * index + slot * 0.23;
      const y = pad.top + chartH - barH;
      const w = slot * 0.54;
      ctx.fillStyle = index === 0 ? '#1aa58e' : '#7b8de8';
      ctx.beginPath();
      const radius = Math.min(8, w / 2, barH / 2);
      ctx.roundRect(x, y, w, Math.max(1, barH), [radius, radius, 0, 0]);
      ctx.fill();
      ctx.fillStyle = '#526078'; ctx.textAlign = 'center'; ctx.fillText(labels[index] || '', x + w / 2, height - 14);
    });
  }

  function drawCharts() {
    document.querySelectorAll('#transactionChart').forEach(drawLineChart);
    document.querySelectorAll('#transactionMixChart').forEach(drawBarChart);
  }
  drawCharts();
  let resizeTimer;
  window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(drawCharts, 100); });

  const notificationCenter = document.querySelector('[data-notification-center]');
  const notificationToggle = notificationCenter?.querySelector('[data-notification-toggle]');
  const notificationDropdown = notificationCenter?.querySelector('[data-notification-dropdown]');
  const notificationCount = notificationCenter?.querySelector('[data-notification-count]');
  const notificationList = notificationCenter?.querySelector('[data-notification-list]');
  const setNotificationCount = count => {
    if (!notificationCount) return;
    const next = Math.max(0, Number(count) || 0);
    notificationCount.dataset.count = String(next);
    notificationCount.textContent = next > 99 ? '99+' : String(next);
    notificationCount.hidden = next === 0;
    const sublabel = notificationCenter?.querySelector('.notification-dropdown-head small');
    if (sublabel) sublabel.textContent = `${next} ${document.documentElement.dataset.uiLanguage === 'en' ? 'unread' : 'belum dibaca'}`;
  };
  const updateNotificationCount = delta => {
    const current = Number.parseInt(notificationCount?.dataset.count || '0', 10) || 0;
    setNotificationCount(current + delta);
  };
  const renderNotificationList = items => {
    if (!notificationList) return;
    const fragment = document.createDocumentFragment();
    if (!Array.isArray(items) || items.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'notification-empty';
      empty.textContent = document.documentElement.dataset.uiLanguage === 'en' ? 'No new notifications.' : 'Tidak ada notifikasi baru.';
      fragment.appendChild(empty);
      notificationList.replaceChildren(fragment);
      return;
    }
    items.forEach(item => {
      const article = document.createElement('article');
      const isAnnouncement = item.notification_type === 'announcement';
      const isRead = Number(item.is_read) === 1;
      article.className = `notification-item${isAnnouncement && isRead ? ' notification-item-read' : ''}`;
      article.dataset.notificationItem = String(item.id);
      article.dataset.notificationType = item.notification_type || '';
      const kind = document.createElement('span');
      const isPayment = item.notification_type === 'payment';
      kind.className = `notification-kind ${isPayment ? 'notification-kind-payment' : 'notification-kind-announcement'}`;
      kind.textContent = document.documentElement.dataset.uiLanguage === 'en' ? (isPayment ? 'PAYMENT' : 'ANNOUNCEMENT') : (isPayment ? 'PEMBAYARAN' : 'PENGUMUMAN');
      const title = document.createElement('strong');
      title.textContent = item.title || 'Notifikasi';
      const message = document.createElement('p');
      message.textContent = item.message || '';
      const footer = document.createElement('div');
      footer.className = 'notification-item-foot';
      const time = document.createElement('small');
      time.textContent = item.created_at || '';
      const button = document.createElement('button');
      button.type = 'button';
      button.dataset.csrf = notificationCenter?.dataset.csrf || '';
      if (isAnnouncement && isRead) {
        button.textContent = document.documentElement.dataset.uiLanguage === 'en' ? 'Read' : 'Sudah dibaca';
        button.disabled = true;
      } else {
        button.dataset.markNotificationRead = String(item.id);
        button.textContent = document.documentElement.dataset.uiLanguage === 'en' ? 'Mark as read' : 'Tandai dibaca';
      }
      footer.append(time, button);
      article.append(kind, title, message, footer);
      fragment.appendChild(article);
    });
    notificationList.replaceChildren(fragment);
    applyTranslations();
  };
  const refreshNotifications = async () => {
    if (!notificationCenter || document.visibilityState !== 'visible') return;
    try {
      const body = new URLSearchParams({ action: 'fetch_notifications', csrf_token: notificationCenter.dataset.csrf || '', ajax: '1' });
      const response = await fetch(window.location.pathname + window.location.search, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json' },
        body: body.toString(),
      });
      if (!response.ok) return;
      const result = await response.json();
      if (!result.ok) return;
      setNotificationCount(result.unread_count);
      renderNotificationList(result.notifications);
    } catch (_) {
      // Keep the last rendered notification list if the connection is temporarily unavailable.
    }
  };
  notificationToggle?.addEventListener('click', () => {
    if (!notificationDropdown) return;
    const opening = notificationDropdown.hidden;
    notificationDropdown.hidden = !opening;
    notificationToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
  });
  document.addEventListener('click', event => {
    if (notificationCenter && !notificationCenter.contains(event.target) && notificationDropdown && !notificationDropdown.hidden) {
      notificationDropdown.hidden = true;
      notificationToggle?.setAttribute('aria-expanded', 'false');
    }
  });
  notificationList?.addEventListener('click', async event => {
    const button = event.target.closest('[data-mark-notification-read]');
    if (!button) return;
    const notificationId = button.dataset.markNotificationRead;
    if (!notificationId) return;
    button.disabled = true;
    try {
      const body = new URLSearchParams({
        action: 'mark_notification_read',
        notification_id: notificationId,
        csrf_token: button.dataset.csrf || '',
        ajax: '1',
      });
      const response = await fetch(window.location.pathname + window.location.search, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'Accept': 'application/json' },
        body: body.toString(),
      });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Notifikasi belum dapat ditandai dibaca.');
      const notificationItem = button.closest('[data-notification-item]');
      const isAnnouncement = notificationItem?.dataset.notificationType === 'announcement' || result.notification_type === 'announcement';
      if (isAnnouncement && notificationItem) {
        notificationItem.classList.add('notification-item-read');
        button.textContent = document.documentElement.dataset.uiLanguage === 'en' ? 'Read' : 'Sudah dibaca';
        button.disabled = true;
        button.removeAttribute('data-mark-notification-read');
      } else {
        notificationItem?.remove();
      }
      updateNotificationCount(-1);
      if (notificationList && !notificationList.querySelector('[data-notification-item]')) {
        renderNotificationList([]);
      }
    } catch (error) {
      button.disabled = false;
      alert(error instanceof Error ? error.message : 'Gagal memperbarui notifikasi.');
    }
  });
  refreshNotifications();
  window.setInterval(refreshNotifications, 15000);

  // Progressive Web App: register a scoped service worker and expose installation when supported.
  if ('serviceWorker' in navigator && window.location.protocol !== 'file:') {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(new URL('sw.js', document.baseURI), { scope: './' })
        .catch(() => { /* The dashboard remains usable if service-worker registration is unavailable. */ });
    }, { once: true });
  }

  const installButtons = Array.from(document.querySelectorAll('[data-install-app]'));
  let deferredInstallPrompt = null;
  const setInstallButtonsVisible = visible => installButtons.forEach(button => { button.hidden = !visible; });
  setInstallButtonsVisible(false);
  window.addEventListener('beforeinstallprompt', event => {
    event.preventDefault();
    deferredInstallPrompt = event;
    setInstallButtonsVisible(true);
  });
  installButtons.forEach(button => button.addEventListener('click', async () => {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    const choice = await deferredInstallPrompt.userChoice.catch(() => null);
    if (choice?.outcome === 'accepted') setInstallButtonsVisible(false);
    deferredInstallPrompt = null;
  }));
  window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    setInstallButtonsVisible(false);
  });
})();
