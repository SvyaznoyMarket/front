<?php

namespace EnterMobile\Controller;

use Enter\Http;
use Enter\Templating;
use Enter\Util;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\AbTestTrait;
use EnterQuery as Query;

class AbTest {
    use ConfigTrait, CurlTrait, LoggerTrait, AbTestTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $abTest = $this->getAbTest();

        $abTestQuery = new Query\AbTest\GetActiveList();
        $curl->prepare($abTestQuery);

        $curl->execute();

        try {
            $abTest->setObjectListByQuery($abTestQuery);
            $abTest->setValueForObjectListByHttpRequest($request);
        } catch(\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['abtest']]);
        }

        if ('POST' === $request->getMethod()) {
            $value = $request->data['abTest'];
            if (!is_array($value)) {
                throw new \Exception('Не передан параметр abTest', 400);
            }

            $abTest->setValueForObjectList($value);
        }

        $page = [
            'abTests' => $abTest->getObjectList(),
        ];

        // рендер
        $rendererConfig = new Templating\PhpClosure\Config();
        $rendererConfig->templateDir = $config->mustacheRenderer->templateDir;
        $renderer = new Templating\PhpClosure\Renderer($rendererConfig);
        $content = $renderer->render('page/abTest', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}