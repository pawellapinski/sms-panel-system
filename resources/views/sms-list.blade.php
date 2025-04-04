<!DOCTYPE html>
<html>
<head>
    <title>Lista SMS-ów</title>
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .pagination {
            margin-top: 20px;
        }
        .raw-data {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <h2>Lista otrzymanych SMS-ów</h2>
    
    <table>
        <thead>
            <tr>
                <th>Data otrzymania</th>
                <th>Numer telefonu</th>
                <th>Wiadomość</th>
                <th>Device ID</th>
                <th>SIM</th>
                <th>Surowe dane</th>
            </tr>
        </thead>
        <tbody>
            @foreach($messages as $message)
            <tr>
                <td>{{ $message->received_at->format('Y-m-d H:i:s') }}</td>
                <td>{{ $message->phone_number }}</td>
                <td>{{ $message->message }}</td>
                <td>{{ $message->device_id }}</td>
                <td>{{ $message->sim_number }}</td>
                <td class="raw-data" title="{{ json_encode($message->raw_payload, JSON_PRETTY_PRINT) }}">
                    {{ json_encode($message->raw_payload) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pagination">
        {{ $messages->links() }}
    </div>
</body>
</html> 