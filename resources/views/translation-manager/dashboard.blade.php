@extends('translation-manager::layout')

@section('content')
    <div class="space-y-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between border-b border-gray-200 pb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900 tracking-tight">Overview</h2>
                <p class="mt-1 text-sm text-gray-500">System metrics and translation coverage status.</p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('translation-manager.sheets.index') }}"
                    class="btn-primary inline-flex items-center shadow-sm">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Generate Locales
                </a>
            </div>
        </div>

        <!-- Stats -->
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div
                class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow-sm rounded-lg overflow-hidden border border-gray-200">
                <dt>
                    <div class="absolute bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                    </div>
                    <p class="ml-16 text-sm font-medium text-gray-500 truncate">Total Locales</p>
                </dt>
                <dd class="ml-16 pb-1 flex items-baseline sm:pb-7">
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_locales'] }}</p>
                </dd>
            </div>

            <div
                class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow-sm rounded-lg overflow-hidden border border-gray-200">
                <dt>
                    <div class="absolute bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="ml-16 text-sm font-medium text-gray-500 truncate">Source Files</p>
                </dt>
                <dd class="ml-16 pb-1 flex items-baseline sm:pb-7">
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_files'] }}</p>
                </dd>
            </div>

            <div
                class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow-sm rounded-lg overflow-hidden border border-gray-200">
                <dt>
                    <div class="absolute bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                    </div>
                    <p class="ml-16 text-sm font-medium text-gray-500 truncate">Total Keys</p>
                </dt>
                <dd class="ml-16 pb-1 flex items-baseline sm:pb-7">
                    <p class="text-2xl font-semibold text-gray-900">{{ number_format($stats['total_strings']) }}</p>
                </dd>
            </div>

            <div
                class="relative bg-white pt-5 px-4 pb-12 sm:pt-6 sm:px-6 shadow-sm rounded-lg overflow-hidden border border-gray-200">
                <dt>
                    <div class="absolute bg-indigo-500 rounded-md p-3">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 21v-8a2 2 0 01-2-2h0a2 2 0 012-2v0m18 12v-8a2 2 0 00-2-2h0a2 2 0 00-2 2v0M3 13V9a2 2 0 012-2h0a2 2 0 012 2v4m14 0V9a2 2 0 00-2-2h0a2 2 0 00-2 2v4M6 13h12" />
                        </svg>
                    </div>
                    <p class="ml-16 text-sm font-medium text-gray-500 truncate">Base Locale</p>
                </dt>
                <dd class="ml-16 pb-1 flex items-baseline sm:pb-7">
                    <p class="text-2xl font-semibold text-gray-900 uppercase">{{ $stats['source_locale'] }}</p>
                </dd>
            </div>
        </dl>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Coverage -->
            <div class="lg:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-5 py-4 border-b border-gray-200 bg-gray-50/50 rounded-t-lg">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Translation Velocity</h3>
                </div>

                <ul class="divide-y divide-gray-200">
                    @foreach($stats['coverage'] as $locale => $coverage)
                        <li class="px-5 py-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 ring-1 ring-gray-200">
                                        <span class="text-xs font-bold text-gray-600 uppercase">{{ $locale }}</span>
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ $coverage['translated'] }} /
                                        {{ $coverage['total'] }}</span>
                                </div>
                                <span
                                    class="text-sm font-semibold {{ $coverage['percentage'] == 100 ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $coverage['percentage'] }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full {{ $coverage['percentage'] == 100 ? 'bg-green-500' : 'bg-amber-500' }}"
                                    style="width: {{ $coverage['percentage'] }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Config Side -->
            <div class="space-y-6">
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="px-5 py-4 border-b border-gray-200 bg-gray-50/50 rounded-t-lg">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Configuration</h3>
                    </div>
                    <div class="p-5">
                        @if(empty($stats['spreadsheet_id']))
                            <div class="flex gap-3 text-amber-700 bg-amber-50 p-3 rounded-md border border-amber-200">
                                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <div class="text-sm font-medium">Missing Sheet ID</div>
                            </div>
                        @else
                            <div
                                class="flex gap-3 text-green-700 bg-green-50 p-3 rounded-md border border-green-200 items-center">
                                <div class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></div>
                                <div class="text-sm font-medium">Connected</div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Sheet ID</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <code
                                        class="flex-1 bg-gray-50 border border-gray-300 rounded-md px-3 py-1 text-xs font-mono text-gray-600 truncate">
                                                    {{ $stats['spreadsheet_id'] }}
                                                </code>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection