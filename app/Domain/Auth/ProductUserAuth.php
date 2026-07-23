<?php

namespace App\Domain\Auth;

use App\Domain\Support\RowMapper;
use App\Infrastructure\ProductDb\ProductQuery;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Mirrors Delphi TUser.logins / load / pravo matrix sync into local app users.
 * Delphi: select user_id from users where active=1 and login=? and password=? (plain).
 */
class ProductUserAuth
{
    public function __construct(
        private readonly ProductQuery $query,
    ) {}

    /**
     * Attempt product DB login; upsert local User + permissions.
     */
    public function attempt(string $login, string $password): ?User
    {
        try {
            $row = $this->query->selectOne(
                'SELECT user_id, `name`, email, login, `password`, `active`, podr_id, isAdm,
                        podrazd_fn_get_name(podr_id) AS podr_name
                 FROM users
                 WHERE `active` = 1 AND login = ? AND `password` = ?
                 ORDER BY user_id DESC
                 LIMIT 1',
                [$login, $password]
            );
        } catch (Throwable) {
            try {
                $row = $this->query->selectOne(
                    'SELECT user_id, `name`, email, login, `password`, `active`, podr_id, isAdm
                     FROM users
                     WHERE `active` = 1 AND login = ? AND `password` = ?
                     ORDER BY user_id DESC
                     LIMIT 1',
                    [$login, $password]
                );
            } catch (Throwable) {
                return null;
            }
        }

        if (! $row) {
            return null;
        }

        $productUserId = RowMapper::int($row, 'user_id');
        // Delphi: super users 1/5/6 OR users.isAdm=1 (web admin is product user_id=100)
        $isSuper = in_array($productUserId, [1, 5, 6], true)
            || RowMapper::bool($row, 'isAdm')
            || RowMapper::int($row, 'isAdm') === 1;

        $user = User::query()->updateOrCreate(
            ['login' => RowMapper::str($row, 'login')],
            [
                'name' => RowMapper::str($row, 'name') ?: RowMapper::str($row, 'login'),
                'email' => RowMapper::str($row, 'email') ?: null,
                'password' => Hash::make($password),
                'product_user_id' => $productUserId,
                'podr_id' => RowMapper::int($row, 'podr_id'),
                'podr_name' => RowMapper::str($row, 'podr_name'),
                'is_active' => true,
                'is_admin' => $isSuper,
            ]
        );

        $this->syncPravo($user, $productUserId, $isSuper);

        return $user;
    }

    private function syncPravo(User $user, int $productUserId, bool $isSuper): void
    {
        if ($isSuper) {
            return;
        }

        try {
            $rows = $this->query->select(
                'SELECT pravo_id, podr_id FROM users_pravo WHERE user_id = ?',
                [$productUserId]
            );
        } catch (Throwable) {
            return;
        }

        UserPermission::query()->where('user_id', $user->id)->delete();

        foreach ($rows as $r) {
            UserPermission::query()->create([
                'user_id' => $user->id,
                'pravo_id' => RowMapper::int($r, 'pravo_id'),
                'podr_id' => RowMapper::int($r, 'podr_id', -1),
            ]);
        }
    }
}
