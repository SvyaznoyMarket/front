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
     * @param bool $required
     * @return Model\User|null
     * @throws \Exception
     */
    public function getObjectByQuery(Query $query, $required = true) {
        $user = null;

        try {
            if ($item = $query->getResult()) {
                $user = new Model\User($item);
            }
        } catch (\Exception $e) {
            if ($required) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);

                if (402 == $e->getCode()) {
                    throw new \Exception('Пользователь не авторизован', 401);
                }
            }
        }

        return $user;
    }
}