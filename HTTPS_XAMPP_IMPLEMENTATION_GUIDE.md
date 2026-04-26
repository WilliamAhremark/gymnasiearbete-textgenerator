# HTTPS + SSL i XAMPP (A-niva guide)

Denna guide visar exakt hur du gar fran HTTP till HTTPS for projektet, vad som sker tekniskt i varje steg, och vad du behover gora sjalv i XAMPP.

## 1. Malbild (arkitektur)

Klient (webblasare) -> Apache i XAMPP (HTTPS) -> PHP-applikation -> MySQL

Samtidigt:
PHP -> intern FastAPI pa 127.0.0.1:8000 for AI-generering.

Varfor: HTTPS skyddar trafiken mellan klient och webbserver mot avlyssning och manipulation (MITM). Intern API-trafik halls lokal pa loopback-adress.

## 2. Vad som redan ar implementerat i projektet

1. `web/.htaccess` finns nu och tvingar HTTP -> HTTPS.
2. `web/config.php` satter sakrare session-cookieflaggor (`httponly`, `samesite`, och `secure` nar anslutningen ar HTTPS).
3. `StartProject.txt` ar uppdaterad till Apache/HTTPS-flode och separat AI-API-start.

## 3. Vad du behover gora sjalv i XAMPP

## 3.1 Aktivera Apache-moduler

Oppna `C:\xampp\apache\conf\httpd.conf` och sakerstall att dessa rader inte ar kommenterade:

```apache
LoadModule ssl_module modules/mod_ssl.so
LoadModule rewrite_module modules/mod_rewrite.so
Include conf/extra/httpd-ssl.conf
```

Varfor:
- `ssl_module` ger TLS/SSL-stod.
- `rewrite_module` behovs for redirect-regler.
- `httpd-ssl.conf` laddar SSL-konfiguration.

## 3.2 Skapa certifikat med XAMPP makecert.bat

I XAMPP-mappen (ofta `C:\xampp\apache`) kor:

```powershell
Set-Location C:\xampp\apache
.\makecert.bat
```

Varfor: certifikatet identifierar servern och mojliggor krypterad handskakning. I lokal miljo ar det sjalvsignerat, sa webblasaren varnar eftersom ingen publik CA har intygat certifikatet.

## 3.3 Skapa lokal doman

1. Oppna hosts-filen som Administrator:
`C:\Windows\System32\drivers\etc\hosts`
2. Lag till:

```txt
127.0.0.1 gymnasiearbete.local
```

Varfor: domannamnet pekar lokalt till din dator och gor redovisningen mer realistisk an enbart localhost.

## 3.4 Konfigurera VirtualHost for HTTP + HTTPS

Oppna `C:\xampp\apache\conf\extra\httpd-vhosts.conf` och lagg till:

```apache
<VirtualHost *:80>
    ServerName gymnasiearbete.local
    DocumentRoot "C:/xampp/htdocs/GYMNASIEARBETE1/web"

    <Directory "C:/xampp/htdocs/GYMNASIEARBETE1/web">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    ServerName gymnasiearbete.local
    DocumentRoot "C:/xampp/htdocs/GYMNASIEARBETE1/web"

    SSLEngine on
    SSLCertificateFile "C:/xampp/apache/conf/ssl.crt/server.crt"
    SSLCertificateKeyFile "C:/xampp/apache/conf/ssl.key/server.key"

    <Directory "C:/xampp/htdocs/GYMNASIEARBETE1/web">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Varfor:
- Port 80 tar emot okrypterad HTTP (som sedan redirectas av `.htaccess`).
- Port 443 ar krypterad HTTPS med certifikat + privat nyckel.
- `AllowOverride All` behovs for att Apache ska lasa `.htaccess`.

## 3.5 Sakerstall project path i htdocs

Din webbrot maste finnas pa:
`C:\xampp\htdocs\GYMNASIEARBETE1\web`

Om projektet ligger pa annan disk kan du skapa junction/symlink.

Varfor: Apache serverar filer fran sin DocumentRoot, inte fran din tidigare `php -S` process.

## 3.6 Starta tjanster

I XAMPP Control Panel:
1. Start Apache
2. Start MySQL

## 3.7 Starta AI-backend separat

Kor i PowerShell:

```powershell
Set-Location "g:\Min enhet\Allt\Webserverprogrameringx\GYMNASIEARBETE1"
$env:AI_API_URL='http://127.0.0.1:8000/generate'
$env:PREFER_AI_API_URL='1'
$env:CORS_ORIGINS='https://gymnasiearbete.local,http://localhost'
& 'C:\Users\William\mambaforge\python.exe' -m uvicorn api.app:app --host 127.0.0.1 --port 8000
```

Varfor: PHP-anrop i projektet vidarebefordras till FastAPI for textgenerering.

## 4. Verifiering (bevis for rapport)

## 4.1 Redirect-test

Oppna:
- `http://gymnasiearbete.local/GYMNASIEARBETE1/web/`

Forvantat:
- Du hamnar pa `https://gymnasiearbete.local/GYMNASIEARBETE1/web/`

Varfor: bevisar att osakra anrop tvingas over till krypterad kanal.

## 4.2 Certifikat-test

I webblasaren, klicka pa hanglaset och visa certifikat.

Forvantat:
- certifikatet ar installerat och servern anvander TLS
- ev varning om sjalvsignerat certifikat ar normal i lokal miljo

## 4.3 Cookie-test

I DevTools -> Application/Storage -> Cookies, kontrollera sessioncookie (`PHPSESSID`).

Forvantat:
- `HttpOnly` = true
- `Secure` = true nar sidan ar laddad over HTTPS

Varfor: cookie ar svarare att lasa via JS (XSS) och skickas inte over okrypterad trafik.

## 4.4 API-test

```powershell
curl http://127.0.0.1:8000/
```

Forvantat: JSON-health-response.

Testa sedan AI-generering fran webbgranssnittet.

## 5. Felsokning

1. Apache startar inte pa 443:
- kontrollera om annan process redan anvander 443
- kontrollera certifikatvagar i `httpd-vhosts.conf`

2. Redirect fungerar inte:
- sakerstall `rewrite_module` ar aktiv
- sakerstall `AllowOverride All` ar satt i VirtualHost

3. SSL-varning i browser:
- normalt for self-signed cert
- forklar i rapporten skillnad mellan self-signed och CA-signerat cert

4. AI fungerar inte:
- kontrollera att uvicorn-processen kor pa 127.0.0.1:8000
- kontrollera env variabler i terminalen dar du startar API

## 6. MySQL-SSL (A-niva resonemang + praktisk del)

I lokal XAMPP anvands ofta okrypterad lokal socket/TCP mellan PHP och MySQL. For A-niva ska du visa att du forstar hur DB-trafik ocksa kan krypteras.

Praktiskt i redovisning:
1. Beskriv att HTTPS skyddar klient <-> webbserver.
2. Beskriv separat att MySQL-SSL skyddar app <-> databas (framfor allt nar DB ligger pa annan host).
3. Visa hur PDO kan ges SSL-attribut i produktion, till exempel `PDO::MYSQL_ATTR_SSL_CA`.

Exempel (produktionsmonster):

```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca.pem',
];
```

Varfor: du visar nyanserad forstaelse for att skyddet kan behovas i flera hopp, inte bara i webblasaren.

## 7. Teori att kunna forklara for lararen

1. Kryptering i transit:
TLS krypterar datapaket sa att avlyssning inte ger lasbar information.

2. Certifikatets roll:
- Self-signed: du signerar sjalv, bra for lokal test, inte publikt fortroende.
- CA-signerat: tredjepart intygar identitet (t ex Let's Encrypt), webblasare litar pa kedjan.

3. OSI-koppling:
TLS kopplas ofta till session/presentation-lager men skyddar applikationsdata (HTTP -> HTTPS).

4. MITM-skydd:
HTTPS minskar risken for att angripare manipulerar eller laser trafik mellan klient och server.

5. VPN-jamforelse:
Bade VPN och HTTPS anvander kryptografiska principer, men HTTPS skyddar webbtrafik per anslutning medan VPN skyddar hela tunneln mellan klient och natverk.

## 8. Rekommenderat redovisningsbevis

1. Skarmbild pa HTTP -> HTTPS redirect.
2. Skarmbild pa certifikatdetaljer (issuer, subject, giltighet).
3. Skarmbild pa cookieflaggor (`Secure`, `HttpOnly`).
4. Skarmbild pa fungerande inloggning + AI-generering over HTTPS.
5. Kort felanalys: self-signed warning, orsak och riktig produktionslosning.
