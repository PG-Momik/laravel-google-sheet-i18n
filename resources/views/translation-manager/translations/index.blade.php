@extends('translation-manager::layout')

@section('content')
    <div class="h-full" x-data="translationsManager()">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Translation Data</h1>
                <p class="mt-1 text-sm text-gray-500">Manage localized strings across your application.</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <div class="relative inline-block text-left w-full sm:w-auto">
                    <select x-model="selectedLocale" @change="changeLocale()"
                            class="input-premium pl-3 pr-10 py-2 bg-white shadow-sm font-medium text-gray-700 focus:ring-indigo-500 focus:border-indigo-500 text-sm rounded-lg border-gray-300">
                        @foreach($locales as $loc)
                            <option value="{{ $loc }}" {{ $currentLocale === $loc ? 'selected' : '' }}>
                                {{ strtoupper($loc) }} ({{ $loc }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Master-Detail Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start h-[calc(100vh-12rem)]">

            <!-- SIDEBAR: File Explorer -->
            <div class="lg:col-span-1 h-full flex flex-col bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                <!-- Sidebar Header & Search -->
                <div class="p-3 border-b border-gray-200 bg-gray-50/50">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Source Files</h3>
                        <span class="text-[10px] font-mono text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200" x-text="files.length"></span>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" x-model="fileSearch" 
                               class="block w-full pl-8 pr-3 py-1.5 border-gray-300 rounded-md leading-5 bg-white placeholder-gray-400 focus:outline-none focus:placeholder-gray-300 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-xs transition duration-150 ease-in-out" 
                               placeholder="Filter files...">
                    </div>
                </div>

                <!-- File List -->
                <nav class="flex-1 overflow-y-auto p-2 space-y-0.5" aria-label="File List">
                    <template x-for="file in filteredFiles" :key="file">
                        <button @click="selectedFile = file; loadTranslations()"
                                class="w-full group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-all duration-150 ease-in-out text-left truncate relative"
                                :class="selectedFile === file ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'">

                            <span class="absolute left-0 top-1/2 -mt-2 h-4 w-1 rounded-r-full bg-indigo-500 transition-opacity duration-200"
                                  :class="selectedFile === file ? 'opacity-100' : 'opacity-0'"></span>

                            <svg class="flex-shrink-0 mr-2.5 h-4 w-4 transition-colors duration-200" 
                                 :class="selectedFile === file ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'" 
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="truncate" x-text="file"></span>
                        </button>
                    </template>

                    <div x-show="filteredFiles.length === 0" x-cloak class="px-4 py-8 text-center">
                        <p class="text-xs text-gray-500">No files match.</p>
                    </div>
                </nav>
            </div>

            <!-- MAIN CONTENT: Translations -->
            <div class="lg:col-span-3 h-full flex flex-col space-y-4 min-h-0">

                <!-- Global Search -->
                <div class="bg-white p-2 rounded-lg shadow-sm border border-gray-200 flex-shrink-0">
                    <div class="relative rounded-md group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               x-model="searchQuery"
                               class="block w-full pl-10 pr-12 py-2.5 border-transparent rounded-md leading-5 bg-transparent placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-0 sm:text-sm transition-colors"
                               placeholder="Search keys or values in selected file...">
                        <div class="absolute inset-y-0 right-0 flex py-2 pr-2.5">
                            <kbd class="inline-flex items-center border border-gray-200 rounded px-2 text-xs font-sans font-medium text-gray-400 bg-gray-50">Cmd+K</kbd>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 flex flex-col flex-1 overflow-hidden min-h-0">
                    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50/50 flex justify-between items-center flex-shrink-0">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <span class="flex-shrink-0 font-mono text-[10px] font-bold text-gray-500 px-1.5 py-0.5 bg-gray-200 rounded uppercase tracking-wider">FILE</span>
                            <h3 class="text-sm font-bold text-gray-900 truncate" x-text="selectedFile"></h3>
                        </div>
                        <span class="flex-shrink-0 text-xs font-medium text-gray-500 bg-white border border-gray-200 px-2 py-1 rounded-md shadow-sm">
                            <span x-text="filteredTranslations.length" class="text-indigo-600 font-bold"></span> keys
                        </span>
                    </div>

                    <div class="overflow-auto flex-1 bg-white relative">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                            <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-1/4 border-b border-gray-200 bg-gray-50">
                                        Key
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-3/4 border-b border-gray-200 bg-gray-50">
                                        Translation <span class="ml-1 text-indigo-600 font-bold">{{ strtoupper($currentLocale) }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="translation in filteredTranslations" :key="translation.key">
                                    <tr class="hover:bg-indigo-50/40 transition-colors duration-100 group">
                                        <!-- Key -->
                                        <td class="px-6 py-4 align-top w-1/4 border-r border-dashed border-gray-100">
                                            <div class="flex flex-col gap-1 items-start">
                                                <span class="text-xs font-semibold text-gray-700 font-mono break-all select-all hover:text-indigo-600 cursor-pointer transition-colors" 
                                                      title="Click to copy"
                                                      @click="copyToClipboard(translation.key)"
                                                      x-text="translation.key"></span>
                                            </div>
                                        </td>

                                        <!-- Value -->
                                        <td class="px-6 py-4 align-top w-3/4">
                                            <div class="text-sm text-gray-600 break-words whitespace-pre-wrap leading-relaxed group-hover:text-gray-900 transition-colors" x-text="translation.value"></div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr x-show="filteredTranslations.length === 0" x-cloak>
                                    <td colspan="2" class="px-6 py-24 text-center">
                                        <div class="mx-auto h-12 w-12 text-gray-300 flex items-center justify-center rounded-full bg-gray-100 mb-4">
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                        </div>
                                        <h3 class="text-sm font-medium text-gray-900">No matches found</h3>
                                        <p class="mt-1 text-sm text-gray-500">No keys match "<span x-text="searchQuery"></span>" in this file.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function translationsManager() {
        return {
            selectedLocale: '{{ $currentLocale }}',
            selectedFile: '{{ $files[0] ?? "" }}',
            searchQuery: '{{ $search ?? "" }}',
            fileSearch: '',
            files: @json($files),
            allTranslations: @json($translations),
            filteredTranslations: [],

            get filteredFiles() {
                if (this.fileSearch === '') return this.files;
                return this.files.filter(f => f.toLowerCase().includes(this.fileSearch.toLowerCase()));
            },

            init() {
                // Initial load
                this.loadTranslations();

                // Watchers for real-time filtering
                this.$watch('searchQuery', () => this.filterContent());

                // Global Shortcuts
                document.addEventListener('keydown', (e) => {
                    // Cmd+K to focus search
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        const searchInput = this.$el.querySelector('input[placeholder*="Search keys"]');
                        if (searchInput) searchInput.focus();
                    }
                });
            },

            loadTranslations() {
                this.filteredTranslations = this.allTranslations.filter(t => t.file === this.selectedFile);
                this.filterContent();
            },

            // Client-side filtering
            filterContent() {
                const currentFileTranslations = this.allTranslations.filter(t => t.file === this.selectedFile);

                if (this.searchQuery) {
                    const query = this.searchQuery.toLowerCase();
                    this.filteredTranslations = currentFileTranslations.filter(t => 
                        t.key.toLowerCase().includes(query) || 
                        t.value.toLowerCase().includes(query)
                    );
                } else {
                    this.filteredTranslations = currentFileTranslations;
                }
            },

            changeLocale() {
                window.location.href = '{{ route("translation-manager.translations.index") }}?locale=' + this.selectedLocale;
            },

            copyToClipboard(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                    window.dispatchEvent(new CustomEvent('notify', { detail: 'Key copied to clipboard' }));
                }
            }
        }
    }
    </script>
@endsection