SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for oc_t_lzto_user_settings
-- ----------------------------
DROP TABLE IF EXISTS /*TABLE_PREFIX*/t_lzto_user_settings;
CREATE TABLE oc_t_lzto_user_settings (
  s_ip int(11) unsigned NOT NULL,
  s_name varchar(50) NOT NULL,
  s_settings text,
  dt_updated datetime NOT NULL,
  PRIMARY KEY (s_ip,s_name),
  UNIQUE KEY lzto_users_settings_key (s_ip,s_name),
  KEY lzto_users_settings_ip (s_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
