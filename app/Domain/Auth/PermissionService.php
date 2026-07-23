<?php

namespace App\Domain\Auth;

use App\Models\User;

class PermissionService
{
    public function check(User $user, int $pravoId, ?int $podrId = -1): bool
    {
        return $user->hasPravo($pravoId, $podrId);
    }

    /**
     * Hierarchical nav for AppLayout (Delphi Main PageControl1 + warehouse children).
     */
    public function modulesFor(User $user): array
    {
        return $this->filterTree(config('product.nav', []), $user);
    }

    /**
     * Delphi TMainMenu: Справочники / Инструменты / Отчёты.
     */
    public function topMenuFor(User $user): array
    {
        return $this->filterTree(config('product.top_menu', []), $user);
    }

    /**
     * @param  list<array>  $items
     * @return list<array>
     */
    private function filterTree(array $items, User $user): array
    {
        return collect($items)
            ->filter(function (array $m) use ($user) {
                if (! empty($m['pravo']) && ! $user->hasPravo((int) $m['pravo'], -1)) {
                    return false;
                }

                return true;
            })
            ->map(function (array $m) use ($user) {
                if (! empty($m['children'])) {
                    $m['children'] = $this->filterTree($m['children'], $user);
                    // Drop empty parent groups that only had pravo-gated children
                    if ($m['children'] === [] && empty($m['href'])) {
                        return null;
                    }
                }

                return $m;
            })
            ->filter()
            ->values()
            ->all();
    }
}
