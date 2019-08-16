# yii2-sentry
Sentry logger for Yii2
## Installation

```bash
composer require p4it/yii2-sentry
```

Add target class in the application config:

```php
return [ 
    'components' => [
	    'log' => [
		    'traceLevel' => YII_DEBUG ? 3 : 0,
		    'targets' => [
                [
                    'class'      => \p4it\sentry\log\SentryTarget::class,
                    'on beforeCapture' => static function(\yii\base\Event $event) {
                        /** @var \p4it\sentry\log\SentryTarget $sender */
                        $sender = $event->sender;
                        $sender->getScope()->setExtra('environment', 'test');
                    },
                    'client' => [
                        'class' => \p4it\sentry\SentryComponent::class,
                        'dns' => ''
                    ]
                ],
		    ],
	    ],
    ],
];
```
