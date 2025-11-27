// ==================== Notification System ====================

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {string} type - Type of notification: 'success', 'error', 'warning', 'info'
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');

    // Icon and color based on type
    const config = {
        success: { icon: 'fa-check-circle', bg: 'bg-green-500', text: 'text-white' },
        error: { icon: 'fa-times-circle', bg: 'bg-red-500', text: 'text-white' },
        warning: { icon: 'fa-exclamation-triangle', bg: 'bg-yellow-500', text: 'text-white' },
        info: { icon: 'fa-info-circle', bg: 'bg-blue-500', text: 'text-white' }
    };

    const { icon, bg, text } = config[type] || config.info;

    toast.className = `${bg} ${text} px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px] max-w-md transform transition-all duration-300 translate-x-full opacity-0`;
    toast.innerHTML = `
        <i class="fas ${icon} text-xl"></i>
        <span class="flex-1 text-sm font-medium">${message}</span>
        <button class="ml-2 hover:opacity-75 transition-opacity" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    }, 10);

    // Auto-dismiss after 4 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

/**
 * Show a confirmation dialog
 * @param {string} title - Dialog title
 * @param {string} message - Dialog message
 * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
 */
function showConfirm(title, message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const okBtn = document.getElementById('confirmOk');
        const cancelBtn = document.getElementById('confirmCancel');

        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.classList.remove('hidden');

        function cleanup() {
            modal.classList.add('hidden');
            okBtn.removeEventListener('click', handleOk);
            cancelBtn.removeEventListener('click', handleCancel);
        }

        function handleOk() {
            cleanup();
            resolve(true);
        }

        function handleCancel() {
            cleanup();
            resolve(false);
        }

        okBtn.addEventListener('click', handleOk);
        cancelBtn.addEventListener('click', handleCancel);
    });
}

// ==================== Main Application ====================

document.addEventListener('DOMContentLoaded', () => {
    // State
    let currentVehicleId = null;
    let fuelEntries = [];
    let maintenanceEntries = [];
    let expenses = []; // Keep expenses here for now, will be updated by fetchExpenses
    let categories = []; // Keep categories here for now, will be updated by fetchCategories
    let settings = {
        currency: '৳',
        distanceUnit: 'km',
        volumeUnit: 'L',
        entriesPerPage: 10
    };
    let currentMonth = new Date();
    let selectedCategory = 'all';

    // Pagination State
    const paginationState = {
        fuel: { page: 1, limit: 10 },
        expenses: { page: 1, limit: 10 },
        maintenance: { page: 1, limit: 10 }
    };

    // Elements
    const vehicleSelect = document.getElementById('vehicleSelect');
    const addVehicleBtn = document.getElementById('addVehicleBtn');
    const editVehicleBtn = document.getElementById('editVehicleBtn');
    const deleteVehicleBtn = document.getElementById('deleteVehicleBtn');
    const fuelHistoryList = document.getElementById('fuelList');
    const maintenanceList = document.getElementById('maintenanceList');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Modals
    const fuelModal = document.getElementById('fuelModal');
    const vehicleModal = document.getElementById('vehicleModal');
    const expenseModal = document.getElementById('expenseModal');
    const maintenanceModal = document.getElementById('maintenanceModal');

    // Forms
    const fuelForm = document.getElementById('fuelForm');
    const vehicleForm = document.getElementById('vehicleForm');
    const settingsForm = document.getElementById('settingsForm');
    const expenseForm = document.getElementById('expenseForm');
    const maintenanceForm = document.getElementById('maintenanceForm');

    // Init
    init();

    // Helper: Render Pagination Controls
    function renderPagination(totalEntries, currentPage, limit, containerId, onPageChange) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        const totalPages = Math.ceil(totalEntries / limit);

        if (totalPages <= 1) return;

        // Prev Button
        const prevBtn = document.createElement('button');
        prevBtn.className = `px-3 py-1 rounded border ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`;
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = () => onPageChange(currentPage - 1);
        container.appendChild(prevBtn);

        // Page Info
        const info = document.createElement('span');
        info.className = 'px-3 py-1 text-sm text-gray-600 dark:text-gray-300 flex items-center';
        info.textContent = `Page ${currentPage} of ${totalPages}`;
        container.appendChild(info);

        // Next Button
        const nextBtn = document.createElement('button');
        nextBtn.className = `px-3 py-1 rounded border ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed dark:bg-gray-700 dark:text-gray-500' : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'}`;
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.onclick = () => onPageChange(currentPage + 1);
        container.appendChild(nextBtn);
    }

    async function init() {
        await fetchSettings();
        await fetchVehicles();
        await fetchCategories();
        setupEventListeners();
        updateMonthDisplay();
    }

    function setupEventListeners() {
        // Tabs
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.dataset.tab;

                // Update UI
                tabBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                tabContents.forEach(c => c.classList.remove('active'));
                document.getElementById(tab).classList.add('active');

                // Load data if needed
                if (tab === 'analytics') {
                    loadAnalytics();
                    loadBikeAnalytics();
                }
                if (tab === 'daily-costs') fetchExpenses();
                if (tab === 'vehicle') {
                    fetchMaintenanceData();
                    fetchDocuments();
                }
            });
        });

        // Vehicle Sub-tabs
        const vehicleSubTabs = document.querySelectorAll('.vehicle-sub-tab');
        const vehicleSubContents = document.querySelectorAll('.vehicle-sub-content');

        vehicleSubTabs.forEach(btn => {
            btn.addEventListener('click', () => {
                const subtabId = btn.dataset.subtab;

                // Update Buttons
                vehicleSubTabs.forEach(b => {
                    b.classList.remove('active', 'bg-white', 'shadow-sm', 'text-gray-700', 'dark:text-gray-200');
                    b.classList.add('text-gray-500', 'dark:text-gray-400');
                });
                btn.classList.add('active', 'bg-white', 'shadow-sm', 'text-gray-700', 'dark:text-gray-200');
                btn.classList.remove('text-gray-500', 'dark:text-gray-400');

                // Update Content
                vehicleSubContents.forEach(c => c.classList.add('hidden'));
                document.getElementById(subtabId).classList.remove('hidden');

                if (subtabId === 'vehicle-documents') {
                    fetchDocuments();
                } else if (subtabId === 'vehicle-details') {
                    renderVehicleDetails();
                }
            });
        });

        // Settings Sub-tabs
        const settingsSubTabs = document.querySelectorAll('.settings-sub-tab');
        const settingsSubContents = document.querySelectorAll('.settings-sub-content');

        settingsSubTabs.forEach(btn => {
            btn.addEventListener('click', () => {
                const subtabId = btn.dataset.subtab;

                // Update Buttons
                settingsSubTabs.forEach(b => {
                    b.classList.remove('active', 'bg-white', 'shadow-sm', 'text-gray-700', 'dark:text-gray-200');
                    b.classList.add('text-gray-500', 'dark:text-gray-400');
                });
                btn.classList.add('active', 'bg-white', 'shadow-sm', 'text-gray-700', 'dark:text-gray-200');
                btn.classList.remove('text-gray-500', 'dark:text-gray-400');

                // Update Content
                settingsSubContents.forEach(c => c.classList.add('hidden'));
                document.getElementById(subtabId).classList.remove('hidden');

                // Fetch security question when Security tab is clicked
                if (subtabId === 'settings-security') {
                    fetchSecurityQuestion();
                    fetchCurrentSecurityQuestion();
                }
            });
        });

        // Vehicle Select
        vehicleSelect.addEventListener('change', async () => {
            currentVehicleId = vehicleSelect.value;

            // Show/hide edit and delete buttons
            if (currentVehicleId) {
                editVehicleBtn.classList.remove('hidden');
                deleteVehicleBtn.classList.remove('hidden');
            } else {
                editVehicleBtn.classList.add('hidden');
                deleteVehicleBtn.classList.add('hidden');
            }

            if (currentVehicleId) {
                // Reset pagination state for new vehicle
                paginationState.fuel.page = 1;
                paginationState.maintenance.page = 1;

                fetchFuelData();
                fetchMaintenanceData();
                fetchDocuments();
                loadBikeAnalytics();
                renderVehicleDetails();
            }
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch('api/auth/logout.php');
            window.location.href = 'login.php';
        });

        // Modals
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                fuelModal.classList.add('hidden');
                vehicleModal.classList.add('hidden');
                expenseModal.classList.add('hidden');
                maintenanceModal.classList.add('hidden');
            });
        });

        document.getElementById('addFuelBtn').addEventListener('click', () => {
            if (!currentVehicleId) return showToast('Please select a vehicle first', 'warning');
            document.getElementById('fuelId').value = '';
            document.getElementById('fuelForm').reset();
            // Set default date
            document.getElementById('fuelDate').value = new Date().toISOString().slice(0, 16);
            // Reset button text and title
            document.getElementById('fuelModalTitle').textContent = 'Add Fuel Entry';
            const btn = fuelForm.querySelector('button[type="submit"]');
            btn.innerHTML = '<i class="fas fa-plus mr-1"></i> Add';
            fuelModal.classList.remove('hidden');
        });

        addVehicleBtn.addEventListener('click', () => {
            vehicleForm.reset();
            document.getElementById('vehicleId').value = '';
            document.getElementById('vehicleModalTitle').textContent = 'Add Vehicle';
            vehicleModal.classList.remove('hidden');
        });

        editVehicleBtn.addEventListener('click', () => {
            if (!currentVehicleId) return;
            editVehicle(currentVehicleId);
        });

        deleteVehicleBtn.addEventListener('click', () => {
            if (!currentVehicleId) return;
            deleteVehicle(currentVehicleId);
        });

        // Forms
        fuelForm.addEventListener('submit', handleFuelSubmit);
        vehicleForm.addEventListener('submit', handleVehicleSubmit);
        settingsForm.addEventListener('submit', saveSettings); // Changed to saveSettings
        expenseForm.addEventListener('submit', handleExpenseSubmit);
        maintenanceForm.addEventListener('submit', handleMaintenanceSubmit);

        // Password change form
        document.getElementById('changePasswordForm').addEventListener('submit', handlePasswordChange);

        // Security question update form
        document.getElementById('updateSecurityQuestionForm').addEventListener('submit', handleSecurityQuestionUpdate);

        // Maintenance button
        document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
            if (!currentVehicleId) return showToast('Please select a vehicle first', 'warning');
            document.getElementById('maintenanceId').value = '';
            maintenanceForm.reset();
            document.getElementById('maintenanceDate').value = new Date().toISOString().slice(0, 16);
            maintenanceModal.classList.remove('hidden');
        });

        // Expense buttons
        document.getElementById('addExpenseBtn').addEventListener('click', () => {
            document.getElementById('expenseId').value = '';
            expenseForm.reset();
            document.getElementById('expenseDate').value = new Date().toISOString().slice(0, 16);
            expenseModal.classList.remove('hidden');
        });

        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', () => {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        });

        // Month navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            updateMonthDisplay();
            fetchExpenses();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            updateMonthDisplay();
            fetchExpenses();
        });

        // Auto-Calc Logic
        const totalCostInput = document.getElementById('fuelTotalCost');
        const priceInput = document.getElementById('fuelCostPerLiter');
        const litersInput = document.getElementById('fuelLiters');
        const disableAutoCalcBtn = document.getElementById('disableAutoCalcBtn');
        let autoCalcEnabled = true;

        function calcLiters() {
            if (!autoCalcEnabled) return;
            const total = parseFloat(totalCostInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            if (total > 0 && price > 0) {
                litersInput.value = (total / price).toFixed(2);
            }
        }

        totalCostInput.addEventListener('input', calcLiters);
        priceInput.addEventListener('input', calcLiters);

        disableAutoCalcBtn.addEventListener('click', () => {
            autoCalcEnabled = !autoCalcEnabled;
            disableAutoCalcBtn.classList.toggle('bg-red-100');
            disableAutoCalcBtn.classList.toggle('text-red-700');
            disableAutoCalcBtn.innerHTML = autoCalcEnabled ?
                '<i class="fas fa-calculator mr-1"></i> Disable Auto-Calc' :
                '<i class="fas fa-calculator mr-1"></i> Enable Auto-Calc';

            if (autoCalcEnabled) calcLiters();
        });

        // Document upload
        document.getElementById('documentUpload').addEventListener('change', handleDocumentUpload);

        // Sticky tabs with smooth shadow on scroll
        const tabsContainer = document.getElementById('tabsContainer');
        let lastScrollY = window.scrollY;

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;

            if (currentScrollY > 100) {
                tabsContainer.classList.add('shadow-lg');
            } else {
                tabsContainer.classList.remove('shadow-lg');
            }

            lastScrollY = currentScrollY;
        });
    }

    // API Calls
    async function fetchSettings() {
        try {
            const res = await fetch('api/settings/index.php');
            const data = await res.json();
            settings = { ...settings, ...data }; // Merge fetched settings with defaults

            // Populate settings form
            document.getElementById('settingCurrency').value = settings.currency || 'BDT';
            document.getElementById('settingDistanceUnit').value = settings.distanceUnit || 'km';
            document.getElementById('settingVolumeUnit').value = settings.volumeUnit || 'L';
            document.getElementById('settingEntriesPerPage').value = settings.entriesPerPage || 10;

            // Update pagination limits
            const limit = parseInt(settings.entriesPerPage) || 10;
            paginationState.fuel.limit = limit;
            paginationState.expenses.limit = limit;
            paginationState.maintenance.limit = limit;

            // Load analytics after settings are fetched and pagination limits are set
            loadAnalytics();
        } catch (e) {
            console.error('Error fetching settings', e);
        }
    }

    async function fetchVehicles() {
        try {
            const res = await fetch('api/vehicles/index.php');
            const data = await res.json();
            vehicleSelect.innerHTML = '<option value="">Select a vehicle...</option>';

            // Store vehicles globally for details rendering
            window.vehicles = data.vehicles;

            // Sort by displayOrder (ascending), then by name
            data.vehicles.sort((a, b) => {
                const orderA = a.displayOrder !== undefined ? a.displayOrder : 999;
                const orderB = b.displayOrder !== undefined ? b.displayOrder : 999;
                if (orderA !== orderB) return orderA - orderB;
                return a.name.localeCompare(b.name);
            });
            data.vehicles.forEach(v => {
                const option = document.createElement('option');
                option.value = v.id;
                option.textContent = v.name;
                option.dataset.displayOrder = v.displayOrder || 0;
                vehicleSelect.appendChild(option);
            });

            if (data.vehicles.length > 0) {
                currentVehicleId = data.vehicles[0].id;
                vehicleSelect.value = currentVehicleId;

                // Show edit and delete buttons
                editVehicleBtn.classList.remove('hidden');
                deleteVehicleBtn.classList.remove('hidden');

                fetchFuelData();
                fetchMaintenanceData();
                fetchDocuments();
                renderVehicleDetails();
            }
        } catch (e) {
            console.error('Error fetching vehicles', e);
        }
    }

    // Assuming editVehicle function is defined here or above this point
    // For the purpose of this edit, we'll place deleteVehicle right before fetchFuelData
    // as per the instruction's implied placement.

    async function deleteVehicle(id) {
        const confirmed = await showConfirm(
            'Delete Vehicle',
            'Are you sure you want to delete this vehicle? All associated data (fuel logs, expenses, maintenance) will also be deleted.'
        );
        if (!confirmed) return;

        try {
            const res = await fetch(`api/vehicles/index.php?id=${id}`, {
                method: 'DELETE'
            });

            if (res.ok) {
                showToast('Vehicle deleted successfully', 'success');
                currentVehicleId = null;
                await fetchVehicles();
                // Clear all data displays
                document.getElementById('fuelList').innerHTML = '';
                document.getElementById('expensesList').innerHTML = ''; // Assuming this element exists
                document.getElementById('maintenanceList').innerHTML = '';
            } else {
                const data = await res.json();
                showToast(data.error || 'Failed to delete vehicle', 'error');
            }
        } catch (e) {
            console.error('Error deleting vehicle', e);
            showToast('An error occurred. Please try again.', 'error');
        }
    }

    async function fetchFuelData() {
        if (!currentVehicleId) return;

        document.getElementById('fuelLoading').classList.remove('hidden');
        document.getElementById('fuelList').innerHTML = ''; // Clear list before loading
        document.getElementById('noFuelEntries').classList.add('hidden');

        try {
            const { page, limit } = paginationState.fuel;
            const res = await fetch(`api/fuel/index.php?vehicleId=${currentVehicleId}&page=${page}&limit=${limit}`);
            const data = await res.json();

            // Handle new response structure
            fuelEntries = data.entries || [];
            const stats = data.stats || {}; // Not directly used here, but good to have
            const pagination = data.pagination || {};

            renderFuelHistory();
            renderPagination(pagination.total, pagination.page, pagination.limit, 'fuelPagination', (newPage) => {
                paginationState.fuel.page = newPage;
                fetchFuelData();
            });

            // Update stats if needed (though loadBikeAnalytics handles most)
        } catch (e) {
            console.error('Error fetching fuel data', e);
        } finally {
            document.getElementById('fuelLoading').classList.add('hidden');
        }
    }

    async function fetchMaintenanceData() {
        if (!currentVehicleId) return;

        document.getElementById('maintenanceLoading').classList.remove('hidden');
        document.getElementById('maintenanceList').innerHTML = ''; // Clear list before loading
        document.getElementById('noMaintenanceEntries').classList.add('hidden'); // Assuming you have this element

        try {
            const { page, limit } = paginationState.maintenance;
            const res = await fetch(`api/maintenance-cost/index.php?vehicleId=${currentVehicleId}&page=${page}&limit=${limit}`);
            const data = await res.json();
            maintenanceEntries = data.entries || [];
            const pagination = data.pagination || {};

            renderMaintenanceHistory();
            renderPagination(pagination.total, pagination.page, pagination.limit, 'maintenancePagination', (newPage) => {
                paginationState.maintenance.page = newPage;
                fetchMaintenanceData();
            });
        } catch (e) {
            console.error('Error fetching maintenance', e);
        } finally {
            document.getElementById('maintenanceLoading').classList.add('hidden');
        }
    }

    // Handlers
    async function handleFuelSubmit(e) {
        e.preventDefault();
        const formData = new FormData(fuelForm);
        const data = Object.fromEntries(formData.entries());
        data.vehicleId = currentVehicleId;
        data.fuelType = document.getElementById('fuelPartial').checked ? 'PARTIAL' : 'FULL';

        const method = data.id ? 'PUT' : 'POST';

        try {
            const res = await fetch('api/fuel/index.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                fuelModal.classList.add('hidden');
                fetchFuelData();
                loadBikeAnalytics(); // Reload analytics after fuel entry
                showToast('Fuel entry saved successfully', 'success');
            } else {
                showToast('Failed to save fuel entry', 'error');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function handleVehicleSubmit(e) {
        e.preventDefault();
        const formData = new FormData(vehicleForm);
        const data = Object.fromEntries(formData.entries());

        const method = data.id ? 'PUT' : 'POST';

        try {
            const res = await fetch('api/vehicles/index.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                vehicleModal.classList.add('hidden');
                fetchVehicles();
                showToast('Vehicle saved successfully', 'success');
                // If current vehicle was updated, re-render details
                if (data.id && data.id === currentVehicleId) {
                    renderVehicleDetails();
                }
            } else {
                showToast('Failed to save vehicle', 'error');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function saveSettings(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('api/settings/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (res.ok) {
                // Update local settings immediately
                settings = { ...settings, ...data };

                // Update pagination limits and reload data
                const limit = parseInt(settings.entriesPerPage) || 10;
                paginationState.fuel.limit = limit;
                paginationState.expenses.limit = limit;
                paginationState.maintenance.limit = limit;

                // Reset pages to 1
                paginationState.fuel.page = 1;
                paginationState.expenses.page = 1;
                paginationState.maintenance.page = 1;

                if (currentVehicleId) {
                    fetchFuelData();
                    fetchMaintenanceData();
                    loadBikeAnalytics();
                }
                fetchExpenses();

                showToast('Settings saved successfully', 'success');
            } else {
                showToast(result.error, 'error');
            }
        } catch (e) {
            console.error('Error saving settings', e);
        }
    }

    async function handleMaintenanceSubmit(e) {
        e.preventDefault();
        const formData = new FormData(maintenanceForm);
        const data = Object.fromEntries(formData.entries());
        data.vehicleId = currentVehicleId;
        const method = data.id ? 'PUT' : 'POST';
        try {
            const res = await fetch('api/maintenance-cost/index.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.ok) {
                maintenanceModal.classList.add('hidden');
                fetchMaintenanceData();
                loadBikeAnalytics();
                showToast('Maintenance entry saved successfully', 'success');
            } else {
                showToast('Failed to save maintenance cost', 'error');
            }
        } catch (e) {
            console.error(e);
        }
    }

    // Password Change Functions
    async function fetchSecurityQuestion() {
        try {
            const res = await fetch('api/auth/change-password.php');
            const data = await res.json();

            if (res.ok && data.question) {
                document.getElementById('securityQuestionDisplay').textContent = data.question;
            } else {
                document.getElementById('securityQuestionDisplay').textContent = 'No security question found';
            }
        } catch (e) {
            console.error('Error fetching security question', e);
            document.getElementById('securityQuestionDisplay').textContent = 'Error loading question';
        }
    }

    async function handlePasswordChange(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // Validate passwords match
        if (data.newPassword !== data.confirmPassword) {
            showToast('New passwords do not match', 'error');
            return;
        }

        // Validate password length
        if (data.newPassword.length < 6) {
            showToast('New password must be at least 6 characters long', 'error');
            return;
        }

        try {
            const res = await fetch('api/auth/change-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    securityAnswer: data.securityAnswer,
                    currentPassword: data.currentPassword,
                    newPassword: data.newPassword
                })
            });

            const result = await res.json();

            if (res.ok) {
                showToast('Password changed successfully!', 'success');
                // Clear form
                e.target.reset();
            } else {
                showToast(result.error || 'Failed to change password', 'error');
            }
        } catch (e) {
            console.error('Error changing password', e);
            showToast('An error occurred. Please try again.', 'error');
        }
    }

    // Security Question Management Functions
    async function fetchCurrentSecurityQuestion() {
        try {
            const res = await fetch('api/auth/security-question.php');
            const data = await res.json();

            if (res.ok && data.question) {
                document.getElementById('currentSecurityQuestion').textContent = data.question;
            } else {
                document.getElementById('currentSecurityQuestion').textContent = 'No security question set';
            }
        } catch (e) {
            console.error('Error fetching current security question', e);
            document.getElementById('currentSecurityQuestion').textContent = 'Error loading question';
        }
    }

    async function handleSecurityQuestionUpdate(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('api/auth/security-question.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    newQuestion: data.newSecurityQuestion,
                    newAnswer: data.newSecurityAnswer,
                    currentPassword: data.verifyPassword
                })
            });

            const result = await res.json();

            if (res.ok) {
                showToast('Security question updated successfully!', 'success');
                // Clear form and refresh
                e.target.reset();
                fetchCurrentSecurityQuestion();
                fetchSecurityQuestion(); // Update the password change form too
            } else {
                showToast(result.error || 'Failed to update security question', 'error');
            }
        } catch (e) {
            console.error('Error updating security question', e);
            showToast('An error occurred. Please try again.', 'error');
        }
    }

    // Renderers
    function renderFuelHistory() {
        fuelHistoryList.innerHTML = '';
        if (fuelEntries.length === 0) {
            document.getElementById('noFuelEntries').classList.remove('hidden');
            return;
        }
        document.getElementById('noFuelEntries').classList.add('hidden');

        // Sort entries by date descending
        fuelEntries.sort((a, b) => new Date(b.date) - new Date(a.date));

        fuelEntries.forEach((entry, index) => {
            let distance = 0;
            let mileage = 0;
            let efficiencyClass = '';
            let efficiencyText = '';
            let showStats = false;
            let accumulatedLiters = parseFloat(entry.liters);
            const isPartial = entry.fuelType === 'PARTIAL';

            // Calculate stats only for FULL entries
            let totalFuelUsed = 0;
            let totalCostUsed = 0;

            // Calculate stats only for FULL entries
            if (!isPartial) {
                // Find previous FULL entry
                let prevFullEntry = null;
                let intermediateLiters = 0;

                for (let i = index + 1; i < fuelEntries.length; i++) {
                    if (fuelEntries[i].fuelType !== 'PARTIAL') {
                        prevFullEntry = fuelEntries[i];
                        break;
                    }
                    // Add partial fuel to accumulated liters
                    intermediateLiters += parseFloat(fuelEntries[i].liters);
                }

                if (prevFullEntry) {
                    distance = parseFloat(entry.odometer) - parseFloat(prevFullEntry.odometer);
                    // Mileage = Distance / (Previous Full Liters + Intermediate Partials)
                    totalFuelUsed = parseFloat(prevFullEntry.liters) + intermediateLiters;
                    // Calculate total cost used (Previous Full Cost + Intermediate Partials Cost)
                    let intermediateCost = 0;
                    for (let i = index + 1; i < fuelEntries.length; i++) {
                        if (fuelEntries[i].fuelType !== 'PARTIAL') break;
                        intermediateCost += parseFloat(fuelEntries[i].totalCost);
                    }
                    totalCostUsed = parseFloat(prevFullEntry.totalCost) + intermediateCost;

                    mileage = distance / totalFuelUsed;
                    showStats = true;

                    if (mileage > 45) {
                        efficiencyText = 'Efficiency: Excellent!';
                        efficiencyClass = 'text-green-600 bg-green-50 border-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400';
                    } else if (mileage > 35) {
                        efficiencyText = 'Efficiency: Good';
                        efficiencyClass = 'text-purple-600 bg-purple-50 border-purple-100 dark:bg-purple-900/20 dark:border-purple-800 dark:text-purple-400';
                    } else if (mileage > 25) {
                        efficiencyText = 'Efficiency: Average';
                        efficiencyClass = 'text-yellow-600 bg-yellow-50 border-yellow-100 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-400';
                    } else {
                        efficiencyText = 'Efficiency: Poor';
                        efficiencyClass = 'text-red-600 bg-red-50 border-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400';
                    }
                }
            }

            const el = document.createElement('div');
            el.className = 'bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 mb-4';
            el.innerHTML = `
                <!-- Header -->
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-start">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-gray-600 dark:text-gray-300">
                        <div class="flex items-center gap-2">
                            <i class="far fa-calendar"></i>
                            <span class="font-medium">${moment(entry.date).format('D MMM YYYY HH:mm')}</span>
                        </div>
                        ${entry.location ? `
                        <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 w-fit">
                            <i class="fas fa-map-marker-alt"></i> ${entry.location}
                        </span>` : ''}
                        ${isPartial ? `
                        <span class="bg-yellow-100 dark:bg-yellow-900/30 px-2 py-0.5 rounded text-xs text-yellow-600 dark:text-yellow-400 flex items-center gap-1 w-fit border border-yellow-200 dark:border-yellow-800">
                            <i class="fas fa-gas-pump"></i> Partial Fill
                        </span>` : ''}
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editFuelEntry('${entry.id}')" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 rounded" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteFuelEntry('${entry.id}')" class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Fuel Cost</div>
                        <div class="font-bold text-gray-800 dark:text-white">${settings.currency || 'BDT'} ${parseFloat(entry.totalCost).toFixed(2)}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Fuel Price/L</div>
                        <div class="font-bold text-gray-800 dark:text-white">${settings.currency || 'BDT'} ${parseFloat(entry.costPerLiter).toFixed(2)}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Total Fuel</div>
                        <div class="font-bold text-gray-800 dark:text-white">${parseFloat(entry.liters).toFixed(2)} L</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Odometer</div>
                        <div class="font-bold text-gray-800 dark:text-white">${entry.odometer} km</div>
                    </div>
                </div>

                <!-- Efficiency Section -->
                ${showStats ? `
                <div class="${efficiencyClass.replace('text-', 'border-').split(' ')[2]} ${efficiencyClass.split(' ')[1]} p-3 mx-4 mb-4 rounded-lg border">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-1 sm:gap-2 ${efficiencyClass.split(' ')[0]}">
                            <i class="fas fa-route text-xs sm:text-sm"></i>
                            <span class="text-xs sm:text-sm hidden sm:inline">Distance</span>
                            <span class="text-xs sm:text-sm sm:hidden">Dist.</span>
                            <span class="font-bold text-xs sm:text-sm">${distance.toFixed(1)} km</span>
                        </div>
                        <div class="flex items-center gap-1 sm:gap-2 ${efficiencyClass.split(' ')[0]}">
                            <i class="fas fa-tachometer-alt text-xs sm:text-sm"></i>
                            <span class="text-xs sm:text-sm hidden sm:inline">Mileage</span>
                            <span class="text-xs sm:text-sm sm:hidden">Avg.</span>
                            <span class="font-bold text-xs sm:text-sm">${mileage.toFixed(2)} km/L</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mb-1 text-[10px] sm:text-xs ${efficiencyClass.split(' ')[0]} opacity-90">
                         <div class="flex items-center gap-1">
                            <i class="fas fa-gas-pump text-[10px]"></i>
                            <span>Trip Fuel: ${totalFuelUsed.toFixed(2)} L</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-coins text-[10px]"></i>
                            <span>Trip Cost: ${settings.currency || 'BDT'} ${totalCostUsed.toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="text-center text-[10px] sm:text-xs ${efficiencyClass.split(' ')[0]} border-t ${efficiencyClass.split(' ')[2]} pt-1 mt-1">
                        ${efficiencyText}
                    </div>
                </div>
                ` : ''}
            `;
            fuelHistoryList.appendChild(el);
        });
    }

    function renderMaintenanceHistory() {
        maintenanceList.innerHTML = '';
        if (maintenanceEntries.length === 0) {
            document.getElementById('noMaintenanceEntries').classList.remove('hidden');
            return;
        }
        document.getElementById('noMaintenanceEntries').classList.add('hidden');

        maintenanceEntries.forEach(entry => {
            const el = document.createElement('div');
            el.className = 'bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex justify-between items-center';
            el.innerHTML = `
            <div>
                    <div class="font-medium dark:text-white">${entry.description}</div>
                    ${entry.notes ? `<div class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">${entry.notes}</div>` : ''}
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">${moment(entry.date).format('MMM D, YYYY')}</div>
                    <div class="text-xs text-gray-400">${entry.category || 'General'}</div>
                </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <div class="font-bold text-orange-600">${settings.currency || '৳'}${entry.cost}</div>
                </div>
                <div class="flex gap-1">
                    <button onclick="editMaintenanceEntry('${entry.id}')" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 rounded" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteMaintenanceEntry('${entry.id}')" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            `;
            maintenanceList.appendChild(el);
        });
    }

    function renderVehicleDetails() {
        const container = document.getElementById('vehicleDetailsContent');
        if (!currentVehicleId || !window.vehicles) return;

        const vehicle = window.vehicles.find(v => v.id === currentVehicleId);
        if (!vehicle) return;

        container.innerHTML = `
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-motorcycle text-blue-600"></i>
                    ${vehicle.name}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ${vehicle.make || ''} ${vehicle.model || ''} ${vehicle.year ? `(${vehicle.year})` : ''}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">License Plate</div>
                    <div class="font-medium text-gray-800 dark:text-white text-lg">${vehicle.licensePlate || 'N/A'}</div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Chassis Number</div>
                    <div class="font-medium text-gray-800 dark:text-white text-lg font-mono">${vehicle.chassisNumber || 'N/A'}</div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Engine Capacity</div>
                    <div class="font-medium text-gray-800 dark:text-white text-lg">${vehicle.engineCC ? vehicle.engineCC + ' CC' : 'N/A'}</div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-100 dark:border-gray-600">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Color</div>
                    <div class="font-medium text-gray-800 dark:text-white text-lg flex items-center gap-2">
                        ${vehicle.color ? `<span class="w-4 h-4 rounded-full border border-gray-300" style="background-color: ${vehicle.color}"></span>` : ''}
                        ${vehicle.color || 'N/A'}
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 flex justify-between">
                <span>Added: ${moment(vehicle.createdAt).format('MMM D, YYYY')}</span>
                <span>ID: ${vehicle.id}</span>
            </div>
        `;
    }

    // Expose editVehicle globally
    function editVehicle(id) {
        const vehicle = window.vehicles.find(v => v.id === id);
        if (!vehicle) return;

        document.getElementById('vehicleId').value = vehicle.id;
        document.getElementById('vehicleName').value = vehicle.name;
        document.getElementById('vehicleLicensePlate').value = vehicle.licensePlate || '';
        document.getElementById('vehicleChassisNumber').value = vehicle.chassisNumber || '';
        document.getElementById('vehicleEngineCC').value = vehicle.engineCC || '';
        document.getElementById('vehicleColor').value = vehicle.color || '';
        document.getElementById('vehicleDisplayOrder').value = vehicle.displayOrder || 0;

        document.getElementById('vehicleModalTitle').textContent = 'Edit Vehicle';
        vehicleModal.classList.remove('hidden');
    }

    // Make editVehicle globally accessible for onclick handlers
    window.editVehicle = editVehicle;

    function loadAnalytics() {
        // Simple Chart.js implementation
        const ctxCost = document.getElementById('costChart').getContext('2d');
        const ctxConsumption = document.getElementById('consumptionChart').getContext('2d');

        // Destroy existing charts if any (store in global var if needed, for now just simple create)
        if (window.costChartInstance) window.costChartInstance.destroy();
        if (window.consumptionChartInstance) window.consumptionChartInstance.destroy();

        // Prepare data (using current paginated fuelEntries for this specific chart)
        const labels = fuelEntries.slice(0, 10).reverse().map(e => moment(e.date).format('MMM D'));
        const costs = fuelEntries.slice(0, 10).reverse().map(e => e.totalCost);
        const consumption = fuelEntries.slice(0, 10).reverse().map(e => e.liters); // Simplified

        window.costChartInstance = new Chart(ctxCost, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cost',
                    data: costs,
                    backgroundColor: 'rgba(37, 99, 235, 0.5)',
                    borderColor: 'rgba(37, 99, 235, 1)',
                    borderWidth: 1
                }]
            }
        });

        window.consumptionChartInstance = new Chart(ctxConsumption, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Fuel (L)',
                    data: consumption,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    tension: 0.1
                }]
            }
        });
    }

    async function loadBikeAnalytics() {
        if (!currentVehicleId) return;
        try {
            // Fetch ALL data for analytics (limit=-1)
            const fuelRes = await fetch(`api/fuel/index.php?vehicleId=${currentVehicleId}&limit=-1`);
            const fuelData = await fuelRes.json();
            const maintenanceRes = await fetch(`api/maintenance-cost/index.php?vehicleId=${currentVehicleId}&limit=-1`);
            const maintenanceData = await maintenanceRes.json();

            // Use stats from API if available, or calculate from entries (which are now ALL entries)
            // Since we passed limit=-1, entries contains all entries.
            // But API also returns pre-calculated global stats in 'stats' object.

            const fuelStats = fuelData.stats;
            const maintenanceStats = maintenanceData.stats;

            // 1. Total Cost (Fuel + Maintenance)
            const totalFuelCost = fuelStats.totalCost;
            const totalMaintenance = maintenanceStats.totalCost;
            const totalCost = totalFuelCost + totalMaintenance;

            // 2. Fuel Efficiency
            const totalLiters = fuelStats.totalLiters;
            const totalDistance = fuelStats.totalDistance;

            let avgMileage = 0;
            if (totalLiters > 0) {
                avgMileage = totalDistance / totalLiters;
            }

            // 3. Cost per km
            let costPerKm = 0;
            if (totalDistance > 0) {
                costPerKm = totalCost / totalDistance;
            }

            // 4. Monthly Average
            // We need to calculate this from the entries (which are ALL entries now)
            const allFuelEntries = fuelData.entries;
            const months = new Set();
            const monthlyCosts = {};

            allFuelEntries.forEach(e => {
                const m = moment(e.date).format('YYYY-MM');
                months.add(m);
                if (!monthlyCosts[m]) monthlyCosts[m] = { fuel: 0, maintenance: 0 };
                monthlyCosts[m].fuel += parseFloat(e.totalCost);
            });

            const numMonths = months.size || 1;
            const monthlyFuelCost = totalFuelCost / numMonths;

            // Bike Age
            // We need vehicle creation date or first entry date.
            // Let's use first fuel entry date if available.
            let bikeAge = 'N/A';
            if (allFuelEntries.length > 0) {
                const firstDate = moment(allFuelEntries[allFuelEntries.length - 1].date);
                const now = moment();
                const diffMonths = now.diff(firstDate, 'months');
                const years = Math.floor(diffMonths / 12);
                const remainingMonths = diffMonths % 12;
                bikeAge = `${years}y ${remainingMonths}m`;
            }

            // Update UI
            const currency = settings.currency || '৳';

            document.getElementById('analyticsTotalCost').textContent = `${currency} ${totalCost.toFixed(2)}`;

            document.getElementById('analyticsTotalFuel').textContent = `${currency} ${totalFuelCost.toFixed(2)}`;
            // Calculate average cost per liter
            const avgCostPerLiter = totalLiters > 0 ? totalFuelCost / totalLiters : 0;
            document.getElementById('analyticsFuelDetails').textContent = `${totalLiters.toFixed(1)} ${settings.volumeUnit} • ${currency}${avgCostPerLiter.toFixed(2)}/${settings.volumeUnit}`;

            document.getElementById('analyticsTotalDistance').textContent = `${totalDistance.toFixed(1)} ${settings.distanceUnit}`;

            document.getElementById('analyticsAvgMileage').textContent = `${avgMileage.toFixed(2)} ${settings.distanceUnit}/${settings.volumeUnit}`;
            document.getElementById('analyticsCostPerKm').textContent = `${currency} ${costPerKm.toFixed(2)} / ${settings.distanceUnit}`;

            document.getElementById('analyticsMonthlyFuelCost').textContent = `${currency} ${monthlyFuelCost.toFixed(2)}`;

            document.getElementById('analyticsTotalMaintenance').textContent = `${currency} ${totalMaintenance.toFixed(2)}`;

            document.getElementById('analyticsBikeAge').textContent = bikeAge;

            // Charts (Monthly Bike Expenses)
            // Combine fuel and maintenance monthly costs
            const allMaintenanceEntries = maintenanceData.entries;
            allMaintenanceEntries.forEach(e => {
                const m = moment(e.date).format('YYYY-MM');
                months.add(m);
                if (!monthlyCosts[m]) monthlyCosts[m] = { fuel: 0, maintenance: 0 };
                monthlyCosts[m].maintenance += parseFloat(e.cost);
            });

            // Sort months
            const sortedMonths = Array.from(months).sort();
            const last12Months = sortedMonths.slice(-12); // Show last 12 months

            const chartLabels = last12Months.map(m => moment(m, 'YYYY-MM').format('MMM YYYY'));
            const fuelCosts = last12Months.map(m => monthlyCosts[m]?.fuel || 0);
            const maintenanceCosts = last12Months.map(m => monthlyCosts[m]?.maintenance || 0);

            const ctx = document.getElementById('bikeExpensesChart').getContext('2d');
            if (window.bikeExpensesChartInstance) window.bikeExpensesChartInstance.destroy();
            window.bikeExpensesChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [
                        { label: 'Fuel', data: fuelCosts, backgroundColor: 'rgba(239, 68, 68, 0.7)' },
                        { label: 'Maintenance', data: maintenanceCosts, backgroundColor: 'rgba(249, 115, 22, 0.7)' }
                    ]
                },
                options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
            });

        } catch (e) {
            console.error('Error loading bike analytics', e);
        }
    }

    // Expense Functions
    async function fetchCategories() {
        try {
            const res = await fetch('api/expenses/categories.php');
            const data = await res.json();
            categories = data.categories;

            // Populate category select in modal
            const select = document.getElementById('expenseCategory');
            select.innerHTML = '<option value="">Select category...</option>';
            categories.forEach(cat => {
                if (cat.id !== 'Fuel' && cat.id !== 'Maintenance') {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                }
            });

            // Populate category filters
            const filtersDiv = document.getElementById('categoryFilters');
            filtersDiv.innerHTML = '';
            // Add 'All' filter
            const allBtn = document.createElement('button');
            allBtn.className = 'category-filter-btn px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full text-xs whitespace-nowrap active';
            allBtn.dataset.category = 'all';
            allBtn.textContent = 'All';
            allBtn.addEventListener('click', () => {
                selectedCategory = 'all';
                document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active'));
                allBtn.classList.add('active');
                fetchExpenses(); // Re-fetch expenses with new filter
            });
            filtersDiv.appendChild(allBtn);

            categories.forEach(cat => {
                const btn = document.createElement('button');
                btn.className = 'category-filter-btn px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full text-xs whitespace-nowrap';
                btn.dataset.category = cat.id;
                btn.textContent = cat.name;
                btn.addEventListener('click', () => {
                    selectedCategory = cat.id;
                    document.querySelectorAll('.category-filter-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    fetchExpenses(); // Re-fetch expenses with new filter
                });
                filtersDiv.appendChild(btn);
            });
        } catch (e) {
            console.error('Error fetching categories', e);
        }
    }

    async function fetchExpenses() {
        const month = currentMonth.toISOString().slice(0, 7); // YYYY-MM
        const category = selectedCategory === 'all' ? '' : selectedCategory; // Use selectedCategory from state

        document.getElementById('expensesLoading').classList.remove('hidden');
        document.getElementById('expensesList').innerHTML = '';
        document.getElementById('noExpenses').classList.add('hidden');

        try {
            const { page, limit } = paginationState.expenses;
            let url = `api/expenses/index.php?month=${month}&page=${page}&limit=${limit}`;
            if (category) url += `&category=${category}`;

            const res = await fetch(url);
            const data = await res.json();

            expenses = data.expenses || []; // Update global expenses state
            const stats = data.stats || {};
            const pagination = data.pagination || {};

            renderExpenses(expenses); // Pass the fetched expenses to render
            renderPagination(pagination.total, pagination.page, pagination.limit, 'expensesPagination', (newPage) => {
                paginationState.expenses.page = newPage;
                fetchExpenses();
            });

            // Update stats UI
            const currency = settings.currency || '৳';
            document.getElementById('totalExpense').textContent = `${currency} ${stats.totalAmount.toFixed(2)}`;
            document.getElementById('totalEntries').textContent = stats.totalEntries;

            // Show top category name instead of count
            const topCategoryNames = Object.keys(stats.topCategories);
            const topCategoryText = topCategoryNames.length > 0 ? topCategoryNames[0] : 'N/A';
            document.getElementById('totalCategories').textContent = topCategoryText;

            // Top Categories Chart
            renderExpenseChart(stats.topCategories);

        } catch (e) {
            console.error('Error fetching expenses', e);
        } finally {
            document.getElementById('expensesLoading').classList.add('hidden');
        }
    }

    function updateMonthDisplay() {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        const display = `${monthNames[currentMonth.getMonth()]} ${currentMonth.getFullYear()}`;
        document.getElementById('currentMonth').textContent = display;
    }

    function updateSummary(stats) {
        document.getElementById('totalExpense').textContent = `${settings.currency || '৳'} ${stats.totalAmount.toFixed(2)}`;
        document.getElementById('totalEntries').textContent = stats.totalEntries;
        document.getElementById('totalCategories').textContent = Object.keys(stats.topCategories).length;
    }

    function renderExpenses() {
        const list = document.getElementById('expensesList');
        const noExpenses = document.getElementById('noExpenses');

        // Filter expenses
        let filtered = expenses;
        if (selectedCategory !== 'all') {
            filtered = expenses.filter(e => e.category === selectedCategory);
        }

        if (filtered.length === 0) {
            list.innerHTML = '';
            noExpenses.classList.remove('hidden');
            return;
        }

        noExpenses.classList.add('hidden');
        list.innerHTML = '';

        // Group by date
        const grouped = {};
        filtered.forEach(exp => {
            const date = moment(exp.date).format('YYYY-MM-DD');
            if (!grouped[date]) grouped[date] = [];
            grouped[date].push(exp);
        });

        // Render grouped
        Object.keys(grouped).sort().reverse().forEach(date => {
            const dateHeader = document.createElement('div');
            dateHeader.className = 'text-xs font-medium text-gray-500 dark:text-gray-400 mt-4 mb-2';
            dateHeader.textContent = moment(date).format('MMM D, YYYY');
            list.appendChild(dateHeader);

            grouped[date].forEach(exp => {
                const cat = categories.find(c => c.id === exp.category) || {};
                const card = document.createElement('div');
                card.className = 'bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-3';
                card.innerHTML = `
        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" style="background-color: ${cat.color || '#6b7280'}20">
        <i class="fas ${cat.icon || 'fa-circle'}" style="color: ${cat.color || '#6b7280'}"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium dark:text-white truncate">${exp.title}</div>
                        ${exp.description ? `<div class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">${exp.description}</div>` : ''}
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${exp.category} • ${exp.paymentMethod}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-blue-600">${settings.currency || '৳'} ${parseFloat(exp.amount).toFixed(2)}</div>
                        <div class="text-xs text-gray-400">${moment(exp.date).format('h:mm A')}</div>
                    </div>
                    <div class="flex gap-1">
                        <button onclick="editExpense('${exp.id}')" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 rounded" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </button>
                        <button onclick="deleteExpense('${exp.id}')" class="p-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded" title="Delete">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </div>
                `;
                list.appendChild(card);
            });
        });
    }

    async function handleExpenseSubmit(e) {
        e.preventDefault();
        const formData = new FormData(expenseForm);
        const data = Object.fromEntries(formData.entries());

        const method = data.id ? 'PUT' : 'POST';

        try {
            const res = await fetch('api/expenses/index.php', {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (res.ok) {
                expenseModal.classList.add('hidden');
                fetchExpenses();
                showToast('Expense saved successfully', 'success');
            } else {
                showToast('Failed to save expense', 'error');
            }
        } catch (e) {
            console.error(e);
        }
    }

    // Document Functions
    async function fetchDocuments() {
        if (!currentVehicleId) return;
        try {
            const res = await fetch(`api/vehicles/documents.php?vehicleId=${currentVehicleId}`);
            const data = await res.json();
            renderDocuments(data.documents);
        } catch (e) {
            console.error('Error fetching documents', e);
        }
    }

    async function handleDocumentUpload(e) {
        if (!currentVehicleId) return showToast('Please select a vehicle first', 'error');
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('vehicleId', currentVehicleId);

        try {
            const res = await fetch('api/vehicles/documents.php', {
                method: 'POST',
                body: formData
            });

            if (res.ok) {
                e.target.value = ''; // Reset input
                fetchDocuments();
            } else {
                const data = await res.json();
                showToast(data.error || 'Failed to upload document', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Failed to upload document', 'error');
        }
    }

    function renderDocuments(documents) {
        const list = document.getElementById('documentsList');
        const noDocuments = document.getElementById('noDocuments');

        if (!documents || documents.length === 0) {
            list.innerHTML = '';
            noDocuments.classList.remove('hidden');
            return;
        }

        noDocuments.classList.add('hidden');
        list.innerHTML = '';

        documents.forEach(doc => {
            const card = document.createElement('div');
            card.className = 'bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 relative group';

            const isPDF = doc.filetype === 'application/pdf';
            const isImage = doc.filetype.startsWith('image/');

            card.innerHTML = `
        <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-md mb-2 flex items-center justify-center overflow-hidden">
        ${isImage ? `<img src="${doc.url}" alt="${doc.filename}" class="w-full h-full object-cover">` :
                    `<i class="fas fa-file-pdf text-4xl text-red-500"></i>`}
                </div>
                <div class="text-xs font-medium dark:text-white truncate">${doc.filename}</div>
                <div class="text-xs text-gray-400">${(doc.size / 1024).toFixed(1)} KB</div>
                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                    <a href="${doc.url}" target="_blank" class="bg-blue-600 text-white p-1 rounded text-xs hover:bg-blue-700">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button onclick="deleteDocument('${doc.id}')" class="bg-red-600 text-white p-1 rounded text-xs hover:bg-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
    `;
            list.appendChild(card);
        });
    }

    window.deleteDocument = async function (id) {
        const confirmed = await showConfirm('Delete Document', 'Are you sure you want to delete this document?');
        if (!confirmed) return;

        try {
            const res = await fetch('api/vehicles/documents.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            if (res.ok) {
                fetchDocuments();
                showToast('Document deleted successfully', 'success');
            } else {
                showToast('Failed to delete document', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Failed to delete document', 'error');
        }
    };

    // Edit/Delete Handlers
    window.editFuelEntry = function (id) {
        const entry = fuelEntries.find(e => e.id === id);
        if (!entry) return;

        document.getElementById('fuelId').value = entry.id;
        document.getElementById('fuelDate').value = moment(entry.date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('fuelLocation').value = entry.location || '';
        document.getElementById('fuelOdometer').value = entry.odometer;
        document.getElementById('fuelLiters').value = entry.liters;
        document.getElementById('fuelCostPerLiter').value = entry.costPerLiter;
        document.getElementById('fuelTotalCost').value = entry.totalCost;
        document.getElementById('fuelPartial').checked = entry.fuelType === 'PARTIAL';
        document.getElementById('fuelNotes').value = entry.notes || '';

        // Update button text and title
        document.getElementById('fuelModalTitle').textContent = 'Edit Fuel Entry';
        const btn = document.getElementById('fuelForm').querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Update';

        fuelModal.classList.remove('hidden');
    };

    window.deleteFuelEntry = async function (id) {
        const confirmed = await showConfirm('Delete Fuel Entry', 'Are you sure you want to delete this fuel entry?');
        if (!confirmed) return;

        try {
            const res = await fetch(`api/fuel/index.php?id=${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchFuelData();
                loadAnalytics();
                loadBikeAnalytics();
                showToast('Fuel entry deleted successfully', 'success');
            } else {
                showToast('Failed to delete fuel entry', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Failed to delete fuel entry', 'error');
        }
    };

    window.editMaintenanceEntry = function (id) {
        const entry = maintenanceEntries.find(e => e.id === id);
        if (!entry) return;

        document.getElementById('maintenanceId').value = entry.id;
        document.getElementById('maintenanceDate').value = moment(entry.date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('maintenanceDescription').value = entry.description;
        document.getElementById('maintenanceCategory').value = entry.category || '';
        document.getElementById('maintenanceCost').value = entry.cost;
        document.getElementById('maintenanceOdometer').value = entry.odometer || '';
        document.getElementById('maintenanceNotes').value = entry.notes || '';
        maintenanceModal.classList.remove('hidden');
    };

    window.deleteMaintenanceEntry = async function (id) {
        const confirmed = await showConfirm('Delete Maintenance Entry', 'Are you sure you want to delete this maintenance entry?');
        if (!confirmed) return;

        try {
            const res = await fetch(`api/maintenance-cost/index.php?id=${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchMaintenanceData();
                loadBikeAnalytics();
                showToast('Maintenance entry deleted successfully', 'success');
            } else {
                showToast('Failed to delete maintenance entry', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Failed to delete maintenance entry', 'error');
        }
    };

    window.editExpense = function (id) {
        const expense = expenses.find(e => e.id === id);
        if (!expense) return;

        document.getElementById('expenseId').value = expense.id;
        document.getElementById('expenseDate').value = moment(expense.date).format('YYYY-MM-DDTHH:mm');
        document.getElementById('expenseCategory').value = expense.category;
        document.getElementById('expenseTitle').value = expense.title;
        document.getElementById('expenseAmount').value = expense.amount;
        document.getElementById('expensePaymentMethod').value = expense.paymentMethod;
        document.getElementById('expenseDescription').value = expense.description || '';
        expenseModal.classList.remove('hidden');
    };

    window.deleteExpense = async function (id) {
        const confirmed = await showConfirm('Delete Expense', 'Are you sure you want to delete this expense?');
        if (!confirmed) return;

        try {
            const res = await fetch(`api/expenses/index.php?id=${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchExpenses();
                showToast('Expense deleted successfully', 'success');
            } else {
                showToast('Failed to delete expense', 'error');
            }
        } catch (e) {
            console.error(e);
            showToast('Failed to delete expense', 'error');
        }
    };

    window.editVehicle = function (id) {
        const vehicle = Array.from(vehicleSelect.options).find(opt => opt.value === id);
        if (!vehicle) return;

        document.getElementById('vehicleId').value = id;
        document.getElementById('vehicleName').value = vehicle.textContent;
        document.getElementById('vehicleDisplayOrder').value = vehicle.dataset.displayOrder || 0;
        vehicleModal.classList.remove('hidden');
    };
});
