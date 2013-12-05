<?php
session_start();
require_once './api/authenticationUtilities.php';
$neededPermissions = array('read');
evaluateSessionPermissions($neededPermissions);
?>
<!doctype html>
<html dir="ltr" lang="en" class="no-js">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/ico" href="favicon.ico"/>

    <title>Product</title>

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script src="product_detailed.js"></script>

</head>
<body onload="displayProduct(getParameter(document.location.search).ProductCode); setProductCode()">

    <div id="loadingProduct">
        <span>Loading product</span><br>
        <img src='ajax-loader.gif' alt='loading' />
    </div>

    <div id="product" style="display: none; /*Jquery deals with showing the element after everything is loaded */">

        <div class="productTitle">
            <strong>Product</strong>
        </div>

        <header id="productHeader">
            <ul class="productInfo">
                <li>Product code: <span id="productCode"></span></li>
            </ul>
        </header>

        <section id="productDetail">
            <ul class="productDetail">
                <li>Description:
                    <p id="productDescription"></p>
                </li>

                <li>Unit Price:
                    <p id="unitPrice"></p>
                </li>

                <li>Unit of measure:
                    <p id="unitOfMeasure"></p>
                </li>
            </ul>
        </section>

        <?php
        $permissions = getSessionPermissions();
        if(!empty($permissions) && $permissions['write'] == 1) {
            echo '<form id="edit" method="get" action="./product_form.php">';
                echo '<input id="productCodeInput" type="number" name="ProductCode" style="display: none;">';
                echo '<input type="submit" value="Edit">';
            echo '</form>';
        }
        ?>
    </div>

</body>

</html>