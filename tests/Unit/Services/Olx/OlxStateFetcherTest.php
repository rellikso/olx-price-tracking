<?php

namespace Tests\Unit\Services\Olx;

use App\Services\Olx\OlxStateFetcher;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OlxStateFetcherTest extends TestCase
{
    /**
     * Testing successful price extraction (in kopecks)
     */
    public function test_it_extracts_price_in_cents_from_ssr_state(): void
    {
        // We specially prepare a string as it appears in the OLX source (escaped quotes)
        $innerJson = '{\"ad\":{\"ad\":{\"price\":{\"value\":1250.50}}}}';
        $html = '<html><body><script>window.__PRERENDERED_STATE__ = "' . $innerJson . '";</script></body></html>';

        Http::fake([
            'olx.ua/*' => Http::response($html, 200)
        ]);

        $fetcher = new OlxStateFetcher();
        $price = $fetcher->getPrice('https://www.olx.ua/d/test.html');

        // 1250.50 * 100 = kopecks копеек
        $this->assertEquals(125050, $price);
    }

    /**
     * We test the case where the JSON structure is different (regularPrice)
     */
    public function test_it_extracts_regular_price_if_available(): void
    {
        $innerJson = '{\"ad\":{\"ad\":{\"price\":{\"regularPrice\":{\"value\":99.99}}}}}';
        $html = 'window.__PRERENDERED_STATE__ = "' . $innerJson . '";';

        Http::fake([
            'olx.ua/*' => Http::response($html, 200)
        ]);

        $fetcher = new OlxStateFetcher();
        $price = $fetcher->getPrice('https://www.olx.ua/d/test.html');

        $this->assertEquals(9999, $price);
    }

    /**
     * We test error handling (for example, broken HTML)
     */
    public function test_it_returns_null_on_invalid_html(): void
    {
        Http::fake([
            'olx.ua/*' => Http::response('<html><body>No state here</body></html>', 200)
        ]);

        $fetcher = new OlxStateFetcher();
        $this->assertNull($fetcher->getPrice('https://www.olx.ua/d/test.html'));
    }
}