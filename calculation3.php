<?php
// header('Content-Type: application/json');
require "./config/config.php";

$conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die();
}
$score = 32;
$sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
where audiogramResults.left_measures != '[]' AND audiogramResults.right_measures = '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
`cncResults`.`Words with 3 Phonemes Correct` = '{$score}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";
$result = $conn->query($sql);

$list = array(500, 1000, 2000, 3000,4000, 6000);
$score_data=array();

if (($handle = fopen('validpatients2.csv', 'r')) !== false) {
    while (($data = fgetcsv($handle, 1000, ",")) !== false) { // Check opening the file is OK!
        //read data    
        if($data[6] == $score){
            $score_data[] = $data;	
        }
    }
    fclose($handle);
}
print_r("Number of patient have 6 scores for 32: ".count($score_data)."\n");
// echo"<pre>";
// var_dump($score_data);
// echo"<pre>";
$newdata = array();
for($num=0;$num < 6; $num++){
    $new = array();
    for($i = 0; $i < count($score_data); $i++){
        array_push($new,$score_data[$i][$num]);
    }
    $newdata[$list[$num]]= $new;
}
// echo"<pre>";
// print_r($newdata);
// echo"<pre>";
$diffArr = array();
for($i = 0; $i < count($list);$i++){
    $diffArr[$list[$i]] = max($newdata[$list[$i]])- min($newdata[$list[$i]]);
} 
ksort($diffArr);
echo"<pre>";
print_r($diffArr);
echo"<pre>";
$weighArr = array();
// for($i = 0; $i < count($list);$i++){
//     $weighArr[$list[$i]] = 5;
// }

// $mean = ($score/2 + $diffArr[$list[0]])/2;
$mean = $score/2;
// $mean = $diffArr[$list[0]];
print_r($mean);
// $sd = 15;
// $sd = (($diffArr[$list[1]]-$mean) + ($diffArr[$list[2]]-$mean))/2;
// $sd = ($diffArr[$list[0]]-$mean);
$sd = array_sum($diffArr)/count($diffArr) - ($mean+2.5);
print_r($sd);
$curve=array(0.341,0.136,0.023,0.0015);
for ($i = 0; $i < count($diffArr);$i++){
    if($diffArr[$list[$i]] <= ($mean + $sd)){
        $weigh = $score * $curve[0] - 1.91;
    }
    else if($diffArr[$list[$i]] <= ($mean + $sd*2)){
        $weigh = $score * $curve[1];
    }
    else if($diffArr[$list[$i]] <= ($mean + $sd*3)){
        $weigh = $score * $curve[2];
    }
    else if($diffArr[$list[$i]] <= ($mean + $sd*4)){
        $weigh = $score * $curve[3];
    }
    // $key = array_search($i,$list[$i]);
    // print_r($key." ");
    $weighArr[$list[$i]] = $weigh;
    // array_push($weighArr,$weigh);
}
// ksort($weighArr);
// print_r($weighArr);
// print_r(array_sum($weighArr));

echo "Weigh Array <br>";
print_r($weighArr);
echo "<br>Sum of all weigh ";
print_r(array_sum($weighArr));
echo "<br><br>";
echo "<table border='1'>
<tr>
<th>Count</th>
<th>Predicted Score</th>
<th>Actual Score</th>
<th>Difference</th>
</tr>";
$count1 = 0;
while ($row = $result->fetch_assoc()) {
    $leftmeasure = json_decode($row['left_measures'], true);
    $rightmeasure = json_decode($row['right_measures'], true);
    $wordScore = $row['Words with 3 Phonemes Correct'];
    $total=0;
    if ($leftmeasure) {
        $Points = array();
        foreach ($leftmeasure as $value) {

            $arr = json_decode($value, true);
            $frequency = $arr['attrs']['audioValues']['frequency'];
            $decibels = $arr['attrs']['audioValues']['decibels'];

            $Points[$frequency] = $decibels;
        }
        
        asort($Points);
        // print_r($Points);
        $f1 = 250;
        $d1 = 0;
        // $count1 = 0;
        $maxd = 150;
        $maxfreq = 6000;
        if ($Points[$f1]) {
            $d1 = $Points[$f1];
        }

        for ($i = 0; $i < count($list); $i++) {
            $f2 = $list[$i];
            $d2 = 0;
            if ($Points[$f2]) {
                $d2 = $Points[$f2];
                $percent = 1 - (($d1 + $d2) / 2 * ($f2 - $f1) / (150 * ($f2 - $f1)));
                $total += $percent * ($weighArr[$list[$i]]);
            }
            $f1 = $f2;
            $d1 = $d2;
        }
        $diff = abs($wordScore - $total);
        $totalPred += $total;
        $totalScore += $wordScore;
        $totdiff+=$diff;
        $count1++;
        // echo (str_pad($total,8) . str_pad($wordScore,4). $diff . "\n<br>");
        echo "<tr>";
        echo "<td>" . $count1. "</td>";
        echo "<td>" . round($total,2) . "</td>";
        echo "<td>" . $wordScore . "</td>";
        echo "<td>" . round($diff,2) . "</td>";
        echo "</tr>";
    }
}
echo "<tr>";
echo "<td>" . "</td>";
echo "<td>" . round($totalPred,2). "</td>";
echo "<td>" . round($totalScore,2)."</td>";
echo "<td>" . round($totdiff,2) . "</td>";
echo "</tr>";
echo "</table>";


?>