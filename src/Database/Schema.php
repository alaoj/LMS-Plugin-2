<?php

declare(strict_types=1);

namespace Zadora\Lms\Database;

final class Schema
{
    public function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables = TableNames::fromWordPress();
        $charset = $wpdb->get_charset_collate();

        dbDelta($this->coursesSql($tables->courses(), $charset));
        dbDelta($this->lessonsSql($tables->lessons(), $charset));
        dbDelta($this->enrollmentsSql($tables->enrollments(), $charset));
        dbDelta($this->progressSql($tables->progress(), $charset));
        dbDelta($this->waitlistsSql($tables->waitlists(), $charset));
        dbDelta($this->assessmentsSql($tables->assessments(), $charset));
        dbDelta($this->submissionsSql($tables->submissions(), $charset));
        dbDelta($this->certificateTemplatesSql($tables->certificateTemplates(), $charset));
        dbDelta($this->certificatesSql($tables->certificates(), $charset));
        dbDelta($this->companiesSql($tables->companies(), $charset));
        dbDelta($this->departmentsSql($tables->departments(), $charset));
        dbDelta($this->companyUsersSql($tables->companyUsers(), $charset));
        dbDelta($this->notificationsSql($tables->notifications(), $charset));
        dbDelta($this->ordersSql($tables->orders(), $charset));
        dbDelta($this->paymentsSql($tables->payments(), $charset));
    }

    private function coursesSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            author_id bigint(20) unsigned NOT NULL,
            company_id bigint(20) unsigned NULL,
            title varchar(190) NOT NULL,
            slug varchar(190) NOT NULL,
            summary text NULL,
            description longtext NULL,
            status varchar(40) NOT NULL DEFAULT 'draft',
            visibility varchar(40) NOT NULL DEFAULT 'public',
            price_amount decimal(12,2) NULL,
            currency char(3) NULL,
            certificate_template_id bigint(20) unsigned NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY author_id (author_id),
            KEY company_id (company_id),
            KEY status (status),
            KEY visibility (visibility)
        ) {$charset};";
    }

    private function lessonsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            title varchar(190) NOT NULL,
            slug varchar(190) NOT NULL,
            content longtext NULL,
            sort_order int unsigned NOT NULL DEFAULT 0,
            duration_minutes int unsigned NULL,
            is_preview tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY course_sort (course_id, sort_order)
        ) {$charset};";
    }

    private function enrollmentsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            company_id bigint(20) unsigned NULL,
            status varchar(40) NOT NULL DEFAULT 'active',
            source varchar(40) NOT NULL DEFAULT 'manual',
            started_at datetime NULL,
            completed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY course_user (course_id, user_id),
            KEY user_id (user_id),
            KEY company_id (company_id),
            KEY status (status),
            KEY completed_at (completed_at)
        ) {$charset};";
    }

    private function progressSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            enrollment_id bigint(20) unsigned NOT NULL,
            lesson_id bigint(20) unsigned NOT NULL,
            status varchar(40) NOT NULL DEFAULT 'not_started',
            progress_percent decimal(5,2) NOT NULL DEFAULT 0.00,
            completed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY enrollment_lesson (enrollment_id, lesson_id),
            KEY lesson_id (lesson_id),
            KEY status (status)
        ) {$charset};";
    }

    private function waitlistsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(40) NOT NULL DEFAULT 'joined',
            joined_at datetime NOT NULL,
            notified_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY course_user (course_id, user_id),
            KEY course_id (course_id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset};";
    }

    private function assessmentsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            course_id bigint(20) unsigned NOT NULL,
            title varchar(190) NOT NULL,
            type varchar(40) NOT NULL DEFAULT 'quiz',
            settings_json longtext NOT NULL,
            passing_score decimal(5,2) NOT NULL DEFAULT 70.00,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY course_id (course_id),
            KEY type (type)
        ) {$charset};";
    }

    private function submissionsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            assessment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(40) NOT NULL DEFAULT 'submitted',
            score decimal(5,2) NULL,
            answers_json longtext NOT NULL,
            feedback text NULL,
            graded_by bigint(20) unsigned NULL,
            graded_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY assessment_id (assessment_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY graded_by (graded_by)
        ) {$charset};";
    }

    private function certificateTemplatesSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            owner_id bigint(20) unsigned NOT NULL,
            company_id bigint(20) unsigned NULL,
            name varchar(190) NOT NULL,
            layout_json longtext NOT NULL,
            background_asset_id bigint(20) unsigned NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY owner_id (owner_id),
            KEY company_id (company_id),
            KEY is_default (is_default)
        ) {$charset};";
    }

    private function certificatesSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            certificate_number varchar(64) NOT NULL,
            verification_hash varchar(96) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NOT NULL,
            template_id bigint(20) unsigned NOT NULL,
            display_name varchar(190) NOT NULL,
            snapshot_json longtext NOT NULL,
            pdf_url text NULL,
            issued_at datetime NOT NULL,
            expires_at datetime NULL,
            revoked_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY certificate_number (certificate_number),
            UNIQUE KEY verification_hash (verification_hash),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY issued_at (issued_at),
            KEY expires_at (expires_at)
        ) {$charset};";
    }

    private function companiesSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            slug varchar(190) NOT NULL,
            logo_url text NULL,
            status varchar(40) NOT NULL DEFAULT 'active',
            primary_admin_id bigint(20) unsigned NULL,
            seat_limit int unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY primary_admin_id (primary_admin_id)
        ) {$charset};";
    }

    private function departmentsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            company_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned NULL,
            name varchar(190) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY company_id (company_id),
            KEY parent_id (parent_id)
        ) {$charset};";
    }

    private function companyUsersSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            company_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            department_id bigint(20) unsigned NULL,
            role varchar(40) NOT NULL DEFAULT 'employee',
            status varchar(40) NOT NULL DEFAULT 'active',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY company_user (company_id, user_id),
            KEY user_id (user_id),
            KEY department_id (department_id),
            KEY role (role),
            KEY status (status)
        ) {$charset};";
    }

    private function notificationsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type varchar(80) NOT NULL,
            title varchar(190) NOT NULL,
            body text NULL,
            data_json longtext NULL,
            read_at datetime NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_read (user_id, read_at),
            KEY type (type),
            KEY created_at (created_at)
        ) {$charset};";
    }

    private function ordersSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            course_id bigint(20) unsigned NULL,
            company_id bigint(20) unsigned NULL,
            status varchar(40) NOT NULL DEFAULT 'pending',
            amount decimal(12,2) NOT NULL,
            currency char(3) NOT NULL,
            gateway varchar(40) NOT NULL,
            gateway_reference varchar(190) NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY gateway_reference (gateway_reference),
            KEY user_id (user_id),
            KEY course_id (course_id),
            KEY company_id (company_id),
            KEY status (status)
        ) {$charset};";
    }

    private function paymentsSql(string $table, string $charset): string
    {
        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            gateway varchar(40) NOT NULL,
            gateway_reference varchar(190) NOT NULL,
            status varchar(40) NOT NULL,
            amount decimal(12,2) NOT NULL,
            currency char(3) NOT NULL,
            payload_json longtext NOT NULL,
            paid_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY gateway_reference (gateway_reference),
            KEY order_id (order_id),
            KEY status (status)
        ) {$charset};";
    }
}
