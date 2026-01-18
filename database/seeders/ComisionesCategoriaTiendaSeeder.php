<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tienda;
use App\Models\Categoria;
use App\Models\ComisionCategoriaTienda;

class ComisionesCategoriaTiendaSeeder extends Seeder
{
    public function run(): void
    {
        $tiendas = Tienda::all();
        $categorias = Categoria::all();

        foreach ($tiendas as $tienda) {
            foreach ($categorias as $categoria) {
                ComisionCategoriaTienda::create([
                    'tienda_id' => $tienda->id,
                    'categoria_id' => $categoria->id,
                    'comision' => 10.00, // Valor por defecto
                ]);
            }
        }
    }
}
