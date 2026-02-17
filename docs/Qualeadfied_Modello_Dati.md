**QUALEAD\*FIED**

Modello Dati

*Documento di Specifica del Database*

Versione 2.0 - Febbraio 2025

**1. Panoramica**

Il modello dati di Qualead\*fied è progettato per supportare una
piattaforma di vendita lead B2B con le seguenti caratteristiche chiave:

-   Architettura separata autenticazione/profilo (Users + Client Profiles)

-   Vendita lead in modalità esclusiva o condivisa (fino a N acquirenti
    per categoria)

-   Sistema a pacchetti multi-categoria con quantità separate esclusivi/condivisi

-   Prova gratuita configurabile per singolo cliente

-   Pagamenti via Stripe (carta e SEPA)

-   Fatturazione elettronica integrata con SDI (via Fatture in Cloud)

-   Backoffice con ruoli operatore (super_admin, admin, operator)

-   Catalogo pubblico e autenticato con filtri avanzati

**2. Entità Principali**

**2.1 USERS (Utenti - Autenticazione)**

Entità base per l'autenticazione. Supporta sia clienti B2B che operatori admin.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | email | string, unique | Email (usata per login) |
  | email_verified_at | timestamp | Data verifica email |
  | password | string | Password hash |
  | role | enum | client \| admin \| super_admin |
  | status | enum | pending \| active \| suspended \| blocked |
  | created_at | timestamp | Data registrazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Lo stato `pending` indica utente in attesa di verifica email

-   Lo stato `blocked` aggiunto per blocco permanente dell'account

-   Il campo `role` determina il tipo di utente e i permessi associati

-   Separazione netta tra dati di autenticazione (Users) e dati di profilo business (Client Profiles)

**2.2 CLIENT_PROFILES (Profili Clienti B2B)**

Dati business dei clienti. Relazione 1:1 con Users (role = 'client').

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | user_id | FK → Users, unique | Utente associato |
  | company_name | string | Ragione sociale |
  | vat_number | string | Partita IVA |
  | phone | string | Telefono aziendale |
  | first_name | string | Nome persona di riferimento |
  | last_name | string | Cognome persona di riferimento |
  | billing_address | string | Indirizzo fatturazione |
  | billing_city | string | Città fatturazione |
  | billing_province | string | Provincia fatturazione |
  | billing_zip | string | CAP fatturazione |
  | billing_country | string | Paese fatturazione (default: IT) |
  | sdi_code | string | Codice destinatario SDI |
  | pec_email | string | PEC per fatturazione elettronica |
  | free_trial_enabled | boolean | Flag prova gratuita attiva |
  | free_trial_leads_remaining | integer | Lead gratuiti ancora disponibili |
  | email_notifications_enabled | boolean | Preferenza notifiche email |
  | marketing_consent | boolean | Consenso marketing (opzionale) |
  | created_at | timestamp | Data creazione profilo |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Dati di fatturazione ora in campi separati (non più JSON) per query dirette

-   `free_trial_leads_remaining` sostituisce i precedenti `free_trial_leads_total` e `free_trial_leads_used`

-   La prova gratuita è configurabile per singolo cliente dall'admin

**2.3 CLIENTS (Vista Admin dei Clienti)**

Vista completa dei clienti usata dal backoffice amministrativo. Combina dati utente e profilo.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | company_name | string | Ragione sociale |
  | vat_number | string | Partita IVA |
  | email | string, unique | Email login |
  | phone | string | Telefono aziendale |
  | contact_first_name | string | Nome persona di riferimento |
  | contact_last_name | string | Cognome persona di riferimento |
  | status | enum | pending \| active \| suspended |
  | free_trial_enabled | boolean | Flag prova gratuita attiva |
  | free_trial_leads_total | integer | N. lead gratuiti assegnati |
  | free_trial_leads_used | integer | Lead gratuiti già utilizzati |
  | billing_data | JSON | Dati fatturazione (address, city, province, postal_code, country, sdi_code, pec) |
  | bank_data | JSON | Dati bancari (iban, bank_account_holder, bic_swift, bank_name) |
  | category_ids | JSON (array) | Categorie di interesse del cliente |
  | terms_accepted | boolean | Accettazione T&C |
  | privacy_accepted | boolean | Accettazione Privacy Policy |
  | marketing_consent | boolean | Consenso marketing (opzionale) |
  | notify_new_leads | boolean | Preferenza notifiche nuovi lead |
  | email_verified_at | timestamp | Data verifica email |
  | created_at | timestamp | Data registrazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   `bank_data` è un JSON che contiene IBAN e dati bancari per pagamenti SEPA

-   `category_ids` è un array JSON con gli ID delle categorie di interesse

-   `billing_data` è un JSON con i dati strutturati per fatturazione

-   Vista aggregata utilizzata dal pannello admin per gestione completa clienti

**2.4 ADMINS (Operatori Backoffice)**

Operatori del backoffice amministrativo. Supporta ruoli differenziati.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | email | string, unique | Email login |
  | first_name | string | Nome operatore |
  | last_name | string | Cognome operatore |
  | password | string | Password hash |
  | role | enum | super_admin \| admin \| operator |
  | status | enum | active \| inactive |
  | last_login_at | timestamp | Ultimo accesso |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Tre livelli di ruolo: `super_admin` (accesso completo), `admin` (gestione operativa), `operator` (operazioni limitate)

-   Lo stato `inactive` sostituisce il precedente booleano `is_active`

-   Separati da Users per sicurezza e contesti distinti

**2.5 CATEGORIES (Categorie Merceologiche)**

Settori merceologici a cui appartengono i lead.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | name | string, unique | Nome categoria |
  | slug | string, unique | Slug URL-friendly |
  | description | text, nullable | Descrizione |
  | max_shares | integer | N massimo condivisioni per lead (default: 3) |
  | is_active | boolean | Categoria attiva |
  | sort_order | integer | Ordine visualizzazione |
  | custom_fields | JSON | Definizioni campi personalizzati (array di {key, label}) |
  | leads_count | integer (computed) | Conteggio lead totali nella categoria |
  | available_leads_count | integer (computed) | Conteggio lead disponibili nella categoria |
  | deleted_at | timestamp | Soft delete (per categorie dismesse) |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Ogni categoria definisce il proprio valore N (max condivisioni)

-   `custom_fields` permette di definire campi aggiuntivi specifici per categoria (es. tipo immobile per "Immobiliare")

-   `leads_count` e `available_leads_count` sono campi calcolati/aggregati per statistiche rapide

-   Non esiste gerarchia tra categorie

-   L'admin può creare nuove categorie o dismettere quelle esistenti

-   Soft delete per mantenere storico lead esistenti

**2.6 CATEGORY_PRICES (Prezzi per Categoria)**

Listino prezzi per categoria. Supporta storicizzazione.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | category_id | FK → Categories | Categoria di riferimento |
  | exclusive_price | decimal | Prezzo lead esclusivo |
  | shared_prices | JSON | Prezzi per ogni slot condiviso (Record\<string, number\>) |
  | valid_from | timestamp | Inizio validità |
  | valid_to | timestamp, nullable | Fine validità (NULL = corrente) |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Il prezzo dipende SOLO dalla categoria e modalità (non dalla provincia)

-   Prezzo condiviso fisso indipendentemente dallo slot occupato

-   La struttura JSON permette flessibilità futura per prezzi differenziati

-   Storicizzazione prezzi per tracciare variazioni nel tempo

-   Log storico modifiche prezzi con `changed_at` e `changed_by` per audit trail

**2.7 PROVINCES (Province)**

Anagrafica province italiane. Pre-popolata con tutte le 107 province, organizzate per 20 regioni.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | name | string | Nome provincia |
  | code | string, unique | Sigla (MI, RM, TO, etc.) |
  | region | string | Regione di appartenenza |
  | is_active | boolean | Provincia attiva |
  | leads_count | integer (computed) | Conteggio lead nella provincia |

**Note:**

-   Pre-popolata con tutte le province italiane (107 province, 20 regioni)

-   Usata per filtro geografico dei lead

-   `leads_count` è un campo calcolato/aggregato per statistiche rapide

-   Le regioni disponibili sono: Abruzzo, Basilicata, Calabria, Campania, Emilia-Romagna, Friuli-Venezia Giulia, Lazio, Liguria, Lombardia, Marche, Molise, Piemonte, Puglia, Sardegna, Sicilia, Toscana, Trentino-Alto Adige, Umbria, Valle d'Aosta, Veneto

**2.8 LEAD_SOURCES (Fonti Lead)**

Sorgenti da cui provengono i lead.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | name | string | Nome sorgente (Meta Ads, Google, Manual, etc.) |
  | slug | string, unique | Identificativo tecnico |
  | description | text | Descrizione |
  | api_key | string, unique | API key per autenticazione (solo sorgenti API) |
  | is_active | boolean | Sorgente attiva |
  | config | JSON | Configurazioni specifiche |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Ogni sorgente esterna ha una propria API key

-   Sorgente "Manual" per inserimenti da backoffice/upload

**2.9 LEADS (Lead)**

Entità principale: i lead da vendere.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | category_id | FK → Categories | Categoria merceologica |
  | province_id | FK → Provinces | Provincia |
  | source_id | FK → Lead_Sources | Fonte del lead |
  | first_name | string | Nome contatto |
  | last_name | string | Cognome contatto |
  | email | string | Email contatto |
  | phone | string | Telefono contatto |
  | request_text | text | Testo della richiesta |
  | extra_tags | JSON | Tag aggiuntivi (Record\<string, any\>) |
  | status | enum | free \| sold_exclusive \| sold_shared \| exhausted |
  | current_shares | integer | Numero condivisioni attuali |
  | generated_at | date | Data generazione del lead |
  | external_id | string | ID dal sistema esterno |
  | created_at | timestamp | Data inserimento in piattaforma |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Un lead già condiviso NON può MAI tornare disponibile per esclusiva

-   Possono esistere duplicati (stessa email/telefono) su categorie diverse

-   Nessuna scadenza temporale dei lead

-   Hard delete solo da admin (es. spam)

-   Non si possono cancellare lead già acquistati

-   Supporto import massivo con mapping colonne e strategia duplicati (skip/update/create)

**2.10 PACKAGES (Pacchetti Lead)**

Pacchetti preconfigurati acquistabili. Supporta multi-categoria con quantità separate per esclusivi e condivisi.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | category_ids | JSON (array) | Categorie associate (array di ID) |
  | name | string | Nome pacchetto |
  | description | text | Descrizione |
  | exclusive_lead_quantity | integer | Quantità lead esclusivi nel pacchetto |
  | exclusive_price | decimal | Prezzo per lead esclusivi (senza IVA) |
  | shared_lead_quantity | integer | Quantità lead condivisi nel pacchetto |
  | shared_price | decimal | Prezzo per lead condivisi (senza IVA) |
  | is_active | boolean | Pacchetto attivo |
  | sort_order | integer | Ordine visualizzazione |
  | sales_count | integer (computed) | Numero vendite totali |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   `category_ids` è un array: un pacchetto può coprire più categorie

-   Quantità e prezzi sono separati per esclusivi e condivisi

-   `sales_count` è un campo calcolato per statistiche rapide

-   Sistema "a scalare": si acquista un monte lead, poi si selezionano manualmente

**2.11 ORDERS (Ordini)**

Ordini effettuati dai clienti.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | user_id | FK → Users | Cliente |
  | order_number | string, unique | Numero ordine leggibile (ORD-2024-00001) |
  | type | enum | single \| package \| free_trial |
  | payment_method | enum | card \| sepa \| free |
  | subtotal | decimal | Totale senza IVA |
  | vat_rate | decimal | Aliquota IVA (default 22%) |
  | vat_amount | decimal | Importo IVA |
  | total | decimal | Totale con IVA |
  | status | enum | pending \| processing \| paid \| completed \| failed \| refunded \| cancelled |
  | billing_snapshot | JSON | Snapshot dati fatturazione al momento ordine |
  | items_count | integer (computed) | Numero di righe nell'ordine |
  | invoice_number | string | Numero fattura associata |
  | invoice_url | string | URL download fattura |
  | payment_id | string | ID pagamento esterno (Stripe) |
  | paid_at | timestamp | Data/ora pagamento |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   IVA sempre esposta separatamente (B2B)

-   `billing_snapshot` congela i dati fatturazione al momento dell'ordine (company_name, vat_number, address, city, province, postal_code, country, sdi_code, pec)

-   Nessun pagamento automatico/ricorrente

-   Lo stato `completed` indica ordine completato con tutti i lead consegnati

**2.12 ORDER_ITEMS (Righe Ordine)**

Dettaglio righe di ogni ordine.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | order_id | FK → Orders | Ordine padre |
  | lead_id | FK → Leads, nullable | Lead singolo (se applicabile) |
  | package_id | FK → Packages, nullable | Pacchetto (se applicabile) |
  | acquisition_mode | enum | exclusive \| shared \| free |
  | unit_price | decimal | Prezzo unitario |
  | quantity | integer | Quantità |
  | line_total | decimal | Totale riga |

**Note:**

-   Una riga ha SEMPRE o lead_id o package_id valorizzato (mai entrambi)

-   acquisition_mode si applica solo ai lead singoli

**2.13 USER_PACKAGES (Pacchetti Acquistati)**

Monte lead da consumare per ogni pacchetto acquistato.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | user_id | FK → Users | Cliente |
  | package_id | FK → Packages | Pacchetto acquistato |
  | order_id | FK → Orders | Ordine di riferimento |
  | package_name | string | Nome pacchetto (snapshot) |
  | category_id | FK → Categories, nullable | Categoria (NULL = multi-categoria) |
  | total_leads | integer | Lead totali acquistati |
  | exclusive_leads_total | integer | Lead esclusivi richiesti |
  | exclusive_leads_used | integer | Lead esclusivi già selezionati |
  | shared_leads_total | integer | Lead condivisi richiesti |
  | shared_leads_used | integer | Lead condivisi già selezionati |
  | status | enum | active \| exhausted \| expired |
  | purchased_at | timestamp | Data acquisto |
  | expires_at | timestamp, nullable | Data scadenza (NULL = nessuna) |
  | is_expired | boolean (computed) | Flag scadenza calcolato |

**Note:**

-   Le quantità esclusivi/condivisi sono indipendenti

-   `package_name` è uno snapshot del nome al momento dell'acquisto

-   Remaining calcolato come: total - used per ciascun tipo

-   Se rimangono lead, l'utente può attendere nuovi lead

-   Nessuna assegnazione automatica

-   `expires_at` opzionale per pacchetti con durata limitata

**2.14 LEAD_SALES (Vendite Lead)**

Registro di ogni singola vendita/assegnazione lead.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | lead_id | FK → Leads | Lead venduto |
  | user_id | FK → Users | Acquirente |
  | order_id | FK → Orders, nullable | Ordine (NULL per gratuiti senza ordine) |
  | user_package_id | FK → User_Packages, nullable | Se da pacchetto |
  | mode | enum | exclusive \| shared \| free |
  | share_slot | integer, nullable | Slot condivisione (1, 2, 3...) - NULL per esclusivi |
  | price_paid | decimal | Prezzo pagato (0 per gratuiti) |
  | sold_at | timestamp | Data/ora vendita |

**Note:**

-   UNIQUE (lead_id, user_id): un utente non può comprare lo stesso lead due volte

-   share_slot è sequenziale per ogni lead (1 = primo acquirente condiviso, 2 = secondo, etc.)

**2.15 USER_LEADS (Portafoglio Lead Utente)**

"I miei lead" - Lead posseduti da ogni utente.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | user_id | FK → Users | Proprietario |
  | lead_id | FK → Leads | Lead |
  | order_id | FK → Orders, nullable | Ordine di acquisto |
  | acquisition_type | enum | exclusive \| shared \| free_trial |
  | purchase_price | decimal | Prezzo pagato |
  | contact_status | enum | new \| contacted \| in_progress \| not_interested \| converted \| unreachable |
  | notes | text | Note dell'utente |
  | purchased_at | timestamp | Data acquisizione |
  | last_contacted_at | timestamp | Ultimo contatto |

**Note:**

-   UNIQUE (user_id, lead_id): un lead può apparire una sola volta nel portafoglio di un utente

-   `acquisition_type` usa `free_trial` (non `free`) per distinguere i lead da prova gratuita

-   Include i dati completi del lead associato (first_name, last_name, email, phone, request_text, extra_tags)

**2.16 CART_ITEMS (Carrello)**

Carrello temporaneo pre-acquisto. Ogni item rappresenta un singolo lead.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | user_id | FK → Users | Utente |
  | lead_id | FK → Leads | Lead selezionato |
  | purchase_mode | enum | exclusive \| shared |
  | price | decimal | Prezzo al momento aggiunta |
  | added_at | timestamp | Data/ora aggiunta al carrello |

**Note:**

-   UNIQUE (user_id, lead_id): stesso lead non può essere nel carrello due volte

-   Il carrello contiene solo lead singoli (i pacchetti sono acquistati direttamente)

-   I totali carrello (subtotal, vat_amount, total) sono calcolati lato client

-   Aliquota IVA fissa al 22% (`VAT_RATE = 22`)

**2.17 TRANSACTIONS (Transazioni Stripe)**

Log completo transazioni pagamento.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | order_id | FK → Orders | Ordine |
  | stripe_payment_intent_id | string, unique | Payment Intent ID |
  | stripe_charge_id | string | Charge ID |
  | stripe_customer_id | string | Customer ID |
  | stripe_payment_method_id | string | Payment Method ID |
  | payment_type | enum | card \| sepa_debit |
  | amount | decimal | Importo |
  | currency | string | Valuta (EUR) |
  | status | enum | pending \| requires_action \| processing \| succeeded \| failed \| canceled |
  | stripe_response | JSON | Risposta Stripe completa |
  | metadata | JSON | Metadati aggiuntivi (order_id, client_id, order_number) |
  | failure_code | string | Codice errore (se fallita) |
  | failure_message | text | Messaggio errore |
  | processed_at | timestamp | Data elaborazione |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   TUTTE le risposte Stripe sono archiviate integralmente (campo stripe_response)

-   Log estremamente accurato per gestione contestazioni

-   Supporto dettagli carta (`CardDetails`: brand, last4, exp_month, exp_year, country) e SEPA (`SepaDetails`: bank_code, branch_code, country, last4)

-   Supporto rimborsi (`TransactionRefund`: id, amount, status, reason, created_at)

-   Supporto eventi transazione (`TransactionEvent`: id, type, status, message, created_at)

**2.18 INVOICES (Fatture)**

Fatture elettroniche emesse.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | order_id | FK → Orders, unique | Ordine (1:1) |
  | invoice_number | string, unique | Numero fattura progressivo |
  | type | enum | invoice \| credit_note |
  | fatture_cloud_id | string | ID su Fatture in Cloud |
  | sdi_status | enum | pending \| sent \| delivered \| accepted \| rejected \| not_delivered \| error |
  | sdi_message | string | Messaggio stato SDI |
  | subtotal | decimal | Imponibile |
  | vat_rate | decimal | Aliquota IVA |
  | vat_amount | decimal | Importo IVA |
  | total | decimal | Totale fattura |
  | billing_data | JSON | Dati fatturazione (company_name, vat_number, address, city, province, postal_code, country, sdi_code, pec) |
  | notes | text | Note aggiuntive |
  | issued_at | timestamp | Data emissione |
  | due_at | timestamp, nullable | Data scadenza pagamento |
  | sent_at | timestamp, nullable | Data invio |
  | created_at | timestamp | Data creazione |
  | updated_at | timestamp | Data ultimo aggiornamento |

**Note:**

-   Integrazione con Fatture in Cloud per invio a SDI

-   Supporta sia fatture (`invoice`) che note di credito (`credit_note`)

-   `sdi_status` ora è un enum tipizzato con 7 stati possibili

-   Importi (subtotal, vat_rate, vat_amount, total) memorizzati direttamente

-   `billing_data` congela i dati di fatturazione al momento dell'emissione

-   Fatture memorizzate localmente e ricercabili da backoffice

**2.19 INVOICE_ITEMS (Righe Fattura)**

Dettaglio righe di ogni fattura.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | invoice_id | FK → Invoices | Fattura padre |
  | description | string | Descrizione riga |
  | quantity | integer | Quantità |
  | unit_price | decimal | Prezzo unitario |
  | line_total | decimal | Totale riga (imponibile) |
  | vat_rate | decimal | Aliquota IVA riga |
  | vat_amount | decimal | Importo IVA riga |

**Note:**

-   Ogni fattura ha le proprie righe di dettaglio

-   IVA gestita per singola riga per flessibilità futura

**2.20 NOTIFICATION_SETTINGS (Impostazioni Notifiche)**

Configurazione notifiche per categoria.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | category_id | FK → Categories | Categoria |
  | category_name | string | Nome categoria (denormalizzato) |
  | frequency | enum | instant \| hourly \| daily \| weekly \| disabled |
  | enabled | boolean | Notifica attiva per questa categoria |

**Note:**

-   Frequenza notifiche estesa: `instant` per notifica immediata, `disabled` per disattivare

-   Nome categoria denormalizzato per performance nelle query di configurazione

-   Inviate ai clienti che hanno acquistato in quella categoria

**2.21 ADMIN_ACTIVITY_LOGS (Log Attività Admin)**

Audit trail operazioni amministrative.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | admin_id | FK → Admins | Operatore |
  | admin_name | string | Nome operatore (denormalizzato) |
  | admin_email | string | Email operatore (denormalizzato) |
  | type | enum | login \| logout \| create \| update \| delete \| export \| import \| status_change \| password_reset \| config_change |
  | entity | enum | user \| client \| lead \| order \| invoice \| category \| package \| pricing \| admin \| system |
  | entity_id | bigint, nullable | ID entità |
  | entity_name | string | Nome/riferimento entità |
  | description | string | Descrizione azione |
  | old_values | JSON | Valori precedenti |
  | new_values | JSON | Nuovi valori |
  | ip_address | string | IP |
  | user_agent | string | Browser/client |
  | created_at | timestamp | Data/ora |

**Note:**

-   `type` tipizzato con 10 azioni specifiche (esteso rispetto al precedente campo generico `action`)

-   `entity` tipizzato con 10 entità specifiche (esteso rispetto al precedente campo generico `entity_type`)

-   Dati admin denormalizzati (admin_name, admin_email) per query rapide senza join

-   `description` per descrizione leggibile dell'azione

**2.22 SYSTEM_SETTINGS (Configurazioni Sistema)**

Parametri configurabili globali. Struttura tipizzata.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | PK | Identificativo univoco |
  | key | string, unique | Chiave configurazione |
  | value | JSON | Valore |
  | description | string | Descrizione |

**Chiavi di configurazione principali:**

  | **Chiave** | **Tipo Valore** | **Descrizione** |
  |---|---|---|
  | default_free_trial_leads | integer | Numero default lead prova gratuita |
  | default_vat_rate | decimal | Aliquota IVA default (22%) |
  | order_number_prefix | string | Prefisso numeri ordine (es. "ORD") |
  | invoice_number_prefix | string | Prefisso numeri fattura |
  | sender_email | string | Email mittente notifiche |
  | sender_name | string | Nome mittente notifiche |

**Configurazione Email (SMTP):**

  | **Chiave** | **Tipo Valore** | **Descrizione** |
  |---|---|---|
  | smtp_host | string | Host server SMTP |
  | smtp_port | integer | Porta SMTP |
  | smtp_username | string | Username SMTP |
  | smtp_password | string | Password SMTP |
  | smtp_encryption | enum | tls \| ssl \| none |

**Configurazione Email (API):**

  | **Chiave** | **Tipo Valore** | **Descrizione** |
  |---|---|---|
  | api_provider | enum | sendgrid \| mailgun \| postmark |
  | api_key | string | API key provider email |

**2.23 FATTURE_CLOUD_CONFIG (Configurazione Fatture in Cloud)**

Configurazione integrazione con il servizio Fatture in Cloud per fatturazione elettronica.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | enabled | boolean | Integrazione attiva |
  | access_token | string | Token di accesso API |
  | company_id | integer, nullable | ID azienda su Fatture in Cloud |
  | company_name | string | Nome azienda |
  | auto_send_sdi | boolean | Invio automatico a SDI |
  | default_payment_method | enum | bonifico \| carta \| ri.ba. \| contanti \| altro |
  | connected_at | timestamp | Data connessione |
  | last_sync_at | timestamp | Data ultima sincronizzazione |

**Note:**

-   `default_payment_method` usa i metodi di pagamento supportati da Fatture in Cloud

-   Supporto test connessione con risultato (success, company_name, company_id, error)

**3. Entità Catalogo Pubblico**

**3.1 PUBLIC_LEADS (Lead Pubblici - Vista Catalogo)**

Vista dei lead visibile nel catalogo pubblico (non autenticato).

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | integer | ID lead |
  | category_id | FK → Categories | Categoria |
  | province_id | FK → Provinces | Provincia |
  | request_preview | string | Anteprima testo richiesta (troncato) |
  | generated_at | date | Data generazione |
  | status | enum | Stato disponibilità |
  | is_exclusive_available | boolean | Disponibile per esclusiva |
  | shared_slots_available | integer | Slot condivisi ancora liberi |
  | shared_slots_total | integer | Slot condivisi totali |
  | base_price | decimal | Prezzo base indicativo |

**Note:**

-   Vista read-only derivata da LEADS con dati parziali

-   `request_preview` mostra solo un'anteprima del testo richiesta

-   Nessun dato personale del contatto è esposto (no nome, email, telefono)

**3.2 AUTHENTICATED_LEADS (Lead Autenticati - Vista Catalogo)**

Vista dei lead nel catalogo per utenti autenticati. Estende la vista pubblica con prezzi e stato personalizzato.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | id | integer | ID lead |
  | category_id | FK → Categories | Categoria |
  | province_id | FK → Provinces | Provincia |
  | request_text_partial | string | Testo richiesta parziale |
  | generated_at | date | Data generazione |
  | status | enum | Stato disponibilità |
  | is_exclusive_available | boolean | Disponibile per esclusiva |
  | shared_slots_available | integer | Slot condivisi ancora liberi |
  | shared_slots_total | integer | Slot condivisi totali |
  | exclusive_price | decimal | Prezzo esclusivo |
  | shared_price | decimal | Prezzo condiviso |
  | is_in_cart | boolean | Lead già nel carrello dell'utente |
  | is_owned | boolean | Lead già acquistato dall'utente |

**Note:**

-   Vista personalizzata per utente con stato carrello e proprietà

-   Prezzi visibili solo per utenti autenticati

-   `is_in_cart` e `is_owned` calcolati per ciascun utente

**3.3 HOMEPAGE_CONTENT (Contenuti Homepage)**

Struttura contenuti homepage pubblica.

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | hero | JSON | Sezione hero (headline, subtitle, cta_text, image_url) |
  | value_propositions | JSON array | Proposte di valore (icon, title, description) |
  | how_it_works | JSON array | Passi "come funziona" (step, title, description) |
  | stats | JSON | Statistiche pubbliche (total_leads_available, categories_count, provinces_covered, satisfied_clients) |
  | featured_categories | JSON array | Categorie in evidenza |

**4. Entità Form Pubblico**

**4.1 PUBLIC_LEAD_SUBMISSIONS (Invio Lead Pubblico)**

Lead inviati tramite form pubblico (landing page per raccolta lead).

  | **Attributo** | **Tipo** | **Descrizione** |
  |---|---|---|
  | category_slug | string | Slug della categoria |
  | province_id | FK → Provinces, nullable | Provincia |
  | first_name | string | Nome contatto |
  | last_name | string | Cognome contatto |
  | email | string | Email contatto |
  | phone | string | Telefono contatto |
  | request_text | text | Testo della richiesta |
  | extra_tags | JSON | Campi personalizzati (Record\<string, string\>) |

**Note:**

-   Form pubblico per raccolta lead direttamente dal sito

-   `extra_tags` contiene i valori dei `custom_fields` definiti nella categoria

-   Il lead viene creato automaticamente nella categoria corrispondente con status `free`

**5. Stati del Lead**

Un lead può trovarsi in uno dei seguenti stati:

  | **Stato** | **Descrizione** |
  |---|---|
  | free | Disponibile per acquisto esclusivo O condiviso |
  | sold_exclusive | Venduto in esclusiva → non più disponibile |
  | sold_shared | Venduto in condivisione (current_shares < max_shares) |
  | exhausted | Condivisioni esaurite (current_shares = max_shares) |

**Transizioni di Stato:**

-   Libero → Venduto Esclusivo: quando un cliente acquista in esclusiva

-   Libero → Venduto Condiviso (1/N): quando il primo cliente acquista
    in condivisione

-   Venduto Condiviso (x/N) → Venduto Condiviso ((x+1)/N): acquisti
    successivi in condivisione

-   Venduto Condiviso ((N-1)/N) → Esaurito Condiviso (N/N): ultimo slot
    condiviso occupato

*Nota importante: Un lead già condiviso non può MAI tornare disponibile
per acquisto esclusivo.*

**6. Enumerazioni**

Riepilogo di tutti i valori enum utilizzati nel sistema:

  | **Enum** | **Valori** | **Usato in** |
  |---|---|---|
  | UserRole | client, admin, super_admin | Users.role |
  | UserStatus | pending, active, suspended, blocked | Users.status |
  | ClientStatus | pending, active, suspended | Clients.status |
  | AdminRole | super_admin, admin, operator | Admins.role |
  | AdminStatus | active, inactive | Admins.status |
  | LeadStatus | free, sold_exclusive, sold_shared, exhausted | Leads.status |
  | OrderType | single, package, free_trial | Orders.type |
  | OrderStatus | pending, processing, paid, completed, failed, refunded, cancelled | Orders.status |
  | PaymentMethod | card, sepa, free | Orders.payment_method |
  | AcquisitionMode | exclusive, shared, free | OrderItems.acquisition_mode, LeadSales.mode |
  | AcquisitionType | exclusive, shared, free_trial | UserLeads.acquisition_type |
  | PurchaseMode | exclusive, shared | CartItems.purchase_mode |
  | ContactStatus | new, contacted, in_progress, not_interested, converted, unreachable | UserLeads.contact_status |
  | TransactionStatus | pending, requires_action, processing, succeeded, failed, canceled | Transactions.status |
  | TransactionPaymentType | card, sepa_debit | Transactions.payment_type |
  | InvoiceType | invoice, credit_note | Invoices.type |
  | SdiStatus | pending, sent, delivered, accepted, rejected, not_delivered, error | Invoices.sdi_status |
  | NotificationFrequency | instant, hourly, daily, weekly, disabled | NotificationSettings.frequency |
  | ActivityType | login, logout, create, update, delete, export, import, status_change, password_reset, config_change | AdminActivityLogs.type |
  | ActivityEntity | user, client, lead, order, invoice, category, package, pricing, admin, system | AdminActivityLogs.entity |
  | PackageStatus | active, exhausted, expired | UserPackages.status |
  | FicPaymentMethod | bonifico, carta, ri.ba., contanti, altro | FattureCloudConfig.default_payment_method |
  | ReportPeriod | today, week, month, quarter, year, custom | Report filtri |
  | ExportFormat | xlsx, csv | Export dati |

**7. Regole di Business**

**7.1 Ciclo di Vita del Lead**

1.  Inserimento: Lead entra con status = 'free', current_shares = 0

2.  Acquisto Esclusivo: status → 'sold_exclusive' (finale)

3.  Acquisto Condiviso: status → 'sold_shared', current_shares += 1.
    Quando current_shares = category.max_shares → status → 'exhausted'

**7.2 Vincoli di Integrità**

  | **Vincolo** | **Implementazione** |
  |---|---|
  | Un utente non può comprare lo stesso lead due volte | UNIQUE(lead_id, user_id) su LEAD_SALES |
  | Lead esclusivo non più modificabile | Check su status prima di ogni vendita |
  | Lead condiviso non può diventare esclusivo | Business logic + status check |
  | Lead acquistati non cancellabili | Check esistenza in LEAD_SALES prima di DELETE |

**7.3 Prova Gratuita**

-   Flag `free_trial_enabled` su CLIENT_PROFILES

-   Contatore `free_trial_leads_remaining` (decrementa ad ogni utilizzo)

-   Lead gratuiti NON contano nel conteggio condivisioni del lead

-   Registrati in LEAD_SALES con mode = 'free'

-   In USER_LEADS con acquisition_type = 'free_trial'

**7.4 Sistema Pacchetti a Scalare**

1.  Acquisto crea record in USER_PACKAGES con used = 0

2.  Ogni selezione lead incrementa il contatore appropriato (exclusive_leads_used / shared_leads_used)

3.  Quando entrambi total = used → status = 'exhausted'

4.  Lead non utilizzati restano disponibili fino a scadenza (se presente)

5.  Pacchetti multi-categoria: un singolo pacchetto può coprire lead di più categorie

**7.5 Import Massivo Lead**

-   Supporto upload file con mapping colonne personalizzabile

-   Strategia duplicati configurabile: skip (ignora), update (aggiorna), create (crea comunque)

-   Report risultato: total_rows, imported, skipped, errors con dettaglio per riga

**7.6 Registrazione Cliente**

-   Form registrazione include: dati azienda, dati contatto, password, consensi (terms, privacy, marketing), categorie di interesse

-   Verifica email obbligatoria (status = 'pending' fino a verifica)

-   Supporto reset e cambio password

**8. Indici Consigliati**

Indici per performance query frequenti:

  | **Tabella** | **Indice** | **Utilizzo** |
  |---|---|---|
  | LEADS | (category_id, status, generated_at) | Listing lead disponibili |
  | LEADS | (category_id, province_id, status) | Filtro geografico |
  | LEAD_SALES | (lead_id, user_id) | Verifica doppio acquisto |
  | LEAD_SALES | (user_id, sold_at) | Storico acquisti utente |
  | USER_LEADS | (user_id, contact_status) | Portafoglio per stato |
  | ORDERS | (user_id, status, created_at) | Lista ordini utente |
  | TRANSACTIONS | (stripe_payment_intent_id) | Lookup webhook Stripe |
  | INVOICES | (sdi_status) | Monitoraggio stato SDI |
  | CLIENTS | (status) | Filtro clienti per stato |
  | ADMIN_ACTIVITY_LOGS | (admin_id, type, created_at) | Filtro log attività |

**9. Considerazioni di Scalabilità**

**9.1 Volume Stimato**

-   ~1.000 lead/mese (caso peggiore)

-   Crescita prevista: lineare

**9.2 Strategie di Scaling**

-   Partitioning: Possibile partizionamento LEADS per generated_at

-   Archiving: Lead vecchi e esauriti archiviabili su tabella separata

-   Read Replicas: Per query di reporting pesanti

-   Caching: Redis per catalogo lead e prezzi

**9.3 Manutenzione**

-   Pulizia periodica CART_ITEMS (carrelli abbandonati)

-   Archiviazione log API dopo X mesi

-   Backup giornalieri con retention policy

**10. Note Implementative**

**10.1 Transazionalità Critica**

Le seguenti operazioni devono essere atomiche:

**Acquisto Lead Singolo:**

-   Verifica disponibilità

-   Creazione order + order_item

-   Creazione lead_sale

-   Update lead status/current_shares

-   Creazione user_lead

**Acquisto Pacchetto:**

-   Creazione order + order_item

-   Creazione user_package

**Selezione Lead da Pacchetto:**

-   Verifica used < total

-   Verifica disponibilità lead

-   Incremento used

-   Creazione lead_sale

-   Update lead status

-   Creazione user_lead

**10.2 Concorrenza**

-   Optimistic Locking su LEADS (version column) per evitare race condition

-   Row-level locking durante acquisto per consistenza slot condivisi

**10.3 Statistiche e Reporting**

Il sistema supporta dashboard e report con le seguenti metriche:

-   **KPI Dashboard Admin:** revenue e ordini (today/week/month), lead venduti, nuovi clienti, totali attivi
-   **Performance Categorie:** lead totali, venduti, disponibili, revenue, ordini, prezzo medio, sell-through rate
-   **Statistiche Province/Regioni:** lead per provincia/regione, revenue, categoria top
-   **Grafici vendite:** serie temporali revenue, ordini, lead per periodo configurabile
-   **Alert categorie:** soglie di lead disponibili con severità warning/critical
-   **Export dati:** leads, orders, clients, transactions in formato xlsx o csv

*Documento generato per il progetto Qualead\*fied*

*Versione: 2.0*
