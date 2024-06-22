# PowsShield for Yii2

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

```bash
composer require blackcube/powshield
```

## Configuration

Add the following code to your configuration file:

```php
return [
    // ...
    'modules' => [
        // ...
        'powshield' => [
            'class' => 'blackcube\powshield\Module',
            'key' => 'your-secret-key',
            'algorithm' => 'SHA-256', // SHA-256, SHA-384, SHA-512
            'minIterations' => 1000, // change iterations to make the process slower
            'maxIterations' => 100000,
            'saltLength' => 12, // change salt length to make the process slower
            'antiReplay' => true, // enable anti-replay mechanism, needs app to have cache component
            'antiReplayTimeout' => 300, // duration of the anti-replay mechanism
            'timeValidity' => 300, // duration of the challenge validity
        ],
    ],
    'bootstrap' => [
        // ...
        'powshield'
    ],
];

```

This sets up the module and:

1. activate api routes:

 * `/powshield/generate-challenge` to generate a challenge
 * `/powshield/verify-solution` to check a solution

2. activate the validator:

 * `powshield` to validate a solution in a model

## Usage

### Client side

You can use the following libraries to generate and check the solution:

 * @blackcube/aurelia2-powshield [Aurelia 2 powshield](https://www.npmjs.com/package/@blackcube/aurelia2-powshield)
 * @blackcube/vanilla-powshield [VanillaJS powshield](https://www.npmjs.com/package/@blackcube/vanilla-powshield)

Once solution is generated, you should send it to the server

### Server side

You can use the following code to validate a solution:

```php
class MyModel extends yii\base\Model
{
    public $captchaSolution;
    public $name;

    public function rules()
    {
        return [
            [['captchaSolution', 'name'], 'required'],
            ['captchaSolution', 'powshield'],
        ];
    }
}
```

If the solution is not valid, the model will have an error on `captchaSolution`.