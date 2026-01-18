<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\PalabraClaveProducto;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            ProductoSeeder::class,
            TiendasTableSeeder::class,
            OfertasProductoTableSeeder::class,
            CholloSeeder::class,
            UsuarioAdminSeeder::class,
            HistoricoPrecioProductoSeeder::class,
            HistoricoPreciosOfertasSeeder::class,
            ClicksTableSeeder::class,
            ComisionesCategoriaTiendaSeeder::class,
        ]);
    }
}
