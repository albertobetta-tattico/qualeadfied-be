# API Import Lead

Endpoint per l'importazione di un singolo lead da sistemi esterni (Zapier, CRM, webhook).

## Endpoint

```
POST /api/import/lead
```

L'endpoint **non richiede autenticazione Sanctum** (login utente/admin). L'autenticazione avviene tramite API key statica associata a una **Sorgente Lead**.

---

## Autenticazione

Ogni richiesta deve includere l'header `X-Api-Key` con la chiave API di una Sorgente Lead attiva.

```
X-Api-Key: <api_key>
```

La chiave identifica automaticamente la sorgente: il campo `source_id` del lead viene valorizzato con la sorgente corrispondente, senza doverlo passare nel body.

### Come ottenere la API key

1. Accedere al **backoffice admin** (`/admin`)
2. Navigare in **Sorgenti Lead** (menu laterale)
3. Creare una nuova sorgente o selezionarne una esistente
4. Cliccare **Rigenera Chiave API** per generare (o rigenerare) la chiave
5. Copiare la chiave mostrata nella risposta

La chiave viene generata come UUID v4 (es. `a1b2c3d4-e5f6-7890-abcd-ef1234567890`).

> **Nota**: la chiave API viene nascosta nelle risposte standard per sicurezza. E' visibile solo al momento della generazione/rigenerazione. Se la chiave viene smarrita, rigenerarla dal backoffice.

### Endpoint admin per la gestione delle sorgenti

| Metodo | Endpoint | Descrizione |
|--------|----------|-------------|
| `GET` | `/api/admin/lead-sources` | Lista sorgenti (richiede auth admin) |
| `POST` | `/api/admin/lead-sources` | Crea nuova sorgente |
| `PUT` | `/api/admin/lead-sources/{id}` | Aggiorna sorgente |
| `DELETE` | `/api/admin/lead-sources/{id}` | Elimina sorgente |
| `POST` | `/api/admin/lead-sources/{id}/regenerate-key` | Rigenera API key |

---

## Campi della richiesta

### Dati anagrafici (obbligatori)

| Campo | Tipo | Obbl. | Max | Descrizione |
|-------|------|-------|-----|-------------|
| `first_name` | string | Si | 100 | Nome del contatto |
| `last_name` | string | Si | 100 | Cognome del contatto |
| `email` | string | Si | 255 | Email del contatto (deve essere un indirizzo valido) |
| `phone` | string | Si | 50 | Telefono del contatto |

### Classificazione (obbligatori)

| Campo | Tipo | Obbl. | Max | Descrizione |
|-------|------|-------|-----|-------------|
| `category` | string | Si | 255 | Categoria merceologica: accetta lo **slug** (es. `fotovoltaico`) oppure il **nome** (es. `Fotovoltaico`). Deve corrispondere a una categoria attiva nel sistema. |
| `province` | string | Si | 10 | Codice provincia italiano (es. `MI`, `RM`, `TO`, `NA`). Viene convertito automaticamente in maiuscolo. |
| `country` | string | Si | 2 | Codice paese ISO 3166-1 alpha-2 (es. `IT`, `DE`, `FR`). Viene convertito automaticamente in maiuscolo. |

### Tracciamento acquisizione (opzionali)

| Campo | Tipo | Obbl. | Max | Descrizione |
|-------|------|-------|-----|-------------|
| `medium` | string | No | 100 | Mezzo di acquisizione (es. `cpc`, `organic`, `social`, `email`, `referral`, `display`) |
| `campaign` | string | No | 255 | Nome della campagna di acquisizione (es. `fotovoltaico-primavera-2026`) |
| `request_text` | string | No | - | Testo della richiesta del contatto |

### Riferimenti esterni (opzionali)

| Campo | Tipo | Obbl. | Max | Descrizione |
|-------|------|-------|-----|-------------|
| `external_id` | string | No | 255 | Identificativo del lead nel sistema di origine (es. ID Delera, ID CRM) |
| `extra_tags` | array | No | - | Array di tag aggiuntivi (es. `["urgente", "grande impianto"]`) |
| `generated_at` | string (date) | No | - | Data di generazione del lead, formato `YYYY-MM-DD`. Se omesso viene usata la data corrente. |

### Campi auto-valorizzati dal sistema

| Campo | Valore | Descrizione |
|-------|--------|-------------|
| `source_id` | Dalla API key | Determinato automaticamente dalla chiave API utilizzata |
| `status` | `free` | Il lead viene creato sempre come disponibile |
| `current_shares` | `0` | Nessuna condivisione al momento della creazione |

---

## Esempio di richiesta JSON

### Payload completo (tutti i campi)

```json
{
  "first_name": "Mario",
  "last_name": "Rossi",
  "email": "mario.rossi@gmail.com",
  "phone": "+39 333 1234567",
  "category": "fotovoltaico",
  "province": "MI",
  "country": "IT",
  "medium": "cpc",
  "campaign": "fotovoltaico-primavera-2026",
  "request_text": "Vorrei un preventivo per impianto fotovoltaico 6kW con accumulo per la mia villetta.",
  "external_id": "DELERA-00123",
  "extra_tags": ["urgente", "residenziale"],
  "generated_at": "2026-03-24"
}
```

### Payload minimo (solo campi obbligatori)

```json
{
  "first_name": "Laura",
  "last_name": "Bianchi",
  "email": "laura.bianchi@yahoo.it",
  "phone": "347 9876543",
  "category": "infissi",
  "province": "RM",
  "country": "IT"
}
```

---

## Risposte

### 201 Created - Lead importato con successo

```json
{
  "message": "Lead imported successfully.",
  "data": {
    "id": 81,
    "category_id": 1,
    "province_id": 10,
    "source_id": 2,
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario.rossi@gmail.com",
    "phone": "+39 333 1234567",
    "country": "IT",
    "medium": "cpc",
    "campaign": "fotovoltaico-primavera-2026",
    "request_text": "Vorrei un preventivo per impianto fotovoltaico 6kW con accumulo.",
    "extra_tags": ["urgente", "residenziale"],
    "status": "free",
    "current_shares": 0,
    "external_id": "DELERA-00123",
    "generated_at": "2026-03-24",
    "created_at": "2026-03-24T15:30:00.000000Z",
    "updated_at": "2026-03-24T15:30:00.000000Z",
    "category": { "id": 1, "name": "Fotovoltaico", "slug": "fotovoltaico" },
    "province": { "id": 10, "name": "Milano", "code": "MI", "region": "Lombardia" },
    "source": { "id": 2, "name": "Facebook Ads", "slug": "facebook-ads" }
  }
}
```

### 401 Unauthorized - Chiave API mancante o non valida

```json
{
  "message": "Missing X-Api-Key header."
}
```

```json
{
  "message": "Invalid or inactive API key."
}
```

### 422 Unprocessable Entity - Errore di validazione

```json
{
  "message": "Category not found.",
  "errors": {
    "category": ["No category matches 'categoria-inesistente'"]
  }
}
```

```json
{
  "message": "The first name field is required.",
  "errors": {
    "first_name": ["The first name field is required."],
    "email": ["The email field must be a valid email address."]
  }
}
```

---

## Test con cURL

### 1. Ottenere la API key (richiede login admin)

```bash
# Login admin
TOKEN=$(curl -s -X POST http://localhost:8000/api/admin/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@qualeadfied.com", "password": "password"}' \
  | jq -r '.data.token')

# Rigenerare la chiave per la sorgente con ID 1
curl -s -X POST http://localhost:8000/api/admin/lead-sources/1/regenerate-key \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  | jq '.data.api_key'
```

### 2. Importare un lead (payload completo)

```bash
API_KEY="la-tua-api-key-qui"

curl -X POST http://localhost:8000/api/import/lead \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -d '{
    "first_name": "Mario",
    "last_name": "Rossi",
    "email": "mario.rossi@gmail.com",
    "phone": "+39 333 1234567",
    "category": "fotovoltaico",
    "province": "MI",
    "country": "IT",
    "medium": "cpc",
    "campaign": "fotovoltaico-primavera-2026",
    "request_text": "Vorrei un preventivo per impianto fotovoltaico 6kW con accumulo.",
    "external_id": "DELERA-00123",
    "extra_tags": ["urgente", "residenziale"],
    "generated_at": "2026-03-24"
  }'
```

### 3. Importare un lead (payload minimo)

```bash
curl -X POST http://localhost:8000/api/import/lead \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -d '{
    "first_name": "Laura",
    "last_name": "Bianchi",
    "email": "laura.bianchi@yahoo.it",
    "phone": "347 9876543",
    "category": "infissi",
    "province": "RM",
    "country": "IT"
  }'
```

### 4. Testare errori

```bash
# Senza API key -> 401
curl -X POST http://localhost:8000/api/import/lead \
  -H "Content-Type: application/json" \
  -d '{"first_name": "Test"}'

# Categoria inesistente -> 422
curl -X POST http://localhost:8000/api/import/lead \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -d '{
    "first_name": "Test",
    "last_name": "Test",
    "email": "test@test.it",
    "phone": "333 0000000",
    "category": "categoria-inesistente",
    "province": "MI",
    "country": "IT"
  }'
```

---

## Configurazione Zapier

1. In Zapier, creare un nuovo Zap con trigger dal CRM (es. Delera)
2. Come action, selezionare **Webhooks by Zapier** > **POST**
3. Configurare:
   - **URL**: `https://tuodominio.com/api/import/lead`
   - **Payload Type**: `json`
   - **Headers**: `X-Api-Key` = `<la_tua_api_key>`
4. Mappare i campi del trigger sui campi del body JSON
5. Testare lo Zap e attivarlo

### Mapping consigliato Delera -> Qualeadfied

| Campo Delera | Campo API |
|-------------|-----------|
| Nome | `first_name` |
| Cognome | `last_name` |
| Email | `email` |
| Telefono | `phone` |
| Categoria | `category` |
| Provincia | `province` |
| Paese | `country` |
| Sorgente/UTM Source | `medium` |
| Campagna/UTM Campaign | `campaign` |
| Note/Richiesta | `request_text` |
| ID Contatto | `external_id` |

---

## Categorie disponibili

Le categorie accettano sia lo slug che il nome. Elenco attuale:

| Slug | Nome |
|------|------|
| `fotovoltaico` | Fotovoltaico |
| `infissi` | Infissi |
| `climatizzazione` | Climatizzazione |
| `caldaie` | Caldaie |
| `pompe-di-calore` | Pompe di Calore |
| `ristrutturazioni` | Ristrutturazioni |
| `efficienza-energetica` | Efficienza Energetica |
| `isolamento-termico` | Isolamento Termico |

> L'elenco puo variare: le categorie sono gestite dal backoffice admin. Per ottenere la lista aggiornata usare `GET /api/public/categories`.
