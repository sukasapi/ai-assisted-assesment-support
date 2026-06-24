<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportParticipantsCsvRequest;
use App\Models\MatrixVersion;
use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParticipantImportController extends Controller
{
    public function create(): View
    {
        return view('participants.import');
    }

    /**
     * Unduh template CSV (UTF-8 + BOM) dengan pemisah koma atau titik koma.
     */
    public function template(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'delimiter' => ['required', 'string', Rule::in([',', ';'])],
        ]);

        $sep = $validated['delimiter'];
        $filename = $sep === ';' ? 'template-impor-peserta-semicolon.csv' : 'template-impor-peserta-comma.csv';

        $columns = ['kode_peserta', 'nama_lengkap', 'alamat_surel', 'jabatan', 'pendidikan', 'tanggal_lahir', 'kode_versi_matriks'];
        $contoh = ['CONTOH-001', 'Contoh Nama Peserta', 'email@contoh.test', 'Analis', 'S1', '1990-05-01', 'KAMUS-17-DEFAULT'];

        return response()->streamDownload(function () use ($sep, $columns, $contoh): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns, $sep);
            fputcsv($out, $contoh, $sep);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(ImportParticipantsCsvRequest $request): RedirectResponse
    {
        $separator = $request->validated('delimiter');
        $path = $request->file('berkas_csv')->getRealPath();
        if ($path === false) {
            return back()->withErrors(['berkas_csv' => 'Berkas tidak dapat dibaca.']);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['berkas_csv' => 'Berkas tidak dapat dibuka.']);
        }

        $header = fgetcsv($handle, 0, $separator);
        if ($header === false) {
            fclose($handle);

            return back()->withErrors(['berkas_csv' => 'CSV kosong.']);
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($header[0] ?? '')) ?? '';
        $header = array_map(fn (mixed $h): string => strtolower(trim((string) $h)), $header);
        $indeks = array_flip($header);
        $wajib = ['kode_peserta', 'nama_lengkap'];
        foreach ($wajib as $kolom) {
            if (! isset($indeks[$kolom])) {
                fclose($handle);

                return back()->withErrors([
                    'berkas_csv' => "Kolom wajib hilang: {$kolom}. Pastikan baris pertama header dan pemisah (delimiter) sesuai pilihan Anda. Kolom: kode_peserta{$separator}nama_lengkap{$separator}alamat_surel{$separator}…",
                ]);
            }
        }

        // Batas baris (cegah transaksi sangat panjang / timeout) & commit per-chunk
        // agar berkas besar tidak mengunci tabel dalam satu transaksi raksasa.
        $maksBaris = (int) config('integrasi.impor_maks_baris', 10000);
        $ukuranChunk = 500;

        $jumlah = 0;
        $terlampaui = false;
        $buffer = [];
        $cacheVersi = [];

        $flush = function () use (&$buffer): void {
            if ($buffer === []) {
                return;
            }
            DB::transaction(function () use (&$buffer): void {
                foreach ($buffer as $atribut) {
                    Participant::query()->updateOrCreate(
                        ['kode_peserta' => $atribut['kode_peserta']],
                        $atribut['nilai'],
                    );
                }
            });
            $buffer = [];
        };

        while (($data = fgetcsv($handle, 0, $separator)) !== false) {
            if (count(array_filter($data, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $ambil = function (string $k) use ($indeks, $data): ?string {
                if (! isset($indeks[$k])) {
                    return null;
                }
                $i = $indeks[$k];
                $v = $data[$i] ?? null;

                return $v !== null && $v !== '' ? trim((string) $v) : null;
            };

            $kode = $ambil('kode_peserta');
            $nama = $ambil('nama_lengkap');
            if ($kode === null || $nama === null) {
                continue;
            }

            if ($jumlah >= $maksBaris) {
                $terlampaui = true;
                break;
            }

            $idVersi = null;
            $kodeVersi = $ambil('kode_versi_matriks');
            if ($kodeVersi !== null) {
                if (! array_key_exists($kodeVersi, $cacheVersi)) {
                    $cacheVersi[$kodeVersi] = MatrixVersion::query()->where('kode_versi', $kodeVersi)->value('id');
                }
                $idVersi = $cacheVersi[$kodeVersi];
            }

            $tanggalLahir = null;
            $tl = $ambil('tanggal_lahir');
            if ($tl !== null) {
                try {
                    $tanggalLahir = Carbon::parse($tl)->format('Y-m-d');
                } catch (\Throwable) {
                    $tanggalLahir = null;
                }
            }

            $buffer[] = [
                'kode_peserta' => $kode,
                'nilai' => [
                    'nama_lengkap' => $nama,
                    'alamat_surel' => $ambil('alamat_surel'),
                    'jabatan' => $ambil('jabatan'),
                    'pendidikan' => $ambil('pendidikan'),
                    'tanggal_lahir' => $tanggalLahir,
                    'id_versi_matriks' => $idVersi,
                    'aktif' => true,
                ],
            ];
            $jumlah++;

            if (count($buffer) >= $ukuranChunk) {
                $flush();
            }
        }

        $flush();
        fclose($handle);

        $pesan = "Berhasil memproses {$jumlah} baris peserta.";
        if ($terlampaui) {
            $pesan .= " Batas {$maksBaris} baris per unggahan tercapai — sisanya tidak diproses. Pecah berkas menjadi beberapa bagian.";

            return redirect()
                ->route('peserta.impor-csv')
                ->with('status', $pesan)
                ->withErrors(['berkas_csv' => "Hanya {$maksBaris} baris pertama yang diproses. Pecah berkas dan unggah ulang sisanya."]);
        }

        return redirect()
            ->route('peserta.impor-csv')
            ->with('status', $pesan);
    }
}
