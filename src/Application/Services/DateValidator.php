<?php

declare(strict_types=1);

namespace App\Application\Services;

class DateValidator 
{
    private array $restrictedDates = [
        '08/30/2025',
        '09/06/2025',
        '09/12/2025',
        '10/04/2025',
        '10/11/2025',
        '11/08/2025',
        '11/22/2025'
    ];

    public function isDateRestricted(string $date): bool 
    {
        // Convert date to mm/dd/yyyy format for comparison
        $formattedDate = date('m/d/Y', strtotime($date));
        return in_array($formattedDate, $this->restrictedDates);
    }

    public function getRestrictedDates(): array 
    {
        return $this->restrictedDates;
    }

    public function validateDates(array $dates): ?string 
    {
        foreach ($dates as $date) {
            if ($this->isDateRestricted($date)) {
                return sprintf(
                    'Date %s is unavailable due to a home game. Please select a different date.',
                    date('m/d/Y', strtotime($date))
                );
            }
        }
        return null;
    }
}