# TYPO3 Extension Manager dump 1.0
#
# Host: localhost    Database: t3_380-dev
#--------------------------------------------------------


#
# Table structure for table 'tx_rsuserimp_presets'
#
CREATE TABLE tx_rsuserimp_presets (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	user_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	public tinyint(3) DEFAULT '0' NOT NULL,
	preset_data blob NOT NULL,
	PRIMARY KEY (uid),
	KEY lookup (uid)
);

#
# Table structure for table 'tx_rsuserimp_presets'
#
CREATE TABLE tx_rsuserimp_sessions (
	uid int(11) DEFAULT '0' NOT NULL auto_increment,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	target_pid int(11) unsigned DEFAULT '0' NOT NULL,
	user_uid int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	usertype tinytext NOT NULL,
	db_table tinytext NOT NULL,
	unique_identifier tinytext NOT NULL,
	num_imp int(11) NOT NULL default '0',
	num_drop int(11) NOT NULL default '0',
	num_upd int(11) NOT NULL default '0',	
	file tinytext NOT NULL,
	dropfile tinytext NOT NULL,
	active tinyint(1) DEFAULT '1' NOT NULL,
	deleted tinyint(1) DEFAULT '0' NOT NULL,
	session_data blob NOT NULL,
	PRIMARY KEY (uid),
	KEY lookup (uid)
);
