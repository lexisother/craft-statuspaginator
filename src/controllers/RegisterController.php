<?php

namespace brikdigital\statuspaginator\controllers;

use brikdigital\statuspaginator\Statuspaginator;
use Craft;
use craft\helpers\App;
use craft\web\Controller;
use yii\web\Response;

class RegisterController extends Controller
{
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;
    public $enableCsrfValidation = false;

    public function actionIndex(): ?Response
    {
        $this->requirePostRequest();

        $theirToken = $this->request->getBodyParam('token');
        $ourToken = App::parseEnv(Statuspaginator::$plugin->getSettings()->token);
        if ($theirToken !== $ourToken) return $this->asJson(['error' => 'Tokens do not match']);

        return $this->asJson([
            'name' => Craft::$app->getSystemName(),
            'timezone' => Craft::$app->getTimeZone(),
        ]);
    }
}
