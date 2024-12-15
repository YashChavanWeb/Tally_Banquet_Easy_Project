<?php
// code
$companyName = "ABC Pvt Ltd";
$gstRegistrationType = "Regular";
$gstRegistration = "ABC Pvt Ltd";
$country = "India";
$sales_narration = "This is the Bill that Yash Chavan has to pay to Banquet Easy";
$special = "&#4;"; // The HTML entity
$decoded = html_entity_decode($special, ENT_QUOTES, 'UTF-8'); // Decode the entity
$ledger_name = "Banquet Sales";
$total_sales_amount = "0";
$bill_type = "New Ref";

//Dynamic data for receipt 
$voucher_receipt_id = "00000002";
$voucher_receipt_Key = "00000004";
$guId_receipt = "00000002";
$billType_receipt = "Agst Ref";
$amount_receipt = "0";
$amount_minus_receipt = "0";

$counter = 1;
$counter1 = 4;
$updater = 1;
$sales_voucherNumber = 1;
$receipt_voucherNumber = 1;

$prefix1 = 'ef1532b1-c551-4b3f-ac45-04402e1668cc-';
$prefix2 = 'ef1532b1-c551-4b3f-ac45-04402e1668cc-0000b146:';

// Escape special characters to be safe in XML context
$escapedDecoded = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');

// Dynamic data (You can change these values as per your requirement)
$voucherType = "sales";
$billType = "New Ref";
$name = 'BQ-1';
$ledgerName = "Banquet Sales";

// Database connection
$host = 'localhost';
$dbname = 'Tallydb';
$username = 'postgres';
$password = '12345678';

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>');

// Add HEADER node
$header = $xml->addChild('HEADER');
$header->addChild('TALLYREQUEST', 'Import Data');

// Add BODY node
$body = $xml->addChild('BODY');

// Add IMPORTDATA node
$importData = $body->addChild('IMPORTDATA');

// Add REQUESTDESC node
$requestDesc = $importData->addChild('REQUESTDESC');
$requestDesc->addChild('REPORTNAME', 'All Masters');

// Add STATICVARIABLES node
$staticVariables = $requestDesc->addChild('STATICVARIABLES');
$staticVariables->addChild('SVCURRENTCOMPANY', 'ABC Pvt Ltd');

// Add REQUESTDATA node
$requestData = $importData->addChild('REQUESTDATA');

$banquetID = 1176;
try {

    //SALES VOUCHER 
    
    // Create a PostgreSQL database connection
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL query to fetch data from bookings table
    $stmt = $pdo->prepare("
        SELECT 
            b.id AS booking_id,
            b.reg_date AS booking_date,
            c.id AS client_id,
            c.fullname AS client_name,
            b.banquet AS banquet_id,
            b.total AS total_amount
        FROM 
            public.bookings b
        INNER JOIN 
            public.clients c ON b.client = c.id
        WHERE 
            b.reg_date BETWEEN '2024-11-17 00:00:00' AND '2024-11-19 00:00:00'
            AND b.banquet = :banquetID
        ORDER BY 
            b.reg_date DESC
        LIMIT 3;
    ");

    // Bind the global banquet_id to the query
    $stmt->bindParam(':banquetID', $banquetID, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all rows from the query result
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no rows are found, throw an exception
    if (empty($rows)) {
        throw new Exception("No data found in the database.");
    }

    foreach ($rows as $row) {

        $banquet_id = $row['banquet_id'];
        $client_id = $row['client_id'];
        $default_booking_id = $row['booking_id'];
        $partyName = $row['client_name'];
        $name = "BQ-" . $client_id;

try {
    // Create PDO instance and connect to database
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query 1: Fetch Booking Details
    $stmt1 = $pdo->prepare("SELECT 
                                clients.fullname, 
                                bookings.total_paid, 
                                bookings.datex,
                                bookings.id AS booking_id
                            FROM bookings
                            JOIN clients ON bookings.client = clients.id
                            WHERE bookings.banquet = :banquet_id
                            AND bookings.total_paid != 0
                            AND bookings.client = :client_id");
    $stmt1->execute([':banquet_id' => $banquet_id, ':client_id' => $client_id]);
    $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);

    // Use the booking ID from the first query or the default if null
    $booking_id = $result1 ? $result1['booking_id'] : $default_booking_id;

    // Query 2: Detailed Bill Amount Calculation
    $stmt2 = $pdo->prepare("SELECT
                                cl.fullname AS client_fullname,
                                bk.reg_date AS event_date,
                                SUM(
                                    (CASE
                                        WHEN m.id = 1 THEN bk.pax * bb.rate
                                        ELSE
                                            CASE
                                                WHEN bb.perhead = 0 THEN bb.rate
                                                ELSE bb.rate * bb.pax
                                            END
                                    END)
                                ) AS total_bill_amount
                            FROM
                                public.bookings bk
                            JOIN
                                public.booking_breakups bb ON bk.id = bb.bookingid
                            JOIN
                                public.monopolies m ON m.id = bb.monopoly
                            JOIN
                                public.clients cl ON cl.id = bk.client
                            WHERE
                                bk.id = :booking_id
                            GROUP BY
                                cl.fullname, bk.reg_date
                            ORDER BY
                                bk.reg_date DESC");

    $stmt2->execute([':booking_id' => $booking_id]);
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Update dynamic values from query results
    if ($result1) {
        // Convert datex from database to YYYYMMDD format
        $originalDate = $result1['datex'];
        $date = DateTime::createFromFormat('Y-m-d', $originalDate)->format('Ymd');
    } else {
        $date = "20240401"; // Default date
    }

    if ($result2) {
        $total_sales_amount = -$result2['total_bill_amount'];
        $partyName = $result2['client_fullname'];
    } else {
        $total_sales_amount = "-25000.00";
    }

// Start of the Sales voucher
$tallyMessage = $requestData->addChild('TALLYMESSAGE');
$tallyMessage->addAttribute('xmlns:UDF', 'TallyUDF');

// Add VOUCHER node
$vch = $tallyMessage->addChild('VOUCHER');

$voucher_receipt_id = $prefix1 . str_pad($counter, 8, '0', STR_PAD_LEFT);
$voucher_receipt_key = $prefix2 . str_pad($counter1, 8, '0', STR_PAD_LEFT);

$vch->addAttribute('REMOTEID', $voucher_receipt_id);
$vch->addAttribute('VCHKEY', $voucher_receipt_key);

$vch->addAttribute('VCHTYPE', 'Sales');
$vch->addAttribute('ACTION', 'Create');
$vch->addAttribute('OBJVIEW', 'Invoice Voucher View');

// OLDAUDITENTRYIDS.LIST node
$oldAuditEntryIdsList = $vch->addChild('OLDAUDITENTRYIDS.LIST');
$oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
$oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');

// Add core voucher details
$vch->addChild('DATE', $date);
$vch->addChild('VCHSTATUSDATE', $date);
$vch->addChild('GUID', $voucher_receipt_id);
$vch->addChild('GSTREGISTRATIONTYPE', $gstRegistrationType);
$vch->addChild('VATDEALERTYPE', $gstRegistrationType);
$vch->addChild('NARRATION', $sales_narration);
$vch->addChild('COUNTRYOFRESIDENCE', $country);
$vch->addChild('PARTYNAME', $partyName);


// GST Registration
$gstRegistrationNode = $vch->addChild('GSTREGISTRATION', $gstRegistration);
$gstRegistrationNode->addAttribute('TAXTYPE', 'GST');
$gstRegistrationNode->addAttribute('TAXREGISTRATION', '');

$vch->addChild('VOUCHERTYPENAME', 'Sales');
$vch->addChild('PARTYLEDGERNAME', $partyName);

$vch->addChild('VOUCHERNUMBER', $sales_voucherNumber);
$sales_voucherNumber++;

$vch->addChild('BASICBUYERNAME', $partyName);
$vch->addChild('CMPGSTREGISTRATIONTYPE', $gstRegistrationType);
$vch->addChild('PARTYMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEEMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEECOUNTRYNAME', $country);
$vch->addChild('BASICBASEPARTYNAME', $partyName);

$vch->addChild('NUMBERINGSTYLE', 'Auto Retain');
$vch->addChild('CSTFORMISSUETYPE', $escapedDecoded . ' Not Applicable');
$vch->addChild('CSTFORMRECVTYPE', $escapedDecoded . ' Not Applicable');
$vch->addChild('FBTPAYMENTTYPE', 'Default');
$vch->addChild('PERSISTEDVIEW', 'Invoice Voucher View');
$vch->addChild('VCHSTATUSTAXADJUSTMENT', 'Default');
$vch->addChild('VCHSTATUSVOUCHERTYPE', $voucherType);
$vch->addChild('VCHSTATUSTAXUNIT', $companyName);
$vch->addChild('VCHGSTCLASS', $escapedDecoded . ' Not Applicable');
$vch->addChild('VCHENTRYMODE', 'Item Invoice');

// Status flags
$statusFlags = [
    'DIFFACTUALQTY', 'ISMSTFROMSYNC', 'ISDELETED', 'ISSECURITYONWHENENTERED', 
    'ASORIGINAL', 'AUDITED', 'ISCOMMONPARTY', 'FORJOBCOSTING', 
    'ISOPTIONAL','EFFECTIVEDATE', 'USEFOREXCISE', 'ISFORJOBWORKIN', 'ALLOWCONSUMPTION', 
    'USEFORINTEREST', 'USEFORGAINLOSS', 'USEFORGODOWNTRANSFER', 
    'USEFORCOMPOUND', 'USEFORSERVICETAX', 'ISREVERSECHARGEAPPLICABLE', 
    'ISSYSTEM', 'ISFETCHEDONLY', 'ISGSTOVERRIDDEN', 'ISCANCELLED', 
    'ISONHOLD', 'ISSUMMARY', 'ISECOMMERCESUPPLY', 'ISBOENOTAPPLICABLE', 
    'ISGSTSECSEVENAPPLICABLE', 'IGNOREEINVVALIDATION', 
    'CMPGSTISOTHTERRITORYASSESSEE', 'PARTYGSTISOTHTERRITORYASSESSEE'
];

foreach ($statusFlags as $flag) {
    $vch->addChild($flag, 'No');
}

// Additional specific flags
$vch->addChild('IRNJSONEXPORTED', 'No');
$vch->addChild('IRNCANCELLED', 'No');
$vch->addChild('IGNOREGSTCONFLICTINMIG', 'No');
$vch->addChild('ISOPBALTRANSACTION', 'No');
$vch->addChild('IGNOREGSTFORMATVALIDATION', 'No');
$vch->addChild('ISELIGIBLEFORITC', 'Yes');
$vch->addChild('UPDATESUMMARYVALUES', 'No');
$vch->addChild('ISEWAYBILLAPPLICABLE', 'No');
$vch->addChild('ISDELETEDRETAINED', 'No');
$vch->addChild('ISNULL', 'No');

// More flag nodes as in the original XML
$additionalFlags = [
    'ISEXCISEVOUCHER','EXCISETAXOVERRIDE','USEFORTAXUNITTRANSFER','ISEXER1NOPOVERWRITE','ISEXF2NOPOVERWRITE',
    'ISEXER3NOPOVERWRITE','IGNOREPOSVALIDATION','EXCISEOPENING','USEFORFINALPRODUCTION',
    'ISTDSOVERRIDDEN', 'ISTCSOVERRIDDEN', 'ISTDSTCSCASHVCH', 
    'INCLUDEADVPYMTVCH', 'ISSUBWORKSCONTRACT', 'ISVATOVERRIDDEN', 
    'IGNOREORIGVCHDATE', 'ISVATPAIDATCUSTOMS', 'ISDECLAREDTOCUSTOMS', 
    'VATADVANCEPAYMENT', 'VATADVPAY', 'ISCSTDELCAREDGOODSSALES', 
    'ISVATRESTAXINV', 'ISSERVICETAXOVERRIDDEN', 'ISISDVOUCHER', 
    'ISEXCISEOVERRIDDEN', 'ISEXCISESUPPLYVCH', 'GSTNOTEXPORTED', 
    'IGNOREGSTINVALIDATION', 'ISGSTREFUND', 'OVRDNEWAYBILLAPPLICABILITY', 
    'ISVATPRINCIPALACCOUNT', 'VCHSTATUSISVCHNUMUSED', 'VCHGSTSTATUSISINCLUDED', 
    'VCHGSTSTATUSISUNCERTAIN', 'VCHGSTSTATUSISEXCLUDED', 'VCHGSTSTATUSISAPPLICABLE', 
    'VCHGSTSTATUSISGSTR2BRECONCILED', 'VCHGSTSTATUSISGSTR2BONLYINPORTAL', 
    'VCHGSTSTATUSISGSTR2BONLYINBOOKS', 'VCHGSTSTATUSISGSTR2BMISMATCH', 
    'VCHGSTSTATUSISGSTR2BINDIFFPERIOD', 'VCHGSTSTATUSISRETEFFDATEOVERRDN', 
    'VCHGSTSTATUSISOVERRDN', 'VCHGSTSTATUSISSTATINDIFFDATE', 
    'VCHGSTSTATUSISRETINDIFFDATE', 'VCHGSTSTATUSMAINSECTIONEXCLUDED', 
    'VCHGSTSTATUSISBRANCHTRANSFEROUT', 'VCHGSTSTATUSISSYSTEMSUMMARY', 
    'VCHSTATUSISUNREGISTEREDRCM', 'VCHSTATUSISOPTIONAL', 'VCHSTATUSISCANCELLED', 
    'VCHSTATUSISDELETED', 'VCHSTATUSISOPENINGBALANCE', 'VCHSTATUSISFETCHEDONLY', 
    'PAYMENTLINKHASMULTIREF', 'ISSHIPPINGWITHINSTATE', 'ISOVERSEASTOURISTTRANS', 
    'ISDESIGNATEDZONEPARTY', 'HASCASHFLOW', 'ISPOSTDATED', 'USETRACKINGNUMBER', 'ISINVOICE',
    'MFGJOURNAL', 'HASDISCOUNTS', 'ASPAYSLIP', 'ISCOSTCENTRE', 'ISSTXNONREALIZEDVCH', 
    'ISEXCISEMANUFACTURERON', 'ISBLANKCHEQUE', 'ISVOID', 'ORDERLINESTATUS', 
    'VATISAGNSTCANCSALES', 'VATISPURCEXEMPTED', 'ISVATRESTAXINVOICE', 
    'VATISASSESABLECALCVCH', 'ISVATDUTYPAID', 'ISDELIVERYSAMEASCONSIGNEE', 'ISDISPATCHSAMEASCONSIGNOR', 
    'ISDELETEDVCHRETAINED', 'CHANGEVCHMODE', 'RESETIRNQRCODE'
];

// Loop through the flags and add them to the object
foreach ($additionalFlags as $flag) {
    $value = ($flag === 'ISVATDUTYPAID' || $flag === 'ISINVOICE') ? 'Yes' : 'No';
    $vch->addChild($flag, $value);
}

$tagsWithValues = [
    'ALTERID' => '1',
    'MASTERID' => '1',
    'VOUCHERKEY' => '194914205827080',
    'VOUCHERRETAINKEY' => '1',
    'VOUCHERNUMBERSERIES' => 'Default'
];

foreach ($tagsWithValues as $tag => $value) {
    $vch->addChild($tag, $value);
}


// Empty list elements
$emptyLists = [
    'EWAYBILLDETAILS.LIST', 'EXCLUDEDTAXATIONS.LIST',
    'OLDAUDITENTRIES.LIST', 'ACCOUNTAUDITENTRIES.LIST', 'AUDITENTRIES.LIST', 
    'DUTYHEADDETAILS.LIST', 'GSTADVADJDETAILS.LIST'
];


foreach ($emptyLists as $listName) {
    $vch->addChild($listName , ' ');
}

$counter++;
$counter1++;

try {

    // Query to fetch the required data
    $sql = "
        SELECT
			CASE
		        WHEN bm.tally_ledger_name IS NOT NULL THEN bm.tally_ledger_name
		        ELSE m.monopoly
		    END AS service_name,
            CASE
                WHEN m.id = 1 THEN bb.pax * bb.rate
                WHEN bb.perhead = 0 THEN bb.rate
                ELSE bb.rate * bb.pax
            END AS total_amount,
			bm.tally_ledger_name as ledger
        FROM
            public.booking_breakups bb
        JOIN
            public.monopolies m ON m.id = bb.monopoly
        JOIN
            public.bookings bk ON bk.id = bb.bookingid
        JOIN
            public.clients cl ON cl.id = bk.client
		JOIN 
			banquet_monopolies bm on bm.monopoly = m.id and bm.banquet = bk.banquet
        WHERE
            bk.id = :booking_id;
    ";

    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':booking_id', $default_booking_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch the data
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // if (empty($results)) {
    //     // Handle case where no data is returned
    //     die("No data found for booking ID: $default_booking_id");
    //     break;
    // }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}


// Loop through the fetched results and append ALLINVENTORYENTRIES.LIST for each row
foreach ($results as $result) {
    // Create ALLINVENTORYENTRIES.LIST for each result
    $inventoryEntries = $vch->addChild('ALLINVENTORYENTRIES.LIST');

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
    $batchAllocations->addChild('INDENTNO', $escapedDecoded . ' Not Applicable');
    $batchAllocations->addChild('ORDERNO', $escapedDecoded . ' Not Applicable');
    $batchAllocations->addChild('TRACKINGNUMBER', $escapedDecoded . ' Not Applicable');
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
    // Ledger name from database
    $accountingAllocations->addChild('LEDGERNAME', $ledger_name); // Ledger name
    $accountingAllocations->addChild('GSTCLASS', $escapedDecoded . ' Not Applicable');

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
        'CONTRITRANS.LIST', 
        'EWAYBILLERRORLIST.LIST', 'IRNERRORLIST.LIST', 'HARYANAVAT.LIST', 
        'SUPPLEMENTARYDUTYHEADDETAILS.LIST', 'INVOICEDELNOTES.LIST', 
        'INVOICEORDERLIST.LIST', 'INVOICEINDENTLIST.LIST', 'ATTENDANCEENTRIES.LIST', 
        'ORIGINVOICEDETAILS.LIST', 'INVOICEEXPORTLIST.LIST'
    ];

    foreach ($emptylists2 as $listName) {
        $vch->addChild($listName , ' ');
    }
    // Ledger Entries
    $ledgerEntries = $vch->addChild('LEDGERENTRIES.LIST');

    $oldAuditIds = $ledgerEntries->addChild('OLDAUDITENTRYIDS.LIST');
    $oldAuditIds->addAttribute('TYPE', 'Number');
    $oldAuditIds->addChild('OLDAUDITENTRYIDS', '-1');

    $ledgerEntries->addChild('LEDGERNAME', $partyName);
    $ledgerEntries->addChild('GSTCLASS', $escapedDecoded . ' Not Applicable');
    $ledgerEntries->addChild('ISDEEMEDPOSITIVE', 'Yes');
    $ledgerEntries->addChild('LEDGERFROMITEM', 'No');
    $ledgerEntries->addChild('REMOVEZEROENTRIES', 'No');
    $ledgerEntries->addChild('ISPARTYLEDGER', 'Yes');
    $ledgerEntries->addChild('GSTOVERRIDDEN', 'No');
    $ledgerEntries->addChild('ISGSTASSESSABLEVALUEOVERRIDDEN', 'No');
    $ledgerEntries->addChild('STRDISGSTAPPLICABLE', 'No');
    $ledgerEntries->addChild('STRDGSTISPARTYLEDGER', 'No');
    $ledgerEntries->addChild('STRDGSTISDUTYLEDGER', 'No');
    $ledgerEntries->addChild('CONTENTNEGISPOS', 'No');
    $ledgerEntries->addChild('ISLASTDEEMEDPOSITIVE', 'Yes');
    $ledgerEntries->addChild('ISCAPVATTAXALTERED', 'No');
    $ledgerEntries->addChild('ISCAPVATNOTCLAIMED', 'No');

    $ledgerEntries->addChild('AMOUNT', $total_sales_amount);
    
    $servicetaxList = $ledgerEntries->addChild('SERVICETAXDETAILS.LIST', ' ');
    $bankAllocationsList = $ledgerEntries->addChild('BANKALLOCATIONS.LIST',' ');
    // Bill Allocations
    $billAllocations = $ledgerEntries->addChild('BILLALLOCATIONS.LIST');
    $billAllocations->addChild('NAME', $name);
    $billAllocations->addChild('BILLTYPE', $bill_type);
    $billAllocations->addChild('TDSDEDUCTEEISSPECIALRATE', 'No');   
    $billAllocations->addChild('AMOUNT', $total_sales_amount);

    $billAllocations->addChild('INTERESTCOLLECTION.LIST', ' ');  
    $billAllocations->addChild('STBILLCATEGORIES.LIST', ' ');  
    

    $emptylists4 = [
        "INTERESTCOLLECTION.LIST",
        "OLDAUDITENTRIES.LIST",
        "ACCOUNTAUDITENTRIES.LIST",
        "AUDITENTRIES.LIST",
        "INPUTCRALLOCS.LIST",
        "DUTYHEADDETAILS.LIST",
        "EXCISEDUTYHEADDETAILS.LIST",
        "RATEDETAILS.LIST",
        "SUMMARYALLOCS.LIST",
        "CENVATDUTYALLOCATIONS.LIST",
        "STPYMTDETAILS.LIST",
        "EXCISEPAYMENTALLOCATIONS.LIST",
        "TAXBILLALLOCATIONS.LIST",
        "TAXOBJECTALLOCATIONS.LIST",
        "TDSEXPENSEALLOCATIONS.LIST",
        "VATSTATUTORYDETAILS.LIST",
        "COSTTRACKALLOCATIONS.LIST",
        "REFVOUCHERDETAILS.LIST",
        "INVOICEWISEDETAILS.LIST",
        "VATITCDETAILS.LIST",
        "ADVANCETAXDETAILS.LIST",
        "TAXTYPEALLOCATIONS.LIST"
    ];

    foreach ($emptylists4 as $listName) {
        $ledgerEntries ->addChild($listName, ' '); // Adding each tag with an empty string as its value
        }

    $emptylists3 = [
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
];

foreach ($emptylists3 as $listName) {
    $vch->addChild($listName , ' ');
}


try {
    // Create a PostgreSQL database connection
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL query to fetch data from public.booking_payments
    $stmt = $pdo->prepare("
        SELECT
          c.fullname AS client_fullname,
          bp.amount AS payment_amount,
          bp.balance AS remaining_balance,
          bp.dop AS payment_date,
          bp.modex AS payment_mode,
          bp.txnid AS transaction_id,
          bp.cheque_no AS cheque_number,
          bp.cheque_bank AS cheque_bank,
          bp.cheque_date AS cheque_date,
          bp.commentsx AS comments,
          bp.receipt_id AS receipt_id,
          bp.banquet_receipt_no AS banquet_receipt_number
        FROM 
          public.booking_payments bp
          INNER JOIN
            public.bookings b ON bp.booking = b.id
          INNER JOIN
            public.clients c ON b.client = c.id
        WHERE
            bp.booking = :booking_id;
    ");

    $stmt->bindParam(':booking_id', $default_booking_id, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch all rows
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no rows found, throw an exception
    // if (empty($rows)) {
    //     throw new Exception("No data found in the database");
    // }

    // Format dates and process rows
    foreach ($rows as &$row) {
        // Format `dop` (payment_date) as YYYYMMDD (only the date part, ignoring time)
        if (!empty($row['payment_date'])) {
            $paymentDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['payment_date']);
            if ($paymentDate) {
                $row['payment_date'] = $paymentDate->format('Ymd');
            } else {
                // Handle invalid date format if needed (e.g., logging or setting to a default value)
                $row['payment_date'] = 'Invalid Date';
            }
        }

        // Format `cheque_date` as YYYYMMDD (only the date part, ignoring time)
        if (!empty($row['cheque_date'])) {
            $chequeDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['cheque_date']);
            if ($chequeDate) {
                $row['cheque_date'] = $chequeDate->format('Ymd');
            } else {
                // Handle invalid date format if needed (e.g., logging or setting to a default value)
                $row['cheque_date'] = 'Invalid Date';
            }
        }
    }


    foreach ($rows as $row) {

        // Start of RECEIPTS
        $tallyMessage = $requestData->addChild('TALLYMESSAGE');
        $tallyMessage->addAttribute('xmlns:UDF', 'TallyUDF');

        // Add VOUCHER node
        $vch = $tallyMessage->addChild('VOUCHER');

        $voucher_receipt_id = $prefix1 . str_pad($counter, 8, '0', STR_PAD_LEFT);
        $voucher_receipt_key = $prefix2 . str_pad($counter1, 8, '0', STR_PAD_LEFT);

        $vch->addAttribute('REMOTEID', $voucher_receipt_id);
        $vch->addAttribute('VCHKEY', $voucher_receipt_key);

        $counter++;
        $counter1++;

        $vch->addAttribute('VCHTYPE', 'Receipt');
        $vch->addAttribute('ACTION', 'Create');
        $vch->addAttribute('OBJVIEW', 'Accounting Voucher View');


        // OLDAUDITENTRYIDS.LIST node
        $oldAuditEntryIdsList = $vch->addChild('OLDAUDITENTRYIDS.LIST', null);
        $oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
        $oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');

        // Adding other elements from original script
        $vch->addChild('DATE', $row['payment_date']);
        $vch->addChild('VCHSTATUSDATE', $row['payment_date']);

        $vch->addChild('GUID', $voucher_receipt_id);

        $vch->addChild('NARRATION', "Receipt ID: " . $row['receipt_id']);

        // Adding GSTREGISTRATION with additional attributes
        $gstRegistration = $vch->addChild('GSTREGISTRATION', 'ABC Pvt Ltd');
        $gstRegistration->addAttribute('TAXTYPE', 'GST');
        $gstRegistration->addAttribute('TAXREGISTRATION', '');

        // Add additional fields related to the voucher
        $vch->addChild('VOUCHERTYPENAME', 'Receipt');
        $vch->addChild('PARTYLEDGERNAME', $row['client_fullname']);

        $vch->addChild('VOUCHERNUMBER', $receipt_voucherNumber);
        $receipt_voucherNumber++;

        $vch->addChild('CMPGSTREGISTRATIONTYPE', 'Regular');
        $vch->addChild('NUMBERINGSTYLE', 'Auto Retain');
        $vch->addChild('CSTFORMISSUETYPE', $escapedDecoded . ' Not Applicable');
        $vch->addChild('CSTFORMRECVTYPE', $escapedDecoded . ' Not Applicable');
        $vch->addChild('FBTPAYMENTTYPE', 'Default');
        $vch->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $vch->addChild('VCHSTATUSTAXADJUSTMENT', 'Default');
        $vch->addChild('VCHSTATUSVOUCHERTYPE', 'Receipt');
        $vch->addChild('VCHSTATUSTAXUNIT', $companyName);
        $vch->addChild('VCHGSTCLASS', $escapedDecoded . ' Not Applicable');

        // Add more flags and status elements
        $vch->addChild('DIFFACTUALQTY', 'No');
        $vch->addChild('ISMSTFROMSYNC', 'No');
        $vch->addChild('ISDELETED', 'No');
        $vch->addChild('ISSECURITYONWHENENTERED', 'No');
        $vch->addChild('ASORIGINAL', 'No');
        $vch->addChild('AUDITED', 'No');
        $vch->addChild('ISCOMMONPARTY', 'No');
        $vch->addChild('FORJOBCOSTING', 'No');
        $vch->addChild('ISOPTIONAL', 'No');
        $vch->addChild('EFFECTIVEDATE', $row['payment_date']);
        $vch->addChild('USEFOREXCISE', 'No');
        $vch->addChild('ISFORJOBWORKIN', 'No');
        $vch->addChild('ALLOWCONSUMPTION', 'No');
        $vch->addChild('USEFORINTEREST', 'No');
        $vch->addChild('USEFORGAINLOSS', 'No');
        $vch->addChild('USEFORGODOWNTRANSFER', 'No');
        $vch->addChild('USEFORCOMPOUND', 'No');
        $vch->addChild('USEFORSERVICETAX', 'No');
        $vch->addChild('ISREVERSECHARGEAPPLICABLE', 'No');
        $vch->addChild('ISSYSTEM', 'No');
        $vch->addChild('ISFETCHEDONLY', 'No');
        $vch->addChild('ISGSTOVERRIDDEN', 'No');
        $vch->addChild('ISCANCELLED', 'No');
        $vch->addChild('ISONHOLD', 'No');
        $vch->addChild('ISSUMMARY', 'No');
        $vch->addChild('ISECOMMERCESUPPLY', 'No');
        $vch->addChild('ISBOENOTAPPLICABLE', 'No');
        $vch->addChild('ISGSTSECSEVENAPPLICABLE', 'No');
        $vch->addChild('IGNOREEINVVALIDATION', 'No');
        $vch->addChild('CMPGSTISOTHTERRITORYASSESSEE', 'No');
        $vch->addChild('PARTYGSTISOTHTERRITORYASSESSEE', 'No');
        $vch->addChild('IRNJSONEXPORTED', 'No');
        $vch->addChild('IRNCANCELLED', 'No');
        $vch->addChild('IGNOREGSTCONFLICTINMIG', 'No');
        $vch->addChild('ISOPBALTRANSACTION', 'No');
        $vch->addChild('IGNOREGSTFORMATVALIDATION', 'No');
        $vch->addChild('ISELIGIBLEFORITC', 'Yes');
        $vch->addChild('UPDATESUMMARYVALUES', 'No');
        $vch->addChild('ISEWAYBILLAPPLICABLE', 'No');
        $vch->addChild('ISDELETEDRETAINED', 'No');
        $vch->addChild('ISNULL', 'No');
        $vch->addChild('ISEXCISEVOUCHER', 'No');
        $vch->addChild('EXCISETAXOVERRIDE', 'No');
        $vch->addChild('USEFORTAXUNITTRANSFER', 'No');
        $vch->addChild('ISEXER1NOPOVERWRITE', 'No');
        $vch->addChild('ISEXF2NOPOVERWRITE', 'No');
        $vch->addChild('ISEXER3NOPOVERWRITE', 'No');
        $vch->addChild('IGNOREPOSVALIDATION', 'No');
        $vch->addChild('EXCISEOPENING', 'No');
        $vch->addChild('USEFORFINALPRODUCTION', 'No');
        $vch->addChild('ISTDSOVERRIDDEN', 'No');
        $vch->addChild('ISTCSOVERRIDDEN', 'No');
        $vch->addChild('ISTDSTCSCASHVCH', 'No');
        $vch->addChild('INCLUDEADVPYMTVCH', 'No');
        $vch->addChild('ISSUBWORKSCONTRACT', 'No');
        $vch->addChild('ISVATOVERRIDDEN', 'No');
        $vch->addChild('IGNOREORIGVCHDATE', 'No');
        $vch->addChild('ISVATPAIDATCUSTOMS', 'No');
        $vch->addChild('ISDECLAREDTOCUSTOMS', 'No');
        $vch->addChild('VATADVANCEPAYMENT', 'No');
        $vch->addChild('VATADVPAY', 'No');
        $vch->addChild('ISCSTDELCAREDGOODSSALES', 'No');
        $vch->addChild('ISVATRESTAXINV', 'No');
        $vch->addChild('ISSERVICETAXOVERRIDDEN', 'No');
        $vch->addChild('ISISDVOUCHER', 'No');
        $vch->addChild('ISEXCISEOVERRIDDEN', 'No');
        $vch->addChild('ISEXCISESUPPLYVCH', 'No');
        $vch->addChild('GSTNOTEXPORTED', 'No');
        $vch->addChild('IGNOREGSTINVALIDATION', 'No');
        $vch->addChild('ISGSTREFUND', 'No');
        $vch->addChild('OVRDNEWAYBILLAPPLICABILITY', 'No');
        $vch->addChild('ISVATPRINCIPALACCOUNT', 'No');

        // Additional status and configuration flags
        $statusFlags = [
            'VCHSTATUSISVCHNUMUSED',
            'VCHGSTSTATUSISINCLUDED',
            'VCHGSTSTATUSISUNCERTAIN',
            'VCHGSTSTATUSISEXCLUDED',
            'VCHGSTSTATUSISAPPLICABLE',
            'VCHGSTSTATUSISGSTR2BRECONCILED',
            'VCHGSTSTATUSISGSTR2BONLYINPORTAL',
            'VCHGSTSTATUSISGSTR2BONLYINBOOKS',
            'VCHGSTSTATUSISGSTR2BMISMATCH',
            'VCHGSTSTATUSISGSTR2BINDIFFPERIOD',
            'VCHGSTSTATUSISRETEFFDATEOVERRDN',
            'VCHGSTSTATUSISOVERRDN',
            'VCHGSTSTATUSISSTATINDIFFDATE',
            'VCHGSTSTATUSISRETINDIFFDATE',
            'VCHGSTSTATUSMAINSECTIONEXCLUDED',
            'VCHGSTSTATUSISBRANCHTRANSFEROUT',
            'VCHGSTSTATUSISSYSTEMSUMMARY',
            'VCHSTATUSISUNREGISTEREDRCM',
            'VCHSTATUSISOPTIONAL',
            'VCHSTATUSISCANCELLED',
            'VCHSTATUSISDELETED',
            'VCHSTATUSISOPENINGBALANCE',
            'VCHSTATUSISFETCHEDONLY',
            'PAYMENTLINKHASMULTIREF',
            'ISSHIPPINGWITHINSTATE',
            'ISOVERSEASTOURISTTRANS',
            'ISDESIGNATEDZONEPARTY',
            'HASCASHFLOW',
            'ISPOSTDATED',
            'USETRACKINGNUMBER',
            'ISINVOICE',
            'MFGJOURNAL',
            'HASDISCOUNTS',
            'ASPAYSLIP',
            'ISCOSTCENTRE',
            'ISSTXNONREALIZEDVCH',
            'ISEXCISEMANUFACTURERON',
            'ISBLANKCHEQUE',
            'ISVOID'

        ];

        foreach ($statusFlags as $flag) {
            $vch->addChild($flag, $flag === 'HASCASHFLOW' ? 'Yes' : 'No');
        }

        // More specific flags
        $vch->addChild('ORDERLINESTATUS', 'No');
        $vch->addChild('VATISAGNSTCANCSALES', 'No');
        $vch->addChild('VATISPURCEXEMPTED', 'No');
        $vch->addChild('ISVATRESTAXINVOICE', 'No');
        $vch->addChild('VATISASSESABLECALCVCH', 'No');
        $vch->addChild('ISVATDUTYPAID', 'Yes');
        $vch->addChild('ISDELIVERYSAMEASCONSIGNEE', 'No');
        $vch->addChild('ISDISPATCHSAMEASCONSIGNOR', 'No');
        $vch->addChild('ISDELETEDVCHRETAINED', 'No');
        $vch->addChild('CHANGEVCHMODE', 'No');
        $vch->addChild('RESETIRNQRCODE', 'No');

        // Alter and master IDs
        $vch->addChild('ALTERID', '2');
        $vch->addChild('MASTERID', '2');
        $vch->addChild('VOUCHERKEY', '194914205827088');
        $vch->addChild('VOUCHERRETAINKEY', '1');
        $vch->addChild('VOUCHERNUMBERSERIES', 'Default');

        // Empty list elements
        $emptyLists = [
            'EWAYBILLDETAILS',
            'EXCLUDEDTAXATIONS',
            'OLDAUDITENTRIES',
            'ACCOUNTAUDITENTRIES',
            'AUDITENTRIES',
            'DUTYHEADDETAILS',
            'GSTADVADJDETAILS',
            'CONTRITRANS',
            'EWAYBILLERRORLIST',
            'IRNERRORLIST',
            'HARYANAVAT',
            'SUPPLEMENTARYDUTYHEADDETAILS',
            'INVOICEDELNOTES',
            'INVOICEORDERLIST',
            'INVOICEINDENTLIST',
            'ATTENDANCEENTRIES',
            'ORIGINVOICEDETAILS',
            'INVOICEEXPORTLIST'

        ];

        foreach ($emptyLists as $listName) {
            $vch->addChild($listName . '.LIST', ' ');
        }

        // First ALLLEDGERENTRIES.LIST
        $allLedgerEntries1 = $vch->addChild('ALLLEDGERENTRIES.LIST');
        $oldAuditEntryIds1 = $allLedgerEntries1->addChild('OLDAUDITENTRYIDS.LIST');
        $oldAuditEntryIds1->addAttribute('TYPE', 'Number');
        $oldAuditEntryIds1->addChild('OLDAUDITENTRYIDS', '-1');

        $allLedgerEntries1->addChild('APPROPRIATEFOR', $escapedDecoded . ' Not Applicable');
        $allLedgerEntries1->addChild('LEDGERNAME', $row['client_fullname']);
        $allLedgerEntries1->addChild('GSTCLASS', $escapedDecoded . ' Not Applicable');

        // More attributes and child elements for the first ALLLEDGERENTRIES.LIST
        $ledgerFlags1 = [
            'ISDEEMEDPOSITIVE',
            'LEDGERFROMITEM',
            'REMOVEZEROENTRIES',
            'ISPARTYLEDGER',
            'GSTOVERRIDDEN',
            'ISGSTASSESSABLEVALUEOVERRIDDEN',
            'STRDISGSTAPPLICABLE',
            'STRDGSTISPARTYLEDGER',
            'STRDGSTISDUTYLEDGER',
            'CONTENTNEGISPOS',
            'ISLASTDEEMEDPOSITIVE',
            'ISCAPVATTAXALTERED',
            'ISCAPVATNOTCLAIMED'

        ];

        foreach ($ledgerFlags1 as $flag) {
            $allLedgerEntries1->addChild($flag, 'No');
        }

        $allLedgerEntries1->addChild('ISPARTYLEDGER', 'Yes');
        $allLedgerEntries1->addChild('AMOUNT', $row['payment_amount']);

        // Empty list elements for first ALLLEDGERENTRIES.LIST
        $emptyLists1 = [
            'SERVICETAXDETAILS',
            'BANKALLOCATIONS',
        ];
        
        foreach ($emptyLists1 as $listName) {
            $allLedgerEntries1->addChild($listName . '.LIST', '');
        }

        // BILLALLOCATIONS.LIST for first ALLLEDGERENTRIES.LIST
        $billAllocations1 = $allLedgerEntries1->addChild('BILLALLOCATIONS.LIST');
        $billAllocations1->addChild('NAME', $name);
        $billAllocations1->addChild('BILLTYPE', $billType_receipt);
        $billAllocations1->addChild('TDSDEDUCTEEISSPECIALRATE', 'No');
        $billAllocations1->addChild('AMOUNT', $row['payment_amount']);

        // Empty list elements for BILLALLOCATIONS.LIST
        $emptyLists2 = [
            'INTERESTCOLLECTION',
            'STBILLCATEGORIES',
        ];
        foreach ($emptyLists2 as $listName) {
            $billAllocations1->addChild($listName . '.LIST', ' ');
        }

        $emptyLists3 = [
            'INTERESTCOLLECTION',
            'OLDAUDITENTRIES',
            'ACCOUNTAUDITENTRIES',
            'AUDITENTRIES',
            'INPUTCRALLOCS',
            'DUTYHEADDETAILS',
            'EXCISEDUTYHEADDETAILS',
            'RATEDETAILS',
            'SUMMARYALLOCS',
            'CENVATDUTYALLOCATIONS',
            'STPYMTDETAILS',
            'EXCISEPAYMENTALLOCATIONS',
            'TAXBILLALLOCATIONS',
            'TAXOBJECTALLOCATIONS',
            'TDSEXPENSEALLOCATIONS',
            'VATSTATUTORYDETAILS',
            'COSTTRACKALLOCATIONS',
            'REFVOUCHERDETAILS',
            'INVOICEWISEDETAILS',
            'VATITCDETAILS',
            'ADVANCETAXDETAILS',
            'TAXTYPEALLOCATIONS'
        ];

        foreach ($emptyLists3 as $listName) {
            $allLedgerEntries1->addChild($listName . '.LIST', ' ');
        }

        // Second ALLLEDGERENTRIES.LIST (for dynamic payment modes)
        $allLedgerEntries2 = $vch->addChild('ALLLEDGERENTRIES.LIST');

        // OLDAUDITENTRYIDS.LIST with TYPE attribute
        $oldAuditEntryIds2 = $allLedgerEntries2->addChild('OLDAUDITENTRYIDS.LIST');
        $oldAuditEntryIds2->addAttribute('TYPE', 'Number');
        $oldAuditEntryIds2->addChild('OLDAUDITENTRYIDS', '-1');

        // Adding the Ledger Name (dynamic based on payment mode)
        $allLedgerEntries2->addChild('LEDGERNAME', htmlspecialchars($row['payment_mode'], ENT_QUOTES, 'UTF-8'));

        // Adding GST Class
        $allLedgerEntries2->addChild('GSTCLASS', $escapedDecoded . ' Not Applicable');

        // Adding other flags and attributes
        $allLedgerEntries2->addChild('ISDEEMEDPOSITIVE', $row['payment_amount'] < 0 ? 'No' : 'Yes');
        $allLedgerEntries2->addChild('LEDGERFROMITEM', 'No');
        $allLedgerEntries2->addChild('REMOVEZEROENTRIES', 'No');
        $allLedgerEntries2->addChild('ISPARTYLEDGER', 'Yes');
        $allLedgerEntries2->addChild('GSTOVERRIDDEN', 'No');
        $allLedgerEntries2->addChild('ISGSTASSESSABLEVALUEOVERRIDDEN', 'No');
        $allLedgerEntries2->addChild('STRDISGSTAPPLICABLE', 'No');
        $allLedgerEntries2->addChild('STRDGSTISPARTYLEDGER', 'No');
        $allLedgerEntries2->addChild('STRDGSTISDUTYLEDGER', 'No');
        $allLedgerEntries2->addChild('CONTENTNEGISPOS', 'No');
        $allLedgerEntries2->addChild('ISLASTDEEMEDPOSITIVE', $row['payment_amount'] < 0 ? 'No' : 'Yes');
        $allLedgerEntries2->addChild('ISCAPVATTAXALTERED', 'No');
        $allLedgerEntries2->addChild('ISCAPVATNOTCLAIMED', 'No');

        // Amount of the transaction (dynamic based on payment amount)
        $amount = $row['payment_amount'];
        $allLedgerEntries2->addChild('AMOUNT', '-' . $amount);

        // Additional fields specific to cheque transactions
        if (strtolower($row['payment_mode']) === 'cheque') {
            $allLedgerEntries2->addChild('CHEQUENUMBER', htmlspecialchars($row['cheque_number'], ENT_QUOTES, 'UTF-8'));
            $allLedgerEntries2->addChild('CHEQUEDATE', htmlspecialchars($row['cheque_date'], ENT_QUOTES, 'UTF-8'));
            $allLedgerEntries2->addChild('CHEQUEBANKNAME', htmlspecialchars($row['cheque_bank'], ENT_QUOTES, 'UTF-8'));
        }

        // Empty lists specific to this entry
        $emptyLists3 = [
            'SERVICETAXDETAILS',
            'BANKALLOCATIONS',
            'BILLALLOCATIONS',
            'INTERESTCOLLECTION',
            'OLDAUDITENTRIES',
            'ACCOUNTAUDITENTRIES',
            'AUDITENTRIES',
            'INPUTCRALLOCS',
            'DUTYHEADDETAILS',
            'EXCISEDUTYHEADDETAILS',
            'RATEDETAILS',
            'SUMMARYALLOCS',
            'CENVATDUTYALLOCATIONS',
            'STPYMTDETAILS',
            'EXCISEPAYMENTALLOCATIONS',
            'TAXBILLALLOCATIONS',
            'TAXOBJECTALLOCATIONS',
            'TDSEXPENSEALLOCATIONS',
            'VATSTATUTORYDETAILS',
            'COSTTRACKALLOCATIONS',
            'REFVOUCHERDETAILS',
            'INVOICEWISEDETAILS',
            'VATITCDETAILS',
            'ADVANCETAXDETAILS',
            'TAXTYPEALLOCATIONS'
        ];

        // Add empty lists for this ledger entry
        foreach ($emptyLists3 as $listName) {
            $allLedgerEntries2->addChild($listName . '.LIST', ' ');
        }

// // Adding BILLALLOCATIONS.LIST for the cheque entry
        // $billAllocations2 = $allLedgerEntries2->addChild('BILLALLOCATIONS.LIST');
        // $billAllocations2->addChild('NAME', $name); // Assuming the name is the same as defined earlier
        // $billAllocations2->addChild('BILLTYPE', $billType_receipt); // Assuming this is a receipt type
        // $billAllocations2->addChild('TDSDEDUCTEEISSPECIALRATE', 'No'); // TDS not applicable
        // $billAllocations2->addChild('AMOUNT', $row['payment_amount']); // The amount deducted by cheque

        // // Empty lists specific to this bill allocation
        // $emptyLists4 = ['INTERESTCOLLECTION', 'STBILLCATEGORIES'];
        // foreach ($emptyLists4 as $listName) {
        //     $billAllocations2->addChild($listName . '.LIST', ' ');
        // }


        // Final empty list elements
        $finalEmptyLists = [
            'GST',
            'STKJRNLADDLCOSTDETAILS',
            'PAYROLLMODEOFPAYMENT',
            'ATTDRECORDS',
            'GSTEWAYCONSIGNORADDRESS',
            'GSTEWAYCONSIGNEEADDRESS',
            'TEMPGSTRATEDETAILS',
            'TEMPGSTADVADJUSTED',
            'GSTBUYERADDRESS',
            'GSTCONSIGNEEADDRESS'
        ];

        foreach ($finalEmptyLists as $listName) {
            $vch->addChild($listName . '.LIST', ' ');
        }
    }
    

    // Output or save the XML
    
}

catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}




}catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    $conn = null;
}
 

// $receipt_voucherNumber++;
}
}catch (PDOException $e) {
    // Handle database connection or query errors
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle any other errors
    echo "Error: " . $e->getMessage();
}


// Output the XML
header('Content-Type: application/xml; charset=utf-8');
echo $xml->asXML();

?>