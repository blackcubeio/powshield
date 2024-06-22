<?php
/**
 * Module.php
 *
 * PHP version 8.2+
 *
 * @author Philippe Gaultier <pgaultier@gmail.com>
 * @copyright 2010-2024 Blackcube
 * @license https://powshield.blackcube.io/en/license
 * @version XXX
 * @link https://powshield.blackcube.io
 * @package blackcube\admin
 */

namespace blackcube\powshield;


use blackcube\powshield\components\Powshield;
use blackcube\powshield\validators\PowshieldValidator;
use yii\base\BootstrapInterface;
use yii\base\Module as BaseModule;
use yii\i18n\GettextMessageSource;
use yii\web\Application as WebApplication;
use yii\web\ErrorHandler;
use yii\web\UrlRule;
use yii\web\GroupUrlRule;
use yii\console\Application as ConsoleApplication;
use Yii;

/**
 * Class module
 *
 * @author Philippe Gaultier <pgaultier@gmail.com>
 * @copyright 2010-2024 Blackcube
 * @license https://powshield.blackcube.io/en/license
 * @version XXX
 * @link https://powshield.blackcube.io
 * @package blackcube\admin
 *
 */
class Module extends BaseModule implements BootstrapInterface
{

    /**
     * {@inheritDoc}
     */
    public $defaultRoute = 'api/generate-challenge';

    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'blackcube\powshield\controllers';

    /**
     * @var string version number
     */
    public $version = 'v1.0.0';

    public $antiReplay = true;

    public $antiReplayTimeout = 300;

    public $algorithm = 'SHA-256';

    public $key;

    public $minIterations = 0;

    public $maxIterations = 50000;

    public $saltLength = 12;

    public $timeValidity = 900;

    /**
     * @var string[]
     */
    public $coreSingletons = [
    ];

    /**
     * @var string[]
     */
    public $coreElements = [
        'powshield' => PowshieldValidator::class,
        Powshield::class => Powshield::class,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->layout = 'main';
        parent::init();
        $this->registerErrorHandler();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        Yii::setAlias('@blackcube/powshield', __DIR__);
        $this->registerDi($app);
        $this->registerTranslations();
        if ($app instanceof ConsoleApplication) {
            $this->bootstrapConsole($app);
        } elseif ($app instanceof WebApplication) {
            $this->bootstrapWeb($app);
        }

    }

    /**
     * @param WebApplication|ConsoleApplication $app
     * @throws \yii\base\InvalidConfigException
     */
    public function registerDi($app)
    {
        foreach($this->coreSingletons as $class => $definition) {
            if (Yii::$container->hasSingleton($class) === false) {
                Yii::$container->setSingleton($class, $definition);
            }
        }
        foreach($this->coreElements as $class => $definition) {
            if (Yii::$container->has($class) === false) {
                if ($class === Powshield::class) {
                    $definition = $this->configurePowshield($definition);
                }
                Yii::$container->set($class, $definition);
            }
        }
    }

    protected function configurePowshield($definition)
    {
        if (is_string($definition) === true) {
            $definition = ['class' => $definition];
        }
        $definition['algorithm'] = $this->algorithm;
        $definition['key'] = $this->key;
        $definition['minIterations'] = $this->minIterations;
        $definition['maxIterations'] = $this->maxIterations;
        $definition['saltLength'] = $this->saltLength;
        $definition['timeValidity'] = $this->timeValidity;
        $definition['antiReplay'] = $this->antiReplay;
        $definition['antiReplayTimeout'] = $this->antiReplayTimeout;
        return $definition;
    }
    /**
     * Bootstrap console stuff
     *
     * @param ConsoleApplication $app
     * @since XXX
     */
    protected function bootstrapConsole(ConsoleApplication $app)
    {
    }

    /**
     * Bootstrap web stuff
     *
     * @param WebApplication $app
     * @since XXX
     */
    protected function bootstrapWeb(WebApplication $app)
    {
        $app->getUrlManager()->addRules([
            [
                'class' => GroupUrlRule::class,
                'prefix' => $this->id,
                'rules' => [
                    [
                        'class' => UrlRule::class,
                        'verb' => ['GET'],
                        'pattern' => 'generate-challenge',
                        'route' => 'api/generate-challenge'
                    ],
                    [
                        'class' => UrlRule::class,
                        'verb' => ['POST'],
                        'pattern' => 'verify-solution',
                        'route' => 'api/verify-solution'
                    ],
                ],
            ]
        ], true);

    }

    /**
     * Register translation stuff
     */
    public function registerTranslations()
    {
        Yii::$app->i18n->translations['blackcube/powshield/*'] = [
            'class' => GettextMessageSource::class,
            'sourceLanguage' => 'en',
            'useMoFile' => true,
            'basePath' => '@blackcube/powshield/i18n',
        ];
    }

    /**
     * Register errorHandler for all module URLs
     * @throws \yii\base\InvalidConfigException
     */
    public function registerErrorHandler()
    {
        if (Yii::$app instanceof WebApplication) {
            list($route,) = Yii::$app->urlManager->parseRequest(Yii::$app->request);
            if ($route !== null && preg_match('#'.$this->uniqueId.'/#', $route) > 0) {
                Yii::configure($this, [
                    'components' => [
                        'errorHandler' => [
                            'class' => ErrorHandler::class,
                            'errorAction' => $this->uniqueId.'/technical/error',
                        ]
                    ],
                ]);
                /** @var ErrorHandler $handler */
                $handler = $this->get('errorHandler');
                Yii::$app->set('errorHandler', $handler);
                $handler->register();
            }
        }
    }

    /**
     * Translates a message to the specified language.
     *
     * This is a shortcut method of [[\yii\i18n\I18N::translate()]].
     *
     * The translation will be conducted according to the message category and the target language will be used.
     *
     * You can add parameters to a translation message that will be substituted with the corresponding value after
     * translation. The format for this is to use curly brackets around the parameter name as you can see in the following example:
     *
     * ```php
     * $username = 'Alexander';
     * echo Module::t('app', 'Hello, {username}!', ['username' => $username]);
     * ```
     *
     * Further formatting of message parameters is supported using the [PHP intl extensions](https://secure.php.net/manual/en/intro.intl.php)
     * message formatter. See [[\yii\i18n\I18N::translate()]] for more details.
     *
     * @param string $category the message category.
     * @param string $message the message to be translated.
     * @param array $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('blackcube/powshield/' . $category, $message, $params, $language);
    }
}
