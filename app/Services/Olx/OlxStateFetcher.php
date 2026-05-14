<?php

namespace App\Services\Olx;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OlxStateFetcher implements PriceFetcherInterface
{
    public function getPrice(string $url): ?int
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();

            $needle = '__PRERENDERED_STATE__';
            $pos = strpos($html, $needle);

            if ($pos === false) return null;

            $startPos = strpos($html, '"', $pos + strlen($needle));
            if ($startPos === false) return null;
            $startPos++;

            $endPos = strpos($html, '";', $startPos);
            if ($endPos === false) return null;

            $rawContent = substr($html, $startPos, $endPos - $startPos);

            $jsonString = json_decode('"' . $rawContent . '"');
            $state = json_decode($jsonString, true, 512, JSON_INVALID_UTF8_IGNORE);

            if (!$state) return null;

            $price = $state['ad']['ad']['price']['regularPrice']['value']
                ?? $state['ad']['ad']['price']['value']
                ?? null;

            return $price ? (int)($price * 100) : null;
        } catch (\Throwable $e) {
            Log::error("OLX Parser Error: " . $e->getMessage());
            return null;
        }
    }
}
