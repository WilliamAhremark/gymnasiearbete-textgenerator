# 🚀 DEPLOY ONLINE - Komplett Guide för TextGenerator

**Mål**: Få ditt gymnasiearbete live på internet (kostnadsfritt/5 USD/månad)

**Tid**: ~30 minuter om allt går bra

---

## 📋 Checklist - Innan du börjar

- [ ] GitHub-konto skapat (https://github.com/signup)
- [ ] Railway-konto skapat (https://railway.app - logga in med GitHub)
- [ ] Supabase-konto skapat (https://supabase.com)
- [ ] Hugging Face-konto skapat (https://huggingface.co/signup)
- [ ] Git installerad på din dator (`git --version` i CMD/PowerShell)

✅ Om du har allt detta, gå till **Steg 1**.

---

## 🔐 STEG 1: Förbered GitHub Repository

Du behöver pushe ditt projekt till GitHub så att Railway kan auto-deploy från det.

### 1a) Öppna PowerShell i projektmappen

```powershell
# Navigera till ditt projekt
cd "G:\Min enhet\Allt\Webserverprogrameringx\GYMNASIEARBETE1"

# Kontrollera att Git är installerad
git --version
```

### 1b) Initiera Git och Pushe till GitHub

```powershell
# Initiera lokalt git-repo
git init

# Lägg till alla filer
git add .

# Gör en commit
git commit -m "Initial gymnasiearbete deployment - TextGenerator"

# Ändra branch-namn till 'main' (GitHub-standard)
git branch -M main

# Länka till GitHub repo
git remote add origin https://github.com/DITTGITHUBNAMN/gymnasiearbete-textgenerator.git

# Pushe till GitHub
git push -u origin main
```

**Ersätt `DITTGITHUBNAMN` med ditt GitHub-användarnamn!**

**Om du får error om autentisering:**
- Skapa ett Personal Access Token på https://github.com/settings/tokens
- Använd token som lösenord när git frågar

### 1c) Verifiera på GitHub

Gå till https://github.com/DITTGITHUBNAMN/gymnasiearbete-textgenerator och se att dina filer finns där!

---

## 📊 STEG 2: Sätt upp Supabase PostgreSQL Database

Railway kan köra databasen, men det är enklare och gratis att använda Supabase.

### 2a) Skapa Supabase Project

1. Gå till https://supabase.com och logga in
2. Klick **Create a new project**
3. Fyll i:
   - **Name**: `gymnasiearbete-db`
   - **Database Password**: Spara denna!! 🔐 (nödvändig senare)
   - **Region**: `Stockholm` (eller närmaste)
4. Klick **Create new project** och vänta 30 sekunder

### 2b) Importera Database-Schema

1. I Supabase Dashboard → **SQL Editor** (vänster meny)
2. Klick **New Query**
3. Kopiera allt från `database/schema.sql` i ditt projekt
4. Klistra in i Supabase SQL Editor
5. Klick **Run** och vänta

**Kontrollera att tabellerna skapades:**
- Gå till **Explore** → Du bör se: `users`, `ai_texts`, `sessions`

### 2c) Kopiera Connection String

1. I Supabase → **Settings** (höger meny) → **Database** → **Connection Pooling**
2. Se till att du är på **URI** och **Session mode**
3. Du ser något som:
   ```
   postgresql://postgres.xxxxx:[PASSWORD]@db.xxxxx.supabase.co:6543/postgres
   ```
4. **Kopiera denna sträng** och spara den i en textfil (du behöver den i Steg 4)

⚠️ **Säkerhet**: Denna connection string innehåller ditt lösenord - **DELA ALDRIG DEN PUBLIKT**

---

## 🤗 STEG 3: Ladda Checkpoint på Hugging Face

AI-modellen behöver en hemlig plats att ladda ifrån.

### 3a) Skapa Model Repository på Hugging Face

1. Gå till https://huggingface.co/new (eller klick på din profil → **New Model**)
2. Fyll i:
   - **Model ID**: `gymnasiearbete-checkpoint`
   - **License**: `mit`
   - **Visibility**: `Public`
3. Klick **Create Model**

### 3b) Ladda up Checkpoint-Fil

1. Du bör vara på din nya modellsida
2. Klick **Add file** → **Upload files**
3. Välj filen: `ai/model_checkpoint.pt` (eller `.pth` - samma sak)
4. Vänta på upload (kan ta 1-2 minuter)

### 3c) Kopiera Download URL

1. Efter upload, klick på filen i repositoryt
2. Högerklick på **Download** → **Copy link**
3. Du får något som:
   ```
   https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt
   ```
4. **Spara denna URL** för Steg 4

---

## 🚂 STEG 4: Deploy på Railway

Railway är som "GitHub för servrar" - den ser din GitHub repo och deployer automatiskt.

### 4a) Logga in på Railway

1. Gå till https://railway.app
2. Klick **Login** → **GitHub**
3. Authorize Railway med ditt GitHub-konto

### 4b) Skapa Nytt Projekt

1. I Railway Dashboard → Klick **New Project**
2. Välj **Deploy from GitHub repo**
3. **Search for a repository** → sök efter `gymnasiearbete-textgenerator`
4. Klick på din repo för att välja den

**Railway börjar nu att analysera ditt projekt...**

Vänta tills det är klart. Du bör se två services skapas:
- `api` (från `api/Dockerfile`)
- `web` (från `web/Dockerfile`)

### 4c) Sätt Environment Variables för `web`-servern

1. I Railway Dashboard → Klick på **web**-servern
2. Gå till **Variables**
3. Lägg till dessa:

```
APP_ENV = production
BASE_URL = /
DB_HOST = db.xxxxx.supabase.co
DB_NAME = postgres
DB_USER = postgres
DB_PASS = [supabase-lösenord från Steg 2]
DB_PORT = 6543
DB_TYPE = postgres
AI_API_URL = https://<API_RAILWAY_URL>/generate
```

**Var hittar jag `<API_RAILWAY_URL>`?**
- Klick på **api**-servern → **Settings** → Se `RAILWAY_PUBLIC_DOMAIN`
- Det ser ut som: `api-gymnasiearbete-xxxxx.railway.app`
- Så `AI_API_URL` blir: `https://api-gymnasiearbete-xxxxx.railway.app`

### 4d) Sätt Environment Variables för `api`-servern

1. I Railway Dashboard → Klick på **api**-servern
2. Gå till **Variables**
3. Lägg till dessa:

```
MODEL_CHECKPOINT_URL = https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt
CHECKPOINT_PATH = /app/ai/model_checkpoint.pt
CORS_ORIGINS = https://<WEB_RAILWAY_URL>
```

**Var hittar jag `<WEB_RAILWAY_URL>`?**
- Klick på **web**-servern → **Settings** → Se `RAILWAY_PUBLIC_DOMAIN`
- Det ser ut som: `web-gymnasiearbete-xxxxx.railway.app`
- Så `CORS_ORIGINS` blir: `https://web-gymnasiearbete-xxxxx.railway.app`

---

## ✅ STEG 5: Verifiera Deployment

### 5a) Vänta på Deployment

1. I Railway Dashboard, se status för båda services
2. Båda bör visa **✅ (grön)** status
3. Vänta tills båda services är "Running"

**Hur länge tar det?**
- Första gången: 5-10 minuter
- Nästa gånger: 2-3 minuter

### 5b) Testa Webbsidan

1. Klick på **web**-servern i Railway
2. Under **Deployments**, klick på **Open in browser**
3. Du bör se **TextGenerator startsida**!

### 5c) Testa API

1. Öppna din browser och gå till:
   ```
   https://<API_RAILWAY_URL>/docs
   ```
2. Du bör se **Swagger UI** med alla API-endpoints

### 5d) Testa Inloggning

1. Gå till din webbsida
2. Klick **Register** och skapa en test-konto
3. Logga in
4. Generera någon text och se om den sparas i historiken

---

## 🎯 STEG 6: Anpassningar och Best Practices

### 6a) Sätt upp Custom Domain (Valfritt)

Om du vill ha ditt eget domännamn (`min-app.com` istället för `web-gymnasiearbete-xxxxx.railway.app`):

1. Köp ett domännamn (t.ex. `namecheap.com`, `godaddy.com`)
2. I Railway → **web**-servern → **Settings** → **Custom Domain**
3. Lägg till ditt domännamn
4. Uppdatera DNS-inställningar enligt Railway-instruktioner

### 6b) Monitorering & Debug

- **Logs**: I Railway → servern → **Logs** för att se fel
- **Health Check**: Railway checkar `/index.php` automatiskt var 30 sekunder

### 6c) Backup av Databasen

Supabase automatisk backupar databasen varje dag. För manuell backup:

1. I Supabase → **Database** → **Backups**
2. Klick **Start backup** för manual backup

---

## 🐛 Vanliga Problem & Lösningar

### Problem: "502 Bad Gateway" på webbsidan
**Lösung**: `AI_API_URL` är felaktig eller API inte uppe än
- Kontrollera att API-servern är grön (✅) i Railway
- Verifiera att `AI_API_URL` börjar med `https://` (inte `http://`)

### Problem: "Database connection error"
**Lösung**: `DB_HOST`, `DB_USER`, `DB_PASS` är felaktig
- Dubbelkolla Supabase connection string
- Se till att du använde `Connection Pooling` URI (port 6543), inte `Direct connection`

### Problem: "CORS error" när du försöker generera text
**Lösung**: `CORS_ORIGINS` på API är felaktig
- Se till att det är ditt web-domännamn
- Måste börja med `https://`

### Problem: Deployment "Failed"
**Lösung**: Kolla Railway Logs
1. Click på servern → **Logs**
2. Scrolla upp för att se error-meddelandet
3. Läsa error och googla det

### Problem: "Model checkpoint not found"
**Lösung**: Hugging Face URL är felaktig
- Test URL i browser - bör kunna ladda filen
- Se till att du använde `resolve/main` (inte `blob/main`)

---

## 📞 Support & Dokumentation

- **Railway Docs**: https://docs.railway.app
- **Supabase Docs**: https://supabase.com/docs
- **Hugging Face Docs**: https://huggingface.co/docs

---

## ✨ Grattis! 🎉

Du har nu:
- ✅ Backend API online
- ✅ Frontend webbapplikation online
- ✅ Databas online
- ✅ AI-modell ladda från cloud

**Total kostnad**: $0 första månaden (Railway ger $5 credit gratis), sedan ~$5/månad maximum.

**Nästa steg**:
1. Testa applikationen grundligt
2. Uppdatera `BASE_URL` i webbkoden om du använde custom domain
3. Dokumentera deployment i ditt gymnasiearbete
4. Säkerhetskopiera din databas regelbundet

---

📝 **Notering**: Om något går fel, kolla `RAILWAY_LOGS` och `RAILWAY_DEBUG` environment variables för att få mer information.
