<?php
$partyName = "Yash Chavan";
$voucherNumber = "1";
$reportName = "Yash";
$companyName = "ABC Pvt Ltd";
$gstRegistrationType = "Regular";
$gstRegistration = "ABC Pvt Ltd";
$special = "&#4;"; // The HTML entity
$decoded = html_entity_decode($special, ENT_QUOTES, 'UTF-8'); // Decode the entity
$ledger_name = "Banquet Sales";
$name = "BQ-1";
$bill_type = "New Ref";

// Dynamic data for receipt 
$voucher_receipt_id = "00000002";
$voucher_receipt_Key = "00000010";
$guId_receipt = "00000002";
$billType_receipt = "Agst Ref";

// Escape special characters to be safe in XML context
$escapedDecoded = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');

//DATABASE CONNECTION
$host = "localhost";
$dbname = "Tallydb";
$username = "postgres";
$password = "12345678";
$port = 5432;

try {

    //create a postgresql database connection
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);

    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare SQL query to fetch data from public.booking_payments
    $stmt = $conn->prepare("
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
            bp.booking = 47987 -- OR c.fullname ILIKE '%aashay%';
    ");

    // Execute the query
    $stmt->execute();

    // Fetch all rows
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no rows found, throw an exception
    if (empty($rows)) {
        throw new Exception("No data found in the database");
    }


    // Create XML
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><ENVELOPE></ENVELOPE>');

    foreach ($rows as $row) {

        // Adding BODY node
        $body = $xml->addChild('BODY');

        // Adding IMPORTDATA node
        $importData = $body->addChild('IMPORTDATA');

        // Adding REQUESTDATA node
        $requestData = $importData->addChild('REQUESTDATA');

        // Adding TALLYMESSAGE node
        $tallyMessage = $requestData->addChild('TALLYMESSAGE');
        $tallyMessage->addAttribute('xmlns:UDF', 'TallyUDF');

        // Adding VOUCHER node
        $vch = $tallyMessage->addChild('VOUCHER');
        $vch->addAttribute('REMOTEID', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-' . $voucher_receipt_id);
        $vch->addAttribute('VCHKEY', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-0000b146:' . $voucher_receipt_Key);
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
        $vch->addChild('GUID', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-' . $guId_receipt);
        $vch->addChild('NARRATION', "Receipt ID: " . $row['receipt_id']);

        // Adding GSTREGISTRATION with additional attributes
        $gstRegistration = $vch->addChild('GSTREGISTRATION', 'ABC Pvt Ltd');
        $gstRegistration->addAttribute('TAXTYPE', 'GST');
        $gstRegistration->addAttribute('TAXREGISTRATION', '');

        // Add additional fields related to the voucher
        $vch->addChild('VOUCHERTYPENAME', 'Receipt');
        $vch->addChild('PARTYLEDGERNAME', $row['client_fullname']);
        $vch->addChild('VOUCHERNUMBER', $voucherNumber);
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
        $vch->addChild('EFFECTIVEDATE', $row['cheque_date']);
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

        foreach ($emptyLists1 as $listName) {
            $allLedgerEntries1->addChild($listName . '.LIST', ' ');
        }

        // BILLALLOCATIONS.LIST for first ALLLEDGERENTRIES.LIST
        $billAllocations1 = $allLedgerEntries1->addChild('BILLALLOCATIONS.LIST');
        $billAllocations1->addChild('NAME', $name);
        $billAllocations1->addChild('BILLTYPE', $billType_receipt);
        $billAllocations1->addChild('TDSDEDUCTEEISSPECIALRATE', 'No');
        $billAllocations1->addChild('AMOUNT', $row['payment_amount']);

        // Empty list elements for BILLALLOCATIONS.LIST
        $emptyLists2 = ['INTERESTCOLLECTION', 'STBILLCATEGORIES'];
        foreach ($emptyLists2 as $listName) {
            $billAllocations1->addChild($listName . '.LIST', ' ');
        }

        // Second ALLLEDGERENTRIES.LIST (for Cheque)
        $allLedgerEntries2 = $vch->addChild('ALLLEDGERENTRIES.LIST');
        $oldAuditEntryIds2 = $allLedgerEntries2->addChild('OLDAUDITENTRYIDS.LIST');
        $oldAuditEntryIds2->addAttribute('TYPE', 'Number');
        $oldAuditEntryIds2->addChild('OLDAUDITENTRYIDS', '-1');

        // Adding the Ledger Name (Cheque)
        $allLedgerEntries2->addChild('LEDGERNAME', $row['payment_mode']);

        // Adding GST Class (Not Applicable in this case)
        $allLedgerEntries2->addChild('GSTCLASS', $escapedDecoded . ' Not Applicable');

        // Is the amount deemed positive (in case of payment through cheque, it can be negative for deduction)
        $allLedgerEntries2->addChild('ISDEEMEDPOSITIVE', 'Yes');

        // Amount of the transaction for the cheque entry
        $allLedgerEntries2->addChild('AMOUNT', $row['payment_amount']);

        // Adding some flags or attributes related to the cheque transaction
        $allLedgerEntries2->addChild('LEDGERFROMITEM', 'No'); // Assuming no items involved in this ledger entry
        $allLedgerEntries2->addChild('REMOVEZEROENTRIES', 'Yes'); // Remove zero entries
        $allLedgerEntries2->addChild('ISPARTYLEDGER', 'No'); // This is a payment ledger, not a party ledger
        $allLedgerEntries2->addChild('ISGSTAPPLICABLE', 'No'); // No GST applicable for the cheque transaction

        // Adding some more fields to reflect payment details, such as cheque number, date, and status
        $allLedgerEntries2->addChild('CHEQUENUMBER', $row['cheque_number']); // Example cheque number
        $allLedgerEntries2->addChild('CHEQUEDATE', $row['cheque_date']); // Example cheque date
        $allLedgerEntries2->addChild('CHEQUEBANKNAME', $row['cheque_bank']); // Example bank name
        $allLedgerEntries2->addChild('ISINCLUDEDINBOOKS', 'Yes'); // Is included in books (payment processed)
        $allLedgerEntries2->addChild('PAYMENTTYPE', $row['payment_mode']); // Payment type is Cheque

        // Adding additional flags
        $allLedgerEntries2->addChild('ISDEEMEDPOSITIVE', 'Yes'); // Ensuring it's positive (as in an expense deduction)
        $allLedgerEntries2->addChild('ISLASTDEEMEDPOSITIVE', 'No'); // Not the last deemed positive
        $allLedgerEntries2->addChild('ISCAPVATNOTCLAIMED', 'Yes'); // Assuming VAT is not claimed
        $allLedgerEntries2->addChild('ISCAPVATTAXALTERED', 'No'); // No alteration to VAT

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
            'TAXTYPEALLOCATIONS',
            'ALLLEDGERENTRIES'
        ];

        // Add empty lists for this ledger entry
        foreach ($emptyLists3 as $listName) {
            $allLedgerEntries2->addChild($listName . '.LIST', ' ');
        }

        // Adding BILLALLOCATIONS.LIST for the cheque entry
        $billAllocations2 = $allLedgerEntries2->addChild('BILLALLOCATIONS.LIST');
        $billAllocations2->addChild('NAME', $name); // Assuming the name is the same as defined earlier
        $billAllocations2->addChild('BILLTYPE', $billType_receipt); // Assuming this is a receipt type
        $billAllocations2->addChild('TDSDEDUCTEEISSPECIALRATE', 'No'); // TDS not applicable
        $billAllocations2->addChild('AMOUNT', $row['payment_amount']); // The amount deducted by cheque

        // Empty lists specific to this bill allocation
        $emptyLists4 = ['INTERESTCOLLECTION', 'STBILLCATEGORIES'];
        foreach ($emptyLists4 as $listName) {
            $billAllocations2->addChild($listName . '.LIST', ' ');
        }


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
    header('Content-Type: application/xml; charset=utf-8');
    echo $xml->asXML();
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    $conn = null;
}
// Or save to a file
// file_put_contents('voucher.xml', $xml->asXML());
?>