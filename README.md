# Iveri Enterprise Integration API Package

[![Latest Stable Version](https://poser.pugx.org/stephenlake/iveri/v/stable)](https://packagist.org/packages/stephenlake/iveri) [![Latest Unstable Version](https://poser.pugx.org/stephenlake/iveri/v/unstable)](https://packagist.org/packages/stephenlake/iveri) [![Total Downloads](https://poser.pugx.org/stephenlake/iveri/downloads)](https://packagist.org/packages/stephenlake/iveri)  [![License](https://poser.pugx.org/stephenlake/iveri/license)](https://packagist.org/packages/stephenlake/iveri) 

**Iveri Enterprise** Integration API Package

- Written & Documented by Stephen Lake under the MIT License
- Copyright Stephen Lake 2016

#### Requirements
- PHP >= 5.6.4

#### Dependencies (Handled by Composer)
- guzzlehttp/guzzle ^6.2
- ramsey/uuid ^3.5
- illuminate/validation ^5.3
- illuminate/support ^5.3
- illuminate/translation ^5.3

## Usage
### Example of full 3DSecured Transaction

#### Create a new configuration instance containing your MyGate account details
```
use StephenLake\Iveri\Objects\Configuration;

$configuration = new Configuration;
$configuration->setIveriUserGroupId('<your-user-group>')
              ->setIveriUsername('<your-backoffice-username>')
              ->setIveriPassword('<your-backoffice-password>')
              ->setIveriApplicationId('<your-test-application-id>')
              ->setIveriCertificateId('<your-test-certificate-id>')
              ->setIveriMerchantId('<your-merchant-id>')
              ->setIveriApiLive(false)
              ->setIveriCmpiProcessorId(1000)
              ->setIveriCmpiPassword('<your-cmpi-password>')
              ->build();
```
**Note**: The `Configuration` will not be built and cannot be used in a `Transaction` instance until it is built. If a required parameter is not set, you will be presented with a `ConfigurationValidateException` describing the missing required parameter.

#### Create a new transaction instance with a standard transaction event listener
```
use StephenLake\Iveri\Objects\Transactions\ThreeDomainLookup;
use StephenLake\Iveri\Listeners\TransactionListener;

$ThreeDomainLookup = new ThreeDomainLookup(new TransactionListener());
$ThreeDomainLookup->setTransactionAmount('<amount-in-decimal-format'>)
                  ->setTransactionPanNumber('<pan>')
                  ->setTransactionPanExpiryMonth('<pan-expiry-month>') // MM
                  ->setTransactionPanExpiryYear('<pan-expiry-year>') // YYYY
                  ->setTransactionReference('<unique-transaction-reference>')
                  ->setTransactionCurrency('<currency-iso>') // Alpha ISO
                  ->build();
```
**Note**: The `Transaction` will not be built and cannot be used in a `MyGate` instance until it is built. If a required parameter is not set, you will be presented with a `TransactionValidateException` describing the missing required parameter.

#### Create Iveri instance and associate the configuration and transaction
```
use use StephenLake\Iveri\Iveri;

$IveriServiceAPI = new Iveri($configuration);
$IveriServiceAPI->setTransaction($ThreeDomainLookup)
                ->submitTransaction();
```
At this point, your lookup request is ready with a result.

#### Processing a result
```
if ($ThreeDomainLookup->succeeds()) {

  if($ThreeDomainLookup->isThreeDomainSecured()) {
  
    // Handle 3DSecure Process
    // See 'Handling 3DSecure'
    
  } else {
    
    // Submit Transaction Request
    // See 'Submitting Debit Transactions'
    
  }

} else {

  $errorCode    = $ThreeDomainLookup->getTransactionResult()->getErrorCode();
  $errorMessage = $ThreeDomainLookup->getTransactionResult()->getErrorMessage();
  
}
```

### Handling 3DSecure

### Extending the Transaction Listener

When constructing the `Transaction` instance, you must pass through an instance of `TransactionListener` which fires off events on certain transaction conditions. You can create your own `TransactionListener` and receive notifications of these events by can extending the default `TransactionListener` as follows.

```
class CustomTransactionListener extends TransactionListener {
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
```

Then setting the transaction to use your custom TransactionListener as follows:
```
new ThreeDomainLookup(new CustomTransactionListener);
```
or 
```
$ThreeDomainLookup->setTransactionListener(new CustomTransactionListener)
```
