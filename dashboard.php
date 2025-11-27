<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Daily Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-btn.active { color: #2563eb; border-bottom: 2px solid #2563eb; }
    </style>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto p-2 sm:p-4 space-y-2 sm:space-y-4 max-w-[960px]">
        <!-- Header -->
        <div class="flex items-center justify-between gap-2 sm:gap-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
            <div class="flex items-center flex-shrink-0">
                <div class="relative w-10 h-10 sm:w-10 sm:h-10 md:w-12 md:h-12">
                    <img src="static/logo.svg" alt="Logo" class="w-full h-full object-contain">
                </div>
            </div>
            
            <div class="text-center space-y-0.5 sm:space-y-1 flex-1 min-w-0">
                <h1 class="text-sm sm:text-sm md:text-base font-bold truncate dark:text-white">Daily Log</h1>
                <p class="text-xs sm:text-xs text-gray-500 dark:text-gray-400 truncate hidden sm:block">Track your fuel consumption and expenses</p>
            </div>
            
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                <div class="flex items-center gap-3 mb-1">
                    <button id="themeToggle" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:block"></i>
                    </button>
                    <div class="text-right min-w-0">
                        <div class="text-xs sm:text-xs font-medium truncate max-w-[80px] sm:max-w-[120px] md:max-w-[150px] dark:text-white">
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?>
                        </div>
                    </div>
                </div>
                <button id="logoutBtn" class="flex items-center gap-1 h-8 sm:h-6 px-2 sm:px-2 min-w-0 text-sm text-gray-600 hover:text-red-600 dark:text-gray-300 dark:hover:text-red-400">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Vehicle Selector -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <select id="vehicleSelect" class="flex-1 block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select a vehicle...</option>
                </select>
                <div class="flex gap-1 sm:gap-2">
                    <button id="editVehicleBtn" class="hidden px-2 sm:px-3 py-2 bg-yellow-600 text-white rounded-md text-sm font-medium hover:bg-yellow-700 transition-colors" title="Edit Vehicle">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button id="deleteVehicleBtn" class="hidden px-2 sm:px-3 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 transition-colors" title="Delete Vehicle">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button id="addVehicleBtn" class="px-2 sm:px-3 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus"></i> <span class="hidden sm:inline">Add Vehicle</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-t-lg shadow-sm sticky top-0 z-20 transition-shadow duration-300" id="tabsContainer">
            <div class="flex border-b border-gray-200 dark:border-gray-700">
                <button class="tab-btn active flex-1 py-3 sm:py-4 px-1 text-center text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200" data-tab="history">
                    <div class="flex flex-col items-center gap-1">
                        <i class="fas fa-gas-pump text-lg sm:text-base"></i>
                        <span class="text-[10px] sm:text-sm leading-none">Fuel Log</span>
                    </div>
                </button>
                <button class="tab-btn flex-1 py-3 sm:py-4 px-1 text-center text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200" data-tab="analytics">
                    <div class="flex flex-col items-center gap-1">
                        <i class="fas fa-chart-bar text-lg sm:text-base"></i>
                        <span class="text-[10px] sm:text-sm leading-none">Analytics</span>
                    </div>
                </button>
                <button class="tab-btn flex-1 py-3 sm:py-4 px-1 text-center text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200" data-tab="daily-costs">
                    <div class="flex flex-col items-center gap-1">
                        <i class="fas fa-wallet text-lg sm:text-base"></i>
                        <span class="text-[10px] sm:text-sm leading-none">Daily Costs</span>
                    </div>
                </button>
                <button class="tab-btn flex-1 py-3 sm:py-4 px-1 text-center text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200" data-tab="vehicle">
                    <div class="flex flex-col items-center gap-1">
                        <i class="fas fa-car text-lg sm:text-base"></i>
                        <span class="text-[10px] sm:text-sm leading-none">Vehicle</span>
                    </div>
                </button>
                <button class="tab-btn flex-1 py-3 sm:py-4 px-1 text-center text-xs sm:text-sm font-medium text-gray-500 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-gray-300 transition-colors duration-200" data-tab="settings">
                    <div class="flex flex-col items-center gap-1">
                        <i class="fas fa-cog text-lg sm:text-base"></i>
                        <span class="text-[10px] sm:text-sm leading-none">Settings</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="bg-white dark:bg-gray-800 rounded-b-lg shadow-sm overflow-hidden">
            <div class="p-2">
                <!-- Fuel History Tab -->
                <div id="history" class="tab-content active space-y-4">
                    <div id="fuelLoading" class="text-center py-4 hidden">Loading...</div>
                    <div id="fuelList" class="space-y-4">
                        <!-- Fuel entries will be injected here -->
                    </div>
                    <div id="fuelPagination" class="mt-4 flex justify-center gap-2"></div>
                    <div id="noFuelEntries" class="text-center py-8 text-gray-500 hidden">
                        No fuel entries found. Add your first entry!
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div id="analytics" class="tab-content space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-medium mb-4 dark:text-white">Cost Overview</h3>
                            <canvas id="costChart"></canvas>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-medium mb-4 dark:text-white">Consumption</h3>
                            <canvas id="consumptionChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Bike Expenses Overview -->
                    <div class="mt-6">
                        <h3 class="text-lg font-medium mb-4 dark:text-white">Bike Expenses Overview</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <!-- 1. Total Cost -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Cost</div>
                                <div id="analyticsTotalCost" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">৳0</div>
                            </div>

                            <!-- 2. Total Fuel Cost -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Fuel Cost</div>
                                <div id="analyticsTotalFuel" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">৳0</div>
                                <div id="analyticsFuelDetails" class="text-xs text-gray-400 mt-1">0L @ ৳0/L</div>
                            </div>

                            <!-- 3. Total Maintenance Cost -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Maintenance Cost</div>
                                <div id="analyticsTotalMaintenance" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">৳0</div>
                            </div>

                            <!-- 4. Total Distance -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Distance</div>
                                <div id="analyticsTotalDistance" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">0 km</div>
                            </div>

                            <!-- 5. Average Mileage -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Average Mileage</div>
                                <div id="analyticsAvgMileage" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">0 km/L</div>
                            </div>

                            <!-- 6. Cost per km -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cost per km</div>
                                <div id="analyticsCostPerKm" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">৳0</div>
                            </div>

                            <!-- 7. Monthly Fuel Cost -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Monthly Fuel Cost</div>
                                <div id="analyticsMonthlyFuelCost" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">৳0</div>
                            </div>

                            <!-- 8. Bike Age -->
                            <div class="bg-white dark:bg-gray-700 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Bike Age</div>
                                <div id="analyticsBikeAge" class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white mt-1">0 Years</div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="text-md font-medium mb-4 dark:text-white">Monthly Bike Expenses</h4>
                            <canvas id="bikeExpensesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Daily Costs Tab -->
                <div id="daily-costs" class="tab-content space-y-4">
                    <!-- Month Selector -->
                    <div class="flex items-center justify-between mb-4">
                        <button id="prevMonth" class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h3 id="currentMonth" class="text-lg font-medium dark:text-white"></h3>
                        <button id="nextMonth" class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-2 sm:p-4 rounded-lg text-white">
                            <div class="text-[10px] sm:text-xs opacity-90">Total</div>
                            <div id="totalExpense" class="text-sm sm:text-xl font-bold">৳0</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-500 to-green-600 p-2 sm:p-4 rounded-lg text-white">
                            <div class="text-[10px] sm:text-xs opacity-90">Entries</div>
                            <div id="totalEntries" class="text-sm sm:text-xl font-bold">0</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-2 sm:p-4 rounded-lg text-white">
                            <div class="text-[10px] sm:text-xs opacity-90">Top Category</div>
                            <div id="totalCategories" class="text-xs sm:text-lg font-bold truncate">N/A</div>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex gap-2 overflow-x-auto pb-2">
                        <button class="category-filter-btn active px-3 py-1 bg-gray-200 dark:bg-gray-700 rounded-full text-xs whitespace-nowrap" data-category="all">
                            All
                        </button>
                        <div id="categoryFilters"></div>
                    </div>

                    <!-- Expense List -->
                    <div id="expensesLoading" class="text-center py-4 hidden">Loading...</div>
                    <div id="expensesList" class="space-y-3">
                        <!-- Expenses will be injected here -->
                    </div>
                    <div id="expensesPagination" class="mt-4 flex justify-center gap-2"></div>
                    <div id="noExpenses" class="text-center py-8 text-gray-500 hidden">
                        No expenses found for this month.
                    </div>
                </div>

                <!-- Vehicle Tab -->

                <div id="vehicle" class="tab-content space-y-4">
                    <!-- Sub-tabs Navigation -->
                    <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg mb-4">
                        <button class="vehicle-sub-tab active flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-600 focus:outline-none transition-all shadow-sm" data-subtab="vehicle-maintenance">
                            Maintenance
                        </button>
                        <button class="vehicle-sub-tab flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-600/50 focus:outline-none transition-all" data-subtab="vehicle-documents">
                            Documents
                        </button>
                        <button class="vehicle-sub-tab flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-600/50 focus:outline-none transition-all" data-subtab="vehicle-details">
                            Details
                        </button>
                    </div>

                    <!-- Maintenance Section -->
                    <div id="vehicle-maintenance" class="vehicle-sub-content active space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium dark:text-white">Maintenance Costs</h3>
                            <button id="addMaintenanceBtn" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">Add Cost</button>
                        </div>
                        <div id="maintenanceLoading" class="text-center py-4 hidden">Loading...</div>
                        <div id="maintenanceList" class="space-y-4">
                            <!-- Maintenance entries will be injected here -->
                        </div>
                        <div id="maintenancePagination" class="mt-4 flex justify-center gap-2"></div>
                        <div id="noMaintenanceEntries" class="text-center py-8 text-gray-500 hidden">
                            No maintenance entries found.
                        </div>
                    </div>
                    
                    <!-- Vehicle Documents Section -->
                    <div id="vehicle-documents" class="vehicle-sub-content hidden space-y-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium dark:text-white">Vehicle Documents</h3>
                            <label for="documentUpload" class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 cursor-pointer">
                                <i class="fas fa-upload mr-1"></i> Upload
                            </label>
                            <input type="file" id="documentUpload" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                        </div>
                        <div id="documentsList" class="grid grid-cols-4 md:grid-cols-10 gap-3">
                            <!-- Documents will be injected here -->
                        </div>
                        <div id="noDocuments" class="text-center py-8 text-gray-500 hidden">
                            No documents uploaded yet.
                        </div>
                    </div>
                    <!-- Vehicle Details Section -->
                    <div id="vehicle-details" class="vehicle-sub-content hidden space-y-4">
                        <div id="vehicleDetailsContent" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                            <!-- Details will be injected here -->
                        </div>
                    </div>
                </div>

                <!-- Settings Tab -->
                <div id="settings" class="tab-content space-y-4">
                    <!-- Settings Sub-tabs Navigation -->
                    <div class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg mb-4">
                        <button class="settings-sub-tab active flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-600 focus:outline-none transition-all shadow-sm" data-subtab="settings-general">
                            <i class="fas fa-cog mr-1"></i> General
                        </button>
                        <button class="settings-sub-tab flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-600/50 focus:outline-none transition-all" data-subtab="settings-security">
                            <i class="fas fa-shield-alt mr-1"></i> Security
                        </button>
                        <button class="settings-sub-tab flex-1 py-1.5 px-3 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/50 dark:hover:bg-gray-600/50 focus:outline-none transition-all" data-subtab="settings-about">
                            <i class="fas fa-info-circle mr-1"></i> About
                        </button>
                    </div>

                    <!-- General Settings -->
                    <div id="settings-general" class="settings-sub-content active">
                        <form id="settingsForm" class="space-y-4 max-w-md mx-auto">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Currency</label>
                                <input type="text" name="currency" id="settingCurrency" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Distance Unit</label>
                                <select name="distanceUnit" id="settingDistanceUnit" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                    <option value="km">Kilometers (km)</option>
                                    <option value="mi">Miles (mi)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Volume Unit</label>
                                <select name="volumeUnit" id="settingVolumeUnit" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                    <option value="L">Liters (L)</option>
                                    <option value="gal">Gallons (gal)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Entries Per Page</label>
                                <input type="number" name="entriesPerPage" id="settingEntriesPerPage" min="1" max="100" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                            </div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save Settings
                            </button>
                        </form>
                    </div>

                    <!-- Security Settings -->
                    <div id="settings-security" class="settings-sub-content hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-lock text-xl text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Update your password securely</p>
                                </div>
                            </div>

                            <form id="changePasswordForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Security Question</label>
                                    <div id="securityQuestionDisplay" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm flex items-center">
                                        Loading...
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Security Answer</label>
                                    <input type="text" name="securityAnswer" id="securityAnswer" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Your answer">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Current Password</label>
                                    <input type="password" name="currentPassword" id="currentPassword" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">New Password</label>
                                    <input type="password" name="newPassword" id="newPassword" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirmPassword" id="confirmPassword" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                </div>

                                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </button>
                            </form>
                        </div>

                        <!-- Manage Security Question -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-100 dark:border-gray-700 mt-6">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-question-circle text-xl text-purple-600 dark:text-purple-400"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Manage Security Question</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Update your security question</p>
                                </div>
                            </div>

                            <form id="updateSecurityQuestionForm" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Current Security Question</label>
                                    <div id="currentSecurityQuestion" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm flex items-center">
                                        Loading...
                                    </div>
                                </div>

                                <div>
                                    <label for="newSecurityQuestion" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">New Security Question</label>
                                    <select id="newSecurityQuestion" name="newSecurityQuestion" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                        <option value="">Select a question...</option>
                                        <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                                        <option value="What city were you born in?">What city were you born in?</option>
                                        <option value="What was your first car?">What was your first car?</option>
                                        <option value="What is your favorite book?">What is your favorite book?</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="newSecurityAnswer" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">New Security Answer</label>
                                    <input type="text" name="newSecurityAnswer" id="newSecurityAnswer" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Your answer">
                                </div>

                                <div>
                                    <label for="verifyPassword" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Current Password (for verification)</label>
                                    <input type="password" name="verifyPassword" id="verifyPassword" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                                </div>

                                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-colors">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Security Question
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- About Settings -->
                    <div id="settings-about" class="settings-sub-content hidden space-y-6">
                        <!-- English Instructions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <i class="far fa-question-circle"></i> How to Use This App
                            </h3>
                            
                            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">1. Manage Your Vehicles</h4>
                                    <p>Use the vehicle selector at the top to switch between your vehicles or click "Manage" to add, edit, or reorder them. All data you enter is tied to the currently selected vehicle.</p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">2. Log Fuel & Maintenance</h4>
                                    <p>Use the <span class="inline-flex items-center justify-center w-5 h-5 bg-green-600 rounded-full text-white text-[10px]"><i class="fas fa-gas-pump"></i></span> and <span class="inline-flex items-center justify-center w-5 h-5 bg-purple-600 rounded-full text-white text-[10px]"><i class="fas fa-wallet"></i></span> buttons at the bottom right to quickly add fuel and maintenance entries.</p>
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">3. Analyze Your Data</h4>
                                    <p>The "Analytics" tab provides a comprehensive overview of your fuel consumption, costs, and efficiency. Explore the sub-tabs for monthly breakdowns and detailed analysis.</p>
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">4. Vehicle-Specific Information</h4>
                                    <p>The "Vehicle" tab contains maintenance logs, service reminders, and this informational guide.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Bengali Instructions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <i class="far fa-question-circle"></i> কিভাবে অ্যাপ ব্যবহার করবেন
                            </h3>
                            
                            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300 font-bengali">
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">১. আপনার যানবাহন পরিচালনা করুন</h4>
                                    <p>আপনার গাড়ি পরিবর্তন করতে উপরের ড্রপডাউন ক্লিক করুন অথবা নতুন যানবাহন যোগ, সম্পাদনা বা ক্রম পরিবর্তন করতে "Manage" এ ক্লিক করুন। আপনার প্রবেশ করা সমস্ত ডেটা নির্বাচিত যানবাহনের সাথে সংযুক্ত।</p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">২. জ্বালানী এবং রক্ষণাবেক্ষণ খরচ</h4>
                                    <p>জ্বালানী এবং রক্ষণাবেক্ষণ খরচের এন্ট্রিগুলি দ্রুত যোগ করতে নীচের ডানদিকে থাকা <span class="inline-flex items-center justify-center w-5 h-5 bg-green-600 rounded-full text-white text-[10px]"><i class="fas fa-gas-pump"></i></span> এবং <span class="inline-flex items-center justify-center w-5 h-5 bg-purple-600 rounded-full text-white text-[10px]"><i class="fas fa-wallet"></i></span> Buttons ব্যবহার করুন।</p>
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">৩. মাইলেজ গণনা কিভাবে কাজ করে</h4>
                                    <p>সঠিক মাইলেজ গণনার জন্য ২টি পদ্ধতি আছে:</p>
                                    <ul class="list-disc pl-5 mt-2 space-y-1 opacity-90">
                                        <li>Full Tank ফুয়েল এন্ট্রি করার সময় Odometer চেক করে এন্ট্রি করুন এবং পরের ফুয়েল এন্ট্রি রিজার্ভ এর সিগনাল দেয়ার পর এন্ট্রি করুন, রিজার্ভ সিগনাল এর আগে এন্ট্রি করলে পার্শিয়াল হিসেবে অ্যাড করুন।</li>
                                        <li>অথবা আপনার গাড়িতে যখন রিজার্ভ এর সিগনাল দিবে তখন ফুয়েল এন্ট্রি করবেন। এরপর পরবর্তী আবার রিজার্ভ সিগনাল দেয়ার পর ফুয়েল এন্ট্রি করবেন, এর আগে করলে পার্শিয়াল হিসেবে অ্যাড করবেন। অ্যাপটি দুটি ফুয়েল এন্ট্রির মধ্যে ভ্রমণ করা দূরত্ব এবং প্রথম ফুয়েল এন্ট্রির জ্বালানীর পরিমাণ (l/g) ব্যবহার করে মাইলেজ গণনা করে।</li>
                                    </ul>
                                    <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-700/50 rounded border border-gray-100 dark:border-gray-600">
                                        <p class="font-semibold">উদাহরণ:</p>
                                        <ul class="list-none space-y-0.5 text-xs">
                                            <li>- প্রথম ফুয়েল এন্ট্রি রিজার্ভ সিগনাল দেয়ার পর: ওডোমিটার ১০০০ কিমি।</li>
                                            <li>- দ্বিতীয় ফুয়েল এন্ট্রি রিজার্ভ সিগনাল দেয়ার পর: ওডোমিটার ১৫০০ কিমি, জ্বালানীর পরিমাণ ৩০ লিটার।</li>
                                            <li>- ভ্রমণ করা দূরত্ব: ১৫০০ - ১০০০ = ৫০০ কিমি।</li>
                                            <li>- মাইলেজ: ৫০০ কিমি / ৩০ লিটার = ১৬.৬৭ কিমি/লিটার।</li>
                                        </ul>
                                    </div>
                                    <p class="mt-2 text-xs text-red-500">দ্রষ্টব্যঃ প্রথম এন্ট্রি মাইলেজ গণনা করবে না এবং দ্বিতীয় এন্ট্রি তে মাইলেজ গণনা সামান্য ভুল হতে পারে</p>
                                </div>

                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 mb-1">৪. আপনার ডেটা বিশ্লেষণ করুন</h4>
                                    <p>"অ্যানালিটিক্স" ট্যাবটি আপনার জ্বালানী খরচ, ব্যয় এবং দক্ষতার একটি বিস্তৃত ওভারভিউ প্রদান করে। মাসিক ভাঙ্গন এবং বিস্তারিত বিশ্লেষণের জন্য সাব-ট্যাবগুলি অন্বেষণ করুন।</p>
                                </div>
                            </div>
                        </div>

                        <!-- Developer Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                <i class="far fa-user"></i> Developer Information
                            </h3>
                            
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 text-gray-400"><i class="far fa-user"></i></div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Developer</div>
                                        <div class="font-medium text-gray-900 dark:text-white">Rasel Ahmed</div>
                                    </div>
                                </div>
                                
                                <div class="flex items-start gap-3">
                                    <div class="mt-1 text-gray-400"><i class="fas fa-phone-alt"></i></div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Contact</div>
                                        <div class="font-medium text-gray-900 dark:text-white">+8801744779727</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <div class="mt-1 text-gray-400"><i class="far fa-envelope"></i></div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Email</div>
                                        <div class="font-medium text-gray-900 dark:text-white">bdmixbd@gmail.com</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Action Buttons -->
        <div class="fixed bottom-4 sm:bottom-6 right-4 sm:right-6 flex flex-col gap-3 sm:gap-3 z-50">
            <button id="addExpenseBtn" class="flex items-center justify-center h-14 w-14 rounded-full shadow-lg bg-purple-600 hover:bg-purple-700 text-white focus:outline-none">
                <i class="fas fa-wallet text-xl"></i>
            </button>
            <button id="addFuelBtn" class="flex items-center justify-center h-14 w-14 rounded-full shadow-lg bg-green-600 hover:bg-green-700 text-white focus:outline-none">
                <i class="fas fa-gas-pump text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Modals -->
    <!-- Add Fuel Modal -->
    <div id="fuelModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 id="fuelModalTitle" class="text-lg font-medium mb-4 dark:text-white">Add Fuel Entry</h3>
            <form id="fuelForm" class="space-y-4">
                <input type="hidden" name="id" id="fuelId">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Entry Date *</label>
                    <input type="datetime-local" name="date" id="fuelDate" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Current Odometer (km) *</label>
                    <input type="number" step="0.1" name="odometer" id="fuelOdometer" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="e.g. 2050.00">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Total Fuel Cost (t) *</label>
                        <input type="number" step="0.01" name="totalCost" id="fuelTotalCost" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="500.00">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Price Per L (t) *</label>
                        <input type="number" step="0.01" name="costPerLiter" id="fuelCostPerLiter" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="122.85">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="relative">
                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Total L *</label>
                        <div class="relative mt-1">
                            <input type="number" step="0.01" name="liters" id="fuelLiters" required class="block w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" placeholder="Auto">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-xs border border-gray-300 rounded px-1">Auto</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Location</label>
                        <input type="text" name="location" id="fuelLocation" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Fuel Station">
                    </div>
                </div>

                <div class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-md">
                    <input id="fuelPartial" name="isPartial" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="fuelPartial" class="ml-2 block text-sm font-medium text-gray-900 dark:text-white">
                        Partial
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Notes</label>
                    <textarea name="notes" id="fuelNotes" rows="2" class="mt-1 block w-full px-4 py-3 min-h-[80px] rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Additional notes..."></textarea>
                </div>

                <div class="flex justify-between items-center mt-6">
                    <button type="button" id="disableAutoCalcBtn" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-md dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                        <i class="fas fa-calculator mr-1"></i> Disable Auto-Calc
                    </button>
                    <div class="flex gap-2">
                        <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-black hover:bg-gray-800 rounded-md dark:bg-blue-600 dark:hover:bg-blue-700">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vehicle Modal -->
    <div id="vehicleModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 id="vehicleModalTitle" class="text-lg font-medium mb-4 dark:text-white">Manage Vehicle</h3>
            <form id="vehicleForm" class="space-y-4">
                <input type="hidden" name="id" id="vehicleId">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Name</label>
                    <input type="text" name="name" id="vehicleName" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">License Plate</label>
                    <input type="text" name="licensePlate" id="vehicleLicensePlate" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Chassis Number</label>
                    <input type="text" name="chassisNumber" id="vehicleChassisNumber" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Engine CC</label>
                    <input type="text" name="engineCC" id="vehicleEngineCC" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Color</label>
                    <input type="text" name="color" id="vehicleColor" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Display Order</label>
                    <input type="number" name="displayOrder" id="vehicleDisplayOrder" min="0" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="0 = first">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Lower numbers appear first (0 = first vehicle)</p>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Modal -->
    <div id="expenseModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-medium mb-4 dark:text-white">Add Expense</h3>
            <form id="expenseForm" class="space-y-4">
                <input type="hidden" name="id" id="expenseId">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Date</label>
                    <input type="datetime-local" name="date" id="expenseDate" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Category</label>
                    <select name="category" id="expenseCategory" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                        <option value="">Select category...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Title</label>
                    <input type="text" name="title" id="expenseTitle" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="e.g., Lunch with client">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Amount</label>
                    <input type="number" step="0.01" name="amount" id="expenseAmount" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Payment Method</label>
                    <select name="paymentMethod" id="expensePaymentMethod" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Mobile Bank">Mobile Bank</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Description (Optional)</label>
                    <textarea name="description" id="expenseDescription" rows="3" class="mt-1 block w-full px-4 py-3 min-h-[80px] rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Where, why, notes..."></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div id="maintenanceModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-medium mb-4 dark:text-white">Add Maintenance Cost</h3>
            <form id="maintenanceForm" class="space-y-4">
                <input type="hidden" name="id" id="maintenanceId">
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Date</label>
                    <input type="datetime-local" name="date" id="maintenanceDate" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Description</label>
                    <input type="text" name="description" id="maintenanceDescription" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="e.g., Oil change">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Category</label>
                    <select name="category" id="maintenanceCategory" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                        <option value="">Select category...</option>
                        <option value="Oil Change">Oil Change</option>
                        <option value="Tire">Tire</option>
                        <option value="Brake">Brake</option>
                        <option value="Battery">Battery</option>
                        <option value="Service">Service</option>
                        <option value="Repair">Repair</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Cost</label>
                    <input type="number" step="0.01" name="cost" id="maintenanceCost" required class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Odometer (Optional)</label>
                    <input type="number" step="0.1" name="odometer" id="maintenanceOdometer" class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Notes (Optional)</label>
                    <textarea name="notes" id="maintenanceNotes" rows="3" class="mt-1 block w-full px-4 py-3 min-h-[80px] rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors" placeholder="Additional details..."></textarea>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="close-modal px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-[60] flex flex-col gap-2"></div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center z-[70]">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
            <div class="flex items-start gap-4">
                <div id="confirmIcon" class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 id="confirmTitle" class="text-lg font-semibold text-gray-900 dark:text-white mb-2"></h3>
                    <p id="confirmMessage" class="text-sm text-gray-600 dark:text-gray-300"></p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button id="confirmCancel" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Cancel
                </button>
                <button id="confirmOk" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>

</body>
</html>
