@extends('translation-manager::layout')

@section('content')
    <div class="space-y-8" x-data="sheetsManager()">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between border-b border-gray-200 pb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900 tracking-tight">Locale Generator</h2>
                <p class="mt-1 text-sm text-gray-500">Manage data flow between your application and Google Sheets.</p>
            </div>

            @if(!$error)
                <div
                    class="flex items-center gap-2 bg-green-50 text-green-700 px-3 py-1.5 rounded-full text-xs font-semibold border border-green-200">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    Connection Active
                </div>
            @else
                <div
                    class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1.5 rounded-full text-xs font-semibold border border-red-200">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    Connection Failed
                </div>
            @endif
        </div>

        @if($error)
            <div class="rounded-md bg-red-50 p-4 border border-red-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Connection Error</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $error }}</p>
                            <p class="mt-2 text-xs">Troubleshoot: Check your .env credentials or run `php artisan list` to
                                ensure command availability.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

            <!-- Interactive Form -->
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Parameters</h3>
                </div>

                <form @submit.prevent="syncTranslations()" class="p-6 space-y-6">
                    <!-- Source -->
                    <div>
                        <label for="source" class="block text-sm font-medium text-gray-700">Source Locale</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm font-mono">src:</span>
                            </div>
                            <input type="text" x-model="sourceLocale" id="source"
                                class="input-premium pl-12 font-mono text-sm" placeholder="en">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">The primary language to push updates from.</p>
                    </div>

                    <!-- Target -->
                    <div>
                        <label for="target" class="block text-sm font-medium text-gray-700">Target Locales <span
                                class="text-indigo-600">*</span></label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm font-mono">tgt:</span>
                            </div>
                            <input type="text" id="target" x-model="targetLocale" required
                                class="input-premium w-full pl-20" placeholder="e.g. es, fr, ja">
                        </div>
                        <p class="mt-2 text-xs text-slate-500 flex items-center gap-1">
                            <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Use ISO 639-1 codes (e.g., use 'ja' for Japan, not 'jp').
                        </p>
                    </div>

                    <!-- Tag -->
                    <div>
                        <label for="tag" class="block text-sm font-medium text-gray-700">Version Tag (Optional)</label>
                        <div class="mt-1">
                            <input type="text" x-model="tag" id="tag" class="input-premium" placeholder="v1.0-release">
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="pt-4 flex items-center justify-end border-t border-gray-100 mt-4">
                        <button type="submit" :disabled="syncing"
                            class="btn-primary flex items-center justify-center w-full sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!syncing" class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <svg x-show="syncing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="syncing ? 'Generating...' : 'Generate Locales'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Log -->
            <div class="flex flex-col h-full bg-gray-900 rounded-lg shadow-lg overflow-hidden border border-gray-800">
                <div class="px-4 py-3 bg-gray-800 border-b border-gray-700 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="flex gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-yellow-500"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-green-500"></div>
                        </div>
                        <span class="ml-3 text-xs font-mono text-gray-400">console.log</span>
                    </div>
                </div>

                <div class="flex-1 p-4 font-mono text-xs text-gray-300 overflow-y-auto space-y-1 relative">
                    <template x-if="!syncOutput">
                        <div class="absolute inset-0 flex items-center justify-center text-gray-700 select-none">
                            <span>Waiting for command...</span>
                        </div>
                    </template>
                    <div x-show="syncOutput" class="space-y-1">
                        <pre x-text="syncOutput" class="whitespace-pre-wrap"></pre>
                        <div x-show="syncing" class="animate-pulse text-indigo-400">Processing...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50/50">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Job History</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($sheets as $sheet)
                    <li class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-indigo-600 truncate mb-1">Sheet: {{ $sheet['name'] }}</p>
                                <p class="text-xs text-gray-500 flex items-center">
                                    <svg class="flex-shrink-0 mr-1.5 h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $sheet['created_at'] }}
                                </p>
                            </div>
                            <!-- Row count removed -->
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-12 text-center text-gray-500 text-sm">
                        No recent jobs found. Use the panel above to generate a locale.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
        function sheetsManager() {
            return {
                sourceLocale: 'en',
                targetLocale: '',
                tag: '',
                syncing: false,
                syncOutput: '',

                async syncTranslations() {
                    if (!this.targetLocale) {
                        document.getElementById('target').focus();
                        return;
                    }

                    this.syncing = true;
                    this.syncOutput = '> Starting generation process...\n';

                    // Split locales by comma and trim whitespace
                    const locales = this.targetLocale.split(',').map(l => l.trim()).filter(l => l);

                    if (locales.length === 0) {
                        this.syncOutput += '> Error: No valid locales specified.\n';
                        this.syncing = false;
                        return;
                    }

                    this.syncOutput += `> Found ${locales.length} target locale(s): ${locales.join(', ')}\n`;

                    // Process locales sequentially to avoid timeouts
                    for (const locale of locales) {
                        this.syncOutput += `> [${locale}] Generating locale...\n`;

                        try {
                            const response = await fetch('{{ route("translation-manager.sync.upload") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    source_locale: this.sourceLocale,
                                    target_locale: locale, // Send one locale at a time
                                    tag: this.tag
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                this.syncOutput += (data.output || `> [${locale}] Generation completed.`) + '\n';
                                this.syncOutput += `> [${locale}] Status: Success\n`;
                            } else {
                                this.syncOutput += `> [${locale}] Error: ` + (data.message || 'Unknown error') + '\n';
                            }
                        } catch (error) {
                            this.syncOutput += `> [${locale}] Fatal Error: ` + error.message + '\n';
                        }

                        this.syncOutput += '-----------------------------------\n';
                    }

                    this.syncOutput += '> All jobs finished.';
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: 'Locale Generation Completed'
                    }));

                    this.syncing = false;
                },
            }
        }
    </script>
@endsection