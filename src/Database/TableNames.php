<?php

declare(strict_types=1);

namespace Zadora\Lms\Database;

final class TableNames
{
    public function __construct(private readonly string $prefix)
    {
    }

    public static function fromWordPress(): self
    {
        global $wpdb;

        return new self($wpdb->prefix);
    }

    public function courses(): string
    {
        return $this->prefix . 'zadora_courses';
    }

    public function lessons(): string
    {
        return $this->prefix . 'zadora_lessons';
    }

    public function enrollments(): string
    {
        return $this->prefix . 'zadora_enrollments';
    }

    public function progress(): string
    {
        return $this->prefix . 'zadora_progress';
    }

    public function waitlists(): string
    {
        return $this->prefix . 'zadora_waitlists';
    }

    public function assessments(): string
    {
        return $this->prefix . 'zadora_assessments';
    }

    public function submissions(): string
    {
        return $this->prefix . 'zadora_submissions';
    }

    public function certificateTemplates(): string
    {
        return $this->prefix . 'zadora_certificate_templates';
    }

    public function certificates(): string
    {
        return $this->prefix . 'zadora_certificates';
    }

    public function companies(): string
    {
        return $this->prefix . 'zadora_companies';
    }

    public function companyUsers(): string
    {
        return $this->prefix . 'zadora_company_users';
    }

    public function departments(): string
    {
        return $this->prefix . 'zadora_departments';
    }

    public function groups(): string
    {
        return $this->prefix . 'zadora_groups';
    }

    public function groupMembers(): string
    {
        return $this->prefix . 'zadora_group_members';
    }

    public function groupCourses(): string
    {
        return $this->prefix . 'zadora_group_courses';
    }

    public function announcements(): string
    {
        return $this->prefix . 'zadora_announcements';
    }

    public function notifications(): string
    {
        return $this->prefix . 'zadora_notifications';
    }

    public function aiRequests(): string
    {
        return $this->prefix . 'zadora_ai_requests';
    }

    public function orders(): string
    {
        return $this->prefix . 'zadora_orders';
    }

    public function payments(): string
    {
        return $this->prefix . 'zadora_payments';
    }
}
