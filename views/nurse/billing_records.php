<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/billing_records.php';

$records = get_all_billing_records();
$page_title = "Billing Records";
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
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?php echo $page_title; ?></h1>
            <div class="flex justify-between items-center mb-4">
                <p class="text-gray-600">Manage patient billing records</p>
                <a href="/it38b-Enterprise/views/nurse/choose_appointment_bill.php"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add New Bill
                </a>
            </div>

            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Invoice #
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Patient
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Appointment</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No billing records found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo !empty($record['invoice_number']) ? htmlspecialchars($record['invoice_number']) : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($record['patient_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($record['formatted_date']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">$<?php echo number_format($record['amount'], 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch ($record['payment_status']) {
                                            case 'Paid':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Partial':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                            <?php echo htmlspecialchars($record['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($record['appointment_id']): ?>
                                            <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $record['appointment_id']; ?>"
                                                class="text-blue-600 hover:text-blue-900">
                                                #<?php echo $record['appointment_id']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-500">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="/it38b-Enterprise/views/nurse/view_billing_record.php?id=<?php echo $record['bill_id']; ?>"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/it38b-Enterprise/views/nurse/edit_billing_record.php?id=<?php echo $record['bill_id']; ?>"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-8">
                <a href="/it38b-Enterprise/routes/dashboard_router.php"
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>

</html>