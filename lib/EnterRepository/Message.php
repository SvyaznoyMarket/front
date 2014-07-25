<?php

namespace EnterRepository;

use Enter\Http;
use EnterModel as Model;

class Message {
    /**
     * @param $key
     * @param Http\Session $session
     * @return Model\Message|null
     */
    public function getObjectByHttpSession($key, Http\Session $session) {
        $message = null;

        if ($item = $session->flashBag->get($key)) {
            if (isset($item['name'])) {
                $message = new Model\Message($item);
            }
        }

        return $message;
    }

    /**
     * @param $listKey
     * @param Http\Session $session
     * @return Model\Message[]
     */
    public function getObjectListByHttpSession($listKey, Http\Session $session) {
        $messages = [];

        if ($data = $session->flashBag->get($listKey)) {
            foreach (is_array($data) ? $data : [] as $key => $item) {
                if (isset($item['name'])) {
                    $messages[$key] = new Model\Message($item);
                }
            }
        }

        return $messages;
    }

    /**
     * @param $key
     * @param Model\Message|string $message
     * @param Http\Session $session
     */
    public function setObjectToHttpSesion($key, $message, Http\Session $session) {
        if (is_string($message)) {
            $message = new Model\Message(['name' => $message]);
        }

        if (!$message instanceof Model\Message) return;

        $session->flashBag->set($key, $this->dumpObject($message));
    }

    /**
     * @param $listKey
     * @param Model\Message[]|string[] $messages
     * @param Http\Session $session
     */
    public function setObjectListToHttpSesion($listKey, array $messages, Http\Session $session) {
        $data = [];

        foreach ($messages as $key => $message) {
            if (is_string($message)) {
                $message = new Model\Message(['name' => $message]);
            }

            if (!$message instanceof Model\Message) continue;

            $data[$key] = $this->dumpObject($message);
        }

        if ((bool)$data) {
            $session->flashBag->set($listKey, $data);
        }
    }

    /**
     * @param Model\Message $message
     * @return array
     */
    public function dumpObject(Model\Message $message) {
        return [
            'type' => $message->type,
            'code' => $message->code,
            'name' => $message->name,
        ];
    }
}