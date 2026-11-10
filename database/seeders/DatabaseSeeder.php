<?php

namespace Database\Seeders;

use App\Models\Bahan;
use App\Models\Pelanggan;
use App\Models\Pembelian;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\Tahapan;
use App\Models\User;
use App\Models\Wo;
use App\Models\WoTahapan;
use App\Support\Gagal;
use App\Support\Pabrik;
use App\Support\Produksi;
use App\Support\Stok;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh konveksi + simulasi operasional 75 hari terakhir.
 * Simulasi memakai layanan yang sama dengan aplikasi (Produksi, Stok), jadi kartu stok, HPP, dan status selalu konsisten.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(42);
        DB::transaction(fn () => $this->isi());
    }

    private function isi(): void
    {
        $mulai = today()->subDays(75);
        Carbon::setTestNow($mulai->copy()->setTime(8, 0));

        $admin = User::create(['name' => 'Bambang Wicaksono', 'email' => 'admin@produksi.test', 'password' => 'admin123', 'peran' => 'admin']);
        User::create(['name' => 'Rina Kusuma', 'email' => 'ppic@produksi.test', 'password' => 'ppic123', 'peran' => 'ppic']);
        $gudang = User::create(['name' => 'Slamet Riyadi', 'email' => 'gudang@produksi.test', 'password' => 'gudang123', 'peran' => 'gudang']);
        $op = [
            User::create(['name' => 'Sri Lestari', 'email' => 'operator@produksi.test', 'password' => 'operator123', 'peran' => 'operator']),
            User::create(['name' => 'Joko Prasetyo', 'email' => 'joko@produksi.test', 'password' => 'operator123', 'peran' => 'operator']),
            User::create(['name' => 'Wulan Sari', 'email' => 'wulan@produksi.test', 'password' => 'operator123', 'peran' => 'operator']),
        ];
        Auth::setUser($admin);
        Pabrik::simpan(Pabrik::DEFAULT);

        $sup = [
            'kain' => Supplier::create(['nama' => 'PT Tekstil Mulia Abadi', 'kontak' => 'Pak Hartono', 'telepon' => '0271-735112', 'alamat' => 'Jl. Raya Solo–Sukoharjo Km 4', 'lead_time' => 3]),
            'aksesoris' => Supplier::create(['nama' => 'Toko Benang & Aksesoris Jaya', 'kontak' => 'Bu Yanti', 'telepon' => '081329876501', 'alamat' => 'Pasar Klewer Blok C-12, Solo', 'lead_time' => 1]),
            'kemas' => Supplier::create(['nama' => 'CV Kemas Plastik Sejahtera', 'kontak' => 'Mas Adi', 'telepon' => '082134560077', 'alamat' => 'Kawasan Industri Palur', 'lead_time' => 2]),
            'sablon' => Supplier::create(['nama' => 'Sablon Supply Solo', 'kontak' => 'Koh Willy', 'telepon' => '081225550012', 'alamat' => 'Jl. Slamet Riyadi 210, Solo', 'lead_time' => 2]),
        ];

        $tahap = [];
        foreach ([['Potong', 'biru'], ['Jahit', 'oranye'], ['Sablon / Bordir', 'ungu'], ['Finishing & QC', 'hijau'], ['Packing', 'abu']] as $i => [$n, $w]) {
            $tahap[$n] = Tahapan::create(['nama' => $n, 'urutan' => $i + 1, 'warna' => $w]);
        }

        // kode, nama, kategori, satuan, harga, stok awal, stok min, supplier, lokasi
        $B = [];
        foreach ([
            ['BHN-001', 'Kain Cotton Combed 30s Hitam', 'Kain', 'kg', 135000, 45, 20, 'kain', 'Rak A1'],
            ['BHN-002', 'Kain Cotton Combed 30s Putih', 'Kain', 'kg', 128000, 30, 20, 'kain', 'Rak A2'],
            ['BHN-003', 'Kain Drill Navy', 'Kain', 'meter', 32000, 260, 120, 'kain', 'Rak B1'],
            ['BHN-004', 'Kain Drill Abu', 'Kain', 'meter', 32000, 140, 80, 'kain', 'Rak B2'],
            ['BHN-005', 'Kain Fleece Cotton Abu Misty', 'Kain', 'kg', 145000, 25, 15, 'kain', 'Rak A3'],
            ['BHN-006', 'Kain Kanvas Natural', 'Kain', 'meter', 28000, 120, 60, 'kain', 'Rak B3'],
            ['BHN-007', 'Benang Jahit Polyester', 'Aksesoris', 'cone', 18000, 40, 15, 'aksesoris', 'Lemari C'],
            ['BHN-008', 'Kancing Kemeja 4 Lubang', 'Aksesoris', 'pcs', 150, 2400, 800, 'aksesoris', 'Lemari C'],
            ['BHN-009', 'Resleting YKK', 'Aksesoris', 'pcs', 4500, 150, 60, 'aksesoris', 'Lemari C'],
            ['BHN-010', 'Rib Leher', 'Aksesoris', 'meter', 9000, 160, 60, 'aksesoris', 'Lemari C'],
            ['BHN-011', 'Label Woven Merek', 'Aksesoris', 'pcs', 450, 1600, 500, 'aksesoris', 'Lemari D'],
            ['BHN-012', 'Plastik Kemasan OPP', 'Kemasan', 'pcs', 300, 1800, 600, 'kemas', 'Lemari D'],
            ['BHN-013', 'Tinta Sablon Plastisol', 'Sablon', 'kg', 165000, 10, 4, 'sablon', 'Ruang sablon'],
            ['BHN-014', 'Benang Bordir Rayon', 'Aksesoris', 'cone', 22000, 18, 8, 'aksesoris', 'Lemari C'],
            ['BHN-015', 'Kain Keras (Interlining)', 'Kain', 'meter', 15000, 90, 40, 'kain', 'Rak B4'],
            ['BHN-016', 'Tali Kur Hoodie', 'Aksesoris', 'meter', 3500, 200, 80, 'aksesoris', 'Lemari C'],
        ] as [$k, $n, $kat, $sat, $h, $awal, $min, $s, $lok]) {
            $b = new Bahan(['kode' => $k, 'nama' => $n, 'kategori' => $kat, 'satuan' => $sat, 'stok_min' => $min, 'supplier_id' => $sup[$s]->id, 'lokasi' => $lok]);
            $b->harga = $h;
            $b->save();
            Stok::mutasi('bahan', $b->id, $awal, 'awal', ['harga' => $h, 'catatan' => 'Saldo awal pemakaian aplikasi']);
            $B[$k] = $b;
        }

        // kode, nama, harga jual, tenaga, overhead, BOM [kode bahan => [qty, susut%]], tahapan
        $P = [];
        $semua = ['Potong', 'Jahit', 'Sablon / Bordir', 'Finishing & QC', 'Packing'];
        $tanpaSablon = ['Potong', 'Jahit', 'Finishing & QC', 'Packing'];
        foreach ([
            ['PRD-001', 'Kaos Polos Hitam Cotton 30s', 65000, 9000, 2500, ['BHN-001' => [0.22, 8], 'BHN-007' => [0.02, 0], 'BHN-010' => [0.45, 5], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $tanpaSablon],
            ['PRD-002', 'Kaos Sablon Event Putih', 75000, 11000, 3000, ['BHN-002' => [0.22, 8], 'BHN-007' => [0.02, 0], 'BHN-010' => [0.45, 5], 'BHN-013' => [0.015, 10], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $semua],
            ['PRD-003', 'Kemeja PDH Drill Navy Bordir', 165000, 35000, 6000, ['BHN-003' => [1.6, 10], 'BHN-007' => [0.04, 0], 'BHN-008' => [9, 5], 'BHN-015' => [0.3, 5], 'BHN-014' => [0.01, 0], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $semua],
            ['PRD-004', 'Jaket Hoodie Zipper Fleece', 185000, 28000, 5000, ['BHN-005' => [0.55, 8], 'BHN-009' => [1, 0], 'BHN-016' => [1.4, 3], 'BHN-010' => [0.6, 5], 'BHN-007' => [0.05, 0], 'BHN-013' => [0.02, 10], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $semua],
            ['PRD-005', 'Totebag Kanvas Sablon', 45000, 7000, 2000, ['BHN-006' => [0.9, 6], 'BHN-007' => [0.02, 0], 'BHN-013' => [0.02, 10], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $semua],
            ['PRD-006', 'Celana Kerja Drill Abu', 150000, 30000, 5000, ['BHN-004' => [1.3, 8], 'BHN-007' => [0.04, 0], 'BHN-008' => [1, 5], 'BHN-009' => [1, 0], 'BHN-011' => [1, 0], 'BHN-012' => [1, 0]], $tanpaSablon],
        ] as [$k, $n, $hj, $tn, $oh, $bom, $th]) {
            $p = Produk::create(['kode' => $k, 'nama' => $n, 'harga_jual' => $hj, 'biaya_tenaga' => $tn, 'biaya_overhead' => $oh]);
            foreach ($bom as $kb => [$q, $s]) $p->bom()->create(['bahan_id' => $B[$kb]->id, 'qty' => $q, 'susut' => $s]);
            $p->tahapan()->attach(array_map(fn ($x) => $tahap[$x]->id, $th));
            $P[$k] = $p;
        }

        $pel = collect([
            ['SMA Negeri 1 Sukoharjo', '0271-593001', 'Jl. Pemuda No. 38, Sukoharjo'],
            ['PT Sinar Logistik Nusantara', '0271-712455', 'Kawasan Pergudangan Solo Baru'],
            ['Panitia Fun Run Solo 2026', '081226661010', 'Stadion Manahan, Solo'],
            ['Rimba Supply (distro kaos)', '085641230987', 'Jl. Dr. Radjiman 145, Solo'],
            ['Komunitas Gowes Bareng Sukoharjo', '081393334455', 'Jl. Jenderal Sudirman 12, Sukoharjo'],
            ['Toko Oleh-oleh Batik Laweyan', '0271-714210', 'Kampung Batik Laweyan, Solo'],
            ['Kantor Desa Mojorejo', '0271-590122', 'Jl. Desa Mojorejo, Bendosari'],
        ])->map(fn ($x) => Pelanggan::create(['nama' => $x[0], 'telepon' => $x[1], 'alamat' => $x[2]]));

        // Pola pesanan: [pelanggan, produk, qty]
        $pola = [[0, 'PRD-003', 60], [1, 'PRD-006', 40], [2, 'PRD-002', 150], [3, 'PRD-001', 120], [4, 'PRD-004', 35], [5, 'PRD-005', 200],
                 [6, 'PRD-003', 25], [3, 'PRD-004', 50], [0, 'PRD-001', 90], [1, 'PRD-003', 45], [2, 'PRD-005', 120], [5, 'PRD-002', 80]];
        $poOtomatis = [];
        $nPesanan = 0;

        for ($d = 0; $d <= 75; $d++) {
            $hari = $mulai->copy()->addDays($d);
            if ($hari->isSunday()) continue;
            $jam = fn (int $h) => Carbon::setTestNow($hari->copy()->setTime($h, mt_rand(0, 59)));
            $akhir = $d === 75; // hari ini: jangan selesaikan semuanya agar ada pekerjaan berjalan

            // 1. Pembelian yang sudah tiba diterima gudang
            $jam(8);
            Auth::setUser($gudang);
            foreach (Pembelian::whereIn('status', ['dipesan', 'sebagian'])->get() as $po) {
                if ($po->tanggal->copy()->addDays($po->supplier->lead_time)->gt($hari) || $d >= 73) continue;
                $qty = $po->item->mapWithKeys(fn ($i) => [$i->id => $i->qty - $i->qty_diterima])->all();
                Produksi::terimaPembelian($po, $qty, $hari->toDateString());
            }

            // 2. Pesanan baru setiap 3–4 hari kerja
            Auth::setUser($admin);
            if (($d % 3 === 1 || $d >= 66) && $d < 74) {
                $jam(9);
                [$pi, $kp, $q] = $pola[$nPesanan % count($pola)];
                $nPesanan++;
                $ps = Pesanan::create(['nomor' => Pabrik::nomor('pesanan', 'SO'), 'pelanggan_id' => $pel[$pi]->id, 'tanggal' => $hari,
                    'tenggat' => $hari->copy()->addDays(mt_rand(12, 20)), 'catatan' => '']);
                $ps->item()->create(['produk_id' => $P[$kp]->id, 'qty' => $q, 'harga' => $P[$kp]->harga_jual]);
                if ($nPesanan % 5 === 0) $ps->item()->create(['produk_id' => $P['PRD-001']->id, 'qty' => 24, 'harga' => $P['PRD-001']->harga_jual]);
                Produksi::cekPesananSiap();
                $ps->refresh();
                foreach (Produksi::pemenuhan($ps) as $x) {
                    if ($x['kurang'] > 0) {
                        $wo = Produksi::buatWo($x['item']->produk, Produksi::saranQty($x['kurang']), $hari->toDateString(), $ps->tenggat->copy()->subDays(2)->toDateString(),
                            $nPesanan % 4 === 0 ? 'tinggi' : 'normal', $ps->id, '');
                        if ($d < 74) $wo->update(['status' => 'rilis']);
                    }
                }
            }
            // PPIC membuat WO susulan untuk pesanan yang masih kurang (mis. karena reject)
            if ($d < 74) {
                foreach (Pesanan::with('item.produk')->where('status', 'produksi')->get() as $ps) {
                    foreach (Produksi::pemenuhan($ps) as $x) {
                        if ($x['kurang'] > 0 && $x['proses'] == 0) {
                            $jam(9);
                            $wo = Produksi::buatWo($x['item']->produk, Produksi::saranQty($x['kurang']), $hari->toDateString(), max($hari->copy()->addDays(5), $ps->tenggat)->toDateString(), 'mendesak', $ps->id, 'Susulan: kekurangan karena reject');
                            $wo->update(['status' => 'rilis']);
                        }
                    }
                }
            }
            // Produksi stok (make-to-stock) kaos polos sesekali
            if ($d % 15 === 7) {
                $jam(10);
                $wo = Produksi::buatWo($P['PRD-001'], 100, $hari->toDateString(), $hari->copy()->addDays(7)->toDateString(), 'normal', null, 'Stok gudang untuk penjualan eceran');
                $wo->update(['status' => 'rilis']);
            }

            // 3. Gudang mengeluarkan bahan untuk WO yang dirilis; bila kurang, PPIC membuat pembelian
            Auth::setUser($gudang);
            foreach (Wo::where('status', 'rilis')->orderBy('id')->get() as $wo) {
                $jam(10);
                try {
                    Produksi::keluarkanBahan($wo);
                } catch (Gagal $e) {
                    if (isset($poOtomatis[$wo->id])) continue;
                    $poOtomatis[$wo->id] = true;
                    $perSup = [];
                    foreach ($wo->bahan as $b) {
                        $bh = $b->bahan->fresh();
                        if ($bh->stok < $b->qty_butuh) {
                            $perSup[$bh->supplier_id][] = [$bh, ceil(($b->qty_butuh - $bh->stok + $bh->stok_min * 1.5))];
                        }
                    }
                    foreach ($perSup as $sid => $items) {
                        $po = Pembelian::create(['nomor' => Pabrik::nomor('pembelian', 'PO'), 'supplier_id' => $sid, 'tanggal' => $hari, 'catatan' => 'Kebutuhan ' . $wo->nomor, 'dibuat_oleh' => $admin->id]);
                        foreach ($items as [$bh, $q]) $po->item()->create(['bahan_id' => $bh->id, 'qty' => $q, 'harga' => round($bh->harga * (1 + mt_rand(-3, 5) / 100), -2)]);
                    }
                }
            }

            // 4. Lantai produksi: setiap tahap yang berjalan punya peluang selesai hari ini
            foreach (WoTahapan::where('status', 'proses')->orderBy('id')->get() as $t) {
                if (mt_rand(1, 100) > ($d >= 68 ? 40 : 80)) continue;
                if ($t->mulai_at && $t->mulai_at->isSameDay($hari) && mt_rand(1, 100) > 45) continue;
                Auth::setUser($op[mt_rand(0, 2)]);
                $jam(mt_rand(11, 16));
                $rej = mt_rand(1, 100) <= 35 ? min($t->qty_masuk, mt_rand(1, max(1, (int) ceil($t->qty_masuk * 0.03)))) : 0;
                Produksi::selesaiTahap($t, $t->qty_masuk - $rej, $rej, $rej ? 'Jahitan miring / sablon luntur, dipisahkan' : '');
            }

            // 5. Pesanan yang siap dikirim keesokan harinya
            Auth::setUser($gudang);
            foreach (Pesanan::where('status', 'siap')->where('updated_at', '<', $hari->copy()->setTime(0, 0))->get() as $ps) {
                if ($akhir) break;
                $jam(15);
                try {
                    Produksi::kirimPesanan($ps);
                } catch (Gagal) {
                }
            }
        }

        // Satu WO masih draft sebagai contoh perencanaan
        Auth::setUser($admin);
        Carbon::setTestNow(now()->setTime(9, 15));
        Produksi::buatWo($P['PRD-005'], 150, today()->addDay()->toDateString(), today()->addDays(9)->toDateString(), 'normal', null, 'Stok totebag untuk pameran UMKM');
        Carbon::setTestNow();
    }
}
