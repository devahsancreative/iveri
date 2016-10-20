<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Objects;

use StephenLake\Iveri\Listeners\TransactionListener;
use StephenLake\Iveri\Objects\TransactionResult;
use StephenLake\Iveri\Exceptions\TransactionValidateException;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Ramsey\Uuid\Uuid;

class Transaction {

    private $transactionIdentifier;

    private $transactionListener;
    private $transactionResult;

    private $transactionPanHolderName;
    private $transactionPanNumber;
    private $transactionPanCode;
    private $transactionPanExpiryMonth;
    private $transactionPanExpiryYear;
    private $transactionAmount;
    private $transactionReference;
    private $transactionCurrency;
    private $transactionThreeDomainServerPARES;
    private $transactionThreeDomainServerPAREQ;
    private $transactionIndex;
    private $transactionCAVV;
    private $transactionECI;
    private $transactionXID;
    private $transactionSignature;

    private $transactionType;

    private $built = FALSE;

    const TRANS_DEBIT = 'DEBIT';
    const TRANS_3DSLOOKUP = '3DSECURE_LOOKUP';
    const TRANS_3DSAUTHORIZE = '3DSECURE_AUTHORIZE';

    public function __construct(TransactionListener $transactionListener)
    {
      $this->transactionIdentifier = Uuid::uuid4()->toString();
      $this->transactionListener = $transactionListener;
    }

    public function getTransactionListener()
    {
      return $this->transactionListener;
    }

    public function setTransactionListener(TransactionListener $transactionListener)
    {
        $this->transactionListener = $transactionListener;

        return $this;
    }

    public function setTransactionResult(TransactionResult $transactionResult) {
        $this->transactionResult = $transactionResult;

        return $this;
    }

    public function isBuilt() {
      return $this->built;
    }

    private function validData() {
      $validation = new Factory(new Translator(new FileLoader(new Filesystem, ''), ''), new Container);

      $transactionTypes = implode(',', [
          self::TRANS_DEBIT,
          self::TRANS_3DSLOOKUP,
          self::TRANS_3DSAUTHORIZE
      ]);

      $entryValidator = $validation->make(get_object_vars($this), [
        'transactionType'           => "required|in:{$transactionTypes}",
      ], [
        'transactionType.required'  => 'The Transaction Type is required',
        'transactionType.in'        => "Invalid Transction Type provided. Available types: {$transactionTypes}",
      ]);

      if ($entryValidator->fails()) {
        return $entryValidator;
      }

      $validationMessages = [
        'transactionAmount.required' => "The Transaction Amount is required for {$this->transactionType} transactions",
        'transactionCurrency.required'   => "The Transaction Currency is required for {$this->transactionType} transactions",
        'transactionPanHolderName.required'   => "The PAN Holder Name is required for {$this->transactionType} transactions",
        'transactionPanNumber.required'   => "The PAN Number is required for {$this->transactionType} transactions",
        'transactionPanCode.required'   => "The PAN Security Code is required for {$this->transactionType} transactions",
        'transactionPanExpiryMonth.required'   => "The PAN Expiry Month is required for {$this->transactionType} transactions",
        'transactionPanExpiryYear.required'   => "The PAN Expiry Year is required for {$this->transactionType} transactions",
        'transactionReference.required' => "The Transaction Reference is required for {$this->transactionType} transactions",
        'transactionIndex.required' => "The Transaction Index is required for {$this->transactionType} transactions",
      ];

      switch($this->transactionType) {

        case self::TRANS_3DSLOOKUP:
            $validator = $validation->make(get_object_vars($this), [
              'transactionAmount'                 => 'required',
              'transactionCurrency'               => 'required',
              'transactionPanNumber'              => 'required',
              'transactionPanExpiryMonth'         => 'required',
              'transactionPanExpiryYear'          => 'required',
              'transactionReference'              => 'required',
            ], $validationMessages);
            break;

        case self::TRANS_DEBIT:
            $validator = $validation->make(get_object_vars($this), [
              'transactionAmount'                 => 'required',
              'transactionPanHolderName'          => 'required',
              'transactionPanNumber'              => 'required',
              'transactionPanCode'                => 'required',
              'transactionPanExpiryMonth'         => 'required',
              'transactionPanExpiryYear'          => 'required',
              'transactionCurrency'               => 'required',
              'transactionReference'              => 'required',
            ], $validationMessages);
            break;

        case self::TRANS_3DSAUTHORIZE:
            $validator = $validation->make(get_object_vars($this), [
              'transactionPanNumber'              => 'required',
              'transactionIndex'                  => 'required',
              'transactionThreeDomainServerPARES' => 'required',
            ], $validationMessages);
            break;
      }

      return $validator;
    }

    public function build() {
      $validator = $this->validData();

      if ($validator->fails()) {
        throw new TransactionValidateException("Cannot build transaction: {$validator->errors()->first()}");
      }

      $this->built = TRUE;

      switch($this->transactionType) {

        case self::TRANS_3DSLOOKUP:
            $this->transactionListener->threeDomainLookupPrepared($this);
            break;

        case self::TRANS_DEBIT:
            $this->transactionListener->debitPrepared($this);
            break;

        case self::TRANS_3DSAUTHORIZE:
            $this->transactionListener->threeDomainAuthorizePrepared($this);
            break;
      }

      return $this;
    }

    public function fails() {
      return $this->transactionResult->hasError();
    }

    public function succeeds() {
      return !$this->fails();
    }

    public function errorCode() {
      return $this->transactionResult->getErrorCode();
    }

    public function errorMessage() {
      return $this->transactionResult->getErrorMessage();
    }

    public function isThreeDomainSecured() {
      return $this->transactionResult->isThreeDomainSecured();
    }

    /**
     * Get the value of Transaction Id
     *
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionIdentifier;
    }

    /**
     * Get the value of Transaction Id
     *
     * @return mixed
     */
    public function getTransactionIdentifier()
    {
        return $this->transactionIdentifier;
    }

    /**
     * Set the value of Transaction Id
     *
     * @param mixed transactionIdentifier
     *
     * @return self
     */
    public function setTransactionIdentifier($transactionIdentifier)
    {
        $this->transactionIdentifier = $transactionIdentifier;

        return $this;
    }

    /**
     * Get the value of Transaction Pan Holder Name
     *
     * @return mixed
     */
    public function getTransactionPanHolderName()
    {
        return $this->transactionPanHolderName;
    }

    /**
     * Set the value of Transaction Pan Holder Name
     *
     * @param mixed transactionPanHolderName
     *
     * @return self
     */
    public function setTransactionPanHolderName($transactionPanHolderName)
    {
        $this->transactionPanHolderName = $transactionPanHolderName;

        return $this;
    }

    /**
     * Get the value of Transaction Pan Code
     *
     * @return mixed
     */
    public function getTransactionPanCode()
    {
        return $this->transactionPanCode;
    }

    /**
     * Set the value of Transaction Pan Code
     *
     * @param mixed transactionPanCode
     *
     * @return self
     */
    public function setTransactionPanCode($transactionPanCode)
    {
        $this->transactionPanCode = $transactionPanCode;

        return $this;
    }

    /**
     * Get the value of Transaction Pan Expiry Month
     *
     * @return mixed
     */
    public function getTransactionPanExpiryMonth()
    {
        return $this->transactionPanExpiryMonth;
    }

    /**
     * Set the value of Transaction Pan Expiry Month
     *
     * @param mixed transactionPanExpiryMonth
     *
     * @return self
     */
    public function setTransactionPanExpiryMonth($transactionPanExpiryMonth)
    {
        $this->transactionPanExpiryMonth = $transactionPanExpiryMonth;

        return $this;
    }

    /**
     * Get the value of Transaction Pan Expiry Year
     *
     * @return mixed
     */
    public function getTransactionPanExpiryYear()
    {
        return $this->transactionPanExpiryYear;
    }

    /**
     * Set the value of Transaction Pan Expiry Year
     *
     * @param mixed transactionPanExpiryYear
     *
     * @return self
     */
    public function setTransactionPanExpiryYear($transactionPanExpiryYear)
    {
        $this->transactionPanExpiryYear = $transactionPanExpiryYear;

        return $this;
    }

    /**
     * Get the value of Transaction Amount
     *
     * @return mixed
     */
    public function getTransactionAmount()
    {
        return $this->transactionAmount;
    }

    /**
     * Get the value of Transaction Amount
     *
     * @return mixed
     */
    public function getTransactionAmountInCents()
    {
        return ($this->transactionAmount*100);
    }

    /**
     * Set the value of Transaction Amount
     *
     * @param mixed transactionAmount
     *
     * @return self
     */
    public function setTransactionAmount($transactionAmount)
    {
        $this->transactionAmount = $transactionAmount;

        return $this;
    }

    /**
     * Get the value of Transaction Reference
     *
     * @return mixed
     */
    public function getTransactionReference()
    {
        return $this->transactionReference;
    }

    /**
     * Set the value of Transaction Reference
     *
     * @param mixed transactionReference
     *
     * @return self
     */
    public function setTransactionReference($transactionReference)
    {
        $this->transactionReference = $transactionReference;

        return $this;
    }

    /**
     * Get the value of Transaction Currency
     *
     * @return mixed
     */
    public function getTransactionCurrency()
    {
        return $this->transactionCurrency;
    }

    /**
     * Set the value of Transaction Currency
     *
     * @param mixed transactionCurrency
     *
     * @return self
     */
    public function setTransactionCurrency($transactionCurrency)
    {
        $this->transactionCurrency = $transactionCurrency;

        return $this;
    }

    /**
     * Get the value of Transaction Result
     *
     * @return mixed
     */
    public function getTransactionResult()
    {
        return $this->transactionResult;
    }

    /**
     * Get the value of Transaction Pan Number
     *
     * @return mixed
     */
    public function getTransactionPanNumber()
    {
        return $this->transactionPanNumber;
    }

    /**
     * Set the value of Transaction Pan Number
     *
     * @param mixed transactionPanNumber
     *
     * @return self
     */
    public function setTransactionPanNumber($transactionPanNumber)
    {
        $this->transactionPanNumber = $transactionPanNumber;

        return $this;
    }

    /**
     * Get the value of Transaction Three Domain Server
     *
     * @return mixed
     */
    public function getTransactionThreeDomainServerPARES()
    {
        return $this->transactionThreeDomainServerPARES;
    }

    /**
     * Set the value of Transaction Three Domain Server
     *
     * @param mixed transactionThreeDomainServerPARES
     *
     * @return self
     */
    public function setTransactionThreeDomainServerPARES($transactionThreeDomainServerPARES)
    {
        $this->transactionThreeDomainServerPARES = $transactionThreeDomainServerPARES;

        return $this;
    }

    /**
     * Get the value of Transaction Three Domain Server
     *
     * @return mixed
     */
    public function getTransactionThreeDomainServerPAREQ()
    {
        return $this->transactionThreeDomainServerPAREQ;
    }

    /**
     * Set the value of Transaction Three Domain Server
     *
     * @param mixed transactionThreeDomainServerPAREQ
     *
     * @return self
     */
    public function setTransactionThreeDomainServerPAREQ($transactionThreeDomainServerPAREQ)
    {
        $this->transactionThreeDomainServerPAREQ = $transactionThreeDomainServerPAREQ;

        return $this;
    }

    /**
     * Get the value of Transaction Type
     *
     * @return mixed
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * Set the value of Transaction Type
     *
     * @param mixed transactionType
     *
     * @return self
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;

        return $this;
    }

    /**
     * Get the value of Transaction Index
     *
     * @return mixed
     */
    public function getTransactionIndex()
    {
        return $this->transactionIndex;
    }

    /**
     * Set the value of Transaction Index
     *
     * @param mixed transactionIndex
     *
     * @return self
     */
    public function setTransactionIndex($transactionIndex)
    {
        $this->transactionIndex = $transactionIndex;

        return $this;
    }


    /**
     * Get the value of Transaction
     *
     * @return mixed
     */
    public function getTransactionECI()
    {
        return $this->transactionECI;
    }

    /**
     * Set the value of Transaction
     *
     * @param mixed transactionECI
     *
     * @return self
     */
    public function setTransactionECI($transactionECI)
    {
        $this->transactionECI = $transactionECI;

        return $this;
    }


    /**
     * Get the value of Transaction
     *
     * @return mixed
     */
    public function getTransactionCAVV()
    {
        return $this->transactionCAVV;
    }

    /**
     * Set the value of Transaction
     *
     * @param mixed transactionCAVV
     *
     * @return self
     */
    public function setTransactionCAVV($transactionCAVV)
    {
        $this->transactionCAVV = $transactionCAVV;

        return $this;
    }

    /**
     * Get the value of Transaction
     *
     * @return mixed
     */
    public function getTransactionXID()
    {
        return $this->transactionXID;
    }

    /**
     * Set the value of Transaction
     *
     * @param mixed transactionXID
     *
     * @return self
     */
    public function setTransactionXID($transactionXID)
    {
        $this->transactionXID = $transactionXID;

        return $this;
    }

    /**
     * Get the value of Transaction Signature
     *
     * @return mixed
     */
    public function getTransactionSignature()
    {
        return $this->transactionSignature;
    }

    /**
     * Set the value of Transaction Signature
     *
     * @param mixed transactionSignature
     *
     * @return self
     */
    public function setTransactionSignature($transactionSignature)
    {
        $this->transactionSignature = $transactionSignature;

        return $this;
    }

}
