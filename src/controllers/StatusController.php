<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace brikdigital\statuspaginator\controllers;

use brikdigital\statuspaginator\Statuspaginator;
use Craft;
use craft\base\PluginInterface;
use craft\controllers\AppController;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Illuminate\Support\Arr;
use ReflectionClass;
use ReflectionException;
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
     * @throws ReflectionException
     */
    public function actionIndex(): Response
    {
        $this->requirePostRequest();

        $theirToken = $this->request->getBodyParam('token');
        $ourToken = App::parseEnv(Statuspaginator::$plugin->getSettings()->token);
        if ($theirToken !== $ourToken) return $this->asJson(['error' => 'Tokens do not match']);

        $plugins = Arr::map(
            Craft::$app->getPlugins()->getAllPlugins(),
            fn (PluginInterface $plugin) =>
            (object) [
                'name' => $plugin->name,
                'description' => $plugin->description,
                'version' => $plugin->version,
                'developer' => (object) [
                    'name' => $plugin->developer,
                    'developerUrl' => $plugin->developerUrl
                ]
            ]
        );

        return $this->asJson([
            'meta' => [
                'cpurl' => UrlHelper::cpUrl(),
                'rebrand' => Statuspaginator::$plugin->getRebrandAssets(),
            ],
            'php' => App::phpVersion(),
            'craft' => [
                'edition' => Craft::$app->edition->name,
                'version' => Craft::$app->getVersion(),
                'updates' => $this->getDetailedUpdates()
            ],
            'plugins' => $plugins
        ]);
    }

    /**
     * Gets the detailed listing of updates.
     *
     * @return array
     * @throws ReflectionException
     * @see https://github.com/craftcms/cms/blob/c706b6410623319d510ae36be20aa4b67c2ab026/src/web/assets/cp/src/js/CP.js#L1070-L1187
     * @see https://github.com/craftcms/cms/blob/c706b6410623319d510ae36be20aa4b67c2ab026/src/controllers/AppController.php#L162-L225
     */
    protected function getDetailedUpdates(): array
    {
        // First, let's get the update data in a regular manner.
        $updatesService = Craft::$app->getUpdates();
        $updates = $updatesService->getUpdates();

        /**
         * If we want, we can cache the results. For now, that's no concern. If
         * we end up enabling caching, make sure to append `->toArray()` to the
         * `getUpdates` call above!
         */
        // $updates = $updatesService->cacheUpdates($updates);

        /**
         * I found out that there is a function on any Yii application called
         * `runAction`. In theory, this allows you to run controller actions
         * from any point in your application.
         * The function takes two arguments; the action name (in our case this
         * would've been `app/check-for-updates` or `app/cache-updates`) and
         * the parameters to pass to the action.
         *
         * The problem with this approach is that if the controller action
         * decides "we are sending back JSON data, so the request must have
         * `Accept: application/json` in its headers" (using
         * `$this->requireAcceptsJson()`), this approach is immediately off the
         * table because we cannot mask a `runAction` call as a web request
         * that accepts JSON data as its return value.
         *
         * On top of that, the actual _response_ we're looking for is
         * returned by a _private function_ on the controller.
         *
         * So, we are left with two options:
         *   1. Make an actual HTTP request to the action to retrieve our data
         *   2. Construct a fake instance of the controller and call the private function using reflection
         *
         * To minimize on request duration, I've opted for fetching an instance of the private method using reflection and calling it myself.
         */
        // Construct a dummy instance of the AppController with the necessary
        // arguments. I retrieved these by writing a constructor directly in
        // this class and logging out the three arguments typically passed into
        // Yii controllers.
        $obj = new AppController('app', Craft::$app, []);

        // Now, make a reflectable copy of the controller class and make our
        // private method invokable.
        $reflector = new ReflectionClass($obj);
        $method = $reflector->getMethod('_updatesResponse');
        $method->setAccessible(true); // no-op since 8.1 but keeping for posterity

        // Finally, get our detailed update data and return it.
        return $method->invoke($obj, $updates, true)->data;
    }
}
