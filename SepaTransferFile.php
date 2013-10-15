<?php

/**
 * SEPA file generator.
 *
 * ALPHA QUALITY SOFTWARE
 * Do NOT use in production environments!!!
 *
 * @copyright © Digitick <www.digitick.net> 2012-2013
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
 *
 * @author Jérémy Cambon
 * @author Ianaré Sévi
 * @author Vincent MOMIN
 * @author Robert-Jan de Dreu
 */
require 'lib/SepaFileBlock.php';
require 'lib/SepaPaymentInfo.php';
require 'lib/SepaCollectInfo.php';
require 'lib/SepaCreditTransfer.php';
require 'lib/SepaDebitTransfer.php';

/**
 * SEPA payments file object.
 */
class SepaTransferFile extends SepaFileBlock
{
	/**
	 * @var boolean If true, the transaction will never be executed.
	 */
	public $isTest = false;
	/**
	 * @var string Unambiguously identify the message.
	 */
	public $messageIdentification;
	/**
	 * @var string Payment sender's name.
	 */
	public $initiatingPartyName;
	/**
	 * @var string Payment sender's ID (for example: the tax ID).
	 */
	public $initiatingPartyId;
	/**
	 * @var string Purpose of the transaction(s).
	 */
	public $categoryPurposeCode;
	/**
	 * @var string NOT USED - reserve for future.
	 */
	public $grouping;

	/**
	 * @var integer Sum of all transactions in all payments regardless of currency.
	 */
	protected $controlSumCents = 0;
	/**
	 * @var integer Number of payment transactions.
	 */
	protected $numberOfTransactions = 0;
	/**
	 * @var SimpleXMLElement
	 */
	protected $xml;
	/**
	 * @var string
	 */
	protected $type;
	/**
	 * @var SepaPaymentInfo[], SepaCollectInfo[]
	 */
	protected $transactions = array();

	const CREDIT_STRING = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03"></Document>';
	const DEBIT_STRING = '<?xml version="1.0" encoding="UTF-8"?><Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02"></Document>';

	public function __construct($type = 'credit')
	{
		if ($type == 'debit')
		{
			$this->xml = simplexml_load_string(self::DEBIT_STRING);
			$this->xml->addChild('CstmrDrctDbtInitn');

			$this->type = 'debit';
		}
		else
		{
			$this->xml = simplexml_load_string(self::CREDIT_STRING);
			$this->xml->addChild('CstmrCdtTrfInitn');

			$this->type = 'credit';
		}
	}
	
	/**
	 * Return the XML string.
	 * @return string
	 */
	public function asXML()
	{
		$this->generateXml();
		return $this->xml->asXML();
	}
	
	/**
	 * Get the header control sum in cents.
	 * @return integer
	 */
	public function getHeaderControlSumCents()
	{
		return $this->controlSumCents;
	}

	/**
	 * Get the transaction control sum in cents.
	 * @return integer
	 */
	public function getTransactionControlSumCents()
	{
		return $this->controlSumCents;
	}

	/**
	 * Set the information for the "Payment Information" block.
	 * @param array $paymentInfo
	 * @return SepaPaymentInfo
	 */
	public function addPaymentInfo(array $paymentInfo)
	{
		$payment = new SepaPaymentInfo($this);
		$payment->setInfo($paymentInfo);
		
		$this->transactions[] = $payment;
		
		return $payment;
	}

	/**
	 * Set the information for the "Collect Information" block.
	 * @param array $collectInfo
	 * @return SepaCollectInfo
	 */
	public function addCollectInfo(array $collectInfo)
	{
		$collect = new SepaCollectInfo($this);
		$collect->setInfo($collectInfo);

		$this->transactions[] = $collect;

		return $collect;
	}

	/**
	 * Update counters related to "Transaction Information" blocks.
	 */
	protected function updateTransactionCounters()
	{
		$this->numberOfTransactions = 0;
		$this->controlSumCents = 0;
		
		foreach ($this->transactions as $payment) {
			$this->numberOfTransactions += $payment->getNumberOfTransactions();
			$this->controlSumCents += $payment->getControlSumCents();
		}
	}

	/**
	 * Generate the XML structure.
	 */
	protected function generateXml()
	{
		$this->updateTransactionCounters();
		
		$datetime = new DateTime();
		$creationDateTime = $datetime->format('Y-m-d\TH:i:s');

		// -- Group Header -- \\

		if ($this->type == 'debit') {
			$GrpHdr = $this->xml->CstmrDrctDbtInitn->addChild('GrpHdr');
		} else {
			$GrpHdr = $this->xml->CstmrCdtTrfInitn->addChild('GrpHdr');
		}

		$GrpHdr->addChild('MsgId', $this->messageIdentification);
		$GrpHdr->addChild('CreDtTm', $creationDateTime);
		if ($this->isTest)
			$GrpHdr->addChild('Authstn')->addChild('Prtry', 'TEST');

		$GrpHdr->addChild('NbOfTxs', $this->numberOfTransactions);
		$GrpHdr->addChild('CtrlSum', $this->intToCurrency($this->controlSumCents));
		$GrpHdr->addChild('InitgPty')->addChild('Nm', $this->initiatingPartyName);
		if (isset($this->initiatingPartyId))
			$GrpHdr->addChild('InitgPty')->addChild('Id', $this->initiatingPartyId);

		// -- Payment Information --\\
		foreach ($this->transactions as $payment) {
			$this->xml = $payment->generateXml($this->xml);
		}
	}
}

