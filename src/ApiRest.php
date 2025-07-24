<?php

/**
 * API REST de PAYCOMET para PHP.
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

namespace Jtesouro\KlarnaCheckout;

class ApiRest
{
    private $apiKey;

    private $endpoint_url;

    private const TEST_URL = 'https://api.playground.klarna.com/';
    private const PROD_URL = 'https://api.klarna.com/';

    public function __construct($apiKey, $enviroment)
    {
        $this->apiKey = $apiKey;
        switch ($enviroment) {
            case 'test':
                $this->endpoint_url = self::TEST_URL;
                # code...
                break;

            default:
                $this->endpoint_url = self::PROD_URL;
                # code...
                break;
        }
    }

    public function createKCOOrder(
        $purchase_country,
        $purchase_currency,
        $locale,
        $order_amount,
        $order_tax_amount,
        $order_lines,
        $terms_url,
        $checkout_url,
        $confirmation_url,
        $callback_url,
        $order_id
    ) {
        $params = [
            "purchase_country" => (string) $purchase_country,
            "purchase_currency" => (string) $purchase_currency,
            "locale" => (string) $locale,
            "order_amount" => (string) $order_amount,
            "order_tax_amount" => (int) $order_tax_amount,
            "order_lines" => (array) $order_lines,
            "merchant_urls" => (array) array(
                "terms" => $terms_url,
                "checkout" => $checkout_url,
                "confirmation" => $confirmation_url,
                "push" => $callback_url
            ),
            "merchant_reference1" => (string) $order_id
        ];

        return $this->executeRequest('/payments/v1/sessions', $params);
    }

    public function createPaymentSession(
        $purchase_country,
        $purchase_currency,
        $locale,
        $order_amount,
        $order_tax_amount,
        $order_lines,
        $confirmation_url,
        $callback_url,
        $order_id
    ) {
        $params = [
            "purchase_country" => (string) $purchase_country,
            "purchase_currency" => (string) $purchase_currency,
            "locale" => (string) $locale,
            "order_amount" => (string) $order_amount,
            "order_tax_amount" => (int) $order_tax_amount,
            "order_lines" => (array) $order_lines,
            "merchant_urls" => (array) array(
                "confirmation" => $confirmation_url,
                "push" => $callback_url
            ),
            "merchant_reference1" => (string) $order_id,
            "intent" => "buy"
        ];

        return $this->executeRequest('/payments/v1/sessions', $params);
    }

    public function createHPPSession(
        $session_id,
        $error_url,
        $confirmation_url,
        $order_id,
        $push_url
    ) {
        $params = [
            "merchant_urls" => (array) array(
                "back" => $error_url,
                "cancel" => $error_url,
                "error" => $error_url,
                "failure" => $error_url,
                "success" => $confirmation_url . '/?token={{authorization_token}}&numOrder=' . $order_id,
                "status_update" => $push_url . '?hppSessionId={{session_id}}numOrder=' . $order_id
            ),
            "payment_session_url" => $this->endpoint_url . '/payments/v1/sessions/' . $session_id,
        ];

        return $this->executeRequest('/hpp/v1/sessions', $params);
    }

    private function executeRequest($endpoint, $params)
    {
        $jsonParams = json_encode($params);

        $curl = curl_init();

        $url = $this->endpoint_url . $endpoint;

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $jsonParams,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic $this->apiKey",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return json_decode($response);
    }
}