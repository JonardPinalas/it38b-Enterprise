<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Store the current URL in the session
$_SESSION['last_page'] = $_SERVER['REQUEST_URI'];

// --- Data Fetching Function ---
function fetchValidAppointmentsForRecordCreation($conn)
{
    $query = "SELECT a.appointment_id, u.first_name AS patient_first_name, u.last_name AS patient_last_name, a.appointment_datetime
              FROM appointments a
              JOIN patients p ON a.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              WHERE a.status IN ('Scheduled', 'Completed')
              ORDER BY a.appointment_datetime DESC";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error fetching appointments: " . mysqli_error($conn));
    }
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// --- Fetch Data ---
$appointments = fetchValidAppointmentsForRecordCreation($conn);

$page_title = "Create New Medical Record";

// Determine the back URL
$back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/it38b-Enterprise/routes/dashboard_router.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Healthcare System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h1>
            <p class="text-gray-600 mb-4">Select an existing appointment to create a new medical record for.</p>

            <?php if (empty($appointments)): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <strong class="font-bold">Information!</strong>
                    <span class="block sm:inline">No valid appointments found to create a medical record for.</span>
                </div>
            <?php else: ?>
                <form method="get" action="/it38b-Enterprise/views/nurse/medical_record_form.php">
                    <div class="mb-4">
                        <label for="appointment_id" class="block text-gray-700 text-sm font-bold mb-2">Select
                            Appointment:</label>
                        <select id="appointment_id" name="appointment_id"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            required>
                            <option value="">-- Select Appointment --</option>
                            <?php foreach ($appointments as $appointment): ?>
                                <option value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
                                    #<?php echo htmlspecialchars($appointment['appointment_id']); ?> -
                                    <?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>
                                    -
                                    <?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($appointment['appointment_datetime']))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus-circle mr-2"></i> Create Medical Record
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-8">
                <a href="<?php echo htmlspecialchars($back_url); ?>"
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
        </div>
    </div>
</body>

</html>
<?php
mysqli_close($conn);
?>