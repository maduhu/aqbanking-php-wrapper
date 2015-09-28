<?php
class Bank_Kreissparkasse extends Bank
{
	public function __construct($bank_code)
	{
		parent::__construct($bank_code);
		
		$this->setHbciUrl('https://hbci-pintan.gad.de/cgi-bin/hbciservlet');
		//$this->setSSLVersion(3);
	}
}