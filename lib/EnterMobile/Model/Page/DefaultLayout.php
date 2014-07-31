<?php

namespace EnterMobile\Model\Page {
    use EnterMobile\Model\HtmlPage;
    use EnterMobile\Model\Partial;

    class DefaultLayout extends HtmlPage {
        /** @var string */
        public $dataDebug;
        /** @var string */
        public $dataVersion;
        /** @var string */
        public $dataModule;
        /** @var string */
        public $dataConfig;
        /** @var DefaultLayout\Template[] */
        public $templates = [];
        /** @var DefaultLayout\GoogleAnalytics|null */
        public $googleAnalytics;
        /** @var DefaultLayout\GoogleTagManager|null|bool */
        public $googleTagManager;
        /** @var DefaultLayout\YandexMetrika|null|bool */
        public $yandexMetrika;
        /** @var DefaultLayout\RegionBlock */
        public $regionBlock;
        /** @var DefaultLayout\MainMenu */
        public $mainMenu;
        /** @var Partial\UserBlock */
        public $userBlock;
        /** @var DefaultLayout\BreadcrumbBlock|null */
        public $breadcrumbBlock;
        /** @var DefaultLayout\Search */
        public $search;
        /** @var DefaultLayout\Content */
        public $content;
        /** @var Partial\Partner[] */
        public $partners = [];

        public function __construct() {
            parent::__construct();

            $this->regionBlock = new DefaultLayout\RegionBlock();
            $this->mainMenu = new DefaultLayout\MainMenu();
            $this->userBlock = new Partial\UserBlock();
            $this->search = new DefaultLayout\Search();
            $this->content = new DefaultLayout\Content();
        }
    }
}

namespace EnterMobile\Model\Page\DefaultLayout {
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

namespace EnterMobile\Model\Page\DefaultLayout\BreadcrumbBlock {
    class Breadcrumb {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}

namespace EnterMobile\Model\Page\DefaultLayout\RegionBlock {
    class Region {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $dataGa;
    }
}

namespace EnterMobile\Model\Page\DefaultLayout\Search {
    class Hint {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}