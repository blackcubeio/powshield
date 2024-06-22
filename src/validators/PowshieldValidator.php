<?php

namespace blackcube\powshield\validators;

use blackcube\powshield\components\Powshield;
use blackcube\powshield\Module;
use yii\validators\Validator;
use Yii;

class PowshieldValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $base64Payload = $model->{$attribute};
        $powshield = Yii::createObject(Powshield::class);
        if ($powshield->antiReplay) {
            $cache = Yii::$app->get('cache');
            if ($cache) {
                $value = $cache->get($base64Payload);
                if ($value !== false) {
                    $model->{$attribute} = null;
                    $model->addError($attribute, Module::t('validators', 'Powshield challenge already used'));
                } else {
                    $cache->set($base64Payload, true, $powshield->antiReplayTimeout);
                }
            }
        }
        $challengeStatus = $powshield->verifyChallenge($base64Payload);
        if ($challengeStatus === false) {
            $model->{$attribute} = null;
            $model->addError($attribute, Module::t('validators', 'Are you a robot ?'));
        }
    }
}