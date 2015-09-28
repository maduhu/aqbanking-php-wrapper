<?php
require 'inc.php';

/*
 * initiate Banking account for online Banking
 */


$account = new Account('DEXXXXXXXXXXX');

$account->setLogin('XXXXXXXXXXX');
$account->setPin('XXXXX');
$account->setOwner('Peter Lustig');

echo $account->getBalance();

$transactions = $account->listTransactions('2015-09-01');

print_r($transactions);

/*
 * initiate Account2 to transfer money
 */

$account2 = new Account('DEXXXXXXXXXXXX');
$account2->setOwner('Benjamin Bluemchen');

/*
 * transfer money
 */


$transfer = new Transfer($account, $account2, 2);
$transfer->setSubject('Hallo Ben');
$transfer->exec();


/*
 * Dauerauftrag einreichen
 */

$trans = new StandingOrder($account, $account2, 1);
$trans->setSubject('Dauertest Ben');
$trans->exec();


/*
 * lastschrift
 *
 */

// comming soon