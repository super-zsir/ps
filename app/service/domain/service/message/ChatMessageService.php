<?php
namespace Imee\Service\Domain\Service\Message;

use Imee\Service\Domain\Context\Message\ChatMessageListContext;
use Imee\Service\Domain\Context\Message\ListContext;
use Imee\Service\Domain\Service\Message\Processes\ChatMessageListProcess;
use Imee\Service\Domain\Service\Message\Processes\ListProcess;

class ChatMessageService
{
    public function getChatMessageList(ChatMessageListContext $context)
    {
        $process = new ChatMessageListProcess($context);
        return $process->handle();
    }

    public function getList(ListContext $context)
    {
        $process = new ListProcess($context);
        return $process->handle();
    }
}
