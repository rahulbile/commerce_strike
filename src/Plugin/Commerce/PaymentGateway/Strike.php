<?php

namespace Drupal\commerce_strike\Plugin\Commerce\PaymentGateway;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_price\Price;


/**
 * Provides the Bitcoin Lightning payment powered by Strike API.
 *
 * @CommercePaymentGateway(
 *   id = "strike",
 *   label = @Translation("Strike - Bitcoin (Lightning)"),
 *   display_label = @Translation("Strike - Bitcoin (Lightning)"),
 *    forms = {
 *     "add-payment-method" = "Drupal\commerce_strike\PluginForm\Onsite\PaymentMethodAddForm",
 *   },
 *   payment_type = "payment_manual",
 *   payment_method_types = {"commerce_strike"},
 *   requires_billing_information = FALSE
 * )
 */
class Strike extends OnsitePaymentGatewayBase implements StrikeInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    // You can create an instance of the SDK here and assign it to $this->api.
    // Or inject Guzzle when there's no suitable SDK.
  }

  /**
   * {@inheritdoc}
   */
  public function getApiKey() {
    return $this->configuration['api_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiMode() {
    return $this->configuration['mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->configuration['currency'];
  }

  /**
   * {@inheritdoc}
   */
   public function defaultConfiguration() {
   return [
      'api_key' => '',
     ] + parent::defaultConfiguration();
   }

   /**
    * {@inheritdoc}
    */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
      $form = parent::buildConfigurationForm($form, $form_state);

      $form['api_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('API key'),
        '#description' => $this->t('The Strike account API key provided by account manager.'),
        '#default_value' => $this->configuration['api_key'],
        '#required' => TRUE,
      ];

      $form['currency'] = [
        '#type' => 'select',
        '#title' => $this->t('Currency'),
        '#description' => $this->t('The Strike account currency for  creating invoices.'),
        '#default_value' => $this->configuration['currency'],
        '#options' => [ 'USDT' => 'USDT', 'USD' => 'USD'],
        '#required' => TRUE,
      ];

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
      parent::submitConfigurationForm($form, $form_state);
      if ($form_state->getErrors()) {
        return;
      }
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['currency'] = $values['currency'];
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
      $payment_method->check_number = $payment_details['strike_invoiceId'];
      $payment_method->setReusable(FALSE);
      $payment_method->save();
    }

    /**
     * {@inheritdoc}
     */
    public function createPayment(PaymentInterface $payment, $received = FALSE) {
      $this->assertPaymentState($payment, ['new']);

      $payment->state = $received ? 'completed' : 'pending';
      $payment->save();
    }

    /**
     * {@inheritdoc}
     */
    public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {

      // capturePayment method.
    }

    /**
     * {@inheritdoc}
     */
    public function voidPayment(PaymentInterface $payment) {
      $this->assertPaymentState($payment, ['pending']);

      $payment->state = 'voided';
      $payment->save();
    }

    /**
     * {@inheritdoc}
     */
    public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
      $payment_method->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function updatePaymentMethod(PaymentMethodInterface $payment_method) {
      $payment_method->save();
    }
}
