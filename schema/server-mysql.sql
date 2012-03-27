
DROP TABLE IF EXISTS oauth_session_scopes, oauth_sessions, oauth_clients, oauth_scopes;

CREATE TABLE `oauth_clients` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `client_id` varchar(32) NOT NULL DEFAULT '',
  `client_secret` varchar(32) NOT NULL DEFAULT '',
  `redirect_uri` varchar(250) NOT NULL DEFAULT '',
  `auto_approve` tinyint(1) NOT NULL DEFAULT '0',
  `autonomous` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('development','pending','approved','rejected') NOT NULL DEFAULT 'development',
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `notes` tinytext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `oauth_sessions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` varchar(32) NOT NULL DEFAULT '',
  `redirect_uri` varchar(250) NOT NULL DEFAULT '',
  `type_id` varchar(64) DEFAULT NULL,
  `type` enum('user','auto') NOT NULL DEFAULT 'user',
  `code` text,
  `access_token` varchar(50) DEFAULT '',
  `stage` enum('request','granted') NOT NULL DEFAULT 'request',
  `first_requested` int(10) unsigned NOT NULL,
  `last_updated` int(10) unsigned NOT NULL,
  `limited_access` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Used for user agent flows',
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `oauth_sessions_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `oauth_clients` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `oauth_scopes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `description` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `oauth_session_scopes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(11) unsigned NOT NULL,
  `access_token` varchar(50) NOT NULL DEFAULT '',
  `scope` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `scope` (`scope`),
  KEY `access_token` (`access_token`),
  CONSTRAINT `oauth_session_scopes_ibfk_1` FOREIGN KEY (`scope`) REFERENCES `oauth_scopes` (`scope`),
  CONSTRAINT `oauth_session_scopes_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `oauth_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;