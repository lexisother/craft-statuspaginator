<?php

namespace brikdigital\statuspaginator\controllers;

use Craft;
use craft\helpers\App;
use craft\utilities\SystemReport;
use craft\web\Controller;
use Illuminate\Support\Arr;
use yii\web\Response;

/**
 * Status controller
 */
class StatusController extends Controller
{
    /**
     * @var string the ID of the action that is used when the action ID is not specified
     * in the request. Defaults to 'index'.
     */
    public $defaultAction = 'index';

    /**
     * @var int|bool|int[]|string[] Whether this controller’s actions can be accessed anonymously.
     *
     * This can be set to any of the following:
     *
     * - `false` or `self::ALLOW_ANONYMOUS_NEVER` (default) – indicates that all controller actions should never be
     *   accessed anonymously
     * - `true` or `self::ALLOW_ANONYMOUS_LIVE` – indicates that all controller actions can be accessed anonymously when
     *    the system is live
     * - `self::ALLOW_ANONYMOUS_OFFLINE` – indicates that all controller actions can be accessed anonymously when the
     *    system is offline
     * - `self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE` – indicates that all controller actions can be
     *    accessed anonymously when the system is live or offline
     * - An array of action IDs (e.g. `['save-guest-entry', 'edit-guest-entry']`) – indicates that the listed action IDs
     *   can be accessed anonymously when the system is live
     * - An array of action ID/bitwise pairs (e.g. `['save-guest-entry' => self::ALLOW_ANONYMOUS_OFFLINE]` – indicates
     *   that the listed action IDs can be accessed anonymously per the bitwise int assigned to it.
     */
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * _statuspaginator/status action
     */
    public function actionIndex(): Response
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();
        $plugins = Arr::map($plugins, fn ($p) => $p->version);

        return $this->asJson([
            'php' => App::phpVersion(),
            'craft' => [
                'edition' => App::editionName(Craft::$app->getEdition()),
                'version' => Craft::$app->getVersion()
            ],
            'plugins' => $plugins
        ]);
    }
}
