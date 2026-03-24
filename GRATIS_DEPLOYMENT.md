# Helt Gratis Deployment - NeuralText

## Kostnadssummering
| Tjänst | Kostnad | Begränsning |
|--------|---------|------------|
| **Railway** (Web + API) | $5/mån credit | +Unlimited users |
| **Supabase** (PostgreSQL) | Gratis | 500 MB storage |
| **Hugging Face** (Checkpoint) | Gratis | Unlimited |
| **GitHub** (Repository) | Gratis | Public |
| **TOTAL** | **$0-5/mån** | Helt reasonable |

---

## Deployment-steg

### 1. **Förbered Supabase PostgreSQL**
```
1. Gå till https://supabase.com och skapa ett gratis konto
2. Skapa nytt project (välj region närmast Sverige)
3. I SQL Editor → kör database/schema.sql
4. Kopiera Connection String från Settings → Database → Connection Pooling
   Format: postgresql://user:password@db.xxxx.supabase.co:6543/postgres
```

### 2. **Ladda up checkpoint på Hugging Face**
```
1. Gå till https://huggingface.co och registrera
2. Skapa ett public repository (namn: "gymnasiearbete-neuralhtext-checkpoint")
3. Ladda up ai/model_checkpoint.pt till Files
4. Kopiera download URL från File info
   Exempel: https://huggingface.co/.../resolve/main/model_checkpoint.pt
```

### 3. **Pushga till GitHub**
```
1. Skapa public repo på GitHub
2. git init
3. git add .
4. git commit -m "Initial gymnasiearbete deployment"
5. git remote add origin https://github.com/DITTNAMN/gymnasiearbete.git
6. git push -u origin main
```

### 4. **Deploy på Railway**
```
1. Gå till https://railway.app
2. Logga in med GitHub
3. Ny projekt → Deploy från GitHub repo
4. Railway auto-detectar Dockerfiles och skapar två services
5. Sätt Environment Variables (se nedan)
```

### 5. **Environment Variables i Railway**

**För web-service:**
```
APP_ENV=production
BASE_URL=https://yourapp.railway.app
DB_HOST=db.xxxx.supabase.co
DB_NAME=postgres
DB_USER=postgres
DB_PASS=[supabase password]
DB_PORT=6543
AI_API_URL=https://api-yourapp.railway.app
```

**För api-service:**
```
MODEL_CHECKPOINT_URL=https://huggingface.co/.../resolve/main/model_checkpoint.pt
CHECKPOINT_PATH=/tmp/model_checkpoint.pt
CORS_ORIGINS=https://yourapp.railway.app
```

---

## Kostnadsfritt för immer:
✅ GitHub (obegränsad)  
✅ Supabase (500MB gratis)  
✅ Hugging Face (unlimited)  
✅ Railway ($5 monthly credit räcker för denna app)  

---

## Nästa steg:
1. Förbered Supabase PostgreSQL daggen
2. Skapa GitHub repo
3. Registrera Hugging Face och ladda checkpoint
4. Connecta Railway till GitHub
5. Sätt env-variabler i Railway Dashboard
6. Testa health endpoints
