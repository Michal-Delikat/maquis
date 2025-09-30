
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

CREATE TABLE IF NOT EXISTS `round_data` (
    `active_space` INT DEFAULT 0,
    `selected_field` INT DEFAULT 0,
    `shot_today` BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `board` (
    `space_id` INT UNSIGNED NOT NULL,
    `space_name` varchar(16) NOT NULL,
    `is_safe` BOOLEAN NOT NULL DEFAULT FALSE,
    `is_field` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`space_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `board_path` (
    `path_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `space_id_start` INT unsigned NOT NULL,
    `space_id_end` INT unsigned NOT NULL,
    PRIMARY KEY (`path_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `patrol_card` (
    `card_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `card_type` VARCHAR(32) NOT NULL,
    `card_type_arg` INT UNSIGNED NOT NULL,
    `card_location` VARCHAR(32) NOT NULL,
    `card_location_arg` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `action` (
    `action_id` INT UNSIGNED NOT NULL,
    `action_name` varchar(32) NOT NULL,
    `action_description` varchar(255) NOT NULL,
    `is_safe` BOOLEAN NOT NULL DEFAULT FALSE,
    PRIMARY KEY (`action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `board_action` (
    `space_id` INT unsigned NOT NULL,
    `action_id` INT unsigned NOT NULL,
    FOREIGN KEY (`space_id`)
        REFERENCES `board`(`space_id`),
    FOREIGN KEY (`action_id`)
        REFERENCES `action`(`action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `components` (
    `name` VARCHAR(128) NOT NULL,
    `location` VARCHAR(32) NOT NULL,
    `state` VARCHAR(16),
    PRIMARY KEY (`name`)
) ENGINE=InnoDb DEFAULT CHARSET=utf8;