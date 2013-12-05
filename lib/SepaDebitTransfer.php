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
 * SEPA file "Credit Transfer Transaction Information" block.
 */
class SepaDebitTransfer extends SepaFileBlock
{
	/**
	 * @var string Payment ID.
	 */
	public $id;
	/**
	 * @var string
	 */
	public $endToEndId;
	/**
	 * @var string Account bank's BIC
	 */
	public $debtorBIC;
	/**
	 * @var string Name
	 */
	public $debtorName;
	/**
	 * @var string account IBAN
	 */
	public $debtorAccountIBAN;
	/**
	 * @var string Remittance information.
	 */
	public $remittanceInformation;
	/**
	 * @var string Mandate Identification
	 */
	public $mandateIdentification;
	/**
	 * @var string Mandate Date Of Signature
	 */
	public $mandateDateOfSignature = '2009-11-01';
	/**
	 * @var string Mandate Amendment Indicator
	 */
	public $mandateAmendmentIndicator = 'false';

	/**
	 * @var string ISO currency code
	 */
	protected $currency;
	/**
	 * @var integer Transfer amount in cents.
	 */
	protected $amountCents = 0;

	/**
	 * Set the transfer amount.
	 * @param mixed $amount
	 */
	public function setAmount($amount)
	{
		$amount += 0;
		if (is_float($amount))
			$amount = (integer) ($amount * 100);

		$this->amountCents = $amount;
	}

	/**
	 * Get the transfer amount in cents.
	 * @return integer
	 */
	public function getAmountCents()
	{
		return $this->amountCents;
	}
	
	/**
	 * Set the debtor's account currency code.
	 * @param string $code currency ISO code
	 * @throws Exception
	 */
	public function setCurrency($code)
	{
		$this->currency = $this->validateCurrency($code);
	}
	
	/**
	 * DO NOT CALL THIS FUNCTION DIRECTLY!
	 * 
	 * @param SimpleXMLElement $xml
	 * @return SimpleXMLElement
	 */
	public function generateXml(SimpleXMLElement $xml)
	{
		// -- Debit Transfer Transaction Information --\\
		
		$amount = $this->intToCurrency($this->getAmountCents());

		$DrctDbtTxInf = $xml->addChild('DrctDbtTxInf');
		$PmtId = $DrctDbtTxInf->addChild('PmtId');
		$PmtId->addChild('InstrId', $this->id);
		$PmtId->addChild('EndToEndId', $this->endToEndId);
		$DrctDbtTxInf->addChild('InstdAmt', $amount)->addAttribute('Ccy', $this->currency);
		if ($this->mandateIdentification) {
			$MndtRltdInf = $DrctDbtTxInf->addChild('DrctDbtTx')->addChild('MndtRltdInf');
			$MndtRltdInf->addChild('MndtId', $this->mandateIdentification);
			$MndtRltdInf->addChild('DtOfSgntr', $this->mandateDateOfSignature);
			$MndtRltdInf->addChild('AmdmntInd', $this->mandateAmendmentIndicator);
		}
		$FinInstnId = $DrctDbtTxInf->addChild('DbtrAgt')->addChild('FinInstnId');
		if ($this->debtorBIC) {
			$FinInstnId->addChild('BIC', $this->debtorBIC);
		}
		$DrctDbtTxInf->addChild('Dbtr')->addChild('Nm', htmlentities($this->debtorName));
		$DrctDbtTxInf->addChild('DbtrAcct')->addChild('Id')->addChild('IBAN', $this->debtorAccountIBAN);
		$DrctDbtTxInf->addChild('RmtInf')->addChild('Ustrd', $this->remittanceInformation);
		
		return $xml;
	}

}
