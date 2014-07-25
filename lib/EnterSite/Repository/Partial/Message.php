<?php

namespace EnterSite\Repository\Partial;

use EnterSite\Model;
use EnterSite\Model\Partial;

class Message {
    /**
     * @param \EnterModel\Message[] $messageModels
     * @return Partial\Message[]
     */
    public function getList(
        array $messageModels
    ) {
        $messages = [];

        foreach ($messageModels as $messageModel) {
            $message = new Partial\Message();
            $message->code = (string)$messageModel->code;
            $message->name = $messageModel->name;
            $message->isInfo = \EnterModel\Message::TYPE_INFO == $messageModel->type;
            $message->isError = \EnterModel\Message::TYPE_ERROR == $messageModel->type;
            $message->isSuccess = \EnterModel\Message::TYPE_SUCCESS == $messageModel->type;

            $messages[] = $message;
        }

        return $messages;
    }
}