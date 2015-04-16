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

    public function setValueForObjectListByHttpRequest(Http\Request $request) {
        $value = $this->getValueByHttpRequest($request);

        foreach ($this->modelsByToken as $model) {
            if (!empty($value[$model->token])) {
                $model->value = $value[$model->token];
            } else {

            }
        }
    }

    /**
     * @param Query $query
     */
    public function setObjectListByQuery(Query $query) {
        try {
            foreach ($query->getResult() as $item) {
                if (empty($item['token'])) continue;

                $model = new Model\AbTest($item);
                $this->modelsByToken[$model->token] = $model;
            }
        } catch(\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @return Model\AbTest[]
     */
    public function getObjectList() {
        return $this->modelsByToken;
    }

    /**
     * @return array
     */
    public function dumpValue() {
        $value = [];

        foreach ($this->modelsByToken as $model) {
            if (empty($model->token) || empty($model->value)) continue;

            $value[$model->token] = $model->value;
        }


        return $value;
    }

    public function generateValueForObject(Model\AbTest $model) {

    }
}