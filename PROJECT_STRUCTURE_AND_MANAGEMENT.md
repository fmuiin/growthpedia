# GrowthPedia Project Structure and Management

## Ringkasan

GrowthPedia adalah aplikasi **modular monolith** berbasis Laravel 13 di backend, React 19 + TypeScript 6 di frontend, dengan Inertia.js sebagai jembatan SSR-style SPA.  
Semua domain bisnis dipisah per modul di `app/Modules`, tetapi tetap dideploy sebagai satu aplikasi Laravel.

Dokumen ini merangkum:
- Struktur direktori project
- Pola arsitektur dan alur request
- Cara pengelolaan modul, dependency, konfigurasi, dan environment
- Strategi testing
- Kondisi manajemen project saat ini dan rekomendasi perbaikan

---

## 1) Struktur Direktori Utama

Direktori penting di root project:

- `app/` -> kode backend Laravel
- `app/Modules/` -> domain modules (Admin, User, Course, dsb.)
- `app/Shared/` -> komponen lintas modul (base DTO, base contract, exception umum)
- `resources/js/` -> frontend React + TypeScript (Inertia pages + reusable components)
- `routes/` -> route global level aplikasi
- `config/` -> konfigurasi service/framework
- `database/` -> migration, factory, seeder
- `tests/` -> test global (Feature/Unit)
- `bootstrap/` -> bootstrap aplikasi dan inisialisasi provider

Top-level dependency/build files:
- `composer.json` -> dependency PHP dan script backend
- `package.json` -> dependency frontend dan script Vite
- `vite.config.ts` -> konfigurasi build frontend
- `phpunit.xml` -> konfigurasi test suite
- `.env.example` -> template environment

---

## 2) Arsitektur Aplikasi

### 2.1 Modular Monolith

GrowthPedia memakai pola **modular monolith**, artinya:
- Modul dipisahkan berdasarkan domain bisnis
- Tiap modul punya controller/service/request/dto/routes sendiri
- Seluruh modul tetap berjalan dalam satu runtime Laravel dan satu database

Modul yang tersedia saat ini:
- `Admin`
- `Catalog`
- `Certificate`
- `Course`
- `Discussion`
- `Lesson`
- `Payment`
- `Progress`
- `Subscription`
- `User`

### 2.2 Auto-registration Module Providers

Provider modul tidak didaftarkan satu per satu secara manual.  
`app/Providers/ModuleServiceProvider.php` melakukan scanning `app/Modules/*/Providers/*ServiceProvider.php` dan register otomatis ke container.

Provider ini sendiri terdaftar pada `bootstrap/providers.php`.

### 2.3 Konsep Dependency Internal

Pola yang dipakai:
- Controller bergantung pada interface service (`Contracts/*ServiceInterface.php`)
- Binding interface -> implementasi dilakukan di `Providers/*ServiceProvider.php`
- Validasi request menggunakan Form Request (`Requests/*.php`)
- Transfer data antar layer memakai DTO (`DTOs/*.php`)

Pola repository formal (folder `Repositories`) belum terlihat konsisten di modul; akses data umumnya langsung via Eloquent model di service/controller.

---

## 3) Alur Request End-to-End

Secara umum alurnya:

1. Client mengakses route web (global atau route modul)
2. Route diarahkan ke controller modul
3. Controller validasi input via Form Request
4. Controller memanggil service interface
5. Service menjalankan business logic dan akses model/database
6. Response dikembalikan:
   - JSON (jika endpoint API-like), atau
   - Inertia response untuk render halaman React

Pada halaman web:
- Laravel mengirim props Inertia
- React page di `resources/js/Pages` merender UI
- TypeScript types di `resources/js/Types` membantu konsistensi kontrak data

---

## 4) Pengelolaan Routing

### 4.1 Routing Global

Routing utama aplikasi dikonfigurasi di:
- `bootstrap/app.php` (withRouting ke `routes/web.php`)
- `routes/web.php` untuk route utama seperti landing/home

### 4.2 Routing per Modul

Setiap modul meng-load route sendiri lewat provider:
- `loadRoutesFrom(__DIR__ . '/../Routes/web.php')`

Dengan pola ini, route domain terlokalisasi dalam modul masing-masing dan lebih mudah dikelola saat skala bertambah.

---

## 5) Pengelolaan Frontend

Frontend berada di `resources/js` dengan pola:
- `Pages/` -> Inertia pages (entry tiap halaman)
- `Components/` -> komponen reusable
- `Hooks/` -> custom React hooks
- `Types/` -> interface/type TS domain
- `Utils/` -> helper frontend

Tooling frontend:
- Vite 8 (`vite`, `laravel-vite-plugin`)
- React 19
- TypeScript 6 dengan strict mode
- Tailwind CSS 4

Script utama:
- `npm run dev` -> dev server Vite
- `npm run build` -> production build asset

---

## 6) Pengelolaan Dependency dan Script

## 6.1 Backend (Composer)

Dependency inti:
- `php ^8.3`
- `laravel/framework ^13`
- `inertiajs/inertia-laravel ^3`

Dependency dev:
- Pest, PHPUnit, Eris (property-based)
- Pint (formatter)
- Pail (log tailing), Collision, Faker, Mockery

Composer scripts penting:
- `composer setup` -> install + bootstrap env + migrate + build
- `composer dev` -> menjalankan server, queue listener, log watcher, dan Vite secara concurrent
- `composer test` -> clear config + run test

### 6.2 Frontend (NPM)

Script di `package.json` sederhana dan fokus:
- `dev`
- `build`

Pendekatan ini cocok untuk tim kecil-menengah karena command surface area tetap ringkas.

---

## 7) Pengelolaan Konfigurasi dan Environment

Template env tersedia di `.env.example` dengan default stack:
- DB: PostgreSQL (untuk runtime app)
- Cache/Queue: Redis

Konfigurasi terpusat di `config/*.php`:
- `database.php`, `cache.php`, `queue.php`, `auth.php`, `services.php`, dll.

Catatan penting:
- Implementasi payment gateway Stripe membaca `config('services.stripe.*')`.
- Saat ini `config/services.php` belum menampilkan section `stripe`.
- Ini perlu sinkronisasi agar konfigurasi payment tidak gagal saat deploy/runtime.

---

## 8) Pengelolaan Testing dan Quality

Testing menggunakan Pest + PHPUnit.

`phpunit.xml` mendefinisikan suite:
- `tests/Unit`
- `tests/Feature`
- `app/Modules/*/Tests`

`tests/Pest.php` juga meng-extend test case untuk folder modul (`../app/Modules`), sehingga test modular bisa dijalankan konsisten.

Kondisi saat ini:
- Unit test pada modul sudah cukup banyak
- Feature/integration test terlihat belum sebanyak unit test

Implikasi manajemen kualitas:
- Logic domain relatif terjaga
- Risiko regresi alur end-to-end (HTTP, auth flow, cross-module integration) masih perlu ditutup dengan feature test tambahan

---

## 9) Pengelolaan Cross-Module Communication

Pola komunikasi antar modul dilakukan lewat:
- Interface/service contract (dependency inversion)
- Event/listener Laravel untuk side effects lintas domain (mis. payment -> subscription/progress)

Keuntungan:
- Coupling lebih rendah dibanding direct call antar service konkret
- Side effect bisa dipisah dan diuji lebih modular

Hal yang perlu dijaga:
- Hindari akses langsung model lintas modul jika tidak perlu
- Standarkan "public API" per modul (service contract + DTO) agar boundary tetap bersih

---

## 10) Status Manajemen Project (Operational View)

### Sudah baik
- Struktur modular jelas dan scalable
- Auto-discovery provider modul memudahkan maintenance
- Controller-Service-Request-DTO pattern konsisten
- Stack modern dan relevan untuk product web app
- Script dev/test cukup praktis untuk local development

### Perlu diperkuat
- Tambahkan konfigurasi `services.stripe` agar sinkron dengan implementasi payment
- Tambah pipeline CI (minimal: install, lint/format check, test, build)
- Tingkatkan cakupan feature/integration tests
- Pertimbangkan standar lint frontend (ESLint/Prettier) jika tim frontend bertambah
- Dokumentasikan deployment flow (environment matrix, queue worker, scheduler, asset build)

---

## 11) Rekomendasi Praktis Pengelolaan (Prioritas)

Prioritas 1 (cepat, dampak tinggi):
1. Tambah konfigurasi `services.stripe` + variabel env terkait
2. Buat CI dasar untuk test/build otomatis di setiap PR
3. Tambahkan smoke feature tests untuk auth, subscription checkout, dan akses course

Prioritas 2:
1. Definisikan guideline boundary modul (kapan boleh akses model lintas modul)
2. Standardisasi contract antar modul untuk use-case kritikal
3. Tambah observability dasar (error monitoring + queue failure alert)

Prioritas 3:
1. Evaluasi kebutuhan repository abstraction per domain yang query-heavy
2. Perluas test matrix ke skenario cross-module dan concurrency edge cases

---

## 12) Kesimpulan

GrowthPedia sudah berada pada fondasi arsitektur yang sehat untuk skala produk: modular monolith, service contracts, dan pemisahan frontend-backend yang rapi via Inertia.  
Fokus manajemen berikutnya sebaiknya pada penguatan **operational excellence** (CI/CD, deployment docs, integration tests, dan sinkronisasi konfigurasi payment) agar reliability produksi meningkat seiring pertumbuhan fitur dan tim.

