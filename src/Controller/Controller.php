<?php

namespace Drupal\commerce_strike\Controller;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Controller
 * @package Drupal\commerce_strike\Controller
 */
class Controller extends ControllerBase{

  public function capturePayment() {
    $commerce_order_id = $_GET['order_id'];

    // Validating  Signature.
    $success = true;

    $url =  Url::fromRoute('commerce_payment.checkout.return', [
      'commerce_order' => $commerce_order_id,
      'step' => 'payment',
    ], ['absolute' => TRUE])->toString();
    return new RedirectResponse($url);

  }
}
