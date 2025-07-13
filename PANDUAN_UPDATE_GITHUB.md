# Panduan Update ke GitHub Repository

Berikut adalah perintah-perintah untuk melakukan update dan push ke GitHub repository untuk proyek sistem pakar kematangan tomat Anda:

### 🔧 **Persiapan Awal (Jika Belum Ada Repository)**

```bash
# Inisialisasi Git repository
git init

# Tambahkan remote repository GitHub
git remote add origin https://github.com/username/nama-repository.git

# Atau jika menggunakan SSH
git remote add origin git@github.com:username/nama-repository.git
```

### 📝 **Langkah-langkah Update ke GitHub**

**1. Cek Status File**
```bash
git status
```

**2. Tambahkan File yang Akan Di-commit**
```bash
# Tambahkan semua file
git add .

# Atau tambahkan file spesifik
git add app/Http/Controllers/DashboardController.php
git add resources/views/dashboard/index.blade.php
git add routes/web.php
```

**3. Commit Perubahan**
```bash
# Commit dengan pesan yang deskriptif
git commit -m "feat: Implementasi dashboard real-time monitoring ESP32

- Tambah DashboardController untuk API endpoints
- Buat view dashboard responsive dengan Tailwind CSS
- Implementasi real-time update menggunakan jQuery
- Fitur save data sebagai training sample
- Status indicator ESP32 online/offline
- Statistik sistem pakar dan distribusi kematangan"
```

**4. Push ke GitHub**
```bash
# Push ke branch main (untuk pertama kali)
git push -u origin main

# Push selanjutnya
git push
```

### 🌿 **Workflow dengan Branch (Recommended)**

**1. Buat Branch Baru untuk Feature**
```bash
git checkout -b feature/dashboard-realtime
```

**2. Lakukan Perubahan dan Commit**
```bash
git add .
git commit -m "feat: Dashboard real-time ESP32 monitoring"
```

**3. Push Branch ke GitHub**
```bash
git push -u origin feature/dashboard-realtime
```

**4. Merge ke Main Branch**
```bash
# Pindah ke main branch
git checkout main

# Pull update terbaru
git pull origin main

# Merge feature branch
git merge feature/dashboard-realtime

# Push hasil merge
git push origin main

# Hapus branch feature (opsional)
git branch -d feature/dashboard-realtime
git push origin --delete feature/dashboard-realtime
```

### 🔄 **Update Rutin**

```bash
# Cek perubahan
git status

# Tambahkan file yang berubah
git add .

# Commit dengan pesan yang jelas
git commit -m "fix: Perbaikan error handling di ESP32 controller"

# Push ke GitHub
git push
```

### 📋 **Template Commit Message**

```bash
# Format: <type>: <description>

# Types:
# feat: fitur baru
# fix: perbaikan bug
# docs: update dokumentasi
# style: perubahan formatting
# refactor: refactoring code
# test: menambah/update test
# chore: maintenance task

# Contoh:
git commit -m "feat: Tambah API endpoint untuk real-time data"
git commit -m "fix: Perbaikan validasi data RGB dari ESP32"
git commit -m "docs: Update README dengan panduan instalasi"
```

### 🛠️ **Perintah Berguna Lainnya**

```bash
# Lihat history commit
git log --oneline

# Lihat perubahan file
git diff

# Undo commit terakhir (keep changes)
git reset --soft HEAD~1

# Undo commit terakhir (discard changes)
git reset --hard HEAD~1

# Pull update dari GitHub
git pull origin main

# Lihat remote repository
git remote -v
```

### 📁 **File .gitignore (Pastikan Ada)**

Buat file `.gitignore` di root project:
```
/vendor/
/node_modules/
/public/storage
/storage/*.key
/storage/logs/*
.env
.env.backup
.phpunit.result.cache
composer.lock
package-lock.json
.DS_Store
Thumbs.db
```

### 🚀 **Quick Update Command**

Untuk update cepat sehari-hari:
```bash
# One-liner untuk update cepat
git add . && git commit -m "update: $(date +'%Y-%m-%d %H:%M')" && git push
```

Dengan mengikuti panduan ini, Anda dapat dengan mudah melakukan update dan mengelola versi kode sistem pakar kematangan tomat di GitHub repository!
        