/**
 * @file
 * JS for Breema-specific social media share links / icons.
 */

/**
 * Show/hide the "More links" options under the (+) icon.
 */
(function($) {
  Drupal.behaviors.shareExpander = {
    attach: function (context, settings) {

      $(".main-links .more", context).click(function(e) {
        e.preventDefault();
        $(this).closest('.main-links').siblings('.more-links').toggleClass('open');
      });
      $(".more-links-close", context).click(function() {
        $(this).closest('.more-links').removeClass('open');
      });
    }
  };
})(jQuery);
