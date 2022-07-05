<?php
    include './XsdformClass.php';

if(isset($_FILES)) {
    //Создаем папку преобразования
    $uid = uniqid();
    $path = './forms/'.$uid.'/';
    mkdir($path, 0777, true);
    foreach ($_FILES as $file){
        move_uploaded_file($file['tmp_name'],$path.$file['name']);
    }
    $selected_file = $_POST["SelectedFile"];
    $FC = new XsdformClass($selected_file,$path);

    $result['uid'] = $uid;
    $result['xsds'] = $FC -> files;
    print_r(json_encode($result));
}
?>
