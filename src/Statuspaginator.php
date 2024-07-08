<?php

namespace brikdigital\statuspaginator;

use Craft;
use brikdigital\statuspaginator\models\Settings;
use craft\base\Model;
use craft\base\Plugin;
use craft\enums\CmsEdition;
use craft\log\MonologTarget;
use craft\web\twig\variables\Rebrand;
use Monolog\Formatter\LineFormatter;
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

        // Register a custom log target, keeping the format as simple as possible.
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'craft-statuspaginator',
            'categories' => ['craft-statuspaginator'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "%datetime% [%channel%.%level_name%] [%extra.yii_category%] %message% %context% %extra%\n",
                dateFormat: 'Y-m-d H:i:s',
                allowInlineLineBreaks: true,
                ignoreEmptyContextAndExtra: true,
            )
        ]);

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
        if (Craft::$app->edition === CmsEdition::Solo) return ['icon' => false, 'logo' => false];

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
