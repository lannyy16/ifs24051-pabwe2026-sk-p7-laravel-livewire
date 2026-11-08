<?php

namespace App\Features\Stock\Policies;

use App\Features\Stock\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function view(User $user, Stock $stock): bool
    {
        return $user->isMember()
            && (int) $stock->user_id === (int) $user->getKey();
    }

    public function update(User $user, Stock $stock): bool
    {
        return $this->view($user, $stock);
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $this->view($user, $stock);
    }
}
