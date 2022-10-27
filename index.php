<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/**
 * https://stripe.com/docs/financial-connections/other-data-powered-products?platform=web
 */

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

$customerSession = $stripe->financialConnections->sessions->create(
  [
    'account_holder' => ['type' => 'customer', 'customer' => $customer->id],
    'permissions' => ['balances', 'ownership', 'payment_method', 'transactions'],
  ]
);

echo '<details><summary>Session (Customer)</summary><pre>', print_r($customerSession), '</pre></details>';

$accountSession = $stripe->financialConnections->sessions->create(
  [
    'account_holder' => ['type' => 'account', 'account' => $account->id],
    'permissions' => ['balances', 'ownership', 'payment_method', 'transactions'],
    'filters' => ['countries' => ['US']],
  ]
);

echo '<details><summary>Session (Account)</summary><pre>', print_r($accountSession), '</pre></details>';
?>

<p><button name="collect-financial-connections-accounts-customer">Collect Financial Connections Accounts for Customer</button></p>
<p><button name="collect-financial-connections-accounts-account">Collect Financial Connections Accounts for Account</button></p>

<p><button name="collect-bank-account-token-customer">Collect Bank Account Token for Customer</button></p>
<p><button name="collect-bank-account-token-account">Collect Bank Account Token for Account</button></p>

<p><button name="create-external-account-for-payouts" disabled>Create External Account for Payouts</button></p>

<pre><code></code></pre>

<script src="https://js.stripe.com/v3/"></script>
<script>
  /**
   * https://stripe.com/docs/js/financial_connections
   */
  const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLIC_KEY']; ?>');
  let result;

  document.querySelector('[name="collect-financial-connections-accounts-customer"]').addEventListener('click', async () => {
    result = await stripe.collectFinancialConnectionsAccounts({
      clientSecret: '<?php echo $customerSession->client_secret; ?>',
    });
    disableCollectButtons();
    disableCreateButton(!result.token);
    log(result);
  });

  document.querySelector('[name="collect-financial-connections-accounts-account"]').addEventListener('click', async () => {
    result = await stripe.collectFinancialConnectionsAccounts({
      clientSecret: '<?php echo $accountSession->client_secret; ?>',
    });
    disableCollectButtons();
    disableCreateButton(!result.token);
    log(result);
  });

  document.querySelector('[name="collect-bank-account-token-customer"]').addEventListener('click', async () => {
    result = await stripe.collectBankAccountToken({
      clientSecret: '<?php echo $customerSession->client_secret; ?>',
    });
    disableCollectButtons();
    disableCreateButton(!result.token);
    log(result);
  });

  document.querySelector('[name="collect-bank-account-token-account"]').addEventListener('click', async () => {
    result = await stripe.collectBankAccountToken({
      clientSecret: '<?php echo $accountSession->client_secret; ?>',
    });
    disableCollectButtons();
    disableCreateButton(!result.token);
    log(result);
  });

  document.querySelector('[name="create-external-account-for-payouts"]').addEventListener('click', async () => {
    // Create bank account from `result.token.id`
    const response = await fetch('/create.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        account_id: '<?php echo $account->id; ?>',
        customer_id: '<?php echo $customer->id; ?>',
        // btok_...
        external_account: result.token.id,
      }),
    });
    disableCreateButton();
    const json = await response.json();
    log(json);
  });

  function log(data) {
    console.log(data);
    document.querySelector('code').innerText = JSON.stringify(data, null, 2);
  }

  function disableCollectButtons() {
    document.querySelectorAll('[name^="collect-"]').forEach((button) => {
      button.disabled = true;
    });
  }

  function disableCreateButton(disabled = true) {
    document.querySelector('[name="create-external-account-for-payouts"]').disabled = disabled;
  }
</script>
