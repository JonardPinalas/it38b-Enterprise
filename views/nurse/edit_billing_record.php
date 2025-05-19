<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/billing_records.php';

// Check for form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'bill_id' => $_POST['bill_id'],
        'description' => $_POST['description'],
        'amount' => $_POST['amount'],
        'payment_status' => $_POST['payment_status'],
        'payment_method' => $_POST['payment_method'] ?? null,
        'payment_date' => $_POST['payment_date'] ?? null,
        'invoice_number' => $_POST['invoice_number'] ?? null,
        'notes' => $_POST['notes'] ?? null
    ];

    $result = update_billing_record($data);

    if ($result['success']) {
        header("Location: /it38b-Enterprise/views/nurse/view_billing_record.php?id=" . $result['bill_id'] . "&success=Record+updated+successfully");
        exit();
    } else {
        $error = $result['error'];
    }
}

// Ensure 'id' is present and valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?error=Invalid+record+ID");
    exit();
}

$billId = intval($_GET['id']);

// Fetch billing record with all related information
$record = get_billing_record_by_id($billId);

// Redirect if record not found
if (!$record) {
    header("Location: /it38b-Enterprise/views/nurse/billing_records.php?error=Record+not+found");
    exit();
}

function add_billing_record($data)
{
    global $conn;

    $appointment_id = isset($data['appointment_id']) ? intval($data['appointment_id']) : 0;
    $patient_id = isset($data['patient_id']) ? intval($data['patient_id']) : 0;
    $description = isset($data['description']) ? mysqli_real_escape_string($conn, $data['description']) : '';
    $amount = isset($data['amount']) ? floatval($data['amount']) : 0.00;
    $payment_status = isset($data['payment_status']) ? mysqli_real_escape_string($conn, $data['payment_status']) : 'Pending';
    $payment_method = isset($data['payment_method']) ? mysqli_real_escape_string($conn, $data['payment_method']) : '';
    $invoice_number = isset($data['invoice_number']) ? mysqli_real_escape_string($conn, $data['invoice_number']) : '';
    $notes = isset($data['notes']) ? mysqli_real_escape_string($conn, $data['notes']) : '';
    $record_id = isset($data['record_id']) ? intval($data['record_id']) : null;
    $created_at = date('Y-m-d H:i:s');

    $sql = "INSERT INTO billing_records (
                patient_id,
                description,
                amount,
                payment_status,
                payment_method,
                invoice_number,
                notes,
                record_id,
                created_at
                ";

    if ($appointment_id > 0) {
        $sql .= ", appointment_id";
    }

    $sql .= ") VALUES (
                '$patient_id',
                '$description',
                '$amount',
                '$payment_status',
                '$payment_method',
                '$invoice_number',
                '$notes',
                " . ($record_id !== null ? "'$record_id'" : 'NULL') . ",
                '$created_at'
                ";

    if ($appointment_id > 0) {
        $sql .= ", '$appointment_id'";
    }

    $sql .= ")";

    if (mysqli_query($conn, $sql)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => "Database error: " . mysqli_error($conn)];
    }
}
?>
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Billing Record</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit Billing Record</h1>
                <a href="/it38b-Enterprise/views/nurse/view_billing_record.php?id=<?php echo $billId; ?>"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Record
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="bill_id" value="<?php echo $billId; ?>">

                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="font-medium text-gray-700 mb-2">
                            Patient: <span
                                class="text-gray-600"><?php echo htmlspecialchars($record['patient_name']); ?></span>
                        </p>
                        <p class="font-medium text-gray-700 mb-2">
                            Date: <span
                                class="text-gray-600"><?php echo htmlspecialchars($record['formatted_date']); ?></span>
                        </p>
                    </div>
                    <div>
                        <?php if ($record['appointment_id']): ?>
                            <p class="font-medium text-gray-700 mb-2">
                                Appointment: <span class="text-gray-600">
                                    <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $record['appointment_id']; ?>"
                                        class="text-blue-600 hover:underline">
                                        #<?php echo htmlspecialchars($record['appointment_id']); ?>
                                    </a>
                                </span>
                            </p>
                        <?php endif; ?>
                        <?php if ($record['record_id']): ?>
                            <p class="font-medium text-gray-700 mb-2">
                                Medical Record: <span class="text-gray-600">
                                    <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $record['record_id']; ?>"
                                        class="text-blue-600 hover:underline">
                                        #<?php echo htmlspecialchars($record['record_id']); ?>
                                    </a>
                                </span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-2">Invoice
                        Number</label>
                    <input type="text" name="invoice_number" id="invoice_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        value="<?php echo htmlspecialchars($record['invoice_number'] ?? ''); ?>">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount *</label>
                        <input type="number" name="amount" id="amount" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($record['amount']); ?>" required>
                    </div>
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-2">Payment Status
                            *</label>
                        <select name="payment_status" id="payment_status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            required>
                            <option value="Pending" <?php echo $record['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Partial" <?php echo $record['payment_status'] === 'Partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="Paid" <?php echo $record['payment_status'] === 'Paid' ? 'selected' : ''; ?>>
                                Paid</option>
                            <option value="Cancelled" <?php echo $record['payment_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment
                            Method</label>
                        <input type="text" name="payment_method" id="payment_method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo htmlspecialchars($record['payment_method'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">Payment
                            Date</label>
                        <input type="date" name="payment_date" id="payment_date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            value="<?php echo !empty($record['payment_date']) ? date('Y-m-d', strtotime($record['payment_date'])) : ''; ?>">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea name="description" id="description" rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required><?php echo htmlspecialchars($record['description']); ?></textarea>
                </div>

                <div class="mb-6">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($record['notes'] ?? ''); ?></textarea>
                </div>

                <div class="flex justify-between border-t pt-6">
                    <a href="/it38b-Enterprise/views/nurse/view_billing_record.php?id=<?php echo $billId; ?>"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>