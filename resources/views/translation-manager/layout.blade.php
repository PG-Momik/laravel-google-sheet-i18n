<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50/50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translation Manager</title>

    <!-- Fonts: Inter var -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter var', 'sans-serif'],
                    },
                    colors: {
                        gray: {
                            850: '#1f2937',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-feature-settings: 'cv11', 'cv01';
        }

        /* Premium Input Reset */
        .input-premium {
            appearance: none;
            background-color: #fff;
            border-color: #d1d5db;
            border-width: 1px;
            border-radius: 0.375rem;
            padding-top: 0.5rem;
            padding-right: 0.75rem;
            padding-bottom: 0.5rem;
            padding-left: 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            --tw-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
            transition: all 0.15s ease-in-out;
            width: 100%;
        }

        .input-premium:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
            --tw-ring-opacity: 1;
            --tw-ring-color: rgb(99 102 241 / var(--tw-ring-opacity));
            /* Indigo 500 */
            border-color: #6366f1;
        }

        .btn-primary {
            background-color: #4f46e5;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        .btn-primary:hover {
            background-color: #4338ca;
        }

        .btn-primary:focus {
            outline: none;
            box-shadow: 0 0 0 2px #fff, 0 0 0 4px #4f46e5;
        }
    </style>
</head>

<body class="h-full flex flex-col text-gray-900">
    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left -->
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <div class="bg-indigo-600 rounded-lg p-1.5 shadow-sm">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-900 text-sm tracking-tight">Translation Manager</span>
                    </div>

                    <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                        <a href="{{ route('translation-manager.dashboard') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors {{ request()->routeIs('translation-manager.dashboard') ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('translation-manager.translations.index') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors {{ request()->routeIs('translation-manager.translations.*') ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }}">
                            Translations
                        </a>
                        <a href="{{ route('translation-manager.sheets.index') }}"
                            class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors {{ request()->routeIs('translation-manager.sheets.*') ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900 hover:border-gray-300' }}">
                            Generate </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 py-10 bg-gray-50/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>

    <!-- Simple Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-center text-xs text-gray-400">
            <p>&copy; 2026 Momik Shrestha. Built with purpose.</p>
        </div>
    </footer>

    <!-- Notification -->
    <div x-data="{ show: false, message: '' }"
        @notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)" x-show="show"
        x-cloak x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed bottom-4 right-4 z-50 bg-gray-900 text-white shadow-lg rounded-md px-4 py-3 text-sm font-medium flex items-center gap-3">
        <svg class="h-4 w-4 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span x-text="message"></span>
    </div>
</body>

</html>