# Railway Deployment - Steg för steg

Railway ger dig **$5 månatlig credit** helt gratis - räcker för denna app.

## Quiz innan du börjar ✅
- [ ] GitHub-konto skapat?
- [ ] Repository pushad med alla filer?
- [ ] Supabase-databas upprättad och schema importerad?
- [ ] Hugging Face-checkpoint uppladdat?

---

## Steg 1: Förbered GitHub
```bash
cd G:\Min enhet\Allt\Webserverprogrameringx\GYMNASIEARBETE1
git init
git add .
git commit -m "Initial gymnasiearbete deployment"
git remote add origin https://github.com/DITTNAMN/gymnasiearbete.git
git branch -M main
git push -u origin main
```

## Steg 2: Förbered Supabase PostgreSQL

1. Gå till https://supabase.com
2. Skapa gratis konto (registrera med GitHub)
3. Nytt project: 
   - Namn: `gymnasiearbete-db`
   - Region: `Europe (Stockholm)` eller närmaste
   - Password: Spara denna! 🔐
4. Vänta på initalisering (30 sekunder)
5. I Dashboard → SQL Editor → **Kör this:**
   ```sql
   -- Import database/schema.sql innehål här
   -- (Kopiera hela innehållet från din database/schema.sql fil)
   ```
6. Kopiera **Connection String** från Settings → Database → Connection Pooling:
   - Välj `URI`
   - Kopiera hela strängen (ser ut som: `postgresql://postgres:PASSWD@db.xxxxx.supabase.co:6543/postgres`)

## Steg 3: Ladda Checkpoint på Hugging Face

1. Gå till https://huggingface.co
2. Registrera (gratis)
3. Klick på ditt profilbild → **New Model**
4. Modellnamn: `gymnasiearbete-checkpoint`
5. License: MIT
6. Create repository
7. I Files-sektionen → **Upload file**
   - Välj `ai/model_checkpoint.pt` från din dator
   - Vänta på upload (stora filen ~50MB)
8. Efter upload → Klick på filen → **Copy to clipboard** (Download URL)
   - URL ser ut som: `https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/blob/main/model_checkpoint.pt`
   - **Ändra** `blob/main` → `resolve/main` så den blir:
   - `https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt`

## Steg 4: Deploy på Railway

1. Gå till https://railway.app
2. Logga in med GitHub
3. Klick **New Project**
4. Välj **Deploy from GitHub repo**
5. Authorize Railway och välj din gymnasiearbete-repo
6. Railway auto-detectar:
   - `Dockerfile` i `api/` → skapar service "api"
   - `Dockerfile` i `web/` → skapar service "web"

## Steg 5: Sätt Environment Variables

I Railway Dashboard för varje service:

### Service: **web**
```
APP_ENV=production
BASE_URL=https://<din-web-url-från-railway>.railway.app
DB_HOST=db.xxxxx.supabase.co
DB_NAME=postgres
DB_USER=postgres
DB_PASS=<ditt-supabase-password>
DB_PORT=6543
DB_TYPE=postgres
AI_API_URL=https://<din-api-url-från-railway>.railway.app
```

### Service: **api**
```
MODEL_CHECKPOINT_URL=https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt
CHECKPOINT_PATH=/tmp/model_checkpoint.pt
CORS_ORIGINS=https://<din-web-url-från-railway>.railway.app
```

**Var hittar jag URL:erna från Railway?**
- I Railway Dashboard → klick på respektive service → **Settings** → **Environment** → se `RAILWAY_PUBLIC_DOMAIN`
- Eller vänta på att services deployas och se länkarna i Dashboard

## Steg 6: Testa Deployment

1. Wai för både services att visa ✅ (grön status)
2. Öppna din web-URL i browser
3. Testa login (skapa konto)
4. Testa **Generate text**-funktionen
5. Checka logs om något fel:
   - Railway Dashboard → Service → **Logs**

---

## Checklistor för Framgång ✅

- [ ] GitHub push är klar
- [ ] Supabase PostgreSQL är upprättad
- [ ] Database-schema är importerad i Supabase
- [ ] Checkpoint är uppladdat på Hugging Face
- [ ] Railway services deployas (båda grön)
- [ ] Alla env-variabler är korrekta
- [ ] Web-sidan laddar utan fel
- [ ] Login fungerar
- [ ] Text-generation skapar text (kolla API_URL i generate.php)

---

## Kostnad per månad
- Railway: -0 kr (Du har $5 credit/mån, denna app använder ~$1)
- Supabase: 0 kr (free tier)
- Hugging Face: 0 kr (free tier)
- **Total: 0 kr** ✅

---

## Troubleshooting

**"Database connection failed"**
- Checka DB_HOST, DB_USER, DB_PASS är exakt korrekta
- Checka att Supabase-projekt är aktivt
- Testa anslutning från Supabase Dashboard → SQL Editor (om det går in där, går det in från Railway)

**"AI API returned error"**
- Checka att AI_API_URL är exakt korrekt
- Checka CORS_ORIGINS matching URL-en
- Se API-service logs i Railway Dashboard

**"Text generation tar lång tid"**
- Första förfrågan laddar checkpoint från Hugging Face (~30 sekunder)
- Efterföljande förfrågningar är snabbare
- Om det tar >2 minuter, checka API-logs

**"Services visar error status"**
- Gå till Logs i Railway Dashboard
- Se vad som är problem (oft är det env-variabler)
