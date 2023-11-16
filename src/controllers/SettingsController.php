<?php

namespace brikdigital\statuspaginator\controllers;

use brikdigital\statuspaginator\Statuspaginator;
use Craft;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\UrlHelper;
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

        Craft::getLogger()->log("Saving settings...", LogLevel::INFO, 'craft-statuspaginator');

        // Any validation errors? Bail out.
        if (!$settings->validate()) {
            Craft::getLogger()->log("Failed to validate.\n" . var_export($settings->getErrors(), true), LogLevel::ERROR, 'craft-statuspaginator');
            Craft::$app->getSession()->setError("Couldn't validate settings.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::getLogger()->log("Passed settings validation.", LogLevel::INFO, 'craft-statuspaginator');

        // Somehow failed to save the settings? Bail out.
        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Statuspaginator::$plugin, $settings->toArray());
        if (!$pluginSettingsSaved) {
            Craft::getLogger()->log("Failed to save. Somehow.", LogLevel::ERROR, 'craft-statuspaginator');
            Craft::$app->getSession()->setError("Couldn't save settings.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::getLogger()->log("Done!", LogLevel::INFO, 'craft-statuspaginator');

        // *Now* we're all done.
        Craft::$app->getSession()->setNotice('Settings saved.');
        return $this->redirectToPostedUrl();
    }

    public function actionRegister(): ?Response {
        $statuspaginatorPassed = $this->register();
        if (!$statuspaginatorPassed) {
            Craft::$app->getSession()->setError("Failed to register at Statuspaginator.");
            return null;
        }

        Craft::$app->getSession()->setNotice('Successfully registered.');
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
                'baseUrl' => UrlHelper::baseUrl(),
                'timezone' => Craft::$app->getTimeZone(),
                'token' => App::parseEnv(Statuspaginator::$plugin->getSettings()->token),
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
                'token' => App::parseEnv(Statuspaginator::$plugin->getSettings()->token),
                'baseUrl' => UrlHelper::baseUrl(),
            ]
        ]);

        if ($res->getStatusCode() !== 200) {
            Craft::getLogger()->log("Failed to unregister.\n" . var_export($res, true), LogLevel::ERROR, 'craft-statuspaginator');
            return false;
        }

        return true;
    }
}
