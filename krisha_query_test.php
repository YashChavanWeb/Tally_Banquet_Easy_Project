<?php

// Database connection details
$host = 'localhost';
$dbname = 'Tallydb';
$username = 'postgres';
$password = '12345678';

try {
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
            b.total AS total_amount
        FROM 
            public.bookings b
        INNER JOIN 
            public.clients c ON b.client = c.id
        WHERE 
            b.reg_date BETWEEN '2024-10-01 00:00:00' AND '2024-12-05 00:00:00'
            AND b.banquet = 1176
        ORDER BY 
            b.reg_date DESC;
    ");

    // Execute the query
    $stmt->execute();

    // Fetch all rows from the query result
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no rows are found, throw an exception
    if (empty($rows)) {
        throw new Exception("No data found in the database.");
    }

    // Initialize SimpleXMLElement for Tally XML message
    $requestData = new SimpleXMLElement('<TALLYMESSAGE></TALLYMESSAGE>');
    $requestData->addAttribute('xmlns:UDF', 'TallyUDF');

    // Iterate through the result set and generate Tally XML nodes
    foreach ($rows as $row) {
        // Generate unique values for remote ID and VCHKEY based on booking ID or any other logic
        $voucher_receipt_id = uniqid('receipt-', true);  // Replace with your logic
        $voucher_receipt_key = uniqid('key-', true);  // Replace with your logic

        // Start the VOUCHER node for each row
        $vch = $requestData->addChild('VOUCHER');
        $vch->addAttribute('REMOTEID', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-' . $voucher_receipt_id);
        $vch->addAttribute('VCHKEY', 'ef1532b1-c551-4b3f-ac45-04402e1668cc-0000b146:' . $voucher_receipt_key);
        $vch->addAttribute('VCHTYPE', 'Receipt');
        $vch->addAttribute('ACTION', 'Create');
        $vch->addAttribute('OBJVIEW', 'Accounting Voucher View');

        // Add details of each receipt (customize as needed)
        $vch->addChild('DATE', date('Ymd', strtotime($row['booking_date'])));
        $vch->addChild('VOUCHERNUMBER', 'RCPT-' . $row['booking_id']);
        $vch->addChild('PARTYNAME', htmlspecialchars($row['client_name']));
        $vch->addChild('AMOUNT', $row['total_amount']);
        // Add other necessary fields, like ledger, taxes, etc.

        // Add more custom logic if necessary, for example, UDFs or other details
    }

    // Output the generated XML (You can save this to a file if needed)
    Header('Content-type: text/xml');
    echo $requestData->asXML();

} catch (PDOException $e) {
    // Handle database connection or query errors
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    // Handle any other errors
    echo "Error: " . $e->getMessage();
}
?>
