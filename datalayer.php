<?php
	error_reporting(E_ALL);
	include "./classes.php";	
	$lng = "de";
	$connect = mysqli_connect("localhost", "scribblesql23", "cb2613c7") or die("Error: 005 ".mysqli_error());
	mysqli_select_db($connect,"scribblesql23") or die("Error: 006 ".mysqli_error()); 	
	
	mysqli_query($connect,"SET NAMES 'utf8'");
	mysqli_query($connect,"SET CHARACTER SET 'utf8'");
	if (isset($_GET["language"]))
	{
		if ($_GET["language"] == "en"){
			$lng = "En";
		}
			
	}				
	if (!isset($_GET["task"])){
		die(json_encode("No task given"));
	}
	else{
		switch ($_GET["task"]) {
			case 'loadQuestion':	
				echo loadQuestion($lng,$connect,$_GET["id"]);
				break;					
			case 'LoadDistributionByAnswer':	
				echo GetDistributionByAnswer($lng,$connect,$_GET["id"]);
				break;		
			case 'isLastQuestion':	
				echo json_encode(isLastQuestion($connect,$_GET["id"]));
				break;	
			case 'GetListOfDistributions':	
				echo json_encode(GetListOfDistributions($connect));
				break;	
			case 'GetQuestionCount':	
				echo json_encode(GetQuestionCount($connect));
				break;	
			case 'IncreaseTestCount':	
				IncreaseTestCount($connect);
				break;	
			case 'GetTestCount':	
				echo json_encode(GetTestCount($connect));
				break;	
			case 'SaveRating':	
				echo json_encode(SaveRating($connect,$_GET["rating"]));
				break;	
		}
	}
	function isLastQuestion($connect,$id){
		$id = mysqli_real_escape_string($connect,$id);			
		$dbresult = mysqli_query($connect,"Select count(id) as Amount from Question where ID > $id") or die(mysqli_error($connect));		
		while ($row = mysqli_fetch_object($dbresult)) {			
			if ($row->Amount == 0)
				return true;
			else
				return false;
		}
		return true;
	}
	function isFirstQuestion($connect,$id){
		$id = mysqli_real_escape_string($connect,$id);			
		$dbresult = mysqli_query($connect,"Select count(id) as Amount from Question where ID < $id") or die(mysqli_error($connect));		
		while ($row = mysqli_fetch_object($dbresult)) {			
			if ($row->Amount == 0)
				return true;
			else
				return false;
		}
		return true;
	}
	function loadQuestion($lng,$connect,$id){
		$found =false;
		$id = mysqli_real_escape_string($connect,$id);	
		$columnPrefix = "";
		if ($lng != "de")
			$columnPrefix = $lng;
	
		if ($id == -1)
			$dbresult = mysqli_query($connect,"Select Id,".$columnPrefix."Title,".$columnPrefix."Subtitle from Question");
		else		
			$dbresult = mysqli_query($connect,"Select Id,".$columnPrefix."Title,".$columnPrefix."Subtitle   from Question where ID >=$id") or die(mysqli_error($connect));
		while ($row = mysqli_fetch_object($dbresult)) {
			$question = new Question();
			$question->Id = $row->Id;
			$question->Question = $row->{$columnPrefix."Title"};
			if (isLastQuestion($connect,$row->Id))
				$question->IsLastQuestion = true;
			$question->Answers = GetAnswersOfQuestion($lng,$connect,$question->Id);
			$question->SubTitle = $row->{$columnPrefix."Subtitle"};;	
			$question->IsFirstQuestion = isFirstQuestion($connect,$row->Id);
			$question->IsLastQuestion = isLastQuestion($connect,$row->Id);		
			return json_encode($question);
		}
		return null;
	}
	function GetAnswersOfQuestion($lng,$connect,$id){
		$result = array();
		$questionid = mysqli_real_escape_string($connect,$id);
		$columnPrefix = "";
		if ($lng != "de")
			$columnPrefix = $lng;
		$dbresult = mysqli_query($connect,"Select AID,".$columnPrefix."Text from QuestionAnswerRelation  inner join Answer a on a.ID = AID where QID = $questionid");
		while ($row = mysqli_fetch_object($dbresult)) {
			$answer = new Answer();
			$answer->Text = $row->{$columnPrefix."Text"};
			$answer->Id = $row->AID;
			$answer->Question = $id;
			$result[] = $answer;
		}
		return $result;
	}
	function GetDistributionByAnswer($lng,$connect,$id){
		$result = array();
		$id = mysqli_real_escape_string($connect,$id);
		$columnPrefix = "";
		if ($lng != "de")
			$columnPrefix = $lng;
		$dbresult = mysqli_query($connect,"Select d.ID, Name,d.".$columnPrefix."Description,d.ImageLink,d.License,d.Website,d.TextSource,d.ImageSource from AnswerDistributionRelation inner join Distribution d on d.Id = DID where AID = $id order by d.Name");
		while ($row = mysqli_fetch_object($dbresult)) {
			$distro = new Distribution();
			$distro->Id = $row->ID;
			$distro->Name = $row->Name;
			$distro->Description = $row->{$columnPrefix."Description"};
			$distro->ImageLink = $row->ImageLink;
			$distro->License = $row->License;
			$distro->Website = $row->Website;
			$distro->ImageSource = $row->ImageSource;
			$distro->TextSource = str_replace("Zitat ","",$row->TextSource);
			$result[] = $distro;
		}
		return json_encode($result);

	}
	function GetListOfDistributions($connect){
		$result = array();
		$dbresult = mysqli_query($connect,"Select Name from Distribution order by Name asc");
		while ($row = mysqli_fetch_object($dbresult)) {			
			$result[] = $row->Name;
		}
		return $result;
	}
	function GetQuestionCount($connect){		
		$dbresult = mysqli_query($connect,"Select count(id) as Amount from Question ");
		while ($row = mysqli_fetch_object($dbresult)) {			
			return $row->Amount;
		}
	}
	function IncreaseTestCount($connect){		
		$dbresult = mysqli_query($connect,"Update Stats set TestsMade = TestsMade +1");
	}
	function GetTestCount($connect){		
		$dbresult = mysqli_query($connect,"Select TestsMade as Amount from Stats ");
		while ($row = mysqli_fetch_object($dbresult)) {			
			return $row->Amount;
		}
	}
	function SaveRating($connect,$rating){
		$rating = mysqli_real_escape_string($connect,$rating);
		$result = mysqli_query($connect,"Update Rating Set Amount = Amount + 1 where Rating = '$rating'");
		if (!$result)
			return false;
		else
			return true;
	}	
?>
