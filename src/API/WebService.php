<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\API;

use StephenLake\Iveri\Objects\Configuration;
use StephenLake\Iveri\Objects\Transaction;
use StephenLake\Iveri\Objects\TransactionResult;
use StephenLake\Centinel\CentinelService;
use StephenLake\Centinel\Util\CurrencyCode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Exception;
use stdClass;

class WebService {

  const IVERI_ENTERPRISE_URL = 'https://portal.nedsecure.co.za/api/';

  private $config;
  private $transaction;

  private $iveriGatewayURL;

  public function __construct(Configuration $config, Transaction $transaction) {
      $this->config = $config;
      $this->transaction = $transaction;
      $this->iveriGatewayURL = is_null($this->config->getIveriGateway()) ? self::IVERI_ENTERPRISE_URL : $this->config->getIveriGateway();
  }

  public function performTransaction() {
    switch($this->transaction->getTransactionType()) {
      case Transaction::TRANS_3DSLOOKUP:
          $this->performThreeDomainLookup();
          break;

      case Transaction::TRANS_DEBIT:
          $this->performDebit();
          break;

      case Transaction::TRANS_3DSAUTHORIZE:
          $this->performThreeDomainAuthorize();
          break;
    }
  }

  private function performThreeDomainLookup() {

    $this->transaction->getTransactionListener()->threeDomainLookupInitiated($this->transaction);

    $centinel = new CentinelService();
    $centinel->setRequestProcessorId($this->config->getIveriCmpiProcessorId())
             ->setRequestMerchantId($this->config->getIveriMerchantId())
             ->setRequestTransactionPwd($this->config->getIveriCmpiPassword())
             ->setRequestTransactionType(CentinelService::TRANS_CREDIT_DEBIT_CARD)
             ->setRequestAmount($this->transaction->getTransactionAmountInCents())
             ->setRequestCurrencyCode(CurrencyCode::getNumericCode($this->transaction->getTransactionCurrency()))
             ->setRequestOrderNumber($this->transaction->getTransactionReference())
             ->setRequestCardNumber($this->transaction->getTransactionPanNumber())
             ->setRequestCardExpMonth($this->transaction->getTransactionPanExpiryMonth())
             ->setRequestCardExpYear($this->transaction->getTransactionPanExpiryYear())
             ->setRequestApiLive($this->config->getIveriApiLive())
             ->requestLookup();

     $payload = new stdClass();
     $payload->success = FALSE;
     $payload->transactionType = $this->transaction->getTransactionType();

    if ($centinel->succeeds()) {

      $sanitizedData = $centinel->getResult();

      $payload->success = TRUE;
      $payload->errorCode = isset($sanitizedData['ErrorNo']) ? $sanitizedData['ErrorNo'] : NULL;
      $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
      $payload->transactionIndex = $sanitizedData['TransactionId'];
      $payload->threeDomainEnrolled = $sanitizedData['Enrolled'];
      $payload->threeDomainECI = isset($sanitizedData['EciFlag']) ? $sanitizedData['EciFlag'] : NULL;
      $payload->threeDomainACSUrl = isset($sanitizedData['ACSUrl']) ? $sanitizedData['ACSUrl'] : NULL;
      $payload->threeDomainPAREQ = isset($sanitizedData['Payload']) ? $sanitizedData['Payload'] : NULL;
      $payload->threeDomainOrderId = isset($sanitizedData['OrderId']) ? $sanitizedData['OrderId'] : NULL;

      $this->transaction
           ->setTransactionIndex($payload->transactionIndex)
           ->setTransactionThreeDomainServerPAREQ($payload->threeDomainPAREQ);
    } else {

      $error = $centinel->getError();

      $payload->success = FALSE;
      $payload->errorCode = $error['code'];
      $payload->errorMessage = $error['desc'];
      $payload->transactionIndex = NULL;

    }

    $this->transaction->setTransactionResult(new TransactionResult($payload));

    if ($this->transaction->fails()) {
        $this->transaction->getTransactionListener()->threeDomainLookupFailed($this->transaction);
    } else {
        $this->transaction->getTransactionListener()->threeDomainLookupSucceeded($this->transaction);
    }
  }

  private function performThreeDomainAuthorize() {

    $this->transaction->getTransactionListener()->threeDomainAuthorizeInitiated($this->transaction);

    $centinel = new CentinelService();
    $centinel->setRequestProcessorId($this->config->getIveriCmpiProcessorId())
             ->setRequestMerchantId($this->config->getIveriMerchantId())
             ->setRequestTransactionPwd($this->config->getIveriCmpiPassword())
             ->setRequestTransactionType(CentinelService::TRANS_CREDIT_DEBIT_CARD)
             ->setRequestTransactionId($this->transaction->getTransactionIndex())
             ->setRequestPARES($this->transaction->getTransactionThreeDomainServerPARES())
             ->setRequestApiLive($this->config->getIveriApiLive())
             ->requestAuthenticate();

     $payload = new stdClass();
     $payload->success = FALSE;
     $payload->transactionType = $this->transaction->getTransactionType();

    if ($centinel->succeeds()) {

      $sanitizedData = $centinel->getResult();

      $payload->success = TRUE;
      $payload->errorCode = isset($sanitizedData['ErrorNo']) ? $sanitizedData['ErrorNo'] : NULL;
      $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;

      $payload->threeDomainECI = isset($sanitizedData['EciFlag']) ? $sanitizedData['EciFlag'] : NULL;
      $payload->threeDomainCAVV = isset($sanitizedData['Cavv']) ? $sanitizedData['Cavv'] : NULL;
      $payload->threeDomainXID = isset($sanitizedData['Xid']) ? $sanitizedData['Xid'] : NULL;
      $payload->threeDomainSignature = isset($sanitizedData['SignatureVerification']) ? $sanitizedData['SignatureVerification'] : NULL;
      $payload->threeDomainPARESStatus = isset($sanitizedData['PAResStatus']) ? $sanitizedData['PAResStatus'] : NULL;

      $this->transaction->setTransactionCAVV($payload->threeDomainCAVV)
                        ->setTransactionXID($payload->threeDomainXID)
                        ->setTransactionSignature($payload->threeDomainSignature);
    } else {

      $error = $centinel->getError();

      $payload->success = FALSE;
      $payload->errorCode = $error['code'];
      $payload->errorMessage = $error['desc'];
      $payload->transactionIndex = NULL;
    }

    $this->transaction->setTransactionResult(new TransactionResult($payload));

    if ($this->transaction->fails()) {
        $this->transaction->getTransactionListener()->threeDomainAuthorizeFailed($this->transaction);
    } else {
        $this->transaction->getTransactionListener()->threeDomainAuthorizeSucceeded($this->transaction);
    }

  }

  private function performDebit() {

    $this->transaction->getTransactionListener()->debitInitiated($this->transaction);

    $this->submitIveriRequest('POST', 'transactions', [
      'Content-Type' => 'application/json',
      'body' => json_encode([
          'CertificateID' => $this->config->getIveriCertificateId(),
          'Transaction' => [
              'ApplicationID' => $this->config->getIveriApplicationId(),
              'Command' => 'Debit',
              'Mode' => $this->config->getIveriApiLive() ? 'Live' : 'Test',
              'ExpiryDate' => $this->transaction->getTransactionPanExpiryMonth() . $this->transaction->getTransactionPanExpiryYear(),
              'PAN' => $this->transaction->getTransactionPanNumber(),
              'CardSecurityCode' => $this->transaction->getTransactionPanCode(),
              'Amount' => $this->transaction->getTransactionAmountInCents(),
              'Currency' => $this->transaction->getTransactionCurrency(),
              'MerchantReference' => $this->transaction->getTransactionReference(),
              'ElectronicCommerceIndicator' => $this->getEcommerceIndicatorFlag($this->transaction->getTransactionECI()),   //ECIFlag
              'CardholderName' => $this->transaction->getTransactionPanHolderName(),
              'CardHolderAuthenticationID' => $this->transaction->getTransactionXID(), //TransactionId
              'CardHolderAuthenticationData' => $this->transaction->getTransactionCAVV(),// CAVV
              //'ThreeDSecure_SignedPARes' => $this->transaction->getTransactionThreeDomainServerPARES()
          ]
      ]),
    ]);
  }

  private function submitIveriRequest($method, $url, $params = []) {

    $params = array_merge($params, [
        'Authorization' => $this->generateAuthHeader($url),
    ]);

    $payload = new stdClass();
    $payload->success = FALSE;
    $payload->transactionType = $this->transaction->getTransactionType();

    try {
      $httpClient = new Client([
          'base_uri' => $this->iveriGatewayURL,
          'verify' => false,
      ]);

      $httpRequest = new Request($method, $url, $params);
      $httpResponse = $httpClient->send($httpRequest, $params);
      $httpResult = json_decode($httpResponse->getBody()->getContents());
    }

    catch(ClientException $e) {
      $payload->success = FALSE;
      $payload->errorCode = "N0001";
      $payload->errorMessage = "Connection Error: {$e->getMessage()}";
    }
    catch(RequestException $e) {
      $payload->success = FALSE;
      $payload->errorCode = "N0001";
      $payload->errorMessage = "Connection Error: {$e->getMessage()}";
    }
    catch(Exception $e) {
      $payload->success = FALSE;
      $payload->errorCode = "N0001";
      $payload->errorMessage = "Connection Error: {$e->getMessage()}";
    }

    $payload->detail = $httpResult;

    if (!isset($httpResult->Transaction->Result)) {

      if (!isset($payload->errorCode)) {
        $payload->success = FALSE;
        $payload->errorCode = "X0002";
        $payload->errorMessage = "Response Error: Received an unexpected response from the Iveri API";
      }

    } else {

      if ($httpResult->Transaction->Result->Status != 0) {
        $payload->success = FALSE;
        $payload->errorCode = $httpResult->Transaction->Result->Code;
        $payload->errorMessage = $httpResult->Transaction->Result->Description;
      }
      else {
        $payload->success = TRUE;
        $payload->errorCode = NULL;
        $payload->errorMessage = NULL;
      }
    }

    $this->transaction->setTransactionResult(new TransactionResult($payload));

    if ($this->transaction->fails()) {
        $this->transaction->getTransactionListener()->debitFailed($this->transaction);
    } else {
        $this->transaction->getTransactionListener()->debitSucceeded($this->transaction);
    }
  }

  private function getEcommerceIndicatorFlag($eciCode) {

        $eci_flags = [
            '00' => 'SecureChannel',
            '01' => 'ThreeDSecureAttempted',
            '02' => 'ThreeDSecure',
            '05' => 'ThreeDSecure',
            '06' => 'ThreeDSecureAttempted',
            '07' => 'SecureChannel'
        ];

        return isset($eci_flags["{$eciCode}"]) ? $eci_flags["{$eciCode}"] : 'ThreeDSecureAttempted';
  }

  private function generateAuthHeader($endpoint) {

      $date = date('YmdHis500');

      $token_bytes = $this->iveriGatewayURL . $endpoint . $date . md5($this->config->getIveriPassword(), true);
      $token_hash = hash('sha256', $token_bytes, true);
      $token_base64 = base64_encode($token_hash);

      $auth = ''
              . 'Basic '
              . 'usergroup="' . $this->config->getIveriUserGroupId() . '", '
              . 'username="' . $this->config->getIveriUsername() . '", '
              . 'timestamp="' . $date . '", '
              . 'token="' . $token_base64 . '"';

    return $auth;
  }
}
