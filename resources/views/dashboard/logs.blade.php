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
                                class="filter-btn px-3 py-1 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-full text-sm active font-bold" 
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

                    <!-- Statystyki logów -->
                    <div id="log-stats" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Podsumowanie</h4>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Wszystkie wpisy:</span>
                                <span id="stats-total" class="font-bold text-indigo-700">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-gray-600">Rozmiar pliku:</span>
                                <span id="stats-size" class="font-bold text-indigo-700">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-gray-600">Ostatnia modyfikacja:</span>
                                <span id="stats-last-modified" class="font-bold text-indigo-700">-</span>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Poziomy logów</h4>
                            <div class="flex justify-between items-center">
                                <span class="text-blue-600">INFO:</span>
                                <span id="stats-info" class="font-bold text-blue-700">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-yellow-600">WARNING:</span>
                                <span id="stats-warning" class="font-bold text-yellow-700">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-red-600">ERROR:</span>
                                <span id="stats-error" class="font-bold text-red-700">-</span>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Inne poziomy</h4>
                            <div class="flex justify-between items-center">
                                <span class="text-purple-600">NOTICE:</span>
                                <span id="stats-notice" class="font-bold text-purple-700">-</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-green-600">DEBUG:</span>
                                <span id="stats-debug" class="font-bold text-green-700">-</span>
                            </div>
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
                // Pobierz statystyki logów
                await loadLogStats();
                
                // Przygotuj parametry URL
                const params = new URLSearchParams();
                if (activeFilter !== 'ALL') {
                    params.append('filter', activeFilter);
                }
                if (searchQuery) {
                    params.append('search', searchQuery);
                }
                
                const url = `${baseUrl}/api/logs${params.toString() ? '?' + params.toString() : ''}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Błąd HTTP: ${response.status}`);
                }
                
                const data = await response.text();
                const logsContent = document.getElementById('logs-content');
                
                if (data.trim() === '') {
                    logsContent.innerHTML = '<div class="text-gray-400 italic">Brak logów do wyświetlenia.</div>';
                } else {
                    logsContent.innerHTML = data;
                }
            } catch (error) {
                document.getElementById('logs-content').innerHTML = `<div class="text-red-500">Błąd podczas ładowania logów: ${error.message}</div>`;
                console.error('Błąd ładowania logów:', error);
            }
        }
        
        // Załaduj statystyki logów
        async function loadLogStats() {
            try {
                const response = await fetch(`${baseUrl}/api/logs/stats`);
                
                if (!response.ok) {
                    throw new Error(`Błąd HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    const stats = data.stats;
                    
                    // Aktualizuj statystyki na stronie
                    document.getElementById('stats-total').textContent = stats.total;
                    document.getElementById('stats-info').textContent = stats.info;
                    document.getElementById('stats-error').textContent = stats.error;
                    document.getElementById('stats-warning').textContent = stats.warning;
                    document.getElementById('stats-notice').textContent = stats.notice;
                    document.getElementById('stats-debug').textContent = stats.debug;
                    
                    // Formatuj rozmiar pliku
                    const size = stats.size;
                    let formattedSize = '';
                    
                    if (size < 1024) {
                        formattedSize = `${size} B`;
                    } else if (size < 1024 * 1024) {
                        formattedSize = `${(size / 1024).toFixed(2)} KB`;
                    } else {
                        formattedSize = `${(size / (1024 * 1024)).toFixed(2)} MB`;
                    }
                    
                    document.getElementById('stats-size').textContent = formattedSize;
                    document.getElementById('stats-last-modified').textContent = stats.last_modified;
                }
            } catch (error) {
                console.error('Błąd ładowania statystyk logów:', error);
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