# Kapitel 3 - Testresultat (2026-03-30)

## 1) Sakerhetstest: SQL-injektion

### Testupplagg
- Endpoint: `http://127.0.0.1:8080/login.php`
- Attackpayload (email): `admin@ai-project.com' OR '1'='1`
- Kontrolltest: normal login med giltiga uppgifter

### Resultat
| Deltest | Metrik | Resultat |
|---|---|---|
| Attack-POST | HTTP-status | 200 |
| Efter attack | `GET /dashboard.php` | 302 -> `login.php` |
| Slutsats attack | Inloggning blockerad | PASS |
| Kontrolllogin | `POST /login.php` | 302 -> `dashboard.php` |
| Kontroll-dashboard | `GET /dashboard.php` | 200 |

**Tolkning:** SQL-injektion gav inte autentiserad session. Systemet blockerade attacken.

---

## 2) Prestandatest: Genereringstid

### Testupplagg
- Endpoint: `http://127.0.0.1:8000/generate`
- Prompt: `Neural text benchmark prompt`
- Langd: 200
- Antal korningar: 10

### Radata
| Korning | Tid (sekunder) |
|---|---:|
| 1 | 23.1044 |
| 2 | 23.9021 |
| 3 | 23.7517 |
| 4 | 25.4544 |
| 5 | 24.4439 |
| 6 | 25.2591 |
| 7 | 26.8243 |
| 8 | 21.7983 |
| 9 | 18.4612 |
| 10 | 20.0633 |

### Sammanfattning
| Metrik | Varde |
|---|---:|
| Medel | 23.3063 s |
| Median | 23.8269 s |
| Min | 18.4612 s |
| Max | 26.8243 s |

---

## 3) Prestandatest: Traningstid

### Testupplagg
- Traning benchmark korning via tillfalligt benchmarkscript baserat pa `ai/ai.py`

### Resultat
| Metrik | Varde |
|---|---:|
| Exit-kod | -1 |
| Uppmatt tid till avbrott | 51.6666 s |

**Status:** Ej godkant slutresultat (korningsfel). `ai/ai.py` har uppdaterats med PyTorch-kompatibel `GradScaler` for att mojliggora ny korning.

---

## 4) Bedomning mot kravtext

- Automatiserat sakerhetstest med verkligt attackpayload ar genomfort och dokumenterat.
- Prestandatest for generering ar genomfort med exakta sekunder och tabell.
- Traningstid ar uppmatt till avbrott (51.6666 s), men testet maste koras om for ett fullstandigt godkant trani ngsresultat.
