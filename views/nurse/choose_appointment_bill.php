<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Store the current URL in the session
$_SESSION['last_page'] = $_SERVER['REQUEST_URI'];

// API endpoint for creating bills
$api_create_bill_url = 'http://localhost/it38b-Enterprise/api/nurse/create_bill_api.php';

// --- Data Fetching Functions ---
function fetchAppointments($conn)
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

function fetchPatients($conn)
{
    $query = "SELECT p.patient_id, u.first_name, u.last_name
              FROM patients p
              JOIN users u ON p.user_id = u.user_id
              ORDER BY u.last_name, u.first_name";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error fetching patients: " . mysqli_error($conn));
    }
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function fetchMedicalRecords($conn)
{
    $query = "SELECT mr.record_id, u_pat.first_name AS patient_first_name, u_pat.last_name AS patient_last_name, mr.record_datetime
              FROM medicalrecords mr
              JOIN patients pat ON mr.patient_id = pat.patient_id
              JOIN users u_pat ON pat.user_id = u_pat.user_id
              ORDER BY mr.record_datetime DESC";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error fetching medical records: " . mysqli_error($conn));
    }
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// We don't need the fetchDoctors function anymore for the standalone bill creation

// --- Fetch Data ---
$appointments = fetchAppointments($conn);
$patients = fetchPatients($conn);
$medical_records = fetchMedicalRecords($conn);
// $doctors = fetchDoctors($conn); // No need to fetch doctors for standalone anymore

$page_title = "Add New Bill";
$error = '';
$success_message = '';

// --- Handle Standalone Bill Creation via API ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['standalone_bill_api'])) {
    $api_data = [
        'patient_id' => isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0,
        // 'doctor_id' => isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null, // Removed doctor_id
        'description' => isset($_POST['description']) ? $_POST['description'] : '',
        'amount' => isset($_POST['amount']) ? floatval($_POST['amount']) : 0.00,
        'payment_status' => isset($_POST['payment_status']) ? $_POST['payment_status'] : 'Pending',
        'payment_method' => isset($_POST['payment_method']) ? $_POST['payment_method'] : '',
        'invoice_number' => isset($_POST['invoice_number']) ? $_POST['invoice_number'] : '',
        'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
        'record_id' => isset($_POST['record_id']) && $_POST['record_id'] !== '' ? intval($_POST['record_id']) : null,
        'appointment_id' => null // Explicitly set to null for standalone bills
    ];

    // Basic client-side validation
    if ($api_data['patient_id'] <= 0 || empty($api_data['description']) || $api_data['amount'] < 0) {
        $error = "Please fill in all required fields.";
    } else {
        $ch = curl_init($api_create_bill_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $api_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error_number = curl_errno($ch);
        $curl_error_message = curl_error($ch);
        curl_close($ch);

        if ($http_code === 201) {
            $api_result = json_decode($api_response, true);
            if (isset($api_result['message'])) {
                $success_message = $api_result['message'];
                $_POST = []; // Clear the form on success
            } else {
                $error = "Failed to create billing record via API. Unexpected response.";
                if ($api_response) {
                    $error .= " Response: " . $api_response;
                }
            }
        } else {
            $error = "Failed to create billing record via API. HTTP Code: " . $http_code;
            if ($api_response) {
                $error .= ", Response: " . $api_response;
            }
            if ($curl_error_number) {
                $error .= ", cURL Error (" . $curl_error_number . "): " . $curl_error_message;
            }
        }
    }
}

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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const standaloneBillButton = document.getElementById('standaloneBillButton');
            const standaloneBillModal = document.getElementById('standaloneBillModal');
            const closeStandaloneModal = document.getElementById('closeStandaloneModal');

            standaloneBillButton.addEventListener('click', function (event) {
                event.preventDefault();
                standaloneBillModal.classList.remove('hidden');
            });

            closeStandaloneModal.addEventListener('click', function () {
                standaloneBillModal.classList.add('hidden');
            });

            window.addEventListener('click', function (event) {
                if (event.target === standaloneBillModal) {
                    standaloneBillModal.classList.add('hidden');
                }
            });
        });
    </script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h1>
            <p class="text-gray-600 mb-4">Choose an existing appointment to create a new bill for, or create a
                standalone bill.</p>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo $success_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-700 mb-2">Link to Existing Appointment (Optional)</h2>
                <?php if (empty($appointments)): ?>
                    <p class="text-gray-500">No scheduled or completed appointments found.</p>
                <?php else: ?>
                    <form action="/it38b-Enterprise/views/nurse/billing_record_form.php" method="get" class="mb-4">
                        <div class="mb-2">
                            <label for="appointment_id" class="block text-gray-700 text-sm font-bold mb-2">Select
                                Appointment:</label>
                            <select id="appointment_id" name="appointment_id"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
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
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-link mr-2"></i> Create Bill for Selected Appointment
                        </button>
                    </form>
                <?php endif; ?>

                <h2 class="text-lg font-semibold text-gray-700 mb-2">Create Standalone Bill</h2>
                <button id="standaloneBillButton"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-file-invoice mr-2"></i> Create Standalone Bill
                </button>
            </div>

            <div id="standaloneBillModal"
                class="hidden fixed z-10 inset-0 overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div class="p-4 sm:p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800">Create Standalone Bill</h3>
                                <button id="closeStandaloneModal" class="text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <form method="post">
                                <input type="hidden" name="standalone_bill_api" value="1">
                                <div class="mb-4">
                                    <label for="patient_id" class="block text-gray-700 text-sm font-bold mb-2">Select
                                        Patient:</label>
                                    <select id="patient_id" name="patient_id"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                        required>
                                        <option value="">-- Select Patient --</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo htmlspecialchars($patient['patient_id']); ?>">
                                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                (ID: <?php echo htmlspecialchars($patient['patient_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="record_id" class="block text-gray-700 text-sm font-bold mb-2">
                                        Associated Medical Record (Optional)
                                    </label>
                                    <select id="record_id" name="record_id"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <option value="">None</option>
                                        <?php foreach ($medical_records as $record): ?>
                                            <option value="<?php echo htmlspecialchars($record['record_id']); ?>">
                                                #<?php echo htmlspecialchars($record['record_id']); ?> -
                                                <?php echo htmlspecialchars($record['patient_first_name'] . ' ' . $record['patient_last_name']); ?>
                                                (<?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($record['record_datetime']))); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="invoice_number" class="block text-gray-700 text-sm font-bold mb-2">
                                        Invoice Number
                                    </label>
                                    <input type="text" id="invoice_number" name="invoice_number"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">
                                        Description
                                    </label>
                                    <textarea id="description" name="description" rows="3"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                        required></textarea>
                                </div>

                                <div class="mb-4">
                                    <label for="amount" class="block text-gray-700 text-sm font-bold mb-2">
                                        Amount
                                    </label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div
                                            class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center">
                                            <span class="text-gray-500 sm:text-sm">$</span>
                                        </div>
                                        <input type="number" id="amount" name="amount" step="0.01" min="0" required
                                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline pl-7"
                                            value="0.00">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="payment_status" class="block text-gray-700 text-sm font-bold mb-2">
                                        Payment Status
                                    </label>
                                    <select id="payment_status" name="payment_status"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Partial">Partial</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="payment_method" class="block text-gray-700 text-sm font-bold mb-2">
                                        Payment Method
                                    </label>
                                    <select id="payment_method" name="payment_method"
                                        class="shadow appearance-none border rounded w-full py-2px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <option value="">Select Payment Method</option>
                                        <option value="Cash">Cash</option>
                                        <option value="Credit Card">Credit Card</option>
                                        <option value="Debit Card">Debit Card</option>
                                        <option value="Check">Check</option>
                                        <option value="Insurance">Insurance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">
                                        Notes
                                    </label>
                                    <textarea id="notes" name="notes" rows="3"
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                                </div>

                                <div class="flex justify-end">
                                    <button type="button" id="closeStandaloneModal"
                                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded mr-2">
                                        Cancel
                                    </button>
                                    <button type="submit" name="standalone_bill_api"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        <i class="fas fa-save mr-2"></i> Save Standalone Bill (API)
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

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