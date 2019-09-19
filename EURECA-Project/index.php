<?php
require_once "config/config.php";
$conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
$fp=fopen('leftrightcncphonemes.txt','w');
if (mysqli_connect_errno()) {
    exit( "Failed to connect to MySQL: " . mysqli_connect_error());
}  
else {
    // echo("Successfully connected to database");
    $sql="SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults 
        where audiogramResults.left_measures != '[]' AND audiogramResults.right_measures != '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition)";
    $result = $conn->query($sql);
if($result==false)
{
    die(print_r($conn->error));
}
if ($result->num_rows > 0) {
   $string="";
    // output data of each row
    $count=1;
    while($row = $result->fetch_assoc()) {
        $string.="{";
        // var_dump($row);die();
        
        $leftmeasure=json_decode($row['left_measures'],true);
        $rightmeasure=json_decode($row['right_measures'],true);
        $cncphenomes=$row['Phonemes Correct'];
        $cncwords=$row['Words with 3 Phonemes Correct'];
        // var_dump($leftmeasure);
        // 
        if(count($leftmeasure)!=count($rightmeasure))
        {

            echo "$count. No Match <br>Left<br>";
            $count++;
            // var_dump($leftmeasure);var_dump($rightmeasure);
            foreach($leftmeasure as $value)
            {
                $arr=json_decode($value,true);
                print($arr['attrs']['audioValues']['frequency'].",".$arr['attrs']['audioValues']['decibels']."<br>");
            }
            echo "Right<br>";
            foreach($rightmeasure as $value)
            {
                $arr=json_decode($value,true);
                print($arr['attrs']['audioValues']['frequency'].",".$arr['attrs']['audioValues']['decibels']."<br>");
            }
            echo "<br><br>";
        }
        // var_dump($rightmeasure);
        // die();
        // if($rightmeasure)
        // {
        //     foreach($rightmeasure as $value)
        //     {
        //         $arr=json_decode($value,true);
        //         // var_dump($arr);die();
        //         $frequency=$arr['attrs']['audioValues']['frequency'];
        //         $decibels=$arr['attrs']['audioValues']['decibels'];
        //         $string.="(".$frequency.",".$decibels.",$cncphenomes)";
        //     }
        // }
        // print_r($leftmeasure);die();
        $string.="}\n";
    }
    // print_r($string);

    // fwrite($fp,$string);
    // fclose($fp);
} else {
    echo "0 results";
}
$conn->close();
}
?>