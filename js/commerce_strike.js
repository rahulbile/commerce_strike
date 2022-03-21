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
      Drupal.strikePaymentSuccessCalback = function(successParam) {
       $("[name='payment_information[add_payment_method][payment_details][strike_invoiceId]']").val(successParam.invoiceId);
       $('#edit-actions-next').prop('disabled', false).click();
      }
      $('.strike-form', context).once('strike-processed').each(function () {
        var $form = $(this).closest('form');
        $form.find('#edit-actions-next').prop('value', 'Scan QR and Pay').prop('disabled', true);
        try {
          strikeJS.generateInvoice({
    				'debug': true,
    				'element': '#strikeInvoiceCard',
    				'amount': parseFloat(.01),
    				'currency': drupalSettings.commerceStrike.currency,
    				'redirectCallback': Drupal.strikePaymentSuccessCalback,
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
