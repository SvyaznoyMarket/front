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
    public $groupedPossiblePointIds = [];
    /** @var string|null */
    public $comment;

    public function __construct($data = []) {
        if (isset($data['block_name'])) {
            $this->blockName = (string)$data['block_name'];
        }

        if (isset($data['seller'])) {
            $this->seller = new Order\Seller($data['seller']);
        }

        if (isset($data['products']) && is_array($data['products'])) {
            foreach ($data['products'] as $item) {
                $this->products[] = new Order\Product($item);
            }
        }

        if (isset($data['discounts']) && is_array($data['discounts'])) {
            foreach ($data['discounts'] as $item) {
                $this->discounts[] = new Order\Discount($item);
            }
        }

        if (isset($data['actions']) && is_array($data['actions'])) {
            foreach ($data['actions'] as $item) {
                $this->actions[] = new Order\Action($item);
            }
        }

        if (isset($data['delivery'])) {
            $this->delivery = new Order\Delivery($data['delivery']);
        }

        if (isset($data['total_cost'])) {
            $this->sum = (string)$data['total_cost'];
        }

        if (isset($data['total_original_cost'])) {
            $this->originalSum = (string)$data['total_original_cost'];
        }

        if (isset($data['payment_method_id'])) {
            $this->paymentMethodId = (string)$data['payment_method_id'];
        }

        if (isset($data['possible_deliveries']) && is_array($data['possible_deliveries'])) {
            foreach ($data['possible_deliveries'] as $token) {
                $this->possibleDeliveryMethodTokens[] = (string)$token;
            }
        }

        if (isset($data['possible_intervals']) && is_array($data['possible_intervals'])) {
            foreach ($data['possible_intervals'] as $item) {
                $this->possibleIntervals[] = new Interval($item);
            }
        }

        if (isset($data['possible_days']) && is_array($data['possible_days'])) {
            foreach ($data['possible_days'] as $day) {
                $this->possibleDays[] = (string)$day;
            }
        }

        if (isset($data['possible_payment_methods']) && is_array($data['possible_payment_methods'])) {
            foreach ($data['possible_payment_methods'] as $id) {
                $this->possiblePaymentMethodIds[] = (string)$id;
            }
        }

        if (isset($data['possible_points']) && is_array($data['possible_points'])) {
            foreach ($data['possible_points'] as $token => $ids) {
                if (is_array($ids)) {
                    foreach ($ids as $id) {
                        $this->groupedPossiblePointIds[$token][] = (string)$id;
                    }
                }
            }
        }

        if (isset($data['comment'])) {
            $this->comment = (string)$data['comment'];
        }
    }
}
