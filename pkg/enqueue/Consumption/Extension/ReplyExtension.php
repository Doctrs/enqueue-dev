<?php

namespace Enqueue\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;

class ReplyExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $replyTo = $context->getInteropMessage()->getReplyTo();
        if (false == $replyTo) {
            return;
        }

        /** @var Result $result */
        $result = $context->getResult();
        if (false == $result instanceof Result) {
            return;
        }

        if (false == $result->getReply()) {
            return;
        }

        $correlationId = $context->getInteropMessage()->getCorrelationId();
        $replyMessage = clone $result->getReply();
        $replyMessage->setCorrelationId($correlationId);

        $replyQueue = $context->getInteropContext()->createQueue($replyTo);

        $context->getLogger()->debug(sprintf('[ReplyExtension] Send reply to "%s"', $replyTo));
        $context->getInteropContext()->createProducer()->send($replyQueue, $replyMessage);
    }
}
