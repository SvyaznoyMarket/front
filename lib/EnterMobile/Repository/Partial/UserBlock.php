<?php

namespace EnterMobile\Repository\Partial;

use EnterAggregator\PriceHelperTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class UserBlock {
    use RouterTrait, PriceHelperTrait;

    /**
     * @param \EnterModel\Cart $cart
     * @param \EnterModel\User|null $user
     * @return Partial\UserBlock
     */
    public function getObject(
        \EnterModel\Cart $cart,
        \EnterModel\User $user = null
    ) {
        $router = $this->getRouter();

        $userBlock = new Model\Partial\UserBlock();

        if ($user) {
            $userBlock->isUserAuthorized = true;
            $userBlock->userLink->name = $user->firstName ?: $user->lastName;
            $userBlock->userLink->url = $router->getUrlByRoute(new Routing\User\Index());
            $userBlock->isEnterprizeMember = $user->isEnterprizeMember;
        } else {
            $userBlock->isUserAuthorized = false;
            $userBlock->userLink->url = $router->getUrlByRoute(new Routing\User\Login());
        }

        $userBlock->isCartNotEmpty = (bool)$cart->product;
        $userBlock->cart->url = $router->getUrlByRoute(new Routing\Cart\Index());
        if ($userBlock->isCartNotEmpty) {
            $userBlock->cart->quantity = count($cart);
            $userBlock->cart->shownSum = $cart->sum ? $this->getPriceHelper()->format($cart->sum) : null;
            $userBlock->cart->sum = $cart->sum;
        }

        return $userBlock;
    }
}