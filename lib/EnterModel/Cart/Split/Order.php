<?php
namespace EnterModel\Cart\Split;
use EnterModel as Model;

class Order {
    /** @var string|null */
    public $blockName;
    /** @var Order\Seller|null */
    public $seller;
    /** @var Order\Product[] */
    public $products = [];
    /** @var Order\Discount[] */
    public $discounts = [];
    /** @var Order\Action[] */
    public $actions = [];
    /** @var Order\Delivery|null */
    public $delivery;
    /** @var string|null */
    public $sum;
    /** @var string|null */
    public $originalSum;
    /** @var string|null */
    public $paymentMethodId;
    /** @var array */
    public $possibleDeliveryMethodTokens = [];
    /** @var Model\Cart\Split\Interval[] */
    public $possibleIntervals = [];
    /** @var array */
    public $possibleDays = [];
    /** @var array */
    public $possiblePaymentMethodIds = [];
    /** @var array */
    public $possiblePointIds = [];
    /** @var array */
    public $groupedPossiblePointIds = [];
    /** @var string|null */
    public $comment;

    public function __construct($data = []) {
        $this->blockName = $data['block_name'] ? (string)$data['block_name'] : null;
        $this->seller = $data['seller'] ? new Order\Seller($data['seller']) : null;

        foreach ($data['products'] as $item) {
            $this->products[] = new Order\Product($item);
        }

        foreach ($data['discounts'] as $item) {
            $this->discounts[] = new Order\Discount($item);
        }

        foreach ($data['actions'] as $item) {
            $this->actions[] = new Order\Action($item);
        }

        $this->delivery = $data['delivery'] ? new Order\Delivery($data['delivery']) : null;
        $this->sum = $data['total_cost'] ? (string)$data['total_cost'] : null;
        $this->originalSum = $data['total_original_cost'] ? (string)$data['total_original_cost'] : null;
        $this->paymentMethodId = $data['payment_method_id'] ? (string)$data['payment_method_id'] : null;
        foreach ($data['possible_deliveries'] as $token) {
            $this->possibleDeliveryMethodTokens[] = (string)$token;
        }
        foreach ($data['possible_intervals'] as $item) {
            $this->possibleIntervals[] = new Interval($item);
        }
        foreach ($data['possible_days'] as $day) {
            $this->possibleDays[] = (string)$day;
        }
        foreach ((array)$data['possible_payment_methods'] as $id) { // FIXME: убрать приведение к массиву
            $this->possiblePaymentMethodIds[] = (string)$id;
        }
        foreach ($data['possible_points'] as $token => $ids) {
            foreach ($ids as $id) {
                $this->groupedPossiblePointIds[$token][] = (string)$id;
            }
        }
        $this->comment = $data['comment'] ? (string)$data['comment'] : null;
    }
}
