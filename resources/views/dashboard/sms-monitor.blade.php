<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold mb-4 text-indigo-800">Monitor SMS</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Przycisk odświeżania -->
                    <div class="mb-4">
                        <button id="refresh-button" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition duration-300 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                            </svg>
                            Odśwież dane
                        </button>
                    </div>

                    <!-- Ostatni SMS - z poprawioną czytelnością, w jednym kafelku z podziałem na dwie części -->
                    <div id="last-sms-container" class="relative">
                        <div id="last-sms" class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200 shadow-md">
                            <h3 class="text-lg font-bold mb-3 text-indigo-800 border-b border-indigo-200 pb-2">Ostatni odebrany SMS:</h3>
                            <div id="last-sms-content" class="text-lg">Ładowanie...</div>
                            <div id="server-status" class="mt-4 pt-3 text-sm text-gray-600 border-t border-indigo-200">
                                <div class="flex flex-wrap gap-4">
                                    <div class="flex items-center">
                                        <span class="font-medium">Status serwera:</span>
                                        <span id="server-status-value" class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 rounded">Ładowanie...</span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="font-medium">IP serwera:</span>
                                        <span id="server-ip" class="ml-2">Ładowanie...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Placeholder dla sticky SMS - będzie widoczny tylko gdy oryginalny SMS jest przyklejony -->
                        <div id="last-sms-placeholder" class="hidden"></div>
                    </div>

                    <!-- Lista SMS-ów - z poprawioną czytelnością, w jednym kafelku z podziałem na dwie części -->
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

        // Funkcja do sprawdzania statusu serwera
        async function checkServerStatus() {
            try {
                const response = await fetch('/status');
                const data = await response.json();

                const statusElement = document.getElementById('server-status-value');
                const ipElement = document.getElementById('server-ip');

                if (statusElement && ipElement) {
                    if (data.status === 'running') {
                        statusElement.textContent = 'running';
                        statusElement.classList.remove('bg-red-100', 'text-red-800');
                        statusElement.classList.add('bg-green-100', 'text-green-800');
                    } else {
                        statusElement.textContent = 'offline';
                        statusElement.classList.remove('bg-green-100', 'text-green-800');
                        statusElement.classList.add('bg-red-100', 'text-red-800');
                    }

                    // Ustawienie IP serwera (z fallbackiem do 127.0.0.1 jeśli nie jest dostępne)
                    ipElement.textContent = data.server_ip || '127.0.0.1';
                    
                    console.log('Status serwera zaktualizowany:', data);
                }
            } catch (error) {
                console.error('Błąd podczas sprawdzania statusu serwera:', error);
                const statusElement = document.getElementById('server-status-value');
                const ipElement = document.getElementById('server-ip');

                if (statusElement && ipElement) {
                    statusElement.textContent = 'offline';
                    statusElement.classList.remove('bg-green-100', 'text-green-800');
                    statusElement.classList.add('bg-red-100', 'text-red-800');

                    ipElement.textContent = 'Niedostępne';
                }
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
                        <div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row">
                            <div class="md:w-1/5 space-y-2 md:pr-4 md:border-r md:border-gray-200">
                                <div class="flex items-center">
                                    <span class="font-bold text-indigo-700 w-16">Od:</span>
                                    <span class="text-lg">${sms.phone_number}</span>
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
                            <div class="md:w-4/5 mt-4 md:mt-0 md:pl-4">
                                <span class="font-bold text-indigo-700 block mb-1">Treść:</span>
                                <div class="text-lg font-medium bg-yellow-50 p-3 rounded border border-yellow-200 break-words">${sms.message}</div>
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
                    <div class="sms-item p-4 mb-4 bg-blue-50 border border-blue-200 rounded-lg shadow-sm hover:shadow-md transition duration-300 flex flex-col md:flex-row">
                        <div class="md:w-1/5 space-y-2 md:pr-4 md:border-r md:border-gray-200">
                            <div class="flex items-center">
                                <span class="font-bold text-blue-700 w-16">Od:</span>
                                <span class="text-lg">${sms.phone_number}</span>
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
                        <div class="md:w-4/5 mt-4 md:mt-0 md:pl-4">
                            <span class="font-bold text-blue-700 block mb-1">Treść:</span>
                            <div class="text-lg font-medium bg-blue-50 p-3 rounded border border-blue-100 break-words">${sms.message}</div>
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
                getLastSMS();
                this.loadAllMessages();
                this.initStickyLastSms();

                // Dodaj obsługę przycisku odświeżania
                document.getElementById('refresh-button').addEventListener('click', () => {
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
                stickySms.style.backgroundColor = 'rgba(238, 242, 255, 0.85)'; // Zwiększona przezroczystość tła
                stickySms.style.backdropFilter = 'blur(5px)'; // Dodanie lekkiego rozmycia tła
                stickySms.style.paddingTop = '0.5rem'; // Mniejszy padding górny
                stickySms.style.paddingBottom = '0.5rem'; // Mniejszy padding dolny

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
            checkServerStatus();
            setInterval(checkServerStatus, 30000);
        });
    </script>
    @endpush
</x-app-layout>
