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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Set variables
		$today=date("Y-m-d");
			
		//Proceed!
		//Get viewBy, date and class variables
		$params="" ;
		$viewBy=NULL ;
		if (isset($_GET["viewBy"])) {
			$viewBy=$_GET["viewBy"] ;
		}
		$subView=NULL ;
		if (isset($_GET["subView"])) {
			$subView=$_GET["subView"] ;
		}
		if ($viewBy!="date" AND $viewBy!="class") {
			$viewBy="date" ;
		}
		$gibbonCourseClassID=NULL ;
		$date=NULL ;
		$dateStamp=NULL ;
		if ($viewBy=="date") {
			$date=$_GET["date"] ;
			if (isset($_GET["dateHuman"])==TRUE) {
				$date=dateConvert($guid, $_GET["dateHuman"]) ;
			}
			if ($date=="") {
				$date=date("Y-m-d");
			}
			list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
			$dateStamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);	
			$params="&viewBy=date&date=$date" ;
		}
		else if ($viewBy=="class") {
			$class=NULL ;
			if (isset($_GET["class"])) {
				$class=$_GET["class"] ;
			}
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
			$params="&viewBy=class&class=$class&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView" ;
		}
		
		list($todayYear, $todayMonth, $todayDay)=explode('-', $today);
		$todayStamp=mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
			
		$proceed=TRUE ;
		$extra="" ;
		if ($viewBy=="class") {
			if ($gibbonCourseClassID=="") {
				$proceed=FALSE ;
			}
			else {
				try {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
					}
					else {
						$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonDepartmentID, gibbonCourse.gibbonYearGroupIDList FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND role='Teacher' ORDER BY course, class" ;
					}
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($result->rowCount()!=1) {
					$proceed=FALSE ;
				}
				else {
					$row=$result->fetch() ;
					$extra=$row["course"] . "." . $row["class"] ;
					$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
					$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
				}
			}
		}
		else {
			$extra=dateConvertBack($guid, $date) ;
		}
		
		if ($proceed==FALSE) {
			print "<div class='error'>" ;
				print "You do not have access to this page." ;
			print "</div>" ;
		}
		else {
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/planner.php$params'>Planner $extra</a> > </div><div class='trailEnd'>Add Lesson Plan</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail5") {
					$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
				}
				else if ($addReturn=="fail6") {
					$addReturnMessage="Your request failed due to an attachment error." ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage="Your request was completed successfully.You can now add another record if you wish." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			?>
	
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/planner_addProcess.php?viewBy=$viewBy&subView=$subView&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3>Basic Information</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Class *</b><br/>
						</td>
						<td class="right">
							<?
							if ($viewBy=="class") {
								?>
								<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<? print $row["course"] . "." . $row["class"] ?>" type="text" style="width: 300px">
								<input name="gibbonCourseClassID" id="gibbonCourseClassID" maxlength=20 value="<? print $row["gibbonCourseClassID"] ?>" type="hidden" style="width: 300px">
								<?
							}
							else {
								?>
								<select name="gibbonCourseClassID" id="gibbonCourseClassID" style="width: 302px">
									<?
									print "<option value='Please select...'></option>" ;
									try {
										if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
											$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
										}
										else {
											$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
											$sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
										}
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["gibbonCourseClassID"]==$gibbonCourseClassID) {
											$selected="selected" ;
										}
										print "<option $selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
									}		
									?>				
								</select>
								<script type="text/javascript">
									var gibbonCourseClassID=new LiveValidation('gibbonCourseClassID');
									gibbonCourseClassID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
								 </script>
								<?
							}
							?>
						</td>
					</tr>
				
					<tr>
						<td> 
							<b>Unit</b><br/>
						</td>
						<td class="right">
							<?
							if ($viewBy=="class") {
								?>
								<select name="gibbonUnitID" id="gibbonUnitID" style="width: 302px">
									<?
									//List gibbon units
									try {
										$dataSelect=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
										$sqlSelect="SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND running='Y' ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									$lastType="" ;
									$currentType="" ;
									print "<option value=''></option>" ;
									print "<optgroup label='--Gibbon Units--'>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$currentType=$rowSelect["type"] ;
										if ($currentType!=$lastType) {
											print "<optgroup label='--" . $currentType . "--'>" ;
										}
										print "<option value='" . $rowSelect["gibbonUnitID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										$lastType=$currentType ;
									}
									print "</optgroup>" ;
									
									//List any hooked units
									$lastType="" ;
									$currentType="" ;
									try {
										$dataHooks=array(); 
										$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name" ;
										$resultHooks=$connection2->prepare($sqlHooks);
										$resultHooks->execute($dataHooks);
									}
									catch(PDOException $e) { }
									while ($rowHooks=$resultHooks->fetch()) {
										$hookOptions=unserialize($rowHooks["options"]) ;
										if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
											try {
												$dataHookUnits=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
												$sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") WHERE " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
												$resultHookUnits=$connection2->prepare($sqlHookUnits);
												$resultHookUnits->execute($dataHookUnits);
											}
											catch(PDOException $e) { }
											while ($rowHookUnits=$resultHookUnits->fetch()) {
												$currentType=$rowHooks["name"] ;
												if ($currentType!=$lastType) {
													print "<optgroup label='--" . $currentType . "--'>" ;
												}
												print "<option value='" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "'>" . htmlPrep($rowHookUnits[$hookOptions["unitNameField"]]) . "</option>" ;
												$lastType=$currentType ;
											}										
										}
									}
									?>				
								</select>
								<?
							}
							else {
								?>
								<select name="gibbonUnitID" id="gibbonUnitID" style="width: 302px">
									<?
									//List basic and smart units
									try {
										$dataSelect=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
										$sqlSelect="SELECT * FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									$lastType="" ;
									$currentType="" ;
									print "<option value=''></option>" ;
									print "<optgroup label='--Gibbon Units--'>" ;
									while ($rowSelect=$resultSelect->fetch()) {
										$currentType=$rowSelect["type"] ;
										if ($currentType!=$lastType) {
											print "<optgroup label='--" . $currentType . "--'>" ;
										}
										print "<option class='" . $rowSelect["gibbonCourseClassID"] . "' value='" . $rowSelect["gibbonUnitID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
										$lastType=$currentType ;
									}	
									print "</optgroup>" ;	
									
									//List any hooked units
									$lastType="" ;
									$currentType="" ;
									try {
										$dataHooks=array(); 
										$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name" ;
										$resultHooks=$connection2->prepare($sqlHooks);
										$resultHooks->execute($dataHooks);
									}
									catch(PDOException $e) { }
									while ($rowHooks=$resultHooks->fetch()) {
										$hookOptions=unserialize($rowHooks["options"]) ;
										if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
											print "qhere" ;
											try {
												$dataHookUnits=array(); 
												print $sqlHookUnits="SELECT * FROM " . $hookOptions["unitTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") ORDER BY " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] ;
												$resultHookUnits=$connection2->prepare($sqlHookUnits);
												$resultHookUnits->execute($dataHookUnits);
											}
											catch(PDOException $e) { }
											while ($rowHookUnits=$resultHookUnits->fetch()) {
												$currentType=$rowHooks["name"] ;
												if ($currentType!=$lastType) {
													print "<optgroup label='--" . $currentType . "--'>" ;
												}
												print "<option class='" . $rowHookUnits[$hookOptions["classLinkJoinFieldClass"]] . "' value='" . $rowHookUnits[$hookOptions["unitIDField"]] . "-" . $rowHooks["gibbonHookID"] . "'>" . htmlPrep($rowHookUnits[$hookOptions["unitNameField"]]) . "</option>" ;
												$lastType=$currentType ;
											}										
										}
									}
									?>				
								</select>
								<script type="text/javascript">
									$("#gibbonUnitID").chainedTo("#gibbonCourseClassID");
								</script>
								<?
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=50 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Summary *</b><br/>
						</td>
						<td class="right">
							<input name="summary" id="summary" maxlength=255 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var summary=new LiveValidation('summary');
								summary.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					
					<?
					
					//Try and find the next unplanned slot for this class.
					if ($viewBy=="class") {
						//Get $_GET values
						$nextDate=NULL ;
						if (isset($_GET["date"])) {
							$nextDate=$_GET["date"] ;
						}
						$nextTimeStart=NULL ;
						if (isset($_GET["timeStart"])) {
							$nextTimeStart=$_GET["timeStart"] ;
						}
						$nextTimeEnd=NULL ;
						if (isset($_GET["timeEnd"])) {
							$nextTimeEnd=$_GET["timeEnd"] ;
						}
						
						
						if ($nextDate=="") {
							try {
								$dataNext=array("gibbonCourseClassID"=>$gibbonCourseClassID, "date"=>date("Y-m-d")); 
								$sqlNext="SELECT timeStart, timeEnd, date FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND date>=:date ORDER BY date, timestart LIMIT 0, 10" ;
								$resultNext=$connection2->prepare($sqlNext);
								$resultNext->execute($dataNext);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							$nextDate="" ;
							$nextTimeStart="" ;
							$nextTimeEnd="" ;
							while ($rowNext=$resultNext->fetch()) {
								try {
									$dataPlanner=array("date"=>$rowNext["date"], "timeStart"=>$rowNext["timeStart"], "timeEnd"=>$rowNext["timeEnd"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sqlPlanner="SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID" ;
									$resultPlanner=$connection2->prepare($sqlPlanner);
									$resultPlanner->execute($dataPlanner);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultPlanner->rowCount()==0) {
									$nextDate=$rowNext["date"] ;
									$nextTimeStart=$rowNext["timeStart"] ;
									$nextTimeEnd=$rowNext["timeEnd"] ;	
									break ;
								}
							}
						}
					}
					?>
					
					<tr>
						<td> 
							<b>Date *</b><br/>
							<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
						</td>
						<td class="right">
							<?
							if ($viewBy=="date") {
								?>
								<input readonly name="date" id="date" maxlength=10 value="<? print dateConvertBack($guid, $date) ?>" type="text" style="width: 300px">
								<?
							}
							else {
								?>
								<input name="date" id="date" maxlength=10 value="<? print dateConvertBack($guid, $nextDate) ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var date=new LiveValidation('date');
									date.add(Validate.Presence);
									date.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
								 </script>
								 <script type="text/javascript">
									$(function() {
										$( "#date" ).datepicker();
									});
								</script>
								<?
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Start Time *</b><br/>
							<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
						</td>
						<td class="right">
							<input name="timeStart" id="timeStart" maxlength=5 value="<? if (isset($nextTimeStart)) { print substr($nextTimeStart,0,5) ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeStart=new LiveValidation('timeStart');
								timeStart.add(Validate.Presence);
								timeStart.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeStart FROM gibbonPlannerEntry ORDER BY timeStart" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . substr($rowAuto["timeStart"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeStart" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>End Time *</b><br/>
							<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
						</td>
						<td class="right">
							<input name="timeEnd" id="timeEnd" maxlength=5 value="<? if (isset($nextTimeEnd)) { print substr($nextTimeEnd,0,5) ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var timeEnd=new LiveValidation('timeEnd');
								timeEnd.add(Validate.Presence);
								timeEnd.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT timeEnd FROM gibbonPlannerEntry ORDER BY timeEnd" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . substr($rowAuto["timeEnd"],0,5) . "\", " ;
										}
										?>
									];
									$( "#timeEnd" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b>Lesson Details</b>
							<? $description=getSettingByScope($connection2, "Planner", "lessonDetailsTemplate" ) ?>
							<? print getEditor($guid,  TRUE, "description", $description, 25, true, false, false) ?>
						</td>
					</tr>
					<tr id="teachersNotesRow">
						<td colspan=2> 
							<b>Teacher's Notes</b>
							<? $teachersNotes=getSettingByScope($connection2, "Planner", "teachersNotesTemplate" ) ?>
							<? print getEditor($guid,  TRUE, "teachersNotes", $teachersNotes, 25, true, false, false ) ?>
						</td>
					</tr>
					
					
					
					<script type="text/javascript">
						/* Homework Control */
						$(document).ready(function(){
							$("#homeworkDueDateRow").css("display","none");
							$("#homeworkDueDateTimeRow").css("display","none");
							$("#homeworkDetailsRow").css("display","none");
							$("#homeworkSubmissionRow").css("display","none");
							$("#homeworkSubmissionDateOpenRow").css("display","none");
							$("#homeworkSubmissionDraftsRow").css("display","none");
							$("#homeworkSubmissionTypeRow").css("display","none");
							$("#homeworkSubmissionRequiredRow").css("display","none");
							$("#homeworkCrowdAssessRow").css("display","none");
							$("#homeworkCrowdAssessControlRow").css("display","none");
							
							//Response to clicking on homework control
							$(".homework").click(function(){
								if ($('input[name=homework]:checked').val()=="Yes" ) {
									homeworkDueDate.enable();
									homeworkDetails.enable();
									$("#homeworkDueDateRow").slideDown("fast", $("#homeworkDueDateRow").css("display","table-row")); 
									$("#homeworkDueDateTimeRow").slideDown("fast", $("#homeworkDueDateTimeRow").css("display","table-row")); 
									$("#homeworkDetailsRow").slideDown("fast", $("#homeworkDetailsRow").css("display","table-row")); 
									$("#homeworkSubmissionRow").slideDown("fast", $("#homeworkSubmissionRow").css("display","table-row")); 					
								
									if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
										$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row")); 
										$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row")); 
										$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row")); 
										$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row")); 
										$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row")); 
										
										if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
											$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
											
										} else {
											$("#homeworkCrowdAssessControlRow").css("display","none");
										}
									} else {
										$("#homeworkSubmissionDateOpenRow").css("display","none");
										$("#homeworkSubmissionDraftsRow").css("display","none");
										$("#homeworkSubmissionTypeRow").css("display","none");
										$("#homeworkSubmissionRequiredRow").css("display","none");
										$("#homeworkCrowdAssessRow").css("display","none");
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								} else {
									homeworkDueDate.disable();
									homeworkDetails.disable();
									$("#homeworkDueDateRow").css("display","none");
									$("#homeworkDueDateTimeRow").css("display","none");
									$("#homeworkDetailsRow").css("display","none");
									$("#homeworkSubmissionRow").css("display","none");
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 });
							 
							 //Response to clicking on online submission control
							 $(".homeworkSubmission").click(function(){
								if ($('input[name=homeworkSubmission]:checked').val()=="Yes" ) {
									$("#homeworkSubmissionDateOpenRow").slideDown("fast", $("#homeworkSubmissionDateOpenRow").css("display","table-row")); 
									$("#homeworkSubmissionDraftsRow").slideDown("fast", $("#homeworkSubmissionDraftsRow").css("display","table-row")); 
									$("#homeworkSubmissionTypeRow").slideDown("fast", $("#homeworkSubmissionTypeRow").css("display","table-row")); 
									$("#homeworkSubmissionRequiredRow").slideDown("fast", $("#homeworkSubmissionRequiredRow").css("display","table-row")); 
									$("#homeworkCrowdAssessRow").slideDown("fast", $("#homeworkCrowdAssessRow").css("display","table-row")); 
								
									if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
										$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
										
									} else {
										$("#homeworkCrowdAssessControlRow").css("display","none");
									}
								} else {
									$("#homeworkSubmissionDateOpenRow").css("display","none");
									$("#homeworkSubmissionDraftsRow").css("display","none");
									$("#homeworkSubmissionTypeRow").css("display","none");
									$("#homeworkSubmissionRequiredRow").css("display","none");
									$("#homeworkCrowdAssessRow").css("display","none");
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 });
							 
							 //Response to clicking on crowd assessment control
							 $(".homeworkCrowdAssess").click(function(){
								if ($('input[name=homeworkCrowdAssess]:checked').val()=="Yes" ) {
									$("#homeworkCrowdAssessControlRow").slideDown("fast", $("#homeworkCrowdAssessControlRow").css("display","table-row")); 
									
								} else {
									$("#homeworkCrowdAssessControlRow").css("display","none");
								}
							 }); 
						});
					</script>
						
					<tr class='break' id="homeworkHeaderRow">
						<td colspan=2> 
							<h3>Homework</h3>
						</td>
					</tr>
					<tr id="homeworkRow">
						<td> 
							<b>Homework? *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input type="radio" name="homework" value="Yes" class="homework" /> Yes
							<input checked type="radio" name="homework" value="No" class="homework" /> No
						</td>
					</tr>
					<tr id="homeworkDueDateRow">
						<td> 
							<b>Homework Due Date *</b><br/>
							<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
						</td>
						<td class="right">
							<input name="homeworkDueDate" id="homeworkDueDate" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var homeworkDueDate=new LiveValidation('homeworkDueDate');
								homeworkDueDate.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 	homeworkDueDate.add(Validate.Presence);
								homeworkDueDate.disable();
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#homeworkDueDate" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkDueDateTimeRow">
						<td> 
							<b>Homework Due Date Time</b><br/>
							<span style="font-size: 90%"><i>Format: hh:mm (24hr)<br/></i></span>
						</td>
						<td class="right">
							<input name="homeworkDueDateTime" id="homeworkDueDateTime" maxlength=5 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var homeworkDueDateTime=new LiveValidation('homeworkDueDateTime');
								homeworkDueDateTime.add( Validate.Format, {pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm" } ); 
							 </script>
							<script type="text/javascript">
								$(function() {
									var availableTags=[
										<?
										try {
											$dataAuto=array(); 
											$sqlAuto="SELECT DISTINCT SUBSTRING(homeworkDueDateTime,12,5) AS homeworkDueTime FROM gibbonPlannerEntry ORDER BY homeworkDueDateTime" ;
											$resultAuto=$connection2->prepare($sqlAuto);
											$resultAuto->execute($dataAuto);
										}
										catch(PDOException $e) { }
										while ($rowAuto=$resultAuto->fetch()) {
											print "\"" . $rowAuto["homeworkDueTime"] . "\", " ;
										}
										?>
									];
									$( "#homeworkDueDateTime" ).autocomplete({source: availableTags});
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkDetailsRow">
						<td colspan=2> 
							<b>Homework Details *</b> 
							<? print getEditor($guid,  TRUE, "homeworkDetails", "", 25, true, true, true ) ?>
						</td>
					</tr>
					<tr id="homeworkSubmissionRow">
						<td> 
							<b>Online Submission? *</b><br/>
							<span style="font-size: 90%"><i>Allow online homework submission?</i></span>
						</td>
						<td class="right">
							<input type="radio" name="homeworkSubmission" value="Yes" class="homeworkSubmission" /> Yes
							<input checked type="radio" name="homeworkSubmission" value="No" class="homeworkSubmission" /> No
						</td>
					</tr>
					<tr id="homeworkSubmissionDateOpenRow">
						<td> 
							<b>Submission Open Date</b><br/>
							<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
						</td>
						<td class="right">
							<input name="homeworkSubmissionDateOpen" id="homeworkSubmissionDateOpen" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var homeworkSubmissionDateOpen=new LiveValidation('homeworkSubmissionDateOpen');
								homeworkSubmissionDateOpen.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#homeworkSubmissionDateOpen" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr id="homeworkSubmissionDraftsRow">
						<td> 
							<b>Drafts *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionDrafts" id="homeworkSubmissionDrafts" style="width: 302px">
								<option value="0">None</option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
							</select>
						</td>
					</tr>
					<tr id="homeworkSubmissionTypeRow">
						<td> 
							<b>Submission Type *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionType" id="homeworkSubmissionType" style="width: 302px">
								<option value="Link">Link</option>
								<option value="File">File</option>
								<option value="Link/File">Link/File</option>
							</select>
						</td>
					</tr>
					<tr id="homeworkSubmissionRequiredRow">
						<td> 
							<b>Submission Required *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="homeworkSubmissionRequired" id="homeworkSubmissionRequired" style="width: 302px">
								<option value="Optional">Optional</option>
								<option value="Compulsory">Compulsory</option>
							</select>
						</td>
					</tr>
					<? if (isActionAccessible($guid, $connection2, "/modules/Crowd Assessment/crowdAssess.php")) { ?>
						<tr id="homeworkCrowdAssessRow">
							<td> 
								<b>Crowd Assessment? *</b><br/>
								<span style="font-size: 90%"><i>Allow crowd assessment of homework?</i></span>
							</td>
							<td class="right">
								<input type="radio" name="homeworkCrowdAssess" value="Yes" class="homeworkCrowdAssess" /> Yes
								<input checked type="radio" name="homeworkCrowdAssess" value="No" class="homeworkCrowdAssess" /> No
							</td>
						</tr>
						<tr id="homeworkCrowdAssessControlRow">
							<td> 
								<b>Access Controls?</b><br/>
								<span style="font-size: 90%"><i>Decide who can do what</i></span>
							</td>
							<td class="right">
								<?
								print "<table cellspacing='0' style='width: 308px' align=right>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Role" ;
										print "</th>" ;
										print "<th style='text-align: center'>" ;
											print "Read" ;
										print "</th>" ;
									print "</tr>" ;
									print "<tr class='even'>" ;
										print "<td style='text-align: left'>" ;
											print "Class Teachers" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input checked disabled='disabled' type='checkbox' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='even'>" ;
										print "<td style='text-align: left'>" ;
											print "Submitter" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input checked disabled='disabled' type='checkbox' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='odd'>" ;
										print "<td style='text-align: left'>" ;
											print "Classmates" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessClassmatesRead' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='even'>" ;
										print "<td style='text-align: left'>" ;
											print "Other Students" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessOtherStudentsRead' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='odd'>" ;
										print "<td style='text-align: left'>" ;
											print "Other Teachers" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessOtherTeachersRead' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='even'>" ;
										print "<td style='text-align: left'>" ;
											print "Submitter's Parents" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessSubmitterParentsRead' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='odd'>" ;
										print "<td style='text-align: left'>" ;
											print "Classmates's Parents" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessClassmatesParentsRead' />" ;
										print "</td>" ;
									print "</tr>" ;
									print "<tr class='even'>" ;
										print "<td style='text-align: left'>" ;
											print "Other Parents" ;
										print "</td>" ;
										print "<td style='text-align: center'>" ;
											print "<input type='checkbox' name='homeworkCrowdAssessOtherParentsRead' />" ;
										print "</td>" ;
									print "</tr>" ;
								print "</table>" ;
								?>
							</td>
						</tr>
					<? } ?>
					
					
					<?
					//OUTCOMES
					if ($viewBy=="date") {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3>Outcomes</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<div class='warning'>
									Outcomes cannot be set when viewing the Planner by date. Use the "Choose A Class" dropdown in the sidebar to switch to a class. Make sure to save your changes first.
								</div>
							</td>
						</tr>
						<?
					}
					else {
						?>
						<tr class='break'>
							<td colspan=2> 
								<h3>Outcomes</h3>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<p>Link this lesson to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which lessons.</p>
							</td>
						</tr>
						<?
						$type="outcome" ; 
						$allowOutcomeEditing=getSettingByScope($connection2, "Planner", "allowOutcomeEditing") ;
						$categories=array() ;
						$categoryCount=0 ;
						?> 
						<style>
							#<? print $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<? print $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<? print $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<? print $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<? print $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<script>
							$(function() {
								$( "#<? print $type ?>" ).sortable({
									placeholder: "<? print $type ?>-ui-state-highlight";
									axis: 'y'
								});
							});
						</script>
						<tr>
							<td colspan=2> 
								<div class="outcome" id="outcome" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
										<div id="outcomeOuter0">
											<div style='color: #ddd; font-size: 230%; margin: 15px 0 0 6px'>Key outcomes listed here...</div>
										</div>
									</div>
								<div style='width: 100%; padding: 0px 0px 0px 0px'>
									<div class="ui-state-default_dud" style='padding: 0px; min-height: 66px'>
										<table class='blank' cellspacing='0' style='width: 100%'>
											<tr>
												<td style='width: 50%'>
													<script type="text/javascript">
														var outcomeCount=1 ;
														/* Unit type control */
														$(document).ready(function(){
															$("#new").click(function(){
															
															 });
														});
													</script>
													<select id='newOutcome' onChange='outcomeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'>Choose an outcome to add it to this lesson</option>
														<?
														$currentCategory="" ;
														$lastCategory="" ;
														$switchContents="" ;
															
														try {
															$countClause=0 ;
															$years=explode(",", $gibbonYearGroupIDList) ;
															$dataSelect=array();  
															$sqlSelect="" ;
															foreach ($years as $year) {
																$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																$sqlSelect.="(SELECT * FROM gibbonOutcome WHERE active='Y' AND scope='School' AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																$countClause++ ;
															}
															$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY category, name");
															$resultSelect->execute($dataSelect);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														print "<optgroup label='--SCHOOL OUTCOMES--'>" ;
														while ($rowSelect=$resultSelect->fetch()) {
															$currentCategory=$rowSelect["category"] ;
															if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																print "<optgroup label='--" . $currentCategory . "--'>" ;
																print "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>" ;
																$categories[$categoryCount]=$currentCategory ;
																$categoryCount++ ;
															}
															print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
															$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
															$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
															$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
															$switchContents.="outcomeCount++ ;" ;
															$switchContents.="$('#newOutcome').val('0');" ;
															$switchContents.="break;" ;
															$lastCategory=$rowSelect["category"] ;
														}
													
														if ($gibbonDepartmentID!="") {
															$currentCategory="" ;
															$lastCategory="" ;
															$currentLA="" ;
															$lastLA="" ;
															try {
																$countClause=0 ;
																$years=explode(",", $gibbonYearGroupIDList) ;
																$dataSelect=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
																$sqlSelect="" ;
																foreach ($years as $year) {
																	$dataSelect["clause" . $countClause]="%" . $year . "%" ;
																	$sqlSelect.="(SELECT gibbonOutcome.*, gibbonDepartment.name AS learningArea FROM gibbonOutcome JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE active='Y' AND scope='Learning Area' AND gibbonDepartment.gibbonDepartmentID=:gibbonDepartmentID AND gibbonYearGroupIDList LIKE :clause" . $countClause . ") UNION " ;
																	$countClause++ ;
																}
																$resultSelect=$connection2->prepare(substr($sqlSelect,0,-6) . "ORDER BY learningArea, category, name");
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															while ($rowSelect=$resultSelect->fetch()) {
																$currentCategory=$rowSelect["category"] ;
																$currentLA=$rowSelect["learningArea"] ;
																if (($currentLA!=$lastLA) AND $currentLA!="") {
																	print "<optgroup label='--" . strToUpper($currentLA) . " OUTCOMES--'>" ;
																}
																if (($currentCategory!=$lastCategory) AND $currentCategory!="") {
																	print "<optgroup label='--" . $currentCategory . "--'>" ;
																	print "<option class='$currentCategory' value='0'>Choose an outcome to add it to this lesson</option>" ;
																	$categories[$categoryCount]=$currentCategory ;
																	$categoryCount++ ;
																}
																print "<option class='all " . $rowSelect["category"] . "'   value='" . $rowSelect["gibbonOutcomeID"] . "'>" . $rowSelect["name"] . "</option>" ;
																$switchContents.="case \"" . $rowSelect["gibbonOutcomeID"] . "\": " ;
																$switchContents.="$(\"#outcome\").append('<div id=\'outcomeOuter' + outcomeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');" ;
																$switchContents.="$(\"#outcomeOuter\" + outcomeCount).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockOutcomeAjax.php\",\"type=outcome&id=\" + outcomeCount + \"&title=" . urlencode($rowSelect["name"]) . "\&category=" . urlencode($rowSelect["category"]) . "&gibbonOutcomeID=" . $rowSelect["gibbonOutcomeID"] . "&contents=" . urlencode($rowSelect["description"]) . "&allowOutcomeEditing=" . urlencode($allowOutcomeEditing) . "\") ;" ;
																$switchContents.="outcomeCount++ ;" ;
																$switchContents.="$('#newOutcome').val('0');" ;
																$switchContents.="break;" ;
																$lastCategory=$rowSelect["category"] ;
																$lastLA=$rowSelect["learningArea"] ;
															}
														}
														?>
													</select><br/>
													<?
													if (count($categories)>0) {
														?>
														<select id='outcomeFilter' style='float: none; margin-left: 3px; margin-top: 0px; width: 350px'>
															<option value='all'>View All</option>
															<?
															$categories=array_unique($categories) ;
															$categories=msort($categories) ;
															foreach ($categories AS $category) {
																print "<option value='$category'>$category</option>" ;
															}
															?>
														</select>
														<script type="text/javascript">
															$("#newOutcome").chainedTo("#outcomeFilter");
														</script>
														<?
													}
													?>
													<script type='text/javascript'>
														var <? print $type ?>Used=new Array();
														var <? print $type ?>UsedCount=0 ;
														
														function outcomeDisplayElements(number) {
															$("#<? print $type ?>Outer0").css("display", "none") ;
															if (<? print $type ?>Used.indexOf(number)<0) {
																<? print $type ?>Used[<? print $type ?>UsedCount]=number ;
																<? print $type ?>UsedCount++ ;
																switch(number) {
																	<? print $switchContents ?>
																}
															}
															else {
																alert("This element has already been selected!") ;
																$('#newOutcome').val('0');
															}
														}
													</script>
												</td>
											</tr>
										</table>
									</div>
								</div>
							</td>
						</tr>
						<?
					}
					?>
					
								
					<tr class='break'>
						<td colspan=2> 
							<h3>Markbook</h3>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Create Markbook Column?</b><br/>
							<span style="font-size: 90%"><i>Linked to this lesson by default</i></span>
						</td>
						<td class="right">
							<input type="radio" name="markbook" value="Y" id="markbook" /> Yes
							<input checked type="radio" name="markbook" value="N" id="markbook" /> No
						</td>
					</tr>
					
					
					
					<tr class='break'>
						<script type="text/javascript">
							/* Advanced Options Control */
							$(document).ready(function(){
								$("#accessRow").css("display","none");
								$("#accessRowStudents").css("display","none");
								$("#accessRowParents").css("display","none");
								$("#guestRow").css("display","none");
								$("#guestListRow").css("display","none");
								$("#guestRoleRow").css("display","none");
								$("#twitterRow").css("display","none");
								$("#twitterRowDetails").css("display","none");
								
								$(".advanced").click(function(){
									if ($('input[name=advanced]:checked').val()=="Yes" ) {
										$("#accessRow").slideDown("fast", $("#accessRow").css("display","table-row")); 
										$("#accessRowStudents").slideDown("fast", $("#accessRowStudents").css("display","table-row")); 
										$("#accessRowParents").slideDown("fast", $("#accessRowParents").css("display","table-row")); 
										$("#guestRow").slideDown("fast", $("#guestRow").css("display","table-row")); 
										$("#guestListRow").slideDown("fast", $("#guestListRow").css("display","table-row")); 
										$("#guestRoleRow").slideDown("fast", $("#guestRoleRow").css("display","table-row")); 
										$("#twitterRow").slideDown("fast", $("#twitterRow").css("display","table-row")); 
										$("#twitterRowDetails").slideDown("fast", $("#twitterRowDetails").css("display","table-row")); 
									} 
									else {
										$("#accessRow").slideUp("fast"); 
										$("#accessRowStudents").slideUp("fast"); 
										$("#accessRowParents").slideUp("fast"); 
										$("#guestRow").slideUp("fast"); 
										$("#guestListRow").slideUp("fast"); 
										$("#guestRoleRow").slideUp("fast"); 
										$("#twitterRow").slideUp("fast"); 
										$("#twitterRowDetails").slideUp("fast"); 
									}
								 });
							});
						</script>
						<td colspan=2> 
							<h3>Advanced Options</h3>
						</td>
					</tr>
					<tr>
						<td></td>
						<td class="right">
							<?
							print "<input type='checkbox' name='advanced' class='advanced' id='advanced' value='Yes' />" ;
							print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> Show Advanced Options</span>" ;
							?>
						</td>
					</tr>
					
					<tr class='break' id="accessRow">
						<td colspan=2> 
							<h4>Access</h4>
						</td>
					</tr>
					<tr id="accessRowStudents">
						<td> 
							<b>Viewable to Students *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<?
							$sharingDefaultStudents=getSettingByScope( $connection2, "Planner", "sharingDefaultStudents" ) ;
							?>
							<select name="viewableStudents" id="viewableStudents" style="width: 302px">
								<option <? if ($sharingDefaultStudents=="Y") { print "selected" ; } ?> value="Y">Y</option>
								<option <? if ($sharingDefaultStudents=="N") { print "selected" ; } ?> value="N">N</option>
							</select>
						</td>
					</tr>
					<tr id="accessRowParents">
						<td> 
							<b>Viewable to Parents *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<?
							$sharingDefaultParents=getSettingByScope( $connection2, "Planner", "sharingDefaultParents" ) ;
							?>
							<select name="viewableParents" id="viewableParents" style="width: 302px">
								<option <? if ($sharingDefaultParents=="Y") { print "selected" ; } ?> value="Y">Y</option>
								<option <? if ($sharingDefaultParents=="N") { print "selected" ; } ?> value="N">N</option>
							</select>
						</td>
					</tr>
					
					<tr class='break' id="guestRow">
						<td colspan=2> 
							<h4>Guests</h4>
						</td>
					</tr>
					<tr id="guestListRow">
						<td> 
							<b>Guest List</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td class="right">
							<select name="guests[]" id="guests[]" multiple style="width: 302px; height: 150px">
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT title, surname, preferredName, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName(htmlPrep($rowSelect["title"]), htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), htmlPrep($rowSelect["category"]), true, true). "</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr id="guestRoleRow">
						<td> 
							<b>Role</b><br/>
						</td>
						<td class="right">
							<select name="role" id="role" style="width: 302px">
								<option value="Guest Student">Guest Student</option>
								<option value="Guest Teacher">Guest Teacher</option>
								<option value="Guest Assistant">Guest Assistant</option>
								<option value="Guest Technician">Guest Technician</option>
								<option value="Guest Parent">Guest Parent</option>
								<option value="Other Guest">Other Guest</option>
							</select>
						</td>
					</tr>
					<tr class='break' id="twitterRow">
						<td colspan=2> 
							<h4>Twitter</h4>
						</td>
					</tr>
					<tr id="twitterRowDetails">
						<td> 
							<b>Integreate Twitter Content</b><br/>
							<span style="font-size: 90%"><i>Returned tweets will display results in your lesson. TAKE CARE! <a href='https://support.twitter.com/articles/71577#'>Need help?</a></i></span>
						</td>
						<td class="right">
							<input name="twitterSearch" id="twitterSearch" maxlength=255 value="" type="text" style="width: 300px">
						</td>
					</tr>
					
					
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="submit" value="<? print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
		
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $_SESSION[$guid]["gibbonPersonID"], $dateStamp, $gibbonCourseClassID ) ;
	}
}
?>