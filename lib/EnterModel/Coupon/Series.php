<?php

namespace EnterModel\Coupon {

    use EnterModel as Model;

    class Series {
        /** @var string */
        public $id;
        /** @var string */
        public $discount;
        /** @var string */
        public $backgroundImageUrl;
        /** @var string */
        public $startAt;
        /** @var string */
        public $endAt;
        /** @var string */
        public $minOrderSum;
        /** @var Series\Segment|null */
        public $productSegment;
        /** @var int */
        public $limit;
        /** @var bool */
        public $isForMember;
        /** @var bool */
        public $isForNotMember;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('uid', $data)) $this->id = (string)$data['uid'];
            if (!empty($data['value'])) {
                $isCurrency = @$data['is_currency'];
                $this->discount = (string)$data['value'] . ($isCurrency ? '' : '%');
            }
            try {
                if (!empty($data['start_date'])) $this->startAt = date('c', strtotime((string)$data['start_date']));
            } catch (\Exception $e) {}
            try {
                if (!empty($data['end_date'])) $this->endAt = date('c', strtotime((string)$data['end_date']));
            } catch (\Exception $e) {}
            if (!empty($data['background_image_url'])) $this->backgroundImageUrl = (string)$data['background_image_url'];
            if (!empty($data['min_order_sum'])) $this->minOrderSum = (string)$data['min_order_sum'];

            $this->productSegment = new Series\Segment();
            if (array_key_exists('segment', $data)) $this->productSegment->name = (string)$data['segment'];
            if (array_key_exists('segment_url', $data)) $this->productSegment->url = (string)$data['segment_url'];
            if (array_key_exists('segment_image_url', $data)) $this->productSegment->imageUrl = (string)$data['segment_image_url'];
            if (array_key_exists('segment_description', $data)) $this->productSegment->description = str_replace('\\r\\n', '', strip_tags((string)$data['segment_description']));

            if (array_key_exists('is_for_member', $data)) $this->isForMember = (bool)$data['is_for_member'];
            if (array_key_exists('is_for_not_member', $data)) $this->isForNotMember = (bool)$data['is_for_not_member'];
        }
    }
}

namespace EnterModel\Coupon\Series {
    class Segment {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $imageUrl;
        /** @var string */
        public $description;
    }
}