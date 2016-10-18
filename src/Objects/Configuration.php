<?php

/**
 * Stephen Lake - Iveri API Wrapper Package
 *
 * @author Stephen Lake <stephen-lake@live.com>
 */

namespace StephenLake\Iveri\Objects;

use StephenLake\Iveri\Exceptions\ConfigurationValidateException;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

class Configuration {

    private $iveriGateway;
    private $iveriUserGroupId;
    private $iveriUsername;
    private $iveriPassword;
    private $iveriApplicationId;
    private $iveriCertificateId;
    private $iveriApiLive;
    private $iveriCmpiProcessorId;
    private $iveriCmpiPassword;
    private $iveriMerchantId;

    private $built = FALSE;

    private function validData() {

      $validation = new Factory(new Translator(new FileLoader(new Filesystem, ''), ''), new Container);

      return $validation->make(get_object_vars($this), [
        'iveriUserGroupId'      => 'required',
        'iveriUsername'         => 'required',
        'iveriPassword'         => 'required',
        'iveriApplicationId'    => 'required',
        'iveriCertificateId'    => 'required',
        'iveriApiLive'          => 'required',
        'iveriCmpiProcessorId'  => 'required',
        'iveriCmpiPassword'     => 'required',
        'iveriMerchantId'       => 'required',
      ], [
        'iveriUserGroupId.required'    => 'The User Group ID is required',
        'iveriUsername.required'       => 'The Username is required',
        'iveriPassword.required'       => 'The Password is required',
        'iveriApplicationId.required'  => 'The Application ID is required',
        'iveriCertificateId.required'  => 'The Certificate ID is required',
        'iveriApiLive.required'        => 'The API live boolean is required',
        'iveriCmpiProcessorId.required'=> 'The CMPI Processor ID is required',
        'iveriCmpiPassword.required'   => 'The CMPI Password is required',
        'iveriMerchantId'              => 'The Merchant ID is required',
      ]);
    }

    public function build() {
      $validator = $this->validData();

      if ($validator->fails()) {
        throw new ConfigurationValidateException("Cannot build config: {$validator->errors()->first()}");
      }

      $this->built = TRUE;

      return $this;
    }

    public function isBuilt() {
      return $this->built;
    }



    /**
     * Get the value of Stephen Lake - Iveri API Wrapper Package
     *
     * @return mixed
     */
    public function getIveriGateway()
    {
        return $this->iveriGateway;
    }

    /**
     * Set the value of Stephen Lake - Iveri API Wrapper Package
     *
     * @param mixed iveriGateway
     *
     * @return self
     */
    public function setIveriGateway($iveriGateway)
    {
        $this->iveriGateway = $iveriGateway;

        return $this;
    }

    /**
     * Get the value of Iveri User Group Id
     *
     * @return mixed
     */
    public function getIveriUserGroupId()
    {
        return $this->iveriUserGroupId;
    }

    /**
     * Set the value of Iveri User Group Id
     *
     * @param mixed iveriUserGroupId
     *
     * @return self
     */
    public function setIveriUserGroupId($iveriUserGroupId)
    {
        $this->iveriUserGroupId = $iveriUserGroupId;

        return $this;
    }

    /**
     * Get the value of Iveri Username
     *
     * @return mixed
     */
    public function getIveriUsername()
    {
        return $this->iveriUsername;
    }

    /**
     * Set the value of Iveri Username
     *
     * @param mixed iveriUsername
     *
     * @return self
     */
    public function setIveriUsername($iveriUsername)
    {
        $this->iveriUsername = $iveriUsername;

        return $this;
    }

    /**
     * Get the value of Iveri Password
     *
     * @return mixed
     */
    public function getIveriPassword()
    {
        return $this->iveriPassword;
    }

    /**
     * Set the value of Iveri Password
     *
     * @param mixed iveriPassword
     *
     * @return self
     */
    public function setIveriPassword($iveriPassword)
    {
        $this->iveriPassword = $iveriPassword;

        return $this;
    }

    /**
     * Get the value of Iveri Application Id
     *
     * @return mixed
     */
    public function getIveriApplicationId()
    {
        return $this->iveriApplicationId;
    }

    /**
     * Set the value of Iveri Application Id
     *
     * @param mixed iveriApplicationId
     *
     * @return self
     */
    public function setIveriApplicationId($iveriApplicationId)
    {
        $this->iveriApplicationId = $iveriApplicationId;

        return $this;
    }

    /**
     * Get the value of Iveri Certificate Id
     *
     * @return mixed
     */
    public function getIveriCertificateId()
    {
        return $this->iveriCertificateId;
    }

    /**
     * Set the value of Iveri Certificate Id
     *
     * @param mixed iveriCertificateId
     *
     * @return self
     */
    public function setIveriCertificateId($iveriCertificateId)
    {
        $this->iveriCertificateId = $iveriCertificateId;

        return $this;
    }

    /**
     * Get the value of Iveri Api Live
     *
     * @return mixed
     */
    public function getIveriApiLive()
    {
        return $this->iveriApiLive;
    }

    /**
     * Set the value of Iveri Api Live
     *
     * @param mixed iveriApiLive
     *
     * @return self
     */
    public function setIveriApiLive($iveriApiLive)
    {
        $this->iveriApiLive = $iveriApiLive;

        return $this;
    }

    /**
     * Get the value of Iveri Cmpi Processor Id
     *
     * @return mixed
     */
    public function getIveriCmpiProcessorId()
    {
        return $this->iveriCmpiProcessorId;
    }

    /**
     * Set the value of Iveri Cmpi Processor Id
     *
     * @param mixed iveriCmpiProcessorId
     *
     * @return self
     */
    public function setIveriCmpiProcessorId($iveriCmpiProcessorId)
    {
        $this->iveriCmpiProcessorId = $iveriCmpiProcessorId;

        return $this;
    }

    /**
     * Get the value of Iveri Cmpi Password
     *
     * @return mixed
     */
    public function getIveriCmpiPassword()
    {
        return $this->iveriCmpiPassword;
    }

    /**
     * Set the value of Iveri Cmpi Password
     *
     * @param mixed iveriCmpiPassword
     *
     * @return self
     */
    public function setIveriCmpiPassword($iveriCmpiPassword)
    {
        $this->iveriCmpiPassword = $iveriCmpiPassword;

        return $this;
    }




    /**
     * Get the value of Iveri Merchant Id
     *
     * @return mixed
     */
    public function getIveriMerchantId()
    {
        return $this->iveriMerchantId;
    }

    /**
     * Set the value of Iveri Merchant Id
     *
     * @param mixed iveriMerchantId
     *
     * @return self
     */
    public function setIveriMerchantId($iveriMerchantId)
    {
        $this->iveriMerchantId = $iveriMerchantId;

        return $this;
    }

}
