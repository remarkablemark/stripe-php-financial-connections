<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Stripe\StripeClient;

// Set your secret key. Remember to switch to your live secret key in production.
// See your keys here: https://dashboard.stripe.com/apikeys
$stripe = new StripeClient($_ENV['STRIPE_SECRET_KEY']);

header('Content-Type: application/json');

try {
    // retrieve JSON from POST body
    $jsonStr = file_get_contents('php://input');
    $jsonObj = json_decode($jsonStr);

    // Create an external account that you can use for payouts
    // https://stripe.com/docs/connect/payouts-bank-accounts?bank-account-collection-integration=direct-api#create-an-external-payouts-account
    $account = $stripe->accounts->update(
      // acct_...
      $jsonObj->account_id || $jsonObj->customer_id,
      // btok_...
      ['external_account' => $jsonObj->external_account]
    );

    echo json_encode(['data' => $account]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
