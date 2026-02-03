<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AutoPulse - Garage Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
</head>
<body class="antialiased bg-gray-50 text-gray-800">

    <div class="min-h-screen flex flex-col justify-center items-center">
        <!-- Hero Section -->
        <div class="text-center max-w-2xl px-6">
            <div class="bg-blue-600 text-white p-4 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-6 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
            </div>
            
            <h1 class="text-5xl font-bold tracking-tight text-gray-900 mb-4">AutoPulse</h1>
            <p class="text-xl text-gray-600 mb-8">Enterprise Multi-Tenant Garage Management System</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="/docs/api" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition duration-200">
                    API Documentation
                </a>
                <a href="https://codecanyon.net/item/autopulse" class="px-6 py-3 bg-white hover:bg-gray-100 text-gray-700 font-semibold rounded-lg shadow-md border border-gray-200 transition duration-200">
                    Purchase License
                </a>
            </div>
        </div>

        <!-- Status Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-16 max-w-4xl w-full px-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <h3 class="font-semibold text-gray-900">API Gateway</h3>
                </div>
                <p class="text-sm text-gray-500">v1.0.0 Online • 99.9% Uptime</p>
            </div>
            
             <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <h3 class="font-semibold text-gray-900">Multi-Tenancy</h3>
                </div>
                <p class="text-sm text-gray-500">Domain Isolation Active</p>
            </div>

             <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <h3 class="font-semibold text-gray-900">Documentation</h3>
                </div>
                <p class="text-sm text-gray-500">Latest Build Available</p>
            </div>
        </div>

        <div class="mt-16 text-center text-sm text-gray-400">
            &copy; {{ date('Y') }} AutoPulse. All rights reserved. Laravel v{{ Illuminate\Foundation\Application::VERSION }} ({{ PHP_VERSION }})
        </div>
    </div>
</body>
</html>
