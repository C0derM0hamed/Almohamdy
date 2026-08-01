<?php

namespace App\Support\Training;

final class TrainingPermissions
{
    public const MANAGEMENT = 'view training_confirmation';

    public const COORDINATION = 'view training_confirmation_coordinator';

    public const COORDINATION_CREATE = 'create training_confirmation_coordinator';

    public const COORDINATION_EDIT = 'edit training_confirmation_coordinator';

    public const COORDINATION_DELETE = 'delete training_confirmation_coordinator';
}
