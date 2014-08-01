<?php

namespace EnterMobile\Model\Page {
    use EnterModel\HtmlPage;
    use EnterMobile\Model\Partial;

    class DefaultPage extends HtmlPage {
        /** @var string */
        public $dataDebug;
        /** @var string */
        public $dataVersion;
        /** @var string */
        public $dataModule;
        /** @var string */
        public $dataConfig;
        /** @var DefaultPage\Template[] */
        public $templates = [];
        /** @var DefaultPage\GoogleAnalytics|null */
        public $googleAnalytics;
        /** @var DefaultPage\GoogleTagManager|null|bool */
        public $googleTagManager;
        /** @var DefaultPage\YandexMetrika|null|bool */
        public $yandexMetrika;
        /** @var DefaultPage\RegionBlock */
        public $regionBlock;
        /** @var DefaultPage\MainMenu */
        public $mainMenu;
        /** @var Partial\UserBlock */
        public $userBlock;
        /** @var DefaultPage\BreadcrumbBlock|null */
        public $breadcrumbBlock;
        /** @var DefaultPage\Search */
        public $search;
        /** @var DefaultPage\Content */
        public $content;
        /** @var Partial\Partner[] */
        public $partners = [];

        public function __construct() {
            parent::__construct();

            $this->regionBlock = new DefaultPage\RegionBlock();
            $this->mainMenu = new DefaultPage\MainMenu();
            $this->userBlock = new Partial\UserBlock();
            $this->search = new DefaultPage\Search();
            $this->content = new DefaultPage\Content();
        }
    }
}

namespace EnterMobile\Model\Page\DefaultPage {
    class GoogleAnalytics {
        public $id;
    }

    class GoogleTagManager {
        /** @var string */
        public $id;
    }

    class YandexMetrika {
        /** @var int */
        public $id;
    }

    /**
     * Шаблоны mustache для блоков <script id="{{id}}" type="text/html">{{content}}</script>
     */
    class Template {
        /** @var string */
        public $id;
        /** @var string */
        public $content;
        /** @var string */
        public $dataPartial;
    }

    class BreadcrumbBlock {
        /** @var BreadcrumbBlock\Breadcrumb[] */
        public $breadcrumbs = [];
    }

    class RegionBlock {
        /** @var string */
        public $regionName;
        /** @var string */
        public $setUrl;
        /** @var string */
        public $autocompleteUrl;
        /** @var RegionBlock\Region[] */
        public $regions = [];
    }

    class MainMenu {
    }

    class Search {
        /** @var string */
        public $inputPlaceholder;
        /** @var Search\Hint[] */
        public $hints = [];
    }

    class Content {
        /** @var string */
        public $title;

        public function __construct() {}
    }

}

namespace EnterMobile\Model\Page\DefaultPage\BreadcrumbBlock {
    class Breadcrumb {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}

namespace EnterMobile\Model\Page\DefaultPage\RegionBlock {
    class Region {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $dataGa;
    }
}

namespace EnterMobile\Model\Page\DefaultPage\Search {
    class Hint {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}