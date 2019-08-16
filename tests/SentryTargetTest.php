<?php
namespace p4it\sentry\tests\unit;

use Codeception\PHPUnit\TestCase;
use p4it\sentry\log\SentryMessage;
use p4it\sentry\log\SentryTarget;
use p4it\sentry\SentryComponent;
use ReflectionClass;
use Sentry\Severity;
use Yii;
use yii\base\Event;
use yii\db\Migration;
use yii\log\Dispatcher;
use yii\log\Logger;

/**
 * Unit-tests for SentryTarget
 *
 * @mixin TestCase
 */
class SentryTargetTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();
        /** @var \yii\db\Connection $db */
        $db = Yii::$app->getDb();
        $migration = new Migration();

        $db->createCommand()->createTable('log', [
            'id' => $migration->bigPrimaryKey(),
            'level' => $migration->integer(),
            'category' => $migration->string(),
            'log_time' => $migration->double(),
            'prefix' => $migration->text(),
            'message' => $migration->text(),
        ])->execute();
    }



    protected function tearDown()
    {
        $db = Yii::$app->getDb();
        $db->createCommand()->dropTable('log')->execute();
        parent::tearDown();
    }


    /**
     * Testing setup
     */
    public function testSetup()
    {
        $this->assertTrue(\Yii::$app->getLog()->targets[0] instanceof SentryTarget, 'Targets contains SentryTarget');
        /** @var SentryTarget $target */
        $target = \Yii::$app->getLog()->targets[0];
        $this->assertTrue($target->getSentryComponent() instanceof SentryComponent, 'Client is instance of SentryComponent');
    }
    /**
     * Testing setup
     */
    public function testBeforeCapture()
    {
        /** @var SentryTarget $target */
        $target = \Yii::$app->getLog()->targets[0];
        $beforeCaptureEventHappened = false;
        $target->on(SentryTarget::EVENT_BEFORE_CAPTURE, function (Event $event) use (&$beforeCaptureEventHappened) {
            $beforeCaptureEventHappened = true;

            /** @var \p4it\sentry\log\SentryTarget $sender */
            $sender = $event->sender;
            $sender->getScope()->setExtra('beforeCapture', 'beforeCapture');
        });

        /** @var Dispatcher $dispatcher */
        $dispatcher = \Yii::$app->getLog();
        \Yii::warning('warning message');
        $dispatcher->logger->flush(true);

        $this->assertTrue($beforeCaptureEventHappened, 'Before capture event did not happened');
        $this->assertArrayHasKey('beforeCapture', $target->getScope()->getExtra(), 'Before capture did not modified scope');
    }

    /**
     * Testing before capture event
     */
    public function testSentryMessage()
    {
        /** @var SentryTarget $target */
        $target = \Yii::$app->getLog()->targets[0];
        $target->categories[] = 'extra';

        /** @var Dispatcher $dispatcher */
        $dispatcher = \Yii::$app->getLog();
        \Yii::error(SentryMessage::create()->setMessage('message')->setExtra('extra','extra'),'extra');
        $dispatcher->logger->flush(true);

        $this->assertArrayHasKey('extra', $target->getScope()->getExtra(), 'Before capture did not modified scope');
    }


    /**
     * Testing method getSeverity()
     * - returns level name for each logger level
     * @see SentryTarget::getSeverity
     */
    public function testLogLevelToSeverity()
    {
        /** @var SentryTarget $target */
        $target = \Yii::$app->getLog()->targets[0];

        //valid level names
        $loggerClass = new ReflectionClass(Logger::class);
        $loggerLevelConstants = $loggerClass->getConstants();
        foreach ($loggerLevelConstants as $constant => $value) {
            if (strpos($constant, 'LEVEL_') === 0) {
                $level = $target::getSeverity($value);
                $this->assertNotEmpty($level);
                $this->assertTrue($level instanceof Severity, sprintf('Level "%s" is incorrect', $level));
            }
        }
        //check default level name
        $this->assertEquals(Severity::fatal(), $target::getSeverity(''));
        $this->assertEquals(Severity::fatal(), $target::getSeverity(uniqid('sentry', false)));
    }
}