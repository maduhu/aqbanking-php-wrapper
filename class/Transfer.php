<?php
class Transfer {
	
	private $account;
	private $remote_account;
	private $subject;
	private $value;
	private $aqbanking;
	
	public function __construct($account, $remote_account, $value) {
		
		$this->account = $account;
		$this->remote_account = $remote_account;
		$this->setSubject('Geld fuer Dich');
		$this->setValue($value);
		
		$this->initAqBanking();
	}
	
	/**
	 * Überweisung ausführen
	 * 
	 * @return boolean 
	 */
	public function exec() {
		
		if($this->aqbanking->transfer($this->value, $this->remote_account->getOwner(), $this->remote_account->getAccountNumber(), $this->remote_account->getBankCode(), $this->subject))
		{
			return true;
		}
		
		return false;
	}
	
	/*
	 * Setter
	 */
	
	public function setValue($val) {
		$this->value = floatval($val);
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	/**
	 * initiate aqbanking wrapper
	 */
	public function initAqBanking() {
	
		if(!$this->aqbanking) {
			$this->aqbanking = new AqBanking($this->account->getBankCode(), $this->account->getLogin(), $this->account->getPin(), $this->account->getAccountNumber(), $this->account->getOwner(), $this->account->getTanMedium());
		}
	
	}
	
}