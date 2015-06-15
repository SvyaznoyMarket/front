<?php

namespace EnterModel;

use EnterModel as Model;

class Cart implements \Countable {
    /** @var Model\Cart\Product[] */
    public $product = [];
    /** @var float */
    public $sum;
    /**
     * @var int Увеличивающийся при каждом изменении корзины счётчик (не используем время, т.к. в одну секунду может
     *          произойти запрос на получение содержимого корзины и запрос на изменение корзины, что привело бы к тому,
     *          что последующие запросы на получения корзины возвращали бы 304 код ответа и произошедшее изменение
     *          корзины осталось бы незамеченным)
     */
    public $cacheId;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['product_list'][0])) {
            foreach ($data['product_list'] as $productData) {
                if (empty($productData['id'])) continue;

                $this->product[] = new Model\Cart\Product($productData);
            }
        }
        if (array_key_exists('sum', $data)) $this->sum = (float)$data['sum'];
    }

    /**
     * @return int
     */
    public function count() {
        $count = 0;
        foreach ($this->product as $cartProduct) {
            $count += $cartProduct->quantity;
        }

        return $count;
    }
}