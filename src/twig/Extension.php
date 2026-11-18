<?php

namespace designworkshop\searchurlencoder\twig;

use designworkshop\searchurlencoder\SearchUrlEncoder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            // {{ searchUrl('red shoes') }}
            // {{ searchUrl('red shoes', { section: 'products' }, profile='products') }}
            new TwigFunction('searchUrl', fn(
                string $q,
                array $params = [],
                ?int $siteId = null,
                string $profile = 'default'
            ) => SearchUrlEncoder::getInstance()->encoder->url($q, $params, $siteId, $profile)),
        ];
    }

    public function getFilters(): array
    {
        return [
            // {{ 'red shoes'|encodeSearch }}  → token only
            new TwigFilter('encodeSearch', fn(string $q, array $params = [], string $profile = 'default') =>
                SearchUrlEncoder::getInstance()->encoder->encode($q, $params, $profile)),
            // {{ token|decodeSearch }}
            new TwigFilter('decodeSearch', fn(string $token, string $profile = 'default') =>
                SearchUrlEncoder::getInstance()->encoder->decode($token, $profile)),
        ];
    }
}
