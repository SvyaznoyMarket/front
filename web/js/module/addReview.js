define(
    ['jquery', 'mustache', 'module/config', 'module/util', 'jquery.popup', 'jquery.maskedinput'],
    function ($, Mustache, config, util) {

        var $body = $('body');

        function ReviewForm(params) {

            var $el = {
                content         : $('.js-content'),
                jsContentHidden : $('.js-content-hidden')
            };

            var ui = {};

            var template = $('#tpl-product-addReviewForm').html();

            var reviewData = {
                score       : 10,
                author      : '',
                email       : '',
                pros        : '',
                cons        : '',
                extract     : ''
            };

            var options = params;

            function renderTemplate(data) {
                return Mustache.render(template,data);
            }

            function appendTemplate(data) {
                var tplData = data || {
                                productName   : options.productName,
                                productId     : options.productId
                                };
                $el.content.append( renderTemplate(tplData) );
            }

            function toggleContentContainer() {
                $el.jsContentHidden.toggleClass('hidden');
            }

            function postReview(evt) {
                evt.preventDefault();

                if ( !validateForm() ) {
                    return;
                }

                var formObject = $(this).serializeArray();

                for (var i = 0, l = formObject.length; i < l; i++) {
                    reviewData[formObject[i].name] = formObject[i].value;
                }

                reviewData.productId = options.productId;

                $.ajax({
                    url         : ui.reviewForm.prop('action'),
                    type        : 'post',
                    data        : {review: reviewData},
                    beforeSend  : function() {
                        if (ui.error) {
                            ui.error.remove();
                        }
                    },
                    error       : function() {

                    },
                    success     : function(result) {
                        if (result.success) {
                            unset();
                            init('confirm');
                        } else if (result.error) {
                            var $error = ui.error =  $('<div class="error">При заполнении формы допущены ошибки</div>');
                            ui.reviewsWrap.prepend($error);
                            scrollToTop();
                        }
                    }
                });
            }

            function validateForm() {
                var errors = [];
                var fields = [
                    $('input[name="author"]'),
                    $('input[name="email"]'),
                    $('textarea[name="pros"]'),
                    $('textarea[name="cons"]'),
                    $('textarea[name="extract"]')
                ];

                for (var i = 0, ll = fields.length; i < ll; i++) {

                    if (fields[i].val() === '') {
                        fields[i].addClass('fieldError');
                        fields[i].parents('.js-input-group').append(
                        '<span class="error-message">' +
                            getErrorMessage(fields[i].prop('name')) +
                        '</span>');
                        errors.push(fields[i]);
                    } else {
                        fields[i].removeClass('fieldError');
                        fields[i].parents('.js-input-group').find('.error-message').remove();
                    }

                }

                return (errors.length === 0);

            }

            function getErrorMessage(fieldName) {
                var errorMessages = {
                    author  : 'Не указано имя',
                    email   : 'Неверный e-mail',
                    pros    : 'Не указаны достоинства',
                    cons    : 'Не указаны недостатки',
                    extract : 'Не указан комментарий',
                    unknown : 'Не заполнено поле'
                };

                switch (fieldName) {
                    case 'author':
                        return errorMessages.author;
                        break;
                    case 'email':
                        return errorMessages.email;
                        break;
                    case 'pros':
                        return errorMessages.pros;
                        break;
                    case 'cons':
                        return errorMessages.cons;
                        break;
                    case 'extract':
                        return errorMessages.extract;
                        break;
                    default:
                        return errorMessages.unknown;
                        break;
                }
            }

            function handleRatingHover() {
                var hoveredElementIndex = ui.reviewMarkItem.index($(this));

                ui.reviewMarkItem.slice(0, ++hoveredElementIndex).addClass('mark-full');
                ui.reviewMarkItem.slice(hoveredElementIndex).removeClass('mark-full');
            }

            function saveUserRating(evt) {
                evt.preventDefault();

                reviewData.score = (ui.reviewMarkItem.index($(this)) + 1) * 2;
            }

            function bindFormEvents() {
                ui.reviewMarkItem.hover(handleRatingHover);
                ui.reviewMarkItem.click(saveUserRating);

                ui.reviewForm.submit(postReview);

                ui.closeReviewFormBtn.click(closeReviewForm);
            }

            function populateUIObject() {
                ui = $.extend(ui, {
                    jsAddReviewPopup    : $('.js-addReviewPopup'),
                    reviewMarkItem      : $('.reviews-mark__item'),
                    reviewForm          : $('#review-form'),
                    closeReviewFormBtn  : $('.js-close-review-form'),
                    reviewsWrap         : $('.reviews-wrap')
                });
            }

            function showForm() {
                ui.jsAddReviewPopup.show();
            }

            function unset() {
                ui.jsAddReviewPopup.remove();
                toggleContentContainer();
            }

            function closeReviewForm(evt) {
                evt.preventDefault();

                unset();
            }

            function scrollToTop() {
                $body.scrollTo(0);
            }

            function init(reviewConfirm) {
                var formType = (reviewConfirm === 'confirm') ? {success: true} : {};

                appendTemplate(formType);
                toggleContentContainer();
                populateUIObject();
                bindFormEvents();
                showForm();
                scrollToTop();
            }

            (function(){
                init();
            })();

        }

        function showAddReviewForm(evt) {
            evt.preventDefault();

            new ReviewForm({
                productName : $(this).data('productName'),
                productId   : $(this).data('productId')
            });
        }

        $body.on('click', '.js-reviews-add', showAddReviewForm);
    }
);