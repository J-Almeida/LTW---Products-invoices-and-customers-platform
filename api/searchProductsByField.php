<?php
require_once '../bootstrap.php';
require_once 'utilities.php';
require_once 'search.php';
require_once 'authenticationUtilities.php';

if(!comparePermissions(array('read'))) {
    $error = new Error(601, 'Permission Denied');
    die( json_encode($error->getInfo()) );
}

$parameters = getSearchParametersFromURL();

$parameters['table'] = 'Product';
$parameters['rows'] = array('productCode', 'productDescription', 'unitPrice', 'unitOfMeasure');
$parameters['joins'] = array();

$result = executeSearch($parameters);

foreach($result as &$product) {
    roundProductTotals($product);
}

if (!$result)
    echo '[]';
else
    echo json_encode($result);