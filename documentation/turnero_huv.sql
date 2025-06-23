-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-06-2025 a las 17:26:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `turnero_huv`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cajas`
--

CREATE TABLE `cajas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('activa','inactiva') NOT NULL DEFAULT 'activa',
  `ubicacion` varchar(255) DEFAULT NULL,
  `numero_caja` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cajas`
--

INSERT INTO `cajas` (`id`, `nombre`, `descripcion`, `estado`, `ubicacion`, `numero_caja`, `created_at`, `updated_at`) VALUES
(5, 'VENTANILLA 1', NULL, 'activa', 'UBA', 1, '2025-06-20 21:27:40', '2025-06-20 21:27:40'),
(6, 'VENTANILLA 13', NULL, 'activa', 'UBA', 13, '2025-06-20 21:27:51', '2025-06-20 21:27:51'),
(7, 'VENTANILLA 14', NULL, 'activa', 'UBA', 14, '2025-06-20 21:28:07', '2025-06-20 21:28:07'),
(8, 'VENTANILLA 15', NULL, 'activa', 'UBA', 15, '2025-06-20 21:28:19', '2025-06-20 21:28:19'),
(9, 'VENTANILLA 16', NULL, 'activa', 'UBA', 16, '2025-06-20 21:28:27', '2025-06-20 21:29:10'),
(10, 'VENTANILLA 17', NULL, 'activa', 'UBA', 17, '2025-06-20 21:28:37', '2025-06-20 21:28:37'),
(11, 'VENTANILLA 18', NULL, 'activa', 'UBA', 18, '2025-06-20 21:28:44', '2025-06-20 21:28:44'),
(12, 'VENTANILLA 19', NULL, 'activa', 'UBA', 19, '2025-06-20 21:28:56', '2025-06-20 21:28:56'),
(13, 'VENTANILLA 20', NULL, 'activa', 'UBA', 20, '2025-06-20 21:29:05', '2025-06-20 21:29:05'),
(14, 'VENTANILLA 21', NULL, 'activa', 'UBA', 21, '2025-06-20 21:29:20', '2025-06-20 21:29:20'),
(15, 'CAJA 10', NULL, 'activa', 'UBA', 10, '2025-06-20 21:29:42', '2025-06-20 21:29:42'),
(16, 'CAJA 11', NULL, 'activa', 'UBA', 11, '2025-06-20 21:29:49', '2025-06-20 21:29:49'),
(17, 'CAJA 12', NULL, 'activa', 'UBA', 12, '2025-06-20 21:29:55', '2025-06-20 21:29:55'),
(18, 'CAJA 2', NULL, 'activa', 'UBA', 2, '2025-06-20 21:30:00', '2025-06-20 21:30:00'),
(19, 'CAJA 3', NULL, 'activa', 'UBA', 3, '2025-06-20 21:30:08', '2025-06-20 21:30:08'),
(20, 'CAJA 4', NULL, 'activa', 'UBA', 4, '2025-06-20 21:30:14', '2025-06-20 21:30:14'),
(21, 'CAJA 5', NULL, 'activa', 'UBA', 5, '2025-06-20 21:30:20', '2025-06-20 21:30:20'),
(22, 'CAJA 6', NULL, 'activa', 'UBA', 6, '2025-06-20 21:30:25', '2025-06-20 21:30:25'),
(23, 'CAJA 7', NULL, 'activa', 'UBA', 7, '2025-06-20 21:30:31', '2025-06-20 21:30:31'),
(24, 'CAJA 8', NULL, 'activa', 'UBA', 8, '2025-06-20 21:30:39', '2025-06-20 21:30:39'),
(25, 'CAJA 9', NULL, 'activa', 'UBA', 9, '2025-06-20 21:30:46', '2025-06-20 21:30:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

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
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_06_19_151026_modify_users_table_for_turnero', 2),
(5, '2025_06_19_151222_clean_users_table', 3),
(6, '2025_06_19_174151_add_default_value_to_name_old_column', 4),
(7, '2025_06_19_174316_add_default_value_to_email_old_column', 5),
(8, '2025_06_19_203203_create_cajas_table', 6),
(9, '2025_06_20_123352_create_servicios_table', 7),
(10, '2025_06_20_create_user_servicio_table', 8),
(11, '2025_06_20_192459_create_tv_configs_table', 9),
(12, '2025_06_20_200807_create_multimedia_table', 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `multimedia`
--

CREATE TABLE `multimedia` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `archivo` varchar(255) NOT NULL,
  `tipo` enum('imagen','video') NOT NULL,
  `extension` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `duracion` int(11) NOT NULL DEFAULT 10,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `tamaño` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `multimedia`
--

INSERT INTO `multimedia` (`id`, `nombre`, `archivo`, `tipo`, `extension`, `orden`, `duracion`, `activo`, `tamaño`, `created_at`, `updated_at`) VALUES
(7, 'Captura de pantalla 2025-06-19 210310', 'multimedia/90431196-7a7d-4fc9-97ce-22c9f2e41403.png', 'imagen', 'png', 1, 10, 1, 29698, '2025-06-21 08:11:49', '2025-06-21 08:11:49'),
(8, 'Captura de pantalla 2025-06-15 100820', 'multimedia/281ba3f9-33f5-48b1-a09f-4b3423aadfdc.png', 'imagen', 'png', 2, 5, 1, 15793, '2025-06-21 08:12:00', '2025-06-21 08:12:00'),
(9, 'Roblox 2025-06-20 16-38-29', 'multimedia/5d29314e-3152-4dfc-8e73-60b0b572e709.mp4', 'video', 'mp4', 3, 11, 1, 12473182, '2025-06-21 08:12:15', '2025-06-21 08:12:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `nivel` enum('servicio','subservicio') NOT NULL DEFAULT 'servicio',
  `servicio_padre_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
  `codigo` varchar(255) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `descripcion`, `nivel`, `servicio_padre_id`, `estado`, `codigo`, `orden`, `created_at`, `updated_at`) VALUES
(1, 'CITAS', NULL, 'servicio', NULL, 'activo', 'CIT', 1, '2025-06-20 17:40:01', '2025-06-20 20:47:55'),
(2, 'COPAGOS', NULL, 'servicio', NULL, 'activo', 'COP', 2, '2025-06-20 17:40:01', '2025-06-20 20:47:58'),
(3, 'FACTURACIÓN', NULL, 'servicio', NULL, 'activo', 'FAC', 3, '2025-06-20 17:40:01', '2025-06-20 20:48:00'),
(15, 'PROGRAMACIÓN', NULL, 'servicio', NULL, 'activo', 'PRO', 4, '2025-06-20 20:39:30', '2025-06-20 21:00:17'),
(18, 'IMPRESIONES', NULL, 'subservicio', 1, 'activo', 'IMP', 5, '2025-06-20 21:06:18', '2025-06-20 21:06:58'),
(19, 'CITAS GENERAL', NULL, 'subservicio', 1, 'activo', 'GEN', 6, '2025-06-20 21:21:55', '2025-06-20 21:21:55'),
(20, 'CITAS PRIORITARIA', NULL, 'subservicio', 1, 'activo', 'PRI', 7, '2025-06-20 21:22:20', '2025-06-20 21:22:20'),
(21, 'CITA FUNCIONARIO', NULL, 'subservicio', 1, 'activo', 'FUN', 8, '2025-06-20 21:22:43', '2025-06-20 21:22:43'),
(22, 'PROGRAMACIÓN GENERAL', NULL, 'subservicio', 15, 'activo', 'PRG', 1, '2025-06-20 21:25:26', '2025-06-20 21:25:26'),
(23, 'PROGRAMACION PRIORITARIA', NULL, 'subservicio', 15, 'activo', 'PRP', 2, '2025-06-20 21:25:42', '2025-06-20 21:25:42');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('bIrWXqGaaV20BiBrZ0WdvhxAemHanf1sqTStN9l4', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQjQ3RDRkeWdrM0c2cXVJUUxUcUtyMk5ORG9wRWxPWXlIcWpRaEpyVSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3R2LWNvbmZpZyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9', 1750522626),
('bS9A7EURnzOcNMGhzh3BlcO3Hyjq9wpZ7KwbilXH', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiNDE3OWxZUUxscll4YUNLUE1YTWxxeVZrbFk3aE5tRlowVHFFWWpHYyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvbXVsdGltZWRpYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1750520210);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tv_configs`
--

CREATE TABLE `tv_configs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ticker_message` text NOT NULL DEFAULT '? Bienvenidos al Hospital Universitario del Valle "Evaristo García" E.S.E • Horarios de atención: Lunes a Viernes 6:00 AM - 6:00 PM • Sábados 6:00 AM - 2:00 PM • Para emergencias las 24 horas • Recuerde mantener su distancia y usar tapabocas • Su salud es nuestra prioridad • Gracias por confiar en nosotros ?',
  `ticker_speed` int(11) NOT NULL DEFAULT 35,
  `ticker_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tv_configs`
--

INSERT INTO `tv_configs` (`id`, `ticker_message`, `ticker_speed`, `ticker_enabled`, `created_at`, `updated_at`) VALUES
(1, '🏥 Bienvenidos al Hospital Universitario del Valle \"Evaristo García\" E.S.E • Horarios de atención: Lunes a Viernes 6:00 AM - 6:00 PM • Sábados 6:00 AM - 2:00 PM • Para emergencias las 24 horas • Recuerde mantener su distancia y usar tapabocas • Su salud es nuestra prioridad • Gracias por confiar en nosotros 💙', 56, 1, '2025-06-21 00:45:11', '2025-06-21 20:53:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `correo_electronico` varchar(255) NOT NULL,
  `rol` enum('Administrador','Asesor') NOT NULL,
  `cedula` varchar(255) NOT NULL,
  `nombre_usuario` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name_old` varchar(255) DEFAULT 'legacy_user',
  `email_old` varchar(255) DEFAULT 'legacy@example.com'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre_completo`, `correo_electronico`, `rol`, `cedula`, `nombre_usuario`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `name_old`, `email_old`) VALUES
(1, 'Administrador HUV', 'admin@huv.gov.co', 'Administrador', '12345678', 'admin', NULL, '$2y$12$GGqLafJEKbdT45nfyuURgeQ4OzZqHCtqYfigrXgJ/tjgPRvGLiliG', NULL, '2025-06-19 20:12:59', '2025-06-19 20:12:59', 'legacy_user', 'legacy@example.com'),
(2, 'Kevin David Chavarro Erazo', 'asesor@huv.gov.co', 'Asesor', '19191919', 'asesor', NULL, '$2y$12$hXML1y6LaKhNpNoyhzNl2OpfK8FXomyWFoPfk0XSoiYJRJ0M3pQ5a', NULL, '2025-06-19 20:12:59', '2025-06-20 20:58:21', 'legacy_user', 'legacy@example.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_servicio`
--

CREATE TABLE `user_servicio` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cajas`
--
ALTER TABLE `cajas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cajas_nombre_unique` (`nombre`),
  ADD UNIQUE KEY `cajas_numero_caja_unique` (`numero_caja`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `multimedia`
--
ALTER TABLE `multimedia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `servicios_codigo_unique` (`codigo`),
  ADD KEY `servicios_nivel_estado_index` (`nivel`,`estado`),
  ADD KEY `servicios_servicio_padre_id_index` (`servicio_padre_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `tv_configs`
--
ALTER TABLE `tv_configs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_correo_electronico_unique` (`correo_electronico`),
  ADD UNIQUE KEY `users_cedula_unique` (`cedula`),
  ADD UNIQUE KEY `users_nombre_usuario_unique` (`nombre_usuario`);

--
-- Indices de la tabla `user_servicio`
--
ALTER TABLE `user_servicio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_servicio_user_id_servicio_id_unique` (`user_id`,`servicio_id`),
  ADD KEY `user_servicio_user_id_index` (`user_id`),
  ADD KEY `user_servicio_servicio_id_index` (`servicio_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cajas`
--
ALTER TABLE `cajas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `multimedia`
--
ALTER TABLE `multimedia`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `tv_configs`
--
ALTER TABLE `tv_configs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `user_servicio`
--
ALTER TABLE `user_servicio`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `servicios_servicio_padre_id_foreign` FOREIGN KEY (`servicio_padre_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_servicio`
--
ALTER TABLE `user_servicio`
  ADD CONSTRAINT `user_servicio_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_servicio_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
