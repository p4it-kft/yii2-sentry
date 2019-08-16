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
				    'class' => 'p4it\sentry\SentryTarget',
				    'dsn' => '',
				    'levels' => ['error', 'warning'],
			    ],
		    ],
	    ],
    ],
];
```
