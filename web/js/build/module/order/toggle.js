define(["require","jquery"],function(e){var g=e(".js-toggle-wrap"),l=g.find(".js-toggle-link"),o=(g.find(".js-toggle-box"),"toggle--active");g.removeClass(o),l.click(function(){e(this).closest(".js-toggle-wrap").toggleClass(o)})});