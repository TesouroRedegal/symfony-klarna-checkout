<?php

namespace Jtesouro\KlarnaCheckout;

use Exception;
use SoapClient;
use SoapFault;
use stdClass;

/**
 * API de PAYCOMET para PHP. Métodos BankStore IFRAME/FULLSCREEN/XML/JET
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    PAYCOMET
 * @copyright  (c) 2020, PAYCOMET
 * @link       https://www.paycomet.com
 */

class Client
{


	private string $api_key;
	private string $purchase_country;
	private string $purchase_currency;
	private string $locale;
	private string $terms_url;
	private string $checkout_url;
	private string $confirmation_url;
	private string $push_url;
	private string $error_url;

	private string $enviroment;

	public function __construct($api_key, $purchase_country, $purchase_currency, $locale, $terms_url, $checkout_url, $confirmation_url, $push_url, $error_url, $enviroment)
	{
		$this->api_key = $api_key;
		$this->purchase_country = $purchase_country;
		$this->purchase_currency = $purchase_currency;
		$this->locale = $locale;
		$this->terms_url = $terms_url;
		$this->checkout_url = $checkout_url;
		$this->confirmation_url = $confirmation_url;
		$this->push_url = $push_url;
		$this->error_url = $error_url;

		$this->enviroment = $enviroment;
	}

	public function createKCOOrder($order_id, $amount)
	{
		$order_lines = $this->createMockOrderLines($amount);

		try {
			$api = new ApiRest($this->api_key, $this->enviroment);

			$response = $api->createKCOOrder(
				$this->purchase_country,
				$this->purchase_currency,
				$this->locale,
				$amount,
				$amount - ($amount * 10000 / (10000 + 1000)),
				$order_lines,
				$this->terms_url,
				$this->checkout_url,
				$this->confirmation_url,
				$this->push_url,
				$order_id
			);

		} catch (Exception $e) {
			return $this->SendResponse();
		}

		return $this->SendResponse($response);
	}

	public function createPaymentSession($order_id, $amount)
	{
		$order_lines = $this->createMockOrderLines($amount);

		try {
			$api = new ApiRest($this->api_key, $this->enviroment);

			$response = $api->createPaymentSession(
				$this->purchase_country,
				$this->purchase_currency,
				$this->locale,
				$amount,
				$amount - ($amount * 10000 / (10000 + 1000)),
				$order_lines,
				$this->confirmation_url,
				$this->push_url,
				$order_id
			);

			$session_id = $response->session_id;

			$response = $api->createHPPSession(
				$session_id,
				$this->error_url,
				$this->confirmation_url,
				$order_id
			);

		} catch (Exception $e) {
			return $this->SendResponse();
		}

		return $this->SendResponse($response);
	}

	private function createMockOrderLines($amount)
	{
		return array(
			array(
				"type" => "physical",
				"reference" => "19-402-USA",
				"name" => "Red T-Shirt",
				"quantity" => 1,
				"quantity_unit" => "pcs",
				"unit_price" => $amount,
				"tax_rate" => 1000,
				"total_amount" => $amount,
				"total_discount_amount" => 0,
				"total_tax_amount" => $amount - ($amount * 10000 / (10000 + 1000))
			)
		);
	}

	// /**
	// * Crea una respuesta del servicio Klarna en objeto
	// * @param array $respuesta Array de la respuesta a ser convertida a objeto
	// * @return object Objeto de respuesta. Se incluye el valor RESULT (OK para correcto y KO incorrecto)
	// * @version 1.0 2016-06-03
	// */
	private function SendResponse($rawResponse = false)
	{
		$result = new stdClass();
		if (is_array($rawResponse)) {
			$result->RESULT = "KO";
			$result->DS_ERROR_ID = 1011; // No se pudo conectar con el host
		} else {
			$result = (object) $rawResponse;
			if (!empty($rawResponse->DS_ERROR_ID) && $rawResponse->DS_ERROR_ID != 0) {
				$result->RESULT = "KO";
			} else {
				$result->RESULT = "OK";
			}
		}

		return $result;
	}
}