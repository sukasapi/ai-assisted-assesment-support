<?php

namespace Tests\Unit;

use App\Services\Stt\AudioTranscriptionChunker;
use App\Services\Stt\GroqSttTranscriber;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqSttTranscriberTest extends TestCase
{
    public function test_chunker_tidak_pecah_file_kecil(): void
    {
        $chunker = new AudioTranscriptionChunker;
        $path = tempnam(sys_get_temp_dir(), 'stt-audio-');
        $this->assertNotFalse($path);
        file_put_contents($path, str_repeat('a', 1024));

        try {
            $hasil = $chunker->pecahJikaPerlu($path);
            $this->assertSame([$path], $hasil);
        } finally {
            @unlink($path);
        }
    }

    public function test_groq_transcriber_memanggil_api_dan_mengembalikan_teks(): void
    {
        config([
            'stt.aktif' => true,
            'stt.groq.kunci_api' => 'gsk-test',
            'stt.groq.url_dasar' => 'https://api.groq.com/openai/v1',
            'stt.groq.model' => 'whisper-large-v3-turbo',
            'stt.bahasa' => 'id',
        ]);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'text' => 'Halo dari Groq.',
                'segments' => [
                    ['start' => 0.0, 'end' => 2.0, 'text' => 'Halo dari Groq.'],
                ],
            ], 200),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'stt-audio-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'fake-audio');

        try {
            $hasil = (new GroqSttTranscriber)->transcribe($path, 'wav');
            $this->assertSame('Halo dari Groq.', $hasil['text']);
        } finally {
            @unlink($path);
        }
    }
}
