<?php
// get the patient that has data of the right measure for all needed frequency, put it in a csv file

// header('Content-Type: application/json');
require "./config/config.php";
$conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die();
}
$score = 30;
$sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults where audiogramResults.left_measures = '[]' AND audiogramResults.right_measures != '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND `cncResults`.`Words with 3 Phonemes Correct` != 0 and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";

$result = $conn->query($sql);
$dataPoints = array();
while ($row = $result->fetch_assoc()) {
    $leftmeasure = json_decode($row['left_measures'], true);
    $rightmeasure = json_decode($row['right_measures'], true);
    if ($rightmeasure) {
        $Points = array();
        foreach ($rightmeasure as $value) {

            $arr = json_decode($value, true);
            $frequency = $arr['attrs']['audioValues']['frequency'];
            $decibels = $arr['attrs']['audioValues']['decibels'];

            $Points[$frequency] = $decibels;
        }
        asort($Points);
        array_push($dataPoints, $Points);
    }
}
// print_r($dataPoints);

$tot = 0;

$list = array(500, 1000, 2000,3000,4000);
$percentPerFrequency = array(500=>1,1000=>2,2000=>4,3000=>2,4000=>2,6000=>1);
for ($i = 0; $i < count($list); $i++) {
    $freqData = array();
    // $f2 = $list[$i];
    // echo $f2;
    // $d2 = 0;
    // $count2 = 0;
    print_r($list[$i] . ": ");
    foreach ($dataPoints as $patients) {
        if ($patients[$list[$i]]) {
            // $d2 += $patients[$list[$i]];
            // $count2++;
            array_push($freqData, $patients[$list[$i]]);
        }

    }

    // foreach($freqData as $key => $value) {
    //     if($value == "" & $value != '0') {
    //       unset($freqData[$key]);
    //     }
    // }
    // $sumDiff = array();

    // $sumDiff=max($freqData)-min($freqData);

    // print($sumDiff);
    // print_r($freqData);
    print_r("Max: " . max($freqData) . " ");
    print_r("Min: " . min($freqData) . " ");

    print_r("Difference: " . (max($freqData) - min($freqData)) . "\n <br>");

    // $percent = 1 - (($d1 / $count1) + ($d2 / $count2)) / 2 * ($f2 - $f1) / (150 * ($f2 - $f1));
    $percentPerFrequency[$list[$i]] = 150 / (max($freqData) - min($freqData));
    // $percent=150*6000/(($d1/$count1)*($d2/$count2)/2*($f2-$f1));
    //echo $percent." for $f2 with averge decibel of ".$d2/$count2."\n<br>";
    // $tot += $percent;
    // $f1 = $f2;
    // $d1 = $d2;
    // $count1 = $count2;
}
// echo $tot;
print_r($percentPerFrequency);
echo "<br><br>";

echo "<table border='1'>
<tr>
<th>Predicted Score</th>
<th>Actual Score</th>
<th>Difference</th>
</tr>";
$result = $conn->query($sql);
$totdiff = 0;
$count=0;
$smallest=5000;
$fp=fopen('RightNot6000.csv','w');
while ($row = $result->fetch_assoc()) {
    $leftmeasure = json_decode($row['left_measures'], true);
    $rightmeasure = json_decode($row['right_measures'], true);
    $wordScore = $row['Words with 3 Phonemes Correct'];
    $total = 0;
    if ($rightmeasure) {
        $Points = array();
        foreach ($rightmeasure as $value) {

            $arr = json_decode($value, true);
            $frequency = $arr['attrs']['audioValues']['frequency'];
            $decibels = $arr['attrs']['audioValues']['decibels'];

            $Points[$frequency] = $decibels;
        }
        ksort($Points);
        // print_r($Points);
        // print("<br>");

        $f1 = 250;
        $d1 = 0;
        $count1 = 0;
        $maxd = 150;
        $maxfreq = 6000;
        $percentArray=array();
        if ($Points[500] && $Points[1000] && $Points[2000] && $Points[3000] && $Points[4000]) {
            $count++;
            if ($Points[$f1]) {
            $d1 = $Points[$f1];
            }
            else{
                $d1=$Points[500];
                $f1=0;
            }           
            for ($i = 0; $i < count($list); $i++) {
                $f2 = $list[$i];
                $d2 = 0;
                if ($Points[$f2]) {
                    $d2 = $Points[$f2];
                    $percent = 1 - (($d1 + $d2) / 2 * ($f2 - $f1) / (150 * ($f2 - $f1)));
                    $total += $percent * $percentPerFrequency[$list[$i]]+3;
                    $percentArray[]=$percent;
                }
                $f1 = $f2;
                $d1 = $d2;
            }
            $percentArray[]=$wordScore;
            // var_dump($percentArray);die();
            fputcsv($fp,$percentArray);
            $diff = abs($wordScore - $total);
            $totdiff += $diff;
            // echo (str_pad($total,8) . str_pad($wordScore,4). $diff . "\n<br>");
            echo "<tr>";
            echo "<td>" . round($total, 2) . "</td>";
            echo "<td>" . $wordScore . "</td>";
            echo "<td>" . round($diff, 2) . "</td>";
            echo "</tr>";
        }
        // if ($Points[$f1]) {
        //     $d1 = $Points[$f1];
        // }

    }
}
fclose($fp);
echo "<tr>";
echo "<td>" . $count."</td>";
echo "<td>" . "</td>";
echo "<td>" . round($totdiff, 2) . "</td>";
echo "</tr>";
echo "</table>";

?>
