<?php

namespace brikdigital\statuspaginator;

use Craft;
use brikdigital\statuspaginator\models\Settings;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\twig\variables\Rebrand;

/**
 * statuspaginator plugin
 *
 * @method static Statuspaginator getInstance()
 * @method Settings getSettings()
 */
class Statuspaginator extends Plugin
{
    public static Statuspaginator $plugin;
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->controller->renderTemplate('_statuspaginator/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings()
        ]);
    }

    public function getRebrandAssets(): array {
        $rebrand = new Rebrand();
        return [
            'icon' => $rebrand->getIcon()->getUrl(),
            'logo' => $rebrand->getLogo()->getUrl(),
        ];
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
