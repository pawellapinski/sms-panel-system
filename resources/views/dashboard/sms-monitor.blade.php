<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Monitor SMS') }}
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
                        <button id="refresh-btn" class="refresh-btn px-6 py-3 bg-indigo-700 hover:bg-indigo-800 text-white font-bold rounded-lg border-2 border-indigo-900 shadow-lg">
                            <span class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Odśwież
                            </span>
                        </button>
                    </div>

                    <!-- Ostatni SMS - z poprawioną czytelnością, w jednej kolumnie -->
                    <div id="last-sms-container" class="relative">
                        <div id="last-sms" class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200 shadow-md">
                            <h3 class="text-lg font-bold mb-3 text-indigo-800 border-b border-indigo-200 pb-2">Ostatni odebrany SMS:</h3>
                            <div id="last-sms-content" class="text-lg">Ładowanie...</div>
                        </div>
                        <!-- Placeholder dla sticky SMS - będzie widoczny tylko gdy oryginalny SMS jest przyklejony -->
                        <div id="last-sms-placeholder" class="hidden"></div>
                    </div>

                    <!-- Lista SMS-ów - z poprawioną czytelnością, w jednej kolumnie -->
                    <div id="sms-list" class="mt-8">
                        <h3 class="text-xl font-bold mb-4 text-indigo-800 border-b border-indigo-200 pb-2">Lista SMS-ów:</h3>
                        <div id="sms-items" class="text-lg">Ładowanie...</div>

                        <!-- Paginacja z separatorem -->
                        <div class="mt-8 pt-4 border-t-2 border-indigo-100">
                            <div class="text-center text-indigo-700 font-bold mb-4">Strony</div>
                            <div id="pagination" class="flex justify-center items-center space-x-2">
                                <!-- Przyciski paginacji będą generowane przez JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Base URL
        const baseUrl = "{{ url('/') }}";

        // Zmienne globalne dla paginacji
        let allMessages = [];
        let currentPage = 1;
        const messagesPerPage = 10;

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

        // Pobierz ostatniego SMS-a
        async function getLastSMS() {
            try {
                const response = await fetch(baseUrl + '/api/last-sms');
                const data = await response.json();

                const lastSmsContent = document.getElementById('last-sms-content');

                if (data.status === 'success' && data.sms) {
                    const sms = data.sms;
                    // Format daty
                    const receivedDate = new Date(sms.received_at);
                    const formattedDate = receivedDate.toLocaleString('pl-PL', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    lastSmsContent.innerHTML = `
                        <div class="bg-white p-4 rounded-lg shadow">
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <span class="font-bold text-indigo-700 w-16">Od:</span>
                                    <span class="text-lg">${sms.phone_number}</span>
                                </div>
                                <div>
                                    <span class="font-bold text-indigo-700 block mb-1">Treść:</span>
                                    <div class="text-lg font-medium bg-yellow-50 p-3 rounded border border-yellow-200 break-words">${sms.message}</div>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-bold text-indigo-700 w-16">Czas:</span>
                                    <span class="text-base text-gray-600">${formattedDate}</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <span class="font-medium w-16">ID:</span>
                                    <span>${sms.id}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Powiadom o załadowaniu nowego SMS-a
                    document.dispatchEvent(new CustomEvent('smsLoaded'));
                } else {
                    lastSmsContent.innerHTML = '<p class="p-4 bg-gray-100 rounded text-center">Brak SMS-ów w bazie danych.</p>';
                }

            } catch (error) {
                document.getElementById('last-sms-content').innerHTML = '<p class="p-4 bg-red-100 text-red-700 rounded text-center">Błąd: Nie udało się pobrać ostatniego SMS-a.</p>';
                console.error('Błąd pobierania ostatniego SMS:', error);
            }
        }

        // Renderuj stronę SMS-ów
        function renderSMSPage(page) {
            const smsItems = document.getElementById('sms-items');
            currentPage = page;

            if (allMessages.length === 0) {
                smsItems.innerHTML = '<p class="p-4 bg-gray-100 rounded text-center">Brak SMS-ów.</p>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            // Oblicz indeksy dla aktualnej strony
            const startIndex = (page - 1) * messagesPerPage;
            const endIndex = Math.min(startIndex + messagesPerPage, allMessages.length);
            const pageMessages = allMessages.slice(startIndex, endIndex);

            let html = '';

            pageMessages.forEach(sms => {
                // Format daty
                const receivedDate = new Date(sms.received_at);
                const formattedDate = receivedDate.toLocaleString('pl-PL', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `
                    <div class="sms-item p-4 mb-4 bg-blue-50 border border-blue-200 rounded-lg shadow-sm hover:shadow-md transition duration-300 max-w-3xl mx-auto">
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <span class="font-bold text-blue-700 w-16">Od:</span>
                                <span class="text-lg">${sms.phone_number}</span>
                            </div>
                            <div>
                                <span class="font-bold text-blue-700 block mb-1">Treść:</span>
                                <div class="text-lg font-medium bg-white p-3 rounded border border-blue-100 break-words">${sms.message}</div>
                            </div>
                            <div class="flex items-center">
                                <span class="font-bold text-blue-700 w-16">Czas:</span>
                                <span class="text-base text-gray-600">${formattedDate}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <span class="font-medium w-16">ID:</span>
                                <span>${sms.id}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            smsItems.innerHTML = html;

            // Renderuj paginację
            renderPagination();
        }

        // Renderuj paginację
        function renderPagination() {
            const paginationElement = document.getElementById('pagination');
            const totalPages = Math.ceil(allMessages.length / messagesPerPage);

            // Zawsze pokazuj paginację, nawet dla jednej strony
            let paginationHtml = '';

            // Dodaj informację o stronach
            paginationHtml += `
                <div class="text-gray-600 mr-4">
                    Strona ${currentPage} z ${totalPages > 0 ? totalPages : 1}
                </div>
            `;

            // Przycisk "Poprzednia"
            paginationHtml += `
                <button
                    class="px-4 py-2 ${currentPage === 1 ? 'bg-gray-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'} text-white rounded-md shadow-md"
                    ${currentPage === 1 ? 'disabled' : 'onclick="smsApp.changePage(' + (currentPage - 1) + ')"'}
                >
                    Poprzednia
                </button>
            `;

            // Numerowane strony
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(Math.max(totalPages, 1), startPage + 4);

            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <button
                        class="px-4 py-2 ${i === currentPage ? 'bg-indigo-800 text-white' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'} rounded-md shadow-md"
                        onclick="smsApp.changePage(${i})"
                    >
                        ${i}
                    </button>
                `;
            }

            // Przycisk "Następna"
            paginationHtml += `
                <button
                    class="px-4 py-2 ${currentPage === Math.max(totalPages, 1) ? 'bg-gray-300 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'} text-white rounded-md shadow-md"
                    ${currentPage === Math.max(totalPages, 1) ? 'disabled' : 'onclick="smsApp.changePage(' + (currentPage + 1) + ')"'}
                >
                    Następna
                </button>
            `;

            paginationElement.innerHTML = paginationHtml;
        }

        // Inicjalizacja aplikacji
        const smsApp = {
            changePage: function(page) {
                console.log(`Przechodzę do strony ${page}`);
                renderSMSPage(page);
                // Przewiń do góry listy SMS-ów
                document.getElementById('sms-list').scrollIntoView({ behavior: 'smooth' });
            },

            init: function() {
                checkServerStatus();
                getLastSMS();
                this.loadAllMessages();
                this.initStickyLastSms();

                // Dodaj obsługę przycisku odświeżania
                document.getElementById('refresh-btn').addEventListener('click', () => {
                    this.loadAllMessages();
                });

                // Automatyczne odświeżanie co 30 sekund
                setInterval(() => {
                    getLastSMS();
                    this.loadAllMessages();
                }, 30000);
            },

            // Inicjalizacja efektu sticky dla ostatniego SMS-a
            initStickyLastSms: function() {
                const lastSms = document.getElementById('last-sms');
                const lastSmsContainer = document.getElementById('last-sms-container');
                const lastSmsPlaceholder = document.getElementById('last-sms-placeholder');
                
                // Utwórz kopię SMS-a dla efektu sticky
                const stickySms = lastSms.cloneNode(true);
                stickySms.id = 'sticky-last-sms';
                stickySms.classList.add('hidden', 'fixed', 'top-0', 'left-0', 'right-0', 'z-50');
                stickySms.classList.add('shadow-lg', 'border-b', 'border-indigo-300');
                stickySms.style.maxWidth = '100%';
                stickySms.style.margin = '0';
                stickySms.style.borderRadius = '0';
                stickySms.style.backgroundColor = 'rgba(238, 242, 255, 0.97)'; // Jaśniejszy indigo z przezroczystością
                
                // Dodaj przycisk zamykania
                const closeButton = document.createElement('button');
                closeButton.id = 'close-sticky-sms';
                closeButton.innerHTML = '&times;'; // Symbol X
                closeButton.classList.add('absolute', 'top-2', 'right-2');
                closeButton.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'rounded-full');
                closeButton.classList.add('w-8', 'h-8', 'flex', 'items-center', 'justify-center', 'font-bold', 'text-xl');
                closeButton.classList.add('shadow-md', 'focus:outline-none', 'transition', 'duration-200');
                closeButton.style.zIndex = '60';
                
                // Dodaj obsługę kliknięcia przycisku zamykania
                closeButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Ukryj sticky SMS
                    stickySms.classList.add('hidden');
                    // Zapamiętaj, że użytkownik zamknął sticky SMS
                    this.stickySmsVisible = false;
                });
                
                stickySms.appendChild(closeButton);
                document.body.appendChild(stickySms);
                
                // Flaga określająca, czy sticky SMS powinien być widoczny
                this.stickySmsVisible = true;
                
                // Obsługa przewijania strony
                window.addEventListener('scroll', () => {
                    const containerRect = lastSmsContainer.getBoundingClientRect();
                    const smsRect = lastSms.getBoundingClientRect();
                    
                    // Jeśli oryginalny SMS wychodzi poza górną krawędź ekranu i użytkownik nie zamknął sticky SMS
                    if (smsRect.bottom <= 0 && this.stickySmsVisible) {
                        // Pokaż sticky SMS
                        stickySms.classList.remove('hidden');
                        // Pokaż placeholder, aby zachować wysokość
                        lastSmsPlaceholder.classList.remove('hidden');
                        lastSmsPlaceholder.style.height = `${lastSms.offsetHeight}px`;
                        // Ukryj oryginalny SMS
                        lastSms.classList.add('invisible');
                    } else {
                        // Ukryj sticky SMS
                        stickySms.classList.add('hidden');
                        // Ukryj placeholder
                        lastSmsPlaceholder.classList.add('hidden');
                        // Pokaż oryginalny SMS
                        lastSms.classList.remove('invisible');
                    }
                });
                
                // Aktualizuj zawartość sticky SMS-a po załadowaniu nowego SMS-a
                document.addEventListener('smsLoaded', () => {
                    stickySms.innerHTML = lastSms.innerHTML;
                    
                    // Dodaj ponownie przycisk zamykania, który został nadpisany
                    stickySms.appendChild(closeButton);
                    
                    // Resetuj flagę widoczności przy nowym SMS-ie
                    this.stickySmsVisible = true;
                });
            },

            // Nowa metoda do ładowania wszystkich wiadomości
            loadAllMessages: async function() {
                try {
                    // Najpierw spróbuj pobrać wszystkie wiadomości
                    await this.fetchAllMessages();
                } catch (error) {
                    console.error('Błąd ładowania wiadomości:', error);
                }
            },

            // Metoda do pobierania wszystkich wiadomości
            fetchAllMessages: async function() {
                try {
                    const response = await fetch(baseUrl + '/api/sms-list?limit=100');
                    const data = await response.json();

                    console.log('Otrzymano SMS-y:', data.messages ? data.messages.length : 0);

                    if (data.messages && data.messages.length > 0) {
                        allMessages = data.messages;
                        const totalPages = Math.ceil(allMessages.length / messagesPerPage);
                        console.log(`Liczba stron: ${totalPages} (${allMessages.length} SMS-ów / ${messagesPerPage} na stronę)`);

                        renderSMSPage(currentPage);
                    } else {
                        allMessages = [];
                        document.getElementById('sms-items').innerHTML = '<p class="p-4 bg-gray-100 rounded text-center">Brak SMS-ów.</p>';
                        document.getElementById('pagination').innerHTML = `
                            <div class="text-gray-500">Brak SMS-ów w systemie.</div>
                        `;
                    }

                    // Zaktualizuj też ostatniego SMS-a
                    getLastSMS();

                } catch (error) {
                    document.getElementById('sms-items').innerHTML = `<p class="p-4 bg-red-100 text-red-700 rounded text-center">Błąd: Nie udało się pobrać listy SMS-ów. ${error.message}</p>`;
                    document.getElementById('pagination').innerHTML = '';
                    console.error('Błąd pobierania listy SMS:', error);
                    throw error;
                }
            }
        };

        // Uruchom funkcje po załadowaniu strony
        document.addEventListener('DOMContentLoaded', function() {
            // Inicjalizuj aplikację
            window.smsApp = smsApp;
            smsApp.init();
        });
    </script>
    @endpush
</x-app-layout>
