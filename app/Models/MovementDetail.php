<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditFields;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Support\Facades\Auth;

class MovementDetail extends Model implements AuditableContract
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, Auditable;

    protected $fillable = [
        'movement_id',
        'product_id',
        'unit_price',
        'boxes',
        'units_per_box',
        'fractions',
        'quantity_type',
        'expiry_date',
        'total_price',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    // ────────────────────────────
    // 🔗 Relaciones
    // ────────────────────────────
    public function movement() {
        return $this->belongsTo(Movement::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function kardexEntries() {
        return $this->hasMany(Kardex::class, 'movement_detail_id');
    }

    // ────────────────────────────
    // ⚙️ Eventos automáticos CORREGIDOS
    // ────────────────────────────
    protected static function booted()
    {
        static::created(function ($detail) {
            $user = Auth::user();
            if (!$user) return;
            
            $subBranchId = $user->sub_branch_id ?? null;
            if (!$subBranchId) return;
            
            // ✅ REFRESCAR EL MODELO PARA OBTENER TODOS LOS DATOS DE LA BD
            $detail->refresh();
            
            // ✅ OBTENER EL PRODUCTO PRIMERO PARA SABER SI ES FRACCIONABLE
            $product = Product::find($detail->product_id);
            if (!$product) return;
            
            // Buscar producto en la sucursal del usuario
            $subBranchProduct = SubBranchProduct::where('sub_branch_id', $subBranchId)
                ->where('product_id', $detail->product_id)
                ->first();
            
            // Si no existe, crear registro
            if (!$subBranchProduct) {
                $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
                
                $subBranchProduct = SubBranchProduct::create([
                    'sub_branch_id' => $subBranchId,
                    'product_id' => $detail->product_id,
                    'packages_in_stock' => 0,
                    'units_per_package' => $unitsPerPackage,
                    'current_stock' => 0,
                    'min_stock' => 0,
                    'max_stock' => 0,
                    'is_fractionable' => $product->is_fractionable,
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
            
            // ✅ OBTENER LAS UNIDADES POR PAQUETE DEL PRODUCTO
            $unitsPerPackage = $product->is_fractionable ? ($product->fraction_units ?? 1) : 1;
            if ($unitsPerPackage <= 0) $unitsPerPackage = 1;
            
            // ✅ CALCULAR LO QUE ENTRA (EXACTO)
            $cajasEntrantes = 0;
            $fraccionesEntrantes = 0;
            
            switch($detail->quantity_type) {
                case 'packages':
                    $cajasEntrantes = (int)($detail->boxes ?? 0);
                    $fraccionesEntrantes = 0;
                    break;
                case 'fractions':
                    // ✅ Si el producto NO es fraccionable, tratarlo como paquetes
                    if (!$product->is_fractionable) {
                        $cajasEntrantes = (int)($detail->fractions ?? 0);
                        $fraccionesEntrantes = 0;
                    } else {
                        $cajasEntrantes = 0;
                        $fraccionesEntrantes = (int)($detail->fractions ?? 0);
                    }
                    break;
                case 'both':
                    $cajasEntrantes = (int)($detail->boxes ?? 0);
                    $fraccionesEntrantes = $product->is_fractionable ? (int)($detail->fractions ?? 0) : 0;
                    break;
            }
            
            // Stock anterior
            $SAnteriorCaja = $subBranchProduct->packages_in_stock;
            $SAnteriorFraccion = $product->is_fractionable 
                ? ($subBranchProduct->current_stock % $unitsPerPackage) 
                : 0;
            
            // ✅ ACTUALIZAR STOCK CORRECTAMENTE
            if ($product->is_fractionable) {
                // Producto fraccionable: calcular con conversiones
                $unidadesEntrantes = ($cajasEntrantes * $unitsPerPackage) + $fraccionesEntrantes;
                $nuevoCurrentStock = $subBranchProduct->current_stock + $unidadesEntrantes;
                $nuevosPaquetes = intdiv($nuevoCurrentStock, $unitsPerPackage);
                $nuevasFracciones = $nuevoCurrentStock % $unitsPerPackage;
            } else {
                // Producto NO fraccionable: solo contar paquetes completos
                $nuevosPaquetes = $subBranchProduct->packages_in_stock + $cajasEntrantes;
                $nuevasFracciones = 0;
                $nuevoCurrentStock = $nuevosPaquetes; // Para no fraccionables, current_stock = paquetes
            }
            
            // ✅ Actualizar el modelo (incluyendo units_per_package)
            $subBranchProduct->current_stock = $nuevoCurrentStock;
            $subBranchProduct->packages_in_stock = $nuevosPaquetes;
            $subBranchProduct->units_per_package = $unitsPerPackage;
            $subBranchProduct->updated_by = $user->id;
            $subBranchProduct->save();
            
            // ✅ Kardex con protección adicional
            Kardex::create([
                'product_id' => $detail->product_id,
                'sub_branch_id' => $subBranchProduct->sub_branch_id,
                'movement_detail_id' => $detail->id,
                'precio_total' => $detail->total_price ?? 0, // ✅ DOBLE PROTECCIÓN
                'SAnteriorCaja' => $SAnteriorCaja,
                'SAnteriorFraccion' => $SAnteriorFraccion,
                'cantidadCaja' => $cajasEntrantes,
                'cantidadFraccion' => $fraccionesEntrantes,
                'SParcialCaja' => $nuevosPaquetes,
                'SParcialFraccion' => $nuevasFracciones,
                'movement_type' => 'entrada',
                'movement_category' => 'compra',
                'estado' => 1,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }
}