<?php

namespace Nuage\Modules;

use function \Nuage\Core\format as format;

function fopen_utf8($filename, $mode) {
    $handler = @fopen($filename, $mode);
    $bom = fread($handler, 3);
    if ($bom != b"\xEF\xBB\xBF")
        rewind($handler);
    else
        echo 'bom found!', PHP_EOL;

    return $handler;
}

class Pad extends \Nuage\Core\Module
{
    const REQUEST = 'pad-content';
    const MODULE_NAME = 'Pad';

    private $fileName;
    private $document = '';
    private $fileHandler = null;

    public function __construct(\Nuage\Core\Server $server) {
        parent::__construct($server);

        $this->fileName = DOCUMENTS.'untitled.json';

// 		$this->document = mb_convert_encoding(file_get_contents($this->fileName), 'UTF-8', 'HTML-ENTITIES');
// 		$this->document = file_get_contents($this->fileName);

        $this->fileHandler = fopen_utf8($this->fileName, 'a+b');
// 		stream_filter_append($this->fileHandler, 'convert.iconv.UTF-8/OLD-ENCODING');

// 		$this->document = json_decode(file_get_contents($this->fileName), true);

        if(false === ($doc = fread($this->fileHandler, filesize($this->fileName))))
            $this->stderr(format('Error reading document "'.$this->fileName.'".', 'red'));

        if (!is_readable($this->fileName))
            $this->stderr(format('Warning : File "'.$this->fileName.'" is not readable', 'orange'));

        $this->document = json_decode($doc, true);

        if(json_last_error() !== JSON_ERROR_NONE)
            $this->stderr(format('Error decoding JSON of document "'.$this->fileName.'" : ('.json_last_error().') '.json_last_error_msg().'.', 'red'));
        elseif(!$this->document) {
            $this->document = [
                'title' => '',
                'ctime' => time(),
                'atime' => time(),
                'mtime' => null,
                'content' => '',
            ];

            $this->stdout('New document "'.$this->fileName.'".');
            if(false === fwrite($this->fileHandler, json_encode($this->document)))
                $this->stderr('Writing header of new document "'.$this->fileName.'" '.format('[failed]', 'red').'.');
            else
                $this->stdout('Writing header of new document "'.$this->fileName.'" '.format('[ok]', 'green').'.');
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
            $this->stderr(format('Warning. File "'.$this->fileName.'" is not writeable', 'orange'));

        if (stream_set_write_buffer($this->fileHandler, 512) !== 0)
            $this->stderr(format('Warning. Could not bufferize document "'.$this->fileName.'"', 'orange'));
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
//                     $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).utf8_decode($data->content).mb_substr($this->document['content'], $data->position->start);
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).$data->content.mb_substr($this->document['content'], $data->position->start);
                elseif($data->action == 'replace')
//                     $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).utf8_decode($data->content).mb_substr($this->document['content'], $data->position->end);
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start).$data->content.mb_substr($this->document['content'], $data->position->end);
                elseif($data->action == 'delete')
                    $this->document['content'] = mb_substr($this->document['content'], 0, $data->position->start - $data->position->end).mb_substr($this->document['content'], $data->position->start);
            }

            if ($this->fileHandler)
// 				fwrite($this->fileHandler, mb_convert_encoding($this->document, 'HTML-ENTITIES', 'UTF-8'));
// 				fwrite($this->fileHandler, $this->document);

                ftruncate($this->fileHandler, 0);
                echo PHP_EOL, 'Length of json document : ', mb_strlen(json_encode($this->document)), PHP_EOL;

                if(false === fwrite($this->fileHandler, json_encode($this->document)))
                    $this->stderr('Writing document "'.$this->fileName.'" '.format('[failed]', 'red').'.');
                else
                    $this->stdout('Writing document "'.$this->fileName.'" '.format('[ok]', 'green').'.');
        }
    }

    public function shutdown() {
        $this->stdout('Shutdown : pad ');

        if ($this->fileHandler) {
            if(!fflush($this->fileHandler))
                $this->stderr(format('Could not fflush document on shutdown', 'orange'));
            else
                $this->stdout('Writing buffer '.format('[ok]', 'green').'.');
            fclose($this->fileHandler);
        }
    }
}
