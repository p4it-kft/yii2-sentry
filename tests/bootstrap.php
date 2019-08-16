<?php

error_reporting(-1);

define('YII_ENABLE_ERROR_HANDLER', false);
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

Yii::setAlias('@tests', __DIR__);

new \yii\console\Application([
    'id'         => 'unit',
    'basePath'   => __DIR__,
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'bootstrap'  => ['log'],
    'components' => [
        'db'  => [
            'class' => 'yii\db\Connection',
            'dsn'   => 'sqlite::memory:',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class'      => \p4it\sentry\log\SentryTarget::class,
                    'on beforeCapture' => static function(\yii\base\Event $event) {
                        /** @var \p4it\sentry\log\SentryTarget $sender */
                        $sender = $event->sender;
                        $sender->getScope()->setExtra('environment', 'test');
                        $sender->getSentryComponent()->getClient()->getOptions()->setRelease('1.1.1');
                    },
                    'sentryComponent' => [
                        'class' => \p4it\sentry\SentryComponent::class,
                        'transportMode' => \p4it\sentry\SentryComponent::NULL_TRANSPORT,
                    ]
                ],
                [
                    'class'      => \yii\log\DbTarget::class,
                ],
            ]
        ],
    ]
]);