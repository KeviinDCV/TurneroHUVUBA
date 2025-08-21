-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: turnero_huv
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
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
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
  PRIMARY KEY (`key`)
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
-- Table structure for table `cajas`
--

DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cajas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `asesor_activo_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `fecha_asignacion` timestamp NULL DEFAULT NULL,
  `ip_asesor` varchar(45) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `numero_caja` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cajas_nombre_unique` (`nombre`),
  UNIQUE KEY `cajas_numero_caja_unique` (`numero_caja`),
  KEY `cajas_asesor_activo_id_foreign` (`asesor_activo_id`),
  CONSTRAINT `cajas_asesor_activo_id_foreign` FOREIGN KEY (`asesor_activo_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajas`
--

LOCK TABLES `cajas` WRITE;
/*!40000 ALTER TABLE `cajas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cajas` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_06_19_151026_modify_users_table_for_turnero',1),(5,'2025_06_19_203203_create_cajas_table',1),(6,'2025_06_20_123352_create_servicios_table',1),(7,'2025_06_20_192459_create_tv_configs_table',1),(8,'2025_06_20_200807_create_multimedia_table',1),(9,'2025_06_20_create_user_servicio_table',1),(10,'2025_06_24_184553_add_session_tracking_to_users_table',1),(11,'2025_06_24_185141_add_session_tracking_to_cajas_table',1),(12,'2025_06_24_create_turnos_table',1),(13,'2025_06_25_200439_add_duracion_atencion_to_turnos_table',1),(14,'2025_06_26_141024_add_estado_asesor_to_users_table',1),(15,'2025_06_26_142654_add_session_start_to_users_table',1),(16,'2025_07_01_000000_add_duracion_atencion_to_turnos_table',1),(17,'2025_07_24_062854_create_turno_historial_table',1),(18,'2025_08_14_075100_add_ocultar_turno_to_servicios_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `multimedia`
--

DROP TABLE IF EXISTS `multimedia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `multimedia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `tipo` enum('imagen','video') NOT NULL,
  `extension` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `duracion` int(11) NOT NULL DEFAULT 10,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `tamaño` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `multimedia`
--

LOCK TABLES `multimedia` WRITE;
/*!40000 ALTER TABLE `multimedia` DISABLE KEYS */;
/*!40000 ALTER TABLE `multimedia` ENABLE KEYS */;
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
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `nivel` enum('servicio','subservicio') NOT NULL DEFAULT 'servicio',
  `servicio_padre_id` bigint(20) unsigned DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `codigo` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `ocultar_turno` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servicios_codigo_unique` (`codigo`),
  KEY `servicios_nivel_estado_index` (`nivel`,`estado`),
  KEY `servicios_servicio_padre_id_index` (`servicio_padre_id`),
  CONSTRAINT `servicios_servicio_padre_id_foreign` FOREIGN KEY (`servicio_padre_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (1,'CITAS','Gestión de citas médicas y programación de consultas','servicio',NULL,'activo','CIT',1,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(2,'COPAGOS','Gestión de copagos y pagos de servicios médicos','servicio',NULL,'activo','COP',2,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(3,'FACTURACIÓN','Facturación de servicios médicos y administrativos','servicio',NULL,'activo','FAC',3,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(4,'PROGRAMACIÓN','Programación de procedimientos y cirugías','servicio',NULL,'activo','PRO',4,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(5,'Citas Medicina General','Programación de citas para medicina general','subservicio',1,'activo','CIT-MG',1,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(6,'Citas Especialidades','Programación de citas para especialidades médicas','subservicio',1,'activo','CIT-ESP',2,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(7,'Citas Urgentes','Programación de citas urgentes','subservicio',1,'activo','CIT-URG',3,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(8,'Copago Consulta','Pago de copago para consultas médicas','subservicio',2,'activo','COP-CON',1,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(9,'Copago Procedimientos','Pago de copago para procedimientos médicos','subservicio',2,'activo','COP-PRO',2,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(10,'Facturación Ambulatoria','Facturación de servicios ambulatorios','subservicio',3,'activo','FAC-AMB',1,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(11,'Facturación Hospitalaria','Facturación de servicios hospitalarios','subservicio',3,'activo','FAC-HOS',2,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(12,'Programación Cirugías','Programación de cirugías y procedimientos quirúrgicos','subservicio',4,'activo','PRO-CIR',1,0,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(13,'Programación Exámenes','Programación de exámenes diagnósticos','subservicio',4,'activo','PRO-EXA',2,0,'2025-08-15 15:06:33','2025-08-15 15:06:33');
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
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
INSERT INTO `sessions` VALUES ('OSLPDB4gZKb6p5zYFZLPy2m7iF2GFGJNLsvSIrTo',NULL,'192.168.2.202','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoieGp1V3NVaUlBUUFNMzBiMUcwMXJoMnNZQzgyd2hmRGNPbklpVTNHOCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xOTIuMTY4LjIuMjAyOjgwMDAvdHYiO319',1755273263);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turno_historial`
--

DROP TABLE IF EXISTS `turno_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turno_historial` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `turno_original_id` bigint(20) unsigned DEFAULT NULL,
  `codigo` varchar(255) NOT NULL,
  `numero` int(11) NOT NULL,
  `servicio_id` bigint(20) unsigned NOT NULL,
  `caja_id` bigint(20) unsigned DEFAULT NULL,
  `asesor_id` bigint(20) unsigned DEFAULT NULL,
  `estado` enum('pendiente','llamado','atendido','aplazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `prioridad` enum('normal','prioritaria') NOT NULL DEFAULT 'normal',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_llamado` timestamp NULL DEFAULT NULL,
  `fecha_atencion` timestamp NULL DEFAULT NULL,
  `duracion_atencion` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_backup` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_backup` enum('creacion','actualizacion','eliminacion') NOT NULL DEFAULT 'creacion',
  `datos_adicionales` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_adicionales`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `turno_historial_turno_original_id_index` (`turno_original_id`),
  KEY `turno_historial_servicio_id_fecha_creacion_index` (`servicio_id`,`fecha_creacion`),
  KEY `turno_historial_caja_id_fecha_creacion_index` (`caja_id`,`fecha_creacion`),
  KEY `turno_historial_asesor_id_fecha_creacion_index` (`asesor_id`,`fecha_creacion`),
  KEY `turno_historial_fecha_backup_index` (`fecha_backup`),
  KEY `turno_historial_tipo_backup_index` (`tipo_backup`),
  KEY `turno_historial_estado_fecha_creacion_index` (`estado`,`fecha_creacion`),
  CONSTRAINT `turno_historial_asesor_id_foreign` FOREIGN KEY (`asesor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turno_historial_caja_id_foreign` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turno_historial_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turno_historial`
--

LOCK TABLES `turno_historial` WRITE;
/*!40000 ALTER TABLE `turno_historial` DISABLE KEYS */;
/*!40000 ALTER TABLE `turno_historial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `turnos`
--

DROP TABLE IF EXISTS `turnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `turnos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(255) NOT NULL,
  `numero` int(11) NOT NULL,
  `servicio_id` bigint(20) unsigned NOT NULL,
  `caja_id` bigint(20) unsigned DEFAULT NULL,
  `asesor_id` bigint(20) unsigned DEFAULT NULL,
  `estado` enum('pendiente','llamado','atendido','aplazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `prioridad` enum('normal','prioritaria') NOT NULL DEFAULT 'normal',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_llamado` timestamp NULL DEFAULT NULL,
  `fecha_atencion` timestamp NULL DEFAULT NULL,
  `duracion_atencion` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `turnos_servicio_id_estado_index` (`servicio_id`,`estado`),
  KEY `turnos_caja_id_estado_index` (`caja_id`,`estado`),
  KEY `turnos_asesor_id_index` (`asesor_id`),
  KEY `turnos_fecha_creacion_index` (`fecha_creacion`),
  CONSTRAINT `turnos_asesor_id_foreign` FOREIGN KEY (`asesor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turnos_caja_id_foreign` FOREIGN KEY (`caja_id`) REFERENCES `cajas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `turnos_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `turnos`
--

LOCK TABLES `turnos` WRITE;
/*!40000 ALTER TABLE `turnos` DISABLE KEYS */;
/*!40000 ALTER TABLE `turnos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tv_configs`
--

DROP TABLE IF EXISTS `tv_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tv_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticker_message` text NOT NULL DEFAULT '? Bienvenidos al Hospital Universitario del Valle "Evaristo García" E.S.E • Horarios de atención: Lunes a Viernes 6:00 AM - 6:00 PM • Sábados 6:00 AM - 2:00 PM • Para emergencias las 24 horas • Recuerde mantener su distancia y usar tapabocas • Su salud es nuestra prioridad • Gracias por confiar en nosotros ?',
  `ticker_speed` int(11) NOT NULL DEFAULT 35,
  `ticker_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tv_configs`
--

LOCK TABLES `tv_configs` WRITE;
/*!40000 ALTER TABLE `tv_configs` DISABLE KEYS */;
INSERT INTO `tv_configs` VALUES (1,'🏥 Bienvenidos al Hospital Universitario del Valle \"Evaristo García\" E.S.E • Horarios de atención: Lunes a Viernes 6:00 AM - 6:00 PM • Sábados 6:00 AM - 2:00 PM • Para emergencias las 24 horas • Recuerde mantener su distancia y usar tapabocas • Su salud es nuestra prioridad • Gracias por confiar en nosotros 💙',35,1,'2025-08-15 15:54:23','2025-08-15 15:54:23');
/*!40000 ALTER TABLE `tv_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_servicio`
--

DROP TABLE IF EXISTS `user_servicio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_servicio` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `servicio_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_servicio_user_id_servicio_id_unique` (`user_id`,`servicio_id`),
  KEY `user_servicio_user_id_index` (`user_id`),
  KEY `user_servicio_servicio_id_index` (`servicio_id`),
  CONSTRAINT `user_servicio_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_servicio_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_servicio`
--

LOCK TABLES `user_servicio` WRITE;
/*!40000 ALTER TABLE `user_servicio` DISABLE KEYS */;
INSERT INTO `user_servicio` VALUES (1,2,1,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(2,2,2,'2025-08-15 15:06:33','2025-08-15 15:06:33'),(3,2,3,'2025-08-15 15:06:33','2025-08-15 15:06:33');
/*!40000 ALTER TABLE `user_servicio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `correo_electronico` varchar(255) DEFAULT NULL,
  `rol` enum('Administrador','Asesor') NOT NULL DEFAULT 'Asesor',
  `cedula` varchar(255) DEFAULT NULL,
  `nombre_usuario` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `session_start` timestamp NULL DEFAULT NULL COMMENT 'Fecha y hora de inicio de la sesión actual',
  `last_activity` timestamp NULL DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `estado_asesor` enum('disponible','ocupado','descanso') NOT NULL DEFAULT 'disponible' COMMENT 'Estado actual del asesor: disponible, ocupado, en descanso',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrador HUV','admin@huv.gov.co','Administrador','12345678','admin','Administrador HUV','admin@huv.gov.co',NULL,'$2y$12$XRmZoja/FHA8XeXOQoe.4OSZtGXUp1d3f1A7wIcrwZ0jO/SZHr4NW',NULL,NULL,NULL,NULL,NULL,'disponible','2025-08-15 15:06:33','2025-08-15 15:54:10'),(2,'Asesor de Prueba','asesor@huv.gov.co','Asesor','87654321','asesor','Asesor de Prueba','asesor@huv.gov.co',NULL,'$2y$12$Utd7TVZzf28mwo.Fy6onPuixfSkr5NcSw0Wqusmgp1CvUSRn1mMM.',NULL,NULL,NULL,NULL,NULL,'disponible','2025-08-15 15:06:33','2025-08-15 15:06:33');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-21  8:21:52
