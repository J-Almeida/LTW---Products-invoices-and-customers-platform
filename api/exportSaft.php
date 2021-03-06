﻿<?php
require_once '../bootstrap.php';
require_once 'search.php';
require_once 'utilities.php';
require_once 'authenticationUtilities.php';

if(!comparePermissions(array('write'))) {
    $error = new Error(601, 'Permission denied');
    die( json_encode($error->getInfo()) );
}

$sourceID = $_SESSION['username'];

Header('Content-Type: text/xml');

/****************************************************
AUDIT ELEMENT
****************************************************/

$AuditElement = new SimpleXMLElement("<AuditFile></AuditFile>");
$AuditElement->addAttribute('xmlns', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01');
//SimpleXML always removes the first namespace prefix, so we need to repeat it
$AuditElement->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$AuditElement->addAttribute('xmlns:xmlns:spi', 'http://Empresa.pt/invoice1');
$AuditElement->addAttribute('xmlns:xmlns:saf', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01');
$AuditElement->addAttribute('xsi:xsi:schemaLocation', 'urn:OECD:StandardAuditFile-Tax:PT_1.03_01 http://serprest.pt/tmp/SAFTPT-1.03_01.xsd');

/****************************************************
HEADER
****************************************************/
$date = date('Y-m-d');
$fiscalYear = getFiscalYear();
$startDate = getStartDate();
$endDate = getEndDate();

$Header = $AuditElement->addChild('Header');
$Header->addChild('AuditFileVersion','1.03_01');
$Header->addChild('CompanyID','Porto 11511');
$Header->addChild('TaxRegistrationNumber','133713666');
$Header->addChild('TaxAccountingBasis', 'F');
$Header->addChild('CompanyName','Totally Legit Sellers, Inc.');
$CompanyAddress = $Header->addChild('CompanyAddress');
$CompanyAddress->addChild('AddressDetail','Travessa Sta. dos Ludibriados, 117');
$CompanyAddress->addChild('City','Porto');
$CompanyAddress->addChild('PostalCode','1337-666');
$CompanyAddress->addChild('Country','PT');
$Header->addChild('FiscalYear',$fiscalYear);
$Header->addChild('StartDate',$startDate);
$Header->addChild('EndDate',$endDate);
$Header->addChild('CurrencyCode','EUR');
$Header->addChild('DateCreated',$date);
$Header->addChild('TaxEntity','Global');
$Header->addChild('ProductCompanyTaxID','133769666');
$Header->addChild('SoftwareCertificateNumber','0');
$Header->addChild('ProductID','Empresa/MagnumInvoices');
$Header->addChild('ProductVersion','1.0');


/****************************************************
MASTERFILES
****************************************************/
$MasterFile = $AuditElement->addChild('MasterFiles');

$search = new ListAllSearch('Customer', 'CustomerID', array(), array('*'), array('Customer' => 'Country'));
$customers = $search->getResults();

$search2 = new ListAllSearch('Product', 'ProductID', array(), array('*'));
$products = $search2->getResults();

$search3 = new ListAllSearch('Tax', 'TaxID', array(), array('*'));
$taxes = $search3->getResults();

foreach($customers as $customer){
	$customerElement = $MasterFile->addChild('Customer');
	$customerElement->addChild('CustomerID',$customer['CustomerID']);
	$customerElement->addChild('AccountID',$customer['CustomerTaxID']);
	$customerElement->addChild('CustomerTaxID',$customer['CustomerTaxID']);
	$customerElement->addChild('CompanyName',htmlspecialchars($customer['CompanyName']));

	$BillingAddress = $customerElement->addChild('BillingAddress');
	$BillingAddress->addChild('AddressDetail',htmlspecialchars($customer['AddressDetail']));
	$BillingAddress->addChild('City',htmlspecialchars($customer['City']));
	$BillingAddress->addChild('PostalCode',htmlspecialchars($customer['PostalCode']));
	$BillingAddress->addChild('Country',htmlspecialchars($customer['Country']));

	if(isset($customer['Email']) && !empty($customer['Email']))
		$customerElement->addChild('Email', $customer['Email']);
	
	$customerElement->addChild('SelfBillingIndicator', '0');
}

foreach($products as $product){

	$productElement = $MasterFile->addChild('Product');
	$productElement->addChild('ProductType','P');
	$productElement->addChild('ProductCode',$product['ProductCode']);
	$productElement->addChild('ProductGroup','1');
	$productElement->addChild('ProductDescription',htmlspecialchars($product['ProductDescription']));
	$productElement->addChild('ProductNumberCode',htmlspecialchars($product['ProductCode']));
}

$productElement = $MasterFile->addChild('TaxTable');
foreach($taxes as $tax){
	$taxTableElement = $productElement->addChild('TaxTableEntry');
	$taxTableElement->addChild('TaxType',$tax['TaxType']);
	$taxTableElement->addChild('TaxCountryRegion','PT');
	$taxTableElement->addChild('TaxCode','NOR');
	$taxTableElement->addChild('Description',htmlspecialchars($tax['TaxDescription']));
	$taxTableElement->addChild('TaxPercentage',htmlspecialchars($tax['TaxPercentage']));
}

/****************************************************
Sales Invoices
****************************************************/
$SourceDocuments = $AuditElement->addChild('SourceDocuments');

$SalesInvoices = $SourceDocuments->addChild('SalesInvoices');

$search4 = new ListAllSearch('Invoice', 'InvoiceID', array(), array('*'));
$invoices = $search4->getResults();

$number = count($invoices);
$SalesInvoices->addChild('NumberOfEntries',$number);
$SalesInvoices->addChild('TotalDebit','0');

$credit = 0;

foreach($invoices as $invoice)
{
	$credit += $invoice['NetTotal'];
}

$SalesInvoices->addChild('TotalCredit',$credit);

foreach($invoices as $invoice)
{
	$invoiceElement = $SalesInvoices->addChild('Invoice');
	$invoiceElement->addChild('InvoiceNo',$invoice['InvoiceNo']);
	$documentStatus = $invoiceElement->addChild('DocumentStatus');

	$documentStatus->addChild('InvoiceStatus','N');
	$documentStatus->addChild('InvoiceStatusDate',$invoice['SystemEntryDate']);
	$documentStatus->addChild('SourceID',$sourceID);
	$documentStatus->addChild('SourceBilling','P');

	$invoiceElement->addChild('Hash','0');
	$invoiceElement->addChild('InvoiceDate',$invoice['InvoiceDate']);
	$invoiceElement->addChild('InvoiceType','FT');

	$SpecialRegimes = $invoiceElement->addChild('SpecialRegimes');
	$SpecialRegimes->addChild('SelfBillingIndicator','0');
	$SpecialRegimes->addChild('CashVATSchemeIndicator','0');
	$SpecialRegimes->addChild('ThirdPartiesBillingIndicator','0');

	$invoiceElement->addChild('SourceID',$sourceID);
	$invoiceElement->addChild('SystemEntryDate',$invoice['SystemEntryDate']);
	$invoiceElement->addChild('CustomerID',$invoice['CustomerID']);

	//lines
    $table = 'InvoiceLine';
    $field = 'InvoiceID';
    $values = array($invoice['InvoiceID']);
    $rows = array('InvoiceID', 'LineNumber', 'ProductCode', 'ProductDescription', 'Quantity', 'UnitPrice', 'UnitOfMeasure', 'CreditAmount' , 'Tax.TaxID AS TaxID', 'TaxType', 'TaxPercentage');
    $joins = array('InvoiceLine' => array('Tax', 'Product'));

    $invoiceLinesSearch = new EqualSearch($table, $field, $values, $rows, $joins);
    $invoiceLines = $invoiceLinesSearch->getResults();
    foreach($invoiceLines as &$invoiceLine){
        roundLineTotals($invoiceLine);
        setValuesAsArray('Tax', array('TaxType', 'TaxPercentage'), $invoiceLine);

       if($invoiceLine['InvoiceID'] == $invoice['InvoiceID'])
        {
        	$Line = $invoiceElement->addChild('Line');
        	$Line->addChild('LineNumber',$invoiceLine['LineNumber']);
        	$Line->addChild('ProductCode',$invoiceLine['ProductCode']);
        	$Line->addChild('ProductDescription',$invoiceLine['ProductDescription']);
        	$Line->addChild('Quantity',$invoiceLine['Quantity']);
        	$Line->addChild('UnitOfMeasure',$invoiceLine['UnitOfMeasure']);
        	$Line->addChild('UnitPrice',$invoiceLine['UnitPrice']);
        	$Line->addChild('TaxPointDate',$invoice['InvoiceDate']);
        	$Line->addChild('Description',$invoiceLine['ProductDescription']);
        	$Line->addChild('CreditAmount',$invoiceLine['CreditAmount']);
        	$TaxLine = $Line->addChild('Tax');
        	$TaxLine->addChild('TaxType',$invoiceLine['Tax']['TaxType']);
        	$TaxLine->addChild('TaxCountryRegion','PT');
        	$TaxLine->addChild('TaxCode','NOR');
        	$TaxLine->addChild('TaxPercentage',$invoiceLine['Tax']['TaxPercentage']);
        	$Line->addChild('SettlementAmount','0');

        }
    }
	$documentTotals = $invoiceElement->addChild('DocumentTotals');
	$documentTotals->addChild('TaxPayable',$invoice['TaxPayable']);
	$documentTotals->addChild('NetTotal',$invoice['NetTotal']);
	$documentTotals->addChild('GrossTotal',$invoice['GrossTotal']);
}

/****************************************************
Save to a file
****************************************************/
$dom = new DOMDocument("1.0");
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($AuditElement->asXML());

	$filename = $date . '_SAFT-PT.xml';
	if($dom->save($filename)) {
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
		readfile($filename);
		unlink($filename);
	}
?>
