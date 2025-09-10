# Panduan Bulk Update Harga Emas & Perak via CSV

## Overview
Fitur bulk update memungkinkan admin untuk mengupdate harga emas dan perak secara massal melalui file CSV. Sistem ini dilengkapi dengan validasi keamanan dan laporan hasil yang detail.

## Cara Penggunaan

### 1. Akses Menu Bulk Upload
1. Login sebagai admin
2. Masuk ke Dashboard → Manage → Harga Emas Perak
3. Klik tab "Upload CSV"

### 2. Format File CSV

#### Header yang Diperlukan:
```csv
tanggal,emas_jual,emas_beli,perak_jual,perak_beli
```

#### Contoh Data:
```csv
tanggal,emas_jual,emas_beli,perak_jual,perak_beli
2024-01-15,1150000,1100000,15000,14500
2024-01-16,1155000,1105000,15100,14600
2024-01-17,1160000,1110000,15200,14700
```

### 3. Aturan Validasi

#### Format Tanggal:
- **Format**: YYYY-MM-DD
- **Contoh**: 2024-01-15
- **Wajib**: Ya

#### Format Harga:
- **Type**: Angka (integer/decimal)
- **Minimum**: > 0
- **Format**: Tanpa titik/koma pemisah
- **Contoh**: 1150000 (bukan 1.150.000)

#### Logika Bisnis:
- Harga jual harus >= harga beli
- Semua harga harus > 0
- Tanggal tidak boleh duplikat dalam satu file

### 4. Batasan File
- **Ukuran maksimal**: 2MB
- **Format**: .csv saja
- **Encoding**: UTF-8 (recommended)
- **Delimiter**: Koma (,)

## Fitur Keamanan

### 1. Validasi File
- ✅ Pengecekan ekstensi file
- ✅ Validasi ukuran file
- ✅ Sanitasi input data
- ✅ Validasi format header

### 2. Validasi Data
- ✅ Format tanggal
- ✅ Tipe data harga
- ✅ Logika bisnis harga
- ✅ Pengecekan duplikasi

### 3. Database Security
- ✅ Prepared statements
- ✅ Input sanitization
- ✅ Transaction handling
- ✅ Error logging

## Laporan Hasil

### 1. Summary Report
- Total record diproses
- Jumlah berhasil
- Jumlah gagal
- Persentase keberhasilan

### 2. Detail Report
Untuk setiap record:
- Nomor baris dalam CSV
- Tanggal
- Status (Inserted/Updated/Error)
- Harga yang diproses
- Keterangan error (jika ada)

### 3. Status Indikator
- 🟢 **Hijau**: Record berhasil diproses
- 🔴 **Merah**: Record gagal dengan error
- 📊 **Badge**: Summary berhasil/gagal

## Troubleshooting

### Error: "Header CSV tidak sesuai"
**Solusi**: Pastikan baris pertama CSV berisi:
```csv
tanggal,emas_jual,emas_beli,perak_jual,perak_beli
```

### Error: "Format tanggal salah"
**Solusi**: Gunakan format YYYY-MM-DD
```csv
✅ Benar: 2024-01-15
❌ Salah: 15/01/2024, 15-01-2024
```

### Error: "Harga harus lebih besar dari 0"
**Solusi**: Pastikan semua harga > 0
```csv
✅ Benar: 1150000
❌ Salah: 0, -1000, kosong
```

### Error: "Harga jual harus >= harga beli"
**Solusi**: Pastikan logika harga benar
```csv
✅ Benar: jual=1150000, beli=1100000
❌ Salah: jual=1100000, beli=1150000
```

### Error: "File terlalu besar"
**Solusi**: 
- Bagi file menjadi beberapa bagian
- Maksimal 2MB per file
- Hapus data yang tidak perlu

## Best Practices

### 1. Persiapan Data
- Backup data sebelum bulk update
- Validasi data di spreadsheet dulu
- Test dengan file kecil terlebih dahulu

### 2. Proses Upload
- Upload di jam sepi (malam/dini hari)
- Monitor hasil laporan dengan teliti
- Simpan file CSV sebagai backup

### 3. Verifikasi Hasil
- Cek API endpoint: `/api_gold_silver.php`
- Verifikasi di halaman EPI
- Cross-check dengan database

## Template CSV

### Download Template
Klik tombol "Download Template" di halaman upload untuk mendapatkan file template dengan format yang benar.

### Isi Template
Template berisi:
- Header yang benar
- 3 contoh data
- Format yang sudah sesuai

## Monitoring & Logging

### 1. Activity Log
Setiap bulk update tercatat:
- User ID yang melakukan
- Timestamp
- Jumlah record diproses
- Status hasil

### 2. Error Tracking
Error dicatat untuk:
- Debugging
- Audit trail
- Performance monitoring

## FAQ

**Q: Apakah bisa update data yang sudah ada?**
A: Ya, sistem akan otomatis update jika tanggal sudah ada, atau insert jika belum ada.

**Q: Bagaimana jika ada error di tengah proses?**
A: Sistem akan melanjutkan proses record lainnya dan melaporkan error per baris.

**Q: Apakah ada limit jumlah record?**
A: Tidak ada limit khusus, tapi dibatasi ukuran file 2MB.

**Q: Bisa upload format Excel?**
A: Tidak, hanya format CSV yang didukung. Convert Excel ke CSV terlebih dahulu.

**Q: Bagaimana cara menghapus data yang salah?**
A: Gunakan menu update manual atau database admin untuk koreksi.

---

**Dibuat**: 2024
**Versi**: 1.0
**Kompatibel**: SimpleAff Plus v1.3.1+