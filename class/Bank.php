<?php

class Bank
{
	public $bank_code;
	public $hbci_url;
	public $ssl_version;
	public $hbci_version;
	public $itanmode;
	
	public function __construct($bank_code)
	{
		$this->bank_code = $bank_code;
		$this->ssl_version = false;
		$this->hbci_version = false;
		$this->itanmode = false;
	}
	
	public function getHbciUrl()
	{
		return $this->hbci_url;
	}
	
	public function getHbciVersion()
	{
		return $this->hbci_version;
	}
	
	public function setHbciUrl($url)
	{
		$this->hbci_url = $url;
	}
	
	public function setHbciVersion($version_number)
	{
		$this->hbci_version = $version_number;
	}
	
	public function setSSLVersion($version_number)
	{
		$this->ssl_version = $version_number;
	}
	
	public function setItanMode($mode)
	{
		$this->itanmode = $mode;
	}
	
	public function getSSLVersion()
	{
		return $this->ssl_version;
	}
	
	public function getItanMode()
	{
		return $this->itanmode;
	}
	
	public function post_addUser()
	{
		
	}
	
	public function genPinFile($user_id,$bank_code,$pin)
	{
		return 
'# This is a PIN file to be used with AqBanking
# Please insert the PINs/passwords for the users below

# User "'.$user_id.'" at "'.$bank_code.'"
PIN_'.$bank_code.'_'.$user_id.' = "'.$pin.'"';
	}
	
}