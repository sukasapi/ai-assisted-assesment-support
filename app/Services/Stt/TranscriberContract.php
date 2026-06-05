<?php

namespace App\Services\Stt;

interface TranscriberContract
{
    /**
     * @return array{text: string, durasi_detik: ?float, segments: list<array{start: float, end: float, text: string}>}
     */
    public function transcribe(string $pathFileAbsolut, string $formatAudio): array;
}
