<?php
require_once __DIR__ . '/vendor/autoload.php';

//Functions
function Tokenize($string,$token)
{
    $temp = strtok($string,$token);
    $array = array();
    while($temp)
    {
        $array[] = $temp;
        $temp = strtok($token);
    }
    return $array;
}

function PreProcess($file,$regex)
{
    $fileContent = file_get_contents($file);
    $string = strtolower($fileContent);
    if(!is_null($regex))
        $string = preg_replace($regex, '', $string);
    return $string;
}

function PrintArray($array)
{
    $json_string = json_encode($array,JSON_PRETTY_PRINT);
    echo "<pre>".$json_string."<pre>";
}

function InitializeStemmer()
{
    $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
    $stemmer  = $stemmerFactory->createStemmer();
    return $stemmer;
}

function Stem($array,$stemmer)
{
    foreach ($array as $doc)
    {
        foreach ($doc as $token)
        {
            //Error dimana kata "masakan" tidak distem pada library
            if($token == "masakan")
                $token = "masak";
            $stemmedArray[] = $stemmer->stem($token);
        }
        $stemmedArrays[] = $stemmedArray;
        $stemmedArray = null;
    }
    return $stemmedArrays;
}

function StopWordRemoval($array, $stopWordsTokens, &$removedStopWordArrays, &$removedStopWordTokens)
{
    foreach ($array as $stemDoc)
    {
        foreach ($stemDoc as $token)
        {
            if(!in_array($token,$stopWordsTokens))
            {
                $removedStopWordArray[] = $token;
                $removedStopWordTokens[] = $token;
            }
        }
        $removedStopWordArrays[] = $removedStopWordArray;
        $removedStopWordArray = null;
    }
}

function DisplayHeader($array)
{
    echo " \t";
    foreach ($array as $header)
    {
        echo "$header \t";
    }
    echo "<br>";
}

function DisplayBinaryWeight($array, $uniqueArray)
{
    DisplayHeader($uniqueArray);
    $i = 1;
    foreach ($array as $item)
    {
        echo "D$i \t";
        foreach ($uniqueArray as $value)
        {
            if(in_array($value,$item))
            {
                echo "1";
            }
            else
            {
                echo "0";
            }
            echo "\t";
        }
        echo "<br>";
        $i++;
    }
    echo "<hr>";
}

function DisplayRawTermWeight($array, $uniqueArray)
{
    DisplayHeader($uniqueArray);
    $i = 1;
    foreach ($array as $items)
    {
        echo "D$i \t";
        foreach ($uniqueArray as $value)
        {
            $counter = 0;
            foreach ($items as $item)
            {
                if($value == $item)
                {
                    $counter++;
                }
            }
            if(fmod($counter,1)==0.0)
            {
                echo $counter;
            }
            else
            {
                echo number_format($counter,3);
            }
            echo "\t";
        }
        echo "<br>";
        $i++;
    }

    echo "<hr>";
}

function DisplayLogFrequencyWeight($stopDocs,$finalTokens)
{
    DisplayHeader($finalTokens);
    $i = 1;
    foreach ($stopDocs as $stopDoc)
    {
        echo "D$i \t";
        foreach ($finalTokens as $item)
        {
            $counter = 0;
            foreach ($stopDoc as $value)
            {
                if($item == $value)
                {
                    $counter++;
                }
            }
            if($counter>0)
                $counter = 1 + log($counter,10);
            if(fmod($counter,1)==0.0)
            {
                echo $counter;
            }
            else
            {
                echo number_format($counter,3);
            }
            echo "\t";
        }
        echo "<br>";
        $i++;
    }

    echo "<hr>";
}

function DisplayDocumentFrequencyWeight($stopDocs,$finalTokens,$fileDocs)
{
    $i = 1;
    DisplayHeader($finalTokens);
    foreach ($stopDocs as $stopDoc)
    {
        echo "D$i \t";
        foreach ($finalTokens as $item)
        {
            $counter = 0;
            foreach ($stopDoc as $value)
            {
                if($item == $value)
                {
                    $counter++;
                }
            }
            if($counter>0)
                $counter = 1 + log($counter,10);
            $tableValue[$i-1][] = $counter;
            if(fmod($counter,1)==0.0)
            {
                echo $counter;
            }
            else
            {
                echo number_format($counter,3);
            }
            echo "\t";
            $tableSum[] = 0;
        }
        $i++;
        echo "<br>";
    }

    for ($index = 0; $index<sizeof($finalTokens); $index++)
    {
        for ($index1 = 0; $index1<sizeof($stopDocs); $index1++)
        {
            if($tableValue[$index1][$index])
                $tableSum[$index]++;
        }
    }

    $n = 0;
    echo "IDF\t";
    foreach ($finalTokens as $item)
    {
        $counter = log(count($fileDocs)/$tableSum[$n],10);
        echo number_format($counter,3)."\t";
        $n++;
    }

    echo "<hr>";
}

function DisplayTFIDFWeight($stopDocs,$finalTokens,$fileDocs)
{
    $i = 1;
    DisplayHeader($finalTokens);
    foreach ($stopDocs as $stopDoc)
    {
        foreach ($finalTokens as $item)
        {
            $counter = 0;
            foreach ($stopDoc as $value)
            {
                if($item == $value)
                {
                    $counter++;
                }
            }
            if($counter>0)
                $counter = 1 + log($counter,10);
            $tableValue[$i-1][] = $counter;
            $tableSum[] = 0;
        }
        $i++;
    }

    for ($index = 0; $index<sizeof($finalTokens); $index++)
    {
        for ($index1 = 0; $index1<sizeof($stopDocs); $index1++)
        {
            if($tableValue[$index1][$index])
                $tableSum[$index]++;
        }
    }

    $n = 0;
    foreach ($finalTokens as $item)
    {
        $IDF[] = log(count($fileDocs)/$tableSum[$n],10);
        $n++;
    }

    $i = 1;
    for ($index1 = 0; $index1<sizeof($stopDocs); $index1++)
    {
        echo "D$i \t";
        for ($index = 0; $index<sizeof($finalTokens); $index++)
        {
            $tableValue[$index1][$index] *= $IDF[$index];
            if(fmod($tableValue[$index1][$index],1)==0.0)
            {
                echo $tableValue[$index1][$index];
            }
            else
            {
                echo number_format($tableValue[$index1][$index],3);
            }
            echo "\t";

        }
        echo "<br>";
        $i++;
    }
    echo"<hr>";
}
//end of functions

//Var
$fileStopWord = "Docs/stopword_list_tala.txt";
$fileDocs = array("Docs/d1.txt","Docs/d2.txt","Docs/d3.txt","Docs/d4.txt","Docs/d5.txt");

//PreProcess
$regex = null;
$preStopWord = PreProcess($fileStopWord,$regex);
$regex = '/[^a-z \-]/';
foreach ($fileDocs as $fileDoc)
    $preDocs[] = PreProcess($fileDoc,$regex);


//Tokenize
$token = "\n\r";
$tokenStopWords = Tokenize($preStopWord,$token);
$token = " ";
foreach ($preDocs as $preDoc)
    $tokenDocs[] = Tokenize($preDoc,$token);

//Stemming
$stemmer = InitializeStemmer();
$stemDocs = Stem($tokenDocs,$stemmer);

//Stop Word Removal
StopWordRemoval($stemDocs,$tokenStopWords,$stopDocs,$stopTokens);

//Remove duplicates
$finalTokens = array_values(array_unique($stopTokens));
?>

<?php
//Display
echo "<h1>PreProcess</h1>";
PrintArray($preDocs);
echo "<h1>Tokenisasi</h1>";
PrintArray($tokenDocs);
echo "<h1>Stemming</h1>";
PrintArray($stemDocs);
echo "<h1>Stop Word Removal</h1>";
PrintArray($stopDocs);
//PrintArray($stopTokens);
echo "<h1>Token Unik</h1>";
PrintArray($finalTokens);
echo "<h2>Binary</h2>";
DisplayBinaryWeight($stopDocs,$finalTokens);
echo "<h2>Raw Term</h2>";
DisplayRawTermWeight($stopDocs,$finalTokens);
echo "<h2>Frequency</h2>";
DisplayLogFrequencyWeight($stopDocs,$finalTokens);
echo "<h2>IDF</h2>";
DisplayDocumentFrequencyWeight($stopDocs,$finalTokens,$fileDocs);
echo "<h2>TFIDF</h2>";
DisplayTFIDFWeight($stopDocs,$finalTokens,$fileDocs);
?>
