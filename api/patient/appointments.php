<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get database connection
require_once __DIR__ . '/../../config/config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$response = ['success' => false];

try {
    switch ($action) {
        case 'list':
            // Get appointments based on status filter
            $status = isset($_GET['status']) ? $_GET['status'] : null;

            $query = "
                SELECT a.*,
                       u.first_name as doctor_first_name,
                       u.last_name as doctor_last_name,
                       s.specialization_name,
                       DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as formatted_date,
                       DATE_FORMAT(a.appointment_datetime, '%h:%i %p') as formatted_time
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                JOIN specializations s ON d.specialization_id = s.specialization_id
                WHERE a.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)";

            if ($status) {
                $query .= " AND a.status = ?";
            }
            $query .= " ORDER BY a.appointment_datetime DESC";

            $stmt = mysqli_prepare($conn, $query);
            if ($status) {
                mysqli_stmt_bind_param($stmt, "is", $user_id, $status);
            } else {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $response = [
                'success' => true,
                'appointments' => $appointments
            ];
            break;

        case 'get':
            // Get details of a specific appointment
            $appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if (!$appointment_id) {
                throw new Exception('Invalid appointment ID');
            }

            $query = "
                SELECT a.*,
                       d.doctor_id,
                       u.first_name as doctor_first_name,
                       u.last_name as doctor_last_name,
                       s.specialization_name,
                       DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as formatted_date,
                       DATE_FORMAT(a.appointment_datetime, '%h:%i %p') as formatted_time,
                       DATE_FORMAT(a.appointment_datetime, '%Y-%m-%d') as formatted_date_ymd,
                       DATE_FORMAT(a.appointment_datetime, '%H:%i') as formatted_time_24hr
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                JOIN specializations s ON d.specialization_id = s.specialization_id
                JOIN patients p ON a.patient_id = p.patient_id
                WHERE a.appointment_id = ? AND p.user_id = ?";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $appointment = mysqli_fetch_assoc($result);

            if ($appointment) {
                $response = [
                    'success' => true,
                    'appointment' => $appointment
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Appointment not found or unauthorized'
                ];
            }
            break;

        case 'create':
            // Validate and create new appointment
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                throw new Exception('Invalid request data');
            }

            // Get patient_id
            $patient_query = "SELECT patient_id FROM patients WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $patient_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $patient_result = mysqli_stmt_get_result($stmt);
            $patient = mysqli_fetch_assoc($patient_result);

            if (!$patient) {
                throw new Exception('Patient record not found');
            }

            // Insert appointment
            $insert_query = "
                INSERT INTO appointments (
                    patient_id, doctor_id, appointment_datetime,
                    reason_for_visit, notes, status
                ) VALUES (?, ?, ?, ?, ?, 'Requested')";

            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param(
                $stmt,
                "iisss",
                $patient['patient_id'],
                $data['doctor_id'],
                $data['appointment_datetime'],
                $data['reason'],
                $data['notes']
            );

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'success' => true,
                    'message' => 'Appointment requested successfully',
                    'appointment_id' => mysqli_insert_id($conn)
                ];
            } else {
                throw new Exception('Failed to create appointment');
            }
            break;

        case 'cancel':
            // Cancel an appointment
            $appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if (!$appointment_id) {
                throw new Exception('Invalid appointment ID');
            }

            // Verify ownership
            $verify_query = "
                SELECT a.* FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                WHERE a.appointment_id = ? AND p.user_id = ?";

            $stmt = mysqli_prepare($conn, $verify_query);
            mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (!mysqli_fetch_assoc($result)) {
                throw new Exception('Unauthorized to cancel this appointment');
            }

            // Update appointment status
            $update_query = "UPDATE appointments SET status = 'Cancelled' WHERE appointment_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "i", $appointment_id);

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'success' => true,
                    'message' => 'Appointment cancelled successfully'
                ];
            } else {
                throw new Exception('Failed to cancel appointment');
            }
            break;

        case 'reschedule':
            // Reschedule an appointment
            $data = json_decode(file_get_contents('php://input'), true);
            $appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;

            if (!$appointment_id || !isset($data['appointment_datetime'])) {
                throw new Exception('Invalid request data for rescheduling');
            }

            // Verify ownership
            $verify_query = "
                SELECT a.* FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                WHERE a.appointment_id = ? AND p.user_id = ?";

            $stmt = mysqli_prepare($conn, $verify_query);
            mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (!mysqli_fetch_assoc($result)) {
                throw new Exception('Unauthorized to reschedule this appointment');
            }

            // Update appointment
            $update_query = "
                UPDATE appointments
                SET appointment_datetime = ?,
                    status = 'Scheduled',
                    notes = CONCAT(IFNULL(notes, ''), IF(notes IS NULL, '', '\\n'), 'Rescheduled on: ', NOW())
                WHERE appointment_id = ?";

            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $data['appointment_datetime'], $appointment_id);

            if (mysqli_stmt_execute($stmt)) {
                $response = [
                    'success' => true,
                    'message' => 'Appointment rescheduled successfully'
                ];
            } else {
                throw new Exception('Failed to reschedule appointment');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);