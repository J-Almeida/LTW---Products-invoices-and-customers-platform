function getParameter(urlQuery) {
    urlQuery = urlQuery.split("+").join(" ");

    var params = {};
    var tokens;
    var regex = /[?&]?([^=]+)=([^&]*)/g;

    while (tokens = regex.exec(urlQuery)) {
        params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    return params;
}

function drawCustomerDetails(customerId, content) {
    $.getJSON("./api/getCustomer.php?CustomerID=" + customerId, function(data) {
        var details = data.companyName + " (T.ID  " + data.customerTaxId +  ")<br>";
        details += data.addressDetail + "<br>" + data.postalCode + " " + data.cityName + ", " + data.countryName;
        content.html(details);
    });
}

function drawSupplierDetails(supplierId, content) {
    $.getJSON("./api/getSupplier.php?SupplierID=" + supplierId, function(data) {
        var details = data.companyName + "    (T.ID  " + data.supplierTaxId +  ")<br>";
        details += data.addressDetail + "<br>" + data.postalCode + " " + data.cityName + ", " + data.countryName;
        content.html(details);
    });
}

function getProductDetails(productCode) {
    $.ajaxSetup({
        async: false
    });
    var details = "";
    $.getJSON("./api/getProduct.php?ProductCode=" + productCode, function(data) {
        details = data;
    });

    $.ajaxSetup({
        async: true
    });
    return details;
}

function drawInvoiceStructure(invoiceData) {
    var json = JSON.parse(invoiceData);

    $("#invoiceNo").html(json.invoiceNo);
    $("#invoiceDate").html(json.invoiceDate);
    drawCustomerDetails(json.customerId, $("#invoiceToName"));
    drawSupplierDetails(json.supplierId, $("#invoiceFromName"));

    $("#invoiceCustomer").click(function() {
        window.open("customer_detailed.html?CustomerID=" + json.customerId);
    });

    var lines = "";
    for(result in json.Line) {
        var object = json.Line[result];
        var productData;
        lines += "<tr id=";
        lines += object.lineNumber;
        lines += ">";
        for(field in object) {
            if(field != "lineNumber") {
                if(field == "tax") {
                    for(taxField in object[field]) {
                        lines += "<td>";
                        lines += object[field][taxField];
                        lines += "</td>";
                    }
                }
                else if(field == "productCode") {
                    productData = getProductDetails(object[field]);
                    lines += "<td>";
                    lines += "[";
                    lines += productData.productCode;
                    lines += "] ";
                    lines += productData.productDescription;
                    lines += "</td>";
                }
                else {
                    lines += "<td>";
                    if(field == "unitPrice" || field == "creditAmount")
                        lines += "€ "; 
                    lines += object[field];
                    lines += "</td>";
                }
            }

            if(field == "quantity") {
                lines += "<td>";
                lines += productData.unitOfMeasure;
                lines += "</td>";
            }
        }
        lines += "</tr>";
    }

    $("#invoiceLines").html(lines);

//Load onClick events for table rows
var rowProd = {};
for(result in json.Line) {
    var object = json.Line[result];
    var rowID = "#" + object.lineNumber;
    var pCode = object.productCode;
    rowProd[object.lineNumber] = pCode;
    $(rowID).click(function() {
        window.open("product_detailed.html?ProductCode=" + rowProd[this.id]);
    });
}

var documentTotals = json.DocumentTotals;

$("#taxPay").html("€ " + documentTotals.taxPayable);
$("#netTotal").html("€ " + documentTotals.netTotal);
$("#grossTotal").html("€ " + documentTotals.grossTotal);

$("#invoiceFooter").html(json.invoiceNo + "     |     " + json.invoiceDate);
}

function displayInvoice(invoiceNo) {
    $("#invoice").hide();
    $.ajax("./api/getInvoice.php?InvoiceNo=" + invoiceNo, {
        async: false,
        type: "GET",
        data: "",
        success: function(data)
        {
            drawInvoiceStructure(data);
        },
        error: function(a, b, c)
        {
            console.log(a + ", " + b + ", " + c);
        }
    })

    $("#loadingInvoice").fadeOut(400, function() {
        $("#invoice").fadeIn('slow', function() {});
    });
}