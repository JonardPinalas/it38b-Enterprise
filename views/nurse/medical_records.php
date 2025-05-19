<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions/medical_records.php';

$records = get_all_medical_records();
$page_title = "Medical Records";
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
    <div class="min-h-screen bg-gray-100 py-6 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-semibold text-gray-900 mb-6"><?php echo $page_title; ?></h1>
            <p class="text-gray-600 mb-4">Review and manage patient medical histories.</p>

            <div class="flex justify-end mb-6">
                <a href="/it38b-Enterprise/views/nurse/choose_medical_record.php"
                    class="inline-flex items-center px-4 py-2 bg-green-500 hover:bg-green-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add New Record
                </a>
            </div>

            <div class="bg-white shadow overflow-hidden rounded-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Patient
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Doctor
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Diagnosis
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Appointment
                                </th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">No medical
                                        records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['patient_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">Dr.
                                                <?php echo htmlspecialchars($record['doctor_name']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($record['formatted_date']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 truncate max-w-md"
                                                title="<?php echo htmlspecialchars($record['diagnosis']); ?>">
                                                <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 70) . (strlen($record['diagnosis']) > 70 ? '...' : '')); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($record['appointment_id']): ?>
                                                <a href="/it38b-Enterprise/views/nurse/appointment_view.php?id=<?php echo $record['appointment_id']; ?>"
                                                    class="text-blue-500 hover:text-blue-700">
                                                    #<?php echo $record['appointment_id']; ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-500">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="/it38b-Enterprise/views/nurse/view_medical_record.php?id=<?php echo $record['record_id']; ?>"
                                                class="text-blue-500 hover:text-blue-700 mr-2" title="View Record">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/it38b-Enterprise/views/nurse/edit_medical_record.php?id=<?php echo $record['record_id']; ?>"
                                                class="text-indigo-500 hover:text-indigo-700 mr-2" title="Edit Record">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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