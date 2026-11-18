<?php

namespace designworkshop\searchurlencoder;

use designworkshop\searchurlencoder\models\Settings;
use designworkshop\searchurlencoder\services\Encoder;
use designworkshop\searchurlencoder\twig\Extension;
use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @property-read Encoder $encoder
 * @method Settings getSettings()
 */
class SearchUrlEncoder extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'encoder' => Encoder::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Event handlers are attached right away so they can't miss the event
        // if the URL manager is created before Craft finishes initializing.
        // One pretty route per profile: /<path>/<token>
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                foreach ($this->getProfiles() as $handle => $profile) {
                    $event->rules[$profile['path'] . '/<token:[A-Za-z0-9_.-]+>'] = [
                        'route' => 'search-url-encoder/search/render',
                        'params' => ['profile' => $handle],
                    ];
                }
            }
        );

        // Anything that loads Twig waits until Craft is fully initialized.
        Craft::$app->onInit(function () {
            Craft::$app->getView()->registerTwigExtension(new Extension());
        });
    }

    /**
     * All search profiles: the "default" one from the plugin settings,
     * plus any defined under `profiles` in config/search-url-encoder.php.
     *
     * @return array<string, array{path: string, template: string}>
     */
    public function getProfiles(): array
    {
        $settings = $this->getSettings();

        $profiles = [
            'default' => [
                'path' => trim($settings->searchPath, '/'),
                'template' => $settings->searchTemplate,
            ],
        ];

        foreach ($settings->profiles as $handle => $profile) {
            if (
                is_string($handle)
                && preg_match('/^[A-Za-z0-9_-]+$/', $handle)
                && !empty($profile['path'])
                && !empty($profile['template'])
            ) {
                $profiles[$handle] = [
                    'path' => trim((string)$profile['path'], '/'),
                    'template' => (string)$profile['template'],
                ];
            }
        }

        return $profiles;
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('search-url-encoder/settings', [
            'settings' => $this->getSettings(),
            'profiles' => $this->getProfiles(),
        ]);
    }
}
