<?php

namespace EnterMobile\Repository;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Model;

class Template {
    use ConfigTrait, LoggerTrait, TemplateHelperTrait;

    /**
     * @param Model\Page\DefaultPage $page
     * @param array $templateData ['id' => null, 'name' => null, 'partials' => []]
     */
    public function setListForPage(Model\Page\DefaultPage $page, array $templateData) {
        /** @var int[] */
        static $indexesById = [];

        $templateHelper = $this->getTemplateHelper();
        $templateDir = $this->getConfig()->mustacheRenderer->templateDir;

        foreach ($templateData as $templateItem) {
            try {
                $template = new Model\Page\DefaultPage\Template();
                $template->id = $templateItem['id'];
                $template->content = file_get_contents($templateDir . '/' . $templateItem['name'] . '.mustache');

                $partialData = [];
                if (isset($templateItem['partials'][0])) {
                    foreach ($templateItem['partials'] as $partial) {
                        $partialData[$partial] = file_get_contents($templateDir . '/' . $partial . '.mustache');
                    }
                }

                $template->dataPartial = $templateHelper->json($partialData);

                $index = (array_key_exists($template->id, $indexesById) ? $indexesById[$template->id] : null);
                if (null !== $index) {
                    $page->templates[$index] = $template;
                } else {
                    $index = array_push($page->templates, $template) - 1;
                    $indexesById[$template->id] = $index;
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['template']]);
            }
        }
    }
}