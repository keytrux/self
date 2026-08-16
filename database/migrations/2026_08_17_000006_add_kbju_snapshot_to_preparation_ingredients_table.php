<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preparation_ingredients', function (Blueprint $table) {
            $table->decimal('calories', 10, 2)->default(0)->after('unit');
            $table->decimal('protein', 10, 2)->default(0)->after('calories');
            $table->decimal('fat', 10, 2)->default(0)->after('protein');
            $table->decimal('carbs', 10, 2)->default(0)->after('fat');
        });

        // Backfill: исторический snapshot из текущих значений продуктов.
        foreach (DB::table('preparation_ingredients')->select('id', 'product_id')->get() as $row) {
            $product = DB::table('products')->find($row->product_id);
            if ($product) {
                DB::table('preparation_ingredients')
                    ->where('id', $row->id)
                    ->update([
                        'calories' => $product->calories,
                        'protein' => $product->protein,
                        'fat' => $product->fat,
                        'carbs' => $product->carbs,
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('preparation_ingredients', function (Blueprint $table) {
            $table->dropColumn(['calories', 'protein', 'fat', 'carbs']);
        });
    }
};
