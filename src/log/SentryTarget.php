<?php
namespace p4it\sentry\log;

use p4it\sentry\SentryComponent;
use Sentry\Severity;
use Sentry\State\Scope;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

/**
 * SentryTarget records log messages in a Sentry.
 *
 * @see https://sentry.io
 */
class SentryTarget extends Target
{
    public const EVENT_BEFORE_CAPTURE = 'beforeCapture';

    /**
     * @var bool Write the context information. The default implementation will dump user information, system variables, etc.
     */
    public $context = true;
    /**
     * @var bool Write the trace information.
     */
    public $trace = true;
    /**
     * @var SentryComponent
     */
    public $client;

    /**
     * @var Scope
     */
    protected $scope;

     /**
     * @throws InvalidConfigException
     * @inheritdoc
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            $this->scope = null;

            [$text, $level, $category, $timestamp, $traces] = $message;

            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string) $text;
                } elseif($text instanceof  SentryMessage) {
                    $this->scope = $text->getScope();
                    $text = VarDumper::export($text->getMessage());
                } else {
                    $text = VarDumper::export($text);
                }
            }

            $scope = $this->getScope();

            $user = Yii::$app->user??null;
            if($user) {
                $scope->setUser([
                    'id' => $user->getId(),
                    'isGuest' => $user->isGuest,
                ]);
            }

            $scope->setLevel(self::getSeverity($level));
            $scope->setTag('category',$category);
            $scope->setTag('timestamp', $timestamp);

            if ($this->context) {
                $scope->setExtra('context', $this->getContextMessage());
            }
            if ($this->trace) {
                $scope->setExtra('trace', $traces);
            }

            $this->trigger(self::EVENT_BEFORE_CAPTURE);

            $this->getClient()->captureMessage($text, self::getSeverity($level), $scope);
        }
    }


    /**
     * we can control of including context or not
     *
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        return '';
    }

    /**
     * Translates log levels to Sentry Severity.
     *
     * @param $logLevel
     * @return Severity
     */
    public static function getSeverity($logLevel)
    {
        switch ($logLevel) {
            case Logger::LEVEL_TRACE:
            case Logger::LEVEL_PROFILE_BEGIN:
            case Logger::LEVEL_PROFILE_END:
                return Severity::debug();
            case Logger::LEVEL_INFO:
                return Severity::info();
            case Logger::LEVEL_WARNING:
                return Severity::warning();
            case Logger::LEVEL_ERROR:
                return Severity::error();
            default:
                return Severity::fatal();
        }
    }

    /**
     * @return SentryComponent
     * @throws \yii\base\InvalidConfigException
     */
    public function getClient(): SentryComponent
    {
        if(is_array($this->client)) {
            $this->client = \Yii::createObject($this->client);
        }
        return $this->client;
    }

    /**
     * @return Scope
     */
    public function getScope(): Scope
    {
        if(!$this->scope) {
            $this->scope = new Scope();
        }

        return $this->scope;
    }

}
