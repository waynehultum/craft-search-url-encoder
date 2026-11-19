# Release Notes for Search URL Encoder

## 1.0.0 - 2026-11-19

### Added
- Initial release.
- Encodes search queries and extra parameters into clean, shareable URLs (`/search/<token>`).
- `searchUrl()` Twig function and `encodeSearch` / `decodeSearch` Twig filters.
- `search-url-encoder/search/submit` controller action for search forms.
- Optional HMAC signing of tokens using Craft's security key.
- Multiple search profiles, each with its own URL path and results template, via `config/search-url-encoder.php`.
