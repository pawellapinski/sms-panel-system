<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Logi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Nagłówek i przyciski -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-indigo-800">Logi systemu</h3>
                        <div class="flex space-x-3">
                            <button 
                                id="refresh-btn" 
                                class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded-lg shadow-md flex items-center"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Odśwież
                            </button>
                            <button 
                                id="clear-logs-btn" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-md flex items-center"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v10M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                </svg>
                                Wyczyść logi
                            </button>
                        </div>
                    </div>

                    <!-- Opcje filtrowania -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="text-sm text-gray-700 mb-2 font-medium">Filtruj logi:</div>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                class="filter-btn px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full text-sm" 
                                data-filter="INFO"
                            >
                                INFO
                            </button>
                            <button 
                                class="filter-btn px-3 py-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-800 rounded-full text-sm" 
                                data-filter="WARNING"
                            >
                                WARNING
                            </button>
                            <button 
                                class="filter-btn px-3 py-1 bg-red-100 hover:bg-red-200 text-red-800 rounded-full text-sm" 
                                data-filter="ERROR"
                            >
                                ERROR
                            </button>
                            <button 
                                class="filter-btn px-3 py-1 bg-purple-100 hover:bg-purple-200 text-purple-800 rounded-full text-sm" 
                                data-filter="NOTICE"
                            >
                                NOTICE
                            </button>
                            <button 
                                class="filter-btn px-3 py-1 bg-green-100 hover:bg-green-200 text-green-800 rounded-full text-sm" 
                                data-filter="DEBUG"
                            >
                                DEBUG
                            </button>
                            <button 
                                class="filter-btn px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-full text-sm active" 
                                data-filter="ALL"
                            >
                                WSZYSTKIE
                            </button>
                        </div>
                        <div class="mt-3">
                            <input 
                                type="text" 
                                id="search-logs" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                placeholder="Wyszukaj w logach..."
                            >
                        </div>
                    </div>

                    <!-- Zawartość logów -->
                    <div id="logs-container" class="bg-gray-800 text-gray-200 p-4 rounded-lg shadow-inner overflow-x-auto">
                        <pre id="logs-content" class="text-sm font-mono whitespace-pre-wrap">Ładowanie logów...</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal potwierdzenia czyszczenia logów -->
    <div id="clear-logs-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Potwierdź czyszczenie logów</h3>
            <p class="text-gray-700 mb-6">Czy na pewno chcesz wyczyścić logi? Ta operacja jest nieodwracalna.</p>
            <div class="flex justify-end space-x-3">
                <button 
                    onclick="closeModal()" 
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded"
                >
                    Anuluj
                </button>
                <button 
                    onclick="clearLogs()" 
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded"
                >
                    Potwierdź
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Base URL
        const baseUrl = "{{ url('/') }}";
        
        // Aktywny filtr
        let activeFilter = 'ALL';
        let searchQuery = '';
        
        // Załaduj logi
        async function loadLogs() {
            try {
                const response = await fetch(`${baseUrl}/api/logs`);
                
                if (!response.ok) {
                    throw new Error(`Błąd HTTP: ${response.status}`);
                }
                
                const data = await response.text();
                const logsContent = document.getElementById('logs-content');
                
                if (data && data.trim() !== '') {
                    // Podziel logi na linie
                    const logLines = data.split('\n');
                    
                    // Zastosuj filtrowanie
                    let filteredLogs = logLines;
                    
                    // Filtrowanie po typie logów
                    if (activeFilter !== 'ALL') {
                        filteredLogs = logLines.filter(line => line.includes(activeFilter));
                    }
                    
                    // Filtrowanie po wyszukiwanej frazie
                    if (searchQuery && searchQuery.trim() !== '') {
                        const query = searchQuery.toLowerCase();
                        filteredLogs = filteredLogs.filter(line => line.toLowerCase().includes(query));
                    }
                    
                    // Kolorowanie logów
                    let coloredLogs = filteredLogs.map(line => {
                        if (line.includes('ERROR')) {
                            return `<span class="text-red-400">${line}</span>`;
                        } else if (line.includes('WARNING')) {
                            return `<span class="text-yellow-400">${line}</span>`;
                        } else if (line.includes('INFO')) {
                            return `<span class="text-blue-300">${line}</span>`;
                        } else if (line.includes('NOTICE')) {
                            return `<span class="text-purple-300">${line}</span>`;
                        } else if (line.includes('DEBUG')) {
                            return `<span class="text-green-300">${line}</span>`;
                        } else {
                            return line;
                        }
                    });
                    
                    logsContent.innerHTML = coloredLogs.join('\n');
                } else {
                    logsContent.textContent = 'Brak dostępnych logów.';
                }
            } catch (error) {
                document.getElementById('logs-content').textContent = `Błąd podczas ładowania logów: ${error.message}`;
                console.error('Błąd ładowania logów:', error);
            }
        }
        
        // Czyszczenie logów
        async function clearLogs() {
            try {
                const response = await fetch(`${baseUrl}/api/logs/clear`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Błąd HTTP: ${response.status}`);
                }
                
                closeModal();
                loadLogs();
                
                // Wyświetl komunikat sukcesu
                alert('Logi zostały wyczyszczone!');
                
            } catch (error) {
                alert(`Błąd podczas czyszczenia logów: ${error.message}`);
                console.error('Błąd czyszczenia logów:', error);
            }
        }
        
        // Otwórz modal
        function openModal() {
            document.getElementById('clear-logs-modal').classList.remove('hidden');
        }
        
        // Zamknij modal
        function closeModal() {
            document.getElementById('clear-logs-modal').classList.add('hidden');
        }
        
        // Aktualizacja aktywnego filtra
        function updateFilter(filter) {
            activeFilter = filter;
            
            // Aktualizuj wygląd przycisków
            document.querySelectorAll('.filter-btn').forEach(btn => {
                if (btn.dataset.filter === filter) {
                    btn.classList.add('active', 'font-bold');
                } else {
                    btn.classList.remove('active', 'font-bold');
                }
            });
            
            // Załaduj logi z nowym filtrem
            loadLogs();
        }
        
        // Inicjalizacja
        document.addEventListener('DOMContentLoaded', function() {
            // Załaduj logi
            loadLogs();
            
            // Obsługa przycisku odświeżania
            document.getElementById('refresh-btn').addEventListener('click', function() {
                loadLogs();
            });
            
            // Obsługa przycisku czyszczenia logów
            document.getElementById('clear-logs-btn').addEventListener('click', function() {
                openModal();
            });
            
            // Obsługa przycisków filtrowania
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    updateFilter(this.dataset.filter);
                });
            });
            
            // Obsługa wyszukiwania
            document.getElementById('search-logs').addEventListener('input', function(e) {
                searchQuery = e.target.value;
                loadLogs();
            });
            
            // Zamykanie modalu po kliknięciu poza nim
            document.getElementById('clear-logs-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Automatyczne odświeżanie co 30 sekund
            setInterval(() => {
                loadLogs();
            }, 30000);
        });
        
        // Obsługa klawisza Escape dla modalu
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
        
        // Dodaj globalne funkcje
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.clearLogs = clearLogs;
    </script>
    @endpush
</x-app-layout> 