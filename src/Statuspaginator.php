<?php

namespace brikdigital\statuspaginator;

use Craft;
use brikdigital\statuspaginator\models\Settings;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\twig\variables\Rebrand;
use Psr\Log\LogLevel;

/**
 * statuspaginator plugin
 *
 * @method static Statuspaginator getInstance()
 * @method Settings getSettings()
 */
class Statuspaginator extends Plugin
{
    public static Statuspaginator $plugin;
    public $schemaVersion = '1.0.0';
    public $hasCpSettings = true;

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

        $this->attachEventHandlers();
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    public function settingsHtml(): mixed
    {
        return Craft::$app->view->renderTemplate('statuspaginator/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings()
        ]);
    }

    public function getRebrandAssets(): array {
        if (Craft::$app->getEdition() === Craft::Solo) return ['icon' => false, 'logo' => false];

        $rebrand = new Rebrand();
        $icon = $rebrand->getIcon();
        $logo = $rebrand->getLogo();
        return [
            'icon' => $icon ? $icon->getUrl() : false,
            'logo' => $logo ? $logo->getUrl() : false,
        ];
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
