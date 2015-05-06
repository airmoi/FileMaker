<pre><?php
use airmoi\FileMaker\FileMaker;

require('../FileMaker.php');

$fm = new FileMaker('filemaker-test', '192.168.1.22' , 'filemaker', 'filemaker');
//var_dump($fm);

/* get layouts list */
echo "Testing layouts list...";
$layouts = $fm->listLayouts();
if ( sizeof ($layouts) != 2 ) {
    echo "FAIL !".PHP_EOL;
    exit;
}
echo implode(', ', $layouts) . "...SUCCESS".PHP_EOL;

/* get layout infos */
echo "Testing layout infos...";
$layout = $fm->getLayout($layouts[0]);




