<?php

namespace EnterMobile\Repository\Page\Product;

use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Product\ReviewList as Page;

class ReviewList {
    use ConfigTrait, MustacheRendererTrait, DateHelperTrait;

    /**
     * @param Page $page
     * @param ReviewList\Request $request
     */
    public function buildObjectByRequest(Page $page, ReviewList\Request $request) {
        $renderer = $this->getRenderer();

        $dateHelper = $this->getDateHelper();

        $ratingRepository = new Repository\Partial\Rating();

        $page->count = $request->reviewCount;
        $page->page = $request->pageNum;
        $page->limit = $request->limit;

        $reviews = [];
        foreach ($request->reviews as $reviewModel) {
            $review = new Partial\ProductReview(); // TODO: перенести
            $review->author = $reviewModel->author;
            $review->createdAt = $reviewModel->createdAt ? $dateHelper->dateToRu($reviewModel->createdAt): null;
            $review->extract = $reviewModel->extract;
            $review->cons = $reviewModel->cons;
            $review->pros = $reviewModel->pros;
            $review->stars = $ratingRepository->getStarList($reviewModel->starScore);

            $reviews[] = $review;
        }

        $page->reviewBlock = (bool)$reviews ? $renderer->render('page/product-card/review-list', ['reviews' => $reviews]) : null;

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}