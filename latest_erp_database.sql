-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: erp2
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `annual_budget_items`
--

DROP TABLE IF EXISTS `annual_budget_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annual_budget_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `annual_budget_id` bigint(20) unsigned NOT NULL,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `annual_budget_items_annual_budget_id_foreign` (`annual_budget_id`),
  CONSTRAINT `annual_budget_items_annual_budget_id_foreign` FOREIGN KEY (`annual_budget_id`) REFERENCES `annual_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_budget_items`
--

LOCK TABLES `annual_budget_items` WRITE;
/*!40000 ALTER TABLE `annual_budget_items` DISABLE KEYS */;
INSERT INTO `annual_budget_items` VALUES (1,1,'Events & Summits','National Industry Summit Venue & Staging',3500000.00,'Vigyan Bhawan & Pragati Maidan booking and AV staging','2026-11-21 06:46:16','2026-11-21 06:46:16'),(2,1,'Exhibition & Fabrication','Pavilion Construction & Branding',2500000.00,'Contracted booth fabrication and thematic branding','2026-11-21 06:46:16','2026-11-21 06:46:16'),(3,1,'Logistics & Delegates','Delegate Management & Speaker Logistics',1500000.00,'VIP speaker hospitality and registration portals','2026-11-21 06:46:16','2026-11-21 06:46:16'),(4,1,'Outreach','Regional Industry Roundtables',1000000.00,'State chapter tier-2 manufacturing conclaves','2026-11-21 06:46:17','2026-11-21 06:46:17'),(5,2,'Cloud & Hosting','Enterprise Cloud Hosting & Database Cluster',1200000.00,'AWS & Google Cloud high-availability hosting','2026-11-21 06:46:17','2026-11-21 06:46:17'),(6,2,'Cybersecurity','Perimeter Firewall & SOC Subscriptions',600000.00,'Next-gen threat intelligence and SSL certs','2026-11-21 06:46:17','2026-11-21 06:46:17'),(7,2,'Hardware','Core Network Switch & Workstation Refresh',400000.00,'Server rack UPS replacement and engineering terminals','2026-11-21 06:46:17','2026-11-21 06:46:17'),(8,3,'Facilities & AMC','HVAC, Elevator & Electrical AMCs',1600000.00,'Annual comprehensive maintenance contracts','2026-11-21 06:46:17','2026-11-21 06:46:17'),(9,3,'Utilities','Commercial Power & High-Speed Telecoms',1000000.00,'Grid electricity, DG diesel, and leased line internet','2026-11-21 06:46:17','2026-11-21 06:46:17'),(10,3,'Services','Security, Housekeeping & Courier Contracts',600000.00,'24x7 security personnel and facility sanitation','2026-11-21 06:46:17','2026-11-21 06:46:17'),(11,4,'Digital Campaigns','Omnichannel Digital Campaigns & Social Media',700000.00,'LinkedIn sponsored outreach and policy campaign ads','2026-11-21 06:46:17','2026-11-21 06:46:17'),(12,4,'Publications','ASSOCHAM Annual Economic Outlook Reports',500000.00,'Research monograph printing and digital distribution','2026-11-21 06:46:17','2026-11-21 06:46:17'),(13,4,'Media & PR','National Press Bureau & PR Wire Retainer',300000.00,'National press briefing releases and media tracking','2026-11-21 06:46:17','2026-11-21 06:46:17'),(14,5,'Insurance','Group Mediclaim & Accidental Cover Subsidy',700000.00,'Annual premium co-pay for full-time staff','2026-11-21 06:46:17','2026-11-21 06:46:17'),(15,5,'L&D Programs','Executive Leadership & Compliance Training',300000.00,'Workshops on ESG, taxation, and legal compliance','2026-11-21 06:46:17','2026-11-21 06:46:17'),(16,5,'Staff Welfare','Annual Health Checkups & Recognition Awards',200000.00,'Preventive health screening and quarterly awards','2026-11-21 06:46:17','2026-11-21 06:46:17'),(17,6,'Audit & Advisory','Statutory Audit Fees & GST Advisory',500000.00,'Independent chartered accountant retainer and tax filings','2026-11-21 06:46:17','2026-11-21 06:46:17'),(18,6,'Software','Enterprise Accounting & Payroll Cloud License',250000.00,'Annual SaaS licenses for accounting ledger','2026-11-21 06:46:17','2026-11-21 06:46:17'),(19,6,'Compliance','ROC & Regulatory Filing Fees',150000.00,'Ministry of Corporate Affairs annual returns','2026-11-21 06:46:17','2026-11-21 06:46:17'),(20,7,'Cloud Infrastructure','Multi-Region Hybrid Cloud Hosting',1400000.00,'Redundant disaster recovery cluster','2026-11-21 06:46:17','2026-11-21 06:46:17'),(21,7,'Cybersecurity','Zero-Trust Endpoint Protection',700000.00,'EDR agents and automated vulnerability scanning','2026-11-21 06:46:17','2026-11-21 06:46:17'),(22,7,'Hardware Refresh','Conference Room AV & Smart Boards',500000.00,'Hybrid meeting room modernization','2026-11-21 06:46:17','2026-11-21 06:46:17'),(23,8,'Conclaves','National Infrastructure Summit (Bharat Mandapam)',4500000.00,'3-day global convention with exhibition','2026-11-21 06:46:17','2026-11-21 06:46:17'),(24,8,'Conclaves','Global Clean Energy & Biofuel Forum',3000000.00,'International delegations and diplomatic sessions','2026-11-21 06:46:17','2026-11-21 06:46:17'),(25,8,'Regional Summits','Tier-2 Industrial Hub Series (6 States)',2300000.00,'State-level MSME roundtables','2026-11-21 06:46:17','2026-11-21 06:46:17'),(26,9,'Digital Media','Global Campaign & Digital Content Studio',1100000.00,'Video podcasts and thought leadership reels','2026-11-21 06:46:17','2026-11-21 06:46:17'),(27,9,'Broadcast','Business News Channel Broadcast Partnerships',1000000.00,'Live summit streaming and panel coverage','2026-11-21 06:46:17','2026-11-21 06:46:17'),(28,10,'Capital Overhaul','Central Chiller Plant Replacement',2800000.00,'Industrial HVAC replacement','2026-11-21 06:46:17','2026-11-21 06:46:17'),(29,10,'Solar Energy','50 kW Rooftop Solar PV Installation',2000000.00,'Renewable energy transition','2026-11-21 06:46:17','2026-11-21 06:46:17');
/*!40000 ALTER TABLE `annual_budget_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `annual_budgets`
--

DROP TABLE IF EXISTS `annual_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `annual_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `budget_number` varchar(60) NOT NULL,
  `department` varchar(100) NOT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `proposed_amount` decimal(14,2) NOT NULL,
  `approved_amount` decimal(14,2) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending_cfo',
  `current_approval_level` int(10) unsigned NOT NULL DEFAULT 1,
  `cfo_approved_at` timestamp NULL DEFAULT NULL,
  `cfo_remarks` text DEFAULT NULL,
  `sg_approved_at` timestamp NULL DEFAULT NULL,
  `sg_remarks` text DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `department_budget_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `annual_budgets_budget_number_unique` (`budget_number`),
  KEY `annual_budgets_submitted_by_foreign` (`submitted_by`),
  KEY `annual_budgets_department_budget_id_foreign` (`department_budget_id`),
  KEY `annual_budgets_department_fiscal_year_index` (`department`,`fiscal_year`),
  KEY `annual_budgets_status_index` (`status`),
  CONSTRAINT `annual_budgets_department_budget_id_foreign` FOREIGN KEY (`department_budget_id`) REFERENCES `department_budgets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `annual_budgets_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `annual_budgets`
--

LOCK TABLES `annual_budgets` WRITE;
/*!40000 ALTER TABLE `annual_budgets` DISABLE KEYS */;
INSERT INTO `annual_budgets` VALUES (1,'AB-26-27-PROJ-001','Projects','FY26-27',3,8500000.00,8500000.00,'Comprehensive national project delivery budget covering industry summits, state roundtables, and international trade missions.','approved',2,'2026-05-21 06:46:16','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:16','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:16',1,'2026-11-21 06:46:16','2026-11-21 06:46:16'),(2,'AB-26-27-IT-001','IT','FY26-27',3,2400000.00,2200000.00,'Core digital infrastructure, secure multi-region cloud services, enterprise cyber defense subscriptions, and hardware life-cycle refresh.','approved',2,'2026-05-21 06:46:17','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:17','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:17',2,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(3,'AB-26-27-OPER-001','Operations','FY26-27',10,3500000.00,3200000.00,'Institutional headquarters operational upkeep, facilities maintenance, power backup, AMC contracts, and administrative utilities.','approved',2,'2026-05-21 06:46:17','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:17','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:17',5,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(4,'AB-26-27-MKT-001','Marketing','FY26-27',3,1500000.00,1500000.00,'Institutional brand amplification, digital engagement campaigns, economic research publications, and national media relations.','approved',2,'2026-05-21 06:46:17','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:17','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:17',3,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(5,'AB-26-27-HR-001','HR','FY26-27',3,1200000.00,1200000.00,'Group employee health insurance policies, corporate learning & leadership workshops, talent onboarding, and staff welfare.','approved',2,'2026-05-21 06:46:17','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:17','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:17',6,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(6,'AB-26-27-FIN-001','Finance','FY26-27',3,900000.00,900000.00,'Statutory audit retainers, GST advisory consultants, accounting software subscriptions, and corporate compliance filings.','approved',2,'2026-05-21 06:46:17','Financial run-rates vetted and confirmed aligned with institutional priorities. Recommended for full sanction.','2026-05-23 06:46:17','Approved and sanctioned for FY26-27 departmental operation.','2026-05-23 06:46:17',4,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(7,'AB-27-28-IT-001','IT','FY27-28',3,2800000.00,2600000.00,'FY27-28 IT strategic roadmap: Hybrid cloud scalability, zero-trust endpoint security, automated data warehouse, and modern video-conferencing suites.','pending_sg',2,'2026-11-19 06:46:17','Reviewed against projected institutional revenue. Rationalized hardware refresh to ₹26.0 Lakhs. Endorsed for Level 2 sanction.',NULL,NULL,NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(8,'AB-27-28-PROJ-001','Projects','FY27-28',3,9800000.00,NULL,'FY27-28 flagship summits including National Infrastructure Conclave, Global Biofuel Partnership, and G20 Legacy Business Forums.','pending_cfo',1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(9,'AB-27-28-MKT-001','Marketing','FY27-28',3,2100000.00,NULL,'Expanded international digital branding, podcasts, global economic digests, and television event broadcast sponsorships.','on_hold',1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(10,'AB-27-28-OPER-001','Operations','FY27-28',10,4800000.00,NULL,'Total building HVAC chiller overhaul, rooftop solar grid installation, and executive cafeteria refurbishment.','rejected',1,NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17');
/*!40000 ALTER TABLE `annual_budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `approvable_type` varchar(255) NOT NULL,
  `approvable_id` bigint(20) unsigned NOT NULL,
  `level` tinyint(3) unsigned NOT NULL,
  `role_required` varchar(255) NOT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approvals_approvable_type_approvable_id_index` (`approvable_type`,`approvable_id`),
  KEY `approvals_approver_id_foreign` (`approver_id`),
  CONSTRAINT `approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=226 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
INSERT INTO `approvals` VALUES (5,'App\\Models\\PurchaseRequisition',1,1,'pr_approver_l1',4,'approved','OK by Basu (L1)','2026-08-07 04:23:56','2026-08-07 04:20:11','2026-08-07 04:23:56'),(6,'App\\Models\\PurchaseRequisition',1,2,'pr_approver_l2',5,'approved','OK by Ambika (L2)','2026-08-07 04:23:58','2026-08-07 04:20:11','2026-08-07 04:23:58'),(7,'App\\Models\\PurchaseRequisition',1,3,'cfo',8,'approved','OK by CFO (L3)','2026-08-07 04:24:00','2026-08-07 04:20:11','2026-08-07 04:24:00'),(8,'App\\Models\\PurchaseRequisition',1,4,'sg',9,'approved','OK by SG (L4)','2026-08-07 04:24:01','2026-08-07 04:20:11','2026-08-07 04:24:01'),(9,'App\\Models\\PurchaseRequisition',2,1,'pr_approver_l1',4,'rejected','Not needed this quarter','2026-08-07 04:24:20','2026-08-07 04:24:18','2026-08-07 04:24:20'),(10,'App\\Models\\PurchaseRequisition',2,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-08-07 04:24:18','2026-08-07 04:24:18'),(11,'App\\Models\\PurchaseRequisition',2,3,'cfo',NULL,'pending',NULL,NULL,'2026-08-07 04:24:18','2026-08-07 04:24:18'),(12,'App\\Models\\PurchaseRequisition',2,4,'sg',NULL,'pending',NULL,NULL,'2026-08-07 04:24:18','2026-08-07 04:24:18'),(17,'App\\Models\\CostComparativeStatement',1,1,'admin_head',3,'approved','L1 vendor confirmed','2026-08-07 04:39:51','2026-08-07 04:39:26','2026-08-07 04:39:51'),(18,'App\\Models\\PurchaseOrder',1,1,'admin_head',3,'approved','Non-IT low value, approved','2026-08-07 04:48:22','2026-08-07 04:47:59','2026-08-07 04:48:22'),(19,'App\\Models\\PurchaseRequisition',3,1,'pr_approver_l1',4,'approved','OK','2026-08-07 04:48:25','2026-08-07 04:48:23','2026-08-07 04:48:25'),(20,'App\\Models\\PurchaseRequisition',3,2,'pr_approver_l2',5,'approved','OK','2026-08-07 04:48:27','2026-08-07 04:48:23','2026-08-07 04:48:27'),(21,'App\\Models\\PurchaseRequisition',3,3,'cfo',8,'approved','OK','2026-08-07 04:48:28','2026-08-07 04:48:23','2026-08-07 04:48:28'),(22,'App\\Models\\PurchaseRequisition',3,4,'sg',9,'approved','OK','2026-08-07 04:48:30','2026-08-07 04:48:23','2026-08-07 04:48:30'),(23,'App\\Models\\CostComparativeStatement',2,1,'admin_head',3,'approved','OK','2026-08-07 04:48:54','2026-08-07 04:48:52','2026-08-07 04:48:54'),(24,'App\\Models\\PurchaseOrder',2,1,'admin_head',3,'approved','Approved for issuance.','2026-11-19 14:09:34','2026-08-07 04:49:13','2026-11-19 14:09:34'),(25,'App\\Models\\PurchaseOrder',2,2,'cfo',NULL,'pending','Reset to pending due to post-approval edit.',NULL,'2026-08-07 04:49:13','2026-11-06 23:19:35'),(26,'App\\Models\\PurchaseOrder',2,3,'sg',NULL,'pending','Reset to pending due to post-approval edit.',NULL,'2026-08-07 04:49:13','2026-11-06 23:19:35'),(27,'App\\Models\\PurchaseRequisition',4,1,'pr_approver_l1',4,'approved','OK','2026-08-07 04:50:28','2026-08-07 04:50:26','2026-08-07 04:50:28'),(28,'App\\Models\\PurchaseRequisition',4,2,'pr_approver_l2',5,'approved','OK','2026-08-07 04:50:29','2026-08-07 04:50:26','2026-08-07 04:50:29'),(29,'App\\Models\\PurchaseRequisition',4,3,'cfo',8,'approved','OK','2026-08-07 04:50:31','2026-08-07 04:50:26','2026-08-07 04:50:31'),(30,'App\\Models\\PurchaseRequisition',4,4,'sg',9,'approved','OK','2026-08-07 04:50:32','2026-08-07 04:50:26','2026-08-07 04:50:32'),(31,'App\\Models\\CostComparativeStatement',3,1,'admin_head',3,'approved','OK','2026-08-07 04:51:00','2026-08-07 04:50:59','2026-08-07 04:51:00'),(32,'App\\Models\\PurchaseOrder',3,1,'admin_head',NULL,'approved','Auto-approved via Exception #1 (PO Force Close).',NULL,'2026-08-07 04:51:02','2026-08-07 06:26:41'),(33,'App\\Models\\PurchaseRequisition',5,1,'pr_approver_l1',4,'approved','OK','2026-08-07 04:57:42','2026-08-07 04:57:40','2026-08-07 04:57:42'),(34,'App\\Models\\PurchaseRequisition',5,2,'pr_approver_l2',5,'approved','OK','2026-08-07 04:57:43','2026-08-07 04:57:40','2026-08-07 04:57:43'),(35,'App\\Models\\PurchaseRequisition',5,3,'cfo',8,'approved','OK','2026-08-07 04:57:45','2026-08-07 04:57:40','2026-08-07 04:57:45'),(36,'App\\Models\\PurchaseRequisition',5,4,'sg',9,'approved','OK','2026-08-07 04:57:47','2026-08-07 04:57:40','2026-08-07 04:57:47'),(37,'App\\Models\\CostComparativeStatement',4,1,'admin_head',3,'approved','OK','2026-08-07 04:57:52','2026-08-07 04:57:50','2026-08-07 04:57:52'),(38,'App\\Models\\PurchaseOrder',4,1,'admin_head',3,'approved','OK','2026-08-07 04:57:55','2026-08-07 04:57:53','2026-08-07 04:57:55'),(39,'App\\Models\\Grn',1,1,'hod',10,'approved','Qty and quality verified','2026-08-07 05:00:37','2026-08-07 05:00:04','2026-08-07 05:00:37'),(40,'App\\Models\\Grn',2,1,'hod',10,'approved','Qty and quality verified','2026-08-07 05:00:37','2026-08-07 05:00:06','2026-08-07 05:00:37'),(41,'App\\Models\\Trf',1,1,'asg1,asg2',7,'approved','Approved for domestic travel','2026-08-07 05:07:55','2026-08-07 05:07:52','2026-08-07 05:07:55'),(42,'App\\Models\\Trf',2,1,'sg',9,'pushed_back','Need updated visa cost estimate','2026-08-07 05:08:20','2026-08-07 05:08:17','2026-08-07 05:08:20'),(43,'App\\Models\\Reimbursement',1,1,'cfo',8,'approved',NULL,'2026-08-18 07:15:30','2026-08-07 05:31:01','2026-08-18 07:15:30'),(44,'App\\Models\\Reimbursement',2,1,'sg',NULL,'pending',NULL,NULL,'2026-08-07 05:31:04','2026-08-07 05:31:04'),(45,'App\\Models\\Trf',3,1,'asg1,asg2',7,'approved','OK','2026-08-07 05:33:52','2026-08-07 05:33:50','2026-08-07 05:33:52'),(46,'App\\Models\\Reimbursement',3,1,'sg',9,'approved','Confirmed','2026-08-07 05:33:57','2026-08-07 05:33:54','2026-08-07 05:33:57'),(47,'App\\Models\\Trf',4,1,'asg1,asg2',6,'approved','OK','2026-08-07 05:34:25','2026-08-07 05:34:22','2026-08-07 05:34:25'),(48,'App\\Models\\Reimbursement',4,1,'cfo',8,'approved','Settled','2026-08-07 05:46:09','2026-08-07 05:46:07','2026-08-07 05:46:09'),(49,'App\\Models\\Trf',5,1,'asg1,asg2',NULL,'pending',NULL,NULL,'2026-08-07 05:46:11','2026-08-07 05:46:11'),(50,'App\\Models\\PurchaseRequisition',1,1,'pr_approver_l1',4,'approved',NULL,'2026-08-14 11:50:09','2026-08-13 11:58:00','2026-08-14 11:50:09'),(51,'App\\Models\\PurchaseRequisition',1,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-08-13 11:58:00','2026-08-13 11:58:00'),(52,'App\\Models\\PurchaseRequisition',1,3,'cfo',NULL,'pending',NULL,NULL,'2026-08-13 11:58:00','2026-08-13 11:58:00'),(53,'App\\Models\\PurchaseRequisition',1,4,'sg',NULL,'pending',NULL,NULL,'2026-08-13 11:58:00','2026-08-13 11:58:00'),(54,'App\\Models\\Reimbursement',1,1,'sg',NULL,'pending',NULL,NULL,'2026-08-13 13:19:04','2026-08-13 13:19:04'),(55,'App\\Models\\PurchaseRequisition',2,1,'pr_approver_l1',4,'approved','cvbnm','2026-08-14 11:49:11','2026-08-13 13:21:03','2026-08-14 11:49:11'),(56,'App\\Models\\PurchaseRequisition',2,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-08-13 13:21:03','2026-08-13 13:21:03'),(57,'App\\Models\\PurchaseRequisition',2,3,'cfo',NULL,'pending',NULL,NULL,'2026-08-13 13:21:03','2026-08-13 13:21:03'),(58,'App\\Models\\PurchaseRequisition',2,4,'sg',NULL,'pending',NULL,NULL,'2026-08-13 13:21:03','2026-08-13 13:21:03'),(59,'App\\Models\\Trf',1,1,'asg1,asg2',6,'approved',NULL,'2026-08-18 06:50:23','2026-08-13 13:21:45','2026-08-18 06:50:23'),(60,'App\\Models\\PurchaseRequisition',3,1,'pr_approver_l1',4,'approved',NULL,'2026-08-18 05:14:03','2026-08-18 05:10:56','2026-08-18 05:14:03'),(61,'App\\Models\\PurchaseRequisition',3,2,'pr_approver_l2',5,'approved',NULL,'2026-08-18 05:16:25','2026-08-18 05:10:56','2026-08-18 05:16:25'),(62,'App\\Models\\PurchaseRequisition',3,3,'cfo',8,'approved',NULL,'2026-08-18 05:18:13','2026-08-18 05:10:56','2026-08-18 05:18:13'),(63,'App\\Models\\PurchaseRequisition',3,4,'sg',9,'approved',NULL,'2026-08-18 05:19:45','2026-08-18 05:10:56','2026-08-18 05:19:45'),(64,'App\\Models\\Vendor',1,1,'admin_head',3,'approved',NULL,'2026-08-18 06:17:04','2026-08-18 05:50:53','2026-08-18 06:17:04'),(65,'App\\Models\\Vendor',1,2,'asg1',6,'approved',NULL,'2026-08-18 06:17:45','2026-08-18 05:50:53','2026-08-18 06:17:45'),(66,'App\\Models\\Vendor',1,3,'asg2',7,'approved',NULL,'2026-08-18 06:18:30','2026-08-18 05:50:53','2026-08-18 06:18:30'),(67,'App\\Models\\Vendor',1,4,'cfo',8,'approved',NULL,'2026-08-18 06:19:30','2026-08-18 05:50:53','2026-08-18 06:19:30'),(68,'App\\Models\\CostComparativeStatement',1,1,'admin_head',3,'approved',NULL,'2026-08-18 06:42:22','2026-08-18 06:25:15','2026-08-18 06:42:22'),(69,'App\\Models\\PurchaseOrder',1,1,'admin_head',3,'approved',NULL,'2026-08-18 06:46:03','2026-08-18 06:44:08','2026-08-18 06:46:03'),(70,'App\\Models\\Trf',2,1,'sg',9,'approved',NULL,'2026-08-18 06:51:16','2026-08-18 06:49:06','2026-08-18 06:51:16'),(71,'App\\Models\\Vendor',2,1,'admin_head',3,'approved','cvbnm','2026-09-18 06:44:06','2026-09-18 06:23:10','2026-09-18 06:44:06'),(72,'App\\Models\\Vendor',2,2,'asg1',6,'rejected','sxdcfvgbh','2026-09-18 07:09:05','2026-09-18 06:23:10','2026-09-18 07:09:05'),(73,'App\\Models\\Vendor',2,3,'asg2',NULL,'pending',NULL,NULL,'2026-09-18 06:23:10','2026-09-18 06:23:10'),(74,'App\\Models\\Vendor',2,4,'cfo',NULL,'pending',NULL,NULL,'2026-09-18 06:23:10','2026-09-18 06:23:10'),(75,'App\\Models\\Vendor',3,1,'admin_head',3,'approved',NULL,'2026-09-22 07:14:37','2026-09-22 07:04:26','2026-09-22 07:14:37'),(76,'App\\Models\\Vendor',3,2,'asg1',6,'approved',NULL,'2026-09-22 07:15:46','2026-09-22 07:04:26','2026-09-22 07:15:46'),(77,'App\\Models\\Vendor',3,3,'asg2',7,'approved',NULL,'2026-09-22 07:17:11','2026-09-22 07:04:26','2026-09-22 07:17:11'),(78,'App\\Models\\Vendor',3,4,'cfo',8,'approved',NULL,'2026-09-22 07:20:30','2026-09-22 07:04:26','2026-09-22 07:20:30'),(83,'App\\Models\\PurchaseRequisition',4,1,'pr_approver_l1',4,'approved',NULL,'2026-10-23 07:39:58','2026-09-29 05:42:20','2026-10-23 07:39:58'),(84,'App\\Models\\PurchaseRequisition',4,2,'pr_approver_l2',5,'approved',NULL,'2026-10-23 07:40:08','2026-09-29 05:42:20','2026-10-23 07:40:08'),(85,'App\\Models\\PurchaseRequisition',4,3,'cfo',8,'approved',NULL,'2026-11-02 06:39:56','2026-09-29 05:42:20','2026-11-02 06:39:56'),(86,'App\\Models\\PurchaseRequisition',4,4,'sg',9,'approved',NULL,'2026-11-02 06:41:40','2026-09-29 05:42:20','2026-11-02 06:41:40'),(87,'App\\Models\\Reimbursement',2,1,'sg',NULL,'pending',NULL,NULL,'2026-10-16 06:54:22','2026-10-16 06:54:22'),(88,'App\\Models\\Reimbursement',3,1,'sg',NULL,'pending',NULL,NULL,'2026-10-16 06:58:09','2026-10-16 06:58:09'),(89,'App\\Models\\PurchaseRequisition',5,1,'pr_approver_l1',4,'approved',NULL,'2026-10-23 07:15:27','2026-10-23 07:07:41','2026-10-23 07:15:27'),(90,'App\\Models\\PurchaseRequisition',5,2,'pr_approver_l2',5,'approved',NULL,'2026-10-23 07:16:46','2026-10-23 07:07:41','2026-10-23 07:16:46'),(91,'App\\Models\\PurchaseRequisition',5,3,'cfo',NULL,'pending',NULL,NULL,'2026-10-23 07:07:41','2026-10-23 07:07:41'),(92,'App\\Models\\PurchaseRequisition',5,4,'sg',NULL,'pending',NULL,NULL,'2026-10-23 07:07:41','2026-10-23 07:07:41'),(93,'App\\Models\\PurchaseRequisition',6,1,'pr_approver_l1',4,'rejected','Price to be renegotiated','2026-11-02 05:51:39','2026-11-02 05:50:50','2026-11-02 05:51:39'),(94,'App\\Models\\PurchaseRequisition',6,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-11-02 05:50:50','2026-11-02 05:50:50'),(95,'App\\Models\\PurchaseRequisition',6,3,'cfo',NULL,'pending',NULL,NULL,'2026-11-02 05:50:50','2026-11-02 05:50:50'),(96,'App\\Models\\PurchaseRequisition',6,4,'sg',NULL,'pending',NULL,NULL,'2026-11-02 05:50:50','2026-11-02 05:50:50'),(97,'App\\Models\\PurchaseRequisition',7,1,'pr_approver_l1',4,'approved','Okay','2026-11-02 06:09:51','2026-11-02 06:07:41','2026-11-02 06:09:51'),(98,'App\\Models\\PurchaseRequisition',7,2,'pr_approver_l2',5,'approved',NULL,'2026-11-02 06:37:40','2026-11-02 06:07:41','2026-11-02 06:37:40'),(99,'App\\Models\\PurchaseRequisition',7,3,'cfo',8,'approved',NULL,'2026-11-02 06:38:43','2026-11-02 06:07:41','2026-11-02 06:38:43'),(100,'App\\Models\\PurchaseRequisition',7,4,'sg',9,'approved',NULL,'2026-11-02 06:40:12','2026-11-02 06:07:41','2026-11-02 06:40:12'),(101,'App\\Models\\Vendor',5,1,'admin_head',3,'approved',NULL,'2026-11-04 09:58:35','2026-11-04 09:46:21','2026-11-04 09:58:35'),(102,'App\\Models\\Vendor',5,2,'asg1',6,'approved',NULL,'2026-11-04 09:59:17','2026-11-04 09:46:21','2026-11-04 09:59:17'),(103,'App\\Models\\Vendor',5,3,'asg2',7,'approved',NULL,'2026-11-04 10:00:23','2026-11-04 09:46:21','2026-11-04 10:00:23'),(104,'App\\Models\\Vendor',5,4,'cfo',8,'approved',NULL,'2026-11-04 10:01:57','2026-11-04 09:46:21','2026-11-04 10:01:57'),(105,'App\\Models\\Vendor',6,1,'admin_head',3,'approved',NULL,'2026-11-04 09:54:30','2026-11-04 09:52:16','2026-11-04 09:54:30'),(106,'App\\Models\\Vendor',6,2,'asg1',6,'approved',NULL,'2026-11-04 09:56:22','2026-11-04 09:52:16','2026-11-04 09:56:22'),(107,'App\\Models\\Vendor',6,3,'asg2',7,'approved',NULL,'2026-11-04 09:59:56','2026-11-04 09:52:16','2026-11-04 09:59:56'),(108,'App\\Models\\Vendor',6,4,'cfo',8,'approved',NULL,'2026-11-04 10:01:46','2026-11-04 09:52:16','2026-11-04 10:01:46'),(109,'App\\Models\\PurchaseRequisition',8,1,'pr_approver_l1',4,'approved',NULL,'2026-11-04 10:06:53','2026-11-04 10:06:07','2026-11-04 10:06:53'),(110,'App\\Models\\PurchaseRequisition',8,2,'pr_approver_l2',5,'approved',NULL,'2026-11-04 10:07:59','2026-11-04 10:06:07','2026-11-04 10:07:59'),(111,'App\\Models\\PurchaseRequisition',8,3,'cfo',8,'approved',NULL,'2026-11-04 10:08:40','2026-11-04 10:06:07','2026-11-04 10:08:40'),(112,'App\\Models\\PurchaseRequisition',8,4,'sg',9,'approved','Executive sanction accorded. Handover to procurement buyer for immediate RFQ dispatch.','2026-11-04 10:09:45','2026-11-04 10:06:07','2026-11-17 07:21:59'),(113,'App\\Models\\CostComparativeStatement',2,1,'admin_head',3,'approved',NULL,'2026-11-04 10:29:39','2026-11-04 10:21:55','2026-11-04 10:29:39'),(114,'App\\Models\\Vendor',7,1,'admin_head',3,'approved',NULL,'2026-11-04 10:43:00','2026-11-04 10:40:26','2026-11-04 10:43:00'),(115,'App\\Models\\Vendor',7,2,'asg1',6,'approved',NULL,'2026-11-04 10:43:58','2026-11-04 10:40:26','2026-11-04 10:43:58'),(116,'App\\Models\\Vendor',7,3,'asg2',7,'approved',NULL,'2026-11-04 10:45:37','2026-11-04 10:40:26','2026-11-04 10:45:37'),(117,'App\\Models\\Vendor',7,4,'cfo',8,'approved',NULL,'2026-11-04 10:46:25','2026-11-04 10:40:26','2026-11-04 10:46:25'),(122,'App\\Models\\PurchaseRequisition',9,1,'pr_approver_l1',4,'approved',NULL,'2026-11-04 10:58:40','2026-11-04 10:56:18','2026-11-04 10:58:40'),(123,'App\\Models\\PurchaseRequisition',9,2,'pr_approver_l2',5,'approved',NULL,'2026-11-04 11:03:19','2026-11-04 10:56:18','2026-11-04 11:03:19'),(124,'App\\Models\\PurchaseRequisition',9,3,'cfo',8,'approved',NULL,'2026-11-04 11:04:25','2026-11-04 10:56:18','2026-11-04 11:04:25'),(125,'App\\Models\\PurchaseRequisition',9,4,'sg',9,'approved',NULL,'2026-11-04 11:05:05','2026-11-04 10:56:18','2026-11-04 11:05:05'),(126,'App\\Models\\CostComparativeStatement',3,1,'admin_head',3,'approved',NULL,'2026-11-04 12:00:15','2026-11-04 11:58:58','2026-11-04 12:00:15'),(127,'App\\Models\\PurchaseRequisition',10,1,'pr_approver_l1',4,'approved',NULL,'2026-11-04 18:07:52','2026-11-04 18:06:53','2026-11-04 18:07:52'),(128,'App\\Models\\PurchaseRequisition',10,2,'pr_approver_l2',5,'approved',NULL,'2026-11-04 18:17:54','2026-11-04 18:06:53','2026-11-04 18:17:54'),(129,'App\\Models\\PurchaseRequisition',10,3,'cfo',8,'approved',NULL,'2026-11-04 18:20:11','2026-11-04 18:06:53','2026-11-04 18:20:11'),(130,'App\\Models\\PurchaseRequisition',10,4,'sg',9,'approved',NULL,'2026-11-04 18:20:44','2026-11-04 18:06:53','2026-11-04 18:20:44'),(131,'App\\Models\\CostComparativeStatement',4,1,'admin_head',3,'approved',NULL,'2026-11-04 18:30:35','2026-11-04 18:28:31','2026-11-04 18:30:35'),(132,'App\\Models\\PurchaseOrder',2,1,'admin_head',3,'approved','Approved for issuance.','2026-11-19 14:10:20','2026-11-04 18:32:10','2026-11-19 14:10:20'),(133,'App\\Models\\Grn',1,1,'hod',10,'approved',NULL,'2026-11-04 18:34:55','2026-11-04 18:34:09','2026-11-04 18:34:55'),(134,'App\\Models\\Reimbursement',4,1,'sg',9,'approved',NULL,'2026-11-04 19:00:49','2026-11-04 19:00:11','2026-11-04 19:00:49'),(135,'App\\Models\\Trf',3,1,'sg',9,'approved',NULL,'2026-11-04 19:15:47','2026-11-04 19:14:44','2026-11-04 19:15:47'),(162,'App\\Models\\Vendor',4,1,'admin_head',NULL,'pending',NULL,NULL,'2026-11-09 01:55:59','2026-11-09 01:55:59'),(163,'App\\Models\\Vendor',4,2,'asg1',NULL,'pending',NULL,NULL,'2026-11-09 01:55:59','2026-11-09 01:55:59'),(164,'App\\Models\\Vendor',4,3,'asg2',NULL,'pending',NULL,NULL,'2026-11-09 01:55:59','2026-11-09 01:55:59'),(165,'App\\Models\\Vendor',4,4,'cfo',NULL,'pending',NULL,NULL,'2026-11-09 01:55:59','2026-11-09 01:55:59'),(166,'App\\Models\\Grn',3,1,'hod',NULL,'pending',NULL,NULL,'2026-11-17 10:24:01','2026-11-17 10:24:01'),(167,'App\\Models\\Grn',4,1,'hod',10,'approved','Goods received in sound condition. Quality, specifications, and counts verified against PO.','2026-11-14 10:24:01','2026-11-17 10:24:01','2026-11-17 10:24:01'),(168,'App\\Models\\Grn',5,1,'hod',NULL,'pending',NULL,NULL,'2026-11-17 10:24:01','2026-11-17 10:24:01'),(169,'App\\Models\\Grn',6,1,'hod',10,'approved','Goods received in sound condition. Quality, specifications, and counts verified against PO.','2026-11-14 10:24:01','2026-11-17 10:24:01','2026-11-17 10:24:01'),(170,'App\\Models\\PurchaseRequisition',14,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(171,'App\\Models\\PurchaseRequisition',14,2,'pr_approver_l2',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(172,'App\\Models\\PurchaseRequisition',14,3,'cfo',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(173,'App\\Models\\PurchaseRequisition',14,4,'sg',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(174,'App\\Models\\PurchaseRequisition',15,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(175,'App\\Models\\PurchaseRequisition',15,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(176,'App\\Models\\PurchaseRequisition',15,3,'cfo',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(177,'App\\Models\\PurchaseRequisition',15,4,'sg',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(178,'App\\Models\\PurchaseRequisition',16,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(179,'App\\Models\\PurchaseRequisition',16,2,'pr_approver_l2',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(180,'App\\Models\\PurchaseRequisition',16,3,'cfo',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(181,'App\\Models\\PurchaseRequisition',16,4,'sg',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(182,'App\\Models\\PurchaseRequisition',17,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(183,'App\\Models\\PurchaseRequisition',17,2,'pr_approver_l2',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(184,'App\\Models\\PurchaseRequisition',17,3,'cfo',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(185,'App\\Models\\PurchaseRequisition',17,4,'sg',NULL,'pending',NULL,NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(186,'App\\Models\\PurchaseRequisition',18,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(187,'App\\Models\\PurchaseRequisition',18,2,'pr_approver_l2',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(188,'App\\Models\\PurchaseRequisition',18,3,'cfo',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(189,'App\\Models\\PurchaseRequisition',18,4,'sg',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(190,'App\\Models\\PurchaseRequisition',19,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(191,'App\\Models\\PurchaseRequisition',19,2,'pr_approver_l2',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(192,'App\\Models\\PurchaseRequisition',19,3,'cfo',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(193,'App\\Models\\PurchaseRequisition',19,4,'sg',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(194,'App\\Models\\PurchaseRequisition',20,1,'pr_approver_l1',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(195,'App\\Models\\PurchaseRequisition',20,2,'pr_approver_l2',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(196,'App\\Models\\PurchaseRequisition',20,3,'cfo',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(197,'App\\Models\\PurchaseRequisition',20,4,'sg',1,'approved',NULL,'2026-11-17 14:55:34','2026-11-17 14:55:34','2026-11-17 14:55:34'),(198,'App\\Models\\Vendor',14,1,'admin_head',NULL,'pending',NULL,NULL,'2026-11-19 12:39:02','2026-11-19 12:39:02'),(199,'App\\Models\\Vendor',14,2,'asg1',NULL,'pending',NULL,NULL,'2026-11-19 12:39:02','2026-11-19 12:39:02'),(200,'App\\Models\\Vendor',14,3,'asg2',NULL,'pending',NULL,NULL,'2026-11-19 12:39:02','2026-11-19 12:39:02'),(201,'App\\Models\\Vendor',14,4,'cfo',NULL,'pending',NULL,NULL,'2026-11-19 12:39:02','2026-11-19 12:39:02'),(202,'App\\Models\\Vendor',8,1,'admin_head',NULL,'pending',NULL,NULL,'2026-11-19 14:13:16','2026-11-19 14:13:16'),(203,'App\\Models\\Vendor',8,2,'asg1',NULL,'pending',NULL,NULL,'2026-11-19 14:13:16','2026-11-19 14:13:16'),(204,'App\\Models\\Vendor',8,3,'asg2',NULL,'pending',NULL,NULL,'2026-11-19 14:13:16','2026-11-19 14:13:16'),(205,'App\\Models\\Vendor',8,4,'cfo',NULL,'pending',NULL,NULL,'2026-11-19 14:13:16','2026-11-19 14:13:16'),(206,'App\\Models\\AnnualBudget',1,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(207,'App\\Models\\AnnualBudget',1,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(208,'App\\Models\\AnnualBudget',2,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(209,'App\\Models\\AnnualBudget',2,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(210,'App\\Models\\AnnualBudget',3,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(211,'App\\Models\\AnnualBudget',3,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(212,'App\\Models\\AnnualBudget',4,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(213,'App\\Models\\AnnualBudget',4,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(214,'App\\Models\\AnnualBudget',5,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(215,'App\\Models\\AnnualBudget',5,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(216,'App\\Models\\AnnualBudget',6,1,'cfo',8,'approved','Level 1 financial endorsement granted.','2026-05-21 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(217,'App\\Models\\AnnualBudget',6,2,'sg',9,'approved','Level 2 final institutional sanction granted.','2026-05-23 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(218,'App\\Models\\AnnualBudget',7,1,'cfo',8,'approved','Endorsed at rationalized ₹26,00,000.00. Advanced to Secretary General.','2026-11-19 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(219,'App\\Models\\AnnualBudget',7,2,'sg',NULL,'pending',NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(220,'App\\Models\\AnnualBudget',8,1,'cfo',NULL,'pending',NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(221,'App\\Models\\AnnualBudget',8,2,'sg',NULL,'pending',NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(222,'App\\Models\\AnnualBudget',9,1,'cfo',8,'on_hold','Please provide vendor quotation breakdowns and projected sponsorship revenue offsets for television broadcasts before review.','2026-11-20 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(223,'App\\Models\\AnnualBudget',9,2,'sg',NULL,'pending',NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17'),(224,'App\\Models\\AnnualBudget',10,1,'cfo',8,'rejected','Capital overhauls exceeding ₹25 Lakhs cannot be accommodated under general departmental OPEX. Please submit as a specialized Capex project proposal.','2026-11-18 06:46:17','2026-11-21 06:46:17','2026-11-21 06:46:17'),(225,'App\\Models\\AnnualBudget',10,2,'sg',NULL,'pending',NULL,NULL,'2026-11-21 06:46:17','2026-11-21 06:46:17');
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attachable_type` varchar(255) NOT NULL,
  `attachable_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `disk` varchar(255) NOT NULL DEFAULT 'public',
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`),
  KEY `attachments_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module` varchar(255) NOT NULL,
  `action` varchar(255) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` bigint(20) unsigned DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=524 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'PurchaseRequisition','created','PR/FY26-27/20260813-2133','App\\Models\\PurchaseRequisition',1,NULL,'2026-08-13 11:58:00'),(2,1,'Reimbursement','created','REIM-5171','App\\Models\\Reimbursement',1,NULL,'2026-08-13 13:19:04'),(3,1,'PurchaseRequisition','created','PR/FY26-27/20260813-3882','App\\Models\\PurchaseRequisition',2,NULL,'2026-08-13 13:21:03'),(4,1,'Trf','created','TRF-3701','App\\Models\\Trf',1,NULL,'2026-08-13 13:21:45'),(5,1,'Grievance','created','GRV-5562','App\\Models\\Grievance',1,NULL,'2026-08-13 13:23:25'),(6,1,'Grievance','created','GRV-6336','App\\Models\\Grievance',2,NULL,'2026-08-13 13:27:58'),(7,10,'Grievance','updated','GRV-6336','App\\Models\\Grievance',2,NULL,'2026-08-14 11:25:24'),(8,10,'Grievance','updated','GRV-5562','App\\Models\\Grievance',1,NULL,'2026-08-14 11:27:42'),(9,4,'PurchaseRequisition','updated','PR/FY26-27/20260813-3882','App\\Models\\PurchaseRequisition',2,NULL,'2026-08-14 11:49:11'),(10,4,'PurchaseRequisition','updated','PR/FY26-27/20260813-2133','App\\Models\\PurchaseRequisition',1,NULL,'2026-08-14 11:50:09'),(11,1,'PurchaseRequisition','created','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-08-18 05:10:56'),(12,4,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-08-18 05:14:03'),(13,5,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-08-18 05:16:25'),(14,8,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-08-18 05:18:13'),(15,9,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-08-18 05:19:45'),(16,2,'Vendor','created','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-08-18 05:50:53'),(17,3,'Vendor','updated','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-08-18 06:17:04'),(18,6,'Vendor','updated','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-08-18 06:17:45'),(19,7,'Vendor','updated','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-08-18 06:18:30'),(20,8,'Vendor','updated','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-08-18 06:19:30'),(21,2,'Rfq','created','RFQ-8655','App\\Models\\Rfq',1,NULL,'2026-08-18 06:23:59'),(22,2,'Rfq','updated','RFQ-8655','App\\Models\\Rfq',1,NULL,'2026-08-18 06:24:58'),(23,2,'CostComparativeStatement','created','CCS-5798','App\\Models\\CostComparativeStatement',1,NULL,'2026-08-18 06:25:15'),(24,3,'CostComparativeStatement','updated','CCS-5798','App\\Models\\CostComparativeStatement',1,NULL,'2026-08-18 06:42:22'),(25,3,'Rfq','updated','RFQ-8655','App\\Models\\Rfq',1,NULL,'2026-08-18 06:42:22'),(26,2,'PurchaseOrder','created','PO-2026-4225','App\\Models\\PurchaseOrder',1,NULL,'2026-08-18 06:44:08'),(27,3,'PurchaseOrder','updated','PO-2026-4225','App\\Models\\PurchaseOrder',1,NULL,'2026-08-18 06:46:03'),(28,3,'Trf','created','TRF-7211','App\\Models\\Trf',2,NULL,'2026-08-18 06:49:06'),(29,6,'Trf','updated','TRF-3701','App\\Models\\Trf',1,NULL,'2026-08-18 06:50:23'),(30,9,'Trf','updated','TRF-7211','App\\Models\\Trf',2,NULL,'2026-08-18 06:51:16'),(31,3,'ExceptionRequest','created','1','App\\Models\\ExceptionRequest',1,NULL,'2026-08-18 07:04:16'),(32,8,'ExceptionRequest','updated','1','App\\Models\\ExceptionRequest',1,NULL,'2026-08-18 07:05:37'),(33,8,'Grievance','updated','GRV-6336','App\\Models\\Grievance',2,NULL,'2026-08-18 07:10:56'),(34,8,'Reimbursement','updated','REIM-5171','App\\Models\\Reimbursement',1,NULL,'2026-08-18 07:15:30'),(35,8,'Grievance','created','GRV-2932','App\\Models\\Grievance',3,NULL,'2026-08-19 06:53:26'),(36,10,'Grievance','updated','GRV-2932','App\\Models\\Grievance',3,NULL,'2026-08-19 07:05:11'),(37,10,'Grievance','updated','GRV-2932','App\\Models\\Grievance',3,NULL,'2026-08-19 07:05:19'),(38,2,'Vendor','created','VEN-8883','App\\Models\\Vendor',2,NULL,'2026-09-18 06:23:10'),(39,3,'Vendor','updated','VEN-8883','App\\Models\\Vendor',2,NULL,'2026-09-18 06:44:06'),(40,6,'Vendor','updated','VEN-8883','App\\Models\\Vendor',2,NULL,'2026-09-18 07:09:05'),(41,NULL,'Member','updated','MEM-C-08842','App\\Models\\Member',1,NULL,'2026-09-19 08:22:22'),(42,NULL,'Member','updated','MEM-A-19204','App\\Models\\Member',2,NULL,'2026-09-19 08:22:23'),(43,NULL,'Member','updated','MEM-C-04471','App\\Models\\Member',3,NULL,'2026-09-19 08:22:23'),(44,NULL,'Member','updated','MEM-C-03301','App\\Models\\Member',4,NULL,'2026-09-19 08:22:23'),(45,NULL,'Member','updated','MEM-C-05512','App\\Models\\Member',5,NULL,'2026-09-19 08:22:23'),(46,NULL,'Member','updated','MEM-I-07733','App\\Models\\Member',6,NULL,'2026-09-19 08:22:23'),(47,NULL,'Member','updated','MEM-I-09912','App\\Models\\Member',7,NULL,'2026-09-19 08:22:24'),(48,NULL,'Member','updated','MEM-A-14401','App\\Models\\Member',8,NULL,'2026-09-19 08:22:24'),(49,NULL,'Member','updated','MEM-C-16650','App\\Models\\Member',9,NULL,'2026-09-19 08:22:24'),(50,NULL,'Member','updated','MEM-A-21008','App\\Models\\Member',10,NULL,'2026-09-19 08:22:24'),(51,NULL,'MembershipTicket','updated','#4465','App\\Models\\MembershipTicket',3,NULL,'2026-09-19 08:22:24'),(52,NULL,'MembershipTicket','updated','#4460','App\\Models\\MembershipTicket',4,NULL,'2026-09-19 08:22:24'),(53,2,'Vendor','created','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-09-22 07:04:26'),(54,3,'Vendor','updated','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-09-22 07:14:37'),(55,6,'Vendor','updated','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-09-22 07:15:46'),(56,7,'Vendor','updated','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-09-22 07:17:11'),(57,8,'Vendor','updated','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-09-22 07:20:30'),(58,2,'Vendor','created','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-09-29 05:26:47'),(59,1,'PurchaseRequisition','created','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-09-29 05:42:20'),(60,2,'Rfq','created','RFQ-6777','App\\Models\\Rfq',2,NULL,'2026-09-29 06:07:04'),(61,2,'Rfq','created','RFQ-9910','App\\Models\\Rfq',3,NULL,'2026-09-29 06:12:34'),(62,2,'Rfq','updated','RFQ-6777','App\\Models\\Rfq',2,NULL,'2026-09-29 06:22:05'),(63,2,'Rfq','updated','RFQ-9910','App\\Models\\Rfq',3,NULL,'2026-09-29 06:23:17'),(64,3,'Vendor','updated','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-10-16 06:47:02'),(65,3,'Reimbursement','created','REIM-8086','App\\Models\\Reimbursement',2,NULL,'2026-10-16 06:54:22'),(66,3,'Reimbursement','created','REIM-3867','App\\Models\\Reimbursement',3,NULL,'2026-10-16 06:58:09'),(67,1,'PurchaseRequisition','created','PR/FY26-27/20261023-6641','App\\Models\\PurchaseRequisition',5,NULL,'2026-10-23 07:07:41'),(68,4,'PurchaseRequisition','updated','PR/FY26-27/20261023-6641','App\\Models\\PurchaseRequisition',5,NULL,'2026-10-23 07:15:27'),(69,5,'PurchaseRequisition','updated','PR/FY26-27/20261023-6641','App\\Models\\PurchaseRequisition',5,NULL,'2026-10-23 07:16:46'),(70,4,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-10-23 07:39:58'),(71,5,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-10-23 07:40:08'),(72,1,'PurchaseRequisition','created','PR/FY26-27/20261102-1072','App\\Models\\PurchaseRequisition',6,NULL,'2026-11-02 05:50:50'),(73,4,'PurchaseRequisition','updated','PR/FY26-27/20261102-1072','App\\Models\\PurchaseRequisition',6,NULL,'2026-11-02 05:51:39'),(74,1,'PurchaseRequisition','created','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-02 06:07:41'),(75,4,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-02 06:09:51'),(76,5,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-02 06:37:40'),(77,8,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-02 06:38:43'),(78,8,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-11-02 06:39:56'),(79,9,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-02 06:40:12'),(80,9,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-11-02 06:41:40'),(81,NULL,'Member','created','MEM-A-57954','App\\Models\\Member',11,NULL,'2026-11-02 12:24:00'),(82,NULL,'MembershipTicket','created','#2061','App\\Models\\MembershipTicket',5,NULL,'2026-11-02 12:25:46'),(83,NULL,'MembershipTicket','created','#2570','App\\Models\\MembershipTicket',6,NULL,'2026-11-02 12:37:31'),(84,NULL,'MembershipTicket','created','#4368','App\\Models\\MembershipTicket',7,NULL,'2026-11-02 12:40:21'),(85,NULL,'MembershipTicket','created','#6569','App\\Models\\MembershipTicket',8,NULL,'2026-11-02 12:40:55'),(86,2,'Vendor','created','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-04 09:46:21'),(87,2,'Vendor','created','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-04 09:52:16'),(88,3,'Vendor','updated','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-04 09:54:30'),(89,6,'Vendor','updated','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-04 09:56:22'),(90,3,'Vendor','updated','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-04 09:58:35'),(91,6,'Vendor','updated','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-04 09:59:17'),(92,7,'Vendor','updated','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-04 09:59:56'),(93,7,'Vendor','updated','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-04 10:00:23'),(94,8,'Vendor','updated','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-04 10:01:46'),(95,8,'Vendor','updated','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-04 10:01:57'),(96,1,'PurchaseRequisition','created','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-04 10:06:07'),(97,4,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-04 10:06:53'),(98,5,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-04 10:07:59'),(99,8,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-04 10:08:40'),(100,9,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-04 10:09:45'),(101,2,'Rfq','created','RFQ-4940','App\\Models\\Rfq',4,NULL,'2026-11-04 10:19:17'),(102,2,'Rfq','updated','RFQ-4940','App\\Models\\Rfq',4,NULL,'2026-11-04 10:21:12'),(103,2,'CostComparativeStatement','created','CCS-9788','App\\Models\\CostComparativeStatement',2,NULL,'2026-11-04 10:21:55'),(104,3,'CostComparativeStatement','updated','CCS-9788','App\\Models\\CostComparativeStatement',2,NULL,'2026-11-04 10:29:39'),(105,3,'Rfq','updated','RFQ-4940','App\\Models\\Rfq',4,NULL,'2026-11-04 10:29:39'),(106,2,'Vendor','created','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-04 10:40:26'),(107,2,'Vendor','created','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-04 10:40:59'),(108,3,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-04 10:42:09'),(109,3,'Vendor','updated','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-04 10:43:00'),(110,6,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-04 10:43:35'),(111,6,'Vendor','updated','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-04 10:43:58'),(112,7,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-04 10:45:27'),(113,7,'Vendor','updated','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-04 10:45:37'),(114,8,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-04 10:46:14'),(115,8,'Vendor','updated','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-04 10:46:25'),(116,1,'PurchaseRequisition','created','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-04 10:56:18'),(117,4,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-04 10:58:40'),(118,5,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-04 11:03:19'),(119,8,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-04 11:04:25'),(120,9,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-04 11:05:05'),(121,2,'Rfq','created','RFQ-8534','App\\Models\\Rfq',5,NULL,'2026-11-04 11:57:26'),(122,2,'Rfq','updated','RFQ-8534','App\\Models\\Rfq',5,NULL,'2026-11-04 11:57:42'),(123,2,'CostComparativeStatement','created','CCS-8740','App\\Models\\CostComparativeStatement',3,NULL,'2026-11-04 11:58:58'),(124,3,'CostComparativeStatement','updated','CCS-8740','App\\Models\\CostComparativeStatement',3,NULL,'2026-11-04 12:00:15'),(125,3,'Rfq','updated','RFQ-8534','App\\Models\\Rfq',5,NULL,'2026-11-04 12:00:15'),(126,1,'PurchaseRequisition','created','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-04 18:06:53'),(127,4,'PurchaseRequisition','updated','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-04 18:07:52'),(128,5,'PurchaseRequisition','updated','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-04 18:17:54'),(129,8,'PurchaseRequisition','updated','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-04 18:20:11'),(130,9,'PurchaseRequisition','updated','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-04 18:20:44'),(131,2,'Rfq','created','RFQ-1873','App\\Models\\Rfq',6,NULL,'2026-11-04 18:25:42'),(132,2,'Rfq','updated','RFQ-1873','App\\Models\\Rfq',6,NULL,'2026-11-04 18:26:33'),(133,2,'CostComparativeStatement','created','CCS-2956','App\\Models\\CostComparativeStatement',4,NULL,'2026-11-04 18:28:31'),(134,3,'CostComparativeStatement','updated','CCS-2956','App\\Models\\CostComparativeStatement',4,NULL,'2026-11-04 18:30:35'),(135,3,'Rfq','updated','RFQ-1873','App\\Models\\Rfq',6,NULL,'2026-11-04 18:30:35'),(136,2,'PurchaseOrder','created','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-04 18:32:10'),(137,3,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-04 18:32:47'),(138,1,'Grn','created','GRN-3644','App\\Models\\Grn',1,NULL,'2026-11-04 18:34:09'),(139,10,'Grn','updated','GRN-3644','App\\Models\\Grn',1,NULL,'2026-11-04 18:34:55'),(140,10,'Reimbursement','created','REIM-1368','App\\Models\\Reimbursement',4,NULL,'2026-11-04 19:00:11'),(141,9,'Reimbursement','updated','REIM-1368','App\\Models\\Reimbursement',4,NULL,'2026-11-04 19:00:49'),(142,9,'Trf','created','TRF-6986','App\\Models\\Trf',3,NULL,'2026-11-04 19:14:44'),(143,9,'Trf','updated','TRF-6986','App\\Models\\Trf',3,NULL,'2026-11-04 19:15:47'),(144,3,'FlightLog','created','FLT-9187','App\\Models\\FlightLog',1,NULL,'2026-11-04 19:20:05'),(145,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:13:13'),(146,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 00:19:38'),(147,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:19:57'),(148,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 00:28:53'),(149,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:31:47'),(150,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 00:32:34'),(151,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:32:55'),(152,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 00:34:40'),(153,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:35:00'),(154,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 00:52:10'),(155,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 00:53:21'),(156,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 03:25:58'),(157,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-06 03:26:10'),(158,9,'System','Switched Role','Secretary General',NULL,NULL,'Active user set to Meenakshi Rao (Secretary General)','2026-11-06 03:26:18'),(159,8,'System','Switched Role','CFO',NULL,NULL,'Active user set to Sanjay Menon (CFO)','2026-11-06 03:26:27'),(160,6,'System','Switched Role','ASG 1',NULL,NULL,'Active user set to Anil Kulkarni (ASG 1)','2026-11-06 03:26:39'),(161,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-06 03:26:48'),(162,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 03:26:54'),(163,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 03:56:43'),(164,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 04:10:05'),(165,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 04:10:13'),(166,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 04:11:10'),(167,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-06 04:32:40'),(168,7,'System','Switched Role','ASG 2',NULL,NULL,'Active user set to Deepak Nair (ASG 2)','2026-11-06 04:32:54'),(169,6,'System','Switched Role','ASG 1',NULL,NULL,'Active user set to Anil Kulkarni (ASG 1)','2026-11-06 04:33:21'),(170,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 05:10:20'),(171,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 05:54:56'),(172,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 06:01:38'),(173,2,'Rfq','created','RFQ-6693','App\\Models\\Rfq',7,NULL,'2026-11-06 06:17:29'),(174,2,'Rfq','created','RFQ-6693','App\\Models\\Rfq',7,'RFQ RFQ-6693 automatically created on behalf of approved PR PR/FY26-27/20260929-2821.','2026-11-06 06:17:29'),(175,2,'Rfq','created','RFQ-3594','App\\Models\\Rfq',8,NULL,'2026-11-06 06:17:29'),(176,2,'Rfq','created','RFQ-3594','App\\Models\\Rfq',8,'RFQ RFQ-3594 automatically created on behalf of approved PR PR/FY26-27/20261102-9615.','2026-11-06 06:17:29'),(177,2,'Rfq','created','RFQ-4210','App\\Models\\Rfq',9,NULL,'2026-11-06 06:17:29'),(178,2,'Rfq','created','RFQ-4210','App\\Models\\Rfq',9,'RFQ RFQ-4210 automatically created on behalf of approved PR PR/FY26-27/20261104-5167.','2026-11-06 06:17:29'),(179,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 06:34:12'),(180,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-06 06:34:22'),(181,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 06:34:28'),(182,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-06 06:34:38'),(183,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 06:34:56'),(184,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-06 06:35:06'),(185,6,'System','Switched Role','ASG 1',NULL,NULL,'Active user set to Anil Kulkarni (ASG 1)','2026-11-06 06:35:12'),(186,7,'System','Switched Role','ASG 2',NULL,NULL,'Active user set to Deepak Nair (ASG 2)','2026-11-06 06:35:17'),(187,8,'System','Switched Role','CFO',NULL,NULL,'Active user set to Sanjay Menon (CFO)','2026-11-06 06:35:24'),(188,9,'System','Switched Role','Secretary General',NULL,NULL,'Active user set to Meenakshi Rao (Secretary General)','2026-11-06 06:35:32'),(189,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-06 06:35:40'),(190,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 06:35:55'),(191,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 07:55:31'),(192,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 07:59:40'),(193,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 07:59:59'),(194,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 08:01:06'),(195,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-06 08:18:33'),(196,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 08:18:44'),(233,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 08:39:45'),(234,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 08:40:12'),(235,2,'CostComparativeStatement','created','CCS-5947','App\\Models\\CostComparativeStatement',5,NULL,'2026-11-06 08:46:51'),(236,2,'Rfq','updated','RFQ-6693','App\\Models\\Rfq',7,NULL,'2026-11-06 08:46:51'),(237,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 09:04:06'),(238,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-06 10:02:32'),(239,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-06 10:02:44'),(240,3,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-06 23:19:35'),(241,3,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,'PO PO-2026-6284 edited post-approval — status reset to pending and routed for re-approval.','2026-11-06 23:19:35'),(242,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 00:10:15'),(243,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-07 00:11:09'),(244,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 00:20:43'),(245,NULL,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-07 02:27:33'),(246,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 02:37:53'),(247,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 02:38:03'),(248,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 02:49:38'),(249,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 02:58:22'),(250,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 02:59:08'),(251,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 02:59:15'),(252,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 03:29:17'),(253,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 03:29:26'),(254,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 03:31:33'),(255,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-07 03:31:48'),(256,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 03:31:54'),(257,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-07 03:59:54'),(258,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 04:00:27'),(259,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-07 04:00:34'),(260,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 04:01:45'),(261,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-07 04:02:32'),(262,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-07 04:02:38'),(263,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-07 04:02:44'),(264,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-07 04:03:11'),(265,6,'System','Switched Role','ASG 1',NULL,NULL,'Active user set to Anil Kulkarni (ASG 1)','2026-11-07 04:03:25'),(266,7,'System','Switched Role','ASG 2',NULL,NULL,'Active user set to Deepak Nair (ASG 2)','2026-11-07 04:03:35'),(267,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-07 04:07:09'),(268,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 04:07:22'),(269,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-07 04:36:19'),(270,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-07 04:41:39'),(271,3,'Vendor','updated','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-11-09 00:59:12'),(272,3,'Vendor','blacklisted','VEN-9606','App\\Models\\Vendor',4,'Vendor VEN-9606 (Lott and Duran) was blacklisted. Reason: bekaar service','2026-11-09 00:59:12'),(273,3,'Vendor','updated','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-11-09 01:04:47'),(274,3,'Vendor','unblacklisted','VEN-9606','App\\Models\\Vendor',4,'Vendor VEN-9606 (Lott and Duran) was unblacklisted / reinstated. Reason: maafi maang liya','2026-11-09 01:04:47'),(275,NULL,'Vendor','updated','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-11-09 01:55:59'),(276,8,'System','Switched Role','CFO',NULL,NULL,'Active user set to Sanjay Menon (CFO)','2026-11-09 09:59:31'),(277,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-09 09:59:52'),(278,NULL,'Rfq','updated','RFQ-8655','App\\Models\\Rfq',1,NULL,'2026-11-10 08:19:31'),(279,NULL,'Rfq','updated','RFQ-6777','App\\Models\\Rfq',2,NULL,'2026-11-10 08:19:31'),(280,NULL,'Rfq','updated','RFQ-9910','App\\Models\\Rfq',3,NULL,'2026-11-10 08:19:31'),(281,NULL,'Rfq','updated','RFQ-4940','App\\Models\\Rfq',4,NULL,'2026-11-10 08:19:31'),(282,NULL,'Rfq','updated','RFQ-8534','App\\Models\\Rfq',5,NULL,'2026-11-10 08:19:31'),(283,NULL,'Rfq','updated','RFQ-1873','App\\Models\\Rfq',6,NULL,'2026-11-10 08:19:31'),(284,NULL,'Rfq','updated','RFQ-6693','App\\Models\\Rfq',7,NULL,'2026-11-10 08:19:31'),(285,NULL,'Rfq','updated','RFQ-3594','App\\Models\\Rfq',8,NULL,'2026-11-10 08:19:31'),(286,NULL,'Rfq','updated','RFQ-4210','App\\Models\\Rfq',9,NULL,'2026-11-10 08:19:31'),(287,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260813-2133','App\\Models\\PurchaseRequisition',1,NULL,'2026-11-10 09:44:08'),(288,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260813-3882','App\\Models\\PurchaseRequisition',2,NULL,'2026-11-10 09:44:08'),(289,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-11-10 09:44:08'),(290,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-11-10 09:44:08'),(291,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261023-6641','App\\Models\\PurchaseRequisition',5,NULL,'2026-11-10 09:44:08'),(292,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261102-1072','App\\Models\\PurchaseRequisition',6,NULL,'2026-11-10 09:44:08'),(293,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-10 09:44:08'),(294,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-10 09:44:08'),(295,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-10 09:44:08'),(296,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261104-5167','App\\Models\\PurchaseRequisition',10,NULL,'2026-11-10 09:44:08'),(297,3,'Trf','booking_email_failed','TRF-3701','App\\Models\\Trf',1,'Booking confirmation email for FLT-9187 failed to send to priya.nair@assocham.test: Failed to authenticate on SMTP server with username \"info@enseur.in\" using the following authenticators: \"LOGIN\", \"XOAUTH2\". Authenticator \"LOGIN\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.139 Authentication unsuccessful, the user credentials were incorrect. [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:13.454Z 08DF3F6CCA5CF33F]\".\". Authenticator \"XOAUTH2\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.3 Authentication unsuccessful [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:23.769Z 08DF3F6CCA5CF33F]\".\".','2026-11-11 09:33:29'),(298,3,'FlightLog','booking_email_failed','FLT-9187','App\\Models\\FlightLog',1,'Booking confirmation email for FLT-9187 failed to send to priya.nair@assocham.test: Failed to authenticate on SMTP server with username \"info@enseur.in\" using the following authenticators: \"LOGIN\", \"XOAUTH2\". Authenticator \"LOGIN\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.139 Authentication unsuccessful, the user credentials were incorrect. [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:13.454Z 08DF3F6CCA5CF33F]\".\". Authenticator \"XOAUTH2\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.3 Authentication unsuccessful [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:23.769Z 08DF3F6CCA5CF33F]\".\".','2026-11-11 09:33:29'),(299,NULL,'Vendor','created','VEND-TRV-101','App\\Models\\Vendor',12,NULL,'2026-11-11 10:36:36'),(300,NULL,'Vendor','created','VEND-TRV-102','App\\Models\\Vendor',13,NULL,'2026-11-11 10:36:36'),(301,NULL,'Vendor','created','VEND-TRV-103','App\\Models\\Vendor',14,NULL,'2026-11-11 10:36:36'),(302,NULL,'Trf','created','TRF-DEMO-810','App\\Models\\Trf',4,NULL,'2026-11-11 10:36:36'),(303,NULL,'Reimbursement','created','REIM-DEMO-810','App\\Models\\Reimbursement',5,NULL,'2026-11-11 10:36:36'),(304,NULL,'FlightLog','created','FLT-DEMO-810','App\\Models\\FlightLog',2,NULL,'2026-11-11 10:36:36'),(305,NULL,'VendorEvaluation','created','1','App\\Models\\VendorEvaluation',1,NULL,'2026-11-11 10:36:36'),(306,NULL,'Trf','created','TRF-DEMO-811','App\\Models\\Trf',5,NULL,'2026-11-11 10:36:36'),(307,NULL,'Reimbursement','created','REIM-DEMO-811','App\\Models\\Reimbursement',6,NULL,'2026-11-11 10:36:36'),(308,NULL,'FlightLog','created','FLT-DEMO-811','App\\Models\\FlightLog',3,NULL,'2026-11-11 10:36:36'),(309,NULL,'VendorEvaluation','created','2','App\\Models\\VendorEvaluation',2,NULL,'2026-11-11 10:36:36'),(310,NULL,'Trf','created','TRF-DEMO-812','App\\Models\\Trf',6,NULL,'2026-11-11 10:36:36'),(311,NULL,'Reimbursement','created','REIM-DEMO-812','App\\Models\\Reimbursement',7,NULL,'2026-11-11 10:36:36'),(312,NULL,'FlightLog','created','FLT-DEMO-812','App\\Models\\FlightLog',4,NULL,'2026-11-11 10:36:36'),(313,NULL,'VendorEvaluation','created','3','App\\Models\\VendorEvaluation',3,NULL,'2026-11-11 10:36:36'),(314,NULL,'Trf','created','TRF-DEMO-813','App\\Models\\Trf',7,NULL,'2026-11-11 10:36:36'),(315,NULL,'Reimbursement','created','REIM-DEMO-813','App\\Models\\Reimbursement',8,NULL,'2026-11-11 10:36:36'),(316,NULL,'FlightLog','created','FLT-DEMO-813','App\\Models\\FlightLog',5,NULL,'2026-11-11 10:36:36'),(317,NULL,'VendorEvaluation','created','4','App\\Models\\VendorEvaluation',4,NULL,'2026-11-11 10:36:36'),(318,NULL,'Vendor','updated','VEND-TRV-101','App\\Models\\Vendor',12,NULL,'2026-11-11 10:36:36'),(319,NULL,'Vendor','updated','VEND-TRV-102','App\\Models\\Vendor',13,NULL,'2026-11-11 10:36:36'),(320,NULL,'Vendor','updated','VEND-TRV-103','App\\Models\\Vendor',14,NULL,'2026-11-11 10:36:36'),(321,9,'System','Switched Role','Secretary General',NULL,NULL,'Active user set to Meenakshi Rao (Secretary General)','2026-11-11 11:42:52'),(322,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-11 11:43:14'),(323,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-11 11:43:28'),(324,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-11 11:45:23'),(325,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-11 11:45:36'),(326,7,'System','Switched Role','ASG 2',NULL,NULL,'Active user set to Deepak Nair (ASG 2)','2026-11-11 11:46:18'),(327,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-11 11:46:30'),(328,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-11 11:47:29'),(329,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Active user set to Basu (DSG — Deputy Secretary General (Basu))','2026-11-11 11:47:36'),(330,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-11 11:47:44'),(331,7,'System','Switched Role','ASG 2',NULL,NULL,'Active user set to Deepak Nair (ASG 2)','2026-11-11 11:47:51'),(332,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-11 11:47:59'),(333,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-11 11:50:36'),(334,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-11 11:55:08'),(335,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-11 11:55:19'),(336,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-12 04:57:16'),(337,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-12 04:57:25'),(338,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-12 04:57:35'),(339,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-12 04:57:42'),(340,9,'System','Switched Role','Secretary General',NULL,NULL,'Active user set to Meenakshi Rao (Secretary General)','2026-11-12 04:57:53'),(341,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-12 04:58:04'),(342,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Active user set to Priya Nair (PR & GRN Creator)','2026-11-17 09:14:59'),(343,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-17 09:15:06'),(344,5,'System','Switched Role','Head of States (Ambika)',NULL,NULL,'Active user set to Ambika (Head of States (Ambika))','2026-11-17 09:15:30'),(345,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-17 09:15:46'),(346,NULL,'Grn','created','GRN-4001','App\\Models\\Grn',3,NULL,'2026-11-17 10:24:01'),(347,NULL,'Grn','created','GRN-4002','App\\Models\\Grn',4,NULL,'2026-11-17 10:24:01'),(348,NULL,'Grn','created','GRN-4003','App\\Models\\Grn',5,NULL,'2026-11-17 10:24:01'),(349,NULL,'Grn','created','GRN-4004','App\\Models\\Grn',6,NULL,'2026-11-17 10:24:01'),(350,NULL,'ProjectBudget','created','1','App\\Models\\ProjectBudget',1,NULL,'2026-11-17 11:37:35'),(351,NULL,'ProjectBudget','created','2','App\\Models\\ProjectBudget',2,NULL,'2026-11-17 11:37:35'),(352,NULL,'ProjectBudget','created','3','App\\Models\\ProjectBudget',3,NULL,'2026-11-17 11:37:35'),(353,NULL,'ProjectBudget','created','4','App\\Models\\ProjectBudget',4,NULL,'2026-11-17 11:37:35'),(354,NULL,'ProjectBudget','created','5','App\\Models\\ProjectBudget',5,NULL,'2026-11-17 11:37:35'),(355,NULL,'ProjectBudget','created','6','App\\Models\\ProjectBudget',6,NULL,'2026-11-17 11:37:35'),(356,NULL,'ProjectBudget','created','7','App\\Models\\ProjectBudget',7,NULL,'2026-11-17 11:37:35'),(357,NULL,'ProjectBudget','created','8','App\\Models\\ProjectBudget',8,NULL,'2026-11-17 11:37:35'),(358,NULL,'ProjectBudget','created','9','App\\Models\\ProjectBudget',9,NULL,'2026-11-17 11:37:35'),(359,NULL,'ProjectBudget','created','10','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 12:08:30'),(360,NULL,'ProjectBudget','created','11','App\\Models\\ProjectBudget',11,NULL,'2026-11-17 12:08:30'),(361,NULL,'ProjectBudget','created','12','App\\Models\\ProjectBudget',12,NULL,'2026-11-17 12:08:30'),(362,NULL,'ProjectBudget','created','13','App\\Models\\ProjectBudget',13,NULL,'2026-11-17 12:08:30'),(363,NULL,'ProjectBudget','updated','1','App\\Models\\ProjectBudget',1,NULL,'2026-11-17 12:08:30'),(364,NULL,'ProjectBudget','updated','2','App\\Models\\ProjectBudget',2,NULL,'2026-11-17 12:08:30'),(365,NULL,'ProjectBudget','updated','3','App\\Models\\ProjectBudget',3,NULL,'2026-11-17 12:08:30'),(366,NULL,'ProjectBudget','updated','4','App\\Models\\ProjectBudget',4,NULL,'2026-11-17 12:08:30'),(367,NULL,'ProjectBudget','updated','5','App\\Models\\ProjectBudget',5,NULL,'2026-11-17 12:08:30'),(368,NULL,'ProjectBudget','updated','6','App\\Models\\ProjectBudget',6,NULL,'2026-11-17 12:08:30'),(369,NULL,'ProjectBudget','updated','7','App\\Models\\ProjectBudget',7,NULL,'2026-11-17 12:08:30'),(370,NULL,'ProjectBudget','updated','8','App\\Models\\ProjectBudget',8,NULL,'2026-11-17 12:08:30'),(371,NULL,'ProjectBudget','updated','9','App\\Models\\ProjectBudget',9,NULL,'2026-11-17 12:08:30'),(372,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260813-2133','App\\Models\\PurchaseRequisition',1,NULL,'2026-11-17 14:40:44'),(373,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260813-3882','App\\Models\\PurchaseRequisition',2,NULL,'2026-11-17 14:40:44'),(374,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260818-3121','App\\Models\\PurchaseRequisition',3,NULL,'2026-11-17 14:40:44'),(375,NULL,'PurchaseRequisition','updated','PR/FY26-27/20260929-2821','App\\Models\\PurchaseRequisition',4,NULL,'2026-11-17 14:40:44'),(376,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261023-6641','App\\Models\\PurchaseRequisition',5,NULL,'2026-11-17 14:40:44'),(377,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261102-1072','App\\Models\\PurchaseRequisition',6,NULL,'2026-11-17 14:40:45'),(378,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261102-9615','App\\Models\\PurchaseRequisition',7,NULL,'2026-11-17 14:40:45'),(379,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261104-4044','App\\Models\\PurchaseRequisition',8,NULL,'2026-11-17 14:40:45'),(380,NULL,'PurchaseRequisition','updated','PR/FY26-27/20261104-7263','App\\Models\\PurchaseRequisition',9,NULL,'2026-11-17 14:40:45'),(381,NULL,'ProjectBudget','created','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:55:33'),(382,NULL,'ProjectBudget','created','Annual Strategy Conclave 2026 (Projects - FY26-27)','App\\Models\\ProjectBudget',15,NULL,'2026-11-17 14:55:33'),(383,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:55:33'),(384,NULL,'ProjectBudget','created','National Summit on Changing Landscape of Indian Automotive Sector (Projects - FY26-27)','App\\Models\\ProjectBudget',16,NULL,'2026-11-17 14:55:34'),(385,NULL,'ProjectBudget','created','National Education & Future Skills Conclave (Projects - FY26-27)','App\\Models\\ProjectBudget',17,NULL,'2026-11-17 14:55:34'),(386,NULL,'ProjectBudget','created','India Leadership Banquet & Corporate Governance Awards (Projects - FY26-27)','App\\Models\\ProjectBudget',18,NULL,'2026-11-17 14:55:34'),(387,NULL,'ProjectBudget','created','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:55:34'),(388,NULL,'ProjectBudget','created','National Membership Drive & Industry Outreach Program (Marketing - FY26-27)','App\\Models\\ProjectBudget',20,NULL,'2026-11-17 14:55:34'),(389,NULL,'ProjectBudget','created','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:55:34'),(390,NULL,'ProjectBudget','created','National MSME Competitiveness & Financing Summit (Projects - FY26-27)','App\\Models\\ProjectBudget',22,NULL,'2026-11-17 14:55:34'),(391,NULL,'ProjectBudget','created','National Healthcare Infrastructure & MedTech Forum (Projects - FY26-27)','App\\Models\\ProjectBudget',23,NULL,'2026-11-17 14:55:34'),(392,NULL,'ProjectBudget','created','National Logistics & Multimodal Supply Chain Conclave (Operations - FY26-27)','App\\Models\\ProjectBudget',24,NULL,'2026-11-17 14:55:34'),(393,NULL,'PurchaseRequisition','created','PR/FY26-27/EV-202608-01','App\\Models\\PurchaseRequisition',14,NULL,'2026-11-17 14:55:34'),(394,NULL,'PurchaseRequisition','created','PR/FY26-27/EV-202608-02','App\\Models\\PurchaseRequisition',15,NULL,'2026-11-17 14:55:34'),(395,NULL,'PurchaseRequisition','created','PR/FY26-27/ASC-202611-01','App\\Models\\PurchaseRequisition',16,NULL,'2026-11-17 14:55:34'),(396,NULL,'PurchaseRequisition','created','PR/FY26-27/ASC-202611-02','App\\Models\\PurchaseRequisition',17,NULL,'2026-11-17 14:55:34'),(397,NULL,'PurchaseRequisition','created','PR/FY26-27/MIN-202606-01','App\\Models\\PurchaseRequisition',18,NULL,'2026-11-17 14:55:34'),(398,NULL,'PurchaseRequisition','created','PR/FY26-27/EXP-202610-01','App\\Models\\PurchaseRequisition',19,NULL,'2026-11-17 14:55:34'),(399,NULL,'PurchaseRequisition','created','PR/FY26-27/IT-202607-01','App\\Models\\PurchaseRequisition',20,NULL,'2026-11-17 14:55:34'),(400,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:55:34'),(401,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:55:34'),(402,NULL,'ProjectBudget','updated','Annual Strategy Conclave 2026 (Projects - FY26-27)','App\\Models\\ProjectBudget',15,NULL,'2026-11-17 14:55:34'),(403,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:55:34'),(404,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:55:34'),(405,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:56:52'),(406,NULL,'ProjectBudget','created','Annual Strategy Conclave (Projects - FY26-27)','App\\Models\\ProjectBudget',25,NULL,'2026-11-17 14:56:52'),(407,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:56:52'),(408,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:56:52'),(409,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:56:52'),(410,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:56:52'),(411,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:56:52'),(412,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:56:52'),(413,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:56:52'),(414,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:57:59'),(415,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:57:59'),(416,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:57:59'),(417,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:57:59'),(418,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:57:59'),(419,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:57:59'),(420,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:57:59'),(421,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:57:59'),(422,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:59:53'),(423,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:59:53'),(424,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:59:53'),(425,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:59:53'),(426,NULL,'ProjectBudget','updated','Conference on Building a Resilient Critical Minerals Supply Chain in India (Projects - FY26-27)','App\\Models\\ProjectBudget',10,NULL,'2026-11-17 14:59:53'),(427,NULL,'ProjectBudget','updated','National Conference on Green Mobility & EV Infrastructure (Projects - FY26-27)','App\\Models\\ProjectBudget',14,NULL,'2026-11-17 14:59:53'),(428,NULL,'ProjectBudget','updated','East India Regional Trade & Investment Expo (Marketing - FY26-27)','App\\Models\\ProjectBudget',19,NULL,'2026-11-17 14:59:53'),(429,NULL,'ProjectBudget','updated','Digital Transformation & Enterprise Cyber Security Conclave (IT - FY26-27)','App\\Models\\ProjectBudget',21,NULL,'2026-11-17 14:59:53'),(430,3,'Rfq','created','RFQ-8069','App\\Models\\Rfq',13,NULL,'2026-11-18 07:30:16'),(431,3,'Rfq','created','RFQ-5560','App\\Models\\Rfq',14,NULL,'2026-11-18 07:32:02'),(432,3,'Rfq','created','RFQ-6847','App\\Models\\Rfq',15,NULL,'2026-11-18 07:32:35'),(433,3,'Rfq','created','RFQ-9738','App\\Models\\Rfq',16,NULL,'2026-11-18 07:33:38'),(434,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-18 07:38:01'),(435,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-18 07:41:11'),(436,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-18 07:43:29'),(437,10,'System','Switched Role','CH / HOD',NULL,NULL,'Active user set to Vikram Shah (CH / HOD)','2026-11-18 07:44:45'),(438,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-18 07:56:47'),(439,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-18 08:05:56'),(440,3,'System','Switched Role','Admin Head',NULL,NULL,'Active user set to Rahul Mehta (Admin Head)','2026-11-18 08:09:03'),(441,2,'System','Switched Role','Buyer',NULL,NULL,'Active user set to Rohit Verma (Buyer)','2026-11-18 08:41:18'),(442,2,'System','Switched Role','Buyer',NULL,NULL,'Admin Rahul Mehta switched active session to Rohit Verma (Buyer)','2026-11-18 08:54:04'),(443,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Admin Rahul Mehta switched active session to Basu (DSG — Deputy Secretary General (Basu))','2026-11-18 08:54:10'),(444,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-18 08:54:17'),(445,3,'Rfq','created','RFQ-4243','App\\Models\\Rfq',17,NULL,'2026-11-18 09:02:13'),(446,2,'System','Switched Role','Buyer',NULL,NULL,'Admin Rahul Mehta switched active session to Rohit Verma (Buyer)','2026-11-18 09:17:26'),(447,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-18 09:17:47'),(448,3,'CostComparativeStatement','created','CCS-4440','App\\Models\\CostComparativeStatement',6,NULL,'2026-11-19 07:56:33'),(449,3,'Rfq','updated','RFQ-9738','App\\Models\\Rfq',16,NULL,'2026-11-19 07:57:01'),(450,3,'CostComparativeStatement','updated','CCS-4440','App\\Models\\CostComparativeStatement',6,NULL,'2026-11-19 07:57:21'),(451,3,'Rfq','vendor_selected','RFQ-9738','App\\Models\\Rfq',16,'Manually selected Canteen as best vendor for RFQ RFQ-9738. Justification: good','2026-11-19 07:57:28'),(452,NULL,'Vendor','updated','VEN-7542','App\\Models\\Vendor',3,NULL,'2026-11-19 10:37:09'),(453,NULL,'Vendor','updated','VEN-9606','App\\Models\\Vendor',4,NULL,'2026-11-19 10:37:09'),(454,NULL,'Vendor','updated','VEN-1857','App\\Models\\Vendor',1,NULL,'2026-11-19 10:37:09'),(455,NULL,'Vendor','updated','VEN-8883','App\\Models\\Vendor',2,NULL,'2026-11-19 10:37:09'),(456,NULL,'Vendor','updated','VEN-1432','App\\Models\\Vendor',5,NULL,'2026-11-19 10:37:09'),(457,NULL,'Vendor','updated','VEN-2673','App\\Models\\Vendor',6,NULL,'2026-11-19 10:37:09'),(458,NULL,'Vendor','updated','VEN-4793','App\\Models\\Vendor',7,NULL,'2026-11-19 10:37:09'),(459,NULL,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-19 10:37:09'),(460,NULL,'Vendor','updated','VEND-TRV-101','App\\Models\\Vendor',12,NULL,'2026-11-19 10:37:09'),(461,NULL,'Vendor','updated','VEND-TRV-102','App\\Models\\Vendor',13,NULL,'2026-11-19 10:37:09'),(462,NULL,'Vendor','updated','VEND-TRV-103','App\\Models\\Vendor',14,NULL,'2026-11-19 10:37:09'),(463,3,'Vendor','updated','VEND-TRV-103','App\\Models\\Vendor',14,NULL,'2026-11-19 12:37:59'),(464,3,'Vendor','blacklisted','VEND-TRV-103','App\\Models\\Vendor',14,'Vendor VEND-TRV-103 (Thomas Cook Business) was blacklisted. Reason: baddest','2026-11-19 12:37:59'),(465,3,'Vendor','updated','VEND-TRV-103','App\\Models\\Vendor',14,NULL,'2026-11-19 12:39:02'),(466,3,'Vendor','unblacklisted','VEND-TRV-103','App\\Models\\Vendor',14,'Vendor VEND-TRV-103 (Thomas Cook Business) was unblacklisted / reinstated. Approval cycle reset to Admin Head (Stage 1). Reason: goodgg','2026-11-19 12:39:02'),(467,3,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-19 13:52:07'),(468,3,'PurchaseOrder','on_hold','PO-2026-6284','App\\Models\\PurchaseOrder',2,'PO PO-2026-6284 placed on hold by Rahul Mehta (Admin Head): not good','2026-11-19 13:52:07'),(469,3,'PurchaseOrder','on_hold','PO-2026-6284','App\\Models\\PurchaseOrder',2,'PO PO-2026-6284 placed on hold by Rahul Mehta (Admin Head): not good','2026-11-19 13:52:41'),(470,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Admin Rahul Mehta switched active session to Basu (DSG — Deputy Secretary General (Basu))','2026-11-19 13:59:32'),(471,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-19 13:59:59'),(472,3,'PurchaseOrder','updated','PO-2026-6284','App\\Models\\PurchaseOrder',2,NULL,'2026-11-19 14:09:34'),(473,3,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-19 14:12:59'),(474,3,'Vendor','blacklisted','VEN-6586','App\\Models\\Vendor',8,'Vendor VEN-6586 (MSIT) was blacklisted. Reason: cxvfdgdrgfvbfrsd','2026-11-19 14:12:59'),(475,3,'Vendor','updated','VEN-6586','App\\Models\\Vendor',8,NULL,'2026-11-19 14:13:16'),(476,3,'Vendor','unblacklisted','VEN-6586','App\\Models\\Vendor',8,'Vendor VEN-6586 (MSIT) was unblacklisted / reinstated. Approval cycle reset to Admin Head (Stage 1). Reason: sgergerfgtrghtreds','2026-11-19 14:13:16'),(477,NULL,'ItemCatalog','created','1','App\\Models\\ItemCatalog',1,NULL,'2026-11-19 14:31:56'),(478,NULL,'ItemCatalog','created','2','App\\Models\\ItemCatalog',2,NULL,'2026-11-19 14:31:56'),(479,NULL,'ItemCatalog','created','3','App\\Models\\ItemCatalog',3,NULL,'2026-11-19 14:31:56'),(480,NULL,'ItemCatalog','created','4','App\\Models\\ItemCatalog',4,NULL,'2026-11-19 14:31:56'),(481,NULL,'ItemCatalog','created','5','App\\Models\\ItemCatalog',5,NULL,'2026-11-19 14:31:56'),(482,NULL,'ItemCatalog','created','6','App\\Models\\ItemCatalog',6,NULL,'2026-11-19 14:31:56'),(483,NULL,'ItemCatalog','created','7','App\\Models\\ItemCatalog',7,NULL,'2026-11-19 14:31:56'),(484,NULL,'ItemCatalog','created','8','App\\Models\\ItemCatalog',8,NULL,'2026-11-19 14:31:56'),(485,NULL,'ItemCatalog','created','9','App\\Models\\ItemCatalog',9,NULL,'2026-11-19 14:31:56'),(486,NULL,'ItemCatalog','created','10','App\\Models\\ItemCatalog',10,NULL,'2026-11-19 14:31:56'),(487,NULL,'ItemCatalog','created','11','App\\Models\\ItemCatalog',11,NULL,'2026-11-19 14:31:56'),(488,NULL,'ItemCatalog','created','12','App\\Models\\ItemCatalog',12,NULL,'2026-11-19 14:31:57'),(489,NULL,'ItemCatalog','created','13','App\\Models\\ItemCatalog',13,NULL,'2026-11-19 14:31:57'),(490,NULL,'ItemCatalog','created','14','App\\Models\\ItemCatalog',14,NULL,'2026-11-19 14:31:57'),(491,2,'System','Switched Role','Buyer',NULL,NULL,'Admin Rahul Mehta switched active session to Rohit Verma (Buyer)','2026-11-19 14:35:57'),(492,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-19 14:36:37'),(493,10,'System','Switched Role','CH / HOD',NULL,NULL,'Admin Rahul Mehta switched active session to Vikram Shah (CH / HOD)','2026-11-19 18:34:58'),(494,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-19 18:44:28'),(495,10,'System','Switched Role','CH / HOD',NULL,NULL,'Admin Rahul Mehta switched active session to Vikram Shah (CH / HOD)','2026-11-19 18:58:19'),(496,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-19 19:12:05'),(497,8,'System','Switched Role','CFO',NULL,NULL,'Admin Rahul Mehta switched active session to Sanjay Menon (CFO)','2026-11-20 05:16:59'),(498,2,'System','Switched Role','Buyer',NULL,NULL,'Admin Rahul Mehta switched active session to Rohit Verma (Buyer)','2026-11-20 05:30:24'),(499,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Admin Rahul Mehta switched active session to Basu (DSG — Deputy Secretary General (Basu))','2026-11-20 05:37:52'),(500,7,'System','Switched Role','ASG 2',NULL,NULL,'Admin Rahul Mehta switched active session to Deepak Nair (ASG 2)','2026-11-20 05:38:00'),(501,8,'System','Switched Role','CFO',NULL,NULL,'Admin Rahul Mehta switched active session to Sanjay Menon (CFO)','2026-11-20 05:38:10'),(502,9,'System','Switched Role','Secretary General',NULL,NULL,'Admin Rahul Mehta switched active session to Meenakshi Rao (Secretary General)','2026-11-20 05:38:28'),(503,10,'System','Switched Role','CH / HOD',NULL,NULL,'Admin Rahul Mehta switched active session to Vikram Shah (CH / HOD)','2026-11-20 05:38:39'),(504,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Admin Rahul Mehta switched active session to Priya Nair (PR & GRN Creator)','2026-11-20 05:38:55'),(505,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-20 05:40:33'),(506,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Admin Rahul Mehta switched active session to Priya Nair (PR & GRN Creator)','2026-11-20 05:44:43'),(507,2,'System','Switched Role','Buyer',NULL,NULL,'Admin Rahul Mehta switched active session to Rohit Verma (Buyer)','2026-11-20 05:47:39'),(508,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-20 05:50:16'),(509,NULL,'AnnualBudget','created','AB-26-27-PROJ-001 (Projects · FY26-27)','App\\Models\\AnnualBudget',1,NULL,'2026-11-21 06:46:16'),(510,NULL,'AnnualBudget','created','AB-26-27-IT-001 (IT · FY26-27)','App\\Models\\AnnualBudget',2,NULL,'2026-11-21 06:46:17'),(511,NULL,'AnnualBudget','created','AB-26-27-OPER-001 (Operations · FY26-27)','App\\Models\\AnnualBudget',3,NULL,'2026-11-21 06:46:17'),(512,NULL,'AnnualBudget','created','AB-26-27-MKT-001 (Marketing · FY26-27)','App\\Models\\AnnualBudget',4,NULL,'2026-11-21 06:46:17'),(513,NULL,'AnnualBudget','created','AB-26-27-HR-001 (HR · FY26-27)','App\\Models\\AnnualBudget',5,NULL,'2026-11-21 06:46:17'),(514,NULL,'AnnualBudget','created','AB-26-27-FIN-001 (Finance · FY26-27)','App\\Models\\AnnualBudget',6,NULL,'2026-11-21 06:46:17'),(515,NULL,'AnnualBudget','created','AB-27-28-IT-001 (IT · FY27-28)','App\\Models\\AnnualBudget',7,NULL,'2026-11-21 06:46:17'),(516,NULL,'AnnualBudget','created','AB-27-28-PROJ-001 (Projects · FY27-28)','App\\Models\\AnnualBudget',8,NULL,'2026-11-21 06:46:17'),(517,NULL,'AnnualBudget','created','AB-27-28-MKT-001 (Marketing · FY27-28)','App\\Models\\AnnualBudget',9,NULL,'2026-11-21 06:46:17'),(518,NULL,'AnnualBudget','created','AB-27-28-OPER-001 (Operations · FY27-28)','App\\Models\\AnnualBudget',10,NULL,'2026-11-21 06:46:17'),(519,4,'System','Switched Role','DSG — Deputy Secretary General (Basu)',NULL,NULL,'Admin Rahul Mehta switched active session to Basu (DSG — Deputy Secretary General (Basu))','2026-11-21 07:54:23'),(520,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-21 07:54:37'),(521,1,'System','Switched Role','PR & GRN Creator',NULL,NULL,'Admin Rahul Mehta switched active session to Priya Nair (PR & GRN Creator)','2026-11-21 14:09:13'),(522,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-21 14:09:21'),(523,3,'System','Switched Role','Admin Head',NULL,NULL,'Admin Rahul Mehta switched active session to Rahul Mehta (Admin Head)','2026-11-21 14:11:20');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_board_items`
--

DROP TABLE IF EXISTS `b2b_board_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_board_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `stage` varchar(255) NOT NULL DEFAULT 'proposed',
  `tag` varchar(255) DEFAULT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `b2b_board_items_member_id_foreign` (`member_id`),
  CONSTRAINT `b2b_board_items_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_board_items`
--

LOCK TABLES `b2b_board_items` WRITE;
/*!40000 ALTER TABLE `b2b_board_items` DISABLE KEYS */;
INSERT INTO `b2b_board_items` VALUES (1,'Joint export pavilion — Textile Expo 2026','proposed','Textiles',1,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,'Shared cold-chain logistics pilot','proposed','Logistics',3,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(3,'MSME supplier onboarding playbook','in_discussion','Manufacturing',4,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(4,'Freight capacity-sharing agreement','committed','Logistics',5,'2026-08-25 13:00:50','2026-08-25 13:00:50');
/*!40000 ALTER TABLE `b2b_board_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_conversations`
--

DROP TABLE IF EXISTS `b2b_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_one_id` bigint(20) unsigned NOT NULL,
  `member_two_id` bigint(20) unsigned NOT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `b2b_conversations_member_one_id_foreign` (`member_one_id`),
  KEY `b2b_conversations_member_two_id_foreign` (`member_two_id`),
  CONSTRAINT `b2b_conversations_member_one_id_foreign` FOREIGN KEY (`member_one_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `b2b_conversations_member_two_id_foreign` FOREIGN KEY (`member_two_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_conversations`
--

LOCK TABLES `b2b_conversations` WRITE;
/*!40000 ALTER TABLE `b2b_conversations` DISABLE KEYS */;
INSERT INTO `b2b_conversations` VALUES (1,2,3,'2026-08-25 11:00:50','2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,1,6,NULL,'2026-09-19 08:29:54','2026-09-19 08:29:54');
/*!40000 ALTER TABLE `b2b_conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_group_member`
--

DROP TABLE IF EXISTS `b2b_group_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_group_member` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `b2b_group_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `b2b_group_member_b2b_group_id_member_id_unique` (`b2b_group_id`,`member_id`),
  KEY `b2b_group_member_member_id_foreign` (`member_id`),
  CONSTRAINT `b2b_group_member_b2b_group_id_foreign` FOREIGN KEY (`b2b_group_id`) REFERENCES `b2b_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `b2b_group_member_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_group_member`
--

LOCK TABLES `b2b_group_member` WRITE;
/*!40000 ALTER TABLE `b2b_group_member` DISABLE KEYS */;
INSERT INTO `b2b_group_member` VALUES (1,1,1,'2026-08-25 13:00:50'),(2,2,1,'2026-08-25 13:00:50'),(3,2,6,'2026-08-25 13:00:50'),(4,2,8,'2026-08-25 13:00:50'),(5,3,3,'2026-08-25 13:00:50'),(6,3,5,'2026-08-25 13:00:50');
/*!40000 ALTER TABLE `b2b_group_member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_groups`
--

DROP TABLE IF EXISTS `b2b_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_groups`
--

LOCK TABLES `b2b_groups` WRITE;
/*!40000 ALTER TABLE `b2b_groups` DISABLE KEYS */;
INSERT INTO `b2b_groups` VALUES (1,'Delhi NCR Chapter','chapter',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,'Textiles & Apparel Forum','sector',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(3,'Logistics & Supply Chain','sector',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(4,'Young Entrepreneurs Council','cross-sector',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50');
/*!40000 ALTER TABLE `b2b_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_meeting_requests`
--

DROP TABLE IF EXISTS `b2b_meeting_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_meeting_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `trade_delegation_id` bigint(20) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `b2b_meeting_requests_member_id_foreign` (`member_id`),
  KEY `b2b_meeting_requests_trade_delegation_id_foreign` (`trade_delegation_id`),
  CONSTRAINT `b2b_meeting_requests_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `b2b_meeting_requests_trade_delegation_id_foreign` FOREIGN KEY (`trade_delegation_id`) REFERENCES `trade_delegations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_meeting_requests`
--

LOCK TABLES `b2b_meeting_requests` WRITE;
/*!40000 ALTER TABLE `b2b_meeting_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `b2b_meeting_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `b2b_messages`
--

DROP TABLE IF EXISTS `b2b_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `b2b_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `b2b_conversation_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `logged_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `b2b_messages_b2b_conversation_id_foreign` (`b2b_conversation_id`),
  KEY `b2b_messages_member_id_foreign` (`member_id`),
  KEY `b2b_messages_logged_by_foreign` (`logged_by`),
  CONSTRAINT `b2b_messages_b2b_conversation_id_foreign` FOREIGN KEY (`b2b_conversation_id`) REFERENCES `b2b_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `b2b_messages_logged_by_foreign` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `b2b_messages_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `b2b_messages`
--

LOCK TABLES `b2b_messages` WRITE;
/*!40000 ALTER TABLE `b2b_messages` DISABLE KEYS */;
INSERT INTO `b2b_messages` VALUES (1,1,2,'Saw your profile in the Logistics directory — open to a cloud partnership for tracking dashboards?',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,1,3,'Absolutely, happy to explore. Mainly warehouse IoT + real-time tracking for our 3PL clients.',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(3,1,2,'Sure, let\'s set up a call next week.',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50');
/*!40000 ALTER TABLE `b2b_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking_mail_transactions`
--

DROP TABLE IF EXISTS `booking_mail_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_mail_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trf_id` bigint(20) unsigned NOT NULL,
  `flight_log_id` bigint(20) unsigned DEFAULT NULL,
  `mail_type` varchar(255) NOT NULL DEFAULT 'booking_confirmation',
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_mail_transactions_trf_id_foreign` (`trf_id`),
  KEY `booking_mail_transactions_flight_log_id_foreign` (`flight_log_id`),
  KEY `booking_mail_transactions_sent_by_foreign` (`sent_by`),
  CONSTRAINT `booking_mail_transactions_flight_log_id_foreign` FOREIGN KEY (`flight_log_id`) REFERENCES `flight_logs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_mail_transactions_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `booking_mail_transactions_trf_id_foreign` FOREIGN KEY (`trf_id`) REFERENCES `trfs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_mail_transactions`
--

LOCK TABLES `booking_mail_transactions` WRITE;
/*!40000 ALTER TABLE `booking_mail_transactions` DISABLE KEYS */;
INSERT INTO `booking_mail_transactions` VALUES (1,1,1,'booking_confirmation','priya.nair@assocham.test','Priya Nair','Travel Booking Confirmation (FLT-9187) — Flight ticket for TRF-3701','<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>Travel Booking Confirmation</title>\n  <style>\n    body {\n      margin: 0;\n      padding: 0;\n      background-color: #f8fafc;\n      font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;\n      color: #334155;\n    }\n    .email-container {\n      max-width: 580px;\n      margin: 28px auto;\n      background: #ffffff;\n      border-radius: 10px;\n      border: 1px solid #e2e8f0;\n      overflow: hidden;\n    }\n    .header {\n      padding: 24px 28px 18px 28px;\n      border-bottom: 1px solid #f1f5f9;\n      background: #ffffff;\n    }\n    .badge-confirmed {\n      display: inline-block;\n      background-color: #eff6ff;\n      color: #1d4ed8;\n      border: 1px solid #bfdbfe;\n      font-size: 11px;\n      font-weight: 700;\n      letter-spacing: 0.04em;\n      text-transform: uppercase;\n      padding: 4px 10px;\n      border-radius: 9999px;\n      margin-bottom: 10px;\n    }\n    .title {\n      font-size: 19px;\n      font-weight: 700;\n      color: #0f172a;\n      margin: 0 0 4px 0;\n    }\n    .subtitle {\n      font-size: 13px;\n      color: #64748b;\n      margin: 0;\n    }\n    .content {\n      padding: 24px 28px;\n    }\n    .greeting {\n      font-size: 14.5px;\n      font-weight: 600;\n      color: #1e293b;\n      margin-bottom: 12px;\n    }\n    .paragraph {\n      font-size: 13.5px;\n      line-height: 1.6;\n      color: #475569;\n      margin-bottom: 18px;\n    }\n    .card-box {\n      background: #f8fafc;\n      border: 1px solid #e2e8f0;\n      border-radius: 8px;\n      padding: 16px 18px;\n      margin-bottom: 20px;\n    }\n    .card-title {\n      font-size: 11px;\n      font-weight: 700;\n      color: #475569;\n      text-transform: uppercase;\n      letter-spacing: 0.05em;\n      margin-bottom: 10px;\n      border-bottom: 1px solid #e2e8f0;\n      padding-bottom: 6px;\n    }\n    .info-table {\n      width: 100%;\n      border-collapse: collapse;\n      font-size: 13px;\n    }\n    .info-table td {\n      padding: 5px 0;\n      vertical-align: top;\n    }\n    .info-label {\n      width: 40%;\n      color: #64748b;\n      font-weight: 500;\n    }\n    .info-value {\n      width: 60%;\n      color: #0f172a;\n      font-weight: 600;\n    }\n    .footer {\n      padding: 16px 28px;\n      background: #f8fafc;\n      border-top: 1px solid #e2e8f0;\n      font-size: 12px;\n      color: #64748b;\n    }\n  </style>\n</head>\n<body>\n    <div class=\"email-container\">\n    <div class=\"header\">\n      <div class=\"badge-confirmed\">Booking Confirmed</div>\n      <h1 class=\"title\">FLT-9187 — Flight ticket</h1>\n      <p class=\"subtitle\">Travel &amp; Boarding Logistics Confirmation for TRF-3701</p>\n    </div>\n\n    <div class=\"content\">\n      <div class=\"greeting\">Dear Priya Nair,</div>\n      <p class=\"paragraph\">\n        Your official travel booking has been confirmed and logged in the ASSOCHAM Procurement &amp; Travel Portal against requisition <strong>TRF-3701</strong>.\n      </p>\n\n      <div class=\"card-box\">\n        <div class=\"card-title\">Booking &amp; Itinerary Summary</div>\n        <table class=\"info-table\">\n          <tr>\n            <td class=\"info-label\">Booking Reference</td>\n            <td class=\"info-value\">FLT-9187</td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Linked TRF</td>\n            <td class=\"info-value\">TRF-3701</td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Booking Type</td>\n            <td class=\"info-value\">Flight ticket</td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Travel Route</td>\n            <td class=\"info-value\">bn</td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Travel Dates</td>\n            <td class=\"info-value\">\n                              21 Aug 2026 – 26 Aug 2026\n                          </td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Service Provider</td>\n            <td class=\"info-value\">ADGIPS</td>\n          </tr>\n          <tr>\n            <td class=\"info-label\">Booked Amount</td>\n            <td class=\"info-value\">₹1,234.00</td>\n          </tr>\n        </table>\n      </div>\n\n      <p class=\"paragraph\" style=\"margin-bottom:0;\">\n                  Your ticket / voucher proof has been archived against TRF-3701. Please retain boarding passes and submit your settlement claim within 7 working days after trip completion.\n              </p>\n    </div>\n\n    <div class=\"footer\">\n      Recorded by Rahul Mehta &bull; ASSOCHAM Travel &amp; Logistics ERP\n    </div>\n  </div>\n</body>\n</html>\n\n','flight-proofs/ORBCWRAoLYBkF7WCDq10lfArP0ntmHCO73Po1r2b.pdf','failed','Failed to authenticate on SMTP server with username \"info@enseur.in\" using the following authenticators: \"LOGIN\", \"XOAUTH2\". Authenticator \"LOGIN\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.139 Authentication unsuccessful, the user credentials were incorrect. [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:13.454Z 08DF3F6CCA5CF33F]\".\". Authenticator \"XOAUTH2\" returned \"Expected response code \"235\" but got code \"535\", with message \"535 5.7.3 Authentication unsuccessful [MA5PR01CA0089.INDPRD01.PROD.OUTLOOK.COM 2026-11-11T09:33:23.769Z 08DF3F6CCA5CF33F]\".\".',3,'2026-11-11 09:33:29','2026-11-11 09:33:29','2026-11-11 09:33:29');
/*!40000 ALTER TABLE `booking_mail_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `broadcast_messages`
--

DROP TABLE IF EXISTS `broadcast_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `broadcast_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cohort` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `recipient_count` int(10) unsigned NOT NULL,
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `broadcast_messages_sent_by_foreign` (`sent_by`),
  CONSTRAINT `broadcast_messages_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `broadcast_messages`
--

LOCK TABLES `broadcast_messages` WRITE;
/*!40000 ALTER TABLE `broadcast_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `broadcast_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ccs_quotes`
--

DROP TABLE IF EXISTS `ccs_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ccs_quotes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cost_comparative_statement_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `quoted_cost` decimal(14,2) NOT NULL,
  `basic_price` decimal(14,2) DEFAULT NULL,
  `gst_amount` decimal(14,2) DEFAULT NULL,
  `transportation_cost` decimal(14,2) DEFAULT NULL,
  `delivery_days` int(10) unsigned DEFAULT NULL,
  `warranty` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `technical_compliance` varchar(255) DEFAULT 'Yes',
  `quotation_validity` varchar(255) DEFAULT '30 Days',
  `remarks` text DEFAULT NULL,
  `rank` varchar(255) DEFAULT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `selection_remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ccs_quotes_cost_comparative_statement_id_foreign` (`cost_comparative_statement_id`),
  KEY `ccs_quotes_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `ccs_quotes_cost_comparative_statement_id_foreign` FOREIGN KEY (`cost_comparative_statement_id`) REFERENCES `cost_comparative_statements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ccs_quotes_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ccs_quotes`
--

LOCK TABLES `ccs_quotes` WRITE;
/*!40000 ALTER TABLE `ccs_quotes` DISABLE KEYS */;
INSERT INTO `ccs_quotes` VALUES (1,1,1,987.00,NULL,NULL,NULL,8,NULL,NULL,'Yes','30 Days','dfghjk','L1',0,NULL,'2026-08-18 06:25:15','2026-08-18 06:25:15'),(2,2,5,120000.00,NULL,NULL,NULL,45,NULL,NULL,'Yes','30 Days','sdfghjk','L1',0,NULL,'2026-11-04 10:21:55','2026-11-04 10:21:55'),(3,3,1,379600.00,320000.00,57600.00,2000.00,10,'2 Years','30 Days','Yes','30 Days',NULL,'L5',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(4,3,3,396300.00,335000.00,60300.00,1000.00,7,'3 Years','45 Days','Yes','45 Days',NULL,'L6',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(5,3,5,370800.00,310000.00,55800.00,5000.00,15,'1 Year','15 Days','Partial','30 Days',NULL,'L4',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(6,3,6,15.00,NULL,NULL,NULL,3,NULL,NULL,'Yes','30 Days',NULL,'L1',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(7,3,7,16.00,NULL,NULL,NULL,2,NULL,NULL,'Yes','30 Days',NULL,'L2',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(8,3,8,17.00,NULL,NULL,NULL,1,NULL,NULL,'Yes','30 Days',NULL,'L3',0,NULL,'2026-11-04 11:58:58','2026-11-06 08:40:31'),(9,4,5,90.00,NULL,NULL,NULL,1,NULL,NULL,'Yes','30 Days','ghj','L1',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(10,4,6,657.00,NULL,NULL,NULL,NULL,NULL,NULL,'Yes','30 Days','fcvgb','L2',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(11,4,8,3456.00,NULL,NULL,NULL,567,NULL,NULL,'Yes','30 Days','dfghj','L3',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(12,4,7,4567.00,NULL,NULL,NULL,NULL,NULL,NULL,'Yes','30 Days','fcgh','L4',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(13,4,3,78798.00,NULL,NULL,NULL,78,NULL,NULL,'Yes','30 Days','fgvhj','L5',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(14,4,1,234567.00,NULL,NULL,NULL,23,NULL,NULL,'Yes','30 Days','dr','L6',0,NULL,'2026-11-04 18:28:31','2026-11-04 18:28:31'),(21,5,5,370800.00,310000.00,55800.00,5000.00,15,'1 Year','15 Days','Partial','30 Days',NULL,'L1',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(22,5,8,370800.00,310000.00,55800.00,5000.00,15,'1 Year','15 Days','Partial','30 Days',NULL,'L2',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(23,5,1,379600.00,320000.00,57600.00,2000.00,10,'2 Years','30 Days','Yes','30 Days',NULL,'L3',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(24,5,6,379600.00,320000.00,57600.00,2000.00,10,'2 Years','30 Days','Yes','30 Days',NULL,'L4',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(25,5,3,396300.00,335000.00,60300.00,1000.00,7,'3 Years','45 Days','Yes','45 Days',NULL,'L5',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(26,5,7,396300.00,335000.00,60300.00,1000.00,7,'3 Years','45 Days','Yes','45 Days',NULL,'L6',0,NULL,'2026-11-06 08:47:13','2026-11-06 08:47:13'),(28,6,1,953.00,877.00,76.00,0.00,10,'1 Year','30 Days','Yes','30 Days','good','L1',1,'good','2026-11-19 07:57:00','2026-11-19 07:57:21');
/*!40000 ALTER TABLE `ccs_quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_comparative_statements`
--

DROP TABLE IF EXISTS `cost_comparative_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cost_comparative_statements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ccs_number` varchar(255) NOT NULL,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `selected_vendor_id` bigint(20) unsigned DEFAULT NULL,
  `selection_remarks` text DEFAULT NULL,
  `selected_by` bigint(20) unsigned DEFAULT NULL,
  `selected_at` timestamp NULL DEFAULT NULL,
  `award_email_sent_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cost_comparative_statements_ccs_number_unique` (`ccs_number`),
  KEY `cost_comparative_statements_rfq_id_foreign` (`rfq_id`),
  KEY `cost_comparative_statements_approved_by_foreign` (`approved_by`),
  KEY `cost_comparative_statements_selected_vendor_id_foreign` (`selected_vendor_id`),
  KEY `cost_comparative_statements_selected_by_foreign` (`selected_by`),
  CONSTRAINT `cost_comparative_statements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cost_comparative_statements_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cost_comparative_statements_selected_by_foreign` FOREIGN KEY (`selected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cost_comparative_statements_selected_vendor_id_foreign` FOREIGN KEY (`selected_vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_comparative_statements`
--

LOCK TABLES `cost_comparative_statements` WRITE;
/*!40000 ALTER TABLE `cost_comparative_statements` DISABLE KEYS */;
INSERT INTO `cost_comparative_statements` VALUES (1,'CCS-5798',1,NULL,NULL,NULL,NULL,NULL,'approved',3,'2026-08-18 06:42:22','2026-08-18 06:25:15','2026-08-18 06:42:22'),(2,'CCS-9788',4,NULL,NULL,NULL,NULL,NULL,'approved',3,'2026-11-04 10:29:39','2026-11-04 10:21:55','2026-11-04 10:29:39'),(3,'CCS-8740',5,NULL,NULL,NULL,NULL,NULL,'approved',3,'2026-11-04 12:00:15','2026-11-04 11:58:58','2026-11-04 12:00:15'),(4,'CCS-2956',6,NULL,NULL,NULL,NULL,NULL,'approved',3,'2026-11-04 18:30:35','2026-11-04 18:28:31','2026-11-04 18:30:35'),(5,'CCS-5947',7,NULL,NULL,NULL,NULL,NULL,'approved',2,'2026-11-06 08:46:51','2026-11-06 08:46:51','2026-11-06 08:46:51'),(6,'CCS-4440',16,1,'good',3,'2026-11-19 07:57:21',NULL,'approved',3,'2026-11-19 07:56:33','2026-11-19 07:56:33','2026-11-19 07:57:21');
/*!40000 ALTER TABLE `cost_comparative_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `councils`
--

DROP TABLE IF EXISTS `councils`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `councils` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `councils`
--

LOCK TABLES `councils` WRITE;
/*!40000 ALTER TABLE `councils` DISABLE KEYS */;
/*!40000 ALTER TABLE `councils` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_budgets`
--

DROP TABLE IF EXISTS `department_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department` varchar(255) NOT NULL,
  `fiscal_year` varchar(255) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL,
  `utilised_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 18.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `department_budgets_department_fiscal_year_unique` (`department`,`fiscal_year`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_budgets`
--

LOCK TABLES `department_budgets` WRITE;
/*!40000 ALTER TABLE `department_budgets` DISABLE KEYS */;
INSERT INTO `department_budgets` VALUES (1,'Projects','FY26-27',8500000.00,6120330.75,18.00,'2026-08-07 13:16:31','2026-11-04 10:56:18'),(2,'IT','FY26-27',2200000.00,1825194.38,18.00,'2026-08-07 13:16:31','2026-11-04 18:06:53'),(3,'Marketing','FY26-27',1500000.00,1380000.00,18.00,'2026-08-07 13:16:31','2026-11-02 05:51:39'),(4,'Finance','FY26-27',900000.00,310000.00,18.00,'2026-08-07 13:16:31','2026-08-25 13:00:50'),(5,'Operations','FY26-27',3200000.00,2762596.00,18.00,'2026-08-07 13:16:31','2026-10-23 07:07:41'),(6,'HR','FY26-27',1200000.00,450000.00,18.00,'2026-08-07 13:16:31','2026-08-25 13:00:50');
/*!40000 ALTER TABLE `department_budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `event_passes`
--

DROP TABLE IF EXISTS `event_passes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_passes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` bigint(20) unsigned NOT NULL,
  `member_id` bigint(20) unsigned NOT NULL,
  `pass_code` varchar(255) NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_passes_event_id_member_id_unique` (`event_id`,`member_id`),
  UNIQUE KEY `event_passes_pass_code_unique` (`pass_code`),
  KEY `event_passes_member_id_foreign` (`member_id`),
  CONSTRAINT `event_passes_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `event_passes_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_passes`
--

LOCK TABLES `event_passes` WRITE;
/*!40000 ALTER TABLE `event_passes` DISABLE KEYS */;
/*!40000 ALTER TABLE `event_passes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `council_label` varchar(255) DEFAULT NULL,
  `format` varchar(255) NOT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `access_note` varchar(255) DEFAULT NULL,
  `is_vip` tinyint(1) NOT NULL DEFAULT 0,
  `non_member_pass_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `format_guidance_note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exceptions`
--

DROP TABLE IF EXISTS `exceptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exceptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `exception_type` varchar(255) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `justification` text NOT NULL,
  `requested_by` bigint(20) unsigned NOT NULL,
  `approver_queue` varchar(255) NOT NULL DEFAULT 'sg',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `decided_by` bigint(20) unsigned DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exceptions_requested_by_foreign` (`requested_by`),
  KEY `exceptions_decided_by_foreign` (`decided_by`),
  CONSTRAINT `exceptions_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `exceptions_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exceptions`
--

LOCK TABLES `exceptions` WRITE;
/*!40000 ALTER TABLE `exceptions` DISABLE KEYS */;
INSERT INTO `exceptions` VALUES (1,'budget_exceed',NULL,NULL,'dxfcghbjn',3,'cfo','rejected',8,'2026-08-18 07:05:37','2026-08-18 07:04:16','2026-08-18 07:05:37');
/*!40000 ALTER TABLE `exceptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `flight_logs`
--

DROP TABLE IF EXISTS `flight_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `flight_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ref` varchar(255) NOT NULL,
  `trf_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flight_logs_ref_unique` (`ref`),
  KEY `flight_logs_trf_id_foreign` (`trf_id`),
  KEY `flight_logs_vendor_id_foreign` (`vendor_id`),
  KEY `flight_logs_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `flight_logs_trf_id_foreign` FOREIGN KEY (`trf_id`) REFERENCES `trfs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flight_logs_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flight_logs_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `flight_logs`
--

LOCK TABLES `flight_logs` WRITE;
/*!40000 ALTER TABLE `flight_logs` DISABLE KEYS */;
INSERT INTO `flight_logs` VALUES (1,'FLT-9187',1,'Flight ticket',7,1234.00,'flight-proofs/ORBCWRAoLYBkF7WCDq10lfArP0ntmHCO73Po1r2b.pdf',3,'2026-11-04 19:20:05','2026-11-04 19:20:05'),(2,'FLT-DEMO-810',4,'Flight ticket',12,11450.00,NULL,3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(3,'FLT-DEMO-811',5,'Hotel',12,28600.00,NULL,3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(4,'FLT-DEMO-812',6,'Flight ticket',13,9800.00,NULL,3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(5,'FLT-DEMO-813',7,'Hotel',14,7600.00,NULL,3,'2026-11-11 10:36:36','2026-11-11 10:36:36');
/*!40000 ALTER TABLE `flight_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grievance_updates`
--

DROP TABLE IF EXISTS `grievance_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grievance_updates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grievance_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `owner_role` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grievance_updates_grievance_id_foreign` (`grievance_id`),
  KEY `grievance_updates_user_id_foreign` (`user_id`),
  CONSTRAINT `grievance_updates_grievance_id_foreign` FOREIGN KEY (`grievance_id`) REFERENCES `grievances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grievance_updates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grievance_updates`
--

LOCK TABLES `grievance_updates` WRITE;
/*!40000 ALTER TABLE `grievance_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `grievance_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grievances`
--

DROP TABLE IF EXISTS `grievances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grievances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(255) NOT NULL,
  `subject_category` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `reimbursement_id` bigint(20) unsigned DEFAULT NULL,
  `raised_by` bigint(20) unsigned NOT NULL,
  `current_owner_role` varchar(255) NOT NULL DEFAULT 'hod',
  `status` varchar(255) NOT NULL DEFAULT 'under_review',
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grievances_ticket_number_unique` (`ticket_number`),
  KEY `grievances_raised_by_foreign` (`raised_by`),
  KEY `grievances_resolved_by_foreign` (`resolved_by`),
  KEY `grievances_reimbursement_id_foreign` (`reimbursement_id`),
  CONSTRAINT `grievances_raised_by_foreign` FOREIGN KEY (`raised_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grievances_reimbursement_id_foreign` FOREIGN KEY (`reimbursement_id`) REFERENCES `reimbursements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `grievances_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grievances`
--

LOCK TABLES `grievances` WRITE;
/*!40000 ALTER TABLE `grievances` DISABLE KEYS */;
INSERT INTO `grievances` VALUES (1,'GRV-5562','Technical Issue','szxdcfvgbhnm','sdfghj',NULL,1,'hod','resolved',10,'2026-08-14 11:27:42',NULL,'2026-08-13 13:23:25','2026-08-14 11:27:42'),(2,'GRV-6336','Technical Issue','b b b','bbn',NULL,1,'admin_head','resolved',8,'2026-08-18 07:10:56',NULL,'2026-08-13 13:27:58','2026-08-18 07:10:56'),(3,'GRV-2932','Technical Issue','Dolorum non non rem quos quas debitis labore ut quis.','nn',NULL,8,'admin_head','resolved',10,'2026-08-19 07:05:19',NULL,'2026-08-19 06:53:26','2026-08-19 07:05:19');
/*!40000 ALTER TABLE `grievances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grns`
--

DROP TABLE IF EXISTS `grns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grn_number` varchar(255) NOT NULL,
  `purchase_order_id` bigint(20) unsigned NOT NULL,
  `purchase_requisition_id` bigint(20) unsigned DEFAULT NULL,
  `item_description` varchar(255) DEFAULT NULL,
  `po_qty` int(10) unsigned NOT NULL,
  `received_qty` int(10) unsigned NOT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `receipt_date` date DEFAULT NULL,
  `quality_remarks` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_hod',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grns_grn_number_unique` (`grn_number`),
  KEY `grns_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `grns_created_by_foreign` (`created_by`),
  KEY `grns_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  CONSTRAINT `grns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grns_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grns_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grns`
--

LOCK TABLES `grns` WRITE;
/*!40000 ALTER TABLE `grns` DISABLE KEYS */;
INSERT INTO `grns` VALUES (1,'GRN-3644',2,3,'item 1, item2',22,1,'1211232','2026-11-04','sdfwdewd','approved',1,'2026-11-04 18:34:09','2026-11-04 18:34:55'),(3,'GRN-4001',1,3,'item 1, item2',22,5,'INV-2026-8812','2026-11-15','Initial bulk lot received at YMCA Stores. Physical outer packaging verified intact.','pending_hod',1,'2026-11-17 10:24:01','2026-11-17 10:24:01'),(4,'GRN-4002',2,3,'item 1, item2',26,8,'CH-DEL-5421','2026-11-12','Complete hardware batch tested and verified. Serial numbers mapped to IT inventory register.','approved',1,'2026-11-17 10:24:01','2026-11-17 10:24:01'),(5,'GRN-4003',1,3,'item 1, item2',22,3,'INV-99430-A','2026-11-16','Consignment inspected on site; preliminary check satisfactory. Awaiting HOD final verification.','pending_hod',1,'2026-11-17 10:24:01','2026-11-17 10:24:01'),(6,'GRN-4004',2,3,'item 1, item2',26,4,'INV-BL-7741','2026-11-10','All items unpacked, tested, and accepted against technical specifications.','approved',1,'2026-11-17 10:24:01','2026-11-17 10:24:01');
/*!40000 ALTER TABLE `grns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_catalogs`
--

DROP TABLE IF EXISTS `item_catalogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_catalogs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `expense_type` varchar(20) NOT NULL DEFAULT 'opex',
  `fixed_price` decimal(14,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'Units',
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 18.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_catalogs_item_code_unique` (`item_code`),
  KEY `item_catalogs_created_by_foreign` (`created_by`),
  CONSTRAINT `item_catalogs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_catalogs`
--

LOCK TABLES `item_catalogs` WRITE;
/*!40000 ALTER TABLE `item_catalogs` DISABLE KEYS */;
INSERT INTO `item_catalogs` VALUES (1,'COPEX-001','High-Performance Executive Laptop','IT & Hardware','copex',85000.00,'Units',18.00,'Dell Latitude 5540, Intel Core i7 13th Gen, 16GB RAM, 512GB SSD, 3-Year Onsite ProSupport.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(2,'COPEX-002','Laptops','IT & Hardware','copex',65000.00,'Units',18.00,'Standard enterprise workstation laptop for staff, 16GB RAM, 512GB SSD, Windows 11 Pro.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(3,'COPEX-003','High-End Mobile Workstation','IT & Hardware','copex',145000.00,'Units',18.00,'Dell Precision 3580, Nvidia RTX A500, 32GB RAM, 1TB SSD for design and data analytics.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(4,'COPEX-004','Conference Room 4K LED Display & VC System','AV & Electronics','copex',320000.00,'Sets',18.00,'75-inch commercial 4K display, PTZ optical zoom camera, wireless microphone array.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(5,'COPEX-005','Cisco Catalyst Core Network Switch','IT & Infrastructure','copex',180000.00,'Units',18.00,'48-port Gigabit PoE+ managed enterprise core switch with redundant power supplies.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(6,'COPEX-006','Enterprise Server PowerEdge R750','IT & Infrastructure','copex',850000.00,'Units',18.00,'Dual Intel Xeon Silver, 128GB ECC RAM, RAID-10 enterprise NVMe storage array.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(7,'COPEX-007','Heavy Duty Industrial Multi-Function Copier','Office Equipment','copex',160000.00,'Units',18.00,'Floor-standing color A3 laser multi-function printer with finisher unit.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(8,'COPEX-008','Ergonomic Executive Workstation & Chair Set','Office Furniture','copex',45000.00,'Sets',18.00,'Modular motorized height-adjustable desk and BIFMA ergonomic high-back mesh chair.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(9,'OPEX-001','Executive Printing & Stationery Kit','Stationery & Consumables','opex',12500.00,'Sets',18.00,'ASSOCHAM branded executive letterheads, envelopes, note pads, and writing supplies.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(10,'OPEX-002','Monthly Office Housekeeping & Consumables','Facilities & Maintenance','opex',35000.00,'Months',18.00,'Monthly pantry, sanitization, and housekeeping consumables.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(11,'OPEX-003','Annual Cloud ERP & Hosting Subscription','Software & Cloud','opex',240000.00,'Years',18.00,'Secure cloud hosting, daily backups, and SSL infrastructure maintenance.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(12,'OPEX-004','Professional Event Stage & AV Rental','Event Operations','opex',175000.00,'Days',18.00,'Daily rental for conference stage lighting, sound system, and technician support.',1,NULL,'2026-11-19 14:31:56','2026-11-19 14:31:56'),(13,'OPEX-005','Courier & Express Logistics Bulk Package','Logistics','opex',18000.00,'Packages',18.00,'Bulk nationwide dispatch package for official event passes and summit brochures.',1,NULL,'2026-11-19 14:31:57','2026-11-19 14:31:57'),(14,'OPEX-006','Annual Central HVAC Comprehensive AMC','Facilities & Maintenance','opex',650000.00,'Years',18.00,'High-value annual comprehensive heating, ventilation, and air-conditioning maintenance contract.',1,NULL,'2026-11-19 14:31:57','2026-11-19 14:31:57');
/*!40000 ALTER TABLE `item_catalogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_council`
--

DROP TABLE IF EXISTS `member_council`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_council` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `council_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `member_council_member_id_council_id_unique` (`member_id`,`council_id`),
  KEY `member_council_council_id_foreign` (`council_id`),
  CONSTRAINT `member_council_council_id_foreign` FOREIGN KEY (`council_id`) REFERENCES `councils` (`id`) ON DELETE CASCADE,
  CONSTRAINT `member_council_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_council`
--

LOCK TABLES `member_council` WRITE;
/*!40000 ALTER TABLE `member_council` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_council` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `member_import_batches`
--

DROP TABLE IF EXISTS `member_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `member_import_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(10) unsigned NOT NULL,
  `success_count` int(10) unsigned NOT NULL,
  `error_count` int(10) unsigned NOT NULL,
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `member_import_batches_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `member_import_batches_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `member_import_batches`
--

LOCK TABLES `member_import_batches` WRITE;
/*!40000 ALTER TABLE `member_import_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `member_import_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_code` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'Corporate',
  `entity_type` varchar(255) DEFAULT NULL,
  `turnover_slab` varchar(255) DEFAULT NULL,
  `startup_tier` varchar(255) DEFAULT NULL,
  `dpiit_number` varchar(255) DEFAULT NULL,
  `trl_level` varchar(255) DEFAULT NULL,
  `startup_stage` varchar(255) DEFAULT NULL,
  `international_entity_type` varchar(255) DEFAULT NULL,
  `msme_registered` varchar(255) DEFAULT NULL,
  `business_description` text DEFAULT NULL,
  `gst_certificate_path` varchar(255) DEFAULT NULL,
  `incorporation_certificate_path` varchar(255) DEFAULT NULL,
  `financials_path` varchar(255) DEFAULT NULL,
  `msme_dpiit_certificate_path` varchar(255) DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `utr_number` varchar(255) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `annual_fee` decimal(12,2) DEFAULT NULL,
  `admission_fee` decimal(12,2) DEFAULT NULL,
  `gst_amount` decimal(12,2) DEFAULT NULL,
  `total_payable` decimal(12,2) DEFAULT NULL,
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_next_due_at` timestamp NULL DEFAULT NULL,
  `b2b_lead_status` varchar(255) DEFAULT NULL,
  `b2b_lead_invited_at` timestamp NULL DEFAULT NULL,
  `fee_currency` varchar(3) NOT NULL DEFAULT 'INR',
  `sector` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `chapter` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'applied',
  `member_since` date DEFAULT NULL,
  `primary_contact` varchar(255) DEFAULT NULL,
  `nominee_designation` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `gstin` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `year_established` varchar(4) DEFAULT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `country` varchar(255) NOT NULL DEFAULT 'India',
  `tags` varchar(255) DEFAULT NULL,
  `directory_visibility` varchar(255) NOT NULL DEFAULT 'public',
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `members_member_code_unique` (`member_code`),
  KEY `members_submitted_by_foreign` (`submitted_by`),
  CONSTRAINT `members_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
INSERT INTO `members` VALUES (1,'MEM-C-08842','Raheja Textiles Pvt. Ltd.',NULL,'Corporate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Textiles & Apparel','Noida','Delhi NCR','active','2019-03-01','Anjali Raheja, Director',NULL,'anjali.raheja@rahejatextiles.in','$2y$12$9JJ1nP.Cay8hcPiHPHQ5J.qtA9Zpk5zttQJAZBK1RsWicojzoYTQ6',NULL,NULL,'07AAAAA0000A1Z5','Sector 63, Noida, UP',NULL,NULL,NULL,'India','Export-oriented,MSME','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:22'),(2,'MEM-A-19204','Vantage Networks LLP',NULL,'Associate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','IT Services','Bengaluru','Karnataka','active','2022-01-15',NULL,NULL,'contact@vantagenetworks.test','$2y$12$mr5AB6nmk/yfyxeX3TSOcehjhfBzuU3bQFvmlNkgGlcm1Jvre3uNW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India','Cloud,B2B SaaS','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:23'),(3,'MEM-C-04471','Sundara Logistics',NULL,'Corporate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Logistics & Freight','Chennai','Tamil Nadu','active','2017-06-01',NULL,NULL,'contact@sundaralogistics.test','$2y$12$EpQFtRRC033zcSmiRWVdUe.b2AXxTgMbcHpPzI1MHjQOCAUgxu71e',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India','Cold-chain,Fleet','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:23'),(4,'MEM-C-03301','Orion Componentz',NULL,'Corporate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Manufacturing','Pune','Maharashtra','renewal_due','2015-09-02',NULL,NULL,'contact@orioncomponentz.test','$2y$12$AqgOvKsPThsSq3xe5cqdiecIHJlumcca7DvneArl7kUQ.j2VIT2zW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India','Auto ancillary','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:23'),(5,'MEM-C-05512','Meridian Freight',NULL,'Corporate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Logistics & Freight','Mumbai','Maharashtra','active','2021-04-01',NULL,NULL,'contact@meridianfreight.test','$2y$12$FaUYaRyaUSmx9BK53xuEoetv.iOj2eCPIUSWI3dJIg3hG8rtJQWHm',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India','3PL,Exports','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:23'),(6,'MEM-I-07733','Bharti Fabtech',NULL,'Individual',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Textiles & Apparel','Surat','Gujarat','expired','2020-06-18',NULL,NULL,'contact@bhartifabtech.test','$2y$12$YEOWWu6EHMwzdbVNemfGn.e4k6mLIu/skUB/YsWr5nRGRc9yvlK4e',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India','Weaving','public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:23'),(7,'MEM-I-09912','Ghosh & Associates',NULL,'Individual',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Manufacturing','Kolkata',NULL,'docs_verified',NULL,NULL,NULL,'contact@ghoshassociates.test','$2y$12$qRgihuP3IfDc37owXQ.ene9yY.WOGxPPMHole8hH7iXsP05XBjXtW',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,'public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(8,'MEM-A-14401','Nakul Knitworks',NULL,'Associate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Textiles & Apparel','Ludhiana',NULL,'approval',NULL,NULL,NULL,'contact@nakulknitworks.test','$2y$12$AzucrSziX.btLbNc1shJIedXNJxaEY3SpEsyY7eq9wZjYskvz7Nci',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,'public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(9,'MEM-C-16650','Aster Cold Chain',NULL,'Corporate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','Logistics & Freight','Hyderabad',NULL,'payment',NULL,NULL,NULL,'contact@astercoldchain.test','$2y$12$hLk4auebul2lSQBUxW6IDevPV5Vppr2Fh1vcGybgOy2LLKUI0Q67y',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,'public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(10,'MEM-A-21008','Halcyon Data Systems',NULL,'Associate',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'INR','IT Services','Pune',NULL,'applied',NULL,NULL,NULL,'contact@halcyondata.test','$2y$12$hrJkPeuWMTansS1mKziBfe2CdWJSyhEEdObNsyWsEsgUUQAEfNPpu',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'India',NULL,'public',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(11,'MEM-A-57954','Meyer and Walker Trading','Velma Moses','Associate','Limited Liability Partnership (LLP)','20_to_50cr',NULL,NULL,NULL,NULL,NULL,'no','In quidem quisquam a','membership-documents/MEM-A-57954/gst_certificate.pdf','membership-documents/MEM-A-57954/incorporation_certificate.pdf','membership-documents/MEM-A-57954/financials.pdf','membership-documents/MEM-A-57954/msme_dpiit_certificate.pdf','offline','W4etyrytjghj','2026-11-04',50000.00,5000.00,9900.00,64900.00,NULL,NULL,NULL,NULL,'INR','Banking, Financial Services & Insurance (BFSI)','Sed excepteur sint a',NULL,'applied',NULL,'Voluptate est porro','Dolore dolorem obcae','vusel@mailinator.com','$2y$12$PfcuImoK4OU0pC7Zo1oEF.EudSSUrdRg4ssiZv9YvzIac6UZtAR56',NULL,'+1 (432) 783-7406','Eum nulla quibusdam','Non sed aut labore s','https://www.tiwatusocake.me.uk','1973','979','India',NULL,'public',NULL,'2026-11-02 12:24:00','2026-11-02 12:24:00');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_certificates`
--

DROP TABLE IF EXISTS `membership_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `certificate_no` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `issued_at` date NOT NULL,
  `valid_till` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_signature',
  `signed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membership_certificates_certificate_no_unique` (`certificate_no`),
  KEY `membership_certificates_member_id_foreign` (`member_id`),
  KEY `membership_certificates_signed_by_foreign` (`signed_by`),
  CONSTRAINT `membership_certificates_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `membership_certificates_signed_by_foreign` FOREIGN KEY (`signed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_certificates`
--

LOCK TABLES `membership_certificates` WRITE;
/*!40000 ALTER TABLE `membership_certificates` DISABLE KEYS */;
INSERT INTO `membership_certificates` VALUES (1,8,'ASSOCHAM-CT-20260391','Associate','2026-07-29','2027-07-28','active',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,5,'ASSOCHAM-CT-20260355','Corporate','2026-07-14','2027-07-13','active',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(3,4,'ASSOCHAM-CT-20250210','Corporate','2025-09-02','2026-09-01','active',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(4,6,'ASSOCHAM-CT-20250109','Individual','2025-06-18','2026-06-17','expired',NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50');
/*!40000 ALTER TABLE `membership_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membership_tickets`
--

DROP TABLE IF EXISTS `membership_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membership_tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_ref` varchar(255) NOT NULL,
  `member_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `first_responded_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `membership_tickets_ticket_ref_unique` (`ticket_ref`),
  KEY `membership_tickets_member_id_foreign` (`member_id`),
  KEY `membership_tickets_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `membership_tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `membership_tickets_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_tickets`
--

LOCK TABLES `membership_tickets` WRITE;
/*!40000 ALTER TABLE `membership_tickets` DISABLE KEYS */;
INSERT INTO `membership_tickets` VALUES (1,'#4471',1,'Certificate not received','Certificate','high','open',NULL,NULL,NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(2,'#4468',2,'Unable to update GST details','Profile Update','medium','open',NULL,NULL,NULL,'2026-08-25 13:00:50','2026-08-25 13:00:50'),(3,'#4465',7,'Directory listing shows old address','Directory','medium','in_progress','2026-09-18 06:22:24',NULL,NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(4,'#4460',3,'Payment receipt requested','Billing','low','resolved','2026-09-17 05:22:24','Receipt re-sent by email.',NULL,'2026-08-25 13:00:50','2026-09-19 08:22:24'),(5,'#2061',11,'Request: Additional Council Affiliation','Other','low','open',NULL,NULL,NULL,'2026-11-02 12:25:46','2026-11-02 12:25:46'),(6,'#2570',11,'Request: Additional Council Affiliation','Other','low','open',NULL,NULL,NULL,'2026-11-02 12:37:31','2026-11-02 12:37:31'),(7,'#4368',11,'Request: Additional Council Affiliation','Other','low','open',NULL,NULL,NULL,'2026-11-02 12:40:21','2026-11-02 12:40:21'),(8,'#6569',11,'Dolorum non non rem quos quas debitis labore ut quis.','Profile Update','high','open',NULL,NULL,NULL,'2026-11-02 12:40:55','2026-11-02 12:40:55');
/*!40000 ALTER TABLE `membership_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_08_07_000001_add_role_and_department_to_users_table',1),(5,'2026_08_07_000002_create_department_budgets_table',1),(6,'2026_08_07_000003_create_vendors_table',1),(7,'2026_08_07_000004_create_purchase_requisitions_table',1),(8,'2026_08_07_000005_create_purchase_requisition_items_table',1),(9,'2026_08_07_000006_create_rfqs_table',1),(10,'2026_08_07_000007_create_rfq_vendors_table',1),(11,'2026_08_07_000008_create_cost_comparative_statements_table',1),(12,'2026_08_07_000009_create_ccs_quotes_table',1),(13,'2026_08_07_000010_create_purchase_orders_table',1),(14,'2026_08_07_000011_create_grns_table',1),(15,'2026_08_07_000012_create_trfs_table',1),(16,'2026_08_07_000013_create_flight_logs_table',1),(17,'2026_08_07_000014_create_reimbursements_table',1),(18,'2026_08_07_000015_create_vendor_evaluations_table',1),(19,'2026_08_07_000016_create_exceptions_table',1),(20,'2026_08_07_000017_create_grievances_table',1),(21,'2026_08_07_000018_create_approvals_table',1),(22,'2026_08_07_000019_create_attachments_table',1),(23,'2026_08_07_000020_create_audit_logs_table',1),(24,'2026_08_07_000021_add_ccs_to_purchase_orders_table',1),(25,'2026_08_07_000022_add_reason_to_reimbursements_table',1),(26,'2026_08_25_075618_create_members_table',2),(27,'2026_08_25_075619_create_membership_certificates_table',2),(28,'2026_08_25_075620_create_b2b_groups_table',2),(29,'2026_08_25_075620_create_membership_tickets_table',2),(30,'2026_08_25_075621_create_b2b_conversations_table',2),(31,'2026_08_25_075621_create_b2b_group_member_table',2),(32,'2026_08_25_075622_create_b2b_messages_table',2),(33,'2026_08_25_075623_create_b2b_board_items_table',2),(34,'2026_09_19_071202_add_password_to_members_table',3),(35,'2026_10_29_101739_create_spocs_table',4),(36,'2026_10_29_102923_add_fee_engine_fields_to_members_table',4),(37,'2026_10_29_104157_add_registration_wizard_fields_to_members_table',4),(38,'2026_10_29_105231_create_councils_table',4),(39,'2026_10_29_105232_create_member_council_table',4),(40,'2026_10_29_110148_create_events_table',4),(41,'2026_10_29_110149_create_event_passes_table',4),(42,'2026_10_29_110149_create_trade_delegations_table',4),(43,'2026_10_29_110150_create_b2b_meeting_requests_table',4),(44,'2026_10_29_111017_add_kyc_fields_to_members_table',4),(45,'2026_10_29_111744_create_member_import_batches_table',4),(46,'2026_10_29_111745_add_b2b_lead_fields_to_members_table',4),(47,'2026_10_29_111745_create_broadcast_messages_table',4),(48,'2026_10_29_111746_add_meeting_mode_fields_to_events_table',4),(49,'2026_11_06_140234_add_quotation_parameters_to_ccs_quotes_table',5),(50,'2026_11_07_044500_add_location_rating_previous_orders_to_vendors_table',6),(51,'2026_11_07_050000_add_commercial_and_terms_to_purchase_orders_table',7),(52,'2026_11_09_061945_add_blacklist_fields_to_vendors_table',8),(53,'2026_11_09_141656_add_description_to_purchase_requisitions_and_items_tables',9),(54,'2026_11_09_143344_add_title_to_purchase_requisitions_table',10),(55,'2026_11_09_152438_create_purchase_requisition_versions_table',11),(56,'2026_11_10_133726_add_sector_and_project_name_to_rfqs_table',12),(57,'2026_11_10_143129_add_empanelment_email_sent_to_vendors_table',13),(58,'2026_11_10_144507_add_details_to_purchase_requisitions_table',14),(59,'2026_11_10_153413_add_due_date_and_email_dispatched_at_to_rfqs_and_rfq_vendors',15),(60,'2026_11_10_162102_add_manual_selection_to_cost_comparative_statements_and_ccs_quotes',16),(61,'2026_11_10_164639_add_disk_to_attachments_table',17),(62,'2026_11_10_165346_add_award_email_sent_to_cost_comparative_statements',18),(63,'2026_11_11_123000_add_regeneration_tracking_to_purchase_orders_table',19),(64,'2026_11_11_124000_add_purchase_requisition_id_to_grns_table',20),(65,'2026_11_11_131500_add_itinerary_fields_to_trfs_table',21),(66,'2026_11_11_150000_create_booking_mail_transactions_table',22),(67,'2026_11_11_153000_make_flight_log_id_nullable_on_booking_mail_transactions_table',23),(68,'2026_11_11_160000_create_reimbursement_items_table',24),(69,'2026_11_11_171000_add_reimbursement_and_resolution_to_grievances_table',25),(70,'2026_11_11_173000_create_grievance_updates_table',26),(71,'2026_11_11_174500_add_designation_and_allowed_modules_to_users_table',27),(72,'2026_11_16_160000_add_excel_form_fields_to_vendors_table',28),(73,'2026_11_17_170000_create_project_budgets_table',29),(74,'2026_11_17_174000_add_event_format_fields_to_project_budgets_table',30),(76,'2026_11_17_200702_add_project_budget_id_to_purchase_requisitions_table',31),(77,'2026_11_18_000001_add_status_fields_to_users_table',32),(78,'2026_11_19_200000_create_item_catalogs_and_add_expense_type',33),(79,'2026_11_19_233000_add_item_wise_vendor_and_po_to_purchase_requisition_items_table',34),(80,'2026_11_19_234500_add_deleted_at_to_users_table',35),(81,'2026_11_20_000001_create_annual_budgets_table',36);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_budgets`
--

DROP TABLE IF EXISTS `project_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_budgets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `council` varchar(255) DEFAULT NULL,
  `cluster_head` varchar(255) DEFAULT NULL,
  `hod` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_month` varchar(255) DEFAULT NULL,
  `fiscal_year` varchar(255) NOT NULL,
  `allocated_amount` decimal(14,2) NOT NULL,
  `utilised_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_budgets_project_name_fiscal_year_unique` (`project_name`,`fiscal_year`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_budgets`
--

LOCK TABLES `project_budgets` WRITE;
/*!40000 ALTER TABLE `project_budgets` DISABLE KEYS */;
INSERT INTO `project_budgets` VALUES (10,'Conference on Building a Resilient Critical Minerals Supply Chain in India','Projects','Green Mobility','Rajesh Sharma','Rajesh Verma','Minor','Delhi','Jun 2026','FY26-27',1500000.00,390200.00,'2026-11-17 12:08:30','2026-11-17 14:59:53'),(14,'National Conference on Green Mobility & EV Infrastructure','Projects','Green Mobility','Rajesh Sharma','Rajesh Verma','Flagship','Delhi','Aug 2026','FY26-27',3500000.00,2360000.00,'2026-11-17 14:55:33','2026-11-17 14:59:53'),(16,'National Summit on Changing Landscape of Indian Automotive Sector','Projects','Auto & Auto Ancillaries','Rajesh Sharma','Rajesh Verma','Major','Delhi','Oct 2026','FY26-27',2800000.00,0.00,'2026-11-17 14:55:33','2026-11-17 14:55:33'),(17,'National Education & Future Skills Conclave','Projects','Education & Skills','Dr. Anita Roy','Rajesh Verma','Major','Bengaluru','Dec 2026','FY26-27',3000000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(18,'India Leadership Banquet & Corporate Governance Awards','Projects','Corporate Affairs','S. K. Bansal','Rajesh Verma','Flagship','Mumbai','Jan 2027','FY26-27',2500000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(19,'East India Regional Trade & Investment Expo','Marketing','Regional Trade & Investment','Sunil Ghosh','Priya Nair','Flagship','Kolkata','Oct 2026','FY26-27',1500000.00,1203600.00,'2026-11-17 14:55:34','2026-11-17 14:59:53'),(20,'National Membership Drive & Industry Outreach Program','Marketing','Member Relations & Outreach','Sunil Ghosh','Priya Nair','Membership','Pan-India','Apr 2026 - Mar 2027','FY26-27',1000000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(21,'Digital Transformation & Enterprise Cyber Security Conclave','IT','Technology & Systems','Deepak Tyagi','Amitabh Sen','Major','Delhi','Jul 2026','FY26-27',1200000.00,318600.00,'2026-11-17 14:55:34','2026-11-17 14:59:53'),(22,'National MSME Competitiveness & Financing Summit','Projects','MSME & Financial Inclusion','R. K. Mehra','Rajesh Verma','Minor','Ahmedabad','Sep 2026','FY26-27',1400000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(23,'National Healthcare Infrastructure & MedTech Forum','Projects','Healthcare & Lifesciences','Dr. Anita Roy','Rajesh Verma','Major','Hyderabad','Nov 2026','FY26-27',2200000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(24,'National Logistics & Multimodal Supply Chain Conclave','Operations','Logistics & Supply Chain','V. Raman','Vikram Seth','Major','Delhi','Aug 2026','FY26-27',2500000.00,0.00,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(25,'Annual Strategy Conclave','Projects','Strategic Initiatives','Manish Gupta','Rajesh Verma','Flagship','Delhi','Nov 2026','FY26-27',3000000.00,0.00,'2026-11-17 14:56:52','2026-11-17 14:56:52');
/*!40000 ALTER TABLE `project_budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(255) NOT NULL,
  `purchase_requisition_id` bigint(20) unsigned NOT NULL,
  `cost_comparative_statement_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `po_qty` int(10) unsigned NOT NULL DEFAULT 1,
  `delivery_date` date DEFAULT NULL,
  `amount_incl_gst` decimal(14,2) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `delivery_terms` text DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(255) DEFAULT NULL,
  `terms_and_conditions` longtext DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `needs_regeneration` tinyint(1) NOT NULL DEFAULT 0,
  `revision_number` int(10) unsigned NOT NULL DEFAULT 0,
  `regenerated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `purchase_orders_vendor_id_foreign` (`vendor_id`),
  KEY `purchase_orders_created_by_foreign` (`created_by`),
  KEY `purchase_orders_cost_comparative_statement_id_foreign` (`cost_comparative_statement_id`),
  CONSTRAINT `purchase_orders_cost_comparative_statement_id_foreign` FOREIGN KEY (`cost_comparative_statement_id`) REFERENCES `cost_comparative_statements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_orders_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-2026-4225',3,1,1,22,'2026-08-19',987.00,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'approved',0,0,NULL,2,'2026-08-18 06:44:08','2026-08-18 06:46:03'),(2,'PO-2026-6284',3,4,5,26,'2026-11-06',90.00,'Finance Requisition Procurement','Net 30123','Delivery on or before 06 Nov 2026',NULL,'THE ASSOCIATED CHAMBERS OF COMMERCE & INDUSTRY OF INDIA\nASSOCHAM Central Stores / Delhi Office, YMCA Building, New Delhi – 110001','Priya Nair',NULL,NULL,'pending',0,0,NULL,2,'2026-11-04 18:32:10','2026-11-19 14:09:34');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_requisition_items`
--

DROP TABLE IF EXISTS `purchase_requisition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_requisition_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_requisition_id` bigint(20) unsigned NOT NULL,
  `purchase_order_id` bigint(20) unsigned DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `unit` varchar(255) DEFAULT NULL,
  `unit_cost` decimal(14,2) NOT NULL,
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 18.00,
  `expense_type` varchar(20) NOT NULL DEFAULT 'opex',
  `item_catalog_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_requisition_items_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `purchase_requisition_items_item_catalog_id_foreign` (`item_catalog_id`),
  KEY `purchase_requisition_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_requisition_items_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `purchase_requisition_items_item_catalog_id_foreign` FOREIGN KEY (`item_catalog_id`) REFERENCES `item_catalogs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_requisition_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_requisition_items_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requisition_items_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisition_items`
--

LOCK TABLES `purchase_requisition_items` WRITE;
/*!40000 ALTER TABLE `purchase_requisition_items` DISABLE KEYS */;
INSERT INTO `purchase_requisition_items` VALUES (1,1,NULL,NULL,'ghj',NULL,1,'8',98.00,18.00,'opex',NULL,'pending','2026-08-13 11:58:00','2026-08-13 11:58:00'),(2,1,NULL,NULL,'gh',NULL,1,'9',98.00,18.00,'opex',NULL,'pending','2026-08-13 11:58:00','2026-08-13 11:58:00'),(3,2,NULL,NULL,'gbhnj',NULL,1,'90',98.00,18.00,'opex',NULL,'pending','2026-08-13 13:21:03','2026-08-13 13:21:03'),(4,2,NULL,NULL,'hjhuuhu',NULL,1,'90',98.00,18.00,'opex',NULL,'pending','2026-08-13 13:21:03','2026-08-13 13:21:03'),(5,3,NULL,NULL,'item 1',NULL,11,'11',12.00,18.00,'opex',NULL,'pending','2026-08-18 05:10:56','2026-08-18 05:10:56'),(6,3,NULL,NULL,'item2',NULL,11,'11',11.93,18.00,'opex',NULL,'pending','2026-08-18 05:10:56','2026-08-18 05:10:56'),(7,4,NULL,NULL,'Dell',NULL,1,'Units',10.00,18.00,'opex',NULL,'pending','2026-09-29 05:42:20','2026-09-29 05:42:20'),(8,5,NULL,NULL,'Sandwiches',NULL,10,'Units',120.00,18.00,'opex',NULL,'pending','2026-10-23 07:07:41','2026-10-23 07:07:41'),(9,5,NULL,NULL,'Fries',NULL,10,'Units',100.00,18.00,'opex',NULL,'pending','2026-10-23 07:07:41','2026-10-23 07:07:41'),(10,6,NULL,NULL,'FLyer',NULL,5,'Units',500.00,18.00,'opex',NULL,'pending','2026-11-02 05:50:50','2026-11-02 05:50:50'),(11,7,NULL,NULL,'Venue - Banquet',NULL,2,'Units',1.00,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(12,7,NULL,NULL,'Venue- Accommodation',NULL,10,'Units',1.00,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(13,7,NULL,NULL,'Flights - Business',NULL,5,'Units',0.90,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(14,7,NULL,NULL,'Flights - Economy',NULL,5,'Units',1.00,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(15,7,NULL,NULL,'Airport Transport',NULL,20,'Units',0.84,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(16,7,NULL,NULL,'AV',NULL,1,'Units',1.00,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(17,7,NULL,NULL,'FLower',NULL,1,'Units',1.00,18.00,'opex',NULL,'pending','2026-11-02 06:07:41','2026-11-02 06:07:41'),(18,8,NULL,NULL,'WEBSITE',NULL,1,'Units',120000.00,17.97,'opex',NULL,'pending','2026-11-04 10:06:07','2026-11-04 10:06:07'),(19,8,NULL,NULL,'APP',NULL,1,'Units',200000.00,18.00,'opex',NULL,'pending','2026-11-04 10:06:07','2026-11-04 10:06:07'),(20,9,NULL,NULL,'SMART EDUCATION PPT',NULL,1,'Units',120.00,18.00,'opex',NULL,'pending','2026-11-04 10:56:18','2026-11-04 10:56:18'),(21,9,NULL,NULL,'BOOJK',NULL,1,'Units',120.00,18.00,'opex',NULL,'pending','2026-11-04 10:56:18','2026-11-04 10:56:18'),(22,10,NULL,NULL,'Laptop',NULL,12,'Units',1234.00,18.00,'opex',NULL,'pending','2026-11-04 18:06:53','2026-11-04 18:06:53'),(23,10,NULL,NULL,'Charger',NULL,1,'Units',123.00,18.00,'opex',NULL,'pending','2026-11-04 18:06:53','2026-11-04 18:06:53'),(27,14,NULL,NULL,'Convention Hall 1 Booking (2 Days)',NULL,1,'Days',650000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(28,14,NULL,NULL,'Dual LED Video Wall (24x12 ft) & Line Array Sound',NULL,1,'Package',380000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(29,14,NULL,NULL,'Simultaneous Interpretation & Stage Lighting',NULL,1,'Setup',120000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(30,15,NULL,NULL,'Executive Delegate Buffet Lunch & High Tea (500 pax)',NULL,500,'Plates',1250.00,18.00,'opex',NULL,'pending','2026-11-17 14:55:34','2026-11-17 14:55:34'),(31,15,NULL,NULL,'Eco-friendly Delegate Conference Bags & Lanyards',NULL,500,'Kits',450.00,18.00,'opex',NULL,'pending','2026-11-17 14:55:34','2026-11-17 14:55:34'),(32,16,NULL,NULL,'Ball Room Day Rental & Setup Fee',NULL,1,'Day',850000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(33,16,NULL,NULL,'Curved Seamless LED Screen & Live Multi-Cam Webcast',NULL,1,'Package',420000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(34,17,NULL,NULL,'Luxury Fleet Chauffeur Transfers for Keynote Speakers',NULL,12,'Vehicles',18000.00,18.00,'opex',NULL,'pending','2026-11-17 14:55:34','2026-11-17 14:55:34'),(35,17,NULL,NULL,'Speaker Mementos & Citations in Brass',NULL,25,'Pieces',4500.00,18.00,'opex',NULL,'pending','2026-11-17 14:55:34','2026-11-17 14:55:34'),(36,18,NULL,NULL,'Auditorium Booking & Technical Operator',NULL,1,'Session',250000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(37,18,NULL,NULL,'Critical Minerals Knowledge Paper Printing (1000 copies)',NULL,1000,'Copies',85.00,12.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(38,19,NULL,NULL,'Exhibition Hall Octanorm Stalls (40 Standard 3x3m booths)',NULL,40,'Booths',22000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(39,19,NULL,NULL,'Exhibition Fascia Branding, Gate Arch & Signages',NULL,1,'Job',140000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(40,20,NULL,NULL,'High-Density Wi-Fi Infrastructure & 1Gbps Dedicated Leased Line',NULL,1,'Event',180000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34'),(41,20,NULL,NULL,'Interactive Touch Kiosks & Registration Counters',NULL,6,'Units',15000.00,18.00,'opex',NULL,'approved','2026-11-17 14:55:34','2026-11-17 14:55:34');
/*!40000 ALTER TABLE `purchase_requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_requisition_versions`
--

DROP TABLE IF EXISTS `purchase_requisition_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_requisition_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_requisition_id` bigint(20) unsigned NOT NULL,
  `version_number` int(10) unsigned NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `action_title` varchar(255) NOT NULL,
  `change_summary` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`snapshot`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_requisition_versions_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `purchase_requisition_versions_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_requisition_versions_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requisition_versions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisition_versions`
--

LOCK TABLES `purchase_requisition_versions` WRITE;
/*!40000 ALTER TABLE `purchase_requisition_versions` DISABLE KEYS */;
INSERT INTO `purchase_requisition_versions` VALUES (1,1,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":231.28}','{\"pr_number\":\"PR\\/FY26-27\\/20260813-2133\",\"title\":\"ghj\",\"department\":\"Projects\",\"required_by\":\"2026-08-12\",\"sole_source\":true,\"description\":null,\"status\":\"pending_l2\",\"current_level\":2,\"estimated_cost\":231.28,\"items\":[{\"id\":1,\"item_name\":\"ghj\",\"description\":null,\"quantity\":1,\"unit\":\"8\",\"unit_cost\":98,\"gst_percent\":18,\"line_total\":115.64},{\"id\":2,\"item_name\":\"gh\",\"description\":null,\"quantity\":1,\"unit\":\"9\",\"unit_cost\":98,\"gst_percent\":18,\"line_total\":115.64}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(2,2,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":231.28}','{\"pr_number\":\"PR\\/FY26-27\\/20260813-3882\",\"title\":\"gbhnj\",\"department\":\"HR\",\"required_by\":\"2026-08-15\",\"sole_source\":false,\"description\":null,\"status\":\"pending_l2\",\"current_level\":2,\"estimated_cost\":231.28,\"items\":[{\"id\":3,\"item_name\":\"gbhnj\",\"description\":null,\"quantity\":1,\"unit\":\"90\",\"unit_cost\":98,\"gst_percent\":18,\"line_total\":115.64},{\"id\":4,\"item_name\":\"hjhuuhu\",\"description\":null,\"quantity\":1,\"unit\":\"90\",\"unit_cost\":98,\"gst_percent\":18,\"line_total\":115.64}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(3,3,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":310.61}','{\"pr_number\":\"PR\\/FY26-27\\/20260818-3121\",\"title\":\"item 1\",\"department\":\"Finance\",\"required_by\":\"2026-08-26\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":310.61,\"items\":[{\"id\":5,\"item_name\":\"item 1\",\"description\":null,\"quantity\":11,\"unit\":\"11\",\"unit_cost\":12,\"gst_percent\":18,\"line_total\":155.76},{\"id\":6,\"item_name\":\"item2\",\"description\":null,\"quantity\":11,\"unit\":\"11\",\"unit_cost\":11.93,\"gst_percent\":18,\"line_total\":154.85}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(4,4,1,1,'created','Initial PR Submission (v1)','Requisition filed with 1 items',NULL,'{\"total\":11.8}','{\"pr_number\":\"PR\\/FY26-27\\/20260929-2821\",\"title\":\"Dell\",\"department\":\"IT\",\"required_by\":\"2026-09-29\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":11.8,\"items\":[{\"id\":7,\"item_name\":\"Dell\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":10,\"gst_percent\":18,\"line_total\":11.8}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(5,5,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":2596}','{\"pr_number\":\"PR\\/FY26-27\\/20261023-6641\",\"title\":\"Sandwiches\",\"department\":\"Operations\",\"required_by\":\"2026-10-23\",\"sole_source\":false,\"description\":null,\"status\":\"pending_l3\",\"current_level\":3,\"estimated_cost\":2596,\"items\":[{\"id\":8,\"item_name\":\"Sandwiches\",\"description\":null,\"quantity\":10,\"unit\":\"Units\",\"unit_cost\":120,\"gst_percent\":18,\"line_total\":1416},{\"id\":9,\"item_name\":\"Fries\",\"description\":null,\"quantity\":10,\"unit\":\"Units\",\"unit_cost\":100,\"gst_percent\":18,\"line_total\":1180}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(6,6,1,1,'created','Initial PR Submission (v1)','Requisition filed with 1 items',NULL,'{\"total\":2950}','{\"pr_number\":\"PR\\/FY26-27\\/20261102-1072\",\"title\":\"FLyer\",\"department\":\"Marketing\",\"required_by\":\"2026-11-04\",\"sole_source\":false,\"description\":null,\"status\":\"rejected\",\"current_level\":1,\"estimated_cost\":2950,\"items\":[{\"id\":10,\"item_name\":\"FLyer\",\"description\":null,\"quantity\":5,\"unit\":\"Units\",\"unit_cost\":500,\"gst_percent\":18,\"line_total\":2950}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(7,7,1,1,'created','Initial PR Submission (v1)','Requisition filed with 7 items',NULL,'{\"total\":47.55}','{\"pr_number\":\"PR\\/FY26-27\\/20261102-9615\",\"title\":\"Venue - Banquet\",\"department\":\"Projects\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":47.55,\"items\":[{\"id\":11,\"item_name\":\"Venue - Banquet\",\"description\":null,\"quantity\":2,\"unit\":\"Units\",\"unit_cost\":1,\"gst_percent\":18,\"line_total\":2.36},{\"id\":12,\"item_name\":\"Venue- Accommodation\",\"description\":null,\"quantity\":10,\"unit\":\"Units\",\"unit_cost\":1,\"gst_percent\":18,\"line_total\":11.8},{\"id\":13,\"item_name\":\"Flights - Business\",\"description\":null,\"quantity\":5,\"unit\":\"Units\",\"unit_cost\":0.9,\"gst_percent\":18,\"line_total\":5.31},{\"id\":14,\"item_name\":\"Flights - Economy\",\"description\":null,\"quantity\":5,\"unit\":\"Units\",\"unit_cost\":1,\"gst_percent\":18,\"line_total\":5.9},{\"id\":15,\"item_name\":\"Airport Transport\",\"description\":null,\"quantity\":20,\"unit\":\"Units\",\"unit_cost\":0.84,\"gst_percent\":18,\"line_total\":19.82},{\"id\":16,\"item_name\":\"AV\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":1,\"gst_percent\":18,\"line_total\":1.18},{\"id\":17,\"item_name\":\"FLower\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":1,\"gst_percent\":18,\"line_total\":1.18}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(8,8,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":377564}','{\"pr_number\":\"PR\\/FY26-27\\/20261104-4044\",\"title\":\"WEBSITE\",\"department\":\"IT\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":377564,\"items\":[{\"id\":18,\"item_name\":\"WEBSITE\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120000,\"gst_percent\":17.97,\"line_total\":141564},{\"id\":19,\"item_name\":\"APP\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":200000,\"gst_percent\":18,\"line_total\":236000}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(9,9,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":283.2}','{\"pr_number\":\"PR\\/FY26-27\\/20261104-7263\",\"title\":\"SMART EDUCATION PPT\",\"department\":\"Projects\",\"required_by\":\"2026-11-06\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":283.2,\"items\":[{\"id\":20,\"item_name\":\"SMART EDUCATION PPT\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120,\"gst_percent\":18,\"line_total\":141.6},{\"id\":21,\"item_name\":\"BOOJK\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120,\"gst_percent\":18,\"line_total\":141.6}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(10,10,1,1,'created','Initial PR Submission (v1)','Requisition filed with 2 items',NULL,'{\"total\":17618.579999999998}','{\"pr_number\":\"PR\\/FY26-27\\/20261104-5167\",\"title\":\"Laptop\",\"department\":\"IT\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":17618.579999999998,\"items\":[{\"id\":22,\"item_name\":\"Laptop\",\"description\":null,\"quantity\":12,\"unit\":\"Units\",\"unit_cost\":1234,\"gst_percent\":18,\"line_total\":17473.44},{\"id\":23,\"item_name\":\"Charger\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":123,\"gst_percent\":18,\"line_total\":145.14}]}','2026-11-09 10:00:30','2026-11-09 10:00:30'),(11,8,2,4,'approved','Operational Endorsement (DSG)','Technical scope verified for IT digital rollout','Scope verified; aligned with annual portal roadmap. Approved to advance to administrative compliance.',NULL,'{\"pr_number\":\"PR\\/FY26-27\\/20261104-4044\",\"title\":\"WEBSITE\",\"event_name\":\"Digital Portal Rollout\",\"department\":\"IT\",\"region\":\"Bengaluru\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"special_instructions\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":377564,\"items\":[{\"id\":18,\"item_name\":\"WEBSITE\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120000,\"gst_percent\":17.97,\"line_total\":141564},{\"id\":19,\"item_name\":\"APP\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":200000,\"gst_percent\":18,\"line_total\":236000}]}','2026-11-17 07:09:00','2026-11-17 07:09:00'),(12,8,3,5,'approved','Compliance Clearance (Head of States)','Vendor empanelment & single-region compliance validated','Vendor eligibility and statutory documentation checked. Cleared for finance appraisal.',NULL,'{\"pr_number\":\"PR\\/FY26-27\\/20261104-4044\",\"title\":\"WEBSITE\",\"event_name\":\"Digital Portal Rollout\",\"department\":\"IT\",\"region\":\"Bengaluru\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"special_instructions\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":377564,\"items\":[{\"id\":18,\"item_name\":\"WEBSITE\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120000,\"gst_percent\":17.97,\"line_total\":141564},{\"id\":19,\"item_name\":\"APP\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":200000,\"gst_percent\":18,\"line_total\":236000}]}','2026-11-17 07:09:00','2026-11-17 07:09:00'),(13,8,4,8,'approved','Financial Sanction (CFO)','Budget commitment reserved under FY26-27 IT Capex ceiling','Budget head 4102 (IT & Infrastructure) confirmed within departmental ceiling. Cleared for executive sanction.',NULL,'{\"pr_number\":\"PR\\/FY26-27\\/20261104-4044\",\"title\":\"WEBSITE\",\"event_name\":\"Digital Portal Rollout\",\"department\":\"IT\",\"region\":\"Bengaluru\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"special_instructions\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":377564,\"items\":[{\"id\":18,\"item_name\":\"WEBSITE\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120000,\"gst_percent\":17.97,\"line_total\":141564},{\"id\":19,\"item_name\":\"APP\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":200000,\"gst_percent\":18,\"line_total\":236000}]}','2026-11-17 07:09:00','2026-11-17 07:09:00'),(14,8,5,9,'approved','Executive Sanction Granted (SG)','Final institutional approval granted; PR sealed for RFQ issuance','Executive sanction accorded. Handover to procurement buyer for immediate RFQ dispatch.',NULL,'{\"pr_number\":\"PR\\/FY26-27\\/20261104-4044\",\"title\":\"WEBSITE\",\"event_name\":\"Digital Portal Rollout\",\"department\":\"IT\",\"region\":\"Bengaluru\",\"required_by\":\"2026-11-05\",\"sole_source\":false,\"description\":null,\"special_instructions\":null,\"status\":\"approved\",\"current_level\":4,\"estimated_cost\":377564,\"items\":[{\"id\":18,\"item_name\":\"WEBSITE\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":120000,\"gst_percent\":17.97,\"line_total\":141564},{\"id\":19,\"item_name\":\"APP\",\"description\":null,\"quantity\":1,\"unit\":\"Units\",\"unit_cost\":200000,\"gst_percent\":18,\"line_total\":236000}]}','2026-11-17 07:09:00','2026-11-17 07:09:00');
/*!40000 ALTER TABLE `purchase_requisition_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_requisitions`
--

DROP TABLE IF EXISTS `purchase_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_requisitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pr_number` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `project_budget_id` bigint(20) unsigned DEFAULT NULL,
  `department` varchar(255) NOT NULL,
  `region` varchar(255) DEFAULT NULL,
  `required_by` date DEFAULT NULL,
  `sole_source` tinyint(1) NOT NULL DEFAULT 0,
  `expense_type` varchar(20) DEFAULT 'opex',
  `description` text DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `current_level` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_requisitions_pr_number_unique` (`pr_number`),
  KEY `purchase_requisitions_created_by_foreign` (`created_by`),
  KEY `purchase_requisitions_project_budget_id_foreign` (`project_budget_id`),
  CONSTRAINT `purchase_requisitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_requisitions_project_budget_id_foreign` FOREIGN KEY (`project_budget_id`) REFERENCES `project_budgets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_requisitions`
--

LOCK TABLES `purchase_requisitions` WRITE;
/*!40000 ALTER TABLE `purchase_requisitions` DISABLE KEYS */;
INSERT INTO `purchase_requisitions` VALUES (1,'PR/FY26-27/20260813-2133','ghj','Annual Strategy Conclave',NULL,'Projects','Delhi HQ','2026-08-12',1,'opex',NULL,NULL,'pending_l2',2,1,'2026-08-13 11:58:00','2026-11-17 14:40:44'),(2,'PR/FY26-27/20260813-3882','gbhnj','Regional HR Summit',NULL,'HR','North','2026-08-15',0,'opex',NULL,NULL,'pending_l2',2,1,'2026-08-13 13:21:03','2026-11-17 14:40:44'),(3,'PR/FY26-27/20260818-3121','item 1','Q2 Financial Audit Review',NULL,'Finance','Delhi HQ','2026-08-26',0,'opex',NULL,NULL,'approved',4,1,'2026-08-18 05:10:56','2026-11-17 14:40:44'),(4,'PR/FY26-27/20260929-2821','Dell','IT Infrastructure Refresh',NULL,'IT','Mumbai','2026-09-29',0,'opex',NULL,NULL,'approved',4,1,'2026-09-29 05:42:20','2026-11-17 14:40:44'),(5,'PR/FY26-27/20261023-6641','Sandwiches','Operations Logistics Meet',NULL,'Operations','Delhi HQ','2026-10-23',0,'opex',NULL,NULL,'pending_l3',3,1,'2026-10-23 07:07:41','2026-11-17 14:40:44'),(6,'PR/FY26-27/20261102-1072','FLyer','East Regional Industry Expo',NULL,'Marketing','Kolkata','2026-11-04',0,'opex',NULL,NULL,'rejected',1,1,'2026-11-02 05:50:50','2026-11-17 14:40:45'),(7,'PR/FY26-27/20261102-9615','Venue - Banquet','Leadership Banquet 2026',NULL,'Projects','Delhi HQ','2026-11-05',0,'opex',NULL,NULL,'approved',4,1,'2026-11-02 06:07:41','2026-11-17 14:40:45'),(8,'PR/FY26-27/20261104-4044','WEBSITE','Digital Portal Rollout',NULL,'IT','Bengaluru','2026-11-05',0,'opex',NULL,NULL,'approved',4,1,'2026-11-04 10:06:07','2026-11-17 14:40:45'),(9,'PR/FY26-27/20261104-7263','SMART EDUCATION PPT','Education & Skill Workshop',NULL,'Projects','Delhi HQ','2026-11-06',0,'opex',NULL,NULL,'approved',4,1,'2026-11-04 10:56:18','2026-11-17 14:40:45'),(10,'PR/FY26-27/20261104-5167','Laptop','Executive Hardware Deployment',NULL,'IT','North','2026-11-05',0,'opex',NULL,NULL,'approved',4,1,'2026-11-04 18:06:53','2026-11-10 09:49:47'),(14,'PR/FY26-27/EV-202608-01','Vigyan Bhawan Plenary Hall & Audio-Visual Setup','National Conference on Green Mobility & EV Infrastructure',14,'Projects','Delhi HQ',NULL,0,'opex',NULL,NULL,'approved',4,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(15,'PR/FY26-27/EV-202608-02','Delegate Catering, Kits & Conference Collaterals','National Conference on Green Mobility & EV Infrastructure',14,'Projects','Delhi HQ',NULL,0,'opex',NULL,NULL,'pending_l3',2,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(16,'PR/FY26-27/ASC-202611-01','Taj Palace Ball Room Venue & Technical Staging','Annual Strategy Conclave 2026',NULL,'Projects','Delhi HQ',NULL,0,'opex',NULL,NULL,'approved',4,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(17,'PR/FY26-27/ASC-202611-02','VIP Speaker Transport & Premium Hospitality','Annual Strategy Conclave 2026',NULL,'Projects','Delhi HQ',NULL,0,'opex',NULL,NULL,'pending_l2',2,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(18,'PR/FY26-27/MIN-202606-01','India Habitat Centre Stein Auditorium & Logistics','Conference on Building a Resilient Critical Minerals Supply Chain in India',10,'Projects','Delhi HQ',NULL,0,'opex',NULL,NULL,'approved',4,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(19,'PR/FY26-27/EXP-202610-01','Biswa Bangla Convention Centre Exhibition Hangar & Stalls','East India Regional Trade & Investment Expo',19,'Marketing','Kolkata',NULL,0,'opex',NULL,NULL,'approved',4,1,'2026-11-17 14:55:34','2026-11-17 14:55:34'),(20,'PR/FY26-27/IT-202607-01','Cyber Security Conclave Cloud Platform & Demo Lab','Digital Transformation & Enterprise Cyber Security Conclave',21,'IT','Delhi HQ',NULL,0,'opex',NULL,NULL,'approved',4,1,'2026-11-17 14:55:34','2026-11-17 14:55:34');
/*!40000 ALTER TABLE `purchase_requisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reimbursement_items`
--

DROP TABLE IF EXISTS `reimbursement_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reimbursement_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reimbursement_id` bigint(20) unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bill_path` varchar(255) DEFAULT NULL,
  `bill_disk` varchar(255) NOT NULL DEFAULT 'public',
  `bill_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reimbursement_items_reimbursement_id_foreign` (`reimbursement_id`),
  CONSTRAINT `reimbursement_items_reimbursement_id_foreign` FOREIGN KEY (`reimbursement_id`) REFERENCES `reimbursements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursement_items`
--

LOCK TABLES `reimbursement_items` WRITE;
/*!40000 ALTER TABLE `reimbursement_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `reimbursement_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reimbursements`
--

DROP TABLE IF EXISTS `reimbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reimbursements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `claim_number` varchar(255) NOT NULL,
  `trf_id` bigint(20) unsigned DEFAULT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `claim_amount` decimal(12,2) NOT NULL,
  `advance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `routing_flag` varchar(255) NOT NULL DEFAULT 'standard',
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending_cfo',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reimbursements_claim_number_unique` (`claim_number`),
  KEY `reimbursements_trf_id_foreign` (`trf_id`),
  KEY `reimbursements_employee_id_foreign` (`employee_id`),
  CONSTRAINT `reimbursements_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reimbursements_trf_id_foreign` FOREIGN KEY (`trf_id`) REFERENCES `trfs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reimbursements`
--

LOCK TABLES `reimbursements` WRITE;
/*!40000 ALTER TABLE `reimbursements` DISABLE KEYS */;
INSERT INTO `reimbursements` VALUES (1,'REIM-5171',NULL,1,8665.00,98.00,8567.00,'exception','ghghh','approved','2026-08-13 13:19:04','2026-08-13 13:19:04','2026-08-18 07:15:30'),(2,'REIM-8086',NULL,3,100.00,0.00,100.00,'exception','abc','pending_sg','2026-10-16 06:54:22','2026-10-16 06:54:22','2026-10-16 06:54:22'),(3,'REIM-3867',NULL,3,123.00,0.00,123.00,'exception','urgent','pending_sg','2026-10-16 06:58:09','2026-10-16 06:58:09','2026-10-16 06:58:09'),(4,'REIM-1368',NULL,10,1200.00,12.00,1188.00,'exception','kerkrekj rknren reekjnrek','approved','2026-11-04 19:00:11','2026-11-04 19:00:11','2026-11-04 19:00:49'),(5,'REIM-DEMO-810',4,10,12000.00,12000.00,0.00,'standard',NULL,'approved','2026-10-22 10:36:36','2026-11-11 10:36:36','2026-11-11 10:36:36'),(6,'REIM-DEMO-811',5,2,35000.00,35000.00,0.00,'standard',NULL,'approved','2026-10-29 10:36:36','2026-11-11 10:36:36','2026-11-11 10:36:36'),(7,'REIM-DEMO-812',6,1,10000.00,10000.00,0.00,'standard',NULL,'approved','2026-11-01 10:36:36','2026-11-11 10:36:36','2026-11-11 10:36:36'),(8,'REIM-DEMO-813',7,10,8000.00,8000.00,0.00,'standard',NULL,'approved','2026-11-06 10:36:36','2026-11-11 10:36:36','2026-11-11 10:36:36');
/*!40000 ALTER TABLE `reimbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfq_vendors`
--

DROP TABLE IF EXISTS `rfq_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfq_vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `custom_vendor_tag` varchar(255) DEFAULT NULL,
  `response_status` varchar(255) NOT NULL DEFAULT 'awaiting',
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_vendors_rfq_id_vendor_id_unique` (`rfq_id`,`vendor_id`),
  KEY `rfq_vendors_vendor_id_foreign` (`vendor_id`),
  CONSTRAINT `rfq_vendors_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfqs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_vendors_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfq_vendors`
--

LOCK TABLES `rfq_vendors` WRITE;
/*!40000 ALTER TABLE `rfq_vendors` DISABLE KEYS */;
INSERT INTO `rfq_vendors` VALUES (1,1,1,NULL,'awaiting',NULL,'2026-08-18 06:23:59','2026-08-18 06:23:59'),(2,2,1,NULL,'awaiting',NULL,'2026-09-29 06:07:04','2026-09-29 06:07:04'),(3,3,1,NULL,'awaiting',NULL,'2026-09-29 06:12:34','2026-09-29 06:12:34'),(4,4,5,NULL,'awaiting',NULL,'2026-11-04 10:19:17','2026-11-04 10:19:17'),(5,5,7,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(6,5,1,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(7,5,3,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(8,5,5,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(9,5,8,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(10,5,6,NULL,'awaiting',NULL,'2026-11-04 11:57:26','2026-11-04 11:57:26'),(11,6,7,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(12,6,1,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(13,6,3,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(14,6,5,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(15,6,8,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(16,6,6,NULL,'awaiting',NULL,'2026-11-04 18:25:42','2026-11-04 18:25:42'),(17,7,1,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(18,7,3,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(19,7,5,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(20,7,6,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(21,7,7,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(22,7,8,NULL,'received',NULL,'2026-11-06 06:17:29','2026-11-06 08:47:13'),(23,8,1,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(24,8,3,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(25,8,5,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(26,8,6,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(27,8,7,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(28,8,8,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(29,9,1,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(30,9,3,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(31,9,5,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(32,9,6,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(33,9,7,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(34,9,8,NULL,'awaiting',NULL,'2026-11-06 06:17:29','2026-11-06 06:17:29'),(38,13,1,NULL,'awaiting',NULL,'2026-11-18 07:30:39','2026-11-18 07:30:39'),(39,14,1,NULL,'awaiting',NULL,'2026-11-18 07:32:22','2026-11-18 07:32:22'),(40,15,1,NULL,'awaiting',NULL,'2026-11-18 07:32:56','2026-11-18 07:32:56'),(41,16,1,NULL,'received',NULL,'2026-11-18 07:34:00','2026-11-19 07:57:00'),(42,17,1,NULL,'awaiting',NULL,'2026-11-18 09:02:35','2026-11-18 09:02:35'),(43,17,3,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(44,17,5,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(45,17,6,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(46,17,7,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(47,17,8,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(48,17,12,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(49,17,13,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20'),(50,17,14,NULL,'awaiting',NULL,'2026-11-18 09:10:20','2026-11-18 09:10:20');
/*!40000 ALTER TABLE `rfq_vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfqs`
--

DROP TABLE IF EXISTS `rfqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_number` varchar(255) NOT NULL,
  `sector` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `purchase_requisition_id` bigint(20) unsigned NOT NULL,
  `requisition_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `poc_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `email_dispatched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfqs_rfq_number_unique` (`rfq_number`),
  UNIQUE KEY `rfqs_project_name_unique` (`project_name`),
  KEY `rfqs_purchase_requisition_id_foreign` (`purchase_requisition_id`),
  KEY `rfqs_poc_user_id_foreign` (`poc_user_id`),
  CONSTRAINT `rfqs_poc_user_id_foreign` FOREIGN KEY (`poc_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `rfqs_purchase_requisition_id_foreign` FOREIGN KEY (`purchase_requisition_id`) REFERENCES `purchase_requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfqs`
--

LOCK TABLES `rfqs` WRITE;
/*!40000 ALTER TABLE `rfqs` DISABLE KEYS */;
INSERT INTO `rfqs` VALUES (1,'RFQ-8655','Finance','item 1',3,'2026-08-18',NULL,2,'approved',NULL,'2026-08-18 06:23:59','2026-11-10 08:19:31'),(2,'RFQ-6777','Finance','item 1 (2)',3,'2026-09-29',NULL,2,'quotes_received',NULL,'2026-09-29 06:07:04','2026-11-10 08:19:31'),(3,'RFQ-9910','Finance','item 1 (3)',3,'2026-09-29',NULL,2,'quotes_received',NULL,'2026-09-29 06:12:34','2026-11-10 08:19:31'),(4,'RFQ-4940','IT','WEBSITE',8,'2026-11-04',NULL,2,'approved',NULL,'2026-11-04 10:19:17','2026-11-10 08:19:31'),(5,'RFQ-8534','Projects','SMART EDUCATION PPT',9,'2026-11-04',NULL,2,'approved',NULL,'2026-11-04 11:57:26','2026-11-10 08:19:31'),(6,'RFQ-1873','Finance','item 1 (4)',3,'2026-11-04',NULL,2,'approved',NULL,'2026-11-04 18:25:42','2026-11-10 08:19:31'),(7,'RFQ-6693','IT','Dell',4,'2026-09-29',NULL,2,'quotes_received',NULL,'2026-11-06 06:17:29','2026-11-10 08:19:31'),(8,'RFQ-3594','Projects','Venue - Banquet',7,'2026-11-05',NULL,2,'dispatched',NULL,'2026-11-06 06:17:29','2026-11-10 08:19:31'),(9,'RFQ-4210','IT','Laptop',10,'2026-11-05',NULL,2,'dispatched',NULL,'2026-11-06 06:17:29','2026-11-10 08:19:31'),(13,'RFQ-8069','Projects','Vigyan Bhawan Plenary Hall & Audio-Visual Setup',14,'2026-11-18','2026-11-25',2,'dispatched','2026-11-18 07:30:16','2026-11-18 07:30:16','2026-11-18 07:30:16'),(14,'RFQ-5560','Projects','Taj Palace Ball Room Venue & Technical Staging',16,'2026-11-18','2026-11-25',2,'dispatched','2026-11-18 07:32:02','2026-11-18 07:32:02','2026-11-18 07:32:02'),(15,'RFQ-6847','Projects','India Habitat Centre Stein Auditorium & Logistics',18,'2026-11-18','2026-11-25',2,'dispatched','2026-11-18 07:32:35','2026-11-18 07:32:35','2026-11-18 07:32:35'),(16,'RFQ-9738','Marketing','Biswa Bangla Convention Centre Exhibition Hangar & Stalls',19,'2026-11-18','2026-11-25',2,'quotes_received','2026-11-18 07:33:38','2026-11-18 07:33:38','2026-11-19 07:57:00'),(17,'RFQ-4243','IT','Digital Transformation & Enterprise Cyber Security Conclave',20,'2026-11-18','2026-11-25',2,'dispatched','2026-11-18 09:02:13','2026-11-18 09:02:13','2026-11-18 09:02:13');
/*!40000 ALTER TABLE `rfqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('ah7bHr5bJ2GvceeUhlILyRs9q2B6GUfyaPP3fUix',3,'103.48.109.68','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/157.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU25sWXBOWjhWSklueE5WWWJCb3p5RzJoYjJlaXY5SjJ6N3ZzTUc0SCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9hdWRpdCI7czo1OiJyb3V0ZSI7czo1OiJhdWRpdCI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==',1793861164),('AKLxX0twb2X8DvT4ebg4Oc88pxvMrQxOsIMlX77z',1,'103.48.109.68','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOGdvNVN1TDNXWmxUOFZ4MVZydXc4d3FSREVvMlJvMXV6NjB4eTJ6MCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9pbmRleCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1793877634),('fz3j9i3Dz3r7G0mI8wymM8OC2Z3IBMatisxmatZF',NULL,'183.83.220.174','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidmNSeU1iZGlFRkdlZDc1SENjUUx0SWhTczBEU2VmaWRLMDhuTDBZbiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cHM6Ly9lcnAuZW5zZXVyLmluL2luZGV4Ijt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9pbmRleCI7czo1OiJyb3V0ZSI7czo5OiJkYXNoYm9hcmQiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1793859967),('HFWVTQrTYo0VQ23GOxNOAmeiqD9Q9vWglaLybvb6',NULL,'103.48.109.68','WhatsApp/2.2641.101 W','YTo0OntzOjY6Il90b2tlbiI7czo0MDoia2ZSWmVCTlprZ1pUdUZ4R21sVEdHZER3bG1SZHdTNVBBNVdoeTIzSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9pbmRleCI7fX0=',1793876223),('iID0FR8X8CSkp6567c3mYHEvv79eiISqBzFLrRoi',1,'183.83.220.174','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoibmJob1dteVFIR1I2aVZyMUp5NDY0blNBOXhuT2x1WHJYSTBBR2p0cyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9yZXF1aXNpdGlvbiI7czo1OiJyb3V0ZSI7czoxMToicmVxdWlzaXRpb24iO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=',1793862852),('kwjFmREu5vgXlTeyEkzj8MpuL70hRBzmHXNGfbdV',2,'103.48.109.68','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNGhla3lENnJZSUw2WmYzZ0pRaUFWdEl2V1RlSXRYNHFuUzdDTlU2cCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9wby8yIjtzOjU6InJvdXRlIjtzOjc6InBvLnNob3ciO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1793875447),('YyUFkuvjq1lualtnqJPUNRlFcJZqd4mC3RGaG8YL',NULL,'2405:201:a418:a87f:6f6b:6b51:c4cd:fee7','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Mobile Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOXJDTk45a1RpYW1YUndOMGJMOVVmYzBuSWtGbWNsMEFlaGxqVUM5biI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6Mjc6Imh0dHBzOi8vZXJwLmVuc2V1ci5pbi9pbmRleCI7fX0=',1793876317);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spocs`
--

DROP TABLE IF EXISTS `spocs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spocs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `member_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `can_manage_events` tinyint(1) NOT NULL DEFAULT 1,
  `can_manage_councils` tinyint(1) NOT NULL DEFAULT 1,
  `can_view_billing` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spocs_email_unique` (`email`),
  KEY `spocs_member_id_foreign` (`member_id`),
  CONSTRAINT `spocs_member_id_foreign` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spocs`
--

LOCK TABLES `spocs` WRITE;
/*!40000 ALTER TABLE `spocs` DISABLE KEYS */;
INSERT INTO `spocs` VALUES (1,11,'Noelle Case','Sint eiusmod odit o','jakow@mailinator.com','+1 (827) 212-5215','$2y$12$JAvVJh7pkE/QO9o86FX1B.h1UAACXv7KNjKeAjRJGzpji.mXDD.yS',NULL,1,1,1,'2026-11-02 12:24:01','2026-11-02 12:24:01');
/*!40000 ALTER TABLE `spocs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trade_delegations`
--

DROP TABLE IF EXISTS `trade_delegations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trade_delegations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `focus` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trade_delegations`
--

LOCK TABLES `trade_delegations` WRITE;
/*!40000 ALTER TABLE `trade_delegations` DISABLE KEYS */;
/*!40000 ALTER TABLE `trade_delegations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trfs`
--

DROP TABLE IF EXISTS `trfs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trfs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trf_number` varchar(255) NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `booking_type` varchar(255) NOT NULL DEFAULT 'self',
  `purpose` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `travel_mode` varchar(255) DEFAULT NULL,
  `destinations` text DEFAULT NULL,
  `is_international` tinyint(1) NOT NULL DEFAULT 0,
  `travel_start` date DEFAULT NULL,
  `travel_end` date DEFAULT NULL,
  `insurance` tinyint(1) NOT NULL DEFAULT 0,
  `visa_required` tinyint(1) NOT NULL DEFAULT 0,
  `visa_details` varchar(255) DEFAULT NULL,
  `accommodation` tinyint(1) NOT NULL DEFAULT 0,
  `accommodation_details` varchar(255) DEFAULT NULL,
  `requested_advance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trfs_trf_number_unique` (`trf_number`),
  KEY `trfs_employee_id_foreign` (`employee_id`),
  CONSTRAINT `trfs_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trfs`
--

LOCK TABLES `trfs` WRITE;
/*!40000 ALTER TABLE `trfs` DISABLE KEYS */;
INSERT INTO `trfs` VALUES (1,'TRF-3701',1,'self','cfvbn','bn',NULL,NULL,0,'2026-08-21','2026-08-26',0,0,NULL,1,NULL,66778.00,'approved','2026-08-13 13:21:45','2026-08-18 06:50:23'),(2,'TRF-7211',8,'on_behalf','dcfgvhbjn','DElhi',NULL,NULL,1,'2026-08-19','2026-08-19',1,0,NULL,1,NULL,768777.83,'approved','2026-08-18 06:49:06','2026-08-18 06:51:16'),(3,'TRF-6986',5,'on_behalf','MEETING WITH ASSOCHAM','new delhi - Patna',NULL,NULL,1,'2026-11-06','2026-11-12',1,0,NULL,1,NULL,12233.00,'approved','2026-11-04 19:14:44','2026-11-04 19:15:47'),(4,'TRF-DEMO-810',10,'self','National Manufacturing Conclave','New Delhi (DEL) → Mumbai (BOM)','Flight','New Delhi (DEL) → Mumbai (BOM)',0,'2026-10-17','2026-10-20',0,0,NULL,1,NULL,12000.00,'approved','2026-11-11 10:36:36','2026-11-11 10:36:36'),(5,'TRF-DEMO-811',2,'self','ASEAN Chamber Delegation','New Delhi (DEL) → Singapore (SIN)','Flight','New Delhi (DEL) → Singapore (SIN)',1,'2026-10-23','2026-10-27',0,0,NULL,1,NULL,35000.00,'approved','2026-11-11 10:36:36','2026-11-11 10:36:36'),(6,'TRF-DEMO-812',1,'self','Southern Region Industry Roundtable','New Delhi (DEL) → Bengaluru (BLR)','Flight','New Delhi (DEL) → Bengaluru (BLR)',0,'2026-10-28','2026-10-30',0,0,NULL,1,NULL,10000.00,'approved','2026-11-11 10:36:36','2026-11-11 10:36:36'),(7,'TRF-DEMO-813',10,'self','Western Corridor Supply Chain Meet','New Delhi (DEL) → Ahmedabad (AMD)','Flight','New Delhi (DEL) → Ahmedabad (AMD)',0,'2026-11-02','2026-11-04',0,0,NULL,1,NULL,8000.00,'approved','2026-11-11 10:36:36','2026-11-11 10:36:36');
/*!40000 ALTER TABLE `trfs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `allowed_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_modules`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Priya Nair','priya.nair@assocham.test','pr_sr_exec','Projects',NULL,NULL,1,NULL,'2026-09-19 08:22:40','$2y$12$7hdDZB1nWvA1tnCBkQetVez4uFu.y.0bUfPqLiNpXK8q71AuEEL6u','wGn5j1aBxt3fonhMv3a6iDvWbgV6qj6gfG0omVUd7GXNfnYayKpfCcw1sb4D','2026-08-07 13:16:29','2026-09-19 08:22:40',NULL),(2,'Rohit Verma','rohit.verma@assocham.test','buyer','IT',NULL,NULL,1,NULL,'2026-09-19 08:22:40','$2y$12$aJi1MEpeIXI/SabrfgRYY.v82BOJ5aC714fXRESWfvgpDRDDWM28G','Y05xqJyW8cuTWftKIlGcFXkcfJ4FbMNx6R2jbjhfN8WsE8Q78gLoEmUW67HT','2026-08-07 13:16:30','2026-09-19 08:22:41',NULL),(3,'Rahul Mehta','rahul.mehta@assocham.test','admin_head',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:41','$2y$12$X5TmPfCAx2H4/WPNxFh2VebGcMkxm7EnKxPrMqsdaSd1s9hSZXhyG','kypiwjr853S95QXOn5LX9HJ7KmZdbGQoE1csybzolGSkdf3S8fcxFMviFZM5','2026-08-07 13:16:30','2026-09-19 08:22:41',NULL),(4,'Basu','basu@assocham.test','pr_approver_l1',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:41','$2y$12$LuK0sRNWSB3aA4Hnq40HK.D0w31QnVf.vtMJcR/mv8flbXaAXpZOy','bwo24hC65VerJ3Q9I1Ww8ueZfGrUppPfB3TXIMMojUlBesjmZyT89xfdRUPZ','2026-08-07 13:16:30','2026-09-19 08:22:41',NULL),(5,'Ambika','ambika@assocham.test','pr_approver_l2',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:41','$2y$12$hKjgZcrKkE/Rur5TLnn6QOzdoL.1vnPQRkOakjUlVLsXYbvoa3XVm','xXYfkmZU6LwMRnYWGbp5GngxhfZfjHFhyc3uOHCO1FbyoomYCYOGQ00D3YOM','2026-08-07 13:16:30','2026-09-19 08:22:41',NULL),(6,'Anil Kulkarni','asg1@assocham.test','asg1',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:41','$2y$12$X/iB2J7Y6SkRBBMYm7OFnObX74sM/R9vpHXnAtM4S.IlBTGELy4.S','4oEYAazbqiZ7CcWMRxoF8tEsV8aSinweFbYgxIa72ZqZmtR8WIhmXjLBkpO4','2026-08-07 13:16:30','2026-09-19 08:22:41',NULL),(7,'Deepak Nair','asg2@assocham.test','asg2',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:41','$2y$12$k24Mt66f.mNI55ExkcSzAOESzccQu6yyqzY6gG0R568S.TaTeI7xa','IH4yiLRyO8jBBJFZY9TMUTheEs1Whq03RCT2zxShrYcy4kcM58WEQGZ3N6AR','2026-08-07 13:16:31','2026-09-19 08:22:42',NULL),(8,'Sanjay Menon','sanjay.menon@assocham.test','cfo',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:42','$2y$12$TRxhpNKAutBLmm.xr61cxOzg/q6/Be8rGUU5ewLVp9OgHkmS1QOKu','IJgmF72Idgas7scXSEw1TXTCMDCPVqOZkoD4BJFCN0SF3Ecj9fclyOkCYI3E','2026-08-07 13:16:31','2026-09-19 08:22:42',NULL),(9,'Meenakshi Rao','meenakshi.rao@assocham.test','sg',NULL,NULL,NULL,1,NULL,'2026-09-19 08:22:42','$2y$12$CwCm1WZAFYQXXse5G5Vrh.oHWIZAGc08/yUj.ksPrA3/Iu7eFwfcW','q9Xd7CQx0I7PFHlWwnhMVcru9PVRkEU4JIpyKLCATfJ4I5OyMNLJitIfH8Nb','2026-08-07 13:16:31','2026-09-19 08:22:42',NULL),(10,'Vikram Shah','hod@assocham.test','hod','Operations',NULL,NULL,1,NULL,'2026-09-19 08:22:42','$2y$12$XfgLJxPsV7odT6/x.Muy4OeGg9nOCqsEK5HgaD16rYpPZiSoj61mm','DU1YbehDI4uLZT2ikyH4pW5TaKjuD0FSXgLoy8s4tvZnjy8llSB4kZyeyv1x','2026-08-07 13:16:31','2026-09-19 08:22:42',NULL),(11,'Anmol Kumar','anmol.kumar@assocham.test','membership_ops','Membership Ops',NULL,NULL,1,NULL,'2026-09-19 08:22:42','$2y$12$OAoSxksJjjsjLr228RQ4a.8OGFUCgqxjSDsMuOYi0svA/Fi4Jmh66','PvMw3YlnwptVy9FNsezhTuWzGh9SqpitOKSh6IzBZVzidmpg6c4UrXaHOsiT','2026-08-25 13:00:50','2026-09-19 08:22:43',NULL),(12,'Priyanka Iyer','priyanka.iyer@assocham.test','b2b_ops','B2B Networking',NULL,NULL,1,NULL,'2026-09-19 08:22:43','$2y$12$YSR7J6OoSG1BgQ1cK/8SWOKfH36AXMJCoJeaLCAE/wSnWYABRJ3x2','jS8c1bmrIWjXRTwp3zylevr3pwcXvb5LSzWvQPlF9Pd4XRvwfjDNxVDOf7cp','2026-09-19 08:22:43','2026-09-19 08:22:43',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_evaluations`
--

DROP TABLE IF EXISTS `vendor_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trf_id` bigint(20) unsigned NOT NULL,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `cost_score` tinyint(3) unsigned NOT NULL,
  `time_score` tinyint(3) unsigned NOT NULL,
  `quality_score` tinyint(3) unsigned NOT NULL,
  `responsiveness_score` tinyint(3) unsigned NOT NULL,
  `accuracy_score` tinyint(3) unsigned NOT NULL,
  `comments` text DEFAULT NULL,
  `rated_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_evaluations_trf_id_foreign` (`trf_id`),
  KEY `vendor_evaluations_vendor_id_foreign` (`vendor_id`),
  KEY `vendor_evaluations_rated_by_foreign` (`rated_by`),
  CONSTRAINT `vendor_evaluations_rated_by_foreign` FOREIGN KEY (`rated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_evaluations_trf_id_foreign` FOREIGN KEY (`trf_id`) REFERENCES `trfs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_evaluations_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_evaluations`
--

LOCK TABLES `vendor_evaluations` WRITE;
/*!40000 ALTER TABLE `vendor_evaluations` DISABLE KEYS */;
INSERT INTO `vendor_evaluations` VALUES (1,4,12,5,5,5,4,5,'Lowest corporate fare locked within 2 hours; boarding passes shared promptly.',3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(2,5,12,4,5,5,5,5,'Seamless Marina Bay corporate hotel check-in and early-morning airport transfer.',3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(3,6,13,4,4,4,5,4,'Good flight options and responsive GST invoice issuance.',3,'2026-11-11 10:36:36','2026-11-11 10:36:36'),(4,7,14,3,4,3,3,4,'Confirmation voucher arrived slightly late; hotel quality was acceptable.',3,'2026-11-11 10:36:36','2026-11-11 10:36:36');
/*!40000 ALTER TABLE `vendor_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `pan` varchar(255) DEFAULT NULL,
  `gstin` varchar(255) DEFAULT NULL,
  `incorporation_certificate_no` varchar(255) DEFAULT NULL,
  `partnership_deed_no` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `references` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_branch` varchar(255) DEFAULT NULL,
  `bank_account_holder` varchar(255) DEFAULT NULL,
  `bank_account_type` varchar(255) DEFAULT NULL,
  `bank_ifsc` varchar(255) DEFAULT NULL,
  `msme_status` tinyint(1) NOT NULL DEFAULT 0,
  `msme_number` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `previous_orders` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `compliance_status` varchar(255) NOT NULL DEFAULT 'active',
  `empanelment_email_sent_at` timestamp NULL DEFAULT NULL,
  `blacklist_reason` text DEFAULT NULL,
  `blacklisted_at` timestamp NULL DEFAULT NULL,
  `blacklisted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `declaration_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `declaration_signatory_name` varchar(255) DEFAULT NULL,
  `declaration_designation` varchar(255) DEFAULT NULL,
  `declaration_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendors_vendor_code_unique` (`vendor_code`),
  KEY `vendors_submitted_by_foreign` (`submitted_by`),
  KEY `vendors_blacklisted_by_foreign` (`blacklisted_by`),
  CONSTRAINT `vendors_blacklisted_by_foreign` FOREIGN KEY (`blacklisted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendors_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (1,'VEN-1857','Canteen','AACCF8651R','awesrdfghj324567',NULL,NULL,'non it',NULL,'Sakshi Jha','08882986402','sakkshijha05@gmail.com',NULL,'zsxdcfgh',NULL,NULL,NULL,NULL,'swedrtfgy345t',0,NULL,'67','e5r6tyuijkn','New Delhi',4.2,5,'approved','active',NULL,NULL,NULL,NULL,2,1,'Sakshi Jha','Authorized Signatory',NULL,'2026-08-18 05:50:53','2026-11-19 10:37:09'),(2,'VEN-8883','Adgips',NULL,'ASTT%$$#^T^%$#@@#',NULL,NULL,'Education',NULL,'Sakshi Jha','08882986402','tester.enseur+98@gmail.com',NULL,'12345678',NULL,NULL,NULL,NULL,'xdfcgvbhnjdfgv',1,NULL,'8987986','sxdcfvgbhnjdfg','New Delhi',4.0,2,'rejected','active',NULL,NULL,NULL,NULL,2,1,'Sakshi Jha','Authorized Signatory',NULL,'2026-09-18 06:23:10','2026-11-19 10:37:09'),(3,'VEN-7542','Day Montoya Trading','IVMPK3713B','27AAACD1234F1Z5',NULL,NULL,'Event Management & Logistics',NULL,'Vikram Day','+1 (428) 467-7361','lafaw@mailinator.com',NULL,'50200012345678','HDFC Bank','Nariman Point','Day Montoya Trading LLP',NULL,'HDFC0000123',1,NULL,'Net 30 Days','Unit 402, Nariman Point, Mumbai - 400021','Mumbai, MH',4.5,8,'approved','active',NULL,NULL,NULL,NULL,2,1,'Vikram Day','Authorized Signatory',NULL,'2026-09-22 07:04:26','2026-11-19 10:37:09'),(4,'VEN-9606','Lott and Duran','IVMPK3713B','29AAACL5678G1Z2',NULL,NULL,'Audio-Visual & Staging Equipment',NULL,'Anil Lott','+1 (601) 766-1508','qidapanuhy@mailinator.com',NULL,'000205012345','ICICI Bank','Koramangala','Lott and Duran Media',NULL,'ICIC0000002',0,NULL,'Net 30 Days','7th Block, Koramangala, Bengaluru - 560095','Bengaluru, KA',3.8,3,'pending_admin_head','active',NULL,NULL,NULL,NULL,2,1,'Anil Lott','Partner',NULL,'2026-09-29 05:26:47','2026-11-19 10:37:09'),(5,'VEN-1432','Enseur','ABCDE1234F','012223345',NULL,NULL,'IT',NULL,'Mannat','8882986402','tester.enseur@gmail.com',NULL,'12345678',NULL,NULL,NULL,NULL,'HDFC123455',0,NULL,'Net 30123','Giriraj','Gurugram, HR',4.8,14,'approved','active',NULL,NULL,NULL,NULL,2,1,'Mannat','Authorized Signatory',NULL,'2026-11-04 09:46:21','2026-11-19 10:37:09'),(6,'VEN-2673','Rishiraj Media',NULL,NULL,NULL,NULL,'Creative',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,'Net 30',NULL,'Noida, UP',4.6,9,'approved','active',NULL,NULL,NULL,NULL,2,1,'Authorized Representative','Authorized Signatory',NULL,'2026-11-04 09:52:16','2026-11-19 10:37:09'),(7,'VEN-4793','ADGIPS','ABCDE1234F','ASTT%$$#^T^%$#@@#',NULL,NULL,'2345678',NULL,'Sakshi Jha Jha','+918882986402','feley68078@bitonc.com',NULL,'12345678',NULL,NULL,NULL,NULL,'HDFC123455',0,NULL,'Net 30','sxdcfvgbhnjdfg','New Delhi',4.1,4,'approved','active',NULL,NULL,NULL,NULL,2,1,'Sakshi Jha Jha','Authorized Signatory',NULL,'2026-11-04 10:40:26','2026-11-19 10:37:09'),(8,'VEN-6586','MSIT','ABCDE1234F','dxfcgvbh',NULL,NULL,'Education',NULL,'Sakshi Jha Jha','+918882986402','tester.enseur@gmail.com',NULL,'12345678',NULL,NULL,NULL,NULL,'HDFC123455',0,NULL,'Net 30','szxdfghjn','New Delhi',4.3,5,'pending_admin_head','active',NULL,NULL,NULL,NULL,2,1,'Sakshi Jha Jha','Authorized Signatory',NULL,'2026-11-04 10:40:59','2026-11-19 14:13:16'),(12,'VEND-TRV-101','Balmer Lawrie Travel',NULL,NULL,NULL,NULL,'Travel & Aviation',NULL,'Arvind Sharma','9811022334','arvind@balmertravel.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,4.8,0,'approved','active',NULL,NULL,NULL,NULL,3,1,'Arvind Sharma','Authorized Signatory',NULL,'2026-11-11 10:36:36','2026-11-19 10:37:09'),(13,'VEND-TRV-102','MakeMyTrip Corporate',NULL,NULL,NULL,NULL,'Travel & Hospitality',NULL,'Neha Kapoor','9820044556','neha.k@mmtcorp.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,4.2,0,'approved','active',NULL,NULL,NULL,NULL,3,1,'Neha Kapoor','Authorized Signatory',NULL,'2026-11-11 10:36:36','2026-11-19 10:37:09'),(14,'VEND-TRV-103','Thomas Cook Business',NULL,NULL,NULL,NULL,'Travel & Visa Services',NULL,'Siddharth Nair','9845066778','siddharth@thomascook.example',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,NULL,NULL,3.4,0,'pending_admin_head','active',NULL,NULL,NULL,NULL,3,1,'Siddharth Nair','Authorized Signatory',NULL,'2026-11-11 10:36:36','2026-11-19 12:39:02');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'erp2'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-22 10:25:49
