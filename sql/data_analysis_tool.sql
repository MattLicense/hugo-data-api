SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `hugo_oauth` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE SCHEMA IF NOT EXISTS `hugo_reports`;
CREATE SCHEMA IF NOT EXISTS `hugo_geography`;
USE `hugo_oauth`;

-- -----------------------------------------------------
-- Table `hugo_oauth`.`user_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_oauth`.`user_roles`;

CREATE  TABLE IF NOT EXISTS `hugo_oauth`.`user_roles` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_role` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_oauth`.`users`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_oauth`.`users`;

CREATE  TABLE IF NOT EXISTS `hugo_oauth`.`users` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `user_name` VARCHAR(255) NOT NULL ,
  `user_logon` VARCHAR(255) NOT NULL ,
  `user_secret` VARCHAR(255) NOT NULL ,
  `active` TINYINT(1) NOT NULL DEFAULT 1 ,
  `user_role` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `user_role_idx` (`user_role` ASC) ,
  UNIQUE INDEX `user_logon_UNIQUE` (`user_logon` ASC) ,
  CONSTRAINT `user_role`
  FOREIGN KEY (`user_role`)
  REFERENCES `hugo_oauth`.`user_roles` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_oauth`.`token_type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_oauth`.`token_type`;

CREATE  TABLE IF NOT EXISTS `hugo_oauth`.`token_type` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `token_type` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_oauth`.`token`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_oauth`.`token`;

CREATE  TABLE IF NOT EXISTS `hugo_oauth`.`token` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `type` INT NOT NULL ,
  `user_id` INT NOT NULL ,
  `token` VARCHAR(255) NOT NULL ,
  `scope` VARCHAR(45) NULL,
  `expires` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `user_token_idx` (`user_id` ASC) ,
  INDEX `token_type_idx` (`type` ASC) ,
  CONSTRAINT unique_user_id UNIQUE (`user_id`) ,
  CONSTRAINT `user_token` FOREIGN KEY (`user_id`)
  REFERENCES `hugo_oauth`.`users` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `token_type` FOREIGN KEY (`type`)
  REFERENCES `hugo_oauth`.`token_type` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

USE `hugo_reports`;

-- -----------------------------------------------------
-- Table `hugo_reports`.`clients`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_reports`.`clients`;

CREATE  TABLE IF NOT EXISTS `hugo_reports`.`clients` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `client_name` VARCHAR(255) NOT NULL ,
  `client_website` VARCHAR(255) NULL ,
  `contact_name` VARCHAR(255) NOT NULL ,
  `contact_phone` VARCHAR(45) NOT NULL ,
  `contact_email` VARCHAR(255) NULL ,
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_reports`.`report_metadata`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_reports`.`report_metadata`;

CREATE  TABLE IF NOT EXISTS `hugo_reports`.`report_metadata` (
  `report_id` INT NOT NULL ,
  `client_id` INT NOT NULL ,
  `report_order` TEXT NOT NULL ,
  `report_about` TEXT NOT NULL ,
  `published` TINYINT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`report_id`) ,
  INDEX `report_client_idx` (`client_id` ASC) ,
  CONSTRAINT `report_client`
  FOREIGN KEY (`client_id`)
  REFERENCES `hugo_reports`.`clients` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_geography`.`lep`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_geography`.`lep`;

CREATE  TABLE IF NOT EXISTS `hugo_geography`.`lep` (
  `lep_code` VARCHAR(5) NOT NULL ,
  `lep_name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`lep_code`) )
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_geography`.`region`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_geography`.`region`;

CREATE  TABLE IF NOT EXISTS `hugo_geography`.`region` (
  `region_code` VARCHAR(5) NOT NULL ,
  `region_name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`region_code`) )
  ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `hugo_geography`.`local_authority`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `hugo_geography`.`local_authority`;

CREATE  TABLE IF NOT EXISTS `hugo_geography`.`local_authority` (
  `la_code` VARCHAR(5) NOT NULL ,
  `la_name` VARCHAR(255) NOT NULL ,
  `lep_1` VARCHAR(5) NULL ,
  `lep_2` VARCHAR(5) NULL ,
  `region` VARCHAR(5) NOT NULL ,
  PRIMARY KEY (`la_code`) ,
  INDEX `la_lep_1_idx` (`la_code`,`lep_1` ASC) ,
  INDEX `la_lep_2_idx` (`la_code`,`lep_2` ASC) ,
  INDEX `la_region_idx` (`la_code`,`region` ASC) ,
  CONSTRAINT `la_lep_1`
  FOREIGN KEY (`lep_1`)
  REFERENCES `hugo_geography`.`lep` (`lep_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `la_lep_2`
  FOREIGN KEY (`lep_2`)
  REFERENCES `hugo_geography`.`lep` (`lep_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `la_region`
  FOREIGN KEY (`region`)
  REFERENCES `hugo_geography`.`region` (`region_code`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
  ENGINE = InnoDB;

CREATE USER 'hugo'@'%' IDENTIFIED BY 'D0ubl3th1nk!';
GRANT ALL PRIVILEGES ON hugo_oauth.* TO 'hugo'@'%';
GRANT ALL PRIVILEGES ON hugo_reports.* TO 'hugo'@'%';
GRANT ALL PRIVILEGES ON hugo_geography.* TO 'hugo'@'%';

CREATE USER 'hugo'@'localhost' IDENTIFIED BY 'D0ubl3th1nk!';
GRANT ALL PRIVILEGES ON hugo_oauth.* TO 'hugo'@'localhost';
GRANT ALL PRIVILEGES ON hugo_reports.* TO 'hugo'@'localhost';
GRANT ALL PRIVILEGES ON hugo_geography.* TO 'hugo'@'localhost';

FLUSH PRIVILEGES;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;