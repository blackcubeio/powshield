<?php

namespace blackcube\powshield\actions;

use blackcube\powshield\components\Powshield;
use yii\web\MethodNotAllowedHttpException;
use Yii;

class GenerateChallengeAction extends \yii\base\Action
{
    public function run()
    {
        if (Yii::$app->request->isGet === false) {
            throw new MethodNotAllowedHttpException();
        }
        $powshield = \Yii::createObject(Powshield::class);
        return $powshield->createChallenge(true);
    }
}