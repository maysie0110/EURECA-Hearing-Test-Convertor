<?php
// get the patient that has data of the left measure for all needed frequency, put it in a csv file
require "./config/config.php";

$list = array(500, 1000, 2000, 3000, 4000, 6000);
$weight = array(500 => 0, 1000 => 0, 2000 => 0, 3000 => 0, 4000 => 0, 6000 => 0);

$smallest = 10000;
$patients = array();
if (($handle = fopen('validpatients2.csv', 'r')) !== false) {
    // Check the resource is valid
    $count = 0;
    $patients[$count]=array();
    while (($data = fgetcsv($handle, 1000, ",")) !== false) { // Check opening the file is OK!

        for ($i = 0; $i < count($data); $i++) { // Loop over the data using $i as index pointer
            // echo $i;
            if($i<=5){
                $patients[$count][$list[$i]] = $data[$i];
            }
            else if($i==6){                
                $patients[$count]["word"]= $data[$i];
                // echo $data[$i]." ".$patients[$count]["word"]."<br>";
            }
          
        }
        $count++;
    }
    fclose($handle);
}
$count=0;
for ($w1=1; $w1 < 10; $w1++) {
    $weight[500]=$w1;
    for ($w2=1; $w2 < 10; $w2++) {
        $weight[1000]=$w2;
        $count++;
        for ($w3=1; $w3 < 10; $w3++) {
            $weight[2000]=$w3;
            for ($w4=1; $w4 < 15; $w4++) {
                $weight[3000]=$w4;
                for ($w5=1; $w5 < 15; $w5++) {
                    $weight[4000]=$w5;
                    for ($w6=1; $w6 < 15; $w6++) {
                        $weight[6000]=$w6;
                        $totdiff = 0;
                        foreach ($patients as $patient) {
                            $total=0;
                            $f1 = 0;
                            $d1 = $patient[500];
                            for ($i = 0; $i < count($list); $i++) {
                                $f2 = $list[$i];
                                $d2 = $patient[$f2];
                                $percent = 1 - (($d1 + $d2) / 2 * ($f2 - $f1) / (150 * ($f2 - $f1)));
                                $total += $d2 * $weight[$list[$i]];
                                $f1 = $f2;
                                $d1 = $d2;
                            }
                            $totdiff+=abs($patient["word"]-$total);
                        }
                        if($totdiff<=$smallest)
                        {
                            print_r($weight);
                            echo "  ".$totdiff."<br>";
                            $smallest=$totdiff;
                        }
                    }
                }
            }
        }
    }
    echo $count."\n";
    print_r($weight);
}

