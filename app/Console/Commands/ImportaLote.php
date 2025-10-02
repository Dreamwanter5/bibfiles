<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Http\File as StorageFile;
use Illuminate\Support\Facades\Storage;
use App\Models\File as FileModel; 

class ImportaLote extends Command
{
    protected $signature = 'lote:importar {directory} {user_id}';
    protected $description = 'Importa arquivos PDFs em lote para upload no sistema bibfiles a partir de uma pasta';

    public function handle(){
        $directory = $this->argument('directory');
        $userId = $this->argument('user_id');

        if (!File::exists($directory)){
            $this->error("O diretório '{$directory}' não foi encontrado.");
            return;
        } else{
            $this->info("Listando os arquivos PDFs que estão na pasta $directory...\n\n");
        }
        
        $pdfFiles = collect(File::files($directory))
            ->filter(fn($file) => $file->getExtension() === 'pdf')
            ->map(fn($file) => $file->getFilename());

         if ($pdfFiles->isEmpty()){
            $this->warn("Atenção: nenhum PDF encontrado em {$directory}\n");
            return;
        }

        $this->info("Arquivos encontrados:");
        $this->line($pdfFiles->implode("\n")); 
        $this->line("\n");
        
        foreach ($pdfFiles as $fileName){
            $sourcePath = "{$directory}/{$fileName}";

            $storedPath = Storage::disk('local')->putFile('.', new StorageFile($sourcePath));

            FileModel::create([
                'original_name' => $fileName,
                'path'          => $storedPath,
                'name'          => pathinfo($fileName, PATHINFO_FILENAME),
                'user_id'       => $userId,
            ]);

            $this->info("✔ Upload realizado: {$fileName}");
        }

        $this->info("\nImportação concluída! \n{$pdfFiles->count()} arquivos adicionados.");

    }
}

