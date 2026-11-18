<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Search URL Encoder icon"></p>

<h1 align="center">Search URL Encoder</h1>

Search URL Encoder turns search form submissions into clean, shareable URLs for Craft CMS 5.

`?q=red+shoes&section=products` becomes `/search/eyJxIjoicmVkIHNob2VzIiwic2VjdGlvbiI6InByb2R1Y3RzIn0.<signature>`

## Requirements

- Craft CMS 5.0.0 or later
- PHP 8.2 or later

## Installation

You can install this plugin from the Plugin Store or with Composer.

### From the Plugin Store

Go to the Plugin Store in your project’s control panel, search for “Search URL Encoder”, then click **Install**.

### With Composer

```bash
# go to the project directory
cd /path/to/my-project

# tell Composer to load the plugin
composer require designworkshop/craft-search-url-encoder

# tell Craft to install the plugin
php craft plugin/install search-url-encoder
```

## Setup

1. In **Settings → Plugins → Search URL Encoder**, set the **Search path** (e.g. `search`) and the **Results template** (e.g. `search/results`).
2. Create the results template. A starting point is in [`src/templates-example/results.twig`](src/templates-example/results.twig).
3. Point your search form at the plugin’s submit action:

```twig
<form method="post">
  {{ csrfInput() }}
  {{ actionInput('search-url-encoder/search/submit') }}
  <input type="search" name="q">
  {# optional extra params, e.g. a section filter #}
  {{ hiddenInput('params[section]', 'products') }}
  <button>Search</button>
</form>
```

The form redirects to `/<search path>/<token>`, and the plugin renders your results template with these variables:

| Variable       | Contents                                           |
| -------------- | -------------------------------------------------- |
| `query`        | The search text                                    |
| `searchParams` | Any extra params sent with the form (scalars only) |
| `token`        | The encoded token from the URL                     |
| `profile`      | The profile handle (`default` unless configured)   |

## Twig

```twig
{{ searchUrl('red shoes', { section: 'products' }) }}   {# full URL #}
{{ 'red shoes'|encodeSearch }}                          {# token only #}
{{ token|decodeSearch }}                                {# array, or null if invalid #}
```

## Signed tokens

With **Sign tokens** on (the default), tokens look like `<payload>.<signature>` and are verified with HMAC-SHA256 using your Craft security key. Edited or forged URLs return a 404.

- The payload is signed, not encrypted. Anyone can read the query from the URL.
- Signing stops people from tampering with an existing link. It does **not** stop anyone from creating a new link with their own values, because anyone can submit the search form. Treat `query` and `searchParams` as visitor input in your templates, and allowlist the values you accept (the example template does this for `section`).
- Changing the security key, or turning signing on or off, invalidates existing links.

## Multiple search pages (profiles)

To have more than one search page, each with its own URL path and results template, copy [`config-example/search-url-encoder.php`](config-example/search-url-encoder.php) to `config/search-url-encoder.php` and define your profiles there. The values in the plugin settings act as the `default` profile.

```php
return [
    'profiles' => [
        'products' => ['path' => 'shop/search', 'template' => 'shop/results'],
    ],
];
```

To choose a profile in a form, add a hidden `profile` field. Leave it out to use the default profile.

```twig
{{ hiddenInput('profile', 'products') }}
```

In Twig:

```twig
{{ searchUrl('red shoes', { section: 'products' }, profile='products') }}
```

Tokens are signed per profile, so a token from one search page is rejected on another. Give each profile a unique path.
