<?php
session_start();
require_once 'utilities.php';
require_once 'search.php';
require_once 'authenticationUtilities.php';

if(!comparePermissions(array('read'))) {
    $error = new Error(601, 'Permission Denied');
    die( json_encode($error->getInfo()) );
}

$parameters = getSearchParametersFromURL();

$parameters['table'] = 'Invoice';
$parameters['rows'] = array('InvoiceNo', 'InvoiceDate', 'taxPayable', 'netTotal' ,'GrossTotal', 'CompanyName');
$parameters['joins'] = array('Invoice' => 'Customer');

$result = executeSearch($parameters);

// round the invoice totals
foreach ($result as &$invoice) {
    roundDocumentTotals($invoice);
}

if (!$result)
    echo '[]';
else
    echo json_encode($result, JSON_NUMERIC_CHECK);