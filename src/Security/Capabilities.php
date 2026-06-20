<?php

declare(strict_types=1);

namespace Zadora\Lms\Security;

final class Capabilities
{
    public const MANAGE_PLATFORM = 'zadora_manage_platform';
    public const MANAGE_COURSES = 'zadora_manage_courses';
    public const MANAGE_LEARNERS = 'zadora_manage_learners';
    public const MANAGE_COMPANY = 'zadora_manage_company';
    public const GRADE_ASSESSMENTS = 'zadora_grade_assessments';
    public const LEARN = 'zadora_learn';
    public const VIEW_REPORTS = 'zadora_view_reports';
    public const MANAGE_CERTIFICATES = 'zadora_manage_certificates';
    public const MANAGE_PAYMENTS = 'zadora_manage_payments';

    /** @return string[] */
    public function all(): array
    {
        return [
            self::MANAGE_PLATFORM,
            self::MANAGE_COURSES,
            self::MANAGE_LEARNERS,
            self::MANAGE_COMPANY,
            self::GRADE_ASSESSMENTS,
            self::LEARN,
            self::VIEW_REPORTS,
            self::MANAGE_CERTIFICATES,
            self::MANAGE_PAYMENTS,
        ];
    }

    public function install(): void
    {
        $administrator = get_role('administrator');

        if ($administrator) {
            foreach ($this->all() as $capability) {
                $administrator->add_cap($capability);
            }
        }

        add_role('zadora_training_provider', 'Zadora Training Provider', [
            'read' => true,
            self::MANAGE_COURSES => true,
            self::MANAGE_LEARNERS => true,
            self::GRADE_ASSESSMENTS => true,
            self::VIEW_REPORTS => true,
            self::MANAGE_CERTIFICATES => true,
        ]);

        add_role('zadora_corporate_admin', 'Zadora Corporate Admin', [
            'read' => true,
            self::MANAGE_COMPANY => true,
            self::VIEW_REPORTS => true,
            self::MANAGE_CERTIFICATES => true,
        ]);

        add_role('zadora_instructor', 'Zadora Instructor', [
            'read' => true,
            self::GRADE_ASSESSMENTS => true,
            self::VIEW_REPORTS => true,
        ]);

        add_role('zadora_learner', 'Zadora Learner', [
            'read' => true,
            self::LEARN => true,
        ]);
    }
}
