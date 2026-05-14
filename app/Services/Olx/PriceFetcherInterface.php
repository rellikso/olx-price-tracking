<?php

namespace App\Services\Olx;

interface PriceFetcherInterface
{
    public function getPrice(string $url): ?int;
}
