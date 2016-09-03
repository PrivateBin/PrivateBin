<?php
            $RandomCompat_basedir = ini_get('open_basedir');
            if (!empty($RandomCompat_basedir)) {
                $RandomCompat_open_basedir = explode(
                    PATH_SEPARATOR,
                    strtolower($RandomCompat_basedir)
                );
                $RandomCompatUrandom = (array() !== array_intersect(
                    array('/dev', '/dev/', '/dev/urandom'),
                    $RandomCompat_open_basedir
                ));
                $RandomCompat_open_basedir = null;
            }

           var_dump($RandomCompat_basedir);
           var_dump($RandomCompat_open_basedir);
?>
