# 🚀 Quick Start Deployment Guide

**Målsättning:** Få din TextGenerator online på Railway på 15 minuter

## ⚡ TL;DR - 3 Tjänster Behövs:

1. **GitHub** - För att lagra kod
2. **Supabase** - För PostgreSQL databas (gratis)
3. **Railway** - För att köra webappen + API ($5/månad credit gratis)

---

## 📝 Steg-sammanfattning:

### 1️⃣ Push till GitHub (5 min)

```powershell
cd "G:\Min enhet\Allt\Webserverprogrameringx\GYMNASIEARBETE1"
git init
git add .
git commit -m "Initial deployment"
git remote add origin https://github.com/DITTNAMN/gymnasiearbete-textgenerator.git
git branch -M main
git push -u origin main
```

### 2️⃣ Sätt upp Supabase Database (3 min)

- Gå till https://supabase.com
- Skapa projekt
- Importera `database/schema.sql` (Copy-paste i SQL Editor)
- Kopiera **Connection String** från Settings → Database → Connection Pooling

### 3️⃣ Ladda Checkpoint på Hugging Face (3 min)

- Gå till https://huggingface.co/new
- Create Model: `gymnasiearbete-checkpoint`
- Upload file: `ai/model_checkpoint.pt`
- Kopiera **Download URL**

### 4️⃣ Deploy på Railway (4 min)

- Gå till https://railway.app
- Login med GitHub
- New Project → Deploy from GitHub → välj din repo
- Vänta på deploy (5-10 min första gången)
- Sätt Environment Variables (se **DEPLOY_ONLINE.md**)

---

## ❓ Behöver Jag Kunna Koda?

**Nej!** Bara copy-paste instruktioner 😊

---

## 📞 Problem?

Se **DEPLOY_ONLINE.md** för felsökning och detaljerade instruktioner.

---

**SE DEPLOY_ONLINE.md FÖR FULLSTÄNDIG GUIDE!**
