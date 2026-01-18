<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Aviso;

class TestAvisoInterno extends Command
{
    protected $signature = 'test:aviso-interno {--update-existing}';
    protected $description = 'Test the creation of internal avisos';

    public function handle()
    {
        if ($this->option('update-existing')) {
            $this->updateExistingInternalAvisos();
            return 0;
        }

        $this->info('Testing internal aviso creation...');

        try {
            // Verificar conexión a la base de datos
            \DB::connection()->getPdo();
            $this->info('✅ Database connection OK');

            // Crear aviso interno de prueba
            $aviso = Aviso::create([
                'texto_aviso' => 'Test interno desde comando',
                'fecha_aviso' => now()->addDay(),
                'user_id' => 1,
                'avisoable_type' => null,
                'avisoable_id' => null,
                'oculto' => false
            ]);

            $this->info('✅ Aviso interno creado correctamente');
            $this->info('ID: ' . $aviso->id);
            $this->info('Texto: ' . $aviso->texto_aviso);
            $this->info('Elemento: ' . $aviso->elemento_nombre);
            $this->info('Tipo: ' . $aviso->tipo_elemento);

            // Eliminar el aviso de prueba
            $aviso->delete();
            $this->info('✅ Aviso de prueba eliminado');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function updateExistingInternalAvisos()
    {
        $this->info('Updating existing internal avisos...');
        
        // Actualizar avisos que tienen avisoable_id = 999999 a NULL
        $updated = Aviso::where('avisoable_type', null)
            ->where('avisoable_id', 999999)
            ->update(['avisoable_id' => null]);
            
        $this->info("✅ Updated {$updated} internal avisos");
    }
}
