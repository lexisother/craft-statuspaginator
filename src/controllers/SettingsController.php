<?php

namespace brikdigital\statuspaginator\controllers;

use brikdigital\statuspaginator\Statuspaginator;
use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\web\Controller;
use GuzzleHttp\Client;
use Psr\Log\LogLevel;
use yii\web\BadRequestHttpException;
use yii\web\Response;

// See: <https://github.com/verbb/wishlist/blob/craft-4/src/controllers/SettingsController.php>

class SettingsController extends Controller {
    /**
     * @throws MissingComponentException
     * @throws BadRequestHttpException
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $settings = Statuspaginator::$plugin->getSettings();
        $settings->setAttributes($request->getParam('settings'), false);

        // Any validation errors? Bail out.
        if (!$settings->validate()) {
            Craft::$app->getSession()->setError("Couldn't save settings.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        // Somehow failed to save the settings? Bail out.
        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Statuspaginator::$plugin, $settings->toArray());
        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError("Couldn't save settings.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        // Didn't register for some reason? Bail out.
        $statuspaginatorPassed = $this->register();
        if (!$statuspaginatorPassed) {
            Craft::$app->getSession()->setError("Failed to register at Statuspaginator.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        // *Now* we're all done.
        Craft::$app->getSession()->setNotice('Settings saved.');
        return $this->redirectToPostedUrl();
    }

    public function actionUnregister(): ?Response {
        $statuspaginatorPassed = $this->unregister();
        if (!$statuspaginatorPassed) {
            Craft::$app->getSession()->setError("Failed to unregister.");
            return null;
        }

        Craft::$app->getSession()->setNotice('Successfully unregistered.');
        return $this->redirectToPostedUrl();
    }

    private function register(): bool {
        $client = new Client([
            'http_errors' => false,
            'base_uri' => App::env('STATUSPAGINATOR_API_URL')
        ]);
        $res = $client->post('register', [
            'json' => [
                'name' => Craft::$app->getSystemName(),
                'baseUrl' => App::env('PRIMARY_SITE_URL'),
                'timezone' => Craft::$app->getTimeZone(),
                'token' => Statuspaginator::$plugin->getSettings()->token,
            ]
        ]);

        if ($res->getStatusCode() !== 200) {
            Craft::getLogger()->log("Failed to register.\n" . var_export($res, true), LogLevel::ERROR, 'craft-statuspaginator');
            return false;
        }

        return true;
    }

    private function unregister(): bool {
        $client = new Client([
            'http_errors' => false,
            'base_uri' => App::env('STATUSPAGINATOR_API_URL')
        ]);
        $res = $client->post('unregister', [
            'json' => [
                'token' => Statuspaginator::$plugin->getSettings()->token,
                'baseUrl' => App::env('PRIMARY_SITE_URL')
            ]
        ]);

        if ($res->getStatusCode() !== 200) {
            Craft::getLogger()->log("Failed to unregister.\n" . var_export($res, true), LogLevel::ERROR, 'craft-statuspaginator');
            return false;
        }

        return true;
    }
}
