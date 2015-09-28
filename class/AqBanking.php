<?php
require 'vendor/autoload.php';

use AdamBrett\ShellWrapper\Runners\Exec;
use AdamBrett\ShellWrapper\Command\Builder as CommandBuilder;
use AdamBrett\ShellWrapper\Runners\AdamBrett\ShellWrapper\Runners;
use AdamBrett\ShellWrapper\Command\AdamBrett\ShellWrapper\Command;
use AdamBrett\ShellWrapper\Runners\ShellExec;


class AqBanking {
	
	private $config_dir;
	private $bank_code;
	private $banking_login;
	private $banking_pin;
	private $account_number;
	private $tanmedium_id;
	private $bank_user_name;
	private $locale;
	private $iban;
	
	private $bank;
	
	private $is;
	
	private $shell;
	
	private $aqcli;
	
	/**
	 * 
	 * Simpler Konstruktor
	 * benötigt alle Daten zum Online Banking Account
	 * 
	 * @param String $bank_code (Bankleitzahl)
	 * @param String $banking_login (Benutzerkennung)
	 * @param String $banking_pin (Pin)
	 * @param String $account_number (Kontonummer)
	 * @param string $bank_user_name (Name des Kontoinhabers)
	 * @param string $tanmedium_id (Tan-Medium ID z.B. Bezeichnung der Mobilnummer bei Sparkasse)
	 * @param string $locale (Länderkennung)
	 * 
	 */
	public function __construct($bank_code, $banking_login, $banking_pin, $account_number, $bank_user_name = false, $tanmedium_id = false, $locale = 'DE') {
		
		$this->shell = new Exec();
		
		$this->is = '';
		$this->config_dir = DIR_AQCONFIG . $bank_code . '/' . $banking_login;
		
		if(!DEBUG) {
			$this->is = ' --noninteractive';
		}
		
		$this->locale = $locale;
		
		if(($this->bank = $this->findBank($bank_code)) === false) {
			error('Bank ' . $bank_code . ' not available');
		}
		
		$this->bank_code = $bank_code;
		$this->banking_login = $banking_login;
		$this->banking_pin = $banking_pin;
		$this->bank_user_name = $bank_user_name;
		
		if($account_number === false) {
			$account_number = $banking_login;
		}
		
		$this->account_number = $account_number;
		
		$this->aqcli = 'aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'"';
		
		if(!$this->checkConfigDir()) {
			$this->makePinFile();
			$this->decrypt_keyfile();
			
			$this->addUser();
			
			$this->checklogin();
			Db::init($this->config_dir);
			Db::createTables();
			
			if($tanmedium_id !== false) {
				$this->setTanmediumId($tanmedium_id);
			}
			
			//echo "\n\n".$tanmedium_id."\n\n";
			
			
			$this->getItanModes();
			$this->getAccSepa();
		}
		else if($tanmedium_id !== false) {
			$this->makePinFile();
			$this->decrypt_keyfile();
			
			$this->checklogin();
			Db::init($this->config_dir);
			$this->setTanmediumId($tanmedium_id);
		}
		
		$this->generateIBAN();
	}
	
	public function __destruct() {
		debug('destruct');
		$this->encrypt_keyfile();
	}
	
	/**
	 * 
	 * Verbinde zu Bank
	 * hole Aktuelle Umsätze als ctx Datei
	 * lege ctx Datei lokal ab
	 * konvertiere ctx Datei in CSV Datei
	 * 
	 */
	public function updateTransactions() {
	
		/*
		 * GET .ctx file from bank
		 */
		$command = new CommandBuilder('aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'" request');
		
		$command
			->addFlag('b',$this->bank_code)
			->addFlag('a',$this->account_number)
			->addArgument('transactions')
			->addFlag('c',$this->config_dir . '/transactions-'.$this->account_number.'.ctx');
		
		$this->shell->run($command);
			
		
		/*
		 * Convert ctx File to csv
		 */
		$command = new CommandBuilder('aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'" listtrans --exporter=csv --profile=full' );
	
		$command
			->addFlag('b',$this->bank_code)
			->addFlag('a',$this->account_number)
			->addFlag('c',$this->config_dir . '/transactions-'.$this->account_number.'.ctx')
			->addFlag('o',$this->config_dir . '/transactions-'.$this->account_number.'.csv');
	
		$this->shell->run($command);
	}
	
	/**
	 * Gib Aktuelle Umsätze als Array zurück
	 */
	
	public function listTransactions($start_ts,$end_ts) {
		
		/*
		 * Wenn keine csv Datei existiert hole erst aktuelle Umsätze vom Bank-Server
		 */
		if(!file_exists($this->config_dir . '/transactions-'.$this->account_number.'.csv')) {
			$this->updateTransactions();
		}
		
		$row = 1;
		
		$out = array();
		if (($handle = fopen($this->config_dir . '/transactions-'.$this->account_number.'.csv', 'r')) !== false) {
			while (($data = fgetcsv($handle, 1000, ';')) !== false) {

				if($row > 1) {
					
					$localCountry = $data[0];
					$localBankCode = $data[1];
					$localBranchId = $data[2];
					$localAccountNumber = $data[3];
					$localSuffix = $data[4];
					$localIban = $data[5];
					$localName = $data[6];
					$localBic = $data[7];
					$remoteCountry = $data[8];
					$remoteBankName = $data[9];
					$remoteBankLocation = $data[10];
					$remoteBankCode = $data[11];
					$remoteBranchId = $data[12];
					$remoteAccountNumber = $data[13];
					$remoteSuffix = $data[14];
					$remoteIban = $data[15];
					$remoteName = $data[16];
					$remoteName1 = $data[17];
					$remoteBic = $data[18];
					$uniqueId = $data[19];
					$idForApplication = $data[20];
					$groupId = $data[21];
					$valutaDate = $this->aqDateHelper($data[22]);
					$date = $this->aqDateHelper($data[23]);
					$value_value = $this->aqfloatHelper($data[24]);
					$value_orig = $data[24];
					$value_currency = $data[25];
					$fees_value = $data[26];
					$fees_currency = $data[27];
					$textKey = $data[28];
					$textKeyExt = $data[29];
					$transactionKey = $data[30];
					$customerReference = $data[31];
					$bankReference = $data[32];
					$transactionCode = $data[33];
					$transactionText = $data[34];
					$primanota = $data[35];
					$fiId = $data[36];
					$purpose = $this->combineHelper($data,37,48);
					$category = $this->combineHelper($data,49,56);
					$period = $data[57];
					$cycle = $data[58];
					$executionDay = $data[59];
					$firstExecutionDate = $data[60];
					$lastExecutionDate = $data[61];
					$nextExecutionDate = $data[62];
					$type = $data[63];
					$subType = $data[64];
					$status = $data[65];
					$charge = $data[66];
					$remoteAddrStreet = $data[67];
					$remoteAddrZipcode = $data[68];
					$remoteAddrCity = $data[69];
					$remotePhone = $data[70];
					$unitId = $data[71];
					$unitIdNameSpace = $data[72];
					$units_value = $data[73];
					$units_currency = $data[74];
					$unitprice_value = $data[75];
					$unitprice_currency = $data[76];
					$commission_value = $data[77];
					$commission_currency = $data[78];
					$bankAccountId = $data[79];
					$groupId2 = $data[80];
					$creditorSchemeId = $data[81];
					$mandateId = $data[82];
					$mandateDate_dateString = $data[83];
					$mandateDebitorName = $data[84];
					$sequenceType = $data[85];
					$originalCreditorSchemeId = $data[86];
					$originalMandateId = $data[87];
					$originalCreditorName = $data[88];
						
					debug('insert');
					Db::addTransaction($localCountry, $localBankCode, $localBranchId, $localAccountNumber, $localSuffix, $localIban, $localName, $localBic, $remoteCountry, $remoteBankName, $remoteBankLocation, $remoteBankCode, $remoteBranchId, $remoteAccountNumber, $remoteSuffix, $remoteIban, $remoteName, $remoteName1, $remoteBic, $uniqueId, $idForApplication, $groupId, $valutaDate, $date, $value_value, $value_orig, $value_currency, $fees_value, $fees_currency, $textKey, $textKeyExt, $transactionKey, $customerReference, $bankReference, $transactionCode, $transactionText, $primanota, $fiId, $purpose, $category, $period, $cycle, $executionDay, $firstExecutionDate, $lastExecutionDate, $nextExecutionDate, $type, $subType, $status, $charge, $remoteAddrStreet, $remoteAddrZipcode, $remoteAddrCity, $remotePhone, $unitId, $unitIdNameSpace, $units_value, $units_currency, $unitprice_value, $unitprice_currency, $commission_value, $commission_currency, $bankAccountId, $groupId2, $creditorSchemeId, $mandateId, $mandateDate_dateString, $mandateDebitorName, $sequenceType, $originalCreditorSchemeId, $originalMandateId, $originalCreditorName);
				}
				$row++;
			}
			fclose($handle);	

			return Db::listTransactions($start_ts,$end_ts);
		}
	}
	
	public function aqDateHelper($date) {
		return str_replace('/','-',$date);
	}
	
	public function aqfloatHelper($val) {
		$parts = explode('/',$val);
		if(count($parts) > 1) {
			return floatval((int)$parts[0]/(int)$parts[1]);	
		}
		
		return floatval($val);
	}
	
	public function combineHelper($array,$from_index, $to_index) {
		$out = '';
		for ($i=$from_index;$i<=$to_index;$i++) {
			if(!empty($array[$i])) {
				$out .= ';' . str_replace(';',':',$array[$i]);
			}
		}
		
		return substr($out,1);
	}
	
	/**
	 * Verbinde zu Bank und hole den Aktuellen Kontostand als CTX Datei
	 * lege CTX Datei lokal ab
	 * parse ctx Datei und gebe Kontostand zurück
	 * 
	 * @return float
	 */
	public function getBalance() {
		
		$balance_file = $this->config_dir.'/balance-'.$this->account_number.'.ctx';
		
		$command = new CommandBuilder($this->aqcli);
		
		$command->addSubCommand('request')
			->addArgument('balance')
			->addFlag('b', $this->bank_code)
			->addFlag('a', $this->account_number)
			->addFlag('c', $balance_file);
		
		$this->shell->run($command);
		
		$command = new CommandBuilder($this->aqcli);
		$command->addSubCommand('listbal')
			->addFlag('b', $this->bank_code)
			->addFlag('a', $this->account_number)
			->addFlag('c', $balance_file);

		$this->shell->run($command);
		
		$out = $this->shell->getOutput();
		
		$fields = array();
		
		foreach ($out as $line) {
			if(strpos($line, $this->account_number) !== false) {
				$line = explode("\t",$line);
				$fields = $line;
				break;
			}
		}
		
		if(count($fields) > 10) {
			for ($i=0;$i<count($fields);$i++) {
				if($fields[$i] == 'EUR') {
					return floatval(trim($fields[$i-1]));
				}
			}
		}
		
		return false;
		//$this->shell->run(new CommandBuilder('aqbanking-cli'.$this->is.' listbal -a '.$this->account_number.' -b '.$this->bank_code.' -c "'.$this->config_dir.'/balance-'.$this->account_number.'.ctx" | awk \'{print $8 " " $9 ""} {print $6 " " $7 ""}\''));
		
	}
	
	/**
	 * Generiere aus Bankleitzahl und Kontonummer die IBAN
	 */
	public function generateIBAN() {
		$IBANGenerator = new IBANGenerator($this->bank_code, $this->account_number, $this->locale);
		$this->iban = $IBANGenerator->generate();
	}
	
	/**
	 * Finde passende Bank Klasse nach Bankleitzahl
	 * existiert noch keine Klasse zur angegebenen BLZ gib false zurück
	 * 
	 * @param Integer $bank_code
	 * @return Bank Object|false
	 */
	private function findBank($bank_code) {
		$banks = array(
			'37050299' => 'Bank_Kreissparkasse'
		);
		
		if(isset($banks[$bank_code])) {
			$bank = $banks[$bank_code];
			
			require_once DIR_CLASS . '/banks/' . $bank . '.php';
			
			return new $bank($bank_code);
		}
		
		return false;
	}
	
	/**
	 * Überprüfe ob bereits ein Konfigurationsordner für den Banking Nutzer existiert
	 * Existiert er nciht lege die nötigen Ordner an
	 * 
	 * Gibt true zurück wenn Konfigurationsordner existiert
	 * sonst false
	 * 
	 * @return boolean
	 */
	public function checkConfigDir() {		
		if(!is_dir(DIR_AQCONFIG . $this->bank_code)) {
			mkdir(DIR_AQCONFIG . $this->bank_code);
			chmod(DIR_AQCONFIG . $this->bank_code, 0777);
		}
		
		if(!is_dir(DIR_AQCONFIG . $this->bank_code . '/' . $this->banking_login)) {
			mkdir(DIR_AQCONFIG . $this->bank_code . '/' . $this->banking_login);
			chmod(DIR_AQCONFIG . $this->bank_code . '/' . $this->banking_login, 0777);
			
			mkdir(DIR_AQCONFIG . $this->bank_code . '/' . $this->banking_login . '/transfer');
			chmod(DIR_AQCONFIG . $this->bank_code . '/' . $this->banking_login . '/transfer', 0777);
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * Erstellt alle nötigen Konfigurationsdateien im Konfigurationsverzeichnis
	 */
	private function addUser() {
		
		$command = new CommandBuilder('aqhbci-tool4'.$this->is.' -C \''. $this->config_dir . '/config' .'\' adduser');
		
		if($this->bank_user_name !== false) {
			$command->addFlag('N',$this->bank_user_name);
		}
		
		$command
			->addFlag('b', $this->bank_code)
			->addFlag('u', $this->banking_login)
			->addFlag('s', $this->bank->getHbciUrl())
			->addFlag('t', 'pintan');

		debug('adduser');
		$this->shell->run($command);
		
		debug('keepMultipleBlanks');
		$this->shell->run(new CommandBuilder('aqhbci-tool4'.$this->is.' -C \''. $this->config_dir . '/config' .'\' adduserflags -f keepMultipleBlanks'));
		
		if($this->bank->getSSLVersion() === 3) {
			debug('force ssl3');
			$this->shell->run(new CommandBuilder('aqhbci-tool4'.$this->is.' -C \''. $this->config_dir . '/config' .'\' adduserflags -f forceSsl3'));
		}
		
		//$this->bank->post_addUser($this);
		//$this->makePinFile();
		
		// aqhbci-tool4 getsysid -b BLZ -c Benutzerkennung
		
		
	}
	
	/**
	 * Bei manchen Banken wird eine Tan Medium ID benötigt 
	 * z.B. bei Sparkassen die Bezeichnung der Handynummer an die smsTAN gesendet wird.
	 * 
	 * @param String $tanmedium_id
	 */
	private function setTanmediumId($tanmedium_id) {
		$this->tanmedium_id = $tanmedium_id;
		
		$command = new CommandBuilder('aqhbci-tool4'.$this->is.' -C \''. $this->config_dir . '/config\'');
		
		$command
			->addSubCommand('setTanMediumId --tanmediumid="'.$tanmedium_id.'"')
			->addFlag('c', $this->banking_login)
			//->addFlag('u',$this->account_number)
			->addFlag('b',$this->bank_code);
		
		debug('setTanMediumId');
		
		echo "\n" . $command->__toString() . "\n\n";
		
		$this->shell->run($command);
		
	}
	
	/**
	 * Nötige Abfrage bei manchen Banken -> welche TAN Methoden stehen zur Verfügung
	 */
	private function getItanModes() {
		debug('getitanmodes');
		
		$this->shell->run(new CommandBuilder('aqhbci-tool4'.$this->is.' -P "'.$this->config_dir . '/pinfile" -C \''. $this->config_dir . '/config' .'\' getitanmodes -b '.$this->bank_code.' -c ' . $this->banking_login));
	}
	
	/**
	 * Nötige Abfrage um auch mit SEPA Daten (IBAN etc.) arbeiten zu können
	 */
	private function getAccSepa() {
		debug('getaccsepa');
		
		$this->shell->run(new CommandBuilder('aqhbci-tool4'.$this->is.' -P "'.$this->config_dir . '/pinfile" -C \''. $this->config_dir . '/config' .'\' getaccsepa -b '.$this->bank_code.' -a ' . $this->account_number));
	}
	
	/**
	 * 
	 */
	private function decrypt_keyfile() {
		debug('decrtypt keyfile');
		$content = $this->decrypt(file_get_contents($this->config_dir . '/pinfile.crypt'));
		
		file_put_contents($this->config_dir . '/pinfile', $content);
	}
	
	private function encrypt_keyfile() {
		debug('encrypt keyfile');
		//file_put_contents($this->config_dir . '/pinfile','000000000000000000000000000000000000000000000');
		//unlink($this->config_dir . '/pinfile');
	}
	
	/**
	 * Erstellt Schlüsseldatei um PIN eingabe beim onlinebanking zu vermeiden
	 */
	private function makePinFile() {
		debug('make pin file');
		/*
		$shell = new Exec();
		$shell->run(new CommandBuilder('aqhbci-tool4 -C \''. $this->config_dir . '/config' .'\' mkpinlist > \''. $this->config_dir . '/pinfile\' '));
		
		$pinfile = file_get_contents($this->config_dir . '/pinfile');
		
		echo $pinfile;die();
		*/
		
		$content = $this->bank->genPinFile($this->banking_login, $this->bank_code, $this->banking_pin);
		
		$content = $this->encrypt($content);
		
		file_put_contents($this->config_dir . '/pinfile.crypt', $content);
	}
	
	private function encrypt($data_plain) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, CRYPT_KEY, $data_plain, MCRYPT_MODE_ECB, $iv);
	}
	
	private function decrypt($data_encrypted) {
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, CRYPT_KEY, $data_encrypted, MCRYPT_MODE_ECB, $iv);
	}
	
	/**
	 * Erste benötigte Verbindung zum Bank-Server
	 * holt alle nötigen Informationen und legt sie ins Konfigurations-Verzeichnis
	 * 
	 */
	public function checklogin() {
		
		debug('checklogin');
		
		/*
		 * get sys_id
		 */
		$command = new CommandBuilder('aqhbci-tool4' . $this->is . " --acceptvalidcerts -P '". $this->config_dir . "/pinfile' -C '". $this->config_dir . "/config'");
		$command->addSubCommand('getsysid')
			->addFlag('b',$this->bank_code)
			->addFlag('c',$this->banking_login);
		
		$this->shell->run($command);
		
		/*
		 * get accounts
		 */
		$command = new CommandBuilder('aqhbci-tool4' . $this->is . " --acceptvalidcerts -P '". $this->config_dir . "/pinfile' -C '". $this->config_dir . "/config'");
		$command->addSubCommand('getaccounts');
		
		$this->shell->run($command);
		
		/*
		 * set itanmode
		 */
		$mode = $this->bank->getItanMode();
		if($mode !== false)
		{
			$command = new CommandBuilder('aqhbci-tool4' . $this->is . " --acceptvalidcerts -P '". $this->config_dir . "/pinfile' -C '". $this->config_dir . "/config'");
			$command->addSubCommand('setitanmode')
			->addFlag('m', $mode);
			
			$this->shell->run($command);
		}
		
		
	}
	
	public function transfer($value, $name, $account_number, $bank_code, $subject) {
		
		debug('transfer ' . $name);
		//$this->setTanmediumId($this->tanmedium_id);
		
		$transfer_name = date('Y-m-d_H-i') . '_' . $bank_code . '-' . $account_number;
		
		$name = str_replace('"','',$name);
		
		$value = number_format(floatval($value), 2, ',','').':EUR';
		
		$command = new CommandBuilder('aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'" transfer --rbank="' . $bank_code .'" --raccount="'.$account_number.'" --rname="'.$name.'"' );
		
		$command
			->addFlag('c', $this->config_dir . '/transfer/'.$transfer_name.'.ctx')
			->addFlag('v', $value)
			->addFlag('p', $subject)
			->addFlag('a',$this->account_number)
			->addFlag('b',$this->bank_code);
		
		$this->shell->run($command);
		
		return true;
	}
	
	public function standingOrder($value, $name, $account_number, $bank_code, $subject, $period, $start_date, $end_date = false) {
		debug('standing order ' . $name);
		//$this->setTanmediumId($this->tanmedium_id);
		
		$transfer_name = date('Y-m-d_H-i') . '_' . $bank_code . '-' . $account_number;
		
		$name = str_replace('"','',$name);
		
		$value = number_format(floatval($value), 2, ',','').':EUR';
		
		$enddate = '';
		if($end_date !== false)
		{
			$enddate = ' --lastExecutionDate="' . $end_date.'"';
		}
		
		$command = new CommandBuilder('aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'" transfer --executionCycle=1 --executionPeriod="'.$period.'" --rbank="' . $bank_code .'" --raccount="'.$account_number.'" --rname="'.$name.'" --executionDay=1 --firstExecutionDate='.$start_date.'' . $enddate );
		
		$command
			->addFlag('c', $this->config_dir . '/transfer/'.$transfer_name.'.ctx')
			->addFlag('v', $value)
			->addFlag('p', $subject)
			->addFlag('a',$this->account_number)
			->addFlag('b',$this->bank_code);
		
		echo "\n\n" . $command->__toString() . "\n\n";
		
		$this->shell->run($command);
		
		return true;
	}
	
	public function transferIBAN($value, $name, $iban, $bic, $subject) {
		
		$name = str_replace('"','',$name);
		
		$value = str_replace('.',',',$value).':EUR';
		
		$transfer_name = date('Y-m-d_H-i') . '_' . $iban;
		
		$command = new CommandBuilder('aqbanking-cli'.$this->is.' -P "' . $this->config_dir . '/pinfile" -D "'. $this->config_dir . '/config' .'" sepatransfer --riban=' . $iban .' --rname="'.$name.'"' );
		
		$command
			->addFlag('c', $this->config_dir . '/transfer/'.$transfer_name.'.ctx')
			->addFlag('v', $value)
			->addFlag('p', $subject);
		
		$this->shell->run($command);
		
		return true;
	}
	
	/**
	 * Gib IBAN zurück
	 * 
	 * @return string
	 */
	public function getIBAN() {
		return $this->iban;
	}
}