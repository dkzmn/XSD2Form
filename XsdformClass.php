<?php

include './functions.php';

class XsdformClass{

    public $filename='';
    public $path='';
    public $files = [];

    public $xsd = null;

    public $all_files_namespaces = [];
    public $namespaces = [];
    public $targetNamespace = '';

    public function libxml_display_error($error, $ifxslt) {
        $return = "<br/>\n";
        if (!$ifxslt) {
            switch ($error->level) {
                case LIBXML_ERR_WARNING:
                    $return .= "<b>Warning $error->code</b>: ";
                    break;
                case LIBXML_ERR_ERROR:
                    $return .= "<b>Error $error->code</b>: ";
                    break;
                case LIBXML_ERR_FATAL:
                    $return .= "<b>Fatal Error $error->code</b>: ";
                    break;
            }
        }
        if ($ifxslt) {
            if (strpos($error->message, 'ERR ') !== false) {
                $return .= trim($error->message);
            } else {
                $return = '';
            }
        } else {
            $return .= trim($error->message);
        }
        if (!$ifxslt) {
            if ($error->file) {
                $return .= " in <b>$error->file</b>";
            }
            $return .= " on line <b>$error->line</b>\n";
        }
        return $return;
    }

    public function libxml_display_errors($filename, $title, $ifxslt = false) {
        $errors = libxml_get_errors();
        file_put_contents($filename,'<h1>'.$title.'</h1>', FILE_APPEND);
        foreach ($errors as $error) {
            file_put_contents($filename, $this -> libxml_display_error($error, $ifxslt), FILE_APPEND);
        }
        libxml_clear_errors();
    }


    public function translit(){
        foreach (glob($this -> path."*.xsd") as $file) {
            $new_file = basename($file);
            $c = (bool) preg_match('/[А-Яа-я]/', basename($file));
            if($c){
                $new_file = rus2translit(basename($file));
                rename($file, $this -> path.$new_file);
                file_put_contents($this->path . 'log.html', '<br>Обнаружены кириллические символы в имени файла <b>' .
                    basename($file) . '</b>', FILE_APPEND);
            }

            $f = file_get_contents($this -> path.$new_file);

            preg_match_all("'schemaLocation=\"(.*?)\"'si", $f, $match);
            $cyrl_sl = [];
            foreach ($match[1] as $m) {
                $c = (bool) preg_match('/[А-Яа-я]/', $m);
                if($c){
                    $cyrl_sl[] = $m;
                    $newm = rus2translit($m);
                    $f = str_replace($m, $newm, $f);
                }
            }
            if(count($cyrl_sl) > 0) {
                file_put_contents($this->path . 'log.html', '<br>В файле <b>' . basename($new_file) .
                    '</b> обнаружены кириллические символы в названиях файлов импорта ' . implode(', ', $cyrl_sl), FILE_APPEND);
                file_put_contents($this -> path.$new_file, $f);
            }

            preg_match_all("'\"urn:(.*?)\"'si", $f, $match);
            $cyrl_ns = [];
            foreach ($match[0] as $m) {
                $c = (bool) preg_match('/[А-Яа-я]/', $m);
                if($c){
                    $cyrl_ns[] = $m;
                    $newm = rus2translit($m);
                    $f = str_replace($m, $newm, $f);
                }
            }
            if(count($cyrl_ns) > 0) {
                file_put_contents($this->path . 'log.html', '<br>В файле <b>' . basename($file) .
                    '</b> обнаружены кириллические символы в названиях пространств имен ' . implode(', ', $cyrl_ns), FILE_APPEND);
                file_put_contents($this -> path.$new_file, $f);
            }
            $this -> files[] = $new_file;
        }
        $this -> filename = rus2translit($this -> filename);
    }

    public function get_namespaces_from_all_files(){
        $this -> all_files_namespaces = [];
        foreach (glob($this -> path."*.xsd") as $file) {
            $sxml = simplexml_load_file($file);
            $target_namespace = xml_attribute($sxml, 'targetNamespace');
            if(isset($this -> all_files_namespaces[$target_namespace])){
                file_put_contents($this -> path . 'log.html', '<br>Совпадение пространств имен <b>' . $target_namespace .
                    '</b> у файлов: <b>'. basename($file) .'</b> и <b>'. basename($this -> all_files_namespaces[$target_namespace]) . '</b>', FILE_APPEND);
            } else {
                $this -> all_files_namespaces[$target_namespace] = basename($file);
            }
        }
    }

    public function restore_imports($filename){
        $sxml = simplexml_load_file($this -> path.$filename);
        $namespaces = $sxml -> getDocNamespaces();
        $targetNamespace = xml_attribute($sxml, 'targetNamespace');
        $prefix='';
        foreach ($namespaces as $nskey => $nsvalue){
            if ($nsvalue == 'http://www.w3.org/2001/XMLSchema'){
                $prefix = $nskey . ':';
            }
        }
        $imports = $sxml -> xpath("//".$prefix."import");
        $all_imports = [];
        foreach ($imports as $imp) {
            $all_imports[xml_attribute($imp,'namespace')] = xml_attribute($imp,'schemaLocation');
        }

        $dom = new DOMDocument();
        $dom -> preserveWhiteSpace = false;
        $dom -> formatOutput = true;
        $dom -> loadXML($sxml -> asXML());

        foreach ($namespaces as $nskey => $nsvalue){
            if (($nskey!='') && ($nsvalue != $targetNamespace) && ($nsvalue != 'http://www.w3.org/2001/XMLSchema') && (!isset($all_imports[$nsvalue]))) {
                if (isset($this -> all_files_namespaces[$nsvalue])) {
                    $import = $dom -> createElement($prefix.'import','');
                    $import -> setAttribute('namespace',$nsvalue);
                    $import -> setAttribute('schemaLocation',$this -> all_files_namespaces[$nsvalue]);

                    $first = $dom->documentElement->firstChild;
                    $new_import = $dom->documentElement->insertBefore($import, $first);

                    $this -> restore_imports($this -> all_files_namespaces[$nsvalue]);
                    file_put_contents($this -> path . 'log.html', '<br>Для файла <b>'.$filename.'</b> добавлен импорт '.
                        $nsvalue.'(из файла '.$this -> all_files_namespaces[$nsvalue].')', FILE_APPEND);
                } else {
                    file_put_contents($this -> path . 'log.html', '<br>Не найден файл с пространоством имен <b>' .
                        $nsvalue . '</b>', FILE_APPEND);
                }
            }
        }
        $dom -> save($this -> path.$filename);
    }

    public function add_xs($filename) {
//        $sxml = simplexml_load_file($filename);
//        $namespaces = $sxml->getDocNamespaces();
        $xmlschemans = '';
        foreach ($this -> namespaces as $nskey => $nsvalue) {
            if ($nsvalue=='http://www.w3.org/2001/XMLSchema'){
                $xmlschemans = $nskey;
            }
        }
        //Если не задан префикс для стандартного namespace, то добавляем его
        if (isset($namespaces[''])&&($xmlschemans=='')) {
            $xml = new DOMDocument;
            $xml -> load($filename);
//            unset($namespaces['']);
//            $insertstring = '';
//            foreach ($namespaces as $nskey => $nsvalue) {
//                $insertstring .= 'xmlns:' . $nskey . '="' . $nsvalue . '" ';
//            }

            $xsl = new DOMDocument;
            $xsl -> load('./xsl/add_namespace_xs.xsl');

            $proc = new XSLTProcessor;
            $proc -> importStyleSheet($xsl);
            $result = $proc->transformToXML($xml);

    //        $s = strpos($result, 'xmlns:xs="http://www.w3.org/2001/XMLSchema"');
    //        $result = substr($result, 0, $s) . $insertstring . substr($result, $s);
            file_put_contents($filename, $result);
            file_put_contents($this -> path . 'log.html', '<br>В файле <b>'.$filename.
                '</b> не задан префикс стандартного пространства имен', FILE_APPEND);
            foreach ($namespaces as $nskey => $nsvalue) {
                if ($nsvalue!=='http://www.w3.org/2001/XMLSchema'){
                    $this -> add_xs($nsvalue);
                }
            }
        }
    }

    public function add_root_element($filename) {
        $xml = new DOMDocument;
        $xml -> load($this -> path . $filename);
        $xsl = new DOMDocument;
        $xsl -> load('./xsl/add_root_element.xsl');
        $proc = new XSLTProcessor;
        $proc -> importStyleSheet($xsl);
        $xml = $proc -> transformToXML($xml);
        file_put_contents($this -> path.$filename, $xml);
    }

    public function go() {
        $this -> xml -> load($this -> path . $this -> filename);
        $this -> libxml_display_errors($this -> path.'log.html', 'LOAD XML LOG');
        $xsl = new DOMDocument;
        $xsl -> load('./xsd2html2xml/xsd2html2xml.xsl');

        // Настройка преобразования
        $proc = new XSLTProcessor;
        $proc -> importStyleSheet($xsl);
        $this -> libxml_display_errors($this -> path.'log.html', 'LOAD XSL LOG');
//
        $form = $proc -> transformToXML($this -> xml);
        file_put_contents($this -> path.'form.html', $form);
        $this -> libxml_display_errors($this -> path.'log.html', 'XSLT LOG', true);
    }

    public function __construct($filename, $path){
        libxml_use_internal_errors(true);
        $this -> path = $path;
        $this -> filename = $filename;
        file_put_contents($this -> path.'log.html','<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Log file</title></head><body>');
        file_put_contents($this -> path.'log.html','<h1>PREPARE LOG</h1>', FILE_APPEND);
        $this -> translit();
        $this -> xml = new DOMDocument;
        $this -> xml -> load($this -> path . $this -> filename);

        $sxml = simplexml_import_dom($this -> xml);
        $this -> namespaces = $sxml -> getDocNamespaces();
        $this -> targetNamespace = xml_attribute($sxml, 'targetNamespace');

        $this -> get_namespaces_from_all_files();
//        $this -> restore_imports($this -> filename);
        $this -> add_root_element($this -> filename);

        $this -> go();
    }

    public function __destruct(){
        file_put_contents($this -> path.'log.html','</body></html>', FILE_APPEND);
    }


}
?>
