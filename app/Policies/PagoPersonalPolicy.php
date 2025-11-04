<?php

namespace App\Policies;

use App\Models\PagoPersonal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PagoPersonalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view pagos personal');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PagoPersonal $pagoPersonal): bool
    {
        return $user->can('view pagos personal');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create pagos personal');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PagoPersonal $pagoPersonal): bool
    {
        return $user->can('update pagos personal');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PagoPersonal $pagoPersonal): bool
    {
        return $user->can('delete pagos personal');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PagoPersonal $pagoPersonal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PagoPersonal $pagoPersonal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can approve the payment.
     */
    public function approve(User $user, PagoPersonal $pagoPersonal): bool
    {
        return $user->can('approve pagos personal') && 
               $user->sub_branch_id === $pagoPersonal->sub_branch_id &&
               $pagoPersonal->estado === 'pendiente';
    }

    /**
     * Determine whether the user can cancel the payment.
     */
    public function cancel(User $user, PagoPersonal $pagoPersonal): bool
    {
        return $user->can('cancel pagos personal') && 
               $user->sub_branch_id === $pagoPersonal->sub_branch_id &&
               in_array($pagoPersonal->estado, ['pendiente', 'aprobado']);
    }

    /**
     * Determine whether the user can view payments report.
     */
    public function viewReport(User $user): bool
    {
        return $user->can('view reportes pagos personal');
    }

    /**
     * Determine whether the user can process payroll.
     */
    public function processPayroll(User $user): bool
    {
        return $user->can('process payroll');
    }
}