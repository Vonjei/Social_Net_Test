<?php
function resizeProfileImage($file, $width, $height) {
    list($orig_width, $orig_height) = getimagesize($file);
    $src = imagecreatefromstring(file_get_contents($file));
    $dst = imagecreatetruecolor($width, $height);
    
    // Preencher com fundo branco
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    
    // Calcular as dimensões da imagem para centralizar o recorte
    $ratio_orig = $orig_width / $orig_height;
    $ratio_new = $width / $height;

    if ($ratio_orig > $ratio_new) {
        $new_width = $height * $ratio_orig;
        $new_height = $height;
        $src_x = ($new_width - $width) / 2;
        $src_y = 0;
    } else {
        $new_width = $width;
        $new_height = $width / $ratio_orig;
        $src_x = 0;
        $src_y = ($new_height - $height) / 2;
    }
    
    // Redimensionar e cortar a imagem
    imagecopyresampled($dst, $src, 0, 0, $src_x, $src_y, $width, $height, $new_width, $new_height);
    
    return $dst;
}
?>