<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Objects\Transactions;

use StephenLake\Iveri\Listeners\TransactionListener;
use StephenLake\Iveri\Objects\Transaction;

class ThreeDomainLookup extends Transaction {

    public function __construct(TransactionListener $transactionListener) {
      parent::__construct($transactionListener);

      $this->setTransactionType(Transaction::TRANS_3DSLOOKUP);
    }
}
