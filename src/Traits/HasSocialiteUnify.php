<?php

namespace Bleuren\SocialiteUnify\Traits;

use Bleuren\SocialiteUnify\Models\SocialAccount;

trait HasSocialiteUnify
{
    public function initializeHasSocialiteUnify()
    {
        $this->fillable[] = 'has_password';
        $this->mergeCasts(['has_password' => 'boolean']);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }
}
