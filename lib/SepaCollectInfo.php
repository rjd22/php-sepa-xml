<?php

/**
 * SEPA file generator.
 *
 * @copyright © Robert-Jan de Dreu <www.dreu.info> 2012-2013
 * @license GNU Lesser General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Lesser Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * SEPA file "Collect Information" block.
 */
class SepaCollectInfo extends SepaFileBlock
{
	/**
	 * @var string Unambiguously identify the payment.
	 */
	public $id;
	/**
	 * @var string Purpose of the transaction(s).
	 */
	public $categoryPurposeCode;
	/**
	 * @var string Creditor's name.
	 */
	public $creditorName;
	/**
	 * @var string Creditor's account IBAN.
	 */
	public $creditorAccountIBAN;
	/**
	 * @var string Creditor's account bank BIC code.
	 */
	public $creditorAgentBIC;
	/**
	 * @var string When to process the payment (in Y-m-d format)
	 */
	public $requestedExecutionDate;

	/**
	 * @var string Creditor's account ISO currency code.
	 */
	protected $creditorAccountCurrency = 'EUR';
	/**
	 * @var string Collect method.
	 */
	protected $collectMethod = 'DD';
	/**
	 * @var string Local service instrument code.
	 */
	protected $localInstrumentCode;
	/**
	 * @var string Sequence Type. Identifies the direct debit sequence, such as first, recurrent, final or one-off (FRST, RCUR, FNAL, OOFF)
	 */
	protected $sequenceType;
	/**
	 * @var integer
	 */
	protected $controlSumCents = 0;
	/**
	 * @var integer Number of payment transactions.
	 */
	protected $numberOfTransactions = 0;
	/**
	 * @var SepaDebitTransfer[]
	 */
	protected $debitTransfers = array();
	/**
	 * @var SepaTransferFile
	 */
	protected $transferFile;
	
	/**
	 * Constructor.
	 * @param SepaTransferFile $transferFile
	 */
	public function __construct(SepaTransferFile $transferFile)
	{
		$this->setTransferFile($transferFile);
	}

	/**
	 * Set the information for this "Collect Information" block.
	 * @param array $collectInfo
	 */
	public function setInfo(array $collectInfo)
	{
		$values = array(
			'id', 'categoryPurposeCode', 'creditorName', 'creditorAccountIBAN',
			'creditorAgentBIC', 'creditorAccountCurrency', 'requestedCollectionDate'
		);
		foreach ($values as $name) {
			if (isset($collectInfo[$name]))
				$this->$name = $collectInfo[$name];
		}
		if (isset($collectInfo['localInstrumentCode']))
			$this->setLocalInstrumentCode($collectInfo['localInstrumentCode']);

		if (isset($collectInfo['sequenceType']))
			$this->setSequenceType($collectInfo['sequenceType']);
		
		if (isset($collectInfo['collectMethod']))
			$this->setCollectMethod($collectInfo['collectMethod']);
		
		if (isset($collectInfo['creditorAccountCurrency']))
			$this->setCreditorAccountCurrency($collectInfo['creditorAccountCurrency']);
	}

	/**
	 * Set the payment method.
	 * @param string $method
	 * @throws Exception
	 */
	public function setCollectMethod($method)
	{
		$method = strtoupper($method);
		if (!in_array($method, array('DD'))) {
			throw new Exception("Invalid Collect Method: $method");
		}
		$this->collectMethod = $method;
	}

	/**
	 * Set the local service instrument code.
	 * @param string $code
	 * @throws Exception
	 */
	public function setLocalInstrumentCode($code)
	{
		$code = strtoupper($code);
		if (!in_array($code, array('CORE', 'B2B','COR1'))) {
			throw new Exception("Invalid Local Instrument Code: $code");
		}
		$this->localInstrumentCode = $code;
	}

	/**
	 * Set the sequence type.
	 * @param string $code
	 * @throws Exception
	 */
	public function setSequenceType($code)
	{
		$code = strtoupper($code);
		if (!in_array($code, array('FRST', 'RCUR', 'FNAL', 'OOFF'))) {
			throw new Exception("Invalid Local Instrument Code: $code");
		}
		$this->sequenceType = $code;
	}
	
	/**
	 * Set the debtor's account currency code.
	 * @param string $code currency ISO code
	 * @throws Exception
	 */
	public function setCreditorAccountCurrency($code)
	{
		$this->creditorAccountCurrency = $this->validateCurrency($code);
	}

	/**
	 * @return integer
	 */
	public function getNumberOfTransactions()
	{
		return $this->numberOfTransactions;
	}

	/**
	 * @return integer
	 */
	public function getControlSumCents()
	{
		return $this->controlSumCents;
	}
	
	/**
	 * Set the transfer file.
	 * @param SepaTransferFile $transferFile
	 */
	public function setTransferFile(SepaTransferFile $transferFile)
	{
		$this->transferFile = $transferFile;
	}

	/**
	 * Add a debit transfer transaction.
	 * @param array $transferInfo
	 */
	public function addDebitTransfer(array $transferInfo)
	{
		$transfer = new SepaDebitTransfer();
		$values = array(
			'id', 'debtorBIC', 'debtorName',
			'debtorAccountIBAN', 'remittanceInformation',
			'mandateIdentification', 'mandateDateOfSignature',
			'mandateAmendmentIndicator'
		);
		foreach ($values as $name) {
			if (isset($transferInfo[$name]))
				$transfer->$name = $transferInfo[$name];
		}
		if (isset($transferInfo['amount']))
			$transfer->setAmount($transferInfo['amount']);

		if (isset($transferInfo['currency']))
			$transfer->setCurrency($transferInfo['currency']);

		$transfer->endToEndId = $this->transferFile->messageIdentification . '/' . $this->getNumberOfTransactions();

		$this->debitTransfers[] = $transfer;
		$this->numberOfTransactions++;
		$this->controlSumCents += $transfer->getAmountCents();
	}

	/**
	 * DO NOT CALL THIS FUNCTION DIRECTLY!
	 *
	 * Generate the XML structure for this "Payment Info" block.
	 * 
	 * @param SimpleXMLElement $xml
	 * @return SimpleXMLElement
	 */
	public function generateXml(SimpleXMLElement $xml)
	{
		$datetime = new DateTime();

		$requestedExecutionDate = $this->requestedExecutionDate ? : $datetime->format('Y-m-d');

		// -- Payment Information --\\

		$PmtInf = $xml->CstmrDrctDbtInitn->addChild('PmtInf');
		$PmtInf->addChild('PmtInfId', htmlentities($this->id));
		if (isset($this->categoryPurposeCode))
			$PmtInf->addChild('CtgyPurp')->addChild('Cd', $this->categoryPurposeCode);

		$PmtInf->addChild('PmtMtd', $this->collectMethod);
		$PmtInf->addChild('NbOfTxs', $this->numberOfTransactions);
		$PmtInf->addChild('CtrlSum', $this->intToCurrency($this->controlSumCents));
		$PmtInf->addChild('PmtTpInf')->addChild('SvcLvl')->addChild('Cd', 'SEPA');
		if ($this->localInstrumentCode)
			$PmtInf->PmtTpInf->addChild('LclInstrm')->addChild('Cd', $this->localInstrumentCode);
		if ($this->sequenceType)
			$PmtInf->PmtTpInf->addChild('SeqTp', $this->sequenceType);
		
		$PmtInf->addChild('ReqdColltnDt', $requestedExecutionDate);
		$PmtInf->addChild('Cdtr')->addChild('Nm', htmlentities($this->creditorName));

		$CdtrAcct = $PmtInf->addChild('CdtrAcct');
		$CdtrAcct->addChild('Id')->addChild('IBAN', $this->creditorAccountIBAN);
		$CdtrAcct->addChild('Ccy', $this->creditorAccountCurrency);

		$PmtInf->addChild('CdtrAgt')->addChild('FinInstnId')->addChild('BIC', $this->creditorAgentBIC);
		$PmtInf->addChild('ChrgBr', 'SLEV');

		// -- Credit Transfer Transaction Information --\\

		foreach ($this->debitTransfers as $transfer) {
			$PmtInf = $transfer->generateXml($PmtInf);
		}
		return $xml;
	}

}
