<?php

namespace App\Console\Commands;

use App\Models\Carti;
use App\Models\Versete;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Class GenerateMarkdownCommand
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class GenerateMarkdownCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "generate-markdown
                            {bookId=0 : generate only a specific book}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Generate markdown files from database records";

    private $bookId = false;

    public function handle()
    {   
        $this->bookId = $this->argument('bookId');

        if ($this->bookId > 0) {
            $this->info("processing only book ID " . $this->bookId);
        }

        $bookId = $this->bookId;
        $books = Carti::when($this->bookId > 0, function ($q) use ($bookId) {
            return $q->where('c_id', $bookId);
        })
        ->orderBy('c_index', 'asc')
        ->get();

        foreach ($books as $book) {
            $this->generateBook($book);
        }

    }

    private function generateBook(Carti $book)
    {
        $this->info('Generating book ' . $book->c_nume);
        $lines = Versete::where('v_id_carte', $book->c_id)
        ->orderBy('v_id_capitol', 'asc')
        ->orderBy('v_index', 'asc')
        ->get();
        $this->line($lines->count() . ' lines');
        $chapter = 0;
        $chapterLines = [];
        foreach ($lines as $line) {
            if ($chapter != $line->v_id_capitol) {
                if (!empty($chapterLines)) {
                    $this->writeChapter($book, $chapter, $chapterLines);
                    $chapterLines = [];
                }
                $chapter = $line->v_id_capitol;
            }
            $chapterLines[] = $line;
        }
        if (!empty($chapterLines)) {
            $this->writeChapter($book, $chapter, $chapterLines);
        }
    }

    private function writeChapter(Carti $book, $chapter, $lines)
    {
        $content = "# " . $book->c_nume . " - " . $chapter ."\n\n";
        foreach ($lines as $line) {
            $content .= $line->v_index . ". " . $line->v_continut . "\n\n";
        }
        $filepath = "biblia-ortodoxa/" . $book->c_nume_scurt . "/";
        $filename = $book->c_nume_scurt . " - " . $chapter . ".md";
        Storage::disk('local')->put($filepath . $filename, $content);
        $this->line($filename);
    }


}