<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista SMS') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Status serwera i IP -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="status p-3 bg-gray-100 rounded">
                            <p class="text-lg">Status serwera: <span id="server-status" class="font-semibold">Sprawdzanie...</span></p>
                        </div>
                        
                        <div class="status p-3 bg-gray-100 rounded">
                            <p class="text-lg">IP serwera: <span id="server-ip" class="font-semibold">Sprawdzanie...</span></p>
                        </div>
                    </div>
                    
                    <!-- Przyciski -->
                    <div class="flex mb-6">
                        <button id="refresh-btn" class="refresh-btn px-6 py-3 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-lg mr-3 border-2 border-indigo-900 shadow-lg">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Odśwież
                            </span>
                        </button>
                        <button 
                            class="logs-btn px-6 py-3 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded-lg border-2 border-gray-900 shadow-lg" 
                            onclick="window.location.href='{{ route('dashboard.logs') }}'"
                        >
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Zobacz logi
                            </span>
                        </button>
                    </div>
                    
                    <!-- Tabela SMS-ów -->
                    <div class="overflow-x-auto mt-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-indigo-800">Lista SMS-ów</h3>
                            <button id="clear-all-button" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition duration-300 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Wyczyść wszystko
                            </button>
                        </div>
                        <table class="min-w-full bg-white border border-gray-200 shadow-md rounded-lg overflow-hidden">
                            <thead class="bg-indigo-700 text-white">
                                <tr>
                                    <th class="py-3 px-4 text-left">ID</th>
                                    <th class="py-3 px-4 text-left">Od</th>
                                    <th class="py-3 px-4 text-left">Treść</th>
                                    <th class="py-3 px-4 text-left">Data</th>
                                    <th class="py-3 px-4 text-left">SIM</th>
                                    <th class="py-3 px-4 text-left">Surowe dane</th>
                                    <th class="py-3 px-4 text-left">Akcje</th>
                                </tr>
                            </thead>
                            <tbody id="sms-table-body">
                                <tr>
                                    <td colspan="7" class="py-4 px-4 text-center">Ładowanie danych...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginacja -->
                    <div class="mt-6 flex justify-between items-center">
                        <div class="text-gray-600" id="pagination-info">
                            Strona <span id="current-page">1</span> z <span id="total-pages">1</span>
                        </div>
                        <div class="flex space-x-2" id="pagination-controls">
                            <!-- Przyciski paginacji będą wstawione przez JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal do wyświetlania surowych danych -->
    <div id="raw-data-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="p-4 bg-indigo-700 text-white flex justify-between items-center">
                <h3 class="text-lg font-bold">Surowe dane SMS</h3>
                <button onclick="closeRawDataModal()" class="text-white hover:text-gray-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <pre id="raw-data-content" class="bg-gray-100 p-4 rounded text-sm overflow-x-auto whitespace-pre-wrap"></pre>
            </div>
            <div class="p-4 bg-gray-100 flex justify-end">
                <button onclick="closeRawDataModal()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded">
                    Zamknij
                </button>
            </div>
        </div>
    </div>

    <!-- Modal potwierdzenia usunięcia SMS-a -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Potwierdź usunięcie SMS-a</h3>
            
            <!-- Szczegóły SMS-a -->
            <div class="mb-4 p-3 bg-gray-100 rounded-lg">
                <p class="font-medium">Szczegóły usuwanego SMS-a:</p>
                <p><span class="font-medium">ID:</span> <span id="delete-sms-id"></span></p>
                <p><span class="font-medium">Od:</span> <span id="delete-sms-from"></span></p>
                <p><span class="font-medium">Treść:</span> <span id="delete-sms-message"></span></p>
            </div>
            
            <p class="text-gray-700 mb-3">Czy na pewno chcesz usunąć tego SMS-a?</p>
            <p class="text-gray-700 mb-2">Ta operacja:</p>
            <ul class="list-disc ml-6 mb-4 text-gray-700">
                <li>Jest nieodwracalna</li>
                <li>Zostanie zapisana w logach systemu</li>
                <li>Będzie zawierać Twoje dane jako osoby wykonującej operację</li>
            </ul>
            <div class="flex justify-end space-x-3">
                <button 
                    onclick="closeDeleteModal()" 
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded"
                >
                    Anuluj
                </button>
                <button 
                    id="confirm-delete-btn"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded"
                >
                    Usuń
                </button>
            </div>
        </div>
    </div>

    <!-- Modal potwierdzenia usunięcia wszystkich SMS-ów -->
    <div id="clear-all-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Potwierdź usunięcie</h3>
                <p class="text-gray-700 mb-6">Czy na pewno chcesz usunąć wszystkie SMS-y z bazy danych? Ta operacja jest nieodwracalna.</p>
                <div class="flex justify-center space-x-4">
                    <button id="cancel-clear-all" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition duration-300">
                        Anuluj
                    </button>
                    <button id="confirm-clear-all" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition duration-300">
                        Tak, usuń wszystko
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Powiadomienie -->
    <div id="notification" class="fixed bottom-4 right-4 p-4 rounded-lg shadow-lg transform transition-transform duration-300 translate-y-full opacity-0">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="notification-message"></span>
        </div>
    </div>

    @push('scripts')
    <script>
        // Base URL
        const baseUrl = "{{ url('/') }}";
        
        // Zmienne globalne dla paginacji
        let currentPage = 1;
        let totalPages = 1;
        let perPage = 15;
        
        // Zmienne globalne dla operacji usuwania
        let smsToDeleteId = null;
        let smsToDeleteDetails = null;
        
        // Funkcja do otwierania modalu z surowymi danymi
        function showRawData(rawPayload) {
            try {
                // Jeśli to string JSON, przekształć na obiekt i formatuj
                const jsonObj = typeof rawPayload === 'string' ? JSON.parse(rawPayload) : rawPayload;
                document.getElementById('raw-data-content').textContent = JSON.stringify(jsonObj, null, 2);
            } catch (e) {
                // Jeśli nie da się sparsować jako JSON, pokaż jako tekst
                document.getElementById('raw-data-content').textContent = rawPayload;
            }
            
            document.getElementById('raw-data-modal').classList.remove('hidden');
        }
        
        // Funkcja do zamykania modalu
        function closeRawDataModal() {
            document.getElementById('raw-data-modal').classList.add('hidden');
        }
        
        // Funkcja do otwierania modalu potwierdzenia usunięcia
        function showDeleteConfirmation(id, phoneNumber, message) {
            smsToDeleteId = id;
            
            // Ustaw szczegóły SMS-a w modalu
            document.getElementById('delete-sms-id').textContent = id;
            document.getElementById('delete-sms-from').textContent = phoneNumber;
            document.getElementById('delete-sms-message').textContent = message;
            
            document.getElementById('delete-confirm-modal').classList.remove('hidden');
        }
        
        // Funkcja do zamykania modalu potwierdzenia usunięcia
        function closeDeleteModal() {
            document.getElementById('delete-confirm-modal').classList.add('hidden');
            smsToDeleteId = null;
            smsToDeleteDetails = null;
        }
        
        // Funkcja do usuwania SMS-a
        async function deleteSMS(id) {
            try {
                const response = await fetch(`${baseUrl}/api/sms/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Błąd HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                // Zamknij modal
                closeDeleteModal();
                
                // Odśwież listę SMS-ów
                fetchSMSList(currentPage);
                
                // Wyświetl komunikat o sukcesie z informacją o logowaniu
                alert('SMS został pomyślnie usunięty. Operacja została zapisana w logach systemu.');
                
            } catch (error) {
                alert(`Błąd podczas usuwania SMS-a: ${error.message}`);
                console.error('Błąd usuwania SMS-a:', error);
            }
        }
        
        // Sprawdź status serwera
        async function checkServerStatus() {
            try {
                const response = await fetch(baseUrl + '/status');
                const data = await response.json();
                
                document.getElementById('server-status').textContent = data.status || 'Nieznany';
                document.getElementById('server-status').className = data.status === 'running' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold';
                
                const serverIp = data.server_ip || data.client_ip || 'Nieznany';
                document.getElementById('server-ip').textContent = serverIp + ':' + (location.port || '80');
                
            } catch (error) {
                document.getElementById('server-status').textContent = 'Błąd połączenia';
                document.getElementById('server-status').className = 'text-red-600 font-semibold';
                console.error('Błąd sprawdzania statusu:', error);
            }
        }
        
        // Pobierz listę SMS-ów
        async function fetchSMSList(page = 1) {
            try {
                const response = await fetch(`${baseUrl}/api/sms-list?limit=100`);
                const data = await response.json();
                
                if (data.messages && data.messages.length > 0) {
                    // Oblicz całkowitą liczbę stron
                    totalPages = Math.ceil(data.messages.length / perPage);
                    document.getElementById('total-pages').textContent = totalPages;
                    document.getElementById('current-page').textContent = page;
                    
                    // Paginacja danych
                    const startIndex = (page - 1) * perPage;
                    const endIndex = Math.min(startIndex + perPage, data.messages.length);
                    const paginatedMessages = data.messages.slice(startIndex, endIndex);
                    
                    // Wypełnij tabelę danymi
                    const tableBody = document.getElementById('sms-table-body');
                    let tableHTML = '';
                    
                    paginatedMessages.forEach(sms => {
                        // Przygotuj surowe dane
                        const rawPayload = sms.raw_payload || '{}';
                        const payloadPreview = typeof rawPayload === 'string' && rawPayload.length > 30 
                            ? rawPayload.substring(0, 30) + '...' 
                            : rawPayload;
                        
                        tableHTML += `
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-3 px-4">${sms.id}</td>
                                <td class="py-3 px-4">${sms.phone_number}</td>
                                <td class="py-3 px-4 font-medium">
                                    <div class="max-w-xs overflow-hidden text-ellipsis whitespace-nowrap" title="${sms.message}">
                                        ${sms.message}
                                    </div>
                                </td>
                                <td class="py-3 px-4">${formatDate(sms.received_at)}</td>
                                <td class="py-3 px-4">${sms.sim_number || 'N/A'}</td>
                                <td class="py-3 px-4">
                                    <button
                                        onclick="showRawData(${JSON.stringify(rawPayload).replace(/"/g, '&quot;')})"
                                        class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded"
                                    >
                                        Pokaż dane
                                    </button>
                                </td>
                                <td class="py-3 px-4">
                                    <button
                                        onclick="showDeleteConfirmation(${sms.id}, '${sms.phone_number}', '${sms.message.replace(/'/g, "\\'")}')"
                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v10M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3" />
                                        </svg>
                                        Usuń
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    tableBody.innerHTML = tableHTML;
                    
                    // Aktualizuj kontrolki paginacji
                    updatePaginationControls();
                    
                } else {
                    document.getElementById('sms-table-body').innerHTML = `
                        <tr>
                            <td colspan="7" class="py-4 px-4 text-center">Brak SMS-ów w bazie danych</td>
                        </tr>
                    `;
                    document.getElementById('pagination-controls').innerHTML = '';
                    document.getElementById('pagination-info').textContent = 'Brak danych';
                }
                
            } catch (error) {
                document.getElementById('sms-table-body').innerHTML = `
                    <tr>
                        <td colspan="7" class="py-4 px-4 text-center text-red-600">
                            Wystąpił błąd podczas pobierania danych: ${error.message}
                        </td>
                    </tr>
                `;
                console.error('Błąd pobierania SMS-ów:', error);
            }
        }
        
        // Zaktualizuj kontrolki paginacji
        function updatePaginationControls() {
            const paginationControls = document.getElementById('pagination-controls');
            let controlsHTML = '';
            
            // Przycisk "Poprzednia"
            controlsHTML += `
                <button 
                    class="px-4 py-2 ${currentPage === 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'} text-white rounded-md shadow-md"
                    ${currentPage === 1 ? 'disabled' : `onclick="changePage(${currentPage - 1})"`}
                >
                    Poprzednia
                </button>
            `;
            
            // Numerowane strony
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                controlsHTML += `
                    <button 
                        class="px-4 py-2 ${i === currentPage ? 'bg-indigo-800 text-white' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'} rounded-md shadow-md"
                        onclick="changePage(${i})"
                    >
                        ${i}
                    </button>
                `;
            }
            
            // Przycisk "Następna"
            controlsHTML += `
                <button 
                    class="px-4 py-2 ${currentPage === totalPages ? 'bg-gray-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'} text-white rounded-md shadow-md"
                    ${currentPage === totalPages ? 'disabled' : `onclick="changePage(${currentPage + 1})"`}
                >
                    Następna
                </button>
            `;
            
            paginationControls.innerHTML = controlsHTML;
        }
        
        // Zmień stronę
        function changePage(page) {
            currentPage = page;
            fetchSMSList(page);
            // Przewiń do góry tabeli
            document.querySelector('table').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Formatuj datę
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            
            try {
                const date = new Date(dateString);
                return date.toLocaleString('pl-PL', { 
                    year: 'numeric', 
                    month: '2-digit', 
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            } catch (e) {
                return dateString;
            }
        }
        
        // Obsługa klawisza Escape dla modali
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRawDataModal();
                closeDeleteModal();
            }
        });
        
        // Inicjalizacja
        document.addEventListener('DOMContentLoaded', function() {
            // Ustawienie globalnych funkcji
            window.changePage = changePage;
            window.showRawData = showRawData;
            window.closeRawDataModal = closeRawDataModal;
            window.showDeleteConfirmation = showDeleteConfirmation;
            window.closeDeleteModal = closeDeleteModal;
            
            // Wywołaj funkcje inicjalizacyjne
            checkServerStatus();
            fetchSMSList();
            
            // Obsługa przycisku odświeżania
            document.getElementById('refresh-btn').addEventListener('click', function() {
                fetchSMSList(currentPage);
            });
            
            // Obsługa przycisku potwierdzenia usunięcia
            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                if (smsToDeleteId) {
                    deleteSMS(smsToDeleteId);
                }
            });
            
            // Automatyczne odświeżanie co 30 sekund
            setInterval(() => {
                fetchSMSList(currentPage);
            }, 30000);
            
            // Zamykanie modali po kliknięciu poza nimi
            document.getElementById('raw-data-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeRawDataModal();
                }
            });
            
            document.getElementById('delete-confirm-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeDeleteModal();
                }
            });
            
            // Obsługa przycisku "Wyczyść wszystko"
            document.getElementById('clear-all-button').addEventListener('click', function() {
                document.getElementById('clear-all-modal').classList.remove('hidden');
            });

            // Obsługa przycisku "Anuluj" w modalu
            document.getElementById('cancel-clear-all').addEventListener('click', function() {
                document.getElementById('clear-all-modal').classList.add('hidden');
            });

            // Obsługa przycisku "Tak, usuń wszystko" w modalu
            document.getElementById('confirm-clear-all').addEventListener('click', function() {
                clearAllSMS();
            });

            // Funkcja do usuwania wszystkich SMS-ów
            async function clearAllSMS() {
                try {
                    const response = await fetch('/api/sms/clear-all', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        // Zamknij modal
                        document.getElementById('clear-all-modal').classList.add('hidden');
                        
                        // Odśwież listę SMS-ów
                        fetchSMSList(1);
                        
                        // Pokaż powiadomienie
                        showNotification(`Usunięto wszystkie SMS-y (${data.count})`, 'success');
                    } else {
                        showNotification('Błąd podczas usuwania SMS-ów: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('Błąd usuwania wszystkich SMS-ów:', error);
                    showNotification('Błąd podczas usuwania SMS-ów', 'error');
                }
            }

            // Funkcja do wyświetlania powiadomień
            function showNotification(message, type = 'info') {
                const notification = document.getElementById('notification');
                const notificationMessage = document.getElementById('notification-message');
                
                // Ustaw treść powiadomienia
                notificationMessage.textContent = message;
                
                // Ustaw kolor powiadomienia w zależności od typu
                notification.className = 'fixed bottom-4 right-4 p-4 rounded-lg shadow-lg transform transition-transform duration-300 translate-y-full opacity-0';
                
                if (type === 'success') {
                    notification.classList.add('bg-green-100', 'text-green-800');
                } else if (type === 'error') {
                    notification.classList.add('bg-red-100', 'text-red-800');
                } else {
                    notification.classList.add('bg-blue-100', 'text-blue-800');
                }
                
                // Pokaż powiadomienie
                setTimeout(() => {
                    notification.classList.remove('translate-y-full', 'opacity-0');
                }, 100);
                
                // Ukryj powiadomienie po 3 sekundach
                setTimeout(() => {
                    notification.classList.add('translate-y-full', 'opacity-0');
                }, 3000);
            }
        });
    </script>
    @endpush
</x-app-layout>