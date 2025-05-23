<?php
// Get database connection
require_once __DIR__ . '/../../config/config.php';

// Ensure the user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: /login.php'); // Adjust the login path if necessary
    exit();
}

// Fetch all doctors
$doctorQuery = "SELECT d.doctor_id, u.first_name, u.last_name, s.specialization_name
                 FROM doctors d
                 JOIN users u ON d.user_id = u.user_id
                 JOIN specializations s ON d.specialization_id = s.specialization_id
                 ORDER BY u.last_name, u.first_name";
$doctorResult = mysqli_query($conn, $doctorQuery);

// Fetch patient ID
$patientIdQuery = "SELECT patient_id FROM patients WHERE user_id = ?";
$patientIdStmt = mysqli_prepare($conn, $patientIdQuery);
mysqli_stmt_bind_param($patientIdStmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($patientIdStmt);
$patientIdResult = mysqli_stmt_get_result($patientIdStmt);
$patientData = mysqli_fetch_assoc($patientIdResult);
$patientId = $patientData['patient_id'] ?? null;

if (!$patientId) {
    // Handle the case where patient ID cannot be retrieved
    echo "<div class='p-4'><div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative' role='alert'>
            <strong class='font-bold'>Error!</strong>
            <span class='block sm:inline'>Could not retrieve your patient information. Please contact support.</span>
          </div></div>";
    exit();
}
?>

<div class="p-4" x-data="appointmentRequest()">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">Request Appointment</h2>
            <a href="?page=appointments" class="text-blue-500 hover:text-blue-600">
                Back to Appointments
            </a>
        </div>

        <form @submit.prevent="submitRequest" class="space-y-6">
            <div>
                <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                <select x-model="formData.doctor_id" id="doctor_id" required
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    @change="loadAvailableTimes">
                    <option value="">Select a doctor</option>
                    <?php while ($doctor = mysqli_fetch_assoc($doctorResult)): ?>
                        <option value="<?= $doctor['doctor_id'] ?>">
                            Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?>
                            (<?= htmlspecialchars($doctor['specialization_name']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" x-model="formData.appointment_date" required :min="today"
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        @change="loadAvailableTimes">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                    <select x-model="formData.appointment_time" required
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a time</option>
                        <template x-for="timeSlot in availableAppointmentTimes" :key="timeSlot">
                            <option :value="timeSlot" x-text="timeSlot"></option>
                        </template>
                        <option
                            x-show="availableAppointmentTimes.length === 0 && formData.doctor_id && formData.appointment_date"
                            disabled>No available slots for this date.</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                <select x-model="formData.reason" id="reason" required
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Select reason</option>
                    <option value="Regular Checkup">Regular Checkup</option>
                    <option value="Follow-up">Follow-up</option>
                    <option value="Consultation">Consultation</option>
                    <option value="Emergency">Emergency</option>
                    <option value="Laboratory">Laboratory</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes
                    (Optional)</label>
                <textarea x-model="formData.notes" id="notes" rows="3"
                    class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Any additional information..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="?page=appointments"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                    Cancel
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors duration-200"
                    :disabled="isSubmitting">
                    <span x-show="!isSubmitting">Request Appointment</span>
                    <span x-show="isSubmitting">Requesting...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function appointmentRequest() {
        return {
            formData: {
                doctor_id: '',
                appointment_date: '',
                appointment_time: '',
                reason: '',
                notes: '',
                status: 'Requested',
                patient_id: '<?= $patientId ?>' // Inject patient ID
            },
            today: new Date().toISOString().split('T')[0],
            isSubmitting: false,
            availableAppointmentTimes: [], // Array to hold the available time slots

            init() {
                // Initialization logic if needed
            },

            loadAvailableTimes() {
                if (!this.formData.appointment_date || !this.formData.doctor_id) {
                    this.availableAppointmentTimes = []; // Clear times if date or doctor is not selected
                    return;
                }

                // Fetch available times based on the selected date and doctor.
                const availabilityUrl = `/it38b-enterprise/api/doctor/availability.php?date=${this.formData.appointment_date}&doctor_id=${this.formData.doctor_id}`;
                fetch(availabilityUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.timeSlots) { // Use data.timeSlots as per your example URL
                            this.availableAppointmentTimes = data.timeSlots;
                        } else {
                            this.availableAppointmentTimes = [];
                            alert('No available times for the selected date and doctor.');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading available times:', error);
                        alert('Failed to load available times.');
                        this.availableAppointmentTimes = [];
                    });
            },

            submitRequest() {
                if (!this.formData.doctor_id || !this.formData.appointment_date || !this.formData.appointment_time || !this.formData.reason) {
                    alert('Please fill in all required fields.');
                    return;
                }

                this.isSubmitting = true;
                const requestData = {
                    doctor_id: this.formData.doctor_id,
                    appointment_datetime: `${this.formData.appointment_date} ${this.formatTimeTo24Hour(this.formData.appointment_time)}`,
                    reason: this.formData.reason,
                    notes: this.formData.notes || '',
                    status: this.formData.status,
                    patient_id: this.formData.patient_id // Include patient ID in the request
                };

                const appointmentRequestUrl = '/it38b-Enterprise/api/patient/appointments.php?action=create';
                fetch(appointmentRequestUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestData)
                })
                    .then(response => response.json())
                    .then(data => {
                        this.isSubmitting = false;
                        if (data.success) {
                            alert('Appointment requested successfully!');
                            window.location.href = '?page=appointments';
                        } else {
                            alert('Failed to request appointment: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        this.isSubmitting = false;
                        console.error('Error:', error);
                        alert('Failed to request appointment.');
                    });
            },
            formatTimeTo24Hour(time12h) {
                if (!time12h) return null;
                const [time, modifier] = time12h.split(' ');
                let [hours, minutes] = time.split(':');

                if (hours === '12') {
                    hours = '00';
                }

                if (modifier === 'PM' && hours !== '12') {
                    hours = parseInt(hours, 10) + 12;
                }

                return `${hours}:${minutes}:00`;
            }
        };
    }
</script>