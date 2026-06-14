Analisis Tugas 3 – Enterprise Application Integration

Nama: Yudha Pamungkas
NIM: 102022400337
Kelas: SI-48-09

Analisis Transaksi Kritis
Transaksi yang Dipilih

Transaksi kritis yang dipilih pada Loan Service adalah proses Approve Loan melalui endpoint:

POST /api/v1/loans/{id}/approve

Transaksi ini dipilih karena merupakan titik keputusan utama dalam proses bisnis pinjaman. Ketika proses approval berhasil dilakukan, status pinjaman berubah dari PENDING menjadi APPROVED sehingga mempengaruhi kelanjutan proses bisnis pada sistem.

Selain melakukan perubahan data utama, transaksi ini juga melibatkan integrasi dengan layanan terpusat yang disediakan pada Tugas 3, yaitu:

Federated SSO
SOAP Audit Service
RabbitMQ Publisher

Hal tersebut menjadikan proses approval sebagai transaksi paling kritis pada Loan Service.

Role Lokal

Loan Service menerapkan role lokal Admin untuk melakukan proses approval pinjaman.

Hanya pengguna yang memiliki hak akses Admin yang dapat menjalankan endpoint approval pinjaman. Role ini digunakan untuk memastikan bahwa keputusan persetujuan pinjaman hanya dapat dilakukan oleh pihak yang berwenang.

Alasan Transaksi Dikategorikan Kritis
1. Mengubah State Bisnis Secara Permanen

Proses approval mengubah status pinjaman dari kondisi menunggu persetujuan (PENDING) menjadi disetujui (APPROVED).

Perubahan ini merupakan perubahan state utama yang mempengaruhi proses bisnis berikutnya sehingga harus dijaga konsistensi dan keamanannya.

2. Memerlukan Autentikasi Federated SSO

Sebelum mengakses layanan SOAP Audit dan RabbitMQ Publisher, Loan Service harus memperoleh JWT Token dari layanan SSO menggunakan API Key yang diberikan.

Token tersebut digunakan sebagai Bearer Token ketika berkomunikasi dengan layanan terpusat sehingga hanya service yang terotorisasi yang dapat mengakses layanan integrasi.

3. Harus Dicatat ke Sistem Audit Terpusat (SOAP)

Setelah pinjaman disetujui, Loan Service mengirimkan data transaksi ke layanan SOAP Audit menggunakan format XML Envelope.

Data JSON pada aplikasi ditransformasikan menjadi XML sebelum dikirim ke server audit. Sistem kemudian menyimpan Receipt Number yang dikembalikan oleh server sebagai bukti bahwa aktivitas berhasil dicatat.

Hasil Pengujian
SOAP Status    : SUCCESS
Receipt Number : IAE-LOG-2026-07FA6645

Keberhasilan penyimpanan Receipt Number menunjukkan bahwa proses integrasi SOAP XML Client berjalan dengan baik.

4. Harus Disebarkan ke Service Lain (RabbitMQ)

Setelah audit berhasil dilakukan, Loan Service mempublikasikan event LoanApproved ke RabbitMQ dalam format JSON.

Contoh payload:

{
  "event": "LoanApproved",
  "loan_id": "...",
  "status": "approved"
}

Pengiriman event ini memungkinkan service lain menerima informasi bahwa terdapat pinjaman yang telah disetujui tanpa perlu mengakses database Loan Service secara langsung.

Hasil pengujian menunjukkan bahwa event berhasil dipublikasikan ke RabbitMQ tanpa error.

Sequence Diagram

Sequence Diagram Approval Loan disertakan pada file:

SequenceDiagram.png

Diagram tersebut menggambarkan alur approval pinjaman mulai dari verifikasi JWT melalui SSO_IAE, pengambilan data pinjaman, perubahan status pinjaman menjadi APPROVED (transaksi kritis), pengiriman audit ke SOAP Audit Service, publikasi event ke RabbitMQ, hingga penyimpanan nomor bukti transaksi ke database dan pengiriman respons sukses kepada pengguna. 

Admin mengirim request approval pinjaman.
Controller meminta JWT Token ke IAE Cloud SSO.
SSO mengembalikan JWT Token.
Controller mengambil data pinjaman dari database.
Status pinjaman diubah menjadi APPROVED.
Controller mengirim SOAP Audit ke layanan audit terpusat.
SOAP Audit mengembalikan Receipt Number.
Controller mempublikasikan event LoanApproved ke RabbitMQ.
RabbitMQ mengembalikan status publish berhasil.
Sistem mengirim respons sukses kepada Admin.
Operasi yang Tidak Dipilih
GET /api/v1/loans

Hanya menampilkan daftar pinjaman dan tidak mengubah state sistem.

POST /api/v1/loans

Hanya membuat pengajuan pinjaman baru dan belum menghasilkan keputusan bisnis final.

GET /api/v1/loans/{id}

Hanya menampilkan detail data pinjaman tanpa mengubah data.

DELETE /api/v1/loans/{id}

Melakukan penghapusan data tetapi tidak melibatkan integrasi SSO, SOAP Audit, dan RabbitMQ secara bersamaan.

![Sequence Diagram](<SequenceDiagram.jpg>)