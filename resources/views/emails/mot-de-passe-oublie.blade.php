<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, sans-serif; background: #FAF6F0; padding: 2rem; color: #211D19;">
    <div style="max-width: 480px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; border: 1px solid #E9DFD2;">
        <div style="background: #1C1A17; padding: 1.5rem; text-align: center;">
            <span style="color: #F2ECE4; font-weight: 700; font-size: 1.1rem;">MenuQr</span>
        </div>
        <div style="padding: 2rem;">
            <h1 style="font-size: 1.2rem; margin: 0 0 1rem;">🔑 Réinitialisation de mot de passe</h1>
            <p>Bonjour,</p>
            <p>Tu as demandé à réinitialiser ton mot de passe MenuQr. Clique sur le bouton ci-dessous pour en choisir un nouveau :</p>
            <p style="text-align: center; margin: 2rem 0;">
                <a href="{{ $lien }}" style="background: #E85D2B; color: white; padding: 0.85rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 700;">
                    Réinitialiser mon mot de passe
                </a>
            </p>
            <p style="color: #756A5C; font-size: 0.85rem;">
                Ce lien expire dans 1 heure. Si tu n'es pas à l'origine de cette demande, ignore simplement cet email — ton mot de passe reste inchangé.
            </p>
        </div>
    </div>
</body>
</html>
