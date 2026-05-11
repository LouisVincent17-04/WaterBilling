DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','manager','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `full_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_login_at` datetime NULL DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_username`(`username`) USING BTREE,
  UNIQUE INDEX `uq_email`(`email`) USING BTREE,
  INDEX `idx_status`(`status`) USING BTREE,
  INDEX `idx_role`(`role`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 'admin', 'admin@COWASCO Waters.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 'System Admin', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00');
INSERT INTO `users` VALUES (2, 'vinxvade17', 'vinxvadezxz@gmail.com', '$2y$12$UEAfp4aPiXlAeVWaUIl8G.hq9NlIbVUXSyoPETaUphiJoWJtGEZbe', 'user', 'active', 'Louis Vincent Tajanlangit', '2026-04-27 21:17:49', '2026-04-27 21:17:21', '2026-04-27 21:17:21');
INSERT INTO `users` VALUES (3, 'glenn', 'glennrevalde@gmail.com', '$2y$12$2b8F8J6q4RLeBpGaUJ/CxegHt2AlvkyJuPP54pERAODqLYbzdp.AO', 'user', 'active', 'GLENN TAJANLANGIT', '2026-04-28 06:38:57', '2026-04-28 06:38:38', '2026-04-28 06:38:38');

SET FOREIGN_KEY_CHECKS = 1;
-- ----------------------------
-- Table structure for password_resets
-- ----------------------------
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_token`(`token`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `fk_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for user_sessions
-- ----------------------------
DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uq_token`(`token`) USING BTREE,
  INDEX `idx_user_id`(`user_id`) USING BTREE,
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Records of user_sessions
-- ----------------------------
INSERT INTO `user_sessions` VALUES (1, 2, '2bbab102ce8a8c18c6b52897842ad0d7c2cf565dbe4225be066e79d5eed873b7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '::1', '2026-05-27 15:17:49', '2026-04-27 21:17:49');
INSERT INTO `user_sessions` VALUES (2, 3, 'b4ed80ea73a0b0f679d0d3ac2583287fef4bdd0dede196305b5cd9fc12e2cacd', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '192.168.254.113', '2026-05-28 00:38:57', '2026-04-28 06:38:57');

-- ----------------------------
-- Table structure for users
-- ----------------------------

DROP TABLE IF EXISTS `discounts`;
CREATE TABLE `discounts`  (
  `discount_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `discount_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_rate` float NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`discount_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Compact;


DROP TABLE IF EXISTS `discounted_members`;
CREATE TABLE `discounted_members` (
  `dm_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` int(10) NOT NULL,
  `discount_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`dm_id`) USING BTREE,
  CONSTRAINT `fk_discounted_members_discount`
    FOREIGN KEY (`discount_id`) REFERENCES `discounts` (`discount_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_discounted_members_member`
    FOREIGN KEY (`member_id`) REFERENCES `members` (`pkey`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB 
AUTO_INCREMENT=1 
CHARACTER SET=utf8mb4 
COLLATE=utf8mb4_unicode_ci 
ROW_FORMAT=Compact;

--------------------------------------------------------------------------------------------
-- For billing periods 
DROP TABLE IF EXISTS `bill_periods`;
CREATE TABLE `bill_periods` (
  `period_id` int(10) NOT NULL AUTO_INCREMENT,
  `bp_code` varchar(20) NOT NULL, -- EXAMPLE 010226_020226
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',

  `opened_by` int(10) UNSIGNED NOT NULL,
  `closed_by` int(10) UNSIGNED NULL,

  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `closed_at` datetime NULL DEFAULT NULL,

  PRIMARY KEY (`period_id`),

  UNIQUE KEY `uniq_bp_code` (`bp_code`),

  INDEX `idx_opened_by` (`opened_by`),
  INDEX `idx_closed_by` (`closed_by`),

  CONSTRAINT `fk_opened_by_user`
    FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  CONSTRAINT `fk_closed_by_user`
    FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-------------------------------------------------------------------------------------------------------------------------------------------
-- For installment billing
CREATE TABLE `installment_bills` (
  `installment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  `member_id` INT UNSIGNED NOT NULL,
  `bill_code_id`  int NOT NULL, 

  `payment_mode` ENUM('monthly','quarterly','semi_annually','annually') NOT NULL,

  `total_amount` DECIMAL(10,2) NOT NULL,
  `term` INT NOT NULL, -- number of payments (NOT months anymore)

  `amortization_type` ENUM('fixed','flexible') NOT NULL,

  `start_date` DATE NOT NULL,

  `status` ENUM('active','completed','cancelled') DEFAULT 'active',

  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  CONSTRAINT `fk_installment_member`
    FOREIGN KEY (`member_id`)
    REFERENCES `members` (`pkey`)
    ON DELETE CASCADE
  
    CONSTRAINT `fk_installment_bill_code`
    FOREIGN KEY (`bill_code_id`)
    REFERENCES `bill_codes` (`code_id`)
    ON DELETE CASCADE
  
);


CREATE TABLE `installment_schedules` (
  `schedule_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  `installment_id` INT UNSIGNED NOT NULL,

  `due_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,

  `status` ENUM('pending','paid') DEFAULT 'pending',

  `paid_at` DATETIME NULL,

  CONSTRAINT `fk_installment`
    FOREIGN KEY (`installment_id`)
    REFERENCES `installment_bills` (`installment_id`)
    ON DELETE CASCADE
);

--------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `bill_codes`;
CREATE TABLE `bill_codes`  (
  `code_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `gl_account` int(11) NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `default_amount` float NULL DEFAULT 0,
  `modified_by` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`code_id`) USING BTREE,
  UNIQUE INDEX `code`(`code`) USING BTREE,
  INDEX `fk_bill_codes_user`(`modified_by`) USING BTREE,
  INDEX `fk_bill_codes_gl_account`(`gl_account`) USING BTREE,
  CONSTRAINT `fk_bill_codes_user` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_bill_codes_gl_account` FOREIGN KEY (`gl_account`) REFERENCES `chartofaccntstbl` (`AccntID`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;

-- DONE

--------------------------------------------------------------------------------------------

CREATE TABLE `one_time_bills` (
  `bill_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

  `member_id` INT NOT NULL,
  `bill_code_id` INT UNSIGNED NOT NULL,

  `bill_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `term_days` INT NOT NULL,

  `total_amount` DECIMAL(10,2) NOT NULL,

  `status` ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid',

  `created_by` INT UNSIGNED NOT NULL,

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (`bill_id`),

  INDEX (`member_id`),
  INDEX (`bill_code_id`),
  INDEX (`created_by`),

  CONSTRAINT `fk_one_time_member`
    FOREIGN KEY (`member_id`)
    REFERENCES `members` (`pkey`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_one_time_bill_code`
    FOREIGN KEY (`bill_code_id`)
    REFERENCES `bill_codes` (`code_id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_one_time_created_by`
    FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`)
    ON DELETE RESTRICT

) ENGINE=InnoDB;

--------------------------------------------------------------------------------------------

CREATE TABLE `access_control` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `bill_codes`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `bill_periods`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `billing_management`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `configurations.php`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `create_billing`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `dashboard`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `member_list`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `create_billings`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `discount_management`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `discounted_members`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `installment_bill`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `one_time_billing`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `rates_management`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `recurring_bill`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed
  `water_rates`TINYINT DEFAULT 0, -- 0 = denied, 1 = allowed  
  `modified_by` INT UNSIGNED NULL, -- who performed the action
  `timestamp` DATETIME NOT NULL,

  CONSTRAINT `fk_access_control_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
  
  CONSTRAINT `fk_access_control_modified_by`
    FOREIGN KEY (`modified_by`)
    REFERENCES `users` (`id`)
    ON DELETE SET NULL
);



DROP TABLE IF EXISTS `water_rates`;
CREATE TABLE `water_rates` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_cb` int(11) NOT NULL,
  `to_cb` int(11) NULL DEFAULT NULL COMMENT 'NULL signifies Unlimited / Infinity',
  `amount` decimal(10,2) NOT NULL,
  `bill_type` enum('FIXED','VARIABLE') NOT NULL DEFAULT 'VARIABLE',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`rate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;