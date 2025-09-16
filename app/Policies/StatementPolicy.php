<?php

namespace App\Policies;

use App\Models\Statement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the statement.
     */
    public function view($user, Statement $statement)
    {
        // Verificar se a carteira associada ao statement pertence a uma conta do usuário
        $userAccountIds = $user->accounts->pluck('id')->toArray();
        $walletAccountId = $statement->wallet->account_id;
        
        return in_array($walletAccountId, $userAccountIds);
    }
}