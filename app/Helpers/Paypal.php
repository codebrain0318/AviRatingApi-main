<?php
namespace App\Helpers;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
// use PayPal\Api\Details;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;

class Paypal {

	protected $apiContext;

	public function __construct()
	{
        $clientId = config('paypal.sandbox_client_id');
        $secret = config('paypal.sandbox_secret');

        if(config('paypal.settings.mode') == 'live') {
            $clientId = config('paypal.live_client_id');
            $secret = config('paypal.live_secret');
        }

		$this->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential($clientId, $secret)
        );
        $this->apiContext->setConfig(config('paypal.settings'));
	}

	/*
		options = [
			'intent',
			'process-url',
			'cancel-url',
			'payment-desc'
		]
	 */
	public function definePayment($amount_, $currency, $options)
    {
    	if(!array_key_exists('intent', $options)){
    		$options['intent'] = 'sale';
    	}

    	if(!array_key_exists('process-url', $options)){
    		$options['process-url'] = 'process';
    	}
    	$options['process-url'] = url($options['process-url']);

    	if(!array_key_exists('cancel-url', $options)){
    		$options['cancel-url'] = 'cancel';
    	}
    	$options['cancel-url'] = url($options['cancel-url']);

    	if(!array_key_exists('payment-desc', $options)){
    		$options['payment-desc'] = 'Total Amount';
    	}

    	if(!array_key_exists('payment-method', $options)){
    		$options['payment-method'] = 'paypal';
    	}

        // Create new payer and method
        $payer = new Payer();
        $payer->setPaymentMethod($options['payment-method']);

        // Set redirect URLs
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($options['process-url'])
          ->setCancelUrl($options['cancel-url']);

        // Set payment amount
        $amount = new Amount();
        $amount->setCurrency($currency)
        ->setTotal($amount_);

        $item = new Item();                            
		$item->setQuantity(1);                  
		$item->setName($options['payment-desc']);
		$item->setPrice($amount_);
		$item->setCurrency($currency);

		$itemList = new ItemList();                    
		$itemList->setItems(array($item)); 

        // Set transaction object
        $transaction = new Transaction();
        $transaction->setAmount($amount);
        $transaction->setItemList($itemList); 

        // Create the full payment object
        $payment = new Payment();
        $payment->setIntent($options['intent'])
        ->setPayer($payer)
        ->setRedirectUrls($redirectUrls)
        ->setTransactions(array($transaction));

        // Create payment with valid API context
        try {

          	$payment->create($this->apiContext);

          	// Get PayPal redirect URL and redirect the customer
          	return $payment->getApprovalLink();

          // Redirect the customer to $approvalUrl
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
          	dd($ex->getCode(), $ex->getData());
          	return false;
        } catch (Exception $ex) {
        	dd($ex);
          	return false;
        }
    }

    public function processPayment($paymentId, $payerId)
    {
        // Get payment object by passing paymentId
        $payment = Payment::get($paymentId, $this->apiContext);

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            // Execute payment
            $result = $payment->execute($execution, $this->apiContext);
            return $result;
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
			echo $ex->getCode();
			echo $ex->getData();
			return false;
        } catch (Exception $ex) {
          	return false;
        }

        return false;
    }
}