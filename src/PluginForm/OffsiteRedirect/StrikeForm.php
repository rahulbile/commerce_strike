<?php

namespace Drupal\commerce_strike\PluginForm\OffsiteRedirect;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\ExpressionLanguage\Tests\Node\Obj;
use Drupal\Core\Url;


use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides the Off-site payment form.
 */
class StrikeForm extends BasePaymentOffsiteForm {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $owner = \Drupal::routeMatch()->getParameter('commerce_order')->getCustomer();
    $order_id = \Drupal::routeMatch()->getParameter('commerce_order')->id();
    $order = Order::load($order_id);

    if (!$order) {
      throw new \InvalidArgumentException('Payment entity with no order reference given to PaymentAddForm.');
    }

    $amount = ($payment->getAmount()->getNumber());

    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment->bundle(),
    ];

    $form['payment_details'] = $this->buildStrikeForm($order_id, $amount, $form['payment_details'], $form_state);

    return $this->buildRedirectForm($form, $form_state);
  }

  /**
   * Builds the Strike Payment form.
   * @param string $amount
   *   The total amount.
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built Strike payment form.
   */
  protected function buildStrikeForm($order_id, $amount, array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'strike-form';

    $element['strike_invoiceId'] = [
      '#type' => 'hidden'
    ];

    $element['strike_payment'] = [
      '#type' => 'markup',
      '#markup' => '<div id="strikeInvoiceCard" class="strike-invoice-card"></div>'
    ];


    $plugin = $this->plugin;

    $apiUrl = 'https://api.strike.me/v1';

    $mode = $plugin->getApiMode();

    if ($mode === 'test') {
      $apiUrl = 'https://api.next.strike.me/v1';
    }

    $element['#attached']['library'][] = 'commerce_strike/strike';
    $element['#attached']['library'][] = 'commerce_strike/form';
    $moduleVersion = drupal_get_installed_schema_version('commerce_strike');

    $element['#attached']['drupalSettings']['commerceStrike'] = [
      'apiKey' => $plugin->getApiKey(),
      'apiUrl' => $apiUrl,
      'currency' => $plugin->getCurrency(),
      'totalAmount' => $amount,
      'commerce_order_id' => $order_id,
      'moduleVersion' => $moduleVersion,
    ];


    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->entity);
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($element);

    return $element;
  }

  protected function buildRedirectForm(array $form, FormStateInterface $form_state) {

    return $form;
  }

}
