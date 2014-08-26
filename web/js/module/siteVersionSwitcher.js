define(
    ['jquery', 'module/config'],
    function ($, config) {
		$('.js-siteVersionSwitcher').click(function(e){
			e.preventDefault();
            var domain = window.location.host;
            var domainParts = domain.split(".");
            if (domainParts.length > 2) {
                domain = domainParts[domainParts.length - 2] + "." + domainParts[domainParts.length - 1];
            }

			$.cookie(config.siteVersionSwitcher.cookieName, '0', {expires: config.siteVersionSwitcher.cookieLifetime / 60 / 60 / 24, path: '/', domain: domain});
			location = e.currentTarget.href;
		});
    }
);
