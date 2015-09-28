<?php
class Db {
	static $sqlite = false;
	
	public static function init($location) {
		
		if (DB::$sqlite = new SQLite3($location . '/db.sqlite')) {
			
			return true;
		}
		
		return false;
	}
	
	public static function createTables() {
		
		debug('create tables');
		
		$sql = '
			CREATE TABLE IF NOT EXISTS `transaction` (
				id INTEGER PRIMARY KEY, 
				localCountry TEXT,
				localBankCode TEXT,
				localBranchId TEXT,
				localAccountNumber TEXT,
				localSuffix TEXT,
				localIban TEXT,
				localName TEXT,
				localBic TEXT,
				remoteCountry TEXT,
				remoteBankName TEXT,
				remoteBankLocation TEXT,
				remoteBankCode TEXT,
				remoteBranchId TEXT,
				remoteAccountNumber TEXT,
				remoteSuffix TEXT,
				remoteIban TEXT,
				remoteName TEXT,
				remoteName1 TEXT,
				remoteBic TEXT,
				uniqueId TEXT,
				idForApplication TEXT,
				groupId TEXT,
				valutaDate TEXT,
				date TEXT,
				value_value REAL,
				value_orig TEXT,
				value_currency TEXT,
				fees_value TEXT,
				fees_currency TEXT,
				textKey TEXT,
				textKeyExt TEXT,
				transactionKey TEXT,
				customerReference TEXT,
				bankReference TEXT,
				transactionCode TEXT,
				transactionText TEXT,
				primanota TEXT,
				fiId TEXT,
				purpose TEXT,
				category TEXT,
				period TEXT,
				cycle TEXT,
				executionDay TEXT,
				firstExecutionDate TEXT,
				lastExecutionDate TEXT,
				nextExecutionDate TEXT,
				type TEXT,
				subType TEXT,
				status TEXT,
				charge TEXT,
				remoteAddrStreet TEXT,
				remoteAddrZipcode TEXT,
				remoteAddrCity TEXT,
				remotePhone TEXT,
				unitId TEXT,
				unitIdNameSpace TEXT,
				units_value TEXT,
				units_currency TEXT,
				unitprice_value TEXT,
				unitprice_currency TEXT,
				commission_value TEXT,
				commission_currency TEXT,
				bankAccountId TEXT,
				groupId2 TEXT,
				creditorSchemeId TEXT,
				mandateId TEXT,
				mandateDate_dateString TEXT,
				mandateDebitorName TEXT,
				sequenceType TEXT,
				originalCreditorSchemeId TEXT,
				originalMandateId TEXT,
				originalCreditorName TEXT,
				`unique` TEXT
			);';
		
		Db::$sqlite->exec($sql);
	}
	
	public static function q($sql) {
		if ($res = Db::$sqlite->query($sql)) {
			
			$out = array();
			while($result = $res->fetchArray(SQLITE3_ASSOC)) {
				$out[] = $result;
			}
			
			return $out;
		} 
		
		return false;
	}
	
	public static function qOne($sql) {
		if ($res = Db::$sqlite->query($sql)) {
			if($result = $res->fetchArray()) {
				return $result[0];
			}
		} 
		
		return false;
	}
	
	public static function qRow($sql) {
		if ($res = Db::$sqlite->query($sql)) {
			if($result = $res->fetchArray()) {
				return $result;
			}
		} 
		
		return false;
	}
	
	public static function qCol($sql) {
		$q = @Db::$sqlite->query($sql);
		if ($q === false) {
			return false;
		}
	
		return $q->column(0);
	}
	
	public static function insert($sql) {
		$q = @Db::$sqlite->exec($sql);
		if ($q === false) {
			return false;
		}
	
		return Db::$sqlite->lastInsertRowID();
	}
	
	public static function floatval($val) {
		return floatval($val);
	}
	
	public static function strval($val) {
		return '"'.Db::$sqlite->escapeString($val).'"';
	}
	
	public static function transactionExists($unique) {
		return Db::qOne('SELECT id FROM `transaction` WHERE `unique` = ' . Db::strval($unique));
	}
	
	public static function listTransactions($start_ts,$end_ts) {
		return Db::q('
			SELECT 
				id,
				remoteBankCode,
				remoteAccountNumber,
				remoteIban,
				remoteBic,
				remoteName,
				value_value,
				purpose,
				date
				
			FROM 
				`transaction` 
		');
	}
	
	public static function addTransaction(
			$localCountry,
			$localBankCode,
			$localBranchId,
			$localAccountNumber,
			$localSuffix,
			$localIban,
			$localName,
			$localBic,
			$remoteCountry,
			$remoteBankName,
			$remoteBankLocation,
			$remoteBankCode,
			$remoteBranchId,
			$remoteAccountNumber,
			$remoteSuffix,
			$remoteIban,
			$remoteName,
			$remoteName1,
			$remoteBic,
			$uniqueId,
			$idForApplication,
			$groupId,
			$valutaDate,
			$date,
			$value_value,
			$value_orig,
			$value_currency,
			$fees_value,
			$fees_currency,
			$textKey,
			$textKeyExt,
			$transactionKey,
			$customerReference,
			$bankReference,
			$transactionCode,
			$transactionText,
			$primanota,
			$fiId,
			$purpose,
			$category,
			$period,
			$cycle,
			$executionDay,
			$firstExecutionDate,
			$lastExecutionDate,
			$nextExecutionDate,
			$type,
			$subType,
			$status,
			$charge,
			$remoteAddrStreet,
			$remoteAddrZipcode,
			$remoteAddrCity,
			$remotePhone,
			$unitId,
			$unitIdNameSpace,
			$units_value,
			$units_currency,
			$unitprice_value,
			$unitprice_currency,
			$commission_value,
			$commission_currency,
			$bankAccountId,
			$groupId2,
			$creditorSchemeId,
			$mandateId,
			$mandateDate_dateString,
			$mandateDebitorName,
			$sequenceType,
			$originalCreditorSchemeId,
			$originalMandateId,
			$originalCreditorName
			) {
		
		$unique = md5($localCountry.$localBankCode.$localBranchId.$localAccountNumber.$localSuffix.$localIban.$localName.$localBic.$remoteCountry.$remoteBankName.$remoteBankLocation.$remoteBankCode.$remoteBranchId.$remoteAccountNumber.$remoteSuffix.$remoteIban.$remoteName.$remoteName1.$remoteBic.$uniqueId.$idForApplication.$groupId.$valutaDate.$date.$value_value.$value_orig.$value_currency.$fees_value.$fees_currency.$textKey.$textKeyExt.$transactionKey.$customerReference.$bankReference.$transactionCode.$transactionText.$primanota.$fiId.$purpose.$category.$period.$cycle.$executionDay.$firstExecutionDate.$lastExecutionDate.$nextExecutionDate.$type.$subType.$status.$charge.$remoteAddrStreet.$remoteAddrZipcode.$remoteAddrCity.$remotePhone.$unitId.$unitIdNameSpace.$units_value.$units_currency.$unitprice_value.$unitprice_currency.$commission_value.$commission_currency.$bankAccountId.$groupId2.$creditorSchemeId.$mandateId.$mandateDate_dateString.$mandateDebitorName.$sequenceType.$originalCreditorSchemeId.$originalMandateId.$originalCreditorName);
		
		if(Db::transactionExists($unique)) {
			return false;
		}
		
		$sql = '
		INSERT INTO `transaction` (
				localCountry,
				localBankCode,
				localBranchId,
				localAccountNumber,
				localSuffix,
				localIban,
				localName,
				localBic,
				remoteCountry,
				remoteBankName,
				remoteBankLocation,
				remoteBankCode,
				remoteBranchId,
				remoteAccountNumber,
				remoteSuffix,
				remoteIban,
				remoteName,
				remoteName1,
				remoteBic,
				uniqueId,
				idForApplication,
				groupId,
				valutaDate,
				date,
				value_value,
				value_orig,
				value_currency,
				fees_value,
				fees_currency,
				textKey,
				textKeyExt,
				transactionKey,
				customerReference,
				bankReference,
				transactionCode,
				transactionText,
				primanota,
				fiId,
				purpose,
				period,
				category,
				cycle,
				executionDay,
				firstExecutionDate,
				lastExecutionDate,
				nextExecutionDate,
				type,
				subType,
				status,
				charge,
				remoteAddrStreet,
				remoteAddrZipcode,
				remoteAddrCity,
				remotePhone,
				unitId,
				unitIdNameSpace,
				units_value,
				units_currency,
				unitprice_value,
				unitprice_currency,
				commission_value,
				commission_currency,
				bankAccountId,
				groupId2,
				creditorSchemeId,
				mandateId,
				mandateDate_dateString,
				mandateDebitorName,
				sequenceType,
				originalCreditorSchemeId,
				originalMandateId,
				originalCreditorName,
				`unique`
		)
		VALUES(
				' . Db::strval($localCountry) . ',
				' . Db::strval($localBankCode) . ',
				' . Db::strval($localBranchId) . ',
				' . Db::strval($localAccountNumber) . ',
				' . Db::strval($localSuffix) . ',
				' . Db::strval($localIban) . ',
				' . Db::strval($localName) . ',
				' . Db::strval($localBic) . ',
				' . Db::strval($remoteCountry) . ',
				' . Db::strval($remoteBankName) . ',
				' . Db::strval($remoteBankLocation) . ',
				' . Db::strval($remoteBankCode) . ',
				' . Db::strval($remoteBranchId) . ',
				' . Db::strval($remoteAccountNumber) . ',
				' . Db::strval($remoteSuffix) . ',
				' . Db::strval($remoteIban) . ',
				' . Db::strval($remoteName) . ',
				' . Db::strval($remoteName1) . ',
				' . Db::strval($remoteBic) . ',
				' . Db::strval($uniqueId) . ',
				' . Db::strval($idForApplication) . ',
				' . Db::strval($groupId) . ',
				' . Db::strval($valutaDate) . ',
				' . Db::strval($date) . ',
				' . Db::floatval($value_value) . ',
				' . Db::strval($value_orig) . ',
				' . Db::strval($value_currency) . ',
				' . Db::strval($fees_value) . ',
				' . Db::strval($fees_currency) . ',
				' . Db::strval($textKey) . ',
				' . Db::strval($textKeyExt) . ',
				' . Db::strval($transactionKey) . ',
				' . Db::strval($customerReference) . ',
				' . Db::strval($bankReference) . ',
				' . Db::strval($transactionCode) . ',
				' . Db::strval($transactionText) . ',
				' . Db::strval($primanota) . ',
				' . Db::strval($fiId) . ',
				' . Db::strval($purpose) . ',
				' . Db::strval($category) . ',
				' . Db::strval($period) . ',
				' . Db::strval($cycle) . ',
				' . Db::strval($executionDay) . ',
				' . Db::strval($firstExecutionDate) . ',
				' . Db::strval($lastExecutionDate) . ',
				' . Db::strval($nextExecutionDate) . ',
				' . Db::strval($type) . ',
				' . Db::strval($subType) . ',
				' . Db::strval($status) . ',
				' . Db::strval($charge) . ',
				' . Db::strval($remoteAddrStreet) . ',
				' . Db::strval($remoteAddrZipcode) . ',
				' . Db::strval($remoteAddrCity) . ',
				' . Db::strval($remotePhone) . ',
				' . Db::strval($unitId) . ',
				' . Db::strval($unitIdNameSpace) . ',
				' . Db::strval($units_value) . ',
				' . Db::strval($units_currency) . ',
				' . Db::strval($unitprice_value) . ',
				' . Db::strval($unitprice_currency) . ',
				' . Db::strval($commission_value) . ',
				' . Db::strval($commission_currency) . ',
				' . Db::strval($bankAccountId) . ',
				' . Db::strval($groupId2) . ',
				' . Db::strval($creditorSchemeId) . ',
				' . Db::strval($mandateId) . ',
				' . Db::strval($mandateDate_dateString) . ',
				' . Db::strval($mandateDebitorName) . ',
				' . Db::strval($sequenceType) . ',
				' . Db::strval($originalCreditorSchemeId) . ',
				' . Db::strval($originalMandateId) . ',
				' . Db::strval($originalCreditorName) . ',
				' . Db::strval($unique) . '
				
		)';
		
		return Db::insert($sql);
	}
}