<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: -apple-system, sans-serif; background: #FAF6F0; padding: 2rem; color: #211D19;">
    <div style="max-width: 480px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; border: 1px solid #E9DFD2;">
        <div style="background: #1C1A17; padding: 1.5rem; text-align: center;">
            <span style="color: #F2ECE4; font-weight: 700; font-size: 1.1rem;">MenuQr</span>
        </div>
        <div style="padding: 2rem;">
            <h1 style="font-size: 1.2rem; margin: 0 0 1rem;">⏳ Votre abonnement expire bientôt</h1>
            <p>Bonjour,</p>
            <p>
                L'abonnement de <strong>{{ $restaurantNom }}</strong> sur le plan
                <strong>{{ $offreNom }}</strong> expire le <strong>{{ $dateFin }}</strong> (dans 3 jours).
            </p>
            <p>Pour continuer à utiliser MenuQr sans interruption, pensez à renouveler votre abonnement dès maintenant.</p>
            <p style="text-align: center; margin: 2rem 0;">
                <a href="{{ $lienAbonnement }}" style="background: #E85D2B; color: white; padding: 0.85rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 700;">
                    Renouveler mon abonnement
                </a>
            </p>
            <p style="color: #756A5C; font-size: 0.85rem;">
                Si vous avez déjà renouvelé, ignorez cet email.
            </p>
        </div>
    </div>
</body>
</html>