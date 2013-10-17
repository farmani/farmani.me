<?php
/**
 * CTBrainTree.php in CastroBackend.
 * User: Ramin Farmani ramin.farmani@gmail.com
 * Date: 7/31/13
 * Time: 1:23 PM
 */

class CTBrainTree extends CApplicationComponent
{
	public $environment;
	public $merchantId;
	public $publicKey;
	public $privateKey;

	public function init()
	{
		Braintree_Configuration::environment($this->environment);
		Braintree_Configuration::merchantId($this->merchantId);
		Braintree_Configuration::publicKey($this->publicKey);
		Braintree_Configuration::privateKey($this->privateKey);
	}

	public function sale($amount,$creditCard)
	{
		$response = Braintree_Transaction::sale(
			array(
				'amount' => $amount,
				'creditCard' => array(
					'number' => $creditCard['number'],
					'cvv' => $creditCard['cvv'],
					'expirationMonth' => $creditCard['month'],
					'expirationYear' => $creditCard['year'],
					'options' => array(
						'verifyCard' => true
					)
				),
				'options' => array(
					'submitForSettlement' => true
				)
			)
		);

		$result = array();

		if ($response->success) {
			$result['code'] = 200;
			$result['message'] = 'Success';
			$result['transaction_id'] = $response->transaction->id;
		} else if ($response->transaction) {
			$result['code'] = $response->transaction->processorResponseCode;
			$result['message'] = $response->message;
			$result['transaction_id'] = '';
		} else {
			$verification = $response->creditCardVerification;

			if($verification->processorResponseCode>=2000) {
				$result['code'] = 401;
				$result['message'] = $verification->processorResponseText;
				Yii::log('Credit Card Verification Failed' . $verification->processorResponseText, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.60');
				return $result;
			}
			$msg='';
			foreach (($response->errors->deepAll()) as $error) {
				$msg .= $error->message;
			}
			$result['code'] = 400;
			$result['message'] = $msg;
			$result['transaction_id'] = '';
			foreach($response->errors->forKey('transaction')->forKey('creditCard')->shallowAll() AS $error) {
				$result['errors'][$error->attribute] = $error->message;
			}
		}

		return $result;
	}

	public function verify($amount,$creditCard)
	{
		$response = Braintree_Transaction::sale(
			array(
				'amount' => $amount,
				'creditCard' => array(
					'number' => $creditCard['number'],
					'cvv' => $creditCard['cvv'],
					'expirationMonth' => $creditCard['month'],
					'expirationYear' => $creditCard['year'],
					'options' => array(
						'verifyCard' => true
					)
				),
				'options' => array(
					'submitForSettlement' => false
				)
			)
		);

		$result = array();

		if ($response->success) {
			$result['code'] = 200;
			$result['message'] = 'Success';
			$result['transaction_id'] = $response->transaction->id;
		} else if ($response->transaction) {
			$result['code'] = $response->transaction->processorResponseCode;
			$result['message'] = $response->message;
			$result['transaction_id'] = '';
		} else {
			$verification = $response->creditCardVerification;

			if($verification->processorResponseCode>=2000) {
				$result['code'] = 401;
				$result['message'] = $verification->processorResponseText;
				Yii::log('Credit Card Verification Failed' . $verification->processorResponseText, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.158');
				return $result;
			}
			$msg='';
			foreach (($response->errors->deepAll()) as $error) {
				$msg .= $error->message;
			}
			$result['code'] = 400;
			$result['message'] = $msg;
			$result['transaction_id'] = '';
			foreach($response->errors->forKey('transaction')->forKey('creditCard')->shallowAll() AS $error) {
				$result['errors'][$error->attribute] = $error->message;
			}
		}

		return $result;
	}

	public function create($creditCard,$firstName,$lastName,$email,$address=null)
	{
		$response = Braintree_Customer::create(array(
			'firstName' => $firstName,
			'lastName' => $lastName,
			'email' => $email,
			'creditCard' => array(
				'number' => $creditCard['number'],
				'cvv' => $creditCard['cvv'],
				'expirationMonth' => $creditCard['month'],
				'expirationYear' => $creditCard['year'],
				'cardholderName' => $creditCard['holder_name'],
				'billingAddress' => array(
					'firstName' => $firstName,
					'lastName' => $lastName,
					'streetAddress' => (empty($address['address']))?'':$address['address'],
					'locality' => (empty($address['city']))?'':$address['city'],
					'region' => (empty($address['state']))?'':$address['state'],
					'postalCode' => (empty($address['postal_code']))?'':$address['postal_code'],
					'countryCodeAlpha2' => (empty($address['country']))?'':$address['country']
				),
				'options' => array(
					'verifyCard' => true
				)
			)
		));


		$result = array();

		if ($response->success) {
			$result['code'] = 200;
			$result['message'] = 'Success';
			$result['customer_id'] = $response->customer->id;
		} else {
			$verification = $response->creditCardVerification;

			if($verification->processorResponseCode>=2000) {
				$result['code'] = 401;
				$result['message'] = $verification->processorResponseText;
				Yii::log('Credit Card Verification Failed' . $verification->processorResponseText, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.158');
				return $result;
			}

			$msg='';
			foreach (($response->errors->deepAll()) as $error) {
				$msg .= $error->message;
			}
			Yii::log('Credit Card Customer Creation Failed' . $msg, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.168');
			$result['code'] = 400;
			$result['message'] = $msg;
			$result['customer_id'] = '';

			foreach($response->errors->forKey('customer')->forKey('creditCard')->shallowAll() AS $error) {
				$result['errors'][$error->attribute] = $error->message;
			}

			foreach($response->errors->forKey('customer')->forKey('creditCard')->forKey('billingAddress')->shallowAll() AS $error) {
				$result['errors'][$error->attribute] = $error->message;
			}
		}
		return $result;
	}

	public function subscribe($customerId,$plan){

		$result['code'] = 400;
		$result['subscription_id'] = 0;
		$result['subscription_status'] = 'failed';
		$result['customer_id'] = $customerId;
		if(!in_array($plan,Yii::app()->params['membership.plans']))
			throw new CException(Yii::t('CTBrainTree.Subscribe', 'Selected plan is not valid.'));
		try {
			$customer = Braintree_Customer::find($customerId);
			$paymentMethodToken = $customer->creditCards[0]->token;

			$response = Braintree_Subscription::create(array(
					'paymentMethodToken' => $paymentMethodToken,
					'planId' => $plan
				));

			if ($response->success) {
				$result['code'] = 200;
				$result['message'] = 'Success';
				$result['subscription_id'] = $response->subscription->id;
				$result['subscription_status'] = $response->subscription->status;
				$result['customer_id'] = $customerId;
			} else {
				$msg='';
				foreach (($response->errors->deepAll()) as $error) {
					$msg .= $error->message;
				}
				$result['message'] = $msg;
				Yii::log('Credit Card Subscription Failed' . $msg, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.198');
				foreach($response->errors->forKey('subscription')->forKey('base')->shallowAll() AS $error) {
					$result['errors'][$error->attribute] = $error->message;
				}
			}
		} catch (Braintree_Exception_NotFound $e) {
			Yii::log('Failure: no customer found with ID' . $customerId, CLogger::LEVEL_ERROR, 'CTBrainTree.Create.line.204');
		}
		return $result;
	}
}