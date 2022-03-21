<?php

namespace Drupal\commerce_strike\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;
use Drupal\entity\BundleFieldDefinition;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the check payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_strike",
 *   label = @Translation("Bitcoin"),
 * )
 */
class Strike extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $args = [
      '@strike_invoiceId' => $payment_method->strike_invoiceId->value,
    ];
    return $this->t('Strike Invoice Id : @strike_invoiceId', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['strike_invoiceId'] = BundleFieldDefinition::create('string')
      ->setLabel($this->t('Strike Invoice ID '))
      ->setDescription($this->t('The strike Invoice Id'))
      ->setRequired(TRUE);

    return $fields;
  }

}
