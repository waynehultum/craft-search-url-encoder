<?php

namespace designworkshop\searchurlencoder\services;

use designworkshop\searchurlencoder\SearchUrlEncoder;
use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use yii\base\Component;
use yii\base\InvalidArgumentException;

class Encoder extends Component
{
    /**
     * Encodes a query (plus optional extra scalar params) into a URL-safe token.
     * When signing is enabled the token is "<payload>.<hmac>", and the HMAC is
     * bound to the profile so a token can't be reused on another search page.
     */
    public function encode(string $query, array $params = [], string $profile = 'default'): string
    {
        $payload = ['q' => trim($query)];

        foreach ($params as $key => $value) {
            if ($key !== 'q' && is_string($key) && (is_scalar($value) || $value === null)) {
                $payload[$key] = $value;
            }
        }

        $body = $this->b64urlEncode(Json::encode($payload));

        if ($this->signingEnabled()) {
            return $body . '.' . $this->sign($body, $profile);
        }

        return $body;
    }

    /**
     * Decodes a token for a profile. Returns null if it is malformed or the
     * signature is invalid.
     */
    public function decode(string $token, string $profile = 'default'): ?array
    {
        $max = SearchUrlEncoder::getInstance()->getSettings()->maxTokenLength;

        if ($token === '' || strlen($token) > $max) {
            return null;
        }

        $parts = explode('.', $token);

        if ($this->signingEnabled()) {
            if (count($parts) !== 2) {
                return null;
            }
            [$body, $signature] = $parts;
            if (!hash_equals($this->sign($body, $profile), $signature)) {
                return null;
            }
        } else {
            $body = $parts[0];
        }

        $json = $this->b64urlDecode($body);
        if ($json === null) {
            return null;
        }

        try {
            $data = Json::decode($json);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($data) || !isset($data['q']) || !is_string($data['q'])) {
            return null;
        }

        // Keep only scalar values
        return array_filter(
            $data,
            fn($v, $k) => is_string($k) && (is_scalar($v) || $v === null),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Builds the full encoded search URL for a profile.
     */
    public function url(string $query, array $params = [], ?int $siteId = null, string $profile = 'default'): string
    {
        $profiles = SearchUrlEncoder::getInstance()->getProfiles();

        if (!isset($profiles[$profile])) {
            throw new InvalidArgumentException("Unknown search profile “{$profile}”.");
        }

        return UrlHelper::siteUrl(
            $profiles[$profile]['path'] . '/' . $this->encode($query, $params, $profile),
            null,
            null,
            $siteId
        );
    }

    private function signingEnabled(): bool
    {
        return SearchUrlEncoder::getInstance()->getSettings()->signTokens;
    }

    /**
     * HMAC-SHA256 keyed with Craft's security key. The "default" profile signs
     * the payload alone, so links made before profiles existed stay valid.
     */
    private function sign(string $body, string $profile): string
    {
        $key = (string)Craft::$app->getConfig()->getGeneral()->securityKey;
        $scope = $profile === 'default' ? '' : $profile . ':';

        return $this->b64urlEncode(
            hash_hmac('sha256', 'search-url-encoder:v1:' . $scope . $body, $key, true)
        );
    }

    private function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
