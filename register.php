<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Daily Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6 sm:p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Account</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Start tracking your mileage today</p>
            </div>

            <form id="registerForm" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Name</label>
                    <input type="text" id="name" name="name" required 
                        class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Email</label>
                    <input type="email" id="email" name="email" required 
                        class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Password</label>
                    <input type="password" id="password" name="password" required 
                        class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                </div>

                <div>
                    <label for="securityQuestion" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Security Question</label>
                    <select id="securityQuestion" name="securityQuestion" required 
                        class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors">
                        <option value="">Select a question...</option>
                        <option value="What was the name of your first pet?">What was the name of your first pet?</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        <option value="What city were you born in?">What city were you born in?</option>
                        <option value="What was your first car?">What was your first car?</option>
                        <option value="What is your favorite book?">What is your favorite book?</option>
                    </select>
                </div>

                <div>
                    <label for="securityAnswer" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Security Answer</label>
                    <input type="text" id="securityAnswer" name="securityAnswer" required 
                        class="mt-1 block w-full h-11 px-4 py-3 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm transition-colors"
                        placeholder="Your answer">
                </div>

                <div id="errorMessage" class="text-red-500 text-sm hidden"></div>

                <button type="submit" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    Sign Up
                </button>
            </form>

            <div class="mt-6 text-center text-sm">
                <span class="text-gray-500 dark:text-gray-400">Already have an account?</span>
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const securityQuestion = document.getElementById('securityQuestion').value;
            const securityAnswer = document.getElementById('securityAnswer').value;
            const errorMessage = document.getElementById('errorMessage');

            try {
                const response = await fetch('api/auth/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, email, password, securityQuestion, securityAnswer })
                });

                const data = await response.json();

                if (response.ok) {
                    // Auto login after register
                    const loginResponse = await fetch('api/auth/login.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });
                    
                    if (loginResponse.ok) {
                        window.location.href = 'dashboard.php';
                    } else {
                        window.location.href = 'login.php';
                    }
                } else {
                    errorMessage.textContent = data.error || 'Registration failed';
                    errorMessage.classList.remove('hidden');
                }
            } catch (error) {
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
