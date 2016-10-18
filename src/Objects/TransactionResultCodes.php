<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Objects;

class TransactionResultCodes {

  public static function getMessage($errorCode, $fallbackMessage = NULL) {

    if (!isset(self::$errorCodeMapping[$errorCode])) {
      if (!is_null($fallbackMessage)) {
        return $fallbackMessage;
      }

      return self::$errorCodeMapping['X001'];
    }

    return self::$errorCodeMapping[$errorCode];
	}

  private static $errorCodeMapping = [

    // Package/Custom Defined Responses
    'X000' => 'There was an error communicating with the payment gateway. Please try again in a minute.',
    'X001' => 'An unexpected internal network error has occurred.',

  ];

}
