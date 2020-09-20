-- MySQL dump 10.13  Distrib 5.7.30, for Linux (x86_64)
--
-- Host: 192.168.86.85    Database: employees
-- ------------------------------------------------------
-- Server version	5.7.10-log

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
-- Table structure for table `adodb_test`
--

DROP TABLE IF EXISTS `adodb_test`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adodb_test` (
  `emp_no` int(11) NOT NULL,
  `date2` date DEFAULT NULL,
  `vchar1` varchar(14) DEFAULT NULL,
  `vchar2` varchar(16) DEFAULT NULL,
  `enum1` enum('M','F') DEFAULT NULL,
  `date1` date DEFAULT NULL,
  `col9` varchar(50) NOT NULL DEFAULT 'BILL',
  `json1` json DEFAULT NULL,
  `point1` point DEFAULT NULL,
  `number1` decimal(12,2) DEFAULT '0.00',
  `clob1` longtext,
  `blob1` blob,
  `time1` time DEFAULT NULL,
  `datetime1` datetime DEFAULT NULL,
  `logical1` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`emp_no`),
  KEY `NEWCOREIDX` (`vchar1`,`emp_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
