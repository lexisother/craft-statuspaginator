<?php

namespace brikdigital\statuspaginator\migrations;

use craft\console\controllers\SetupController;
use craft\db\Migration;
use craft\helpers\App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

// TODO: Have the API endpoint be configurable, eventually send along all the data we need on the Laravel side
/**
 * """Migration""" that tips off the Statuspaginator instance that this Craft install should be tracked.
 */
class Install extends Migration {
    /**
     * Submit this Craft install for tracking.
     *
     * @throws GuzzleException
     */
    public function safeUp(): bool {

        $client = new Client(['base_uri' => App::env('STATUSPAGINATOR_API_URL')]);
        $res = $client->post('/', [
            'json' => [
                'install' => true
            ],
        ]);

        return true;
    }

    /**
     * Unregister this Craft install.
     *
     * @throws GuzzleException
     */
    public function safeDown() {
        $client = new Client(['base_uri' => App::env('STATUSPAGINATOR_API_URL')]);
        $res = $client->post('/', [
            'json' => [
                'install' => false
            ],
        ]);

        return true;
    }
}
