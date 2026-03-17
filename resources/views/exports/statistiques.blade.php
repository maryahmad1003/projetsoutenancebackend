<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Statistiques DocSecur</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        h1 { color: #0F766E; text-align: center; }
        .stat { margin: 20px 0; padding: 15px; background: #E1F5EE; border-radius: 8px; }
        .stat h3 { color: #0D5D57; margin: 0; }
        .stat p { font-size: 24px; font-weight: bold; margin: 5px 0 0; }
        .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <h1>DocSecur - Rapport Statistiques</h1>
    <p style="text-align: center; color: #666;">Généré le {{ $date }}</p>

    <div class="stat">
        <h3>Total Patients</h3>
        <p>{{ $total_patients }}</p>
    </div>

    <div class="stat">
        <h3>Total Consultations</h3>
        <p>{{ $total_consultations }}</p>
    </div>

    <div class="footer">
        <p>DocSecur - Plateforme de dossiers médicaux sécurisés</p>
    </div>
</body>
</html>