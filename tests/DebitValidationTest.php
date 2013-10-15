<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once '../SepaTransferFile.php';

/**
 * Various schema validation tests.
 */
class ValidationTest extends PHPUnit_Framework_TestCase
{
	protected $schema;
	protected $dom;
	
	protected function setUp()
	{
		$this->schema = "pain.008.001.02.xsd";
		$this->dom = new DOMDocument('1.0', 'UTF-8');
	} 

	/**
	 * Sanity check: test reference file with XSD.
	 */
	public function testSanity()
	{
		$this->dom->load('pain.008.001.02.xml');
		$validated = $this->dom->schemaValidate($this->schema);
		$this->assertTrue($validated);
	}

	/**
	 * Test a transfer file with one charge and one transaction.
	 */
	public function testSinglePaymentSingleTrans()
	{
		$sepaFile = new SepaTransferFile('debit');
		$sepaFile->messageIdentification = 'transferID';
		$sepaFile->initiatingPartyName = 'Me';
		
		$payment = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '0.02',
			'debtorName'            => 'Their Corp',
			'debtorAccountIBAN'     => 'FI1350001540000056',
			'debtorBIC'             => 'OKOYFIHH',
			'remittanceInformation' => 'Transaction description',
		));
		
		$this->dom->loadXML($sepaFile->asXML());
		
		$validated = $this->dom->schemaValidate($this->schema);
		$this->assertTrue($validated);
	}

	/**
	 * Test a transfer file with one payment and several transactions.
	 */
	public function testSinglePaymentMultiTrans()
	{
		$sepaFile = new SepaTransferFile('debit');
		$sepaFile->messageIdentification = 'transferID';
		$sepaFile->initiatingPartyName = 'Me';
		
		$payment = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '0.02',
			'debtorName'            => 'Their Corp',
			'debtorAccountIBAN'     => 'FI1350001540000056',
			'debtorBIC'             => 'OKOYFIHH',
			'remittanceInformation' => 'Transaction description',
		));
		$payment->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '5000.00',
			'debtorName'            => 'GHI Semiconductors',
			'debtorAccountIBAN'     => 'BE30001216371411',
			'debtorBIC'             => 'DDDDBEBB',
			'remittanceInformation' => 'Transaction description',
		));
		
		$this->dom->loadXML($sepaFile->asXML());
		
		$validated = $this->dom->schemaValidate($this->schema);
		$this->assertTrue($validated);
	}
	
	/**
	 * Test a transfer file with several payments, one transaction each.
	 */
	public function testMultiPaymentSingleTrans()
	{
		$sepaFile = new SepaTransferFile('debit');
		$sepaFile->messageIdentification = 'transferID';
		$sepaFile->initiatingPartyName = 'Me';
		
		$payment1 = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment1->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '0.02',
			'debtorName'            => 'Their Corp',
			'debtorAccountIBAN'     => 'FI1350001540000056',
			'debtorBIC'             => 'OKOYFIHH',
			'remittanceInformation' => 'Transaction description',
		));
		$payment2 = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment2->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '5000.00',
			'debtorName'            => 'GHI Semiconductors',
			'debtorAccountIBAN'     => 'BE30001216371411',
			'debtorBIC'             => 'DDDDBEBB',
			'remittanceInformation' => 'Transaction description',
		));
		
		$this->dom->loadXML($sepaFile->asXML());
		
		$validated = $this->dom->schemaValidate($this->schema);
		$this->assertTrue($validated);
	}
	
	/**
	 * Test a transfer file with several payments, several transactions each.
	 */
	public function testMultiPaymentMultiTrans()
	{
		$sepaFile = new SepaTransferFile('debit');
		$sepaFile->messageIdentification = 'transferID';
		$sepaFile->initiatingPartyName = 'Me';
		
		$payment1 = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment1->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '0.02',
			'debtorName'            => 'Their Corp',
			'debtorAccountIBAN'     => 'FI1350001540000056',
			'debtorBIC'             => 'OKOYFIHH',
			'remittanceInformation' => 'Transaction description',
		));
		$payment1->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '5000.00',
			'debtorName'            => 'GHI Semiconductors',
			'debtorAccountIBAN'     => 'BE30001216371411',
			'debtorBIC'             => 'DDDDBEBB',
			'remittanceInformation' => 'Transaction description',
		));
		
		$payment2 = $sepaFile->addCollectInfo(array(
			'id'                    => 'Payment Info ID',
			'creditorName'          => 'My Corp',
			'creditorAccountIBAN'   => 'FR1420041010050500013M02606',
			'creditorAgentBIC'      => 'PSSTFRPPMON'
		));
		$payment2->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '0.02',
			'debtorName'            => 'Their Corp',
			'debtorAccountIBAN'     => 'FI1350001540000056',
			'debtorBIC'             => 'OKOYFIHH',
			'remittanceInformation' => 'Transaction description',
		));
		$payment2->addDebitTransfer(array(
			'id'                    => 'Id shown in bank statement',
			'currency'              => 'EUR',
			'amount'                => '5000.00',
			'debtorName'            => 'GHI Semiconductors',
			'debtorAccountIBAN'     => 'BE30001216371411',
			'debtorBIC'             => 'DDDDBEBB',
			'remittanceInformation' => 'Transaction description',
		));
		
		$this->dom->loadXML($sepaFile->asXML());
		
		$validated = $this->dom->schemaValidate($this->schema);
		$this->assertTrue($validated);
	}
}
