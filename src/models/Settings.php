<?php

namespace brikdigital\statuspaginator\models;

use Craft;
use craft\base\Model;

/**
 * statuspaginator settings
 */
class Settings extends Model
{
    public const ENV_REGEX = '/^\$(\w+)$/';
    public string $token = '';

    public function defineRules(): array
    {
        return [
            [['token'], 'required'],
            [['token'], 'validateToken']
        ];
    }

    public function validateToken($attribute, $params, $validator)
    {
        if (!preg_match(self::ENV_REGEX, $this->$attribute) || strlen($this->$attribute) === 180) {
           $this->addError($attribute, 'The token must be an environment variable or a 180 character string.');
        }
    }
}
