<?php
require_once 'bootstrap.php';
require_once './api/authenticationUtilities.php';
$neededPermissions = array('write');
evaluateSessionPermissions($neededPermissions);
?>
<!doctype html>
<html dir="ltr" lang="en" class="no-js">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="invoiceStyle.css">
    <link rel="icon" type="image/ico" href="favicon.ico"/>

    <title>Invoice</title>

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script src="form.js"></script>
    <script src="invoice_form.js"></script>
    <script>
        var invoiceNo = "<?php echo ( isset( $_GET['InvoiceNo'] ) && $_GET['InvoiceNo'] != '') ? $_GET['InvoiceNo'] : '';?>";
    </script>

    <?php
    require_once('api/utilities.php');
    require_once('api/search.php');
    ?>
</head>
<body onload="getInvoice(invoiceNo); updateAllLines(); updateTotals();">

<div id="loadingInvoice">
    <span>Loading invoice</span><br>
    <img src='ajax-loader.gif' alt='loading' />
</div>

<div id="invoice" style="display: none; /*Jquery deals with showing the element after everything is loaded */">
    <form id="invoiceForm" onsubmit="submitForm('invoice'); return false;" data-action="./api/updateInvoice.php" method="POST" autocomplete="off">

        <div class="invoiceTitle">
            <strong>Invoice</strong>
        </div>

        <header id="invoiceHeader">
            <ul class="invoiceInfo">
                <li>Invoice no: <span id="invoiceNo">
                        <input id="invoiceNoInput" type="text" name="InvoiceNo" readonly
                               onclick="warnReadOnly($(this))">
                </span></li>

                <li>Invoice date: <span id="invoiceDate">
                        <input type="date" pattern="^(19|20)\d\d[- /.](0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])$" name="InvoiceDate">
                </span></li>
            </ul>
        </header>

        <section id="invoiceConcerned">
            <div class="invoiceCustomer" id="invoiceCustomer">
                <h2>Invoice To:</h2>
                <div id="invoiceTo" class="concernedInfo">
                    <select pattern="^[0-9]{1,20}$" name="CustomerID">
                        <?php
                        $search = new ListAllSearch('Customer', 'CustomerID', array(), array('*'));
                        $customers = $search->getResults();
                        foreach($customers as $customer){
                            echo '<option value='.$customer['CustomerID'].'>';
                            echo $customer['CompanyName'] . ' - Tax ID ' . $customer['CustomerTaxID'];
                            echo '</option>';
                        }
                        ?>
                    </select>
                    <div class="concerned" id="invoiceToName"></div>
                </div>
            </div>
        </section>

        <section class="invoiceFinances">
            <div class="invoiceLines">
                <table>
                    <caption>Invoice details:</caption>
                    <thead>
                    <tr>
                        <th>[code] Product</th>
                        <th>Quantity</th>
                        <th>Unit price</th>
                        <th>Credit amount</th>
                        <th>Tax type</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="invoiceLines">
                    <tr class="invoiceLine" id="1">
                        <th>
                            <select class="productCode" pattern="^[a-zA-Z0-9 \u00A0-\u018F &amp;$%!@,'#.-]{1,50}$" name="Line[1].ProductCode" onchange="updateLine($(this));">
                                <?php
                                $search = new ListAllSearch('Product', 'ProductCode', array(), array('*'));
                                $products = $search->getResults();
                                foreach($products as $product){
                                    echo '<option value='.$product['ProductCode'].' data-unitprice="'.$product['UnitPrice'].'">';
                                    echo '['. $product['ProductCode'] . '] ' . $product['ProductDescription'];
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </th>
                        <th>
                            <input class="quantity" type="number" pattern="^[0-9]{1,20}$" min="0" name="Line[1].Quantity" value="1" onchange="updateLine($(this));">
                        </th>
                        <th>
                            <input class="unitPrice" type="number" pattern="^\d*\.?\d*$" min="0" step="any" maxlength="30" name="Line[1].UnitPrice" value="1" readonly>
                        </th>
                        <th>
                            <input class="creditAmount" type="number" pattern="^\d*\.?\d*$" min="0" step="any" maxlength="30" name="Line[1].CreditAmount" value="1" readonly>
                        </th>
                        <th>
                            <select class="taxId" pattern="^[0-9]{1,20}$" name="Line[1].TaxID" onchange="updateTotals();">
                                <?php
                                $search = new ListAllSearch('Tax', 'TaxID', array(), array('*'));
                                $taxes = $search->getResults();
                                foreach($taxes as $tax){
                                    echo '<option value='.$tax['TaxID'].' data-taxpercentage="'.$tax['TaxPercentage'].'">';
                                    echo $tax['TaxType'] . ' - ' . $tax['TaxPercentage'] . '%';
                                    echo '</option>';
                                }
                                ?>
                            </select>
                        </th>
                        <th>
                            <button class="removeRow" onclick="return false;">
                                Remove
                            </button>
                        </th>
                    </tr>
                    </tbody>
                </table>
            </div><br><br>

            <button class="addRow" onclick="addRow(); return false;">
                Add line
            </button>

            <div class="invoiceTotals">
                <table>
                    <caption>Totals: </caption>
                    <tbody>
                    <tr>
                        <th>Payable tax:</th>
                        <td id="taxPay"></td>
                    </tr>

                    <tr>
                        <th>Net total:</th>
                        <td id="netTotal"></td>
                    </tr>

                    <tr>
                        <th>Gross total:</th>
                        <td id="grossTotal"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

        </section>

        <div id="submitButton">
            <input type="submit" value="Submit">
        </div>
    </form>
</div>

<br><br><div id="invoiceFooter"></div>
</body>

</html>