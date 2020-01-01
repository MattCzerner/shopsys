(function ($) {
    Shopsys = window.Shopsys || {};

    $footerLinkTitle = $('.js-footer-link-title');
    var footerLinkWrapperSelector = '.js-footer-link-wrapper';
    var footerLinkContentSelector = '.js-footer-link-content';

    $(document).ready(function () {
        $footerLinkTitle.on("click", function () {
            if(!Shopsys.responsive.isDesktopVersion()){
                if($(this).hasClass('open')){
                    $(this).toggleClass('open');
                    $(this).parent(footerLinkWrapperSelector).find(footerLinkContentSelector).slideToggle();
                } else {
                    $(this).addClass('open');
                    $(this).parent(footerLinkWrapperSelector).find(footerLinkContentSelector).slideDown();
                }
            }
        });
    });

    $(window).resize(function () {
        if(Shopsys.responsive.isDesktopVersion()){
            $footerLinkTitle.removeClass('open');
            $footerLinkTitle.parent(footerLinkWrapperSelector).find(footerLinkContentSelector).slideDown();
        } else {
            $footerLinkTitle.parent(footerLinkWrapperSelector).find(footerLinkContentSelector).slideUp();
        }
    });
})(jQuery);
