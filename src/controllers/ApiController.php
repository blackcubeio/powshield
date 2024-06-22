<?php

namespace blackcube\powshield\controllers;


use blackcube\powshield\actions\GenerateChallengeAction;
use blackcube\powshield\actions\VerifySolutionAction;

class ApiController extends \yii\rest\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        unset($behaviors['rateLimiter']);
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['generate-challenge'] = [
            'class' => GenerateChallengeAction::class,
        ];
        $actions['verify-solution'] = [
            'class' => VerifySolutionAction::class,
        ];
        return $actions;
    }
}