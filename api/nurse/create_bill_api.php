<?php
// Set the content type to application/json
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit();
}

// Get the JSON data from the request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if JSON decoding was successful and if required fields are present
if ($data === null || !isset($data['patient_id']) || !isset($data['description']) || !isset($data['amount'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields: patient_id, description, and amount']);
    exit();
}

// Sanitize and validate the data
$patient_id = intval($data['patient_id']);
$description = isset($data['description']) ? mysqli_real_escape_string($conn, $data['description']) : '';
$amount = floatval($data['amount']);
$payment_status = isset($data['payment_status']) ? mysqli_real_escape_string($conn, $data['payment_status']) : 'Pending';
$payment_method = isset($data['payment_method']) ? mysqli_real_escape_string($conn, $data['payment_method']) : '';
$invoice_number = isset($data['invoice_number']) ? mysqli_real_escape_string($conn, $data['invoice_number']) : '';
$notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : '';
$record_id = isset($data['record_id']) && $data['record_id'] !== '' ? intval($data['record_id']) : null;
$appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : null; // Can be null for standalone
$created_at = date('Y-m-d H:i:s');

$sql = "INSERT INTO billingrecords (
            patient_id,
            description,
            amount,
            payment_status,
            payment_method,
            invoice_number,
            notes,
            record_id,
            appointment_id,
            created_at
        ) VALUES (
            '$patient_id',
            '$description',
            '$amount',
            '$payment_status',
            '$payment_method',
            '$invoice_number',
            '$notes',
            " . ($record_id !== null ? "'$record_id'" : 'NULL') . ",
            " . ($appointment_id !== null ? "'$appointment_id'" : 'NULL') . ",
            '$created_at'
        )";

if (mysqli_query($conn, $sql)) {
    http_response_code(201); // Created
    echo json_encode(['message' => 'Billing record created successfully', 'billing_record_id' => mysqli_insert_id($conn)]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to create billing record', 'details' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>