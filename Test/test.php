<?php
// db.php - Database connection
$host = 'localhost';
$dbname = 'NewTallydb';
$username = 'postgres';
$password = '12345678';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Fetch bookings based on the date range and banquet ID
function getBookings($startDate, $endDate, $banquetID) {
    global $pdo;

    $sql = "
        SELECT 
            b.id AS booking_id,
            b.datex AS booking_date,
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
            AND b.banquet = :banquet_id;
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate, 'banquet_id' => $banquetID]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handling the API request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['startDate'], $data['endDate'], $data['banquetID'])) {
        $startDate = $data['startDate'];
        $endDate = $data['endDate'];
        $banquetID = $data['banquetID'];

        $bookings = getBookings($startDate, $endDate, $banquetID);

        echo json_encode($bookings);
    } else {
        echo json_encode(['error' => 'Missing parameters']);
    }
}
?>
