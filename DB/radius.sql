-- phpMyAdmin SQL Dump
-- version 5.2.3-1.fc43
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Creato il: Mar 11, 2026 alle 16:27
-- Versione del server: 10.11.16-MariaDB
-- Versione PHP: 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `radius`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `mac_to_user`
--

CREATE TABLE `mac_to_user` (
  `id` int(11) NOT NULL,
  `mac_address` varchar(17) NOT NULL,
  `username` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `nas`
--

CREATE TABLE `nas` (
  `id` int(10) NOT NULL,
  `nasname` varchar(128) NOT NULL,
  `shortname` varchar(32) DEFAULT NULL,
  `type` varchar(30) DEFAULT 'other',
  `ports` int(5) DEFAULT NULL,
  `secret` varchar(60) NOT NULL DEFAULT 'secret',
  `server` varchar(64) DEFAULT NULL,
  `community` varchar(50) DEFAULT NULL,
  `description` varchar(200) DEFAULT 'RADIUS Client'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `nasreload`
--

CREATE TABLE `nasreload` (
  `nasipaddress` varchar(15) NOT NULL,
  `reloadtime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `radacct`
--

CREATE TABLE `radacct` (
  `radacctid` bigint(21) NOT NULL,
  `acctsessionid` varchar(64) NOT NULL DEFAULT '',
  `acctuniqueid` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `realm` varchar(64) DEFAULT '',
  `nasipaddress` varchar(15) NOT NULL DEFAULT '',
  `nasportid` varchar(32) DEFAULT NULL,
  `nasporttype` varchar(32) DEFAULT NULL,
  `acctstarttime` datetime DEFAULT NULL,
  `acctupdatetime` datetime DEFAULT NULL,
  `acctstoptime` datetime DEFAULT NULL,
  `acctinterval` int(12) DEFAULT NULL,
  `acctsessiontime` int(12) UNSIGNED DEFAULT NULL,
  `acctauthentic` varchar(32) DEFAULT NULL,
  `connectinfo_start` varchar(128) DEFAULT NULL,
  `connectinfo_stop` varchar(128) DEFAULT NULL,
  `acctinputoctets` bigint(20) DEFAULT NULL,
  `acctoutputoctets` bigint(20) DEFAULT NULL,
  `calledstationid` varchar(50) NOT NULL DEFAULT '',
  `callingstationid` varchar(50) NOT NULL DEFAULT '',
  `acctterminatecause` varchar(32) NOT NULL DEFAULT '',
  `servicetype` varchar(32) DEFAULT NULL,
  `framedprotocol` varchar(32) DEFAULT NULL,
  `framedipaddress` varchar(15) NOT NULL DEFAULT '',
  `framedipv6address` varchar(45) NOT NULL DEFAULT '',
  `framedipv6prefix` varchar(45) NOT NULL DEFAULT '',
  `framedinterfaceid` varchar(44) NOT NULL DEFAULT '',
  `delegatedipv6prefix` varchar(45) NOT NULL DEFAULT '',
  `class` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `radcheck`
--

CREATE TABLE `radcheck` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '==',
  `value` varchar(253) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `radgroupcheck`
--

CREATE TABLE `radgroupcheck` (
  `id` int(11) UNSIGNED NOT NULL,
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT ':=',
  `value` varchar(253) NOT NULL DEFAULT '',
  `start_hour` tinyint(4) DEFAULT 0,
  `end_hour` tinyint(4) DEFAULT 24
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `radgroupcheck`
--

INSERT INTO `radgroupcheck` (`id`, `groupname`, `attribute`, `op`, `value`, `start_hour`, `end_hour`) VALUES
(1, 'basic', 'Simultaneous-Use', ':=', '1', 0, 24),
(2, 'basic', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(3, 'Guest', 'Simultaneous-Use', ':=', '1', 0, 24),
(4, 'Guest', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(5, 'medium', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(6, 'medium', 'Simultaneous-Use', ':=', '1', 0, 24),
(7, 'high', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(8, 'high', 'Simultaneous-Use', ':=', '1', 0, 24),
(9, 'basic', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(10, 'Guest', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(11, 'high', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(12, 'medium', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24);

-- --------------------------------------------------------

--
-- Struttura della tabella `radgroupreply`
--

CREATE TABLE `radgroupreply` (
  `id` int(11) UNSIGNED NOT NULL,
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '=',
  `value` varchar(253) NOT NULL DEFAULT '',
  `start_hour` tinyint(4) DEFAULT 0,
  `end_hour` tinyint(4) DEFAULT 24
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dump dei dati per la tabella `radgroupreply`
--

INSERT INTO `radgroupreply` (`id`, `groupname`, `attribute`, `op`, `value`, `start_hour`, `end_hour`) VALUES
(1, 'Guest', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(2, 'Guest', 'Mikrotik-Rate-Limit', ':=', '1M/2M', 0, 24),
(3, 'basic', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(4, 'basic', 'Mikrotik-Rate-Limit', ':=', '1M/2M', 0, 24),
(5, 'medium', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(6, 'medium', 'Mikrotik-Rate-Limit', ':=', '5M/5M', 0, 24),
(7, 'high', 'Mikrotik-Total-Limit', ':=', '4294967295', 0, 24),
(8, 'high', 'Mikrotik-Rate-Limit', ':=', '8M/10M', 0, 24),
(9, 'basic', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(10, 'Guest', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(11, 'high', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24),
(12, 'medium', 'Mikrotik-Total-Limit-Gigawords', ':=', '1', 0, 24);

-- --------------------------------------------------------

--
-- Struttura della tabella `radpostauth`
--

CREATE TABLE `radpostauth` (
  `id` int(11) NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `pass` varchar(64) NOT NULL DEFAULT '',
  `reply` varchar(32) NOT NULL DEFAULT '',
  `authdate` timestamp(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6),
  `class` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `radreply`
--

CREATE TABLE `radreply` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `attribute` varchar(64) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '=',
  `value` varchar(253) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `radusergroup`
--

CREATE TABLE `radusergroup` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `priority` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `view_active_sessions`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `view_active_sessions` (
`username` varchar(64)
,`framedipaddress` varchar(15)
,`callingstationid` varchar(50)
,`nasipaddress` varchar(15)
,`acctstarttime` datetime
,`duration` time /* mariadb-5.3 */
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `view_daily_stats`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `view_daily_stats` (
`stat_day` date
,`logins_success` bigint(21)
,`logins_fail` bigint(21)
,`upload_mb` decimal(45,4)
,`download_mb` decimal(45,4)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `view_online_detailed`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `view_online_detailed` (
`radacctid` bigint(21)
,`username` varchar(64)
,`framedipaddress` varchar(15)
,`callingstationid` varchar(50)
,`nas_name` varchar(32)
,`profile` varchar(64)
,`acctstarttime` datetime
,`uptime` time /* mariadb-5.3 */
,`session_download` bigint(20)
,`session_upload` bigint(20)
,`acctsessionid` varchar(64)
);

-- --------------------------------------------------------

--
-- Struttura stand-in per le viste `view_profile_stats`
-- (Vedi sotto per la vista effettiva)
--
CREATE TABLE `view_profile_stats` (
`groupname` varchar(64)
,`total_users` bigint(21)
);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `mac_to_user`
--
ALTER TABLE `mac_to_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mac_address` (`mac_address`),
  ADD KEY `username` (`username`);

--
-- Indici per le tabelle `nas`
--
ALTER TABLE `nas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nasname` (`nasname`);

--
-- Indici per le tabelle `nasreload`
--
ALTER TABLE `nasreload`
  ADD PRIMARY KEY (`nasipaddress`);

--
-- Indici per le tabelle `radacct`
--
ALTER TABLE `radacct`
  ADD PRIMARY KEY (`radacctid`),
  ADD UNIQUE KEY `acctuniqueid` (`acctuniqueid`),
  ADD KEY `username` (`username`),
  ADD KEY `framedipaddress` (`framedipaddress`),
  ADD KEY `framedipv6address` (`framedipv6address`),
  ADD KEY `framedipv6prefix` (`framedipv6prefix`),
  ADD KEY `framedinterfaceid` (`framedinterfaceid`),
  ADD KEY `delegatedipv6prefix` (`delegatedipv6prefix`),
  ADD KEY `acctsessionid` (`acctsessionid`),
  ADD KEY `acctsessiontime` (`acctsessiontime`),
  ADD KEY `acctstarttime` (`acctstarttime`),
  ADD KEY `acctinterval` (`acctinterval`),
  ADD KEY `acctstoptime` (`acctstoptime`),
  ADD KEY `nasipaddress` (`nasipaddress`),
  ADD KEY `class` (`class`),
  ADD KEY `radacct_user_time` (`username`,`acctstarttime`);

--
-- Indici per le tabelle `radcheck`
--
ALTER TABLE `radcheck`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));

--
-- Indici per le tabelle `radgroupcheck`
--
ALTER TABLE `radgroupcheck`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupname` (`groupname`(32));

--
-- Indici per le tabelle `radgroupreply`
--
ALTER TABLE `radgroupreply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `groupname` (`groupname`(32));

--
-- Indici per le tabelle `radpostauth`
--
ALTER TABLE `radpostauth`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `class` (`class`);

--
-- Indici per le tabelle `radreply`
--
ALTER TABLE `radreply`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));

--
-- Indici per le tabelle `radusergroup`
--
ALTER TABLE `radusergroup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`(32));

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `mac_to_user`
--
ALTER TABLE `mac_to_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `nas`
--
ALTER TABLE `nas`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `radacct`
--
ALTER TABLE `radacct`
  MODIFY `radacctid` bigint(21) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `radcheck`
--
ALTER TABLE `radcheck`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `radgroupcheck`
--
ALTER TABLE `radgroupcheck`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT per la tabella `radgroupreply`
--
ALTER TABLE `radgroupreply`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `radpostauth`
--
ALTER TABLE `radpostauth`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `radreply`
--
ALTER TABLE `radreply`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `radusergroup`
--
ALTER TABLE `radusergroup`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Struttura per vista `view_active_sessions`
--
DROP TABLE IF EXISTS `view_active_sessions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_active_sessions`  AS SELECT `radacct`.`username` AS `username`, `radacct`.`framedipaddress` AS `framedipaddress`, `radacct`.`callingstationid` AS `callingstationid`, `radacct`.`nasipaddress` AS `nasipaddress`, `radacct`.`acctstarttime` AS `acctstarttime`, timediff(current_timestamp(),`radacct`.`acctstarttime`) AS `duration` FROM `radacct` WHERE `radacct`.`acctstoptime` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `view_daily_stats`
--
DROP TABLE IF EXISTS `view_daily_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_daily_stats`  AS SELECT cast(`days`.`val_date` as date) AS `stat_day`, (select count(0) from `radpostauth` where cast(`radpostauth`.`authdate` as date) = cast(`days`.`val_date` as date) and `radpostauth`.`reply` = 'Access-Accept') AS `logins_success`, (select count(0) from `radpostauth` where cast(`radpostauth`.`authdate` as date) = cast(`days`.`val_date` as date) and `radpostauth`.`reply` = 'Access-Reject') AS `logins_fail`, (select sum(`radacct`.`acctinputoctets`) / 1048576 from `radacct` where cast(`radacct`.`acctstarttime` as date) = cast(`days`.`val_date` as date)) AS `upload_mb`, (select sum(`radacct`.`acctoutputoctets`) / 1048576 from `radacct` where cast(`radacct`.`acctstarttime` as date) = cast(`days`.`val_date` as date)) AS `download_mb` FROM (select curdate() - interval `a`.`a` + 10 * `b`.`a` + 100 * `c`.`a` day AS `val_date` from (((select 0 AS `a` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`) `a` join (select 0 AS `a` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`) `b`) join (select 0 AS `a` union all select 1 AS `1` union all select 2 AS `2` union all select 3 AS `3` union all select 4 AS `4` union all select 5 AS `5` union all select 6 AS `6` union all select 7 AS `7` union all select 8 AS `8` union all select 9 AS `9`) `c`)) AS `days` WHERE `days`.`val_date` >= curdate() - interval 6 day GROUP BY cast(`days`.`val_date` as date) ;

-- --------------------------------------------------------

--
-- Struttura per vista `view_online_detailed`
--
DROP TABLE IF EXISTS `view_online_detailed`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_online_detailed`  AS SELECT `ra`.`radacctid` AS `radacctid`, `ra`.`username` AS `username`, `ra`.`framedipaddress` AS `framedipaddress`, `ra`.`callingstationid` AS `callingstationid`, `n`.`shortname` AS `nas_name`, `rug`.`groupname` AS `profile`, `ra`.`acctstarttime` AS `acctstarttime`, timediff(current_timestamp(),`ra`.`acctstarttime`) AS `uptime`, `ra`.`acctinputoctets` AS `session_download`, `ra`.`acctoutputoctets` AS `session_upload`, `ra`.`acctsessionid` AS `acctsessionid` FROM ((`radacct` `ra` left join `nas` `n` on(`ra`.`nasipaddress` = `n`.`nasname`)) left join `radusergroup` `rug` on(`ra`.`username` = `rug`.`username`)) WHERE `ra`.`acctstoptime` is null ;

-- --------------------------------------------------------

--
-- Struttura per vista `view_profile_stats`
--
DROP TABLE IF EXISTS `view_profile_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `view_profile_stats`  AS SELECT `radusergroup`.`groupname` AS `groupname`, count(0) AS `total_users` FROM `radusergroup` GROUP BY `radusergroup`.`groupname` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
