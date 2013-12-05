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

$parameters['table'] = 'Customer';
$parameters['rows'] = array('customerId', 'customerTaxID', 'companyName', 'addressDetail', 'cityName', 'countryName', 'email');
$parameters['joins'] = array('Customer' => 'Country');

$result = executeSearch($parameters);

if (!$result)
    echo '[]';
else
    echo json_encode($result, JSON_NUMERIC_CHECK);