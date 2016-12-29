<?php

namespace Nuage\Modules;

function fopen_utf8($filename, $mode) {
    $handler = @fopen($filename, $mode);
    $bom = fread($handler, 3);
    if ($bom != b"\xEF\xBB\xBF")
        rewind($handler);
    else
        echo "bom found!\n";

    return $handler;
}

class Pad extends \Nuage\Lib\Module
{
    const REQUEST = 'pad-content';
    const MODULE_NAME = 'Pad';

    private $fileName;
    private $document = '';
    private $fileHandler = null;

    public function __construct(\Nuage\Server $server) {
        parent::__construct($server);

        $this->fileName = DOCUMENTS_PATH.'untitled.json';

// 		$this->document = mb_convert_encoding(file_get_contents($this->fileName), 'UTF-8', 'HTML-ENTITIES');
// 		$this->document = file_get_contents($this->fileName);

        $this->fileHandler = fopen_utf8($this->fileName, 'a+b');
// 		stream_filter_append($this->fileHandler, 'convert.iconv.UTF-8/OLD-ENCODING');

// 		$this->document = json_decode(file_get_contents($this->fileName), true);

        if(false === ($doc = fread($this->fileHandler, filesize($this->fileName))))
            $this->stderr('Erreur lors de la lecture du document "'.$this->fileName.'".');

        if (!is_readable($this->fileName))
            $this->stderr('Warning. File is not readable');

        $this->document = json_decode($doc, true);

        if(json_last_error() !== JSON_ERROR_NONE)
            $this->stderr('Erreur lors du décodage json du document "'.$this->fileName.'" : '.json_last_error().'.');
        elseif(!$this->document) {
            $this->document = [
                'title' => '',
                'ctime' => time(),
                'atime' => time(),
                'mtime' => null,
                'content' => '',
            ];

            $this->stdout('Nouveau document "'.$this->fileName.'".');
            if(false === fwrite($this->fileHandler, json_encode($this->document)))
                $this->stderr('L\'écriture de l\'entête du nouveau document "'.$this->fileName.'" à échoué.');
            else
                $this->stdout('Écriture de l\'entête du nouveau document "'.$this->fileName.'".');
        }

/*
        $jsonIterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($this->document),
            \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($jsonIterator as $key => $val) {
            for($i = $jsonIterator->getDepth(); $i--; )
                echo '	';
            if(is_array($val))
                echo $key.' :'.PHP_EOL;
            else
                echo $key.' => '.$val.PHP_EOL;
        }
*/

        if (!is_writable($this->fileName))
            $this->stderr('Warning. File is not writeable');

        if (stream_set_write_buffer($this->fileHandler, 512) !== 0)
            $this->stderr('Could not bufferize document');
    }

    public function process($user, $input) {
        if($input->method == 'get')
            $this->put($user, [
                'content' => $this->document['content'],
            ]);
        if($input->method == 'patch') {
            $this->patchToAllOthers($user, $input->content);

            $this->document['mtime'] = time();

            foreach($input->content as $data) {
                if($data->action == 'add')
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).utf8_decode($data->content).mb_substr($this->document['content'], $data->position->start);
                elseif($data->action == 'replace')
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).utf8_decode($data->content).mb_substr($this->document['content'], $data->position->end);
                elseif($data->action == 'delete')
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start - $data->position->end).mb_substr($this->document['content'], $data->position->start);
            }

            if ($this->fileHandler)
// 				fwrite($this->fileHandler, mb_convert_encoding($this->document, 'HTML-ENTITIES', 'UTF-8'));
// 				fwrite($this->fileHandler, $this->document);

                ftruncate($this->fileHandler, 0);
                echo PHP_EOL, PHP_EOL, 'length : ', mb_strlen(json_encode($this->document));

                if(false === fwrite($this->fileHandler, json_encode($this->document)))
                    $this->stderr('L\'écriture du document "'.$this->fileName.'" à échoué.');
                else
                    $this->stdout('Écriture du document "'.$this->fileName.'".');
        }
    }

    public function shutdown() {
        $this->stdout('Shutdown : pad ');

        if ($this->fileHandler) {
            if(!fflush($this->fileHandler))
                $this->stderr('Could not fflush document on shudown');
            else
                $this->stdout(\Nuage\format('Writing buffer.', 'green'));
            fclose($this->fileHandler);
        }
    }
}
