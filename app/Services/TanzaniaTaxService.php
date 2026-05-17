<?php

namespace App\Services;

/**
 * Tanzania Statutory Deductions Calculator
 * Based on TRA regulations and Tanzania tax laws (2024/2025 rates).
 *
 * PAYE  — Pay As You Earn (Income Tax Act Cap.332) - employee deduction
 * NSSF  — National Social Security Fund Act 2018    - employee 10% + employer 10%
 * SDL   — Skills and Development Levy               - employer 4.5% of gross
 * WCF   — Workers Compensation Fund                 - employer 0.5% of gross
 */
class TanzaniaTaxService
{
    /**
     * Calculate all statutory deductions for a given gross monthly income.
     *
     * @param  float  $grossMonthly  Basic salary + all allowances
     * @return array
     */
    public static function calculate(float $grossMonthly): array
    {
        $grossMonthly = max(0, round($grossMonthly, 2));

        $paye         = self::calculatePAYE($grossMonthly);
        $nssfEmployee = self::calculateNSSFEmployee($grossMonthly);
        $nssfEmployer = self::calculateNSSFEmployer($grossMonthly);
        $sdl          = self::calculateSDL($grossMonthly);
        $wcf          = self::calculateWCF($grossMonthly);

        // Total employee deductions (reduce take-home pay)
        $totalEmployeeDeductions = round($paye + $nssfEmployee, 2);

        // Total employer costs (not from employee's salary)
        $totalEmployerContributions = round($nssfEmployer + $sdl + $wcf, 2);

        return [
            // Employee deductions
            'paye'                       => $paye,
            'nssf_employee'              => $nssfEmployee,
            'total_employee_deductions'  => $totalEmployeeDeductions,

            // Employer contributions (shown on slip but not deducted from net pay)
            'nssf_employer'              => $nssfEmployer,
            'sdl'                        => $sdl,
            'wcf'                        => $wcf,
            'total_employer_contributions' => $totalEmployerContributions,

            // Net pay after statutory employee deductions
            'gross_income'               => $grossMonthly,
            'net_pay'                    => round(max(0, $grossMonthly - $totalEmployeeDeductions), 2),
        ];
    }

    /**
     * PAYE — Monthly progressive tax brackets (TRA 2024/2025).
     *
     * Monthly income brackets (TZS):
     *   0         – 270,000      → 0%
     *   270,001   – 520,000      → 8%   on amount over 270,000
     *   520,001   – 760,000      → 20,000 + 20% on amount over 520,000
     *   760,001   – 1,000,000   → 68,000 + 25% on amount over 760,000
     *   1,000,001 and above      → 128,000 + 30% on amount over 1,000,000
     */
    public static function calculatePAYE(float $grossMonthly): float
    {
        if ($grossMonthly <= 270000) {
            return 0.0;
        } elseif ($grossMonthly <= 520000) {
            return round(($grossMonthly - 270000) * 0.08, 2);
        } elseif ($grossMonthly <= 760000) {
            return round(20000 + ($grossMonthly - 520000) * 0.20, 2);
        } elseif ($grossMonthly <= 1000000) {
            return round(68000 + ($grossMonthly - 760000) * 0.25, 2);
        } else {
            return round(128000 + ($grossMonthly - 1000000) * 0.30, 2);
        }
    }

    /**
     * NSSF Employee contribution — 10% of gross (NSSF Act 2018).
     * No cap under the new Act.
     */
    public static function calculateNSSFEmployee(float $grossMonthly): float
    {
        return round($grossMonthly * 0.10, 2);
    }

    /**
     * NSSF Employer contribution — 10% of gross (NSSF Act 2018).
     * Shown on payslip as employer cost but not deducted from net pay.
     */
    public static function calculateNSSFEmployer(float $grossMonthly): float
    {
        return round($grossMonthly * 0.10, 2);
    }

    /**
     * SDL — Skills and Development Levy.
     * 4.5% of gross payroll — employer liability only.
     * Shown on payslip for transparency; not deducted from employee net pay.
     */
    public static function calculateSDL(float $grossMonthly): float
    {
        return round($grossMonthly * 0.045, 2);
    }

    /**
     * WCF — Workers Compensation Fund.
     * 0.5% of gross payroll — employer liability only.
     * Shown on payslip for transparency; not deducted from employee net pay.
     */
    public static function calculateWCF(float $grossMonthly): float
    {
        return round($grossMonthly * 0.005, 2);
    }

    /**
     * Return a breakdown array suitable for storing in deductions_breakdown JSON.
     * Only employee deductions (PAYE + NSSF employee) reduce net pay.
     */
    public static function deductionsBreakdown(float $grossMonthly): array
    {
        $taxes = self::calculate($grossMonthly);
        $rows  = [];

        if ($taxes['paye'] > 0) {
            $rows[] = ['name' => 'PAYE', 'amount' => $taxes['paye'], 'type' => 'statutory', 'party' => 'employee'];
        }
        if ($taxes['nssf_employee'] > 0) {
            $rows[] = ['name' => 'NSSF (Employee 10%)', 'amount' => $taxes['nssf_employee'], 'type' => 'statutory', 'party' => 'employee'];
        }

        return $rows;
    }

    /**
     * Return employer contributions breakdown (shown on slip, not deducted from pay).
     */
    public static function employerContributionsBreakdown(float $grossMonthly): array
    {
        $taxes = self::calculate($grossMonthly);
        return [
            ['name' => 'NSSF (Employer 10%)', 'amount' => $taxes['nssf_employer']],
            ['name' => 'SDL (4.5%)',           'amount' => $taxes['sdl']],
            ['name' => 'WCF (0.5%)',           'amount' => $taxes['wcf']],
        ];
    }
}
