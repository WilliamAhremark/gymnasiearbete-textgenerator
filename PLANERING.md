# Planering

Jag bygger en webbapplikation för NeuralText AI där användaren kan registrera sig, logga in, verifiera sin e-postadress och skapa text med hjälp av en AI-tjänst. Webbplatsen använder HTML för struktur, CSS för layout och responsiv design, JavaScript för interaktion, PHP för backend-logik och SQL för lagring av användare och genererad text.

Följande delar från kurslitteratur och W3Schools omsätts i projektet:

- HTML-formulär för registrering, inloggning, profil och textredigering.
- CSS Grid, Flexbox och media queries för responsiv design.
- JavaScript för förbättrad användarupplevelse i formulär och gränssnitt.
- PHP för sessionshantering, säker inloggning, CSRF-skydd och dynamiska sidor.
- SQL för användare, verifieringstoken, historik och rollhantering.
- Prepared statements för att minska risken för SQL-injektion.
- `password_hash()` och `password_verify()` för säkra lösenord.
- Cookies och sessionsinställningar för säkrare inloggning.
- SMTP via PHPMailer för e-postverifiering.

Jag har också planerat in en administratörsdel eftersom betygsnivån A kräver mer än en enkel webbplats. Därför finns rollbaserad åtkomst, verifieringsstatus, kontoöversikt och historikhantering.

Målet är att visa både bredd och djup: flera tekniker ska samverka i en tydlig helhet, och säkerhetsdelarna ska vara praktiskt implementerade snarare än bara beskrivna.