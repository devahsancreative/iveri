# Iveri Enterprise Integration API Package

**Iveri Enterprise** Integration API Package

- Written & Documented by Stephen Lake under the MIT License
- Copyright Stephen Lake 2016

#### Requirements
- PHP >= 5.6.4
- Centinel Password [Email cnpsupport@bankservafrica.com for assistance (Excellent support team)]

#### Dependencies (Handled by Composer)
- guzzlehttp/guzzle ^6.2
- ramsey/uuid ^3.5
- illuminate/validation ^5.3
- illuminate/support ^5.3
- illuminate/translation ^5.3

## Usage
### Example of full 3DSecured Transaction

#### Create a new configuration instance containing your Iveri account details
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
**Note**: The `Transaction` will not be built and cannot be used in a `Iveri` instance until it is built. If a required parameter is not set, you will be presented with a `TransactionValidateException` describing the missing required parameter.

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

**NOTE**: _This package does NOT use Iveri's 3DSecure Lookup API_, it makes use of the Centinel API. This is due to the lack of support received from Iveri, a request that I have personally pinged several times since December 2015 and still not received a response (Current Date 19 October 2016). Please see the requirements to get your credentials for 3DSecure lookups and auths.

Mastercard's product is called "SecureCode" and Visa's product is called "Verified by Visa". The 3D-Secure refers to three domains involved in the security. They are the Acquiring or Merchant's bank, the Card Association's financial networks ie Mastercard and Visa and the Issuing or Cardholder's bank.

When we perform a 3DSecure lookup on a PAN and we're notified that the PAN is 3DSecure enrolled, we receive a payload containing the `threeDomainEnrolled`, `threeDomainACSUrl` and `threeDomainPAREQ`.

In order to display the 3DSecure authorization page to the customer, we need to submit form data to the above recived `threeDomainACSUrl` which will render the 3DSecure view in an iframe.

Once the customer has completed the 3DSecure authentication, the 3DSecure process submits an `HTTP POST` the result back to an endpoint supplied by you which we describe below.

##### Create the necessary variables

**Important**
At this stage, you will need to have a saved record of the transaction as you will need to redirect the user to another endpoint which will destroy any variables you have at the current point. The preferred method is to store the transaction in a **temporary** cache which will automatically be destroyed as you generally don't want to deal with the storing of sensitive data.

```
  $threeDomainSecureACSUrl       = $ThreeDomainLookup->getTransactionResult()->getThreeDomainACSUrl();
  $threeDomainSecurePAREQ        = $ThreeDomainLookup->getTransactionResult()->getThreeDomainPAREQ();
  $threeDomainSecureID           = $ThreeDomainLookup->getTransactionIdentifier();
  $threeDomainSecureTerminateURL = 'https://your-domain-name.moc/3dsecure/terminate';
  
  
  // Cache the transaction data, because we're about to lose it and we need it later
  // Pseudocode Example:
  cache('identifer', $threeDomainSecureID)->setData([
      'panHolderName'                => $ThreeDomainLookup->getTransactionPanHolderName(),
      'panNumber'                    => $ThreeDomainLookup->getTransactionPanNumber(),
      'panSecurityCode'              => $ThreeDomainLookup->getTransactionPanSecurityCode),
      'panExpiryMonth'               => $ThreeDomainLookup->getTransactionPanExpiryMonth(),
      'panExpiryYear'                => $ThreeDomainLookup->getTransactionPanExpiryYear(),
      'transactionIndex'             => $ThreeDomainLookup->getTransactionIndex(),
      'transactionReference'         => $ThreeDomainLookup->getTransactionReference(),
      'currency'                     => $ThreeDomainLookup->getTransactionCurrency(),
      'amount'                       => $ThreeDomainLookup->getTransactionAmount(),
  ]);
```

- `threeDomainSecureACSUrl` is the 3DSecure URL which the form must `POST` to. This was received when performing the 3DSecure lookup on the PAN.

- `threeDomainSecurePAREQ` is the request token received when performing the 3DSecure lookup on the PAN and must be submitted as part of the form.

- `threeDomainSecureTerminateURL` is the URL which the 3DSecure process will submit an `HTTP POST` containing the 3DSecure result. Make sure you own this endpoint and that it accepts POST.

- `threeDomainSecureID` should be a unique identifier defined by you that will be returned from the 3DSecure process after the customer has completed the authentication and will be received via an `HTTP POST` to your given `threeDomainSecureTerminateURL`.

##### Creating the View

Create the HTML IFrame which will hold the 3DSecure form received after submitting the POST to the `threeDomainSecureACSUrl`:
```
<iframe 
  id="3dsecure_iframe" 
  name="3dsecure_iframe" 
  marginwidth="0" 
  marginheight="0" 
  hspace="0" 
  vspace="0" 
  frameborder="0" 
  scrolling="no" 
  frameBorder="0" 
  width="420px" 
  height="700px"
>
</iframe>
```

Create the HTML Form which will submit the `POST` to the `threeDomainSecureACSUrl` and initiatlize the 3DSecure form into the IFrame.

For clarity sake, this example makes use of a templating engine to render our variables in the view, in your code you may use plain PHP syntax such as `<?php echo $exampleVar ?>` or whatever framework you may be using.

```
<form method="POST" action="{{ $threeDomainSecureACSUrl }}" target="3dsecure_iframe">

    <input type="hidden" name="PaReq" value="{{ $threeDomainSecurePAREQ }}">
    
    <input type="hidden" name="TermUrl" value="{{ $threeDomainSecureTerminateURL }}"> />
    <input type="hidden" name="MD" value="{{ $threeDomainSecureID }}" />
   
    <br/>
    
    <button type="submit">PROCEED TO 3DSECURE</button>
</form>
```

##### Completing the 3DSecure Transaction

Regardless of whether or not the 3DSecure process fails, the payload will be submitted to the `threeDomainSecureTerminateURL` given. On this endpoint, your code must handle the received response which will contain 2 important fields:

- `MD`: The unique identifier provided by you to fetch the saved transaction and complete it.
- `PaRes`: The **P**ayment **A**uthorization **Res**ponse that will be used to authorize the transaction.

At this point, an `HTTP POST` has been made your URL (`threeDomainSecureTerminateURL`) and your Iveri API, Configuration and Transaction instances have been lost, so we need to rebuild using the `MD` which is a unique identifier for our transaction.

Your `threeDomainSecureTerminateURL` should contain something like the following (Using plain PHP):

```   
  use StephenLake\Iveri\Iveri;
  use StephenLake\Iveri\Objects\Configuration;
  use StephenLake\Iveri\Objects\Transactions\ThreeDomainAuthorise;
  use StephenLake\Iveri\Objects\Transactions\Debit;
  use StephenLake\Iveri\Listeners\TransactionListener;

  // Fetch the data that was posted to this endpoint
  $transactionIdentifier  = $_POST['MD'];
  $threeDomainSecurePARES = $_POST['PaRes'];
    
  // Fetched your cached transaction data - Pseudocode Example:
  // Depending on how you stored your lost transaction data, fetch it.
  $cachedTransactionData = cache->getWhere('identifier', '=', $transactionIdentifier);
  
  // Create new transaction instance for 3DS_AUTHORIZE
  $ThreeDomainAuthorise = new ThreeDomainAuthorise(new TransactionListener());
  $ThreeDomainAuthorise->setTransactionThreeDomainServerPARES(Input::get('PaRes'))
                       ->setTransactionIndex($transactionIdentifier)
                       ->setTransactionAmount($cachedTransactionData['amount'])
                       ->setTransactionPanNumber($cachedTransactionData['pan'])
                       ->setTransactionPanExpiryMonth($cachedTransactionData['panExpiryMonth']) 
                       ->setTransactionPanExpiryYear($cachedTransactionData['panExpiryYear']) 
                       ->setTransactionReference($cachedTransactionData['transactionReference'])
                       ->setTransactionCurrency($cachedTransactionData['currency'])
                       ->build();
   
  // Submit the 3DS_AUTHORIZE request
  $IveriServiceAPI->setTransaction($ThreeDomainLookup)
                  ->submitTransaction();
         
  if ($ThreeDomainAuthorise->fails()) {

      // Something went wrong with the 3DSecure Authorization
      // Cannot continue with transaction
      
      $errorCode    = $ThreeDomainLookup->getTransactionResult()->getErrorCode();
      $errorMessage = $ThreeDomainLookup->getTransactionResult()->getErrorMessage();

  } else {

     // Perform the transction settlement
     $Debit = new Debit(new TransactionListener);
     $Debit->setTransactionPanHolderName($cachedTransactionData['panHolderName'])
           ->setTransactionReference($cachedTransactionData['transactionReference'])
           ->setTransactionPanCode($cachedTransactionData['panSecurityCode'])
           ->setTransactionCurrency($cachedTransactionData['currency'])
           ->setTransactionAmount($cachedTransactionData['amount'])                   
           ->setTransactionPanNumber($cachedTransactionData['panNumber'])            
           ->setTransactionPanExpiryMonth($cachedTransactionData['panExpiryMonth'])
           ->setTransactionPanExpiryYear($cachedTransactionData['panExpiryYear'])
           ->setTransactionIndex($cachedTransactionData['transactionIndex'])  
           ->build();

     $IveriServiceAPI->setTransaction($Debit)
                     ->submitTransaction();

   if ($Debit->succeeds()) {
   
      // The payment succeeded
      $paymentDetail = $Debit->getTransactionResult()->getTransactionDetail();
      
   } else {
   
      // Something went wrong with the settlement
      // handle errors as you like
      
      $errorCode    = $ThreeDomainLookup->getTransactionResult()->getErrorCode();
      $errorMessage = $ThreeDomainLookup->getTransactionResult()->getErrorMessage();
      
   }
   
  }
   
```

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
