<?php

namespace brikdigital\statuspaginator\models;

use Craft;
use craft\base\Model;

/**
 * statuspaginator settings
 */
class Settings extends Model
{
    public string $token = '';

    public function defineRules(): array
    {
        return [
            [['token'], 'required'],
            [['token'], 'string', 'min' => 180]
        ];
    }
}
