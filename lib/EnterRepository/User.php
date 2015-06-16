<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class User {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Query $query
     * @throws \Exception
     * @return Model\User|null
     */
    public function getObjectByQuery(Query $query) {
        $user = null;

        try {
            if ($item = $query->getResult()) {
                $user = new Model\User($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);

            if (402 == $e->getCode()) {
                throw new \Exception('Пользователь не авторизован', 401);
            }
        }

        return $user;
    }
}