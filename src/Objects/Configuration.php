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

    private $built = FALSE;

    private function validData() {

      $validation = new Factory(new Translator(new FileLoader(new Filesystem, ''), ''), new Container);

      return $validation->make(get_object_vars($this), [
        'iveriGateway'       => 'required',
        'iveriUserGroupId'   => 'required',
        'iveriUsername'      => 'required',
        'iveriPassword'      => 'required',
        'iveriApplicationId' => 'required',
        'iveriCertificateId' => 'required',
        'iveriApiLive'       => 'required',
      ], [
        'iveriGateway.required'        => 'The Gateway is required',
        'iveriUserGroupId.required'    => 'The User Group ID is required',
        'iveriUsername.required'       => 'The Username is required',
        'iveriPassword.required'       => 'The Password is required',
        'iveriApplicationId.required'  => 'The Application ID is required',
        'iveriCertificateId.required'  => 'The Certificate ID is required',
        'iveriApiLive.required'        => 'The API live boolean is required',
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

}
