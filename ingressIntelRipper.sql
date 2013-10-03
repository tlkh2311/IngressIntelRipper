--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat` (
  `messageId` char(34) NOT NULL,
  `messageType` enum('SYSTEM_BROADCAST','PLAYER_GENERATED','SYSTEM_NARROWCAST') NOT NULL,
  `timestamp` double NOT NULL,
  `secure` bit(1) NOT NULL DEFAULT b'0',
  `messageText` text,
  PRIMARY KEY (`messageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `markup`
--

DROP TABLE IF EXISTS `markup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `markup` (
  `messageId` char(34) NOT NULL,
  `markupId` int(11) NOT NULL,
  `type` enum('AT_PLAYER','PLAYER','PORTAL','SECURE','SENDER','TEXT') CHARACTER SET latin1 NOT NULL,
  `value` varchar(255) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`messageId`,`markupId`),
  UNIQUE KEY `primKey` (`messageId`,`markupId`) USING HASH,
  KEY `typeidx` (`type`) USING BTREE,
  KEY `valueidx` (`value`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `messageId` char(34) NOT NULL,
  `timestamp` double NOT NULL,
  `type` enum('SYSTEM_NARROWCAST','PLAYER_GENERATED','SYSTEM_BROADCAST') NOT NULL,
  `team` enum('ALIENS','ENLIGHTENED','RESISTANCE') DEFAULT NULL,
  `secure` tinyint(1) NOT NULL DEFAULT '0',
  `sender` char(35) DEFAULT NULL,
  `target` char(35) DEFAULT NULL COMMENT 'A portal guid',
  PRIMARY KEY (`messageId`),
  UNIQUE KEY `messageId` (`messageId`) USING HASH,
  KEY `timestamp` (`timestamp`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `guid` char(34) NOT NULL,
  `name` varchar(255) NOT NULL,
  `team` enum('ALIENS','ENLIGHTENED','RESISTANCE') NOT NULL,
  `level` tinyint(4) NOT NULL,
  `lastUpdate` datetime DEFAULT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `portals`
--

DROP TABLE IF EXISTS `portals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portals` (
  `guid` char(35) NOT NULL,
  `name` varchar(255) NOT NULL,
  `team` enum('ALIENS','ENLIGHTENED','RESISTANCE') NOT NULL,
  `address` varchar(255) NOT NULL,
  `latE6` int(11) NOT NULL,
  `lngE6` int(11) NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;