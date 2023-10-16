<?php

namespace brikdigital\statuspaginator\controllers;

use brikdigital\statuspaginator\Statuspaginator;
use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
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

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Statuspaginator::$plugin, $settings->toArray());

        // Somehow failed to save the settings? Bail out.
        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError("Couldn't save settings.");

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        // *Now* we're all done.
        // TODO: Make the request to Statuspaginator from here
        Craft::$app->getSession()->setNotice('Settings saved.');
        return $this->redirectToPostedUrl();
    }
}
