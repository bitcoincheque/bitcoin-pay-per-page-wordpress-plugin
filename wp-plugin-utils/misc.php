<?php


function rotateMatrix90( $matrix )
{
    $x_size = count($matrix);
    $y_size = count($matrix[0]);
    $matrix90 = array();
    for($y = 0; $y < $y_size; $y++)
    {
        $row = array();
        for ($x = 0; $x < $x_size; $x++)
        {
            $arr = $matrix[$x];
            $text = $arr[$y];
            $row[] = $text;
        }
        $matrix90[] = $row;
    }
    return $matrix90;
}
