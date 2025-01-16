<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtamaKlasifikasiC45BiologiController extends Controller
{
    public function klasifikasi_mahasiswa_jurusan_biologi(Request $request)
    {
         // Ambil tahun dari database
         $years = DB::table('mahasiswa as m')
         ->select(DB::raw('YEAR(m.tahun_angkatan) as year'))
         ->groupBy('year')
         ->orderBy('year', 'DESC')
         ->get();
 
     // Ambil input dari pengguna
     $predictedLulus = $request->input('predicted_lulus'); // Ambil input dari pengguna
     $year = $request->input('year', 'Semua'); 
 
     // Inisialisasi variabel untuk hasil query dan perhitungan entropi
     $result1 = [];
     $totalMahasiswa = 0;
     $entropyTotal1 = 0;
        // Menjalankan query untuk mengambil data dari database
        
           
         // Query 1: Mengambil data jenis status dan jumlah mahasiswa
         $query1 = DB::table('matakuliah as mk')
         ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
         ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
         ->select(
             DB::raw("
                 CASE 
                     WHEN mk.status IN ('Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 'Belum Lulus'
                     WHEN mk.status = 'Lulus' THEN 'Lulus'
                     ELSE 'Belum Lulus'
                 END AS jenis_status
             "),
             DB::raw("COUNT(m.nim) AS total_mahasiswa")
         )
         ->where('j.jurusan', 'Biologi')
         ->groupBy('jenis_status');
 
         // Query 2: Mengambil data status mahasiswa (Aktif, Lulus, dsb)
         $query2= DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 'mk.status', // Tambahkan status ke dalam select
                 DB::raw("COUNT(mk.nim) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy('mk.status')
             ->orderBy('mk.status');

             $query3 = DB::table('mahasiswa as m')
             ->join('matakuliah as mk', 'mk.nim', '=', 'm.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->join('jenis_sekolah_mahasiswa_baru as js', 'm.kd_jenis_sekolah', '=', 'js.kd_jenis_sekolah')
             ->select(
                 DB::raw("IF(js.jenis_sekolah = '' OR js.jenis_sekolah IS NULL, 'Tidak Terdata', js.jenis_sekolah) AS jenis_sekolah"),
                 DB::raw("COUNT(m.nim) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy('jenis_sekolah')  // Perbaikan: Gunakan js.jenis_sekolah untuk konsistensi
             ->orderBy('jenis_sekolah'); // Perbaikan: Urutkan berdasarkan jenis_sekolah yang benar
 
         
         $query4 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN mk.tanggal_lulus IS NULL THEN 'Belum Daftar'
                     ELSE YEAR(mk.tanggal_lulus)
                 END AS tahun_lulus"),
                 
                 DB::raw("COUNT(*) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy('tahun_lulus')
             ->orderByRaw("FIELD(tahun_lulus, 'Belum Daftar') DESC, tahun_lulus ASC"); // Atur urutan dengan 'Belum Daftar' di atas
         
 
         // Query 3
         $query5 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN mk.sks >= 144 THEN 'Memenuhi'
                     ELSE 'Belum Memenuhi'
                 END AS kategori_sks"),
                 DB::raw("COUNT(*) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy('kategori_sks')  // Menggunakan alias kategori_sks yang sudah didefinisikan di select
             ->orderBy('kategori_sks');  // Menggunakan alias kategori_sks yang sudah didefinisikan di select
 
 
         $query6 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN REPLACE(mk.ipk, ',', '.') >= 2.75 AND REPLACE(mk.ipk, ',', '.') < 3.00 THEN 'Memuaskan'
                     WHEN REPLACE(mk.ipk, ',', '.') >= 3.00 AND REPLACE(mk.ipk, ',', '.') < 3.50 THEN 'Sangat Memuaskan'
                     WHEN REPLACE(mk.ipk, ',', '.') >= 3.50 THEN 'Pujian'
                     ELSE 'Perbaiki'
                 END AS kategori_ipk"),
                 DB::raw("COUNT(*) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy(DB::raw("kategori_ipk")) 
             ->orderBy('kategori_ipk');
 
        $query7 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN mk.kuliah_kerja_lapangan IS NULL OR mk.kuliah_kerja_lapangan = '' THEN 
                         CASE 
                             WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                             ELSE 'belum terdaftar'
                         END
                     ELSE CONCAT('Semester ', mk.kuliah_kerja_lapangan)
                 END AS kategori_pkl"),
                 DB::raw("COUNT(m.nim) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', '=', 'Biologi')
             ->groupBy('kategori_pkl') // Menggunakan DB::raw untuk grup berdasarkan pkl
             ->orderBy('kategori_pkl'); // Mengurutkan berdasarkan kategori pkl
 
         $query8 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN mk.kuliah_kerja_nyata IS NOT NULL AND mk.kuliah_kerja_nyata != '' 
                         THEN CONCAT('Semester ', mk.kuliah_kerja_nyata)
                     WHEN mk.kuliah_kerja_nyata_kkn IS NOT NULL AND mk.kuliah_kerja_nyata_kkn != '' 
                         THEN CONCAT('Semester ', mk.kuliah_kerja_nyata_kkn)
                     WHEN mk.kuliah_kerja_nyata IS NULL AND mk.kuliah_kerja_nyata_kkn IS NULL 
                         THEN NULL
                     ELSE 
                         CASE 
                             WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                             ELSE 'Belum Daftar'
                         END
                 END AS kategori_kkn"),
                 
                 DB::raw("COUNT(mk.nim) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi')
             ->groupBy('kategori_kkn') 
             ->orderBy('kategori_kkn'); 
             
        $query9 = DB::table('matakuliah as mk')
             ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
             ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
             ->select(
                 DB::raw("CASE 
                     WHEN mk.seminar_hasil IS NOT NULL AND mk.seminar_hasil != '' 
                         THEN CONCAT('Semester ', mk.seminar_hasil)
                     WHEN mk.seminar IS NOT NULL AND mk.seminar != '' 
                         THEN CONCAT('Semester ', mk.seminar)
                     WHEN mk.seminar_hasil IS NULL AND mk.seminar IS NULL THEN NULL
                     ELSE 
                         CASE 
                             WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                             ELSE 'belum daftar'
                         END
                 END AS kategori_seminar"),
                 DB::raw("COUNT(*) AS total_mahasiswa"),
                 DB::raw("COUNT(CASE WHEN mk.status IN ('Tidak Aktif', 'Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 1 END) AS belum_lulus"),
                 DB::raw("COUNT(CASE WHEN mk.status = 'Lulus' THEN 1 END) AS lulus")
             )
             ->where('j.jurusan', 'Biologi') 
             ->groupBy('kategori_seminar')
             ->orderBy('kategori_seminar'); 
         
         
        // Filter by year if provided
        if ($year && $year !== 'Semua') {
            $query1->whereYear('m.tahun_angkatan', '=', $year);
            $query2->whereYear('m.tahun_angkatan', '=', $year);
            $query3->whereYear('m.tahun_angkatan', '=', $year);
            $query4->whereYear('m.tahun_angkatan', '=', $year);
            $query5->whereYear('m.tahun_angkatan', '=', $year);
            $query6->whereYear('m.tahun_angkatan', '=', $year);
            $query7->whereYear('m.tahun_angkatan', '=', $year);
            $query8->whereYear('m.tahun_angkatan', '=', $year);
            $query9->whereYear('m.tahun_angkatan', '=', $year);

        }

        
    
        // Eksekusi query untuk mengambil hasil
        $result1 =  $query1->get();
        $result2 = $query2->get();
        $result3 = $query3->get();
        $result4 = $query4->get();
        $result5 = $query5->get();
        $result6 = $query6->get();
        $result7 = $query7->get();
        $result8 = $query8->get();
        $result9 = $query9->get();
        // Hitung jumlah mahasiswa yang lulus dan status lainnya
        $statusCount1 = [];
        foreach ($result1 as $item) {
            $statusCount1[$item->jenis_status] = $item->total_mahasiswa;
        }
    
        // Hitung entropi untuk result1
        $total1 = array_sum($statusCount1);
        foreach ($statusCount1 as $count) {
            if ($count > 0) {
                $probability = $count / $total1;
                $entropyTotal1 -= $probability * log($probability, 2); // Rumus Entropi
            }
        }
        //dd($result1);
        // Pastikan predictedLulus memiliki nilai integer
        if (is_null($predictedLulus)) {
            $predictedLulus = 0; // Set ke 0 jika null
        }
        
        // Hitung entropi berbobot total (jika ada fungsi ini)
        $total2 = $this->calculateTotalWeightedEntropy($result2, $entropyTotal1, $total1, 'status');
        $total3 = $this->calculateTotalWeightedEntropy($result3, $entropyTotal1, $total1, 'jenis_sekolah');
        $total4 = $this->calculateTotalWeightedEntropy($result4, $entropyTotal1, $total1, 'tahun_lulus');
        $total5 = $this->calculateTotalWeightedEntropy($result5, $entropyTotal1, $total1, 'kategori_sks');
        $total6 = $this->calculateTotalWeightedEntropy($result6, $entropyTotal1, $total1,'kategori_ipk');
        $total7 = $this->calculateTotalWeightedEntropy($result7, $entropyTotal1, $total1, 'kategori_pkl');
        $total8 = $this->calculateTotalWeightedEntropy($result8, $entropyTotal1, $total1, 'kategori_kkn');
        $total9 = $this->calculateTotalWeightedEntropy($result9, $entropyTotal1, $total1, 'kategori_seminar');
        
// Mengembalikan view dengan data dan pemetaan kolom
return view('utama.algoritma.klasifikasi_c45_matakuliah_biologi_mahasiswa', compact( 'total1','years', 'year', 'result1', 'total2', 
        'total3', 'total4', 'total5', 'total6', 'total7', 'total8', 'total9', 'entropyTotal1', 'totalMahasiswa'));
    }
    
    public function prediksi_mahasiswa_jurusan_biologi(Request $request)
    {
         // Ambil tahun dari database
         $years = DB::table('mahasiswa as m')
         ->select(DB::raw('YEAR(m.tahun_angkatan) as year'))
         ->groupBy('year')
         ->orderBy('year', 'DESC')
         ->get();
 
        // Ambil input dari pengguna
        $predictedLulus = $request->input('predicted_lulus');
        $year = $request->input('year', 'Semua'); 
        // Hitung test_size
        $testSize = 100 - $predictedLulus; // Menghitung test_size
        // Inisialisasi variabel untuk hasil query dan perhitungan entropi
        $result1 = [];
        $totalMahasiswa = 0;
        $entropyTotal1 = 0;
        $query10 = DB::table('matakuliah as mk')
        ->join('mahasiswa as m', 'm.nim', '=', 'mk.nim')
        ->join('jurusan as j', 'mk.kd_jurusan', '=', 'j.kd_jurusan')
        ->join('jenis_sekolah_mahasiswa_baru as js', 'js.kd_jenis_sekolah', '=', 'm.kd_jenis_sekolah')
        ->select(
    DB::raw("CASE 
                WHEN mk.sks >= 144 THEN 'Memenuhi'
                ELSE 'Belum Memenuhi'
            END AS kategori_sks"),
            DB::raw("CASE 
                WHEN REPLACE(mk.ipk, ',', '.') >= 2.75 AND REPLACE(mk.ipk, ',', '.') < 3.00 THEN 'Memuaskan'
                WHEN REPLACE(mk.ipk, ',', '.') >= 3.00 AND REPLACE(mk.ipk, ',', '.') < 3.50 THEN 'Sangat Memuaskan'
                WHEN REPLACE(mk.ipk, ',', '.') >= 3.50 THEN 'Pujian'
                WHEN REPLACE(mk.ipk, ',', '.') < 2.75 THEN 'Perbaiki'
            END AS kategori_ipk"),
            DB::raw("CASE 
                 WHEN mk.kuliah_kerja_lapangan IS NULL OR mk.kuliah_kerja_lapangan = '' THEN 
                     CASE 
                         WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                         ELSE 'belum terdaftar'
                     END
                 ELSE CONCAT('Semester ', mk.kuliah_kerja_lapangan)
             END AS kategori_pkl"),
            DB::raw("CASE 
                WHEN mk.kuliah_kerja_nyata IS NOT NULL AND mk.kuliah_kerja_nyata != '' 
                    THEN CONCAT('Semester ', mk.kuliah_kerja_nyata)
                WHEN mk.kuliah_kerja_nyata_kkn IS NOT NULL AND mk.kuliah_kerja_nyata_kkn != '' 
                    THEN CONCAT('Semester ', mk.kuliah_kerja_nyata_kkn)
                WHEN mk.kuliah_kerja_nyata IS NULL AND mk.kuliah_kerja_nyata_kkn IS NULL 
                    THEN NULL
                ELSE 
                    CASE 
                        WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                        ELSE 'Belum Daftar'
                    END
            END AS kategori_kkn"),
            DB::raw("CASE 
                 WHEN mk.seminar_hasil IS NOT NULL AND mk.seminar_hasil != '' 
                     THEN CONCAT('Semester ', mk.seminar_hasil)
                 WHEN mk.seminar IS NOT NULL AND mk.seminar != '' 
                     THEN CONCAT('Semester ', mk.seminar)
                 WHEN mk.seminar_hasil IS NULL AND mk.seminar IS NULL THEN NULL
                 ELSE 
                     CASE 
                         WHEN mk.status = 'Lulus' THEN 'lulus tidak terdata'
                         ELSE 'belum daftar'
                     END
             END AS kategori_seminar"),
            DB::raw("
            CASE 
                WHEN mk.status IN ('Aktif', 'Cuti', 'Drop Out', 'Mengundurkan Diri') THEN 'Belum Lulus'
                WHEN mk.status = 'Lulus' THEN 'Lulus'
                ELSE 'Belum Lulus'
            END AS kategori
            "),
        )
        ->where('j.jurusan', '=', 'Biologi');
        
        // Filter by year if provided
        if ($year && $year !== 'Semua') {
            $query10->whereYear('m.tahun_angkatan', '=', $year);
        }

        // Eksekusi query untuk mengambil hasil
        $query10 = $query10->get();

        //dd($query10, $data);
         // Hitung test_size
        $testSize = 100 - $predictedLulus; // Menghitung test_size

        // Simpan test_size ke file JSON untuk digunakan di Python
        $testSizeFilePath = 'C:\\xampp\\htdocs\\skripsi\\app\\python_scripts\\biologi\\test_size.json';
        file_put_contents($testSizeFilePath, json_encode(['test_size' => $testSize]));

        // Mengonversi hasil query menjadi format yang bisa digunakan oleh Python
        $data = [];
        foreach ($query10 as $row) {
            $data[] = [
                'kategori_sks' => $row->kategori_sks, // Satuan Kredit Skor
                'kategori_ipk' => $row->kategori_ipk, // Indeks Prestasi Kumulatif
                'kategori_pkl' => $row->kategori_pkl, // Praktek Kerja Lapangan
                'kategori_kkn' => $row->kategori_kkn, // Kuliah Kerja Nyata
                'kategori_seminar' => $row->kategori_seminar, // Seminar
            ];
            
        }

        //dd($query10, $data);

         // Menyimpan data ke file JSON untuk digunakan di Python
         $jsonFilePath = 'C:\\xampp\\htdocs\\skripsi\\app\\python_scripts\\biologi\\data.json';
         file_put_contents($jsonFilePath, json_encode($data));
        // Menyimpan informasi kol om yang terkait dengan setiap kategori
        $columnMapping = [
            'kategori_sks' => 'Satuan Kredit Skor',
            'kategori_ipk' => 'Indeks Prestasi Kumulatif',
            'kategori_pkl' => 'Praktek Kerja Lapangan', // atau kolom lain yang relevan
            'kategori_kkn' => 'Kuliah Kerja Nyata',
            'kategori_seminar' => 'Seminar',
        ];
        // Menjalankan skrip Python
        $pythonScriptPath = 'C:\\xampp\\htdocs\\skripsi\\app\\python_scripts\\biologi\\decision_tree_visualization.py';
        $output = shell_exec("python \"$pythonScriptPath\"");
        
        // Decode output JSON
        $result = json_decode($output, true);
 

        // Pastikan hasilnya adalah array dan memiliki kunci yang benar
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Jika terjadi kesalahan saat decoding JSON
            $outputData = [
                'tree_text' => 'Gagal mengurai output dari skrip Python.',
                'first_true_path' => [],
                'accuracy' => null,  // Menambahkan key untuk akurasi
            ];
        } else {
            // Ambil data dari hasil
            $treeText = isset($result['tree_text']) ? $result['tree_text'] : 'Tidak ada teks pohon keputusan.';
            $firstTruePath = isset($result['first_true_path']) ? $result['first_true_path'] : [];
            $accuracy = isset($result['accuracy']) ? $result['accuracy'] : null;  // Mengambil akurasi
            $testSize = isset($result['test_size']) ? $result['test_size'] : null;  // Mengambil test_size
            $precision = isset($result['precision']) ? $result['precision'] : null;  // Mengambil precision
            $recall = isset($result['recall']) ? $result['recall'] : null;  // Mengambil recall
            $f1Score = isset($result['f1_score']) ? $result['f1_score'] : null;  // Mengambil F1 Score

            // Mengambil matriks kebingungan
            $confusionMatrix = isset($result['confusion_matrix']) ? $result['confusion_matrix'] : [];  // Mengambil matriks kebingungan

            // Memastikan matriks kebingungan memiliki ukuran yang benar
            if (count($confusionMatrix) > 0) {
                // Mengambil TP, TN, FP, FN dari matriks kebingungan
                $TN = $confusionMatrix[0][0];  // True Negatives
                $FP = $confusionMatrix[0][1];  // False Positives
                $FN = $confusionMatrix[1][0];  // False Negatives
                $TP = $confusionMatrix[1][1];  // True Positives
            } else {
                // Jika matriks kebingungan tidak ada, set nilai default
                $TP = $FP = $TN = $FN = 0;
            }

            // Mengatur output sebagai array
            $outputData = [
                'tree_text' => $treeText,
                'first_true_path' => $firstTruePath,
                'accuracy' => $accuracy,  // Menambahkan akurasi ke output
                'test_size' => $testSize,  // Menambahkan test_size ke output
                'precision' => $precision,  // Menambahkan precision ke output
                'recall' => $recall,        // Menambahkan recall ke output
                'f1_score' => $f1Score,     // Menambahkan F1 Score ke output
                'TP' => $TP,                // Menambahkan True Positives ke output
                'FP' => $FP,                // Menambahkan False Positives ke output
                'TN' => $TN,                // Menambahkan True Negatives ke output
                'FN' => $FN,                // Menambahkan False Negatives ke output
                'confusion_matrix' => $confusionMatrix  // Menambahkan matriks kebingungan ke output
            ];

            //DD($outputData);
            }

        // Mengembalikan view dengan data dan pemetaan kolom
        return view('utama.algoritma.prediksi.prediksi_matakuliah_biologi_mahasiswa', compact('outputData', 'columnMapping','years', 'predictedLulus', 'year' 
            ));
     }
   

    
     public function calculateEntropy(object $item, float $entropyTotal1, float $total1): array {
        $data2 = [
            'total_mahasiswa' => $item->total_mahasiswa,
            'belum_lulus' => $item->belum_lulus,
            'lulus' => $item->lulus
        ];

        // Entropy Calculation
        $entropy2 = 0;

        foreach ($data2 as $key => $value) {
            if ($key != 'total_mahasiswa' && $value > 0) {
                $probability = $value / $item->total_mahasiswa;
                $entropy2 -= $probability * log($probability, 2); // Rumus Entropi
            }
        }

        // Weighted Entropy Calculation
        $probability_total = $data2['total_mahasiswa'] / $total1;
        $weighted_entropy = $probability_total * $entropy2;

        // Add entropy to data
        $data2['entropy'] = $entropy2;
        $data2['probability_total'] = $probability_total;
        $data2['weighted_entropy'] = $weighted_entropy;

        return $data2;
    }

    public function calculateTotalWeightedEntropy($items, $entropyTotal1, $total1, $key) {
        $total_weighted_entropy = 0;
        $results = [];
        
        // Hitung weighted entropy untuk setiap item
        foreach ($items as $item) {
            $result = $this->calculateEntropy($item, $entropyTotal1, $total1);
            
            // Ganti kategori dengan nilai dari $item->$key
            $kategori = $item->$key; // Simpan nilai kategori
            $result['kategori'] = $kategori; // Mengganti kategori dengan nilai dari $item->$key
            $total_weighted_entropy += $result['weighted_entropy'];
            
            // Simpan hasil dengan kategori sebagai kunci
            $results[$kategori] = $result; // Ganti indeks dengan kategori
        }
        
        // Hitung gain berdasarkan total weighted entropy
        $gainC45 = $entropyTotal1 - $total_weighted_entropy;
        
        // Tambahkan total weighted entropy dan gain ke hasil
        foreach ($results as &$result) {
            // Hapus elemen 'kategori'
            unset($result['kategori']); // Menghapus elemen 'kategori'
            
            $result['total_weighted_entropy'] = $total_weighted_entropy;
            $result['gain_45'] = $gainC45; // Menambahkan gain_45 ke setiap item
        }
        
        return $results;
    }
}