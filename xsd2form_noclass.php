<?php


function rus2translit($string)
{
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v',
        'г' => 'g', 'д' => 'd', 'е' => 'e',
        'ё' => 'e', 'ж' => 'zh', 'з' => 'z',
        'и' => 'i', 'й' => 'y', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r',
        'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
        'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

        'А' => 'A', 'Б' => 'B', 'В' => 'V',
        'Г' => 'G', 'Д' => 'D', 'Е' => 'E',
        'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
        'И' => 'I', 'Й' => 'Y', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R',
        'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch',
        'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
    );
    return strtr($string, $converter);
}

function libxml_display_error($error, $ifxslt) {
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

function libxml_display_errors($filename, $title, $ifxslt = false) {
    $errors = libxml_get_errors();
    file_put_contents($filename,'<h1>'.$title.'</h1>', FILE_APPEND);
    foreach ($errors as $error) {
        file_put_contents($filename, libxml_display_error($error, $ifxslt), FILE_APPEND);
    }
    libxml_clear_errors();
}

function xml_attribute($object, $attribute) {
    if(isset($object[$attribute]))
        return (string) $object[$attribute];
}

//Во всех названиях namespase заменяем кириллицу на латиницу
function translit_namespace($filename){
    $f = file_get_contents($filename);
    preg_match_all("'\"urn:(.*?)\"'si", $f, $match);
    foreach($match[0] as $m) {
        $newm = rus2translit($m);
        $f = str_replace($m, $newm, $f);
    }
    file_put_contents($filename, $f);
}

function add_xs($filename) {
    $sxml = simplexml_load_file($filename);
    $namespaces = $sxml->getDocNamespaces();
    $xmlscnemans = '';
    foreach ($namespaces as $nskey => $nsvalue) {
        if ($nsvalue=='http://www.w3.org/2001/XMLSchema'){
            $xmlscnemans = $nskey;
        }
    }
    //Если не задан префикс для стандартного namespace, то добавляем его
    if (isset($namespaces[''])&&($xmlscnemans=='')) {
        $xml = new DOMDocument;
        $xml->load($filename);
        unset($namespaces['']);
        $insertstring = '';
        foreach ($namespaces as $nskey => $nsvalue) {
            $insertstring .= 'xmlns:' . $nskey . '="' . $nsvalue . '" ';
        }

        $xsl = new DOMDocument;
        $xsl->load('./xsl/add_namespace_xs.xsl');
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xsl);
        $result = $proc->transformToXML($xml);

//        $s = strpos($result, 'xmlns:xs="http://www.w3.org/2001/XMLSchema"');
//        $result = substr($result, 0, $s) . $insertstring . substr($result, $s);
        file_put_contents($filename, $result);
    }
}

function add_root_element($filename) {
    $xml = new DOMDocument;
    $xml->load($filename);
    $xsl = new DOMDocument;
    $xsl->load('./xsl/add_root_element.xsl');
    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);
    $result = $proc->transformToXML($xml);
    file_put_contents($filename, $result);
}

libxml_use_internal_errors(true);
$all_namespaces = [];
$xsd_files = [];

if(isset($_FILES)) {
    //Создаем папку преобразования
    $uid = uniqid();
    $path = './forms/'.$uid.'/';
    mkdir($path, 0777, true);
    foreach ($_FILES as $file){
        //Перемещаем файл в папку преобразования
        $tfilename = rus2translit($file['name']);
        move_uploaded_file($file['tmp_name'],$path.$tfilename);

        translit_namespace($path.$tfilename);

        //Сохраняем namespace - имя файла
        $sxml = simplexml_load_file($path.$tfilename);
        $all_namespaces[xml_attribute($sxml, 'targetNamespace')] = $tfilename;
        $xsd_files[] = $tfilename;
    }

    file_put_contents($path.'log.html','<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Log file</title></head><body>');

    $selected_file = rus2translit($_POST["SelectedFile"]);

    $xml = new DOMDocument;
    $xml->load($path.$selected_file);

    $sxml = simplexml_import_dom($xml);
    $namespaces = $sxml->getDocNamespaces();
    $targetNamespace = xml_attribute($sxml, 'targetNamespace');

    foreach ($namespaces as $nskey=>$nsvalue){
        if ($nskey!='') {
            if (($nsvalue != $targetNamespace)&&($nsvalue != 'http://www.w3.org/2001/XMLSchema')) {
                if (isset($all_namespaces[$nsvalue])) {
                    $import = $xml->createElement('xs:import');
                    $import->setAttribute('namespace', $nsvalue);
                    $import->setAttribute('schemaLocation', $all_namespaces[$nsvalue]);
                    $first = $xml->documentElement->firstChild;
                    $new_import = $xml->documentElement->insertBefore($import, $first);
                    add_xs($path.$all_namespaces[$nsvalue]);
                } else {
                    file_put_contents($path . 'log.html', '<br><b>Не найден файл с пространоством имен ' . $nsvalue . '</b>', FILE_APPEND);
                }
            }
        }
    }
    $xml->save($path.$selected_file);
    add_root_element($path.$selected_file);
//    add_xs($path.$selected_file);

    libxml_display_errors($path.'log.html', 'LOAD XML LOG');

    $xml = new DOMDocument;
    $xml->load($path.$selected_file);

    $xsl = new DOMDocument;
    $xsl->load('./xsd2html2xml/xsd2html2xml.xsl');

    // Настройка преобразования
    $proc = new XSLTProcessor;
    $proc->importStyleSheet($xsl);
    libxml_display_errors($path.'log.html', 'LOAD XSL LOG');

    $form = $proc->transformToXML($xml);
    file_put_contents($path.'form.html', $form);
    libxml_display_errors($path.'log.html', 'XSLT LOG', true);

    file_put_contents($path.'log.html','</body></html>', FILE_APPEND);

    $result['uid'] = $uid;
    $result['xsds'] = $xsd_files;

    print_r(json_encode($result));
}
?>
