<?php

namespace App\Services\Auth;

enum PermissionDecision: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case None = 'none';
}
