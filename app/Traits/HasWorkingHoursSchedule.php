<?php

namespace App\Traits;

use App\Support\WorkingHoursCodec;

trait HasWorkingHoursSchedule
{
    public function getWorkingHoursScheduleAttribute(): array
    {
        return WorkingHoursCodec::decode($this->getAttribute('working_hours'));
    }
}
