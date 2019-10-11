<?php
// ****************************************
// calculate the percentage of each frequency for CNC word score = 30
// area of frequency* decibels / area of highest decibels * frequency

require "./config/config.php";
$conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die();
}
$score=30;
$sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
where audiogramResults.left_measures != '[]' AND audiogramResults.right_measures = '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
`cncResults`.`Words with 3 Phonemes Correct` = '{$score}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";
$result = $conn->query($sql);
$dataPoints = array();
while ($row = $result->fetch_assoc()) {
         
    $leftmeasure = json_decode($row['left_measures'], true);
    $rightmeasure = json_decode($row['right_measures'], true);
    
    if ($leftmeasure) {
        $Points = array(); 
        foreach ($leftmeasure as $value) {
            
            $arr = json_decode($value, true);
            $frequency = $arr['attrs']['audioValues']['frequency'];
            $decibels = $arr['attrs']['audioValues']['decibels'];
            
            $Points[$frequency] = $decibels;
            
            //print_r($Points);
            //array_push($dataPoints, array("x" => $frequency, "y" => $decibels));
        }
        asort($Points);
        array_push($dataPoints, $Points);
    }
}
$tot=0;
// var_dump(key($dataPoints[0][250]));die();
$f1=250;
$d1=0;
$count1=0;
$maxd=150;
$maxfreq=6000;

foreach($dataPoints as $patients)
{
    // var_dump($patients);die();
    $d1+=$patients[250];
    $count1++;
}
$percent=($d1/$count1)*$f1/$maxd/$f1;
// $percent=$maxd*$maxfreq/(($d1/$count1)*$f1);
echo $percent." for $f1 with averge decibel of ".$d1/$count1."\n<br>";
// $tot+=$percent*($d1/$count1);
$list=array(500,1000,2000,3000,4000,6000);
for($i=0;$i<count($list);$i++)
{
    $f2=$list[$i];
    // echo $f2;
    $d2=0;
    $count2=0;
    foreach($dataPoints as $patients)
    {
        if($patients[$list[$i]]){
            $d2+=$patients[$list[$i]];
            $count2++;
        }
        
    }
    $percent=1-(($d1/$count1)+($d2/$count2))/2*($f2-$f1)/(150*($f2-$f1));
    // $percent=150*6000/(($d1/$count1)*($d2/$count2)/2*($f2-$f1));
    echo $percent." for $f2 with averge decibel of ".$d2/$count2."\n<br>";
    $tot+=$percent;
    $f1=$f2;
    $d1=$d2;
    $count1=$count2;
}
echo $tot;


?>