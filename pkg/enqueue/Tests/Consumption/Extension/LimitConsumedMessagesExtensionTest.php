<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LimitConsumedMessagesExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumedMessagesExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Expected message limit is int but got: "double"'
        );

        new LimitConsumedMessagesExtension(0.0);
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsZero()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "0"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(0);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsLessThatZero()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "-1"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(-1);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMessageLimitExceeded()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "2"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(2);

        // consume 1
        $extension->onPostReceived($context);
        $this->assertFalse($context->isExecutionInterrupted());

        // consume 2 and exit
        $extension->onPostReceived($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext(): Context
    {
        $context = new Context($this->createMock(InteropContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setConsumer($this->createMock(Consumer::class));
        $context->setProcessor($this->createMock(Processor::class));

        return $context;
    }
}
