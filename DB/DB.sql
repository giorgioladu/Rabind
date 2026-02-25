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
