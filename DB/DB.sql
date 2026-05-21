CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rabind_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) UNIQUE,
    type VARCHAR(20) DEFAULT 'fixed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO radgroupreply
(groupname, attribute, op, value)
VALUES
('basic','Mikrotik-Rate-Limit',':=','5M/2M'),
('medium','Mikrotik-Rate-Limit',':=','10M/5M'),
('high','Mikrotik-Rate-Limit',':=','100M/50M');


CREATE TABLE mac_to_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    mac_address VARCHAR(32) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY mac_unique (mac_address),
    INDEX user_idx (username)
);

CREATE INDEX radacct_user_time
ON radacct(username, acctstarttime);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    ip VARCHAR(45),
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (username, ip, attempt_time)
);

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,            -- ID dell'amministratore che ha eseguito l'azione (NULL se di sistema)
    admin_username VARCHAR(50),   -- Username dell'amministratore (per storico se l'utente viene rimosso)
    ip VARCHAR(45),               -- Indirizzo IP di chi ha effettuato la modifica (supporta IPv4 e IPv6)
    action_type VARCHAR(10),      -- Tipo di operazione: INSERT, UPDATE o DELETE
    target_table VARCHAR(50),     -- La tabella che è stata modificata (es. users, nas, config)
    payload LONGTEXT,             -- I parametri passati alla query codificati in JSON (e sanitizzati)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indici per ottimizzare la ricerca e i filtri nel pannello di controllo
    INDEX idx_admin (admin_id),
    INDEX idx_action (action_type),
    INDEX idx_table (target_table),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
);