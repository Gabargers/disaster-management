<?php

namespace App\Enums;

enum FamilyStatus: string
{
    case TCISS_VERIFIED = 'TCISS_VERIFIED';
    case DAFAC_INTAKE_COMPLETED = 'DAFAC_INTAKE_COMPLETED';
    case DUPLICATE_CHECKED = 'DUPLICATE_CHECKED';
    case VALIDATED = 'VALIDATED';
    case PAYROLL_READY = 'PAYROLL_READY';
    case PAYOUT_SCHEDULED = 'PAYOUT_SCHEDULED';
    case ASSISTANCE_RELEASED = 'ASSISTANCE_RELEASED';
    case REQUIREMENTS_COMPLETED = 'REQUIREMENTS_COMPLETED';
    case NEEDS_REVIEW = 'NEEDS_REVIEW';
    case NEEDS_CORRECTION = 'NEEDS_CORRECTION';
    case DUPLICATE = 'DUPLICATE';
    case REJECTED = 'REJECTED';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNextStatuses(), true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::TCISS_VERIFIED => [self::DAFAC_INTAKE_COMPLETED, self::NEEDS_REVIEW],
            self::DAFAC_INTAKE_COMPLETED => [self::DUPLICATE_CHECKED, self::NEEDS_CORRECTION, self::DUPLICATE],
            self::DUPLICATE_CHECKED => [self::VALIDATED, self::DUPLICATE, self::NEEDS_CORRECTION],
            self::VALIDATED => [self::PAYROLL_READY, self::REJECTED],
            self::PAYROLL_READY => [self::PAYOUT_SCHEDULED],
            self::PAYOUT_SCHEDULED => [self::ASSISTANCE_RELEASED],
            self::ASSISTANCE_RELEASED => [self::REQUIREMENTS_COMPLETED],
            self::NEEDS_REVIEW => [self::TCISS_VERIFIED, self::REJECTED],
            self::NEEDS_CORRECTION => [self::DAFAC_INTAKE_COMPLETED, self::VALIDATED, self::REJECTED],
            self::DUPLICATE, self::REJECTED, self::REQUIREMENTS_COMPLETED => [],
        };
    }
}
