<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, sans-serif; background: #FAF6F0; padding: 2rem; color: #211D19;">
    <div style="max-width: 480px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; border: 1px solid #E9DFD2;">
        <div style="background: #1C1A17; padding: 1.5rem; text-align: center;">
            <span style="color: #F2ECE4; font-weight: 700; font-size: 1.1rem;">MenuQr</span>
        </div>
        <div style="padding: 2rem;">
            <h1 style="font-size: 1.2rem; margin: 0 0 1rem;">👋 Tu es invité(e) !</h1>
            <p>Bonjour {{ $nomComplet }},</p>
            <p>
                <strong>{{ $restaurantNom }}</strong> t'invite à rejoindre son équipe sur MenuQr,
                avec le rôle <strong>{{ $roleLabel }}</strong>.
            </p>
            <p>Clique sur le bouton ci-dessous pour créer ton mot de passe et accéder à ton compte :</p>
            <p style="text-align: center; margin: 2rem 0;">
                <a href="{{ $lien }}" style="background: #E85D2B; color: white; padding: 0.85rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 700;">
                    Accepter l'invitation
                </a>
            </p>
            <p style="color: #756A5C; font-size: 0.85rem;">
                Si tu ne t'attendais pas à cette invitation, tu peux ignorer cet email sans risque.
            </p>
        </div>
    </div>
</body>
</html>