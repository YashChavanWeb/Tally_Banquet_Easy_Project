<?php
// Database connection details (adjust these as per your environment)
$dsn = 'pgsql:host=localhost;dbname=demo';
$username = 'postgres';
$password = 'krisha';


// Include the config.php file to load database credentials and connection string
// require __DIR__ . '/config.php';

try {
    // Create a new PDO instance to connect to the database
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to fetch the required data
    $sql = "
        SELECT
            m.monopoly AS service_name,
            CASE
                WHEN m.id = 1 THEN bb.pax * bb.rate
                WHEN bb.perhead = 0 THEN bb.rate
                ELSE bb.rate * bb.pax
            END AS total_amount
        FROM
            public.booking_breakups bb
        JOIN
            public.monopolies m ON m.id = bb.monopoly
        JOIN
            public.bookings bk ON bk.id = bb.bookingid
        JOIN
            public.clients cl ON cl.id = bk.client
        WHERE
            bk.id = :booking_id
    ";

    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $booking_id = 47987; // The booking ID you are interested in
    $stmt->execute();
    
    // Fetch the data
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        // Handle case where no data is returned
        die("No data found for booking ID: $booking_id");
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Create XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><TALLYMESSAGE xmlns:UDF="TallyUDF"></TALLYMESSAGE>');

// Loop through the fetched results and append ALLINVENTORYENTRIES.LIST for each row
foreach ($results as $result) {
    // Create ALLINVENTORYENTRIES.LIST for each result
    $inventoryEntries = $xml->addChild('ALLINVENTORYENTRIES.LIST');

    // Get values from the result
    $service_name = $result['service_name'];
    $stock_item_amount = $result['total_amount'];

    // STOCKITEMNAME
    $inventoryEntries->addChild('STOCKITEMNAME', $service_name); // Service name as stock item

    // Fixed elements
    $fixedElements = [
        'ISDEEMEDPOSITIVE' => 'No',
        'ISGSTASSESSABLEVALUEOVERRIDDEN' => 'No',
        'STRDISGSTAPPLICABLE' => 'No',
        'CONTENTNEGISPOS' => 'No',
        'ISLASTDEEMEDPOSITIVE' => 'No',
        'ISAUTONEGATE' => 'No',
        'ISCUSTOMSCLEARANCE' => 'No',
        'ISTRACKCOMPONENT' => 'No',
        'ISTRACKPRODUCTION' => 'No',
        'ISPRIMARYITEM' => 'No',
        'ISSCRAP' => 'No'
    ];

    foreach ($fixedElements as $key => $value) {
        $inventoryEntries->addChild($key, $value);
    }

    // AMOUNT (Set to the value of stock_item_amount from the query result)
    $inventoryEntries->addChild('AMOUNT', $stock_item_amount);

    // BATCHALLOCATIONS.LIST
    $batchAllocations = $inventoryEntries->addChild('BATCHALLOCATIONS.LIST');
    $batchAllocations->addChild('GODOWNNAME', 'Main Location');
    $batchAllocations->addChild('BATCHNAME', 'Primary Batch');
    $batchAllocations->addChild('INDENTNO', 'Not Applicable');
    $batchAllocations->addChild('ORDERNO', 'Not Applicable');
    $batchAllocations->addChild('TRACKINGNUMBER', 'Not Applicable');
    $batchAllocations->addChild('DYNAMICCSTISCLEARED', 'No');
    $batchAllocations->addChild('AMOUNT', $stock_item_amount);

    // Empty lists for BATCHALLOCATIONS.LIST
    $batchEmptyLists = [
        'ADDITIONALDETAILS.LIST',
        'VOUCHERCOMPONENTLIST.LIST'
    ];
    foreach ($batchEmptyLists as $listName) {
        $batchAllocations->addChild($listName, ' ');
    }

    // ACCOUNTINGALLOCATIONS.LIST
    $accountingAllocations = $inventoryEntries->addChild('ACCOUNTINGALLOCATIONS.LIST');

    // OLDAUDITENTRYIDS.LIST
    $oldAuditEntryIdsList = $accountingAllocations->addChild('OLDAUDITENTRYIDS.LIST');
    $oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
    $oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');

    // Accounting allocation details
    $ledger_name = $service_name; // Ledger name from database
    $accountingAllocations->addChild('LEDGERNAME', $ledger_name); // Ledger name
    $accountingAllocations->addChild('GSTCLASS', 'Not Applicable');

    $accountingFixedElements = [
        'ISDEEMEDPOSITIVE' => 'No',
        'LEDGERFROMITEM' => 'No',
        'REMOVEZEROENTRIES' => 'No',
        'ISPARTYLEDGER' => 'No',
        'GSTOVERRIDDEN' => 'No',
        'ISGSTASSESSABLEVALUEOVERRIDDEN' => 'No',
        'STRDISGSTAPPLICABLE' => 'No',
        'STRDGSTISPARTYLEDGER' => 'No',
        'STRDGSTISDUTYLEDGER' => 'No',
        'CONTENTNEGISPOS' => 'No',
        'ISLASTDEEMEDPOSITIVE' => 'No',
        'ISCAPVATTAXALTERED' => 'No',
        'ISCAPVATNOTCLAIMED' => 'No'
    ];

    foreach ($accountingFixedElements as $key => $value) {
        $accountingAllocations->addChild($key, $value);
    }

    $accountingAllocations->addChild('AMOUNT', $stock_item_amount);

    // Empty lists for ACCOUNTINGALLOCATIONS.LIST
    $accountingEmptyLists = [
        'SERVICETAXDETAILS.LIST', 'BANKALLOCATIONS.LIST', 'BILLALLOCATIONS.LIST', 
        'INTERESTCOLLECTION.LIST', 'OLDAUDITENTRIES.LIST', 'ACCOUNTAUDITENTRIES.LIST', 
        'AUDITENTRIES.LIST', 'INPUTCRALLOCS.LIST', 'DUTYHEADDETAILS.LIST', 
        'EXCISEDUTYHEADDETAILS.LIST', 'RATEDETAILS.LIST', 'SUMMARYALLOCS.LIST', 
        'CENVATDUTYALLOCATIONS.LIST', 'STPYMTDETAILS.LIST', 'EXCISEPAYMENTALLOCATIONS.LIST', 
        'TAXBILLALLOCATIONS.LIST', 'TAXOBJECTALLOCATIONS.LIST', 'TDSEXPENSEALLOCATIONS.LIST', 
        'VATSTATUTORYDETAILS.LIST', 'COSTTRACKALLOCATIONS.LIST', 'REFVOUCHERDETAILS.LIST', 
        'INVOICEWISEDETAILS.LIST', 'VATITCDETAILS.LIST', 'ADVANCETAXDETAILS.LIST', 
        'TAXTYPEALLOCATIONS.LIST'
    ];

    foreach ($accountingEmptyLists as $listName) {
        $accountingAllocations->addChild($listName, ' ');
    }

    // Final empty lists for ALLINVENTORYENTRIES.LIST
    $finalEmptyLists = [
        'DUTYHEADDETAILS.LIST', 'RATEDETAILS.LIST', 'SUPPLEMENTARYDUTYHEADDETAILS.LIST', 
        'TAXOBJECTALLOCATIONS.LIST', 'REFVOUCHERDETAILS.LIST', 'EXCISEALLOCATIONS.LIST', 
        'EXPENSEALLOCATIONS.LIST'
    ];

    foreach ($finalEmptyLists as $listName) {
        $inventoryEntries->addChild($listName, ' ');
    }
}



$emptylists2 = [
    'CONTRITRANS', 'EWAYBILLERRORLIST', 
    'IRNERRORLIST', 'HARYANAVAT', 'SUPPLEMENTARYDUTYHEADDETAILS', 
    'INVOICEDELNOTES', 'INVOICEORDERLIST', 'INVOICEINDENTLIST', 
    'ATTENDANCEENTRIES', 'ORIGINVOICEDETAILS', 'INVOICEEXPORTLIST', 
    'GST', 'STKJRNLADDLCOSTDETAILS', 'PAYROLLMODEOFPAYMENT', 
    'ATTDRECORDS', 'GSTEWAYCONSIGNORADDRESS', 'GSTEWAYCONSIGNEEADDRESS', 
    'TEMPGSTRATEDETAILS', 'TEMPGSTADVADJUSTED', 'GSTBUYERADDRESS', 
    'GSTCONSIGNEEADDRESS', 'INTERESTCOLLECTION', 'OLDAUDITENTRIES', 
    'ACCOUNTAUDITENTRIES', 'AUDITENTRIES', 'INPUTCRALLOCS', 
    'DUTYHEADDETAILS', 'EXCISEDUTYHEADDETAILS', 'RATEDETAILS', 
    'SUMMARYALLOCS', 'CENVATDUTYALLOCATIONS', 'STPYMTDETAILS', 
    'EXCISEPAYMENTALLOCATIONS', 'TAXBILLALLOCATIONS', 
    'TAXOBJECTALLOCATIONS', 'TDSEXPENSEALLOCATIONS', 
    'VATSTATUTORYDETAILS', 'COSTTRACKALLOCATIONS', 'REFVOUCHERDETAILS', 
    'INVOICEWISEDETAILS', 'VATITCDETAILS', 'ADVANCETAXDETAILS', 
    'TAXTYPEALLOCATIONS', 'EWAYBILLDETAILS.LIST', 'EXCLUDEDTAXATIONS.LIST',
    'OLDAUDITENTRIES.LIST', 'ACCOUNTAUDITENTRIES.LIST', 'AUDITENTRIES.LIST', 
    'DUTYHEADDETAILS.LIST', 'GSTADVADJDETAILS.LIST', 'CONTRITRANS.LIST', 
    'EWAYBILLERRORLIST.LIST', 'IRNERRORLIST.LIST', 'HARYANAVAT.LIST', 
    'SUPPLEMENTARYDUTYHEADDETAILS.LIST', 'INVOICEDELNOTES.LIST', 
    'INVOICEORDERLIST.LIST', 'INVOICEINDENTLIST.LIST', 'ATTENDANCEENTRIES.LIST', 
    'ORIGINVOICEDETAILS.LIST', 'INVOICEEXPORTLIST.LIST',
    'GST.LIST', 
    'STKJRNLADDLCOSTDETAILS.LIST',
    'PAYROLLMODEOFPAYMENT.LIST', 
    'ATTDRECORDS.LIST', 
    'GSTEWAYCONSIGNORADDRESS.LIST', 
    'GSTEWAYCONSIGNEEADDRESS.LIST', 
    'TEMPGSTRATEDETAILS.LIST', 
    'TEMPGSTADVADJUSTED.LIST', 
    'GSTBUYERADDRESS.LIST', 
    'GSTCONSIGNEEADDRESS.LIST',
    'SERVICETAXDETAILS.LIST', 
    'BANKALLOCATIONS.LIST', 
    'INTERESTCOLLECTION.LIST', 
    'STBILLCATEGORIES.LIST'
];


foreach ($emptylists2 as $listName) {
    $xml->addChild($listName, ' ');
}


// Set the Content-Type header to XML
header('Content-Type: application/xml; charset=utf-8');

// Output the XML with formatting
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();   
?>
