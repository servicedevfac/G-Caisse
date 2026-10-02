<x-mail::message>
<div style="text-align:center; margin-bottom:24px;">
<span style="display:inline-block; padding:10px 14px; border-radius:12px; background:#276553; color:#ffffff; font-size:20px; font-weight:800;">CF</span>
</div>

# Réinitialisation de votre mot de passe

Une demande de nouveau mot de passe a été reçue pour **{{ $recipientEmail }}**.

<x-mail::button :url="$resetUrl" color="success">
Choisir un nouveau mot de passe
</x-mail::button>

<x-mail::panel>
Pour votre sécurité, ce lien est valable pendant **1 heure** et ne peut être utilisé qu’une seule fois.
</x-mail::panel>

Si vous n’êtes pas à l’origine de cette demande, ignorez simplement ce message. Votre mot de passe actuel reste inchangé.

À bientôt,  
**L’équipe CaisseFlow**

<x-slot:subcopy>
Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :  
<span style="word-break:break-all;">{{ $resetUrl }}</span>
</x-slot:subcopy>
</x-mail::message>
