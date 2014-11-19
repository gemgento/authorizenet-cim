<?php

/*************************************************************************************************

This class allows for easy connection to Authorize.Net's Customer Information (CIM) API. More
information about the CIM API can be found at http://developer.authorize.net/api/cim/.

PHP version 5

LICENSE: This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.

@category   Ecommerce
@package	AuthnetCIM
@author	John Conde <johnny@johnconde.net>
@copyright  2008 - 2010 John Conde
@license	http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
@version	2.0

**************************************************************************************************/

/**
 * Class was modified and extended from its distributed form to fit our purposes.
 */
class AuthnetCIMException extends Exception {}

class ParadoxLabs_AuthorizeNetCim_Model_Api
{
	const USE_PRODUCTION_SERVER  = 1;
	const USE_DEVELOPMENT_SERVER = 0;

	const EXCEPTION_CURL = 10;

	private $params  = array();
	private $baseParams = array();
	private $items   = array();
	private $success = false;
	private $error   = true;

	private $login;
	private $transkey;
	private $xml;
	private $ch;
	private $response;
	private $url;
	private $resultCode;
	private $code;
	private $text;
	private $profileId;
	private $validation;
	private $paymentProfileId;
	private $results;
	
	private $delimiter = ',';

	private $validationModes = array( 'testMode', 'liveMode', 'none' );
	
	private $lengths = array(
		'refId'					=> 20,
		'billToFirstName'		=> 50,
		'billToLastName'		=> 50,
		'billToCompany'			=> 50,
		'billToAddress'			=> 60,
		'billToCity'			=> 40,
		'billToState'			=> 40,
		'billToZip'				=> 20,
		'billToCountry'			=> 60,
		'billToPhoneNumber'		=> 25,
		'billToFaxNumber'		=> 25,
		'shipToFirstName'		=> 50,
		'shipToLastName'		=> 50,
		'shipToCompany'			=> 50,
		'shipToAddress'			=> 60,
		'shipToCity'			=> 40,
		'shipToState'			=> 40,
		'shipToZip'				=> 20,
		'shipToCountry'			=> 60,
		'shipToPhoneNumber'		=> 25,
		'shipToFaxNumber'		=> 25,
		'cardNumber'			=> 16,
		'merchantCustomerId'	=> 20,
		'description'			=> 255,
		'email'					=> 255,
		'transactionKey'		=> 16,
	);
	
	public $responses;
	public $raw;

	public function init($login, $transkey, $test = self::USE_PRODUCTION_SERVER, $validationMode = 'liveMode')
	{
		$this->login	= trim($login);
		$this->transkey = trim($transkey);
		if (empty($this->login) || empty($this->transkey))
		{
			trigger_error('You have not configured your ' . __CLASS__ . '() login credentials properly.', E_USER_ERROR);
		}

		$this->test = (bool) $test;
		$subdomain  = ($this->test) ? 'apitest' : 'api';
		$this->url = 'https://'.$subdomain.'.authorize.net/xml/v1/request.api';

		$this->params['customerType']	= 'individual';
		$this->params['validationMode']   = in_array($validationMode, $this->validationModes) ? $validationMode : 'liveMode';
		$this->params['taxExempt']		= 'false';
		$this->params['recurringBilling'] = 'false';
		$this->baseParams = $this->params;
		
		return $this;
	}

	public function __destruct()
	{
		if (isset($this->ch))
		{
			curl_close($this->ch);
		}
	}

	public function __toString()
	{
		if (!$this->params)
		{
			return (string) $this;
		}
		$output  = '<table summary="Authnet Results" id="authnet">' . "\n";
		$output .= '<tr>' . "\n\t\t" . '<th colspan="2"><b>Outgoing Parameters</b></th>' . "\n" . '</tr>' . "\n";
		foreach ($this->params as $key => $value)
		{
			$output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>' . $key . '</b></td>';
			$output .= '<td>' . $value . '</td>' . "\n" . '</tr>' . "\n";
		}

		$output .= '</table>' . "\n";
		if (!empty($this->xml))
		{
			$output .= 'XML: ';
			$output .= htmlentities($this->xml);
		}
		return $output;
	}

	private function process()
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->xml);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 15);
		$this->response = curl_exec($this->ch);

		if($this->response)
		{
			// Don't log full CC numbers.
			$this->responses .= preg_replace('#cardCode>(\d+?)</cardCode#', 'cardCode>XXX</cardCode', preg_replace('#cardNumber>(\d{10})(\d+?)</cardNumber#', 'cardNumber>XXXX$2</cardNumber', $this->xml) )."\n";
			$this->responses .= $this->response."\n";
			$this->parseResults();
			if ($this->resultCode === 'Ok')
			{
				$this->success = true;
				$this->error   = false;
			}
			else
			{
				$this->success = false;
				$this->error   = true;
			}
			curl_close($this->ch);
			unset($this->ch);
		}
		else
		{
			throw new AuthnetCIMException('Connection error: ' . curl_error($this->ch) . ' (' . curl_errno($this->ch) . ')', self::EXCEPTION_CURL);
		}
	}

	public function createCustomerProfile($use_profiles = false, $type = 'credit')
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>';
		$this->xml .= '
						<profile>
							<merchantCustomerId>'. $this->getParameter('merchantCustomerId').'</merchantCustomerId>
							<description>'. $this->getParameter('description').'</description>
							<email>'. $this->getParameter('email') .'</email>';

		if ($use_profiles == true)
		{
			$this->xml .= '
							<paymentProfiles>
								<customerType>'. $this->getParameter('customerType').'</customerType>
								<billTo>
									<firstName>'. $this->getParameter('billToFirstName').'</firstName>
									<lastName>'. $this->getParameter('billToLastName').'</lastName>
									<company>'. $this->getParameter('billToCompany') .'</company>
									<address>'. $this->getParameter('billToAddress') .'</address>
									<city>'. $this->getParameter('billToCity') .'</city>';
			if( isset($this->params['billToState']) )
			{
				$this->xml .= '
									<state>'. $this->params['billToState'] .'</state>';
			}
			$this->xml .= '
									<zip>'. $this->getParameter('billToZip') .'</zip>
									<country>'. $this->getParameter('billToCountry') .'</country>
									<phoneNumber>'. $this->getParameter('billToPhoneNumber').'</phoneNumber>
									<faxNumber>'. $this->getParameter('billToFaxNumber').'</faxNumber>
								</billTo>
								<payment>';
			if ($type === 'credit')
			{
				$this->xml .= '
									<creditCard>
										<cardNumber>'. $this->getParameter('cardNumber').'</cardNumber>
										<expirationDate>'.$this->getParameter('expirationDate').'</expirationDate>
									</creditCard>';
			}
			else if ($type === 'check')
			{
				$this->xml .= '
									<bankAccount>
										<accountType>'.$this->getParameter('accountType').'</accountType>
										<nameOnAccount>'.$this->getParameter('nameOnAccount').'</nameOnAccount>
										<echeckType>'. $this->getParameter('echeckType').'</echeckType>
										<bankName>'. $this->getParameter('bankName').'</bankName>
										<routingNumber>'.$this->getParameter('routingNumber').'</routingNumber>
										<accountNumber>'.$this->getParameter('accountNumber').'</accountNumber>
									</bankAccount>
									<driversLicense>
										<dlState>'. $this->getParameter('dlState').'</dlState>
										<dlNumber>'. $this->getParameter('dlNumber').'</dlNumber>
										<dlDateOfBirth>'.$this->getParameter('dlDateOfBirth').'</dlDateOfBirth>
									</driversLicense>';
			}
			$this->xml .= '
								</payment>
							</paymentProfiles>';
			if( !empty($this->params['shipToAddress']) ) {
				$this->xml .= '
							<shipToList>
								<firstName>'. $this->getParameter('shipToFirstName').'</firstName>
								<lastName>'. $this->getParameter('shipToLastName').'</lastName>
								<company>'. $this->getParameter('shipToCompany') .'</company>
								<address>'. $this->getParameter('shipToAddress') .'</address>
								<city>'. $this->getParameter('shipToCity') .'</city>
								<state>'. $this->getParameter('shipToState') .'</state>
								<zip>'. $this->getParameter('shipToZip') .'</zip>
								<country>'. $this->getParameter('shipToCountry') .'</country>
								<phoneNumber>'. $this->getParameter('shipToPhoneNumber').'</phoneNumber>
								<faxNumber>'. $this->getParameter('shipToFaxNumber').'</faxNumber>
							</shipToList>';
			}
		}
			$this->xml .= '
						</profile>
					</createCustomerProfileRequest>';

		$this->process();
	}

	public function createCustomerPaymentProfile($type = 'credit')
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<paymentProfile>
							<customerType>'. $this->getParameter('customerType').'</customerType>
							<billTo>
								<firstName>'. $this->getParameter('billToFirstName').'</firstName>
								<lastName>'. $this->getParameter('billToLastName').'</lastName>
								<company>'. $this->getParameter('billToCompany') .'</company>
								<address>'. $this->getParameter('billToAddress') .'</address>
								<city>'. $this->getParameter('billToCity') .'</city>';
		if( isset($this->params['billToState']) )
		{
			$this->xml .= '
								<state>'. $this->getParameter('billToState') .'</state>';
		}
		$this->xml .= '
								<zip>'. $this->getParameter('billToZip') .'</zip>
								<country>'. $this->getParameter('billToCountry') .'</country>
								<phoneNumber>'. $this->getParameter('billToPhoneNumber').'</phoneNumber>
								<faxNumber>'. $this->getParameter('billToFaxNumber').'</faxNumber>
							</billTo>
							<payment>';
		if ($type === 'credit')
		{
			$this->xml .= '
								<creditCard>
									<cardNumber>'. $this->getParameter('cardNumber').'</cardNumber>
									<expirationDate>'.$this->getParameter('expirationDate').'</expirationDate>';
			if (!empty($this->params['cardCode']))
			{
				$this->xml .= '
									<cardCode>'.$this->getParameter('cardCode').'</cardCode>';
			}
			$this->xml .= '
								</creditCard>';
		}
		else if ($type === 'check')
		{
			$this->xml .= '
								<bankAccount>
									<accountType>'. $this->getParameter('accountType').'</accountType>
									<nameOnAccount>'.$this->getParameter('nameOnAccount').'</nameOnAccount>
									<echeckType>'. $this->getParameter('echeckType').'</echeckType>
									<bankName>'. $this->getParameter('bankName').'</bankName>
									<routingNumber>'.$this->getParameter('routingNumber').'</routingNumber>
									<accountNumber>'.$this->getParameter('accountNumber').'</accountNumber>
								</bankAccount>
								<driversLicense>
									<dlState>'. $this->getParameter('dlState') .'</dlState>
									<dlNumber>'. $this->getParameter('dlNumber').'</dlNumber>
									<dlDateOfBirth>'.$this->getParameter('dlDateOfBirth').'</dlDateOfBirth>
								</driversLicense>';
		}
		$this->xml .= '
							</payment>
						</paymentProfile>
						<validationMode>'. $this->getParameter('validationMode').'</validationMode>
					</createCustomerPaymentProfileRequest>';
		$this->process();
	}

	public function createCustomerShippingAddress()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<address>
							<firstName>'. $this->getParameter('shipToFirstName').'</firstName>
							<lastName>'. $this->getParameter('shipToLastName').'</lastName>
							<company>'. $this->getParameter('shipToCompany') .'</company>
							<address>'. $this->getParameter('shipToAddress') .'</address>
							<city>'. $this->getParameter('shipToCity') .'</city>
							<state>'. $this->getParameter('shipToState') .'</state>
							<zip>'. $this->getParameter('shipToZip') .'</zip>
							<country>'. $this->getParameter('shipToCountry') .'</country>
							<phoneNumber>'. $this->getParameter('shipToPhoneNumber').'</phoneNumber>
							<faxNumber>'. $this->getParameter('shipToFaxNumber').'</faxNumber>
						</address>
					</createCustomerShippingAddressRequest>';
		$this->process();
	}

	public function createCustomerProfileTransaction($type = 'profileTransAuthCapture')
	{
		$types = array('profileTransAuthCapture', 'profileTransCaptureOnly','profileTransAuthOnly','profileTransRefund','profileTransPriorAuthCapture');
		if (!in_array($type, $types))
		{
			trigger_error('createCustomerProfileTransaction() parameter must be "profileTransAuthCapture", "profileTransCaptureOnly", "profileTransAuthOnly", or empty', E_USER_ERROR);
		}

		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
				<transaction>
							<' . $type . '>
								<amount>'. $this->getParameter('amount') .'</amount>';
		if (isset($this->params['taxAmount']))
		{
			$this->xml .= '
								<tax>
									<amount>'. $this->getParameter('taxAmount').'</amount>
									<name>'. $this->getParameter('taxName') .'</name>
									<description>'.$this->getParameter('taxDescription').'</description>
								</tax>';
		}
		if (isset($this->params['shipAmount']))
		{
			$this->xml .= '
								<shipping>
									<amount>'. $this->getParameter('shipAmount').'</amount>
									<name>'. $this->getParameter('shipName') .'</name>
									<description>'.$this->getParameter('shipDescription').'</description>
								</shipping>';
		}
		if (isset($this->params['dutyAmount']))
		{
			$this->xml .= '
								<duty>
									<amount>'. $this->getParameter('dutyAmount').'</amount>
									<name>'. $this->getParameter('dutyName') .'</name>
									<description>'.$this->getParameter('dutyDescription').'</description>
								</duty>';
		}
 
//								<lineItems>' . $this->getLineItems() . '</lineItems>

		$this->xml .= '
						<customerProfileId>'.$this->getParameter('customerProfileId').'</customerProfileId>
								<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>';
		if( !empty($this->params['customerShippingAddressId']) ) {
			$this->xml .= '
								<customerShippingAddressId>'.$this->getParameter('customerShippingAddressId').'</customerShippingAddressId>';
		}
		if (isset($this->params['invoiceNumber']) && $type != 'profileTransPriorAuthCapture')
		{
			$this->xml .= '
								<order>
									<invoiceNumber>'.$this->getParameter('invoiceNumber').'</invoiceNumber>
									<description>'.$this->getParameter('description').'</description>
									<purchaseOrderNumber>'.$this->getParameter('purchaseOrderNumber').'</purchaseOrderNumber>
								</order>';
		}
		if ($type != 'profileTransRefund' && $type != 'profileTransPriorAuthCapture')
		{
		$this->xml .= '
								<taxExempt>'. $this->getParameter('taxExempt').'</taxExempt>
								<recurringBilling>'.$this->getParameter('recurringBilling').'</recurringBilling>';
		}
		if (isset($this->params['transId']))
		{
		$this->xml .= '
								<transId>'. $this->getParameter('transId').'</transId>';
		}
		if (isset($this->params['approvalCode']) && !in_array( $type, array( 'profileTransRefund', 'profileTransPriorAuthCapture', 'profileTransAuthOnly' ) ) )
		{
			$this->xml .= '
								<approvalCode>'. $this->getParameter('approvalCode').'</approvalCode>';
		}
		$this->xml .= '
							</' . $type . '>
						</transaction>
						<extraOptions><![CDATA[x_duplicate_window=30&x_customer_ip=' . $_SERVER['REMOTE_ADDR'] . ']]></extraOptions>
					</createCustomerProfileTransactionRequest>';

		$this->process();
	}

	public function deleteCustomerProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<deleteCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
					</deleteCustomerProfileRequest>';
		$this->process();
	}

	public function deleteCustomerPaymentProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>
					</deleteCustomerPaymentProfileRequest>';
		$this->process();
	}

	public function deleteCustomerShippingAddress()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<deleteCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<customerAddressId>'. $this->getParameter('customerAddressId').'</customerAddressId>
					</deleteCustomerShippingAddressRequest>';
		$this->process();
	}

	public function getCustomerProfileIds()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerProfileIdsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
					</getCustomerProfileIdsRequest>';
		$this->process();
	}

	public function getCustomerProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
					</getCustomerProfileRequest>';
		$this->process();
	}

	public function getCustomerPaymentProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>
					</getCustomerPaymentProfileRequest>';
		$this->process();
	}

	public function getCustomerShippingAddress()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<getCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
							<customerProfileId>'.$this->getParameter('customerProfileId').'</customerProfileId>
							<customerAddressId>'.$this->getParameter('customerAddressId').'</customerAddressId>
					</getCustomerShippingAddressRequest>';
		$this->process();
	}
	
	public function getTransactionDetails()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<getTransactionDetailsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<transId>'.$this->getParameter('transaction_id').'</transId>
					</getTransactionDetailsRequest>';
		$this->process();
	}

	public function updateCustomerProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<updateCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<profile>
							<merchantCustomerId>'.$this->getParameter('merchantCustomerId').'</merchantCustomerId>
							<description>'. $this->getParameter('description').'</description>
							<email>'. $this->getParameter('email') .'</email>
							<customerProfileId>'.$this->getParameter('customerProfileId').'</customerProfileId>
						</profile>
					</updateCustomerProfileRequest>';
		$this->process();
	}

	public function updateCustomerPaymentProfile($type = 'credit')
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<updateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<paymentProfile>';

//					<customerType>'. $this->getParameter('customerType').'</customerType>

		$this->xml .= '
							<billTo>
								<firstName>'. $this->getParameter('billToFirstName').'</firstName>
								<lastName>'. $this->getParameter('billToLastName').'</lastName>
								<company>'. $this->getParameter('billToCompany') .'</company>
								<address>'. $this->getParameter('billToAddress') .'</address>
								<city>'. $this->getParameter('billToCity') .'</city>
								<state>'. $this->getParameter('billToState') .'</state>
								<zip>'. $this->getParameter('billToZip') .'</zip>
								<country>'. $this->getParameter('billToCountry') .'</country>
							</billTo>';
		if ($type === 'credit')
		{
			$this->xml .= '
							<payment>
								<creditCard>
									<cardNumber>'. $this->getParameter('cardNumber').'</cardNumber>';
			if( !empty($this->params['expirationDate']) ) {
				$this->xml .= '
									<expirationDate>'.$this->getParameter('expirationDate').'</expirationDate>';
			}
			
			if (!empty($this->params['cardCode']))
			{
				$this->xml .= '
									<cardCode>'.$this->getParameter('cardCode').'</cardCode>';
			}
			$this->xml .= '
								</creditCard>
							</payment>';
		}
		else if ($type === 'check')
		{
			$this->xml .= '
							<payment>
								<bankAccount>
									<accountType>'.$this->getParameter('accountType').'</accountType>
									<nameOnAccount>'.$this->getParameter('nameOnAccount').'</nameOnAccount>
									<echeckType>'. $this->getParameter('echeckType').'</echeckType>
									<bankName>'. $this->getParameter('bankName').'</bankName>
									<routingNumber>'.$this->getParameter('routingNumber').'</routingNumber>
									<accountNumber>'.$this->getParameter('accountNumber').'</accountNumber>
								</bankAccount>
								<driversLicense>
									<dlState>'. $this->getParameter('dlState').'</dlState>
									<dlNumber>'. $this->getParameter('dlNumber').'</dlNumber>
									<dlDateOfBirth>'.$this->getParameter('dlDateOfBirth').'</dlDateOfBirth>
								</driversLicense>
							</payment>';
		}
		$this->xml .= '
							<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>
						</paymentProfile>
					</updateCustomerPaymentProfileRequest>';
		$this->process();
	}

	public function updateCustomerShippingAddress()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<updateCustomerShippingAddressRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<address>
							<firstName>'. $this->getParameter('shipToFirstName').'</firstName>
							<lastName>'. $this->getParameter('shipToLastName').'</lastName>
							<company>'. $this->getParameter('shipToCompany') .'</company>
							<address>'. $this->getParameter('shipToAddress') .'</address>
							<city>'. $this->getParameter('shipToCity') .'</city>
							<state>'. $this->getParameter('shipToState') .'</state>
							<zip>'. $this->getParameter('shipToZip') .'</zip>
							<country>'. $this->getParameter('shipToCountry') .'</country>
							<phoneNumber>'. $this->getParameter('shipToPhoneNumber').'</phoneNumber>
							<faxNumber>'. $this->getParameter('shipToFaxNumber').'</faxNumber>
							<customerAddressId>'.$this->getParameter('customerAddressId').'</customerAddressId>
						</address>
					</updateCustomerShippingAddressRequest>';
		$this->process();
	}

	public function validateCustomerPaymentProfile()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<validateCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>
						<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>
						<customerAddressId>'. $this->getParameter('customerAddressId').'</customerAddressId>
						<validationMode>'. $this->getParameter('validationMode').'</validationMode>
					</validateCustomerPaymentProfileRequest>';
		$this->process();
	}
	
	public function voidCustomerProfileTransaction()
	{
		$this->xml = '<?xml version="1.0" encoding="utf-8"?>
					<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
						<merchantAuthentication>
							<name>' . $this->login . '</name>
							<transactionKey>' . $this->transkey . '</transactionKey>
						</merchantAuthentication>
						<transaction>
							<profileTransVoid>';
	if( !empty($this->params['customerProfileId']) ) {
		$this->xml .= '
							<customerProfileId>'. $this->getParameter('customerProfileId').'</customerProfileId>';
	}
	if( !empty($this->params['customerPaymentProfileId']) ) {
		$this->xml .= '
							<customerPaymentProfileId>'.$this->getParameter('customerPaymentProfileId').'</customerPaymentProfileId>';
	}
	if( !empty($this->params['customerAddressId']) ) {
		$this->xml .= '
							<customerAddressId>'. $this->getParameter('customerAddressId').'</customerAddressId>';
	}
	$this->xml .= '
							<transId>'. $this->getParameter('transId').'</transId>
							</profileTransVoid>
						</transaction>
					</createCustomerProfileTransactionRequest>';
	$this->process();
	}

	private function getLineItems()
	{
		$tempXml = '';
		foreach ($this->items as $item)
		{
			foreach ($item as $key => $value)
			{
				$tempXml .= "\t" . '<' . $key . '>' . $value . '</' . $key . '>' . "\n";
			}
		}
		return $tempXml;
	}

	public function setLineItem($itemId, $name, $description, $quantity, $unitprice,$taxable = 'false')
	{
		$this->items[] = array('itemId' => $itemId, 'name' => $name, 'description' => $description, 'quantity' => $quantity, 'unitPrice' => $unitprice, 'taxable' => $taxable);
	}

	public function setParameter($field = '', $value = null)
	{
		$field = (is_string($field)) ? trim($field) : $field;
		$value = (is_string($value)) ? trim($value) : $value;
		if (!is_string($field))
		{
			trigger_error(__METHOD__ . '() arg 1 must be a string: ' . gettype($field) . ' given.', E_USER_ERROR);
		}
		if (empty($field))
		{
			trigger_error(__METHOD__ . '() requires a parameter field to be named.', E_USER_ERROR);
		}
		if( is_null($value) )
		{
			return;
		}
		// if (!is_string($value) && !is_numeric($value) && !is_bool($value))
		// {
		// 	trigger_error(__METHOD__ . '() arg 2 (' . $field . ') must be a string, integer, or boolean value: ' . gettype($value) . ' given.', E_USER_ERROR);
		// }
		// if ($value === '' || is_null($value))
		// {
		// 	trigger_error(__METHOD__ . '() parameter "value" is empty or missing (parameter: ' . $field . ').', E_USER_NOTICE);
		// }
		
		$value = str_replace( array( '&', '"', "'", '<', '>', ',' ), '', $value );
		
		if( isset( $this->lengths[ $field ] ) ) {
			$value = substr( $value, 0, $this->lengths[ $field ] );
		}
		
		$this->params[$field] = $value;
	}
	
	public function clearParameters()
	{
		$this->params = $this->baseParams;
		$this->responses = '';
	}
	
	public function getParameter($field = '')
	{
		if( isset( $this->params[ $field ] ) ) {
			return $this->params[ $field ];
		}
		
		return '';
	}

	private function parseResults()
	{
		$response = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $this->response);
		$xml = new SimpleXMLElement($response);
		
		$this->directResponse   = str_replace( '"', '', (string) $xml->directResponse );
		
		if( strlen( $this->directResponse ) > 1 ) {
			$this->delimiter		= substr( $this->directResponse, 1, 1 );
		}
		
		$this->raw			= $xml;
		$this->resultCode	= (string) $xml->messages->resultCode;
		$this->code			= (string) $xml->messages->message->code;
		$this->text			= (string) $xml->messages->message->text;
		$this->validation	= (string) $xml->validationDirectResponse;
		$this->profileId		= (int) $xml->customerProfileId;
		$this->addressId		= (int) $xml->customerAddressId;
		$this->paymentProfileId = (int) $xml->customerPaymentProfileId;
		$this->results		= explode( $this->delimiter, $this->directResponse );
	}

	public function isSuccessful()
	{
		return $this->success;
	}

	public function isError()
	{
		return ( $this->error || $this->getResponseType() == 2 || $this->getResponseType() == 3 );
	}
	
	public function getDelimiter()
	{
		return $this->delimiter;
	}

	public function getResponseSummary()
	{
		return 'Response code: ' . $this->getCode() . ' Message: ' . $this->getResponse();
	}

	public function getResponse()
	{
		return strip_tags($this->text);
	}

	public function getCode()
	{
		return $this->code;
	}

	public function getProfileID()
	{
		return $this->profileId;
	}

	public function validationDirectResponse()
	{
		return $this->validation;
	}

	public function getCustomerAddressId()
	{
		return $this->addressId;
	}

	public function getDirectResponse()
	{
		return $this->directResponse;
	}

	public function getPaymentProfileId()
	{
		return $this->paymentProfileId;
	}

	public function getResponseType()
	{
		return isset( $this->results[0] ) ? $this->results[0] : 1;
	}

	public function getResponseSubcode()
	{
		return $this->results[1];
	}

	public function getResponseCode()
	{
		return $this->results[2];
	}

	public function getResponseText()
	{
		return $this->results[3];
	}

	public function getAuthCode()
	{
		return isset($this->results[4]) ? $this->results[4] : false;
	}

	public function getAVSResponse()
	{
		return $this->results[5];
	}

	public function getTransactionID()
	{
		return isset($this->results[6]) ? $this->results[6] : false;
	}

	public function getCVVResponse()
	{
		return $this->results[38];
	}

	public function getCAVVResponse()
	{
		return $this->results[39];
	}
	
	public function getCcLast4()
	{
		return isset($this->results[50]) ? substr( $this->results[50], -4 ) : '';
	}
	
	public function getCcType()
	{
		$types = array(
			'American Express' => 'AE',
			'Discover' => 'DI',
			'MasterCard' => 'MC',
			'Visa' => 'VI',
		);
		
		if( isset($this->results[51]) && isset( $types[ $this->results[51] ] ) ) {
			return $types[ $this->results[51] ];
		}
		else {
			return '';
		}
	}
}
