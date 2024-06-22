<?php

namespace blackcube\powshield\components;

use blackcube\powshield\Module;
use Exception;
use Yii;
use yii\helpers\Json;

class Powshield extends \yii\base\Component
{
    /**
     * @var array algorithm available
     */
    public $algorithmMapping = [
        'SHA-256' => 'sha256',
        'SHA-384' => 'sha384',
        'SHA-512' => 'sha512',
    ];
    /**
     * @var string default algorithm
     */
    public $algorithm = 'SHA-256';
    /**
     * @var string key used for signature
     */
    public $key;
    /**
     * @var int number min used for POW
     */
    public $minIterations = 0;
    /**
     * @var int number max used for POW
     */
    public $maxIterations = 50000;
    /**
     * @var int length of the salt
     */
    public $saltLength = 12;
    /**
     * @var int time validity of the challenge
     */
    public $timeValidity = 60; // 1 minutes

    public $antiReplay = true;

    public $antiReplayTimeout = 3600; // 5 minutes
    /**
     * PowShield create challenge.
     *
     * @return array
     */
    public function createChallenge($asBase64 = false)
    {
        $salt = Yii::$app->security->generateRandomString($this->saltLength);
        $secretNumber = random_int($this->minIterations, $this->maxIterations);
        $algorithm = $this->algorithm;
        if(!isset($this->algorithmMapping[$algorithm])) {
            throw new Exception(Module::t('challenge', 'Algorithm not supported'));
        }
        $challenge = $this->generateChallenge($salt, $secretNumber, $algorithm);
        if($asBase64) {
            $challenge = base64_encode(Json::encode($challenge));
        }
        return $challenge;
    }

    /**
     * Altcha verify challenge.
     *
     * @param string|array $base64Payload base64 json encoded payload or array
     * @return bool
     */
    public function verifyChallenge($payload)
    {
        if (is_string($payload)) {
            try {
                $payload = Json::decode(base64_decode($payload), true);
            } catch (\Exception $e) {
                Yii::error($e->getMessage(), 'challenge');
                $payload = null;
            }
        }
        if($payload === null) {
            return false;
        }
        $algorithm = $payload['algorithm'] ?? 'SHA-256';
        $cypheredTimestamp = $payload['timestamp'] ?? null;
        $challenge = $payload['challenge'] ?? null;
        $number = $payload['number'] ?? null;
        $salt = $payload['salt'] ?? null;
        $signature = $payload['signature'] ?? null;

        $checkPayload = $this->generateChallenge($salt, $number, $algorithm, $cypheredTimestamp);
        $cypheredTimestamp = base64_decode($cypheredTimestamp);
        $timestamp = Yii::$app->security->decryptByKey($cypheredTimestamp, $this->key);

        if (time() > $timestamp + $this->timeValidity) {
            return false;
        }

        $algoOk = $algorithm === $checkPayload['algorithm'];
        $challengeOk = hash_equals($checkPayload['challenge'], $challenge);
        $signatureOk = hash_equals($checkPayload['signature'], $signature);
        return $algoOk && $challengeOk && $signatureOk;
    }

    /**
     * Generate challenge using known parameters
     *
     * @param string $salt
     * @param string $secretNumber
     * @param string $algorithm
     * @return array
     */
    private function generateChallenge($salt, $secretNumber, $algorithm, $cypheredTimestamp = null)
    {
        if ($cypheredTimestamp === null) {
            $timestamp = time();
            $cypheredTimestamp = Yii::$app->security->encryptByKey($timestamp, $this->key);
            $cypheredTimestamp = base64_encode($cypheredTimestamp);
        }

        $algo = $this->algorithmMapping[$algorithm];
        $challenge = hash($algo, $salt.$secretNumber.$cypheredTimestamp);
        $signature = hash_hmac($algo, $challenge, $this->key);
        return [
            'algorithm' => $algorithm, // 'SHA-256', 'SHA-384', 'SHA-512'
            'timestamp' => $cypheredTimestamp,
            'challenge' => $challenge,
            'salt' => $salt,
            'signature' => $signature,
            'start' => 0,
            'max' => $this->maxIterations,
        ];
    }

}