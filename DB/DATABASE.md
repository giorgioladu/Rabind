
**Configurazione Database RaBind
RaBind utilizza due database distinti per separare i dati di autenticazione di rete dalla logica dell'applicazione:

radius: Il database standard utilizzato da FreeRADIUS.
rabind: Il database dell'applicazione per la gestione degli amministratori e delle note sugli utenti.

**Configurazione Account Amministratore
RaBind utilizza la funzione password_hash() di PHP per la sicurezza. Non è possibile inserire una password in chiaro nel database.

Come generare la password
Puoi generare l'hash della password usando questo comando veloce da terminale (sostituisci tua_password_sicura con la tua vera password):

Bash
php -r "echo password_hash('tua_password_sicura', PASSWORD_DEFAULT);"

Permessi Utente SQL (Consigliato)
Per sicurezza, l'utente del database utilizzato da RaBind dovrebbe avere i seguenti permessi:

Su radius: SELECT, INSERT, UPDATE, DELETE 
Su rabind: SELECT, INSERT, UPDATE, DELETE 
