<?php
class StandingOrder
{
	private $period;
	private $account;
	private $remote_account;
	private $subject;
	private $value;
	private $aqbanking;
	private $start_date;
	private $end_date;
	
	public function __construct($account, $remote_account, $value) {
		
		$this->setPeriodMonthly();
		$this->setValue($value);
		
		$this->account = $account;
		$this->remote_account = $remote_account;
		
		$this->start_date = date('Ymd', strtotime("+1 month"));
		$this->end_date = false;
		
		$this->setSubject('Dauerauftrag');
		
		$this->initAqBanking();
	}
	
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	public function setValue($val) {
		$this->value = floatval($val);
	}
	
	public function setPeriodWeekly() {
		$this->period = 'weekly';
	}
	
	public function setPeriodMonthly() {
		$this->period = 'monthly';
	}
	
	/**
	 * initiate aqbanking wrapper
	 */
	public function initAqBanking() {
	
		if(!$this->aqbanking) {
			$this->aqbanking = new AqBanking($this->account->getBankCode(), $this->account->getLogin(), $this->account->getPin(), $this->account->getAccountNumber(), $this->account->getOwner(), $this->account->getTanMedium());
		}
	
	}
	
	public function exec() {
		$this->aqbanking->standingOrder($this->value, $this->remote_account->getOwner(), $this->remote_account->getAccountNumber(), $this->remote_account->getBankCode(), $this->subject, $this->period, $this->start_date, $this->end_date);
	}
}