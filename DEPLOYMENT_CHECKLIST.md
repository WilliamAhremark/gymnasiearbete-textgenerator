# ✅ Deployment Checklist - TextGenerator Online

Använd denna checklista för att spåra dina framsteg under deployment-processen.

---

## 🔐 Steg 1: Förbered GitHub

- [ ] GitHub-konto skapat (https://github.com/signup)
- [ ] Lokal Git initierad: `git init`
- [ ] Alla filer adderade: `git add .`
- [ ] Commit gjord: `git commit -m \"Initial deployment\"`
- [ ] GitHub-repo skapad (https://github.com/new)
- [ ] Remote URL adderad: `git remote add origin https://github.com/DITTNAMN/gymnasiearbete-textgenerator.git`
- [ ] Pushed till GitHub: `git push -u origin main`
- [ ] Verifiera att filer finns på GitHub (https://github.com/DITTNAMN/gymnasiearbete-textgenerator)

**Anteckningar:**
```
GitHub-repoURL: _________________________________________
GitHub-användarnamn: ____________________________________
```

---

## 📊 Steg 2: Sätt upp Supabase Database

- [ ] Supabase-konto skapat (https://supabase.com)
- [ ] Nytt projekt skapat
  - [ ] Projektnamn: `gymnasiearbete-db`
  - [ ] Region: `Stockholm` (eller närmaste)
  - [ ] Database-lösenord sparat 🔐: `_______________________`
- [ ] Väntat på initalisering (~30 sekunder)
- [ ] SQL Editor öppnad
- [ ] `database/schema.sql` importerad
- [ ] Tabeller verifierade (`users`, `ai_texts`, `sessions`)
- [ ] Connection String kopierad från Settings → Database → Connection Pooling
- [ ] Connection String sparad (med lösenord!)

**Connection String:**
```
postgresql://postgres.:PASSWORD@db.XXXXX.supabase.co:6543/postgres
```

**Anteckningar:**
```
Supabase Projekt-ID: ________________________________________
Database-lösenord: 🔐 _______________________________________
```

---

## 🤗 Steg 3: Ladda Checkpoint på Hugging Face

- [ ] Hugging Face-konto skapat (https://huggingface.co/signup)
- [ ] Nytt Model-repo skapat
  - [ ] Model ID: `gymnasiearbete-checkpoint`
  - [ ] License: `mit`
  - [ ] Visibility: `Public`
- [ ] `ai/model_checkpoint.pt` uppladdat
- [ ] Upload avslutad (kan ta 1-2 minuter)
- [ ] Download-URL kopierad och sparad

**Model Download URL:**
```
https://huggingface.co/USERNAME/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt
```

**Anteckningar:**
```
Hugging Face-användarnamn: _________________________________
Model-repository URL: _______________________________________
```

---

## 🚂 Steg 4: Deploy på Railway

### 4a) Logga in och Skapa Projekt

- [ ] Railway-konto skapat (https://railway.app)
- [ ] Loggad in med GitHub
- [ ] Auktoriserat Railway med GitHub
- [ ] Nytt projekt skapat: **New Project** → **Deploy from GitHub repo**
- [ ] `gymnasiearbete-textgenerator`-repo valt
- [ ] Väntat på deployment (5-10 minuter första gången)

### 4b) Verifiera Services

I Railway Dashboard bör du se:
- [ ] `web` service (grön ✅)
- [ ] `api` service (grön ✅)

**Services finns på:**
```
Web URL: ________________________________________________
API URL: ________________________________________________
```

### 4c) Sätt Environment Variables för `web`-servern

1. Klick på `web`-servern
2. Gå till **Variables**
3. Lägg till dessa (kopiera från dina anteckningar ovan):

- [ ] `APP_ENV` = `production`
- [ ] `BASE_URL` = `/`
- [ ] `DB_HOST` = `db.XXXXX.supabase.co`
- [ ] `DB_NAME` = `postgres`
- [ ] `DB_USER` = `postgres`
- [ ] `DB_PASS` = 🔐`[Ditt Supabase-lösenord]`
- [ ] `DB_PORT` = `6543`
- [ ] `DB_TYPE` = `postgres`
- [ ] `AI_API_URL` = `https://[API_RAILWAY_URL]/generate`

**WICHTIG:** Ersätt `[API_RAILWAY_URL]` med din faktiska API-URL från Railway

### 4d) Sätt Environment Variables för `api`-servern

1. Klick på `api`-servern
2. Gå till **Variables**
3. Lägg till dessa:

- [ ] `MODEL_CHECKPOINT_URL` = 🔐`https://huggingface.co/DITTNAMN/gymnasiearbete-checkpoint/resolve/main/model_checkpoint.pt`
- [ ] `CHECKPOINT_PATH` = `/app/ai/model_checkpoint.pt`
- [ ] `CORS_ORIGINS` = `https://[WEB_RAILWAY_URL]`

**WICHTIG:** Ersätt `[WEB_RAILWAY_URL]` med din faktiska web-URL från Railway

---

## ✅ Steg 5: Verifiera Deployment

- [ ] Båda services visar **✅ (grön)** status i Railway
- [ ] Väntat på att båda services är "Running"
- [ ] Öppnad web-URL i browser
- [ ] Startsidan visas
- [ ] Klickad **Register** och testade registrering
- [ ] Loggat in med test-konto
- [ ] Testade textgenerering
- [ ] Historiken sparade genererad text
- [ ] API `/docs` endpoint fungerar (https://[API_URL]/docs)

**Funktionalitet testad:**
- [ ] Webbsidan laddas utan fel
- [ ] Registrering fungerar
- [ ] Inloggning fungerar
- [ ] Dashboard visas
- [ ] Textgenerering fungerar
- [ ] Historiken sparas
- [ ] Edit-funktionen fungerar
- [ ] Delete-funktionen fungerar
- [ ] API-endpoints är tillgängliga

---

## 🎯 Steg 6: Valfria Anpassningar

- [ ] Custom domain konfigurerad (valfritt)
- [ ] GitHub-webhook verifierad (Railway auto-uppdateras vid push)
- [ ] Backup-plan för databas upprättad
- [ ] Monitorering av logs konfigurerad

---

## 🚨 Troubleshooting

Om något går fel, se **DEPLOY_ONLINE.md** avsnitt "Vanliga Problem & Lösningar"

**Problem uppstod:**
```
Vilket steg:____________________________________________
Felmeddelande:__________________________________________
Lösning: _______________________________________________
```

---

## 📊 Deployment Summary

**Deployment datum:** ___________  
**Deployad av:** ___________  
**Testning vervollständigad:** Ja / Nej

**Användarnamn för test-konto:**
```
Användare: _______________
Lösenord: 🔐 _______________
```

**Noter för framtiden:**
```
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
```

---

## ✨ GRATULERAR! 🎉

Ditt TextGenerator-projekt är nu online!

**Nästa steg:**
1. Testa skriva detta i ditt gymnasiearbete
2. Säkerhetskopiera databasen regelbundet
3. Monitorera applikationen för fel
4. Uppdatera dokumentationen med faktiska URLs

📝 Spara denna checklist tillsammans med ditt gymnasiearbete som bevis på deployment!
