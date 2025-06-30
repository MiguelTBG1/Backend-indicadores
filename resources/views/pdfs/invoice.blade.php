<!DOCTYPE html>
<html>
<head>
    <title>Factura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .item-table th, .item-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Factura #{{ $invoiceNumber }}</h1>
        <p>Fecha: {{ $date }}</p>
    </div>

    <p>Cliente: {{ $customerName }}</p>

    <table class="item-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item['product'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>${{ number_format($item['price'], 2) }}</td>
                    <td>${{ number_format($item['quantity'] * $item['price'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total a pagar: <strong>${{ number_format($total, 2) }}</strong></p>
    </div>
</body>
</html>
