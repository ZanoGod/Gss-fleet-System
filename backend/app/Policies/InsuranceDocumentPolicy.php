<?php

namespace App\Policies;

use App\Models\InsuranceDocument;
use App\Models\User;
use App\Policies\Concerns\RequiresLegacyAdminLink;

class InsuranceDocumentPolicy
{
    use RequiresLegacyAdminLink;

    public function view(User $user, InsuranceDocument $document): bool { return $user->exists; }
    public function update(User $user, InsuranceDocument $document): bool { return $user->exists; }
}
