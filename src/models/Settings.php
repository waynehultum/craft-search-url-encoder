<?php

namespace designworkshop\searchurlencoder\models;

use craft\base\Model;

class Settings extends Model
{
    /** URL segment the encoded token is appended to. */
    public string $searchPath = 'search';

    /** Site template rendered for an encoded search URL. */
    public string $searchTemplate = 'search/results';

    /**
     * Extra search profiles, set in config/search-url-encoder.php:
     * ['products' => ['path' => 'shop/search', 'template' => 'shop/results']]
     */
    public array $profiles = [];

    /** Sign tokens with an HMAC so they can't be tampered with or forged. */
    public bool $signTokens = true;

    /** Maximum accepted token length (characters). */
    public int $maxTokenLength = 1024;

    protected function defineRules(): array
    {
        return [
            [['searchPath', 'searchTemplate'], 'required'],
            [['searchPath', 'searchTemplate'], 'string'],
            ['signTokens', 'boolean'],
            ['profiles', 'safe'],
            ['maxTokenLength', 'integer', 'min' => 16, 'max' => 8192],
        ];
    }
}
