<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Objects;

use StephenLake\Iveri\Objects\TransactionResultCodes;
use StephenLake\Iveri\Objects\Transaction;
use stdClass;

class TransactionResult {

    private $errorCode = NULL;
    private $errorMessage = NULL;
    private $payload;

    public function __construct($payload) {

      $this->payload = $payload;

      if (!$this->payload->success) {
        $this->payload->errorMessage = TransactionResultCodes::getMessage($this->payload->errorCode, $this->payload->errorMessage);
      }
    }

    public function hasError() {
      return !is_null($this->payload->errorCode);
    }

    public function getErrorCode() {
      return $this->payload->errorCode;
    }

    public function getErrorMessage() {
      return $this->payload->errorMessage;
    }

    public function getThreeDomainACSUrl() {
      return $this->payload->threeDomainACSUrl;
    }

    public function getThreeDomainECI() {
      return $this->payload->threeDomainECI;
    }

    public function getThreeDomainPAREQ() {
      return $this->payload->threeDomainPAREQ;
    }

    public function isThreeDomainSecured() {
      return $this->payload->threeDomainEnrolled == 'Y';
    }

    public function getThreeDomainPARESStatus() {
      return $this->payload->threeDomainPARESStatus;
    }

    public function getThreeDomainSignature() {
      return $this->payload->threeDomainSignatureVerification;
    }

    public function getThreeDomainXID() {
      return $this->payload->threeDomainXID;
    }

    public function getThreeDomainCAVV() {
      return $this->payload->threeDomainCAVV;
    }

    public function getThreeDomainOrderId() {
      return $this->payload->threeDomainOrderId;
    }

    public function getPayload() {
      return $this->payload;
    }

}
