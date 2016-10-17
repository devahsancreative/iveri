<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri;

use StephenLake\Iveri\Objects\Configuration;
use StephenLake\Iveri\Objects\Transaction;
use StephenLake\Iveri\Exceptions\IveriValidateException;
use StephenLake\Iveri\API\WebService;

class Iveri {

  private $config;
  private $transaction;

  public function __construct(Configuration $config) {
    if (!$config->isBuilt()) {
      throw new IveriValidateException("Cannot use unbuilt configuration: use build() to validate and construct the config");
    }

    $this->config = $config;
  }

  public function setConfiguration(Configuration $config) {
    if (!$config->isBuilt()) {
      throw new IveriValidateException("Cannot use unbuilt configuration: use build() to validate and construct the config");
    }

    $this->config = $config;
    return $this;
  }

  public function setTransaction(Transaction $transaction) {
    if (!$transaction->isBuilt()) {
      throw new IveriValidateException("Cannot use unbuilt transaction: use build() to validate and construct the transaction");
    }

    $this->transaction = $transaction;
    return $this;
  }

  public function getConfiguration() {
    return $this->config;
  }

  public function getTransaction() {
    return $this->transaction;
  }

  public function submitTransaction() {
    if (is_null($this->transaction) || !$this->transaction->isBuilt()) {
      throw new IveriValidateException("Cannot submit transaction - No transaction has been built");
    }
    if (is_null($this->config) || !$this->config->isBuilt()) {
      throw new IveriValidateException("Cannot submit transaction - No configuration has been built");
    }

    $webService = new WebService($this->config, $this->transaction);
    $webService->performTransaction();
  }

}
