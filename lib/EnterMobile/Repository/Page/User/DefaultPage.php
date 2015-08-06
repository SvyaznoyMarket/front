<?
    namespace EnterMobile\Repository\Page\User;

    use EnterMobile\Routing;
    use EnterMobile\Repository;
    use EnterMobile\Model;
    use EnterMobile\Model\Page\User\DefaultPage as Page;

    class DefaultPage {

        public function buildObjectByRequest(Page $page, DefaultPage\Request $request) {
            (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

            $userMenu = [];

            foreach ($request->userMenu as $menuItem) {
                if (!$request->user->isEnterprizeMember && $menuItem['token'] == 'enterprize') continue;

                if ($request->httpRequest->getPathInfo() == $menuItem['url']) {
                    $menuItem['isActive'] = true;
                }

                $userMenu[] = $menuItem;
            }

            $page->content->userMenu = $userMenu;
        }

    }