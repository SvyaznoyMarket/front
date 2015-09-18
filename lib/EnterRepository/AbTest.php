<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class AbTest {
    use ConfigTrait, LoggerTrait;

    /** @var Model\AbTest[] */
    protected $modelsByToken = [];

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getValueByHttpRequest(Http\Request $request) {
        $config = $this->getConfig()->abTest;

        $value = json_decode((string)$request->cookies[$config->cookieName]);

        return (array)$value;
    }

    /**
     * @param Http\Request $request
     */
    public function setValueForObjectListByHttpRequest(Http\Request $request) {
        $value = $this->getValueByHttpRequest($request);

        $this->setValueForObjectList($value);
    }

    /**
     * @param array $value
     */
    public function setValueForObjectList(array $value) {
        foreach ($this->modelsByToken as $model) {
            if (!empty($value[$model->token])) {
                    foreach ($model->items as $item) {
                        if ($item->token === $value[$model->token]) {
                            if (0 === $item->traffic) {
                                $this->generateValueForObject($model);
                            } else {
                                $model->chosenItem = $item;
                            }

                            break;
                        }
                    }
            } else {
                $this->generateValueForObject($model);
            }
        }
    }

    /**
     * @param Query $query
     */
    public function setObjectListByQuery(Query $query) {
        foreach ($query->getResult() as $item) {
            if (empty($item['token'])) continue;

            $model = new Model\AbTest($item);
            $this->modelsByToken[$model->token] = $model;
        }
        //unset($this->modelsByToken['salePercentage']);
    }

    /**
     * @return Model\AbTest[]
     */
    public function getObjectList() {
        return $this->modelsByToken;
    }

    /**
     * @param $token
     * @return Model\AbTest|null
     */
    public function getObjectByToken($token) {
        $model = isset($this->modelsByToken[$token]) ? $this->modelsByToken[$token] : null;

        if (!$model) {
            $item = new Model\AbTest\Item();
            $item->token = 'default';

            $model = new Model\AbTest();
            $model->token = $token;
            $model->isActive = false;
            $model->items[] = $item;
            $model->chosenItem = $item;
        }

        return $model;
    }

    /**
     * @return array
     */
    public function dumpValue() {
        $value = [];

        foreach ($this->modelsByToken as $model) {
            if (empty($model->token) || !$model->chosenItem) continue;

            $value[$model->token] = $model->chosenItem->token;
        }

        ksort($value);

        return $value;
    }

    /**
     * @param Http\Response $response
     * @param Http\Request $request
     */
    public function setValueForResponse(Http\Response $response, Http\Request $request) {
        $encodedValue = json_encode($this->dumpValue());

        if ($encodedValue !== json_encode($this->getValueByHttpRequest($request))) {
            $config = $this->getConfig()->abTest;

            $response->headers->setCookie(new Http\Cookie(
                $config->cookieName,
                $encodedValue,
                time() + 20 * 365 * 24 * 60 * 60,
                '/',
                $this->getConfig()->abTest->cookieDomain,
                false,
                false
            ));
        }
    }

    /**
     * @param Model\AbTest $model
     */
    public function generateValueForObject(Model\AbTest $model) {
        $luck = mt_rand(0, 99);
        $total = 0;

        foreach ($model->items as $item) {
            if ($total >= 100) continue;

            $diff = (int)$item->traffic;
            if ($luck < $total + $diff) {
                $model->chosenItem = $item;

                return;
            }

            $total += $diff;
        }
    }

    /**
     * Включена ли серверная корзина
     * @return bool
     */
    public function isCoreCartEnabled() {
        return 'enabled' === $this->getObjectByToken('msite_core_cart')->chosenItem->token;
    }
}