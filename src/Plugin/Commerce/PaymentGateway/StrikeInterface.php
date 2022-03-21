<?php

namespace Drupal\commerce_strike\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
/**
 * Provides the interface for the Stripe payment gateway.
 */
interface StrikeInterface extends OnsitePaymentGatewayInterface {

  /**
   * Get the Strike API key set for the payment gateway.
   *
   * @return string
   *   The Strike API publishable key.
   */
  public function getApiKey();

  /**
   * Get the Strike API mode  for the payment gateway.
   *
   * @return string
   *   The Strike API mode.
   */
  public function getApiMode();

  /**
   * Get the Strike Currency  for the payment gateway.
   *
   * @return string
   *   The Strike Currency.
   */
  public function getCurrency();


}
