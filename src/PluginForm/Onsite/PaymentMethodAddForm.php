<?php

namespace Drupal\commerce_strike\PluginForm\Onsite;

use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a class for Strike payment gateway plugin add form.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function getErrorElement(array $form, FormStateInterface $form_state) {
    return $form['payment_details'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment_method->bundle(),
    ];
    $form['payment_details'] = $this->buildStrikeForm($form['payment_details'], $form_state);

    // Move the billing information below the payment details.
    if (isset($form['billing_information'])) {
      $form['billing_information']['#weight'] = 10;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $this->submitStrikeForm($form['payment_details'], $form_state);

    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;

    try {
      $payment_gateway_plugin->createPaymentMethod($payment_method, $values['payment_details']);
    }
    catch (DeclineException $e) {
      $this->logger->warning($e->getMessage());
      throw new DeclineException('We encountered an error processing your payment method. Please verify your details and try again.');
    }
    catch (PaymentGatewayException $e) {
      $this->logger->error($e->getMessage());
      throw new PaymentGatewayException('We encountered an unexpected error processing your payment method. Please try again later.');
    }
  }

  /**
   * Builds the Strike Payment form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built Strike payment form.
   */
  protected function buildStrikeForm(array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'strike-form';

    $element['strike_invoiceId'] = [
      '#type' => 'hidden'
    ];

    $element['strike_payment'] = [
      '#type' => 'markup',
      '#markup' => '<div id="strikeInvoiceCard" class="strike-invoice-card"></div>'
    ];


    $plugin = $this->plugin;

    $mode = $plugin->getApiMode();
    if ($mode === 'test') {
      $apiUrl = 'https://api.next.strike.me/v1';
    }  elseif ($mode === 'live') {
      $apiUrl = 'https://api.strike.me/v1';
    }
    $element['#attached']['library'][] = 'commerce_strike/strike';
    $element['#attached']['library'][] = 'commerce_strike/form';
    $element['#attached']['drupalSettings']['commerceStrike'] = [
      'apiKey' => $plugin->getApiKey(),
      'apiUrl' => $apiUrl,
      'currency' => $plugin->getCurrency()
    ];


    $cacheability = new CacheableMetadata();
    $cacheability->addCacheableDependency($this->entity);
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($element);

    return $element;
  }

  /**
   * Handles the submission of the Strike Payment form.
   *
   * @param array $element
   *   The Strike form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitStrikeForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    $this->entity->strike_invoiceId = $values['strike_invoiceId'];
  }

}
