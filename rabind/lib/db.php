<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/config.php';

/**
 * Classe personalizzata che estende PDOStatement.
 * PHP la istanzia automaticamente a ogni prepare().
 */
class AuditedPDOStatement extends PDOStatement
{
    protected $pdo;

    // Il costruttore riceve l'istanza PDO
    protected function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(?array $params = null): bool
    {
        $result = parent::execute($params);

        if ($result && method_exists($this->pdo, 'logToAuditTable')) {
            $sql = $this->queryString;
            if (preg_match('/^(INSERT|UPDATE|DELETE)\s+/i', trim($sql), $matches)) {
                $action_type = strtoupper($matches[1]);
                $this->pdo->logToAuditTable($action_type, $sql, $params ?? []);
            }
        }

        return $result;
    }
}

/**
 * Estensione di PDO per intercettare e registrare automaticamente
 * le operazioni di scrittura nell'audit log.
 */
class AuditedPDO extends PDO
{
    // Sovrascriviamo il costruttore per iniettare la Statement Class in modo sicuro
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);

        // FIX: Ora che l'oggetto PDO esiste ($this), possiamo passarlo alla nostra Statement Class
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['AuditedPDOStatement', [$this]]);
    }

    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs): PDOStatement|false
    {
        $this->auditDirectQuery($query);
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    public function exec(string $statement): int|false
    {
        $this->auditDirectQuery($statement);
        return parent::exec($statement);
    }

    private function auditDirectQuery(string $sql): void
    {
        if (preg_match('/^(INSERT|UPDATE|DELETE)\s+/i', trim($sql), $matches)) {
            $action_type = strtoupper($matches[1]);
            $this->logToAuditTable($action_type, $sql, []);
        }
    }

    public function logToAuditTable(string $action_type, string $sql, array $params): void
    {
        $target_table = 'unknown';
        if (preg_match('/(?:FROM|INTO|UPDATE)\s+[`"\'\s]?([\w\-]+)/i', $sql, $table_matches)) {
            $target_table = $table_matches[1];
        }

        if ($target_table === 'audit_log') {
            return;
        }

        $admin_id = $_SESSION['admin_id'] ?? null;
        $admin_username = $_SESSION['admin_username'] ?? 'SYSTEM';

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $sanitized_params = $params;
        $sensitive_keys = ['password', 'secret', 'radius_secret', 'wifi_password', 'RADIUS_DB_PASS', 'APP_DB_PASS'];
        foreach ($sanitized_params as $key => $val) {
            foreach ($sensitive_keys as $sensitive) {
                if (str_contains(strtolower($key), $sensitive)) {
                    $sanitized_params[$key] = '********';
                }
            }
        }

        $payload = json_encode($sanitized_params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $audit_sql = "INSERT INTO audit_log (admin_id, admin_username, ip, action_type, target_table, payload)
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = parent::prepare($audit_sql);
            $stmt->execute([$admin_id, $admin_username, $ip, $action_type, $target_table, $payload]);
        } catch (Exception $e) {
            if (defined('APP_DEBUG') && APP_DEBUG) {
                error_log("Audit system error: " . $e->getMessage());
            }
        }
    }
}

/* =========================
   Radius DB Connection
   ========================= */

try {
    $radiusDb = new PDO(
        "mysql:host=" . RADIUS_DB_HOST . ";dbname=" . RADIUS_DB_NAME . ";charset=utf8mb4",
        RADIUS_DB_USER,
        RADIUS_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Radius DB connection error.");
}

/* =========================
   RaBind App DB Connection
   ========================= */

try {
    // Nota: Ho rimosso l'opzione ATTR_STATEMENT_CLASS dall'array qui sotto.
    // Viene ora gestita in automatico e in modo sicuro dal costruttore di AuditedPDO.
    $appDb = new AuditedPDO(
        "mysql:host=" . APP_DB_HOST . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USER,
        APP_DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("App DB connection error.");
}