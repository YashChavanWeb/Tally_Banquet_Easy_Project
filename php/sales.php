<?php
$partyName = "Yash Chavan";
$voucherNumber = "1";
$reportName = "Yash";
$companyName = "ABC Pvt Ltd";
$date = "20240401";
$gstRegistrationType = "Regular";
$gstRegistration = "ABC Pvt Ltd";
$country = "India";
$sales_narration = "This is the Bill that Yash Chavan has to pay to Banquet Easy";
$special = "&#4;"; // The HTML entity
$decoded = html_entity_decode($special, ENT_QUOTES, 'UTF-8'); // Decode the entity
$stock_item_1 = "Room Rent";
$room_rent_amount = "10000.00";
$ledger_name = "Banquet Sales";
$stock_item_2 = "Other Charges";
$amount_2 = "15000.00";
$total_sales_amount = "-25000.00";
$name = "BQ-1";
$bill_type = "New Ref";

//Dynamic data for receipt 
$voucher_receipt_id = "00000002";
$voucher_receipt_Key = "00000010";
$guId_receipt = "00000002";
$billType_receipt = "Agst Ref";
$amount_receipt = "10000.00";
$amount_minus_receipt = "-10000.00";




// Escape special characters to be safe in XML context
$escapedDecoded = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');

// Dynamic data (You can change these values as per your requirement)

$voucherType = "sales";
$billType = "New Ref";
$name = 'BQ-1';
$ledgerName = "Banquet Sales";
$voucher_ID = "00000001";
$voucher_Key = "00000008";
$guID = "00000001";
$reportName = "Yash";
$receipt_narration = "Yash Chavan payed 10,000 by cheque to banquet easy";  

// Database connection
$host = 'localhost';
$dbname = 'Tallydb';
$username = 'postgres';
$password = '12345678';

try{

    // Create PDO instance and connect to database
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Query 1: Fetch Booking Details
    $stmt1 = $pdo->prepare("
        SELECT 
            clients.fullname, 
            bookings.total_paid, 
            bookings.datex,
            bookings.id AS booking_id
        FROM bookings
        JOIN clients ON bookings.client = clients.id
        WHERE bookings.banquet = :banquet_id
        AND bookings.total_paid != 0
        AND bookings.client = :client_id
    ");

    $stmt1->execute([
        ':banquet_id' => 1176, 
        ':client_id' => 47987
    ]);
    $result1 = $stmt1->fetch(PDO::FETCH_ASSOC);

    // Query 2: Detailed Bill Amount Calculation
    $stmt2 = $pdo->prepare("
        SELECT
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
            bk.reg_date DESC
    ");

    $stmt2->execute([
        ':booking_id' => $result1 ? $result1['booking_id'] : 47987
    ]);
    $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    // Update dynamic values from query results
    if ($result1) {
        $date = $result1['datex'];
    } else {
        $date = "20240401";  // Default date
    }

    if ($result2) {
        $total_sales_amount = -$result2['total_bill_amount'];
        $partyName = $result2['client_fullname'];
    } else {
        $total_sales_amount = "-25000.00";
    }
    

// Create XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><TALLYMESSAGE></TALLYMESSAGE>');
$xml->addAttribute('xmlns:UDF', 'TallyUDF');

// Add VOUCHER node
$vch = $xml->addChild('VOUCHER');
$vch->addAttribute('REMOTEID', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-'.$voucher_ID);
$vch->addAttribute('VCHKEY', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-0000b146:'.$voucher_Key);
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
$vch->addChild('GUID', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-'.$guID);
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
$vch->addChild('VOUCHERNUMBER', $voucherNumber);
$vch->addChild('BASICBUYERNAME', $partyName);
$vch->addChild('CMPGSTREGISTRATIONTYPE', $gstRegistrationType);
$vch->addChild('PARTYMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEEMAILINGNAME', $partyName);
$vch->addChild('CONSIGNEECOUNTRYNAME', $country);
$vch->addChild('BASICBASEPARTYNAME', $partyName);

$vch->addChild('NUMBERINGSTYLE', 'Auto Retain');
$vch->addChild('CSTFORMISSUETYPE', 'Not Applicable');
$vch->addChild('CSTFORMRECVTYPE', 'Not Applicable');
$vch->addChild('FBTPAYMENTTYPE', 'Default');
$vch->addChild('PERSISTEDVIEW', 'Invoice Voucher View');
$vch->addChild('VCHSTATUSTAXADJUSTMENT', 'Default');
$vch->addChild('VCHSTATUSVOUCHERTYPE', $voucherType);
$vch->addChild('VCHSTATUSTAXUNIT', $companyName);
$vch->addChild('VCHGSTCLASS', 'Not Applicable');
$vch->addChild('VCHENTRYMODE', 'Item Invoice');

// Status flags
$statusFlags = [
    'DIFFACTUALQTY', 'ISMSTFROMSYNC', 'ISDELETED', 'ISSECURITYONWHENENTERED', 
    'ASORIGINAL', 'AUDITED', 'ISCOMMONPARTY', 'FORJOBCOSTING', 
    'ISOPTIONAL', 'USEFOREXCISE', 'ISFORJOBWORKIN', 'ALLOWCONSUMPTION', 
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
$ledgerEntries->addChild('AMOUNT', $total_sales_amount);

// Bill Allocations
$billAllocations = $ledgerEntries->addChild('BILLALLOCATIONS.LIST');
$billAllocations->addChild('NAME', $name);
$billAllocations->addChild('BILLTYPE', $bill_type);
$billAllocations->addChild('TDSDEDUCTEEISSPECIALRATE', 'No');
$billAllocations->addChild('AMOUNT', $total_sales_amount);



// Empty list elements
$emptyLists = [
    'EWAYBILLDETAILS', 'EXCLUDEDTAXATIONS', 'OLDAUDITENTRIES', 
    'ACCOUNTAUDITENTRIES', 'AUDITENTRIES', 'DUTYHEADDETAILS', 
    'GSTADVADJDETAILS', 'CONTRITRANS', 'EWAYBILLERRORLIST', 
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


foreach ($emptyLists as $listName) {
    $vch->addChild($listName , ' ');
}

// Output the XML
header('Content-Type: application/xml; charset=utf-8');
echo $xml->asXML();
}catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    $conn = null;
}
?>