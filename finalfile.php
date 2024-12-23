<?php

// Database connection

$host = 'localhost';
$dbname = 'NewTallydb';
$username = 'postgres';
$password = '12345678';

// Predefined values

$companyName = "ABC Pvt Ltd";
$gstRegistrationType = "Regular";
$gstRegistration = "ABC Pvt Ltd";
$country = "India";

$ledger_name = "Banquet Sales";
$bill_type = "New Ref";
$billType_receipt = "Agst Ref";

$prefix1 = 'ef1532b1-c551-4b3f-ac45-04402e1668cc-';
$prefix2 = 'ef1532b1-c551-4b3f-ac45-04402e1668cc-0000b146:';

// Narration
$sales_narration = "Sales Narration";
$receipt_narration = "Receipt Narration";

// Counters for the vouchers

$counter = 1;
$counter1 = 4;

$sales_voucherNumber = 1;
$receipt_voucherNumber = 1;

// XML Code starts

// outer tags that are not repeated

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>');
$header = $xml->addChild('HEADER');
$header->addChild('TALLYREQUEST', 'Import Data');
$body = $xml->addChild('BODY');
$importData = $body->addChild('IMPORTDATA');
$requestDesc = $importData->addChild('REQUESTDESC');
$requestDesc->addChild('REPORTNAME', 'All Masters');
$staticVariables = $requestDesc->addChild('STATICVARIABLES');
$staticVariables->addChild('SVCURRENTCOMPANY', $companyName);
$requestData = $importData->addChild('REQUESTDATA');


// Retrieve values from the frontend for the first query

$banquetID = isset($_GET['banquet_id']) ? $_GET['banquet_id'] : null;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if (!$banquetID || !$startDate || !$endDate) {
    die('Invalid input data.');
}

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL Query 1: To fetch client id and booking id based on the date range and the banquet
    $stmt = $pdo->prepare("
    SELECT 
        b.id AS booking_id,
        b.datex AS event_date,
        c.id AS client_id,
        c.fullname AS client_name,
        b.banquet AS banquet_id,
        b.total AS total_amount
    FROM 
        public.bookings b
    INNER JOIN 
        public.clients c ON b.client = c.id
    WHERE 
        b.datex BETWEEN :start_date AND :end_date
        AND b.banquet = :banquetID;
    ");
        $stmt->bindParam(':banquetID', $banquetID, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

    // Fetch the rows into the database
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        throw new Exception("No data found in the database.");
    }

    // Start the loop
    foreach ($rows as $row) {

        // replace the data for other queries to be using it
        $banquet_id = $row['banquet_id'];
        $client_id = $row['client_id'];
        $default_booking_id = $row['booking_id'];
        $partyName = $row['client_name'] . ' - ' . $row['client_id'];
        $name = "BQ-" . $client_id;

/*




### Start of sales voucher





*/


try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL Query 2: To fetch fullname, date and total amount for the sales voucher
    $stmt2 = $pdo->prepare("
    SELECT
        cl.fullname AS client_fullname,
        bk.reg_date AS registration_date,
        SUM(
            CASE
                WHEN m.id = 1 THEN bk.pax * bb.rate
                ELSE
                    CASE
                        WHEN bb.perhead = 0 THEN bb.rate
                        ELSE bb.rate * bb.pax
                    END
            END
        ) + COALESCE(bk.rent, 0) AS total_bill_amount
    FROM
        public.bookings bk
    LEFT JOIN
        public.booking_breakups bb ON bk.id = bb.bookingid
    LEFT JOIN
        public.monopolies m ON m.id = bb.monopoly
    JOIN
        public.clients cl ON cl.id = bk.client
    WHERE
        bk.id = :booking_id
    GROUP BY
        cl.fullname, bk.reg_date, bk.commentsx, bk.rent
    ORDER BY
        bk.reg_date DESC
");
    $stmt2->execute([':booking_id' => $default_booking_id]);
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

// Date formatting 
if ($result2) {
    $originalDate = $result2['registration_date'];
    if (!empty($originalDate)) {
        $regdate = DateTime::createFromFormat('Y-m-d H:i:s', $originalDate);
        if ($regdate) {
            $date = $regdate->format('Ymd');
        } else {
            $date = 'No date Available'; 
        }
    } else {
        $date = 'No date Available'; 
    }
    $total_sales_amount = -$result2['total_bill_amount'];
    $partyName = $row['client_name'] . ' - ' . $row['client_id'];
} else {
    $date = "No date Available";
    $total_sales_amount = "-0.00";
}


// Start of xml of sales voucher

$tallyMessage = $requestData->addChild('TALLYMESSAGE');
$tallyMessage->addAttribute('xmlns:UDF', 'TallyUDF');
$vch = $tallyMessage->addChild('VOUCHER');
$voucher_receipt_id = $prefix1 . str_pad($counter, 8, '0', STR_PAD_LEFT);
$voucher_receipt_key = $prefix2 . str_pad($counter1, 8, '0', STR_PAD_LEFT);
$vch->addAttribute('REMOTEID', $voucher_receipt_id);
$vch->addAttribute('VCHKEY', $voucher_receipt_key);
$vch->addAttribute('VCHTYPE', 'Sales');
$vch->addAttribute('ACTION', 'Create');
$vch->addAttribute('OBJVIEW', 'Invoice Voucher View');
$oldAuditEntryIdsList = $vch->addChild('OLDAUDITENTRYIDS.LIST');
$oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
$oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');
$vch->addChild('DATE', $date);
$vch->addChild('VCHSTATUSDATE', $date);
$vch->addChild('GUID', $voucher_receipt_id);
$vch->addChild('GSTREGISTRATIONTYPE', $gstRegistrationType);
$vch->addChild('VATDEALERTYPE', $gstRegistrationType);
$vch->addChild('NARRATION', "Booking id : " . $row['booking_id']);
$vch->addChild('COUNTRYOFRESIDENCE', $country);
$vch->addChild('PARTYNAME', $partyName);
$gstRegistrationNode = $vch->addChild('GSTREGISTRATION', $gstRegistration);
$gstRegistrationNode->addAttribute('TAXTYPE', 'GST');
$gstRegistrationNode->addAttribute('TAXREGISTRATION', '');
$vch->addChild('VOUCHERTYPENAME', 'Sales');
$vch->addChild('PARTYLEDGERNAME', $partyName);
$vch->addChild('VOUCHERNUMBER', $sales_voucherNumber);
$sales_voucherNumber++; // ############# counter increases for sales voucher id ###################
$vch->addChild('BASICBUYERNAME', $partyName);
$vch->addChild('CMPGSTREGISTRATIONTYPE', $gstRegistrationType);
$vch->addChild('PARTYMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEEMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEECOUNTRYNAME', $country);
$vch->addChild('BASICBASEPARTYNAME', $partyName);
$vch->addChild('NUMBERINGSTYLE', 'Auto Retain');
$vch->addChild('FBTPAYMENTTYPE', 'Default');
$vch->addChild('PERSISTEDVIEW', 'Invoice Voucher View');
$vch->addChild('VCHSTATUSTAXADJUSTMENT', 'Default');
$vch->addChild('VCHSTATUSVOUCHERTYPE', 'Sales');
$vch->addChild('VCHSTATUSTAXUNIT', $companyName);
$vch->addChild('VCHENTRYMODE', 'Item Invoice');
$statusKeys = [
    'DIFFACTUALQTY', 'ISMSTFROMSYNC', 'ISDELETED', 'ISSECURITYONWHENENTERED', 
    'ASORIGINAL', 'AUDITED', 'ISCOMMONPARTY', 'FORJOBCOSTING', 
    'ISOPTIONAL', 'EFFECTIVEDATE', 'USEFOREXCISE', 'ISFORJOBWORKIN', 
    'ALLOWCONSUMPTION', 'USEFORINTEREST', 'USEFORGAINLOSS', 'USEFORGODOWNTRANSFER', 
    'USEFORCOMPOUND', 'USEFORSERVICETAX', 'ISREVERSECHARGEAPPLICABLE', 
    'ISSYSTEM', 'ISFETCHEDONLY', 'ISGSTOVERRIDDEN', 'ISCANCELLED', 
    'ISONHOLD', 'ISSUMMARY', 'ISECOMMERCESUPPLY', 'ISBOENOTAPPLICABLE', 
    'ISGSTSECSEVENAPPLICABLE', 'IGNOREEINVVALIDATION', 
    'CMPGSTISOTHTERRITORYASSESSEE', 'PARTYGSTISOTHTERRITORYASSESSEE'
];
$statusValues = [
    'No', 'No', 'No', 'No', 
    'No', 'No', 'No', 'No', 
    'No', $date, 'No', 'No', 
    'No', 'No', 'No', 'No', 
    'No', 'No', 'No', 
    'No', 'No', 'No', 'No', 
    'No', 'No', 'No', 'No', 
    'No', 'No', 
    'No', 'No'
];
foreach ($statusKeys as $index => $key) {
    $vch->addChild($key, $statusValues[$index]);
}
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
foreach ($additionalFlags as $flag) {
    $value = ($flag === 'ISVATDUTYPAID' || $flag === 'ISINVOICE') ? 'Yes' : 'No';
    $vch->addChild($flag, $value);
}
$tagsWithValues = [
    'ALTERID' => ' 1',
    'MASTERID' => ' 1',
    'VOUCHERKEY' => '194914205827080',
    'VOUCHERRETAINKEY' => '1',
    'VOUCHERNUMBERSERIES' => 'Default'
];
foreach ($tagsWithValues as $tag => $value) {
    $vch->addChild($tag, $value);
}
$emptyLists = [
    'EWAYBILLDETAILS.LIST', 'EXCLUDEDTAXATIONS.LIST',
    'OLDAUDITENTRIES.LIST', 'ACCOUNTAUDITENTRIES.LIST', 'AUDITENTRIES.LIST', 
    'DUTYHEADDETAILS.LIST', 'GSTADVADJDETAILS.LIST'
];
foreach ($emptyLists as $listName) {
    $vch->addChild($listName , ' ');
}
$counter++;    ########################## counter increases for the sales receipt number ###########################
$counter1++;

/*




### Start of sales splits part





*/


try {
        // SQL Query 3: To fetch service name and the total amount of the service 
        $sql = "
    SELECT
        CASE
            WHEN bm.tally_ledger_name IS NOT NULL THEN bm.tally_ledger_name
            ELSE m.monopoly
        END AS service_name,
        
        CASE
            WHEN m.id = 1 THEN bk.pax * bb.rate
            WHEN bb.perhead = 0 THEN bb.rate
            ELSE bb.rate * bb.pax
        END AS total_amount,
        
        bm.tally_ledger_name AS ledger
    FROM
        public.booking_breakups bb
    JOIN
        public.monopolies m ON m.id = bb.monopoly
    JOIN
        public.bookings bk ON bk.id = bb.bookingid
    JOIN
        public.clients cl ON cl.id = bk.client
    JOIN 
        banquet_monopolies bm ON bm.monopoly = m.id AND bm.banquet = bk.banquet
    WHERE
        bk.id = :booking_id
        
    UNION ALL
    
    SELECT 
        'hall_rent' AS service_name,
        bk.rent AS total_amount,
        'hall_name' AS ledger
    FROM
        bookings bk
    JOIN 
        clients cl ON cl.id = bk.client
    WHERE
        bk.id = :booking_id;
";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':booking_id', $default_booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// start of the splits xml code

foreach ($results as $result) {
    $inventoryEntries = $vch->addChild('ALLINVENTORYENTRIES.LIST');
    $service_name = $result['service_name'];
    $stock_item_amount = $result['total_amount'];
    $inventoryEntries->addChild('STOCKITEMNAME', $service_name); 
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
    $inventoryEntries->addChild('AMOUNT', $stock_item_amount);
    $batchAllocations = $inventoryEntries->addChild('BATCHALLOCATIONS.LIST');
    $batchAllocations->addChild('GODOWNNAME', 'Main Location');
    $batchAllocations->addChild('BATCHNAME', 'Primary Batch');
    $batchAllocations->addChild('DYNAMICCSTISCLEARED', 'No');
    $batchAllocations->addChild('AMOUNT', $stock_item_amount);
    $batchEmptyLists = [
        'ADDITIONALDETAILS.LIST',
        'VOUCHERCOMPONENTLIST.LIST'
    ];
    foreach ($batchEmptyLists as $listName) {
        $batchAllocations->addChild($listName, ' ');
    }
    $accountingAllocations = $inventoryEntries->addChild('ACCOUNTINGALLOCATIONS.LIST');
    $oldAuditEntryIdsList = $accountingAllocations->addChild('OLDAUDITENTRYIDS.LIST');
    $oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
    $oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');
    $accountingAllocations->addChild('LEDGERNAME', $ledger_name); 
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
    $finalEmptyLists = [
        'DUTYHEADDETAILS.LIST', 'RATEDETAILS.LIST', 'SUPPLEMENTARYDUTYHEADDETAILS.LIST', 
        'TAXOBJECTALLOCATIONS.LIST', 'REFVOUCHERDETAILS.LIST', 'EXCISEALLOCATIONS.LIST', 
        'EXPENSEALLOCATIONS.LIST'
    ];
    foreach ($finalEmptyLists as $listName) {
        $inventoryEntries->addChild($listName, ' ');
    }
}
/*




### Start of sales total amount writing part





*/
    

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
    $ledgerEntries = $vch->addChild('LEDGERENTRIES.LIST');
    $oldAuditIds = $ledgerEntries->addChild('OLDAUDITENTRYIDS.LIST');
    $oldAuditIds->addAttribute('TYPE', 'Number');
    $oldAuditIds->addChild('OLDAUDITENTRYIDS', '-1');
    $ledgerEntries->addChild('LEDGERNAME', $partyName);
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
        $ledgerEntries ->addChild($listName, ' ');
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
}catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
} 

/*




### Start of receipt vouchers part





*/


try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL Query 4: To fetch clientname, receipt amount and payment date from the database
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
          bp.banquet_receipt_no AS banquet_receipt_number,
          bp.id AS main_id
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
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);





    // Start of the xml code of the receipts voucher

    foreach ($rows as $row) {


        if (!empty($row['payment_date'])) {
            $paymentDate = DateTime::createFromFormat('Y-m-d H:i:s', $row['payment_date']);
            if ($paymentDate) {
                $row['payment_date'] = $paymentDate->format('Ymd');
            } else {
                $row['payment_date'] = 'Invalid Date';
            }
        }

        // Date formatting for cheque date
        if (!empty($row['cheque_date'])) {
            $chequeDate = DateTime::createFromFormat('Y-m-d', $row['cheque_date']);
            if ($chequeDate) {
                $row['cheque_date'] = $chequeDate->format('Ymd');
            } else {
                $row['cheque_date'] = 'Invalid Date';
            }
        }




        $effectiveDate = $row['payment_date'];
        $tallyMessage = $requestData->addChild('TALLYMESSAGE');
        $tallyMessage->addAttribute('xmlns:UDF', 'TallyUDF');
        $vch = $tallyMessage->addChild('VOUCHER');
        $voucher_receipt_id = $prefix1 . str_pad($counter, 8, '0', STR_PAD_LEFT);
        $voucher_receipt_key = $prefix2 . str_pad($counter1, 8, '0', STR_PAD_LEFT);
        $vch->addAttribute('REMOTEID', $voucher_receipt_id);
        $vch->addAttribute('VCHKEY', $voucher_receipt_key);
        $counter++; 
        $counter1++;  // ########################### counter for receipt voucher is incremeneted #########################
        $vch->addAttribute('VCHTYPE', 'Receipt');
        $vch->addAttribute('ACTION', 'Create');
        $vch->addAttribute('OBJVIEW', 'Accounting Voucher View');
        $oldAuditEntryIdsList = $vch->addChild('OLDAUDITENTRYIDS.LIST', null);
        $oldAuditEntryIdsList->addAttribute('TYPE', 'Number');
        $oldAuditEntryIdsList->addChild('OLDAUDITENTRYIDS', '-1');
        $vch->addChild('DATE', $row['payment_date']);
        $vch->addChild('VCHSTATUSDATE', $row['payment_date']);
        $vch->addChild('GUID', $voucher_receipt_id);
         $narration = "BRN: {$default_booking_id} | BE receipt id: {$row['banquet_receipt_number']} | {$row['comments']}";

         // Check if the payment mode is "Online" and add the transaction ID if true
         if ($row['payment_mode'] == 'Online') {
             $narration .= " | Transaction ID: {$row['transaction_id']}";
         }
 
         // Check if the payment mode is "Cheque" and add the cheque details
         if ($row['payment_mode'] == 'Cheque') {
             $narration .= " | Cheque No: {$row['cheque_number']} | Cheque Bank: {$row['cheque_bank']} | Cheque Date: {$row['cheque_date']}";
         }
 

    // Add the NARRATION as a child element
    $vch->addChild('NARRATION', $narration);
        $gstRegistration = $vch->addChild('GSTREGISTRATION', 'ABC Pvt Ltd');
        $gstRegistration->addAttribute('TAXTYPE', 'GST');
        $gstRegistration->addAttribute('TAXREGISTRATION', '');
        $vch->addChild('VOUCHERTYPENAME', 'Receipt');
        $vch->addChild('PARTYLEDGERNAME', $partyName);
        $vch->addChild('VOUCHERNUMBER', $receipt_voucherNumber);
        $receipt_voucherNumber++;  // ############### receipt voucher number updated #####################
        $vch->addChild('CMPGSTREGISTRATIONTYPE', 'Regular');
        $vch->addChild('NUMBERINGSTYLE', 'Auto Retain');
        $vch->addChild('FBTPAYMENTTYPE', 'Default');
        $vch->addChild('PERSISTEDVIEW', 'Accounting Voucher View');
        $vch->addChild('VCHSTATUSTAXADJUSTMENT', 'Default');
        $vch->addChild('VCHSTATUSVOUCHERTYPE', 'Receipt');
        $vch->addChild('VCHSTATUSTAXUNIT', $companyName);
        $vch->addChild('DIFFACTUALQTY', 'No');
        $vch->addChild('ISMSTFROMSYNC', 'No');
        $vch->addChild('ISDELETED', 'No');
        $vch->addChild('ISSECURITYONWHENENTERED', 'No');
        $vch->addChild('ASORIGINAL', 'No');
        $vch->addChild('AUDITED', 'No');
        $vch->addChild('ISCOMMONPARTY', 'No');
        $vch->addChild('FORJOBCOSTING', 'No');
        $vch->addChild('ISOPTIONAL', 'No');
        $vch->addChild('EFFECTIVEDATE', $effectiveDate);
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
        $vch->addChild('ALTERID', ' 2');
        $vch->addChild('MASTERID', ' 2');
        $vch->addChild('VOUCHERKEY', '194914205827088');
        $vch->addChild('VOUCHERRETAINKEY', '1');
        $vch->addChild('VOUCHERNUMBERSERIES', 'Default');
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
        $allLedgerEntries1 = $vch->addChild('ALLLEDGERENTRIES.LIST');
        $oldAuditEntryIds1 = $allLedgerEntries1->addChild('OLDAUDITENTRYIDS.LIST');
        $oldAuditEntryIds1->addAttribute('TYPE', 'Number');
        $oldAuditEntryIds1->addChild('OLDAUDITENTRYIDS', '-1');
        $allLedgerEntries1->addChild('LEDGERNAME', $partyName);
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
        $emptyLists1 = [
            'SERVICETAXDETAILS',
            'BANKALLOCATIONS',
        ];
        foreach ($emptyLists1 as $listName) {
            $allLedgerEntries1->addChild($listName . '.LIST', '');
        }
        $billAllocations1 = $allLedgerEntries1->addChild('BILLALLOCATIONS.LIST');
        $billAllocations1->addChild('NAME', $name);
        $billAllocations1->addChild('BILLTYPE', $billType_receipt);
        $billAllocations1->addChild('TDSDEDUCTEEISSPECIALRATE', 'No');
        $billAllocations1->addChild('AMOUNT', $row['payment_amount']);
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
        $allLedgerEntries2 = $vch->addChild('ALLLEDGERENTRIES.LIST');
        $oldAuditEntryIds2 = $allLedgerEntries2->addChild('OLDAUDITENTRYIDS.LIST');
        $oldAuditEntryIds2->addAttribute('TYPE', 'Number');
        $oldAuditEntryIds2->addChild('OLDAUDITENTRYIDS', '-1');
        $allLedgerEntries2->addChild('LEDGERNAME', htmlspecialchars($row['payment_mode'], ENT_QUOTES, 'UTF-8'));
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
        $amount = $row['payment_amount'];
        $allLedgerEntries2->addChild('AMOUNT', '-' . $amount);
        if (strtolower($row['payment_mode']) === 'cheque') {
            $allLedgerEntries2->addChild('CHEQUENUMBER', htmlspecialchars($row['cheque_number'], ENT_QUOTES, 'UTF-8'));
            $allLedgerEntries2->addChild('CHEQUEDATE', htmlspecialchars($row['cheque_date'], ENT_QUOTES, 'UTF-8'));
            $allLedgerEntries2->addChild('CHEQUEBANKNAME', htmlspecialchars($row['cheque_bank'], ENT_QUOTES, 'UTF-8'));
        }
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
        foreach ($emptyLists3 as $listName) {
            $allLedgerEntries2->addChild($listName . '.LIST', ' ');
        }
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
}
// receipt try cat
catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

}



}
// maint try block catch 
catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} 

// Logic for interacting with the html requests 

// if (isset($_GET['download']) && $_GET['download'] == 'true') {
//     if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
//         $startDate = $_GET['start_date'];
//         $formattedDate = date("F", strtotime($startDate)) . date("y", strtotime($startDate));
//         $filename = strtolower($formattedDate) . '.xml';
//         header('Content-Type: application/xml');
//         header('Content-Disposition: attachment; filename="' . $filename . '"');
//         echo $xml->asXML();
//         exit;
//     } else {
//         echo 'Start date is required for downloading the file.';
//     }
// } else {
//     header('Content-Type: application/xml; charset=utf-8');
//     echo $xml->asXML();
// }


// Check if download is requested
if (isset($_GET['download']) && $_GET['download'] == 'true') {
    // Get input parameters from the URL
    $banquetId = isset($_GET['banquet_id']) ? $_GET['banquet_id'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml'; // Default to XML if no format specified

    // Validate required parameters
    if ($banquetId && $startDate && $endDate) {
        // Prepare SQL query to fetch data from the database
        $sql = "SELECT 
                    b.id AS booking_id,
                    b.datex AS event_date,
                    c.id AS client_id,
                    c.fullname AS client_name,
                    b.banquet AS banquet_id,
                    b.total AS total_amount
                FROM 
                    public.bookings b
                INNER JOIN 
                    public.clients c ON b.client = c.id
                WHERE 
                    b.datex BETWEEN :start_date AND :end_date
                    AND b.banquet = :banquetID";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':banquetID', $banquetId, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);

        // Execute the query
        $stmt->execute();

        // Fetch the results
        $filteredData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if we have any data to export
        if (empty($filteredData)) {
            echo 'No data found for the specified filters.';
            exit;
        }

        // Based on the requested format, generate either XML or CSV
        if ($format === 'csv') {
            // Prepare the CSV file content
           // Assuming $startDate and $endDate are your start and end dates in 'YYYY-MM-DD' format
            $startMonth = date("F", strtotime($startDate));  // Full month name from start date
            $endMonth = date("F", strtotime($endDate));      // Full month name from end date

            // Format the filename using start month and end month
            $formattedDate = strtolower($startMonth) . '-' . strtolower($endMonth);
            $filename = 'client_data_' . $formattedDate . '.csv';

            // Set the headers for the CSV file download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');

            
            // Open the output stream for CSV
            $output = fopen('php://output', 'w');
            
            // Column headers with combined Client Name and Client ID
            fputcsv($output, ['BRN', 'Client Name - Client ID', 'Event Date']); // Adjusted header
            
            // Output each row of data
            foreach ($filteredData as $row) {
                // Combine client name and client id in one column
                $clientNameAndId = $row['client_name'] . ' - ' . $row['client_id'];
                fputcsv($output, [
                    $row['booking_id'],
                    $clientNameAndId, // Combined client name and id
                    $row['event_date']
                ]);
            }
            
            fclose($output);
            exit;

        } elseif ($format === 'xml') {
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                        $startMonth = date("F", strtotime($startDate));  // Full month name from start date
                        $endMonth = date("F", strtotime($endDate));      // Full month name from end date
                        
                        // Format the filename using start month and end month
                        $formattedDate = strtolower($startMonth) . '-' . strtolower($endMonth);
                        $filename = $formattedDate . '.xml';
                        
                        // Set the headers for the file download
                        header('Content-Type: application/xml');
                        header('Content-Disposition: attachment; filename="' . $filename . '"');
                        echo $xml->asXML();
                        exit;
                    } else {
                        echo 'Start date is required for downloading the file.';
                    }
        } else {
            echo 'Unsupported format requested.';
            exit;
        }
    } else {
        echo 'Banquet ID, Start Date, and End Date are required for downloading the file.';
        exit;
    }
}  else {
        header('Content-Type: application/xml; charset=utf-8');
        echo $xml->asXML();
    }


?>
