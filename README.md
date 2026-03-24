## Gymnasiearbete – NeuralText AI

Detta projekt är en webbapplikation byggd för gymnasiearbete. Den innehåller:

- en PHP-baserad webbdel med inloggning och sidor för användare
- en MySQL-databas
- en enkel AI-del i Python för textgenerering

**För A-betyg: Se [API_DOCUMENTATION.md](API_DOCUMENTATION.md) och [TESTING.md](TESTING.md) för fullständig dokumentation av API, säkerhet och tester.**

## Funktioner

- startsida med modern UI
- registrering och inloggning
- dashboard/profil/historik
- **CRUD-operationer för historik**: Läs, skapa, uppdatera, radera genererade texter
- AI-demo på startsidan
- tekniksektion och informationssidor

## Teknikstack

- Frontend: HTML, CSS, JavaScript
- Backend (webb): PHP (med PDO prepared statements för SQL-injection-skydd)
- Databas: MySQL
- AI/API: Python (FastAPI)
- Säkerhet: CSRF-tokens, bcrypt-hashing, httponly cookies, XSS-skydd

## Projektstruktur

```
GYMNASIEARBETE1/
├── README.md                              # Denna fil
├── API_DOCUMENTATION.md                   # ⭐ API-spec för alla endpoints (A-nivå krav)
├── TESTING.md                             # ⭐ Omfattande test-dokumentation (A-nivå krav)
├── web/                                   # Webbapplikation
│   ├── index.php                          # Startsida med AI-demo
│   ├── register.php                       # Registreringsformulär
│   ├── login.php                          # Inloggningsformulär
│   ├── dashboard.php                      # Användar-dashboard
│   ├── profile.php                        # Profilsida + uppdatering
│   ├── history.php                        # Historik över genererad text
│   ├── delete_text.php                    # ⭐ CRUD: Radera historikpost (ny)
│   ├── update_text.php                    # ⭐ CRUD: Uppdatera historikpost (ny)
│   ├── logout.php                         # Loggning
│   ├── config.php                         # ⭐ Databaskonfiguration + säkerhetsfunktioner (UTFÖRLIGT KOMMENTERAD)
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css                  # Stilar för webbsidor
│   │   ├── js/
│   │   │   └── main.js                    # JavaScript-funktioner
│   │   └── Images/
│   ├── includes/
│   ├── vendor/                            # PHP-beroenden (om använt)
│   └── test_csrf.php                      # CSRF-testnings-script
├── database/
│   └── schema.sql                         # Databasstruktur (CREATE TABLE)
├── ai/                                    # Python AI-modul
│   ├── app.py                             # ⭐ BigramLanguageModel (UTFÖRLIGT KOMMENTERAD)
│   ├── requirements.txt                   # Python-beroenden
│   ├── models/
│   │   └── model_checkpoint.pth           # Tränad modell (lagras här)
│   ├── data/
│   │   └── combined_training.txt          # Träningsdata
│   └── scripts/
├── api/                                   # FastAPI endpoint
│   └── app.py                             # REST-API (alternativ till ai/app.py)
└── assets/                                # Övriga assets
    ├── css/
    ├── js/
    └── Images/
```

## Kom igång (XAMPP)

### 1) Starta tjänster

I XAMPP Control Panel, starta:

- Apache
- MySQL

### 2) Placera projektet i htdocs

Se till att projektet nås via:

`C:\xampp\htdocs\GYMNASIEARBETE1`

(Alternativt en junction/symlink till din arbetsmapp.)

### 3) Importera databas

Öppna `http://localhost/phpmyadmin` och importera:

- [database/schema.sql](database/schema.sql)

Det skapar databasen `ai_project_db` med tabeller.

### 4) Kontrollera databasinställningar

Kolla uppgifter i [web/config.php](web/config.php):

- host
- dbname
- username
- password

*(config.php är också dokumenterad med djupa kommentarer om VARFÖR varje säkerhetsinställning finns)*

## Kör projektet

Öppna i webbläsare:

`http://localhost/GYMNASIEARBETE1/web/`

## AI-del (valfritt under körning)

Om du vill använda AI-generering lokalt:

1. Installera Python-beroenden från [ai/requirements.txt](ai/requirements.txt)
2. Starta AI-servern: `python ai/app.py serve`
3. Kontrollera att endpoint är `http://127.0.0.1:8000` (matchas av [web/index.php](web/index.php))

---

## 🚀 Deployment Online (HELT GRATIS)

För att köra projektet online utan kostnad, se:

- **[QUICK_DEPLOY.md](QUICK_DEPLOY.md)** ← **BÖRJA HÄR** (15 minuter, endast viktiga steg)
- **[DEPLOY_ONLINE.md](DEPLOY_ONLINE.md)** ← Detaljerad steg-för-steg guide med troubleshooting
  - Railway ger $5/mån credit (räcker för denna app)
  - Supabase PostgreSQL (gratis tier, 500MB)
  - Hugging Face för checkpoint-lagring (gratis)

**Kostnad:** $0-5/månad ✅

---

## A-BETYG DOKUMENTATION 🎓

För att uppfylla A-nivå kraven för **Webbtjänster 2** och **Webbserverprogrammering 2** är följande implementerat:

### 1. ✅ OMFATTANDE TESTER
Se [TESTING.md](TESTING.md) för:
- **17 automatiserade säkerhetstester** (SQL-injection, XSS, CSRF, autentisering, validering)
- **4 manuella penetrations-tester** (session hijacking, cookie manipulation, XSS-injection, IDOR)
- **3 prestandatester** (databas, AI-generering, concurrent requests)
- Alla tester dokumenterade med procedur och resultat

### 2. ✅ KODNINGSSTANDARD & UTFÖRLIG KOMMENTERING
- [web/config.php](web/config.php) - UTFÖRLIGT kommenterad med:
  - VARFÖR httponly-flag på cookies (förhindrar XSS att stjäla sessionscookie)
  - VARFÖR prepared statements (förhindrar SQL-injection)
  - VARFÖR bcrypt för lösen (irreversibel hashing)
  - Alla säkerhetskontrroller förklarade
  
- [ai/app.py](ai/app.py) - UTFÖRLIGT kommenterad med:
  - VARFÖR LEARNING_RATE=1e-3 (empirisk standard för AdamW)
  - VARFÖR BATCH_SIZE=128 (balans för stabilitet och hastighet)
  - VARFÖR EMBEDDING_DIM=256 (kapacitet vs träningshastighet)
  - VARFÖR multinomial sampling (varietet i generering)
  - Alla hyperparametrar förklarade

- [web/delete_text.php](web/delete_text.php) och [web/update_text.php](web/update_text.php)
  - VARFÖR ownership-check (`WHERE id = ? AND user_id = ?`) förhindrar IDOR

### 3. ✅ TEKNISK API-DOKUMENTATION
Se [API_DOCUMENTATION.md](API_DOCUMENTATION.md) för fullständig specifikation:
- **Alla PHP-endpoints listade** med metod, parametrar, returvärden
- **Alla Python FastAPI-endpoints** med JSON-exemplar
- **HTTP-statuskoder** dokumenterade
- **Säkerhetsmekanismer** för varje endpoint
- **CURL-exempel** för testning

### 4. ✅ FULLSTÄNDIG CRUD
Är nu implementerad för `ai_texts`-tabellen:
- **CREATE**: Generering via [web/index.php](web/index.php) (redan fanns)
- **READ**: Historikvisning via [web/history.php](web/history.php) (redan fanns)
- **UPDATE**: ⭐ Ny fil [web/update_text.php](web/update_text.php) - Uppdatera prompt/resultat
- **DELETE**: ⭐ Ny fil [web/delete_text.php](web/delete_text.php) - Radera egen historik

Båda filer har:
- Fullständig CSRF-skydd
- Ownership-validering (kan INTE radera/uppdatera andras data)
- Prepared statements (SQL-injection-skydd)
- Input-validering och längd-kontroller

## Filer som normalt inte behöver lämnas in

- `.venv/`
- `.vscode/`
- `__pycache__/`
- loggfiler (`training.log`, `error.log`, etc.)
- lokala hemligheter (`.env.local`)
- tillfälliga backup/test-filer (`*.bak`, debug/test-filer som inte används i slutversionen)

## Säkerhet

Lägg aldrig in riktiga lösenord eller API-nycklar i kod/README.

## Författare

Gymnasiearbete av William.
