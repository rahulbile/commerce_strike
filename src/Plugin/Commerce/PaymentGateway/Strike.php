<?php

namespace Drupal\commerce_strike\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_price\MinorUnitsConverterInterface;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "strike",
 *   label = "Strike Bitcoin Payments",
 *   display_label = "Bitcoin",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_strike\PluginForm\OffsiteRedirect\StrikeForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_strike/strike",
 *   requires_billing_information = FALSE,
 * )
 */
class Strike extends OffsitePaymentGatewayBase {
  /**
   * The Time Interface
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('commerce_price.minor_units_converter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, MinorUnitsConverterInterface $minor_units_converter,TimeInterface $dateTime) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time, $minor_units_converter, $dateTime);
    $this->dateTime = $dateTime;
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
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['currency'] = $values['currency'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $request_time = $this->dateTime->getRequestTime();
    $remote_status = t('Success');
    $message = $this->t('Your payment was successful with Order id : @orderid has been received at : @date', ['@orderid' => $order->id(), '@date' => date("d-m-Y H:i:s", $request_time)]);
    $status = 'completed';

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
        'state' => $status,
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_state' => $remote_status ? $remote_status : $request->get('payment_status'),
        'authorized' => $request_time,
      ]
    );

    $payment->save();
    \Drupal::messenger()->addMessage($message);

  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $status = $request->get('status');
    drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
      '@status' => $status,
      '@gateway' => $this->getDisplayLabel(),
    ]), 'error');
  }


  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }


}
