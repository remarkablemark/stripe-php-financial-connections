<?php

/**
 * https://stripe.com/docs/financial-connections/other-data-powered-products?platform=web
 */

require 'vendor/autoload.php';

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
$stripe = new \Stripe\StripeClient($_ENV['STRIPE_SECRET_KEY']);

$customer = $stripe->customers->create([
  'email' => $_ENV['CUSTOMER_EMAIL'],
  'name' => $_ENV['CUSTOMER_NAME'],
]);

echo '<details><summary>Customer</summary><pre>', print_r($customer), '</pre></details>';

$account = $stripe->accounts->create([
  'country' => 'US',
  'type' => 'custom',
  'capabilities' => [
    'card_payments' => ['requested' => true],
    'transfers' => ['requested' => true],
  ],
]);

echo '<details><summary>Account</summary><pre>', print_r($account), '</pre></details>';

$session = $stripe->financialConnections->sessions->create(
  [
    'account_holder' => ['type' => 'customer', 'customer' => $customer->id],
    'permissions' => ['balances', 'ownership', 'payment_method', 'transactions'],
  ]
);

echo '<details><summary>Session</summary><pre>', print_r($session), '</pre></details>';
?>

<p><button>Financial Connections</button></p>

<script src="https://js.stripe.com/v3/"></script>
<script>
  const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLIC_KEY']; ?>');

  async function collect() {
    const result = await stripe.collectFinancialConnectionsAccounts({
      clientSecret: '<?php echo $session->client_secret; ?>',
    });
    console.log(result);
  }

  document.querySelector('button').addEventListener('click', collect);
</script>
