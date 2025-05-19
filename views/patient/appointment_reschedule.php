<?php
// Ensure this file is in the 'views/patient/' directory

// Get the appointment ID from the URL
$appointment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$appointment_id) {
    echo '<div class="p-4"><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">Invalid appointment ID.</span>
          </div></div>';
    exit();
}
?>

<div class="p-4" x-data="rescheduleAppointment({ appointmentId: <?php echo $appointment_id; ?> })">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold">Reschedule Appointment</h2>
            <a href="?page=appointments" class="text-blue-500 hover:text-blue-600">
                Back to Appointments
            </a>
        </div>

        <template x-if="loading">
            <p class="text-gray-600">Loading appointment details...</p>
        </template>

        <template x-if="error">
            <p class="text-red-500" x-text="error"></p>
        </template>

        <template x-if="appointment">
            <form @submit.prevent="submitReschedule" class="space-y-6">
                <div>
                    <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-1">Select Doctor</label>
                    <select x-model="formData.doctor_id" id="doctor_id" required
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        @change="loadAvailableTimeSlots">
                        <option value="">Select a doctor</option>
                        <template x-for="doctor in doctors" :key="doctor.doctor_id">
                            <option :value="doctor.doctor_id" :selected="formData.doctor_id == doctor.doctor_id">
                                Dr. <span x-text="htmlspecialchars(doctor.first_name + ' ' + doctor.last_name)"></span>
                                (<span x-text="htmlspecialchars(doctor.specialization_name)"></span>)
                            </option>
                        </template>
                    </select>
                    <template x-if="!formData.doctor_id">
                        <p class="text-red-500 text-sm mt-1">Please select a doctor.</p>
                    </template>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Date</label>
                        <input type="date" x-model="formData.appointment_date" required :min="today"
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            @change="loadAvailableTimeSlots">
                        <template x-if="!formData.appointment_date">
                            <p class="text-red-500 text-sm mt-1">Please select a date.</p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Time</label>
                        <select x-model="formData.appointment_time" required
                            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a time</option>
                            <template x-for="slot in availableTimeSlots" :key="slot">
                                <option :value="slot" x-text="slot"></option>
                            </template>
                        </select>
                        <template x-if="!formData.appointment_time && availableTimeSlots.length > 0">
                            <p class="text-red-500 text-sm mt-1">Please select a time.</p>
                        </template>
                        <template
                            x-if="availableTimeSlots.length === 0 && formData.doctor_id && formData.appointment_date">
                            <p class="text-yellow-500 text-sm mt-1">No available time slots for the selected doctor and
                                date.</p>
                        </template>
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                    <input type="text" x-model="formData.reason" id="reason" required
                        class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :value="formData.reason" readonly>
                    <p class="text-gray-500 text-sm mt-1">Reason cannot be changed during rescheduling.</p>
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
                        :disabled="isSubmitting || availableTimeSlots.length === 0">
                        <span x-show="!isSubmitting">Reschedule Appointment</span>
                        <span x-show="isSubmitting">Rescheduling...</span>
                    </button>
                </div>
            </form>
        </template>
    </div>
</div>

<script>
    function rescheduleAppointment({ appointmentId }) {
        return {
            appointmentId: appointmentId,
            loading: true,
            error: '',
            appointment: null,
            doctors: [],
            formData: {
                doctor_id: '',
                appointment_date: '',
                appointment_time: '',
                reason: '',
                notes: ''
            },
            today: new Date().toISOString().split('T')[0],
            isSubmitting: false,
            availableTimeSlots: [],

            init() {
                this.fetchAppointmentDetails();
                this.fetchAllDoctors();
            },

            fetchAppointmentDetails() {
                fetch(`/it38b-Enterprise/api/patient/appointments.php?action=get&id=${this.appointmentId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        this.loading = false;
                        if (data.success && data.appointment) {
                            this.appointment = data.appointment;
                            this.formData.doctor_id = this.appointment.doctor_id;
                            this.formData.appointment_date = this.appointment.formatted_date_ymd;
                            this.formData.reason = this.appointment.reason;
                            this.formData.notes = this.appointment.notes;
                            // Load available time slots based on initial doctor and date
                            this.loadAvailableTimeSlots();
                        } else {
                            this.error = data.error || 'Failed to load appointment details.';
                        }
                    })
                    .catch(error => {
                        this.loading = false;
                        this.error = 'Failed to load appointment details. Please try again later.';
                        console.error('Error fetching appointment details:', error);
                    });
            },

            fetchAllDoctors() {
                fetch('/it38b-Enterprise/api/doctors.php?action=list')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP error ' + response.status);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.doctors) {
                            this.doctors = data.doctors;
                        } else {
                            console.error('Failed to load doctors:', data.error || 'Unknown error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching doctors:', error);
                    });
            },

            convertTo24Hour(time12h) {
                const [time, modifier] = time12h.split(' ');
                let [hours, minutes] = time.split(':');

                if (hours === '12') {
                    hours = '00';
                }

                if (modifier === 'PM') {
                    hours = parseInt(hours, 10) + 12;
                }

                return `${hours}:${minutes}`;
            },

            loadAvailableTimeSlots() {
                this.availableTimeSlots = [];
                if (this.formData.doctor_id && this.formData.appointment_date) {
                    fetch(`/it38b-Enterprise/api/doctor/availability.php?doctor_id=${this.formData.doctor_id}&date=${this.formData.appointment_date}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.available) {
                                this.availableTimeSlots = data.timeSlots || [];
                            } else {
                                this.availableTimeSlots = [];
                                console.error('Failed to load availability:', data.error || 'Doctor not available');
                                // Optionally display a message to the user about unavailability
                            }
                        })
                        .catch(error => {
                            console.error('Error loading availability:', error);
                            alert('Failed to load doctor availability.');
                            this.availableTimeSlots = [];
                        });
                }
            },

            submitReschedule() {
                if (!this.formData.doctor_id || !this.formData.appointment_date || !this.formData.appointment_time) {
                    alert('Please select a doctor, date, and time to reschedule.');
                    return;
                }

                this.isSubmitting = true;
                const rescheduleData = {
                    appointment_id: this.appointmentId,
                    appointment_datetime: `${this.formData.appointment_date} ${this.convertTo24Hour(this.formData.appointment_time)}`,
                    reason: this.formData.reason,
                    notes: this.formData.notes || ''
                };

                fetch('/it38b-Enterprise/api/patient/appointments.php?action=reschedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(rescheduleData)
                })
                    .then(response => response.json())
                    .then(data => {
                        this.isSubmitting = false;
                        if (data.success) {
                            alert('Appointment rescheduled successfully!');
                            window.location.href = '?page=appointments';
                        } else {
                            alert('Failed to reschedule appointment: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        this.isSubmitting = false;
                        console.error('Error:', error);
                        alert('Failed to reschedule appointment.');
                    });
            },

            htmlspecialchars(str) {
                if (typeof str !== 'string') return str;
                return str.replace(/[&<>"']/g, function (match) {
                    switch (match) {
                        case '&': return '&amp;';
                        case '<': return '&lt;';
                        case '>': return '&gt;';
                        case '"': return '&quot;';
                        case "'": return '&#039;';
                        default: return match;
                    }
                });
            }
        };
    }
</script>