<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Listeners;

use StephenLake\Iveri\Interfaces\TransactionListenerInterface;
use StephenLake\Iveri\Objects\Transaction;

class TransactionListener implements TransactionListenerInterface {

  public function threeDomainLookupPrepared(Transaction $transaction){}

  public function threeDomainLookupInitiated(Transaction $transaction){}

  public function threeDomainLookupFailed(Transaction $transaction){}

  public function threeDomainLookupSucceeded(Transaction $transaction){}

  public function threeDomainAuthorizePrepared(Transaction $transaction){}

  public function threeDomainAuthorizeInitiated(Transaction $transaction){}

  public function threeDomainAuthorizeFailed(Transaction $transaction){}

  public function threeDomainAuthorizeSucceeded(Transaction $transaction){}

  public function debitPrepared(Transaction $transaction){}

  public function debitInitiated(Transaction $transaction){}

  public function debitFailed(Transaction $transaction){}

  public function debitSucceeded(Transaction $transaction){}

}
