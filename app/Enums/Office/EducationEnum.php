<?php

namespace App\Enums\Office;

enum EducationEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case HIGHSCHOOL = 'HIGH SCHOOL';
    case IntermediateEducation = 'Intermediate education';
    case ELEMENTARYGRADUATE = 'ELEMENTARY GRADUATE';
    case HIGHSCHOOLGRADUATE = 'HIGH SCHOOL GRADUATE';
    case SECONDYEARHIGHSCHOOL = '2ND YEAR HIGH SCHOOL';
    case COLLEGEUNDERGRAD = 'COLLEGE UNDERGRAD';
    case COLLEGEGRADUATE = 'COLLEGE GRADUATE';
    case HIGHSCHOOLUNDERGRADUATE = 'HIGH SCHOOL UNDERGRADUATE';
    case SECONDARY = 'SECONDARY';
    case ELEMENTARY = 'ELEMENTARY';
    case COLLEGEUNDERGRADFINANCIALMANAGEMENT = 'COLLEGE UNDERGRAD (FINANCIAL MANAGEMENT';
    case THIRDYEARHIGHSCHOOL = '3RD YEAR HIGH SCHOOL';
    case HIGHSCHOOLGRADUTE = 'HIGH SCHOOL GRADUTE';
    case HIGHSCHOOLLEVEL = 'HIGH SCHOOL LEVEL';
    case SENIORFOUR = 'SENIOR FOUR';
    case UNDERGRADHIGHSCHOOL = 'UNDER GRAD HIGHSCHOOL';
    case VOCATIONAL = 'VOCATIONAL';
    case COLLEGEUNDERGRADUATE = 'COLLEGE UNDER GRADUATE';

    public function lang()
    {
        return match ($this) {
            self::Low => __('ingaz.low'),
            self::Medium => __('ingaz.medium'),
            self::High => __('ingaz.high'),
            self::HIGHSCHOOL => __('ingaz.HIGH SCHOOL'),
            self::IntermediateEducation => __('ingaz.Intermediate education'),
            self::ELEMENTARYGRADUATE => __('ingaz.ELEMENTARY GRADUATE'),
            self::HIGHSCHOOLGRADUATE => __('ingaz.HIGH SCHOOL GRADUATE'),
            self::SECONDYEARHIGHSCHOOL => __('ingaz.2ND YEAR HIGH SCHOOL'),
            self::COLLEGEUNDERGRAD => __('ingaz.COLLEGE UNDERGRAD'),
            self::COLLEGEGRADUATE => __('ingaz.COLLEGE GRADUATE'),
            self::HIGHSCHOOLUNDERGRADUATE => __('ingaz.HIGH SCHOOL UNDERGRADUATE'),
            self::SECONDARY => __('ingaz.SECONDARY'),
            self::ELEMENTARY => __('ingaz.ELEMENTARY'),
            self::COLLEGEUNDERGRADFINANCIALMANAGEMENT => __('ingaz.COLLEGE UNDERGRAD (FINANCIAL MANAGEMENT'),
            self::THIRDYEARHIGHSCHOOL => __('ingaz.3RD YEAR HIGH SCHOOL'),
            self::HIGHSCHOOLGRADUTE => __('ingaz.HIGH SCHOOL GRADUTE'),
            self::HIGHSCHOOLLEVEL => __('ingaz.HIGH SCHOOL LEVEL'),
            self::SENIORFOUR => __('ingaz.SENIOR FOUR'),
            self::UNDERGRADHIGHSCHOOL => __('ingaz.UNDER GRAD HIGHSCHOOL'),
            self::VOCATIONAL => __('ingaz.VOCATIONAL'),
            self::COLLEGEUNDERGRADUATE => __('ingaz.COLLEGE UNDER GRADUATE'),
        };
    }
}
