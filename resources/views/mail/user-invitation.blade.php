<x-mail::message>
<div style="text-align:center; margin-bottom:24px;">
<span style="display:inline-block; padding:10px 14px; border-radius:12px; background:#276553; color:#ffffff; font-size:20px; font-weight:800;">CF</span>
</div>

# Bienvenue sur CaisseFlow

Votre accès à la caisse de l’entreprise vient d’être créé pour **{{ $recipientEmail }}**.

Il ne vous reste qu’une étape : cliquez sur le bouton ci-dessous pour renseigner votre nom et choisir personnellement votre mot de passe.

<x-mail::button :url="$invitationUrl" color="success">
Créer mon mot de passe
</x-mail::button>

<x-mail::panel>
Ce lien personnel est valable pendant **72 heures** et ne peut être utilisé qu’une seule fois.
</x-mail::panel>

Si vous n’attendiez pas cette invitation, vous pouvez ignorer ce message en toute sécurité.

À bientôt,  
**L’équipe CaisseFlow**

<x-slot:subcopy>
Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :  
<span style="word-break:break-all;">{{ $invitationUrl }}</span>
</x-slot:subcopy>
</x-mail::message>
