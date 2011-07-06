<?php
global $DB;
$sCreateTableSQL = 'CREATE TABLE IF NOT EXISTS `bg_bitrixgems_custom_data` (
	`gem` VARCHAR( 100 ) NOT NULL ,
	`id` VARCHAR( 100 ) NOT NULL ,
	`data` TEXT NOT NULL ,
	PRIMARY KEY (  `gem` ,  `id` )
) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_unicode_ci;';

$DB->Query( $sCreateTableSQL );