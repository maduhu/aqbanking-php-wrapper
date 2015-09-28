<?php
class Bank_Kreissparkasse extends Bank
{
	public function __construct($bank_code)
	{
		parent::__construct($bank_code);
		
		$this->setHbciUrl('https://hbci-pintan-rl.s-hbci.de/PinTanServlet');
		$this->setSSLVersion(3);
		$this->setItanMode('3920');
	}
}