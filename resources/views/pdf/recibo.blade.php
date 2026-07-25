<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $recibo->id }}</title>
    <style>
        body { font-family: sans-serif; color: #333; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .title { font-size: 24px; font-weight: bold; color: #1e40af; margin: 0; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        .details { margin-top: 30px; width: 100%; border-collapse: collapse; }
        .details td { padding: 10px; border-bottom: 1px solid #ddd; }
        .label { font-weight: bold; color: #475569; width: 40%; }
        .amount { font-size: 20px; font-weight: bold; color: #16a34a; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="title">Comissió de Festes d'Ares</h1>
        <p class="subtitle">Justificant de Pagament / Recibo</p>
    </div>

    <table class="details">
        <tr>
            <td class="label">Nº de Recibo:</td>
            <td>#{{ $recibo->id }}</td>
        </tr>
        <tr>
            <td class="label">Fecha:</td>
            <td>{{ \Carbon\Carbon::parse($recibo->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Pagado por:</td>
            <td>{{ $recibo->nombre_cliente }}</td>
        </tr>
        <tr>
            <td class="label">Teléfono:</td>
            <td>{{ $recibo->telefono }}</td>
        </tr>
        <tr>
            <td class="label">Concepto:</td>
            <td>{{ $recibo->concepto ?? 'Colaboración Fiestas' }}</td>
        </tr>
        <tr>
            <td class="label">Importe:</td>
            <td class="amount">{{ number_format($recibo->importe, 2, ',', '.') }} €</td>
        </tr>
    </table>

    <div class="footer">
        <p>¡Muchas gracias por tu colaboración con las fiestas!</p>
    </div>

</body>
</html>