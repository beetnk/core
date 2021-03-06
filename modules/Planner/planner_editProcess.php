<?
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$gibbonPlannerEntryID=$_GET["gibbonPlannerEntryID"] ;
$viewBy=$_GET["viewBy"] ;
$subView=$_GET["subView"] ;
if ($viewBy!="date" AND $viewBy!="class") {
	$viewBy="date" ;
}
$gibbonCourseClassID=$_POST["gibbonCourseClassID"] ;
$date=dateConvert($guid, $_POST["date"]) ;
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/planner_edit.php&gibbonPlannerEntryID=$gibbonPlannerEntryID" ;

//Params to pass back (viewBy + date or classID)
if ($viewBy=="date") {
	$params="&viewBy=$viewBy&date=$date" ;
}
else {
	$params="&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
}

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_edit.php")==FALSE) {
	//Fail 0
	$URL=$URL . "&updateReturn=fail0$params" ;
	header("Location: {$URL}");
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["address"], $connection2) ;
	if ($highestAction==FALSE) {
		//Fail 0
		$URL=$URL . "&updateReturn=fail0$params" ;
		header("Location: {$URL}");
	}
	else {
		if (empty($_POST)) {
			$URL=$URL . "&updateReturn=fail6" ;
			header("Location: {$URL}");
		}
		else {	
			//Proceed!
			//Check if school year specified
			if ($gibbonPlannerEntryID=="" OR ($viewBy=="class" AND $gibbonCourseClassID=="")) {
				//Fail1
				$URL=$URL . "&updateReturn=fail1$params" ;
				header("Location: {$URL}");
			}
			else {
				try {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					else {
						$data=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail2
					$URL=$URL . "&updateReturn=fail2$params" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($result->rowCount()!=1) {
					//Fail 2
					$URL=$URL . "&updateReturn=fail2$params" ;
					header("Location: {$URL}");
				}
				else {
					$row=$result->fetch() ;
					
					//CHECK IF UNIT IS GIBBON OR HOOKED
					if ($row["gibbonHookID"]==NULL) {
						$hooked=FALSE ;
						$gibbonUnitID=$row["gibbonUnitID"]; 
					}
					else {
						$hooked=TRUE ;
						$gibbonUnitIDToken=$row["gibbonUnitID"]; 
						$gibbonHookIDToken=$row["gibbonHookID"]; 
					
						try {
							$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
							$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
							$resultHooks=$connection2->prepare($sqlHooks);
							$resultHooks->execute($dataHooks);
						}
						catch(PDOException $e) { }
						if ($resultHooks->rowCount()==1) {
							$rowHooks=$resultHooks->fetch() ;
							$hookOptions=unserialize($rowHooks["options"]) ;
							if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
								try {
									$data=array("unitIDField"=>$gibbonUnitIDToken); 
									$sql="SELECT " . $hookOptions["unitTable"] . ".*, gibbonCourse.nameShort FROM " . $hookOptions["unitTable"] . " JOIN gibbonCourse ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["unitIDField"] . "=:unitIDField" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }									
							}
						}
					}
				
					//Validate Inputs
					$timeStart=$_POST["timeStart"] ;
					$timeEnd=$_POST["timeEnd"] ;
					$gibbonUnitID=NULL ;
					if (isset($_POST["gibbonUnitID"])) {
						$gibbonUnitID=$_POST["gibbonUnitID"] ;
					}
					if ($gibbonUnitID=="") {
						$gibbonUnitID=NULL ;
						$gibbonHookID=NULL ;
					}
					else {
						//Check for hooked unit (will have - in value)
						if (strpos($gibbonUnitID, "-")==FALSE OR strpos($gibbonUnitID, "-")==0) {
							//No hook
							$gibbonUnitID=$gibbonUnitID ;
							$gibbonHookID=NULL ;
						}
						else {
							//Hook!
							$gibbonUnitID=substr($_POST["gibbonUnitID"],0,strpos($gibbonUnitID, "-")) ;
							$gibbonHookID=substr($_POST["gibbonUnitID"],(strpos($_POST["gibbonUnitID"], "-")+1)) ;
						}
					}
					$name=$_POST["name"] ;
					$summary=$_POST["summary"] ;
					$description=$_POST["description"] ;
					$teachersNotes=$_POST["teachersNotes"] ;
					$homeworkSubmissionDateOpen=NULL ;
					$homeworkSubmissionDrafts=NULL ;
					$homeworkSubmissionType=NULL ;
					$homeworkSubmissionRequired=NULL ;
					$homeworkCrowdAssess=NULL ;
					$homeworkCrowdAssessOtherTeachersRead=NULL ;
					$homeworkCrowdAssessClassmatesRead=NULL ;
					$homeworkCrowdAssessOtherStudentsRead=NULL ;
					$homeworkCrowdAssessSubmitterParentsRead=NULL ;
					$homeworkCrowdAssessClassmatesParentsRead=NULL ;
					$homeworkCrowdAssessOtherParentsRead=NULL ;
					$homework=$_POST["homework"] ;
					if ($_POST["homework"]=="Yes") {
						$homework="Y" ;
						$homeworkDetails=$_POST["homeworkDetails"] ;
						if ($_POST["homeworkDueDateTime"]!="") {
							$homeworkDueDateTime=$_POST["homeworkDueDateTime"] . ":59" ;
						}
						else {
							$homeworkDueDateTime="23:59:59" ;
						}
						if ($_POST["homeworkDueDate"]!="") {
							$homeworkDueDate=dateConvert($guid, $_POST["homeworkDueDate"]) . " " . $homeworkDueDateTime ;
						}
						
						if ($_POST["homeworkSubmission"]=="Yes") {
							$homeworkSubmission="Y" ;
							if ($_POST["homeworkSubmissionDateOpen"]!="") {
								$homeworkSubmissionDateOpen=dateConvert($guid, $_POST["homeworkSubmissionDateOpen"]) ;
							}
							else {
								$homeworkSubmissionDateOpen=date("Y-m-d") ;
							}
							if (isset($_POST["homeworkSubmissionDrafts"])) {
								$homeworkSubmissionDrafts=$_POST["homeworkSubmissionDrafts"] ;
							}
							$homeworkSubmissionType=$_POST["homeworkSubmissionType"] ;
							$homeworkSubmissionRequired=$_POST["homeworkSubmissionRequired"] ;
							if ($_POST["homeworkCrowdAssess"]=="Yes") {
								$homeworkCrowdAssess="Y" ;
								if (isset($_POST["homeworkCrowdAssessOtherTeachersRead"])) {
									$homeworkCrowdAssessOtherTeachersRead="Y" ;
								}
								else {
									$homeworkCrowdAssessOtherTeachersRead="N" ;
								}
								if (isset($_POST["homeworkCrowdAssessClassmatesRead"])) {
									$homeworkCrowdAssessClassmatesRead="Y" ;
								}
								else {
									$homeworkCrowdAssessClassmatesRead="N" ;
								}
								if (isset($_POST["homeworkCrowdAssessOtherStudentsRead"])) {
									$homeworkCrowdAssessOtherStudentsRead="Y" ;
								}
								else {
									$homeworkCrowdAssessOtherStudentsRead="N" ;
								}
								if (isset($_POST["homeworkCrowdAssessSubmitterParentsRead"])) {
									$homeworkCrowdAssessSubmitterParentsRead="Y" ;
								}
								else {
									$homeworkCrowdAssessSubmitterParentsRead="N" ;
								}
								if (isset($_POST["homeworkCrowdAssessClassmatesParentsRead"])) {
									$homeworkCrowdAssessClassmatesParentsRead="Y" ;
								}
								else {
									$homeworkCrowdAssessClassmatesParentsRead="N" ;
								}
								if (isset($_POST["homeworkCrowdAssessOtherParentsRead"])) {
									$homeworkCrowdAssessOtherParentsRead="Y" ;
								}
								else {
									$homeworkCrowdAssessOtherParentsRead="N" ;
								}
							}
						}
						else {
							$homeworkSubmission="N" ;
						}
					}
					else {
						$homework="N" ;
						$homeworkDueDate=NULL ;
						$homeworkDetails="" ;
						$homeworkSubmission="N" ;
					}
					
					$viewableParents=$_POST["viewableParents"] ;
					$viewableStudents=$_POST["viewableStudents"] ;
					$twitterSearch=$_POST["twitterSearch"] ;
					$gibbonPersonIDCreator=$_SESSION[$guid]["gibbonPersonID"] ;
					$gibbonPersonIDLastEdit=$_SESSION[$guid]["gibbonPersonID"] ;
					
					if ($viewBy=="" OR $gibbonCourseClassID=="" OR $date=="" OR $timeStart=="" OR $timeEnd=="" OR $name=="" OR $summary=="" OR $homework=="" OR $viewableParents=="" OR $viewableStudents=="" OR ($homework=="Y" AND ($homeworkDetails=="" OR $homeworkDueDate==""))) {
						//Fail 3
						$URL=$URL . "&updateReturn=fail3$params" ;
						header("Location: {$URL}");
					}
					else {
						//Scan through guests
						$guests=NULL ;
						if (isset($_POST["guests"])) {
							$guests=$_POST["guests"] ;
						}
						$role=$_POST["role"] ;
						if ($role=="") {
							$role="Student" ;
						}
						if (count($guests)>0) {
							foreach ($guests as $t) {
								//Check to see if person is already registered in this class
								try {
									$dataGuest=array("gibbonPersonID"=>$t, "gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sqlGuest="SELECT * FROM gibbonCourseClassPerson WHERE gibbonPersonID=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID" ;
									$resultGuest=$connection2->prepare($sqlGuest);
									$resultGuest->execute($dataGuest);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								if ($resultGuest->rowCount()==0) {
									//Check to see if person is already a guest in this class
									try {
										$dataGuest2=array("gibbonPersonID"=>$t, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
										$sqlGuest2="SELECT * FROM gibbonPlannerEntryGuest WHERE gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
										$resultGuest2=$connection2->prepare($sqlGuest2);
										$resultGuest2->execute($dataGuest2);
									}
									catch(PDOException $e) { 
										$partialFail=TRUE ;
									}
									if ($resultGuest2->rowCount()==0) {
										try {
											$data=array("gibbonPersonID"=>$t, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "role"=>$role); 
											$sql="INSERT INTO gibbonPlannerEntryGuest SET gibbonPersonID=:gibbonPersonID, gibbonPlannerEntryID=:gibbonPlannerEntryID, role=:role" ;
											$result=$connection2->prepare($sql);
											$result->execute($data);
										}
										catch(PDOException $e) { 
											$partialFail=TRUE ;
										}
									}
								}
							}
						}
						
						//Deal with smart unit
						$partialFail=false ;
						$order=NULL ;
						if (isset($_POST["order"])) {
							$order=$_POST["order"] ;
						}
						$seq=NULL ;
						if (isset($_POST["minSeq"])) {
							$seq=$_POST["minSeq"] ;
						}
						
						if (is_array($order)) {
							foreach ($order as $i) {
								$id=$_POST["gibbonUnitClassBlockID$i"] ;
								$title=$_POST["title$i"] ;
								$type=$_POST["type$i"] ;
								$length=$_POST["length$i"] ;
								$contents=$_POST["contents$i"] ;
								$teachersNotesBlock=$_POST["teachersNotes$i"] ;
								$complete="N" ;
								if (isset($_POST["complete$i"])) {
									if ($_POST["complete$i"]=="on") {
										$complete="Y" ;
									}
								}
																
								//Write to database
								try {
									if ($hooked==FALSE) {
										$data=array("title"=>$title, "type"=>$type, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotesBlock, "complete"=>$complete, "sequenceNumber"=>$seq, "gibbonUnitClassBlockID"=>$id); 
										$sql="UPDATE gibbonUnitClassBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, complete=:complete, sequenceNumber=:sequenceNumber WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID" ;
									}
									else {
										$data=array("title"=>$title, "type"=>$type, "length"=>$length, "contents"=>$contents, "teachersNotes"=>$teachersNotesBlock, "complete"=>$complete, "sequenceNumber"=>$seq, "gibbonUnitClassBlockID"=>$id); 
										$sql="UPDATE " . $hookOptions["classSmartBlockTable"] . " SET " . $hookOptions["classSmartBlockTitleField"] . "=:title, " . $hookOptions["classSmartBlockTypeField"] . "=:type, " . $hookOptions["classSmartBlockLengthField"] . "=:length, " . $hookOptions["classSmartBlockContentsField"] . "=:contents, " . $hookOptions["classSmartBlockTeachersNotesField"] . "=:teachersNotes, " . $hookOptions["classSmartBlockCompleteField"] . "=:complete, " . $hookOptions["classSmartBlockSequenceNumberField"] . "=:sequenceNumber WHERE " . $hookOptions["classSmartBlockIDField"] . "=:gibbonUnitClassBlockID" ;
									}
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$partialFail=true ;
								}
								
								$seq++ ;
							}
						}
						
						//Delete all outcomes
						try {
							$dataDelete=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID);  
							$sqlDelete="DELETE FROM gibbonPlannerEntryOutcome WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
							$resultDelete=$connection2->prepare($sqlDelete);
							$resultDelete->execute($dataDelete);  
						}
						catch(PDOException $e) { 
							//Fail2
							$URL=$URL . "&updateReturn=fail2" ;
							header("Location: {$URL}");
							break ;
						}
						//Insert outcomes
						$count=0 ;
						if (isset($_POST["outcomeorder"])) {
							if (count($_POST["outcomeorder"])>0) {
								foreach ($_POST["outcomeorder"] AS $outcome) {
									if ($_POST["outcomegibbonOutcomeID$outcome"]!="") {
										try {
											$dataInsert=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID, "gibbonOutcomeID"=>$_POST["outcomegibbonOutcomeID$outcome"], "content"=>$_POST["outcomecontents$outcome"], "count"=>$count);  
											$sqlInsert="INSERT INTO gibbonPlannerEntryOutcome SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count" ;
											$resultInsert=$connection2->prepare($sqlInsert);
											$resultInsert->execute($dataInsert);
										}
										catch(PDOException $e) {
											print $e ;
											$partialFail=true ;
										}
									}
									$count++ ;
								}	
							}
						}
						
						
						//Write to database
						try {
							$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>$date, "timeStart"=>$timeStart, "timeEnd"=>$timeEnd, "gibbonUnitID"=>$gibbonUnitID, "gibbonHookID"=>$gibbonHookID, "name"=>$name, "summary"=>$summary, "description"=>$description, "teachersNotes"=>$teachersNotes, "homework"=>$homework, "homeworkDueDate"=>$homeworkDueDate, "homeworkDetails"=>$homeworkDetails, "homeworkSubmission"=>$homeworkSubmission, "homeworkSubmissionDateOpen"=>$homeworkSubmissionDateOpen, "homeworkSubmissionDrafts"=>$homeworkSubmissionDrafts, "homeworkSubmissionType"=>$homeworkSubmissionType, "homeworkSubmissionRequired"=>$homeworkSubmissionRequired, "homeworkCrowdAssess"=>$homeworkCrowdAssess, "homeworkCrowdAssessOtherTeachersRead"=>$homeworkCrowdAssessOtherTeachersRead, "homeworkCrowdAssessClassmatesRead"=>$homeworkCrowdAssessClassmatesRead, "homeworkCrowdAssessOtherStudentsRead"=>$homeworkCrowdAssessOtherStudentsRead, "homeworkCrowdAssessSubmitterParentsRead"=>$homeworkCrowdAssessSubmitterParentsRead, "homeworkCrowdAssessClassmatesParentsRead"=>$homeworkCrowdAssessClassmatesParentsRead, "homeworkCrowdAssessOtherParentsRead"=>$homeworkCrowdAssessOtherParentsRead, "viewableParents"=>$viewableParents, "viewableStudents"=>$viewableStudents, "twitterSearch"=>$twitterSearch, "gibbonPersonIDLastEdit"=>$gibbonPersonIDLastEdit, "gibbonPlannerEntryID"=>$gibbonPlannerEntryID); 
							$sql="UPDATE gibbonPlannerEntry SET gibbonCourseClassID=:gibbonCourseClassID, date=:date, timeStart=:timeStart, timeEnd=:timeEnd, gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, name=:name, summary=:summary, description=:description, teachersNotes=:teachersNotes, homework=:homework, homeworkDueDateTime=:homeworkDueDate, homeworkDetails=:homeworkDetails, homeworkSubmission=:homeworkSubmission, homeworkSubmissionDateOpen=:homeworkSubmissionDateOpen, homeworkSubmissionDrafts=:homeworkSubmissionDrafts, homeworkSubmissionType=:homeworkSubmissionType, homeworkSubmissionRequired=:homeworkSubmissionRequired, homeworkCrowdAssess=:homeworkCrowdAssess, homeworkCrowdAssessOtherTeachersRead=:homeworkCrowdAssessOtherTeachersRead, homeworkCrowdAssessClassmatesRead=:homeworkCrowdAssessClassmatesRead, homeworkCrowdAssessOtherStudentsRead=:homeworkCrowdAssessOtherStudentsRead, homeworkCrowdAssessSubmitterParentsRead=:homeworkCrowdAssessSubmitterParentsRead, homeworkCrowdAssessClassmatesParentsRead=:homeworkCrowdAssessClassmatesParentsRead, homeworkCrowdAssessOtherParentsRead=:homeworkCrowdAssessOtherParentsRead, viewableParents=:viewableParents, viewableStudents=:viewableStudents, twitterSearch=:twitterSearch, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							//Fail 2
							$URL=$URL . "&addReturn=fail2$params" ;
							header("Location: {$URL}");
							break ;
						}
						
						if ($partialFail==TRUE) {
							//Fail 5
							$URL=$URL . "&updateReturn=fail5$params" ;
							header("Location: {$URL}");
						}
						else {
							//Success 0
							$URL=$URL . "&updateReturn=success0$params" ;
							header("Location: {$URL}");
						}
					}
				}
			}
		}
	}
}
?>