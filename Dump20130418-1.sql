CREATE DATABASE  IF NOT EXISTS `daondb` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `daondb`;
-- MySQL dump 10.13  Distrib 5.5.16, for Win32 (x86)
--
-- Host: localhost    Database: daondb
-- ------------------------------------------------------
-- Server version	5.5.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `js_chat`
--

DROP TABLE IF EXISTS `js_chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_chat` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `chat_hash` varchar(32) NOT NULL,
  `chat_type` tinyint(4) NOT NULL DEFAULT '0',
  `chat_content` varchar(1024) NOT NULL DEFAULT '""',
  `chat_content_type` tinyint(4) NOT NULL DEFAULT '-1',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_seq` int(11) NOT NULL DEFAULT '-777',
  `room_hash` varchar(45) NOT NULL DEFAULT '""',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `msg_hash_UNIQUE` (`chat_hash`),
  KEY `creator_seq_idx` (`creator_seq`),
  KEY `type_idx` (`chat_type`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_chat_receiver`
--

DROP TABLE IF EXISTS `js_chat_receiver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_chat_receiver` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `chat_seq` int(11) NOT NULL,
  `receiver_seq` int(11) NOT NULL,
  `is_checked` tinyint(1) NOT NULL DEFAULT '0',
  `checked_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`seq`),
  KEY `receiver_seq_idx` (`receiver_seq`),
  KEY `msg_seq_idx` (`chat_seq`,`receiver_seq`)
) ENGINE=MyISAM AUTO_INCREMENT=100070 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_department`
--

DROP TABLE IF EXISTS `js_department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_department` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `dept_hash` varchar(32) NOT NULL,
  `dept_code` varchar(12) NOT NULL DEFAULT '00000000000',
  `parent_seq` int(11) DEFAULT NULL,
  `depth` tinyint(4) NOT NULL DEFAULT '-1',
  `dept_name` varchar(45) NOT NULL DEFAULT '""',
  `dept_full_path` varchar(100) NOT NULL DEFAULT '""',
  `dept_full_name` varchar(100) NOT NULL DEFAULT '""',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_terminal` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `dept_hash_UNIQUE` (`dept_hash`),
  KEY `dept_code_idx` (`dept_code`),
  KEY `parent_seq` (`parent_seq`)
) ENGINE=InnoDB AUTO_INCREMENT=12152 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_device`
--

DROP TABLE IF EXISTS `js_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_device` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `device_hash` varchar(32) NOT NULL,
  `user_seq` int(11) NOT NULL,
  `device_type` varchar(1) NOT NULL DEFAULT 'e',
  `uuid` varchar(64) NOT NULL DEFAULT '""',
  `regid` varchar(172) NOT NULL DEFAULT '""',
  `device_password` binary(32) NOT NULL,
  `permission` varchar(45) NOT NULL DEFAULT '""',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`),
  UNIQUE KEY `device_hash_UNIQUE` (`device_hash`),
  KEY `user` (`user_seq`,`device_type`,`regid`,`permission`,`is_enabled`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_document`
--

DROP TABLE IF EXISTS `js_document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_document` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `doc_hash` varchar(32) NOT NULL DEFAULT '""',
  `doc_title` varchar(45) NOT NULL DEFAULT '""',
  `doc_content` varchar(1024) NOT NULL DEFAULT '""',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_seq` int(11) NOT NULL DEFAULT '-777',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `doc_hash_UNIQUE` (`doc_hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_document_attachments`
--

DROP TABLE IF EXISTS `js_document_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_document_attachments` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `doc_seq` int(11) NOT NULL,
  `file_type` tinyint(1) NOT NULL DEFAULT '0',
  `file_name` varchar(45) NOT NULL DEFAULT '""',
  `file_path` varchar(128) NOT NULL DEFAULT '""',
  `file_hash` varchar(32) NOT NULL DEFAULT '""',
  `file_size_in_byte` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `file_hash_unq` (`file_hash`),
  KEY `msg_seq_idx` (`doc_seq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_document_forward`
--

DROP TABLE IF EXISTS `js_document_forward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_document_forward` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `doc_seq` int(11) NOT NULL,
  `forwarder_seq` int(11) NOT NULL,
  `forward_comment` varchar(200) NOT NULL DEFAULT '""',
  `forward_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`seq`),
  KEY `fwd_idx` (`doc_seq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_document_receiver`
--

DROP TABLE IF EXISTS `js_document_receiver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_document_receiver` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `doc_seq` int(11) NOT NULL,
  `receiver_seq` int(11) NOT NULL,
  `is_checked` tinyint(4) NOT NULL DEFAULT '0',
  `checked_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`seq`),
  KEY `docseqidx` (`doc_seq`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_room_chatter`
--

DROP TABLE IF EXISTS `js_room_chatter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_room_chatter` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `room_hash` varchar(45) NOT NULL,
  `user_seq` int(11) NOT NULL,
  `last_read_ts` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`seq`),
  KEY `rh_idx` (`room_hash`,`user_seq`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_survey`
--

DROP TABLE IF EXISTS `js_survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_survey` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `survey_hash` varchar(32) NOT NULL DEFAULT '""',
  `survey_title` varchar(45) NOT NULL DEFAULT '""',
  `survey_content` varchar(1024) NOT NULL DEFAULT '""',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `creator_seq` int(11) NOT NULL DEFAULT '-777',
  `open_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `close_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `survey_hash_UNIQUE` (`survey_hash`)
) ENGINE=MyISAM AUTO_INCREMENT=124 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_survey_question`
--

DROP TABLE IF EXISTS `js_survey_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_survey_question` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `survey_seq` int(11) NOT NULL,
  `question_title` varchar(45) NOT NULL DEFAULT '""',
  `question_content` varchar(200) NOT NULL DEFAULT '""',
  `is_multiple` tinyint(4) NOT NULL DEFAULT '0',
  `question_idx` tinyint(4) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`seq`),
  KEY `svyseq_idx` (`survey_seq`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_survey_question_option`
--

DROP TABLE IF EXISTS `js_survey_question_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_survey_question_option` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `question_seq` int(11) NOT NULL,
  `option_content` varchar(45) NOT NULL DEFAULT '""',
  `poll` int(11) NOT NULL DEFAULT '0',
  `option_idx` tinyint(4) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`seq`),
  KEY `q_seq_idx` (`question_seq`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_survey_receiver`
--

DROP TABLE IF EXISTS `js_survey_receiver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_survey_receiver` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `survey_seq` int(11) NOT NULL,
  `receiver_seq` int(11) NOT NULL,
  `is_checked` tinyint(4) NOT NULL DEFAULT '0',
  `checked_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_answered` tinyint(4) NOT NULL DEFAULT '0',
  `answered_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`seq`),
  KEY `survey_seq` (`survey_seq`),
  KEY `survey_chk` (`survey_seq`,`is_checked`),
  KEY `survey_ans` (`survey_seq`,`is_answered`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_user`
--

DROP TABLE IF EXISTS `js_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_user` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `user_hash` varchar(32) NOT NULL,
  `user_name` varchar(10) NOT NULL DEFAULT '""',
  `dept_seq` int(11) NOT NULL DEFAULT '-777',
  `user_rank` tinyint(4) NOT NULL DEFAULT '0',
  `user_role` varchar(16) NOT NULL DEFAULT '""',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_ts` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified_ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq`),
  UNIQUE KEY `user_hash_UNIQUE` (`user_hash`),
  KEY `department` (`dept_seq`),
  KEY `rank` (`user_rank`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `js_user_rank`
--

DROP TABLE IF EXISTS `js_user_rank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `js_user_rank` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'daondb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-04-18 23:02:58
