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
use stdClass;

class WebService {

  const IVERI_ENTERPRISE_URL = 'https://portal.nedsecure.co.za/api/';

  const CENTINEL_URL_LIVE = 'https://msgnedcor.bankserv.co.za/maps/txns.asp';
  const CENTINEL_URL_TEST = 'https://msgtest.bankserv.co.za/maps/txns.asp';

  // Centinel
  // Live
  // https://msgnedcor.bankserv.co.za/maps/txns.asp // VISA & MasterCard
  // https://msgnedcor.bankserv.co.za/maps/amex.asp  // American Express
  // Testing
  // https://msgtest.bankserv.co.za/maps/txns.asp // VISA & MasterCard
  // https://msgtest.bankserv.co.za/maps/amex.asp // American Express

  private $config;
  private $transaction;

  public function __construct(Configuration $config, Transaction $transaction) {
      $this->config = $config;
      $this->transaction = $transaction;
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

      $soapURL = self::MYGATE_THREE_DOMAIN_URL_LIVE;

      if (!$this->config->getIveriApiLive()) {
          $soapURL = self::MYGATE_THREE_DOMAIN_URL_TEST;
      }

      $soapResponse = NULL;

      $payload = new stdClass();
      $payload->success = FALSE;
      $payload->transactionType = $this->transaction->getTransactionType();

      try {
        $this->transaction->getTransactionListener()->threeDomainLookupInitiated($this->transaction);

        $soapClient = new SoapClient($soapURL);
        $soapResponse = $soapClient->__soapCall('lookup', [
          'MerchantID'      => $this->config->getIveriCustomerId(),
          'ApplicationID'   => $this->config->getIveriApplicationId(),
          'Mode'            => intval($this->config->getIveriApiLive()),
          'PAN'             => $this->transaction->getTransactionPanNumber(),
          'PANExpr'         => $this->transaction->getTransactionPanExpiry(),
          'PurchaseAmount'  => $this->transaction->getTransactionAmount()
        ],[
            "exceptions" => true,
        ]);
      }
      catch (SoapFault $e) {
        $payload->errorCode = 'X000';
        $payload->errorMessage = 'SOAP API Connection Fault. An internal network error has occurred, please try again later.';
        $payload->transactionIndex = NULL;
      }

      if (is_soap_fault($soapResponse) || !is_array($soapResponse)) {
          $payload->errorCode = 'X000';
          $payload->errorMessage = NULL;
      } else {
        $sanitizedData = $this->prettifyDisgustingResponse($soapResponse);

        if (!isset($sanitizedData['Result'])) {
            $payload->errorCode = 'X001';
            $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
            $payload->transactionIndex = NULL;
        } else {
          $payload->success = ($sanitizedData['Result'] > -1);
          $payload->errorCode = isset($sanitizedData['ErrorNo']) ? $sanitizedData['ErrorNo'] : NULL;
          $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
          $payload->transactionIndex = $sanitizedData['TransactionIndex'];
          $payload->threeDomainEnrolled = $sanitizedData['Enrolled'];
          $payload->threeDomainECI = isset($sanitizedData['ECI']) ? $sanitizedData['ECI'] : NULL;
          $payload->threeDomainACSUrl = isset($sanitizedData['ACSUrl']) ? $sanitizedData['ACSUrl'] : NULL;
          $payload->threeDomainPAREQ = isset($sanitizedData['PAReqMsg']) ? $sanitizedData['PAReqMsg'] : NULL;

          $this->transaction
               ->setTransactionIndex($payload->transactionIndex)
               ->setTransactionThreeDomainServerPAREQ($payload->threeDomainPAREQ);
        }
      }

      $this->transaction->setTransactionResult(new TransactionResult($payload));

      if ($this->transaction->fails()) {
          $this->transaction->getTransactionListener()->threeDomainLookupFailed($this->transaction);
      } else {
          $this->transaction->getTransactionListener()->threeDomainLookupSucceeded($this->transaction);
      }
  }

  private function performThreeDomainAuthorize() {

    $soapURL = self::MYGATE_THREE_DOMAIN_URL_LIVE;

    if (!$this->config->getIveriApiLive()) {
        $soapURL = self::MYGATE_THREE_DOMAIN_URL_TEST;
    }

    $soapResponse = NULL;

    $payload = new stdClass();
    $payload->success = FALSE;
    $payload->transactionType = $this->transaction->getTransactionType();

    try {
      $this->transaction->getTransactionListener()->threeDomainAuthorizeInitiated($this->transaction);

      $soapClient = new SoapClient($soapURL);
      $soapResponse = $soapClient->__soapCall('authenticate', [
        'TransactionIndex'  => $this->transaction->getTransactionIndex(),
        'PAResPayload'      => $this->transaction->getTransactionThreeDomainServerPARES(),
      ],[
          "exceptions" => true,
      ]);
    }
    catch (SoapFault $e) {
      $payload->errorCode = 'X000';
      $payload->errorMessage = 'SOAP API Connection Fault. An internal network error has occurred, please try again later.';
    }

    if (is_soap_fault($soapResponse) || !is_array($soapResponse)) {
        $payload->errorCode = 'X000';
        $payload->errorMessage = NULL;
    } else {
      $sanitizedData = $this->prettifyDisgustingResponse($soapResponse);

      if (!isset($sanitizedData['Result'])) {
          $payload->errorCode = 'X001';
          $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
          $payload->transactionIndex = NULL;
      } else {
        $payload->success = ($sanitizedData['Result'] == 0);
        $payload->errorCode = isset($sanitizedData['ErrorNo']) ? $sanitizedData['ErrorNo'] : NULL;
        $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
        $payload->threeDomainPARESStatus = isset($sanitizedData['PAResStatus']) ? $sanitizedData['PAResStatus'] : NULL;
        $payload->threeDomainSignatureVerification = isset($sanitizedData['SignatureVerification']) ? $sanitizedData['SignatureVerification'] : NULL;
        $payload->threeDomainXID = isset($sanitizedData['XID']) ? $sanitizedData['XID'] : NULL;
        $payload->threeDomainCAVV = isset($sanitizedData['Cavv']) ? $sanitizedData['Cavv'] : NULL;
        $payload->threeDomainECI = isset($sanitizedData['ECI']) ? $sanitizedData['ECI'] : NULL;
      }
    }

    $this->transaction->setTransactionResult(new TransactionResult($payload));

    if ($this->transaction->fails()) {
        $this->transaction->getTransactionListener()->threeDomainAuthorizeFailed($this->transaction);
    } else {
        $this->transaction->getTransactionListener()->threeDomainAuthorizeSucceeded($this->transaction);
    }

  }

  private function performDebit() {

    $soapURL = self::MYGATE_ENTERPRISE_URL_LIVE;

    if (!$this->config->getIveriApiLive()) {
        $soapURL = self::MYGATE_ENTERPRISE_URL_TEST;
    }

    $soapResponse = NULL;

    $payload = new stdClass();
    $payload->success = FALSE;
    $payload->transactionType = $this->transaction->getTransactionType();

    try {
      $this->transaction->getTransactionListener()->debitInitiated($this->transaction);

      $soapClient = new SoapClient($soapURL);
      $soapResponse = $soapClient->__soapCall('fProcessAndSettle', [
        'GatewayID'           => $this->config->getIveriGatewayId(),
        'MerchantID'          => $this->config->getIveriCustomerId(),
        'ApplicationID'       => $this->config->getIveriApplicationId(),
        'TransactionIndex'    => $this->transaction->getTransactionIndex(),
        'Terminal'            => $this->config->getIveriTerminalId(),
        'Mode'                => intval($this->config->getIveriApiLive()),
        'MerchantReference'   => $this->transaction->getTransactionMerchantReference(),
        'Amount'              => $this->transaction->getTransactionAmount(),
        'Currency'            => $this->transaction->getTransactionCurrency(),
        'CashBackAmount'      => '',
        'CardType'            => $this->getCardTypeForLazyIveri($this->transaction->getTransactionPanNumber()),
        'AccountType'         => '',
        'CardNumber'          => $this->transaction->getTransactionPanNumber(),
        'CardHolder'          => $this->transaction->getTransactionPanHolderName(),
        'CVVNumber'           => $this->transaction->getTransactionPanCode(),
        'ExpiryMonth'         => $this->transaction->getTransactionPanExpiryMonth(),
        'ExpiryYear'          => $this->transaction->getTransactionPanExpiryYear(),
        'Budget'              => '',
        'BudgetPeriod'        => '',
        'AuthorisationNumber' => '',
        'PIN'                 => '',
        'DebugMode'           => '',
        'eCommerceIndicator'  => '',
        'verifiedByVisaXID'   => '',
        'verifiedByVisaCAFF'  => '',
        'secureCodeUCAF'      => '',
        'UCI'                 => '',
        'IPAddress'           => '',
        'ShippingCountryCode' => '',
        'PurchaseItemsID'     => '',
      ],[
          "exceptions"        => true,
      ]);
    }
    catch (SoapFault $e) {
      $payload->errorCode = 'X000';
      $payload->errorMessage = 'SOAP API Connection Fault. An internal network error has occurred, please try again later.';
    }

    if (is_soap_fault($soapResponse) || !is_array($soapResponse)) {
        $payload->errorCode = 'X000';
        $payload->errorMessage = NULL;
    } else {
      $sanitizedData = $this->prettifyDisgustingResponse($soapResponse);

      if (!isset($sanitizedData['Result'])) {
          $payload->errorCode = 'X001';
          $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) :
                                   isset($sanitizedData['FSPMessage']) ? str_replace('&apos;', '', $sanitizedData['FSPMessage']) : NULL;

          $payload->transactionIndex = NULL;
      } else {
          $payload->success = ($sanitizedData['Result'] == 0);
          $payload->errorCode = isset($sanitizedData['ErrorNo']) ? $sanitizedData['ErrorNo'] : NULL;
          $payload->errorMessage = isset($sanitizedData['ErrorDesc']) ? str_replace('&apos;', '', $sanitizedData['ErrorDesc']) : NULL;
          $payload->transactionAuthorisationID = isset($sanitizedData['AuthorisationID']) ? $sanitizedData['AuthorisationID'] : NULL;
          $payload->threeDomainECI = isset($sanitizedData['ECI']) ? $sanitizedData['ECI'] : NULL;
          $payload->transactionIndex = isset($sanitizedData['TransactionIndex']) ? $sanitizedData['TransactionIndex'] : NULL;

          $this->transaction
               ->setTransactionAuthorisationID($payload->transactionAuthorisationID)
               ->setTransactionIndex($payload->transactionIndex);
      }
    }

    $this->transaction->setTransactionResult(new TransactionResult($payload));

    if ($this->transaction->fails()) {
        $this->transaction->getTransactionListener()->debitFailed($this->transaction);
    } else {
        $this->transaction->getTransactionListener()->debitSucceeded($this->transaction);
    }
  }

  private function prettifyDisgustingResponse($array_items)
  {
      $pretty_response = [];

      foreach ($array_items as $item) {
          if (strlen($item) && strpos($item, '||')) {
              $parts = explode('||', $item);
              $key = trim($parts[0]);
              $value = trim($parts[1]);
              $pretty_response[(string) $key] = $value;
          }
      }

      return $pretty_response;
  }

  private function getCardTypeForLazyIveri($card_number) {

    $firstOne = substr($card_number, 0, 1);
    $firstTwo = substr($card_number, 0, 2);
    $firstThree = substr($card_number, 0, 3);
    $firstFour = substr($card_number, 0, 4);
    $firstSix = substr($card_number, 0, 6);

    if ($firstOne == '4') {
        return 4; // visa
    }
    if ($firstTwo >= '51' && $firstTwo <= '55') {
        return 3; //mastercard
    }
    if ($firstTwo == '34' || $firstTwo == '37') {
        return 1; // American Express
    }
    if ($firstTwo == '36') {
        return 5; // Diners Club International
    }
    if ($firstFour == '2014' || $firstFour == '2149') {
        return 5; // Diners Club International
    }
    if ($firstThree >= '300' && $firstThree <= '305') {
        return 5; // Diners Club International
    }
    if (($firstFour == '6011') || ($firstSix >= '622126' && $firstSix <= '622925') || ($firstThree >= '644' && $firstThree <= '649') || ($firstTwo == '65')) {
        return 2; // Discover
    }

    return 4;
  }

}
