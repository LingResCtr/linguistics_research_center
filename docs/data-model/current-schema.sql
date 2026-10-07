/*M!999999\- enable the sandbox mode */ 

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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `book` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_section` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `book_section_book_id_slug_unique` (`book_id`,`slug`),
  CONSTRAINT `book_section_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `book` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  UNIQUE KEY `cache_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_element` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gloss_id` bigint(20) unsigned NOT NULL,
  `part_of_speech` varchar(191) DEFAULT NULL,
  `analysis` varchar(191) DEFAULT NULL,
  `head_word_id` bigint(20) unsigned NOT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eieol_element_gloss_id_order_index` (`gloss_id`,`order`),
  KEY `eieol_element_head_word_id_foreign` (`head_word_id`),
  CONSTRAINT `eieol_element_gloss_id_foreign` FOREIGN KEY (`gloss_id`) REFERENCES `eieol_gloss` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eieol_element_head_word_id_foreign` FOREIGN KEY (`head_word_id`) REFERENCES `eieol_head_word` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_gloss` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `surface_form` varchar(191) DEFAULT NULL,
  `contextual_gloss` varchar(191) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `underlying_form` varchar(191) DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `glossed_text_id` bigint(20) unsigned NOT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eieol_gloss_contextual_gloss_index` (`contextual_gloss`),
  KEY `eieol_gloss_language_id_foreign` (`language_id`),
  KEY `eieol_gloss_glossed_text_id_foreign` (`glossed_text_id`),
  CONSTRAINT `eieol_gloss_glossed_text_id_foreign` FOREIGN KEY (`glossed_text_id`) REFERENCES `eieol_glossed_text` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eieol_gloss_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `eieol_language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_glossed_text` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `glossed_text` text DEFAULT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `custom_gloss_mapping` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eieol_glossed_text_lesson_id_order_unique` (`lesson_id`,`order`),
  KEY `eieol_glossed_text_order_index` (`order`),
  CONSTRAINT `eieol_glossed_text_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `eieol_lesson` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_grammar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `grammar_text` text DEFAULT NULL,
  `section_number` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eieol_grammar_lesson_id_order_unique` (`lesson_id`,`order`),
  UNIQUE KEY `eieol_grammar_lesson_id_section_number_unique` (`lesson_id`,`section_number`),
  KEY `eieol_grammar_order_index` (`order`),
  KEY `eieol_grammar_section_number_index` (`section_number`),
  CONSTRAINT `eieol_grammar_lesson_id_foreign` FOREIGN KEY (`lesson_id`) REFERENCES `eieol_lesson` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_head_word` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `word` varchar(191) DEFAULT NULL,
  `definition` varchar(191) DEFAULT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `etyma_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `keywords` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `eieol_head_word_word_index` (`word`),
  KEY `eieol_head_word_language_id_foreign` (`language_id`),
  KEY `eieol_head_word_etyma_id_foreign` (`etyma_id`),
  CONSTRAINT `eieol_head_word_etyma_id_foreign` FOREIGN KEY (`etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eieol_head_word_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `eieol_language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_language` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(191) DEFAULT NULL,
  `custom_keyboard_layout` text DEFAULT NULL,
  `substitutions` text DEFAULT NULL,
  `custom_sort` text DEFAULT NULL,
  `lang_attribute` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eieol_language_language_index` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_lesson` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_id` bigint(20) unsigned NOT NULL,
  `title` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `language_id` bigint(20) unsigned NOT NULL,
  `intro_text` text DEFAULT NULL,
  `lesson_text` longtext DEFAULT NULL,
  `lesson_translation` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eieol_lesson_series_id_order_unique` (`series_id`,`order`),
  KEY `eieol_lesson_order_index` (`order`),
  KEY `eieol_lesson_language_id_foreign` (`language_id`),
  CONSTRAINT `eieol_lesson_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `eieol_language` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eieol_lesson_series_id_foreign` FOREIGN KEY (`series_id`) REFERENCES `eieol_series` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_series` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  `menu_name` varchar(191) DEFAULT NULL,
  `menu_order` varchar(191) DEFAULT NULL,
  `expanded_title` varchar(191) DEFAULT NULL,
  `published` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eieol_series_title_unique` (`title`),
  KEY `eieol_series_order_index` (`order`),
  KEY `eieol_series_menu_order_index` (`menu_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `eieol_series_language` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `series_id` int(11) NOT NULL,
  `lang` varchar(3) NOT NULL,
  `display` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `iso_language` (
  `iso_id` varchar(3) DEFAULT NULL,
  `Part2B` varchar(3) DEFAULT NULL,
  `Part2T` varchar(3) DEFAULT NULL,
  `Part1` varchar(2) DEFAULT NULL,
  `Scope` varchar(1) DEFAULT NULL,
  `Language_Type` varchar(1) DEFAULT NULL,
  `Ref_Name` varchar(58) DEFAULT NULL,
  `Comment` varchar(42) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL,
  `text` longtext NOT NULL,
  `pointer` varchar(1024) NOT NULL,
  `pointer_desc` varchar(1024) NOT NULL,
  `status` varchar(64) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `issue_comment` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `issue_id` bigint(20) unsigned NOT NULL,
  `type` varchar(16) NOT NULL,
  `text` longtext NOT NULL,
  `user_logon` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issue_comment_issue_id_foreign` (`issue_id`),
  CONSTRAINT `issue_comment_issue_id_foreign` FOREIGN KEY (`issue_id`) REFERENCES `issue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_etyma` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `old_id` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `page_number` varchar(191) DEFAULT NULL,
  `entry` varchar(191) DEFAULT NULL,
  `homograph_number` varchar(16) DEFAULT NULL,
  `gloss` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gloss`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_etyma_lexicon_id_order_index` (`lexicon_id`,`order`),
  CONSTRAINT `lex_etyma_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_etyma_cross_reference` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_etyma_id` bigint(20) unsigned NOT NULL,
  `to_etyma_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_etyma_cross_reference_from_etyma_id_foreign` (`from_etyma_id`),
  KEY `lex_etyma_cross_reference_to_etyma_id_foreign` (`to_etyma_id`),
  CONSTRAINT `lex_etyma_cross_reference_from_etyma_id_foreign` FOREIGN KEY (`from_etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_etyma_cross_reference_to_etyma_id_foreign` FOREIGN KEY (`to_etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_etyma_extra_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etyma_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_etyma_extra_data_etyma_id_foreign` (`etyma_id`),
  CONSTRAINT `lex_etyma_extra_data_etyma_id_foreign` FOREIGN KEY (`etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_etyma_reflex` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etyma_id` bigint(20) unsigned NOT NULL,
  `reflex_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_etyma_reflex_etyma_id_foreign` (`etyma_id`),
  KEY `lex_etyma_reflex_reflex_id_foreign` (`reflex_id`),
  CONSTRAINT `lex_etyma_reflex_etyma_id_foreign` FOREIGN KEY (`etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_etyma_reflex_reflex_id_foreign` FOREIGN KEY (`reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_etyma_semantic_field` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `etyma_id` bigint(20) unsigned NOT NULL,
  `semantic_field_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_etyma_semantic_field_etyma_id_foreign` (`etyma_id`),
  KEY `lex_etyma_semantic_field_semantic_field_id_foreign` (`semantic_field_id`),
  CONSTRAINT `lex_etyma_semantic_field_etyma_id_foreign` FOREIGN KEY (`etyma_id`) REFERENCES `lex_etyma` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_etyma_semantic_field_semantic_field_id_foreign` FOREIGN KEY (`semantic_field_id`) REFERENCES `lex_semantic_field` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_language` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name`)),
  `order` int(11) NOT NULL,
  `abbr` varchar(191) DEFAULT NULL,
  `aka` varchar(191) DEFAULT NULL,
  `sub_family_id` bigint(20) unsigned NOT NULL,
  `override_family` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`description`)),
  PRIMARY KEY (`id`),
  KEY `lex_language_sub_family_id_order_index` (`sub_family_id`,`order`),
  KEY `lex_language_order_index` (`order`),
  CONSTRAINT `lex_language_sub_family_id_foreign` FOREIGN KEY (`sub_family_id`) REFERENCES `lex_language_sub_family` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_language_family` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name`)),
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_language_family_order_index` (`order`),
  KEY `lex_language_family_lexicon_id_foreign` (`lexicon_id`),
  CONSTRAINT `lex_language_family_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_language_sub_family` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name`)),
  `order` int(11) NOT NULL,
  `family_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_language_sub_family_order_index` (`order`),
  KEY `lex_language_sub_family_family_id_foreign` (`family_id`),
  CONSTRAINT `lex_language_sub_family_family_id_foreign` FOREIGN KEY (`family_id`) REFERENCES `lex_language_family` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_lexicon` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `protolang_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`protolang_name`)),
  `viewer_lang_options` varchar(255) DEFAULT NULL,
  `landing_page_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`landing_page_content`)),
  `protolanguage_page_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`protolanguage_page_content`)),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_lexicon_data_cache` (
  `uuid` char(36) NOT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  `reflex_id` bigint(20) unsigned NOT NULL,
  `content_lang_code` varchar(10) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `lex_lexicon_data_cache_reflex_id_foreign` (`reflex_id`),
  KEY `lexicon_id_lang_code_reflex_id_index` (`lexicon_id`,`content_lang_code`,`reflex_id`),
  CONSTRAINT `lex_lexicon_data_cache_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_lexicon_data_cache_reflex_id_foreign` FOREIGN KEY (`reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_part_of_speech` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) DEFAULT NULL,
  `display` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`display`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_part_of_speech_lexicon_id_code_index` (`lexicon_id`,`code`),
  CONSTRAINT `lex_part_of_speech_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_reflex` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `language_id` bigint(20) unsigned NOT NULL,
  `lang_attribute` varchar(191) DEFAULT NULL,
  `gloss` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`gloss`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `entries` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_reflex_language_id_index` (`language_id`),
  CONSTRAINT `lex_reflex_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `lex_language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_reflex_cross_reference` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_reflex_id` bigint(20) unsigned NOT NULL,
  `to_reflex_id` bigint(20) unsigned NOT NULL,
  `relationship` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`relationship`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_reflex_cross_reference_from_reflex_id_foreign` (`from_reflex_id`),
  KEY `lex_reflex_cross_reference_to_reflex_id_foreign` (`to_reflex_id`),
  CONSTRAINT `lex_reflex_cross_reference_from_reflex_id_foreign` FOREIGN KEY (`from_reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_reflex_cross_reference_to_reflex_id_foreign` FOREIGN KEY (`to_reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_reflex_extra_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reflex_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`value`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_reflex_extra_data_reflex_id_foreign` (`reflex_id`),
  CONSTRAINT `lex_reflex_extra_data_reflex_id_foreign` FOREIGN KEY (`reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_reflex_part_of_speech` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reflex_id` bigint(20) unsigned NOT NULL,
  `text` varchar(191) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lex_reflex_part_of_speech_reflex_id_text_unique` (`reflex_id`,`text`),
  CONSTRAINT `lex_reflex_part_of_speech_reflex_id_foreign` FOREIGN KEY (`reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_reflex_source` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reflex_id` bigint(20) unsigned NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `page_number` varchar(32) DEFAULT NULL,
  `original_text` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_reflex_source_source_id_foreign` (`source_id`),
  KEY `lex_reflex_source_reflex_id_source_id_index` (`reflex_id`,`source_id`),
  CONSTRAINT `lex_reflex_source_reflex_id_foreign` FOREIGN KEY (`reflex_id`) REFERENCES `lex_reflex` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lex_reflex_source_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `lex_source` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_semantic_category` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text`)),
  `number` varchar(191) DEFAULT NULL,
  `abbr` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_semantic_category_number_index` (`number`),
  KEY `lex_semantic_category_lexicon_id_abbr_index` (`lexicon_id`,`abbr`),
  CONSTRAINT `lex_semantic_category_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_semantic_field` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`text`)),
  `number` varchar(191) DEFAULT NULL,
  `abbr` varchar(191) DEFAULT NULL,
  `semantic_category_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_semantic_field_number_index` (`number`),
  KEY `lex_semantic_field_semantic_category_id_foreign` (`semantic_category_id`),
  CONSTRAINT `lex_semantic_field_semantic_category_id_foreign` FOREIGN KEY (`semantic_category_id`) REFERENCES `lex_semantic_category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lex_source` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) DEFAULT NULL,
  `display` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lexicon_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lex_source_code_index` (`code`),
  KEY `lex_source_lexicon_id_code_index` (`lexicon_id`,`code`),
  CONSTRAINT `lex_source_lexicon_id_foreign` FOREIGN KEY (`lexicon_id`) REFERENCES `lex_lexicon` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(191) DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`name`)),
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `eieol_glossed_text_lesson_id_order_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_name_unique` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(191) DEFAULT NULL,
  `remember_token` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_username_unique` (`username`),
  UNIQUE KEY `user_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_permission` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `eieol_series_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permission_user_id_eieol_series_id_unique` (`user_id`,`eieol_series_id`),
  KEY `user_permission_user_id_index` (`user_id`),
  KEY `user_permission_eieol_series_id_foreign` (`eieol_series_id`),
  CONSTRAINT `user_permission_eieol_series_id_foreign` FOREIGN KEY (`eieol_series_id`) REFERENCES `eieol_series` (`id`),
  CONSTRAINT `user_permission_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

