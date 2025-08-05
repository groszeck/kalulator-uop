public const PENSION_RATE = 0.0976;
        public const DISABILITY_RATE = 0.015;
        public const SICKNESS_RATE = 0.0245;
        public const HEALTH_RATE = 0.09;
        public const HEALTH_DEDUCTIBLE_RATE = 0.0775;
        public const TAX_RATE = 0.17;
        public const COST_ALLOWANCE_UOP = 250.0;
        public const COST_ALLOWANCE_UZ_RATE = 0.20;
        public const COST_ALLOWANCE_UOD_RATE = 0.50;

        /**
         * Validates gross value.
         *
         * @param float $gross
         * @throws InvalidArgumentException
         */
        private static function validateGross(float $gross): void
        {
            if ($gross < 0 || !is_finite($gross)) {
                throw new InvalidArgumentException('Gross income must be a non-negative finite number.');
            }
        }

        /**
         * Calculates ZUS contributions.
         *
         * @param float  $gross
         * @param string $type  'uop' or 'uz'
         * @return array{pension: float, disability: float, sickness: float, total_social: float}
         * @throws InvalidArgumentException
         */
        public static function calculateZusContributions(float $gross, string $type): array
        {
            self::validateGross($gross);
            if (!in_array($type, ['uop', 'uz'], true)) {
                return ['pension' => 0.0, 'disability' => 0.0, 'sickness' => 0.0, 'total_social' => 0.0];
            }
            $pensionRate    = (float) apply_filters('kpj_pension_rate', self::PENSION_RATE);
            $disabilityRate = (float) apply_filters('kpj_disability_rate', self::DISABILITY_RATE);
            $sicknessRate   = (float) apply_filters('kpj_sickness_rate', self::SICKNESS_RATE);

            $pension    = round($gross * $pensionRate, 2);
            $disability = round($gross * $disabilityRate, 2);
            $sickness   = round($gross * $sicknessRate, 2);
            $totalSocial = round($pension + $disability + $sickness, 2);

            return [
                'pension'       => $pension,
                'disability'    => $disability,
                'sickness'      => $sickness,
                'total_social'  => $totalSocial,
            ];
        }

        /**
         * Calculates tax.
         *
         * @param float $taxBase
         * @return array{rate: float, amount: float}
         * @throws InvalidArgumentException
         */
        public static function calculateTax(float $taxBase): array
        {
            if ($taxBase < 0 || !is_finite($taxBase)) {
                throw new InvalidArgumentException('Tax base must be a non-negative finite number.');
            }
            $rate   = (float) apply_filters('kpj_tax_rate', self::TAX_RATE);
            $amount = round($taxBase * $rate, 2);
            return [
                'rate'   => $rate,
                'amount' => $amount,
            ];
        }

        /**
         * Returns cost allowance.
         *
         * @param string $type  'uop', 'uz', 'uod'
         * @param float  $gross
         * @return float
         * @throws InvalidArgumentException
         */
        public static function getCostsAllowance(string $type, float $gross): float
        {
            self::validateGross($gross);
            switch ($type) {
                case 'uop':
                    return (float) apply_filters('kpj_cost_allowance_uop', self::COST_ALLOWANCE_UOP);
                case 'uz':
                    $rate = (float) apply_filters('kpj_cost_allowance_uz_rate', self::COST_ALLOWANCE_UZ_RATE);
                    return round($gross * $rate, 2);
                case 'uod':
                    $rate = (float) apply_filters('kpj_cost_allowance_uod_rate', self::COST_ALLOWANCE_UOD_RATE);
                    return round($gross * $rate, 2);
                default:
                    return 0.0;
            }
        }

        /**
         * Computes health contributions.
         *
         * @param float $base
         * @return array{health: float, health_deductible: float}
         * @throws InvalidArgumentException
         */
        private static function computeHealth(float $base): array
        {
            if ($base < 0 || !is_finite($base)) {
                throw new InvalidArgumentException('Health base must be a non-negative finite number.');
            }
            $healthRate       = (float) apply_filters('kpj_health_rate', self::HEALTH_RATE);
            $deductibleRate   = (float) apply_filters('kpj_health_deductible_rate', self::HEALTH_DEDUCTIBLE_RATE);

            $health            = round($base * $healthRate, 2);
            $healthDeductible  = round($base * $deductibleRate, 2);

            return [
                'health'             => $health,
                'health_deductible'  => $healthDeductible,
            ];
        }

        /**
         * Calculates UoP gross-to-net.
         *
         * @param float $gross
         * @return array{
         *   gross: float,
         *   zus: array{pension: float, disability: float, sickness: float, total_social: float},
         *   health: float,
         *   health_deductible: float,
         *   costs: float,
         *   tax_base: float,
         *   tax: array{rate: float, amount: float},
         *   advance_tax: float,
         *   net: float
         * }
         * @throws InvalidArgumentException
         */
        public static function calculateUopBruttoNetto(float $gross): array
        {
            self::validateGross($gross);
            $zus          = self::calculateZusContributions($gross, 'uop');
            $socialTotal  = $zus['total_social'];
            $healthBase   = $gross - $socialTotal;
            $healthData   = self::computeHealth($healthBase);
            $costs        = self::getCostsAllowance('uop', $gross);
            $taxBase      = round($gross - $socialTotal - $costs, 2);
            $tax          = self::calculateTax($taxBase);
            $advanceTax   = round($tax['amount'] - $healthData['health_deductible'], 2);
            $net          = round($gross - $socialTotal - $healthData['health'] - $advanceTax, 2);

            return [
                'gross'             => $gross,
                'zus'               => $zus,
                'health'            => $healthData['health'],
                'health_deductible' => $healthData['health_deductible'],
                'costs'             => $costs,
                'tax_base'          => $taxBase,
                'tax'               => $tax,
                'advance_tax'       => $advanceTax,
                'net'               => $net,
            ];
        }

        /**
         * Calculates UZ gross-to-net.
         *
         * @param float $gross
         * @param bool  $includeZus
         * @return array{
         *   gross: float,
         *   zus: array{pension: float, disability: float, sickness: float, total_social: float},
         *   health: float,
         *   health_deductible: float,
         *   costs: float,
         *   tax_base: float,
         *   tax: array{rate: float, amount: float},
         *   advance_tax: float,
         *   net: float
         * }
         * @throws InvalidArgumentException
         */
        public static function calculateUzBruttoNetto(float $gross, bool $includeZus = true): array
        {
            self::validateGross($gross);
            $zus         = $includeZus ? self::calculateZusContributions($gross, 'uz') : ['pension' => 0.0, 'disability' => 0.0, 'sickness' => 0.0, 'total_social' => 0.0];
            $socialTotal = $zus['total_social'];
            $costs       = self::getCostsAllowance('uz', $gross);
            $taxBase     = round($gross - $socialTotal - $costs, 2);
            $tax         = self::calculateTax($taxBase);
            $healthData  = self::computeHealth($taxBase);
            $advanceTax  = round($tax['amount'] - $healthData['health_deductible'], 2);
            $net         = round($gross - $socialTotal - $healthData['health'] - $advanceTax, 2);

            return [
                'gross'             => $gross,
                'zus'               => $zus,
                'health'            => $healthData['health'],
                'health_deductible' => $healthData['health_deductible'],
                'costs'             => $costs,
                'tax_base'          => $taxBase,
                'tax'               => $tax,
                'advance_tax'       => $advanceTax,
                'net'               => $net,
            ];
        }

        /**
         * Calculates UoD gross-to-net.
         *
         * @param float $gross
         * @return array{
         *   gross: float,
         *   costs: float,
         *   tax_base: float,
         *   tax: array{rate: float, amount: float},
         *   net: float
         * }
         * @throws InvalidArgumentException
         */
        public static function calculateUodBruttoNetto(float $gross): array
        {
            self::validateGross($gross);
            $costs   = self::getCostsAllowance('uod', $gross);
            $taxBase = round($gross - $costs, 2);
            $tax     = self::calculateTax($taxBase);
            $net     = round($gross - $tax['amount'], 2);

            return [
                'gross'     => $gross,
                'costs'     => $costs,
                'tax_base'  => $taxBase,
                'tax'       => $tax,
                'net'       => $net,
            ];
        }
    }
}