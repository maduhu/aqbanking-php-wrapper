<?php
class Account
{
	private $account_number;
	private $owner;
	private $bank_code;
	private $login;
	private $pin;
	private $tan_medium;
	private $tan_mode;
	private $iban;
	private $aqbanking;
	private $balance;
	
	/**
	 * Konstruktor
	 * 
	 * @param string $bank_code_or_iban
	 * @param string $account_number
	 */
	public function __construct($bank_code_or_iban = false, $account_number = false) {
		
		$this->tan_medium = false;
		$this->aqbanking = false;
		$this->iban = false;
		$this->balance = false;
		$bank_code = false;
		
		/*
		 * wenn die ersten 2 ziffern buchstaben sind handelt es sich um eine iban und wird zerlegt
		 */
		if(ctype_alpha(substr($bank_code_or_iban.'',0,2))) {
			$this->setIBAN($bank_code_or_iban);
			
			/*
			 * auflösen in kto und blz nur bei deutschen konten
			 */
			if(substr($bank_code_or_iban.'',0,2) == 'DE') {
				$bank_code = substr($bank_code_or_iban,4,8);
				$account_number = substr($bank_code_or_iban,12,10);
			}
			
			
		}
		
		if($bank_code !== false) {
			$this->setBankCode($bank_code);
		}
		
		if($account_number !== false) {
			$this->setAccountNumber($account_number);
		}
		
		/*
		 * set IBAN if not setted
		 */
		if(!$this->iban) {
			$gen = new IBANGenerator($bank_code, $account_number);
			$this->setIBAN($gen->generate());
		}
		
		/*
		 * set tan mode to smsTAN by default
		 */
		$this->setTanModeSMS();
	}
	
	/**
	 * initiate aqbanking wrapper
	 */
	public function initAqBanking() {
		
		if(!$this->aqbanking) {
			$this->aqbanking = new AqBanking($this->bank_code, $this->login, $this->pin, $this->account_number, $this->owner, $this->tan_medium);
		}
		
	}
	
	/**
	 * überweise Geld auf ein anderes Bankkonto
	 * 
	 * @param Account $target_account
	 * @param float $amount
	 * @return boolean
	 */
	public function transfer($account,$value,$subject) {
		
		$this->initAqBanking();
		
		if($this->aqbanking->transfer($value, $account->getOwner(), $account->getAccountNumber(), $account->getBankCode(), $subject))
		{
			return true;
		}
		
		return false;
	}
	
	/*
	 * Setter Methods
	 */
	
	public function setAccountNumber($number) {
		$this->account_number = $number;
	}
	
	public function setOwner($owner) {
		$this->owner = $owner;
	}
	
	public function setBankCode($bank_code) {
		$this->bank_code = $bank_code;
	}
	
	public function setLogin($login) {
		$this->login = $login;
	}
	
	public function setPin($pin) {
		$this->pin = $pin;
	}
	
	public function setTanMode($id,$name) {
		$this->tan_mode = array(
			'id' => $id,
			'name' => $name
		);
	}
	
	public function setTanModeSMS() {
		if(!$this->tan_medium) {
			$this->setTanMedium('smsTAN');
		}
		
		$this->setTanMode(920, 'smsTAN');
	}
	
	public function setTanMedium($tan_medium) {
		$this->tan_medium = $tan_medium;
	}
	
	public function setIBAN($iban) {
		$this->iban = $iban;
	}
	
	/**
	 * get current Account Balance from Bank-Server and store in $this->balance
	 */
	public function updateBalance() {
		$this->initAqBanking();
		
		if($balance = $this->aqbanking->getBalance())
		{
			$this->balance = $balance;
			return true;
		}
		
		return false;
	}
	
	public function listTransactions($start_date = false, $end_date = false) {
		$this->initAqBanking();
		
		//	$this->aqbanking->updateTransactions();
		
		return $this->aqbanking->listTransactions($start_ts,$end_ts);
	}
	
	
	/*
	 * Getter Methods
	 */
	
	public function getAccountNumber() {
		return $this->account_number;
	}
	
	public function getBankCode() {
		return $this->bank_code;
	}
	
	public function getLogin() {
		return $this->login;
	}
	
	public function getPin() {
		return $this->pin;
	}
	
	public function getOwner() {
		return $this->owner;
	}
	
	public function getTanMedium() {
		return $this->tan_medium;
	}
	
	public function getIBAN() {
		return $this->iban;
	}
	
	public function getBalance() {
		if($this->balance === false) {
			$this->updateBalance();
		}
		
		return $this->balance;
	}
}