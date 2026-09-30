<section class="auth-visual" aria-label="Présentation de CaisseFlow">
    <a class="auth-brand" href="{{ route('login') }}"><span>CF</span> CaisseFlow</a>
    <div class="auth-visual-copy">
        <div class="wallet-badge" aria-hidden="true">▣</div>
        <h2>Votre caisse,<br>simple et efficace.</h2>
        <p>Suivez les approvisionnements, les dépenses et chaque mouvement au même endroit.</p>
    </div>
    <svg class="cash-illustration" viewBox="0 0 420 260" role="img" aria-label="Illustration d’une caisse avec pièces, reçu et calculatrice">
        <defs>
            <linearGradient id="receiptGradient" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#f7fbf7"/><stop offset="1" stop-color="#dce9df"/></linearGradient>
            <linearGradient id="calculatorGradient" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#163d34"/><stop offset="1" stop-color="#09271f"/></linearGradient>
        </defs>
        <ellipse cx="210" cy="232" rx="160" ry="18" fill="#0b3329" opacity=".28"/>
        <g transform="translate(158 25) rotate(7 72 100)">
            <rect width="145" height="198" rx="14" fill="url(#receiptGradient)"/>
            <rect x="22" y="28" width="82" height="10" rx="5" fill="#8cb29b"/>
            <rect x="22" y="53" width="101" height="8" rx="4" fill="#bad0c1"/>
            <rect x="22" y="72" width="91" height="8" rx="4" fill="#bad0c1"/>
            <rect x="22" y="91" width="104" height="8" rx="4" fill="#bad0c1"/>
            <path d="M22 128h101" stroke="#8cb29b" stroke-width="4" stroke-dasharray="7 7"/>
            <rect x="22" y="149" width="60" height="12" rx="6" fill="#d1a45e"/>
        </g>
        <g transform="translate(205 92)">
            <rect width="132" height="145" rx="17" fill="url(#calculatorGradient)"/>
            <rect x="18" y="18" width="96" height="34" rx="7" fill="#92c1a1"/>
            <g fill="#d7e8dc">
                <rect x="19" y="66" width="20" height="17" rx="4"/><rect x="47" y="66" width="20" height="17" rx="4"/><rect x="75" y="66" width="20" height="17" rx="4"/><rect x="103" y="66" width="12" height="45" rx="4" fill="#d5a45f"/>
                <rect x="19" y="91" width="20" height="17" rx="4"/><rect x="47" y="91" width="20" height="17" rx="4"/><rect x="75" y="91" width="20" height="17" rx="4"/>
                <rect x="19" y="116" width="20" height="17" rx="4"/><rect x="47" y="116" width="48" height="17" rx="4"/>
            </g>
        </g>
        <g fill="#d9a54f" stroke="#efc877" stroke-width="3">
            <ellipse cx="89" cy="217" rx="38" ry="12"/><path d="M51 184v33c0 7 17 12 38 12s38-5 38-12v-33"/><ellipse cx="89" cy="184" rx="38" ry="12"/>
            <ellipse cx="127" cy="224" rx="33" ry="11"/><path d="M94 202v22c0 7 15 11 33 11s33-4 33-11v-22"/><ellipse cx="127" cy="202" rx="33" ry="11"/>
        </g>
    </svg>
    <p class="auth-visual-foot">Toutes les opérations de l’entreprise, accessibles à votre équipe.</p>
</section>
