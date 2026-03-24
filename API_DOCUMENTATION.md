# API-DOKUMENTATION

## OVERVIEW

Denna applikation exponerar två API:er:

1. **PHP REST API** - För användarhantering, autentisering, historik (på `localhost/GYMNASIEARBETE1/web/`)
2. **Python FastAPI** - För AI-textgenerering (på `http://127.0.0.1:8000`)

---

## PHP REST API (Webbserverprogrammering)

### Basmall

```
Base URL: http://localhost/GYMNASIEARBETE1/web/
Method: POST/GET
Headers: 
    Content-Type: application/x-www-form-urlencoded (för POST)
    Cookie: PHPSESSID (automatisk efter login)
```

---

### 1. AUTENTISERING

#### 1.1 Registrera Ny Användare
**Endpoint:** `POST /register.php`

**Request Parameters:**
```php
email       string   Användarens email (valideras med filter_var)
username    string   Användarnamn (unikt)
password    string   Lösen (valideras för styrka - min 8 tecken, stor, liten, siffra, specialtecken)
csrf_token  string   CSRF-skyddstoken (från formuläret)
```

**Response:**
```php
// Success (HTTP 200):
Redirect till dashboard.php + Flash message: "Registrering lyckades!"

// Error (HTTP 200, svagt):
Visar fel på sidan: "Email redan registrerad" / "Lösen för svagt"
```

**Säkerhetskontroller:**
- ❌ Kollar om email redan existerar (UNIQUE constraint)
- ❌ Validerar email-format med filter_var FILTER_VALIDATE_EMAIL
- ❌ Validerar lösen-styrka: 8+ tecken, stor, liten, siffra, special
- ❌ Hashar lösen med password_hash() (bcrypt)
- ❌ Verifierar CSRF-token innan update

---

#### 1.2 Logga In
**Endpoint:** `POST /login.php`

**Request Parameters:**
```php
email       string   Email
password    string   Lösen
csrf_token  string   CSRF-skyddstoken
```

**Response:**
```php
// Success:
HTTP 302 Redirect till dashboard.php
$_SESSION['user_id'] = user_id
$_SESSION['user_role'] = 'admin' | 'user'
$_SESSION['user_email'] = email

// Failure:
Visar felmeddelande på login-sidan
```

**Säkerhetskontroller:**
- ❌ Använder password_verify() för säker lösenordsverifiering
- ❌ Kollar om email+lösen matchar
- ❌ Lagrar INGET lösen i sessionen (endast user_id)
- ❌ last_login uppdateras vid framgångsrik inloggning

---

#### 1.3 Logga Ut
**Endpoint:** `GET/POST /logout.php`

**Request Parameters:**
(Ingen)

**Response:**
```php
HTTP 302 Redirect till index.php
$_SESSION raderas helt
```

---

### 2. ANVÄNDARHANTERING

#### 2.1 Visa Profil
**Endpoint:** `GET /profile.php`

**Request Parameters:**
(Ingen, användar-ID hämtas från $_SESSION)

**Response:**
```html
<!-- HTML-sidan visar: -->
Email: user@example.com
Username: testuser
Registrerad: 2025-03-18 10:30:00
Senaste inloggning: 2025-03-19 15:45:00
```

**Säkerhetskontroller:**
- ❌ requireLogin() kollar att användare är inloggad
- ❌ Hämtar data från sessionen, INTE från URL-parametrar

---

#### 2.2 Uppdatera Profil
**Endpoint:** `POST /profile.php` (samma fil)

**Request Parameters:**
```php
username    string   Nytt användarnamn (optional)
new_password string  Nytt lösen (optional, valideras)
csrf_token  string   CSRF-skyddstoken
```

**Response:**
```php
// Success:
Flash message: "Profilen uppdaterad!"
Redirect till profile.php

// Failure:
Felmeddelande visas
```

**Säkerhetskontroller:**
- ❌ Verifierar CSRF-token
- ❌ Validerar nytt lösen med checkPasswordStrength()
- ❌ Hackar lösen med password_hash() före lagring
- ❌ Använder prepared statement för uppdatering

---

### 3. AI-TEXTHISTORIK

#### 3.1 Visa Historik
**Endpoint:** `GET /history.php`

**Request Parameters:**
(Ingen)

**Response:**
```html
<!-- HTML-tabell med: -->
ID  | Prompt Text | Generated Text | Created At | Action
--- | ----------- | --------------- | ---------- | ------
1   | "Hello"     | "Hello world..." | 2025-03-18 | Delete
2   | "The..."    | "The snow was..." | 2025-03-18 | Delete
```

**Säkerhetskontroller:**
- ❌ requireLogin() - bara inloggade kan se sin historik
- ❌ `user_id = $_SESSION['user_id']` - visar ENDAST egen historik
- ❌ Hämtar endast rader där ai_texts.user_id = aktuell usuario

---

#### 3.2 Radera Historikpost
**Endpoint:** `POST /delete_text.php` (Ny fil att skapa)

**Request Parameters:**
```php
text_id     integer  ID för ai_texts-post att radera
csrf_token  string   CSRF-skyddstoken
```

**Response:**
```php
// Success:
HTTP 204 No Content
ai_texts.id = text_id raderas

// Failure:
HTTP 403 Forbidden (om du försöker radera andras data)
HTTP 404 Not Found (om ID inte existerar)
```

**Säkerhetskontroller:**
- ❌ requireLogin() - bara auth kan radera
- ❌ Kollar att text_id tillhör inloggad användare
  ```php
  $stmt = $pdo->prepare("DELETE FROM ai_texts WHERE id = ? AND user_id = ?");
  $stmt->execute([$_POST['text_id'], $_SESSION['user_id']]);
  ```
- ❌ Förhindrar att en användare raderar andras historik
- ❌ Verifierar CSRF-token

---

#### 3.3 Uppdatera Historikpost (Optional)
**Endpoint:** `POST /update_text.php` (Ny fil att skapa)

**Request Parameters:**
```php
text_id        integer  ID för post att uppdatera
input_text     string   Reviderad prompt
generated_text string   Reviderad genererad text
csrf_token     string   CSRF-skyddstoken
```

**Response:**
```php
// Success:
Flash message: "Post uppdaterad"
Redirect till history.php

// Failure:
HTTP 403/404 + error message
```

---

### 4. GENERELLA SVAR-KODER

| Kod | Betydelse | Situation |
|-----|-----------|-----------|
| 200 | OK | Framgångsrik GET/POST |
| 302 | Redirect | Omdirigering efter POST (t.ex. login) |
| 403 | Forbidden | Du försöker göra något du inte får (t.ex. radera andras data) |
| 404 | Not Found | Resursen existerar inte (t.ex. text_id) |
| 500 | Server Error | Databasfel, okänt fel |

---

## Python FastAPI (Webbtjänster)

### Basmall

```
Base URL: http://127.0.0.1:8000
Content-Type: application/json
CORS: Aktiverad för http://localhost:5500 och *
```

---

### 1. GENERERA TEXT

**Endpoint:** `POST /generate`

**Request Body (JSON):**
```json
{
    "prompt": "To be or not to be",
    "length": 200
}
```

**Fält-beskrivning:**

| Fält | Typ | Krav | Beskrivning |
|------|-----|------|-------------|
| `prompt` | string | Min 1, Max 2000 tecken | Starttekst för modellen |
| `length` | integer | Min 1, Max 1000 | Antal tecken att generera |

**Response (Success - HTTP 200):**
```json
{
    "text": "To be or not to be, that is the question.\nWhether 'tis nobler in the mind..."
}
```

Längden av `text` = len(prompt) + length

**Response (Error):**

```json
{
    "detail": "Prompt kan inte vara tom"
}
```

| HTTP | Error | Orsak |
|------|-------|-------|
| 400 | "Prompt kan inte vara tom" | Du skickade tom prompt |
| 400 | "Query parameters failed validation" | prompt eller length validerades inte |
| 503 | "Modell ej laddad" | AI-modellen kunde inte laddas (kör `python app.py train` först) |
| 500 | "Genereringsfel: ..." | Okänt problem under generering |

---

### 2. HEALTHCHECK

**Endpoint:** `GET /`

**Request:**
(Ingen body)

**Response (Success):**
```json
{
    "status": "ok",
    "message": "POST /generate med JSON: {'prompt': 'text', 'length': 100}"
}
```

**Response (Error - Modell inte laddad):**
```json
{
    "status": "error",
    "message": "POST /generate med JSON: {'prompt': 'text', 'length': 100}"
}
```

---

## CURL-EXEMPEL

### Registrera användare (PHP)
```bash
curl -X POST http://localhost/GYMNASIEARBETE1/web/register.php \
  -d "email=test@example.com&username=testuser&password=Test123!@&csrf_token=abc123"
```

### Logga in (PHP)
```bash
curl -X POST http://localhost/GYMNASIEARBETE1/web/login.php \
  -d "email=test@example.com&password=Test123!@&csrf_token=abc123" \
  -c cookies.txt
```

### Generera text (Python FastAPI)
```bash
curl -X POST http://127.0.0.1:8000/generate \
  -H "Content-Type: application/json" \
  -d '{"prompt": "To be or not", "length": 100}'
```

### Radera historik (PHP) - Kräver Authentication
```bash
curl -X POST http://localhost/GYMNASIEARBETE1/web/delete_text.php \
  -d "text_id=1&csrf_token=abc123" \
  -b cookies.txt
```

---

## SÄKERHET SAMMANFATTNING

### PHP API
- ✅ **SQL-Injection**: PDO prepared statements
- ✅ **XSS**: htmlspecialchars() på all användaroutput
- ✅ **CSRF**: Session-baserade CSRF-tokens
- ✅ **Auth**: Session-baserad ($\_SESSION['user_id'])
- ✅ **Password**: bcrypt hashing, starkkravsterminering
- ✅ **Cookie**: httponly flag förhindrar JS från att läsa session-cookie

### Python FastAPI  
- ✅ **Input Validation**: Pydantic BaseModel validering
- ✅ **CORS**: Konfigurerad för localhost (förhindrar requests från okänd origin)
- ✅ **Error Handling**: Generiska error-meddelanden (inte stacktraces till client)
- ✅ **Length Limiting**: Max 1000 tokens för att förhindra DoS

---

## IMPLEMENTATION-STATUS

| Feature | Status | Fil |
|---------|--------|-----|
| Registrering | ✅ Implementerad | `register.php` |
| Inloggning | ✅ Implementerad | `login.php` |
| Utloggning | ✅ Implementerad | `logout.php` |
| Profilvisning | ✅ Implementerad | `profile.php` |
| Profiluppdatering | ✅ Implementerad | `profile.php` |
| Historikvisning | ✅ Implementerad | `history.php` |
| Historik Delete | ⚠️ Behöver implementeras | `delete_text.php` (NY) |
| Historik Update | ⚠️ Behöver implementeras | `update_text.php` (NY) |
| AI-generering | ✅ Implementerad | `app.py` (FastAPI) |
| Healthcheck | ✅ Implementerad | `app.py` |

---

## TEST-GUIDE

Se `TESTING.md` för omfattande test-instruktioner och test-cases.
