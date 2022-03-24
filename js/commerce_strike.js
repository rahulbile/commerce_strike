/**
 * @file
 * Javascript to generate Strike QR Code
 */
(function ($, Drupal, drupalSettings) {

  'use strict';
  Drupal.behaviors.commerceStrikeForm = {
    attach: function (context) {
      var self = this;
      if (!drupalSettings.commerceStrike || !drupalSettings.commerceStrike.apiKey) {
        return;
      }

      $('.strike-form', context).once('strike-processed').each(function () {
        var $form = $(this).closest('form');
        $form.find('#edit-actions-next').prop('value', 'Scan QR and Pay').prop('disabled', true);
        var baseUrl =  window.location.origin + '/' + window.location.pathname.split ('/') [1] + '/';
        try {
          strikeJS.generateInvoice({
    				'element': '#strikeInvoiceCard',
    				'amount': parseFloat(drupalSettings.commerceStrike.totalAmount),
    				'currency': drupalSettings.commerceStrike.currency,
    				'redirectUrl': baseUrl + '/strike-capture-payment?order_id=' + drupalSettings.commerceStrike.commerce_order_id,
    				'apiUrl': drupalSettings.commerceStrike.apiUrl,
    				'apiKey': drupalSettings.commerceStrike.apiKey
    			});
        } catch (e) {
          $form.find('#payment-errors').html(Drupal.theme('commerceStrikeError', e.message));
          $form.find(':input.button--primary').prop('disabled', true);
          $(this).find('.form-item').hide();
          return;
        }
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') {
        return;
      }
      var $form = $('.strike-form', context).closest('form');
      if ($form.length === 0) {
        return;
      }

      $form.find('#edit-actions-next').prop('value', 'Pay and complete purchase').prop('disabled', false);

      $form.off('submit.commerce_strike');
    }
  };
})(jQuery, Drupal, drupalSettings);
