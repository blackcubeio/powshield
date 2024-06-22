<?php

namespace blackcube\powshield\actions;

use blackcube\powshield\components\Powshield;
use yii\web\MethodNotAllowedHttpException;
use Yii;

class VerifySolutionAction extends \yii\base\Action
{
    public function run()
    {
        if (Yii::$app->request->isPost === false) {
            throw new MethodNotAllowedHttpException();
        }
        $powshield = Yii::createObject(Powshield::class);
        $base64Payload = Yii::$app->request->post('payload');
        return $powshield->verifyChallenge($base64Payload);
    }
}