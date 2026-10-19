# Python Face Vector API

Layanan ini membuat dan memverifikasi face vector menggunakan Python `face_recognition` (dlib). PHP project memanggil API ini melalui HTTP.

## 1. Jalankan lokal (Windows + XAMPP)

Disarankan Python **3.10 atau 3.11** untuk kompatibilitas `dlib/face-recognition`. Buka PowerShell di folder `python_face_api`, lalu jalankan:

```powershell
py -3.11 -m venv venv
.\venv\Scripts\python.exe -m pip install --upgrade pip
.\venv\Scripts\python.exe -m pip install -r requirements.txt
.\venv\Scripts\python.exe app.py
```

Memanggil `python.exe` dari folder `venv` secara langsung memastikan Flask dan
dependensi lain dipasang pada interpreter yang sama dengan yang menjalankan API.
Jika virtualenv sudah aktif tetapi muncul `No module named 'flask'`, jalankan
perintah instalasi dependensi di atas, lalu jalankan kembali API.

API berjalan di:

`http://127.0.0.1:5002`

Membuka alamat tersebut menampilkan informasi endpoint API. Untuk memeriksa
kondisi layanan, buka:

`http://127.0.0.1:5002/health`

## 2. Alur

- `POST /encode` menerima `image` dan mengembalikan 128 angka.
- `POST /verify` menerima `image` + `stored_vector` dan mengembalikan `match`, `distance`, dan `threshold`.

PHP menyimpan JSON vector ke `users.face_vector`.

## 3. Hosting

Shared hosting PHP biasa tidak bisa menjalankan `app.py` hanya dengan upload file. Python API harus berjalan pada komputer/server yang dapat diakses oleh PHP.

Jika PHP dan Python berjalan di server yang sama, ubah `FACE_API_URL` sesuai alamat internal/public Python. Jika Python berada di VPS, gunakan URL HTTPS VPS tersebut.

## 4. Threshold

Default `0.50`. Semakin kecil threshold, semakin ketat pencocokannya. Nilai dapat diubah dengan environment variable `FACE_THRESHOLD`.
