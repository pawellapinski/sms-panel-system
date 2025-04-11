<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ustawienia') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Sekcja - Sprawdzanie webhooków -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sprawdź zarejestrowane webhooki dla Local Serwer</h3>

                    <!-- Formularz sprawdzania webhooków -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adres IP i port SMS Gateway:
                                </label>
                                <input type="text" id="ip_address"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="np. 192.168.1.86:8089">
                            </div>
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nazwa użytkownika:
                                </label>
                                <input type="text" id="username"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="np. testowy">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Hasło:
                            </label>
                            <input type="password" id="password"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="np. admin123">
                        </div>
                        <div class="flex justify-end">
                            <button id="check_webhooks_btn"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-md">
                                Sprawdź webhooki
                            </button>
                        </div>
                    </div>

                    <!-- Status operacji -->
                    <div id="status_message" class="hidden mb-4 p-4 rounded-md">
                        <!-- Tutaj będzie wyświetlany status operacji -->
                    </div>

                    <!-- Tabela z webhookami -->
                    <div id="webhooks_table_container" class="hidden">
                        <h4 class="text-md font-medium text-gray-900 mb-2">Zarejestrowane webhooki:</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zdarzenie</th>
                                    </tr>
                                </thead>
                                <tbody id="webhooks_table_body" class="bg-white divide-y divide-gray-200">
                                    <!-- Tutaj będą wstawione wiersze z webhookami -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Informacja o braku webhooków -->
                    <div id="no_webhooks_message" class="hidden text-gray-500 text-center py-4">
                        Nie znaleziono żadnych zarejestrowanych webhooków.
                    </div>

                    <!-- Informacje pomocnicze -->
                    <div class="mt-8 bg-blue-50 p-4 rounded-md">
                        <h4 class="text-blue-800 font-medium mb-2">Informacje pomocnicze:</h4>
                        <ul class="list-disc pl-5 text-sm text-blue-800">
                            <li class="mb-1">Podaj adres IP i port aplikacji SMS Gateway z telefonu.</li>
                            <li class="mb-1">Upewnij się, że telefon i komputer są w tej samej sieci lokalnej.</li>
                            <li class="mb-1">Sprawdź, czy aplikacja SMS Gateway jest uruchomiona na telefonie.</li>
                            <li>Jeśli masz problemy z połączeniem, sprawdź ustawienia firewalla i routera.</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Sekcja - Konfiguracja Local Server -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Konfiguracja Local Server</h3>

                    <!-- Status operacji konfiguracji -->
                    <div id="config_status_message" class="hidden mb-4 p-4 rounded-md">
                        <!-- Tutaj będzie wyświetlany status operacji -->
                    </div>

                    <!-- Formularz konfiguracji Local Server -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="local_server_address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Adres IP i port SMS Gateway:
                                </label>
                                <input type="text" id="local_server_address" value="{{ config('sms.local.server') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="np. 192.168.1.86:8089">
                            </div>
                            <div>
                                <label for="local_username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nazwa użytkownika:
                                </label>
                                <input type="text" id="local_username" value="{{ config('sms.local.username') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="np. testowy">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="local_password" class="block text-sm font-medium text-gray-700 mb-1">
                                Hasło:
                            </label>
                            <input type="password" id="local_password" value="{{ config('sms.local.password') }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="np. admin123">
                        </div>
                        <div class="flex justify-end">
                            <button id="save_local_config_btn"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-md">
                                Zapisz ustawienia
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informacja o rejestracji webhook przez curl -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Jak zarejestrować webhook przez konsolę</h3>
                    
                    <div class="bg-yellow-50 p-4 rounded-lg mb-6 border border-yellow-200">
                        <h4 class="text-yellow-800 font-medium mb-2">Użyj curl w konsoli:</h4>
                        <p class="text-sm text-yellow-800 mb-3">Aby zarejestrować nowy webhook, użyj poniższej komendy curl, dostosowując parametry do swoich potrzeb:</p>
                        <div class="bg-gray-800 text-white p-4 rounded-md overflow-x-auto mb-3">
                            <pre class="text-xs">curl -X POST -u testowy:admin123 -H "Content-Type: application/json" -d '{
  "url": "https://twoj-adres-ngrok.ngrok-free.app/api/webhook/sms",
  "secret": "tajnyKlucz",
  "events": ["sms:received"]
}' http://192.168.1.86:8089/webhooks</pre>
                        </div>
                        <p class="text-sm text-yellow-800">Po rejestracji, kliknij "Sprawdź webhooki" aby upewnić się, że webhook został poprawnie dodany.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Funkcje dla sekcji - Sprawdzanie webhooków ---
            const checkWebhooksBtn = document.getElementById('check_webhooks_btn');
            const statusMessage = document.getElementById('status_message');
            const webhooksTableContainer = document.getElementById('webhooks_table_container');
            const webhooksTableBody = document.getElementById('webhooks_table_body');
            const noWebhooksMessage = document.getElementById('no_webhooks_message');

            // Sprawdzanie webhooków po kliknięciu przycisku
            checkWebhooksBtn.addEventListener('click', async function() {
                // Pobierz dane z formularza
                const ipAddress = document.getElementById('ip_address').value.trim();
                const username = document.getElementById('username').value.trim();
                const password = document.getElementById('password').value.trim();

                // Sprawdź czy wszystkie pola są wypełnione
                if (!ipAddress || !username || !password) {
                    showStatus('error', 'Wypełnij wszystkie pola formularza');
                    return;
                }

                // Pokaż status "Ładowanie..."
                showStatus('loading', 'Pobieranie webhooków...');

                try {
                    // Wywołaj API do sprawdzenia webhooków
                    const response = await fetch('{{ url("/api/check-webhooks") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ip_address: ipAddress,
                            username: username,
                            password: password
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        // Pokaż komunikat o sukcesie
                        showStatus('success', 'Webhooks pobrane pomyślnie!');

                        // Wyświetl webhooks w tabeli
                        displayWebhooks(data.webhooks);
                    } else {
                        // Pokaż komunikat o błędzie
                        showStatus('error', data.message || 'Wystąpił błąd podczas pobierania webhooków');

                        // Ukryj tabelę
                        webhooksTableContainer.classList.add('hidden');
                        noWebhooksMessage.classList.add('hidden');
                    }
                } catch (error) {
                    console.error('Błąd:', error);
                    showStatus('error', 'Wystąpił błąd podczas komunikacji z serwerem');

                    // Ukryj tabelę
                    webhooksTableContainer.classList.add('hidden');
                    noWebhooksMessage.classList.add('hidden');
                }
            });

            // Funkcja do wyświetlania statusu
            function showStatus(type, message) {
                statusMessage.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');

                switch (type) {
                    case 'success':
                        statusMessage.classList.add('bg-green-100', 'text-green-800');
                        break;
                    case 'error':
                        statusMessage.classList.add('bg-red-100', 'text-red-800');
                        break;
                    case 'loading':
                        statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
                        break;
                }

                statusMessage.textContent = message;
            }

            // Funkcja do wyświetlania webhooków w tabeli
            function displayWebhooks(webhooks) {
                // Wyczyść tabelę
                webhooksTableBody.innerHTML = '';

                if (webhooks && webhooks.length > 0) {
                    // Pokaż tabelę i ukryj komunikat o braku webhooków
                    webhooksTableContainer.classList.remove('hidden');
                    noWebhooksMessage.classList.add('hidden');

                    // Dodaj wiersze do tabeli
                    webhooks.forEach(webhook => {
                        const row = document.createElement('tr');

                        // ID webhooka
                        const idCell = document.createElement('td');
                        idCell.className = 'px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900';
                        idCell.textContent = webhook.id || 'N/A';
                        row.appendChild(idCell);

                        // URL webhooka
                        const urlCell = document.createElement('td');
                        urlCell.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-500';
                        urlCell.textContent = webhook.url || 'N/A';
                        row.appendChild(urlCell);

                        // Zdarzenie
                        const eventCell = document.createElement('td');
                        eventCell.className = 'px-6 py-4 whitespace-nowrap text-sm text-gray-500';
                        eventCell.textContent = webhook.event || 'N/A';
                        row.appendChild(eventCell);

                        webhooksTableBody.appendChild(row);
                    });
                } else {
                    // Pokaż komunikat o braku webhooków i ukryj tabelę
                    webhooksTableContainer.classList.add('hidden');
                    noWebhooksMessage.classList.remove('hidden');
                }
            }
        });

        // --- Funkcje dla sekcji - Konfiguracja Local Server ---
        const saveLocalConfigBtn = document.getElementById('save_local_config_btn');
        const configStatusMessage = document.getElementById('config_status_message');

        // Zapisywanie ustawień Local Server
        saveLocalConfigBtn.addEventListener('click', async function() {
            const serverAddress = document.getElementById('local_server_address').value.trim();
            const username = document.getElementById('local_username').value.trim();
            const password = document.getElementById('local_password').value.trim();

            // Sprawdź czy wszystkie pola są wypełnione
            if (!serverAddress || !username || !password) {
                showConfigStatus('error', 'Wypełnij wszystkie pola formularza');
                return;
            }

            // Pokaż status "Zapisywanie..."
            showConfigStatus('loading', 'Zapisywanie ustawień Local Server...');

            try {
                // Wywołaj API do zapisania ustawień
                const response = await fetch('{{ url("/api/settings/save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        local_server_address: serverAddress,
                        local_username: username,
                        local_password: password
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showConfigStatus('success', 'Ustawienia Local Server zostały zapisane pomyślnie!');
                } else {
                    showConfigStatus('error', data.message || 'Wystąpił błąd podczas zapisywania ustawień');
                }
            } catch (error) {
                console.error('Błąd:', error);
                showConfigStatus('error', 'Wystąpił błąd podczas komunikacji z serwerem');
            }
        });

        // Funkcja do wyświetlania statusu konfiguracji
        function showConfigStatus(type, message) {
            configStatusMessage.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');

            switch (type) {
                case 'success':
                    configStatusMessage.classList.add('bg-green-100', 'text-green-800');
                    break;
                case 'error':
                    configStatusMessage.classList.add('bg-red-100', 'text-red-800');
                    break;
                case 'loading':
                    configStatusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
                    break;
            }

            configStatusMessage.textContent = message;
        }
    });
</script>
</x-app-layout>
