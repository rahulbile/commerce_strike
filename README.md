Bitcoin Payments - Powered by Strike

Drupal module to allow user to pay with bitcoin on your Commerce website using Strike API.
[Strike Js](https://github.com/rahulbile/strike-js) is used for generating the QR Code.

# Description

Module adds a payment gateway for Commerce enabled stores to accept payment in bitcoin, powered by strike (strike.me)
User is provided with a lightning invoice for payment via QR code.

# Installation

1. Enable the module
2. Add a payment gateway and enable it. Set the strike account API key to which payment should be received.
4. Set the preferred currency.

## NOTE REGARDING API KEY :
For now its suggested to get a API key from Strike Account Manager with following limited scope :
* partner.invoice.quote.generate
* partner.invoice.read
* partner.invoice.create
* partner.account.profile.read

In next release user will be able to authenticate via strike oAuth, generate a key and auto-populate in settings.

## Screenshots

  - Settings Form

  ![AddPaymentGateway.png](/assets/images/AddPaymentGateway.png?raw=true "Payment Gateway Settings")

  - Payment Lightning QR Code

  ![checkout.jpg](/assets/images/checkout.jpg?raw=true "Payment Checkout")
