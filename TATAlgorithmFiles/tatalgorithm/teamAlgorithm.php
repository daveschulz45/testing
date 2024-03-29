<?php
/* created by Morgan Kovacic */

/* ------------------------------------------------------------------------*/
/* Functions to display debug messages while testing                       */
/* ------------------------------------------------------------------------*/

/* Display data from the studentsuitability table */
function dbg_StudentSuitability($link, $message) {
  echo("<br><br>".$message);
  echo("<br>For a student to be considered for allocation he/she must have a studentsubjectresult record with a SubjectID that matches. This indicates the student is enrolled in this subject but not yet marked.");
  $stmt = mysqli_prepare($link, "SELECT SuitabilityScore, SSR_StudentID, ProjectID FROM studentsuitability ORDER BY SuitabilitySCore DESC, SSR_StudentID, ProjectID ");
  $stmt->execute();
  $result = $stmt->get_result();
 if ($result != "") {
    $numcols = mysqli_num_fields($result);
    print "<table border = 1>\n";
    print "<tr>";
    print "<td><b>"."SuitabilityScore"."</b></td>";
    print "<td><b>"."SSR_StudentID"."</b></td>";
    print "<td><b>"."ProjectID"."</b></td>";
    print"</tr>\n";
    while ($row = mysqli_fetch_array($result)) {

      for ($i = 0; $i <$numcols; $i++) {
          print "<td>".$row[$i]."</td>";
      }
      print "</tr>\n";
    }
    print "</table>\n";
  }
  echo("<br>");
}

/* Display data from the projectcounts table */
function dbg_ProjectCounts($link) {
  echo("<br>ProjectCounts table created (no allocations yet):");
  $stmt = mysqli_prepare($link, "SELECT PC_ProjectID, PC_AllocatedCount, PC_MaleCount, PC_FemaleCount FROM projectcounts ORDER BY PC_ProjectID");
  $stmt->execute();
  $result = $stmt->get_result();
 if ($result != "") {
    $numcols = mysqli_num_fields($result);
    print "<table border = 1>\n";
    print "<tr>";
    print "<td><b>"."PC_ProjectID"."</b></td>";
    print "<td><b>"."PC_AllocatedCount"."</b></td>";
    print "<td><b>"."PC_MaleCount"."</b></td>";
    print "<td><b>"."PC_FemaleCount"."</b></td>";
    print"</tr>\n";
    while ($row = mysqli_fetch_array($result)) {

      for ($i = 0; $i <$numcols; $i++) {
          print "<td>".$row[$i]."</td>";
      }
      print "</tr>\n";
    }
    print "</table>\n";
  }
  echo("<br>");
}

/* Display data from the teammebers table */
function dbg_TeamMembers($link) {
  echo("<br>Teammembers table created by algorithm:");
  $stmt = mysqli_prepare($link, "SELECT TM_Number, TM_ProjectID, project.name, TM_StudentID, student.Last_Name FROM teammember, project, student WHERE TM_ProjectID = project.ProjectID and TM_StudentID = student.StudentID  ORDER BY TM_ProjectID, TM_StudentID");
  $stmt->execute();
  $result = $stmt->get_result();
 if ($result != "") {
    $numcols = mysqli_num_fields($result);
    print "<table border = 1>\n";
    print "<tr>";
    print "<td><b>"."TM_Number"."</b></td>";
    print "<td><b>"."TM_ProjectID"."</b></td>";
    print "<td><b>"."Project name"."</b></td>";
    print "<td><b>"."TM_StudentID"."</b></td>";
    print "<td><b>"."Student Last name"."</b></td>";
    print"</tr>\n";
    while ($row = mysqli_fetch_array($result)) {

      for ($i = 0; $i <$numcols; $i++) {
          print "<td>".$row[$i]."</td>";
      }
      print "</tr>\n";
    }
    print "</table>\n";
  }
  echo("<br>");
}
/* ------------------------------------------------------------------------*/

/* ------------------------------------------------------------------------*/
/* Team allocation algorithm functions                                     */
/* ------------------------------------------------------------------------*/

/* Phase 1 - Create temporary tables */
function createTempTables($link, $CourseID, $SubjectID) {
  // Create temporary studentsuitability table
  $stmt = mysqli_prepare($link, "DROP TABLE IF EXISTS studentsuitability");
  $stmt->execute();

  if ($stmt = mysqli_prepare($link, "CREATE TABLE studentsuitability AS SELECT SSR_StudentID, ProjectID, 0 AS SuitabilityScore FROM studentsubjectresult, project WHERE studentsubjectresult.SSR_SubjectID IN ( SELECT SubjectID FROM subject WHERE subject.S_CourseID = ? AND subject.SubjectID = ? ) and P_SubjectID = ?;")) {
    $stmt->bind_param('iii', $CourseID, $SubjectID, $SubjectID);
    $stmt->execute();
  }

  $stmt = mysqli_prepare($link, "ALTER TABLE studentsuitability ADD SuitabilityID int PRIMARY KEY AUTO_INCREMENT");
  $stmt->execute();

  // Create temporary projectcounts table
  $stmt = mysqli_prepare($link, "DROP TABLE IF EXISTS projectcounts");
  $stmt->execute();

  if ($stmt = mysqli_prepare($link, "CREATE TABLE projectcounts ( PC_ID INT NOT NULL AUTO_INCREMENT , PC_ProjectID INT NOT NULL , PC_AllocatedCount INT NOT NULL , PC_MaleCount INT NOT NULL , PC_FemaleCount INT NOT NULL , PRIMARY KEY (PC_ID)) ENGINE = MyISAM")) {
    $stmt->execute();
  }

  // Put ProjectIDs into projectcounts table
  if ($stmt = mysqli_prepare($link, "INSERT INTO projectcounts(PC_ProjectID, PC_AllocatedCount, PC_MaleCount, PC_FemaleCount) SELECT DISTINCT studentsuitability.ProjectID, 0, 0, 0 FROM studentsuitability")) {
    $stmt->execute();
  }
}

/* Phase 1 - Calculate scores and update studentsuitability table */
function calcScores($link) {
    /* For each studentID, projectID combination in the student suitability
       table, match the project's required skills to the student's skills
       and calculate the points. */
   $stmt = mysqli_prepare($link, "SELECT SSR_StudentID, ProjectID FROM studentsuitability ORDER BY SSR_StudentID, ProjectID");
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result != "") {
    /* get the current studentID and projectID */
    while ($row = mysqli_fetch_array($result)) {
      $studentID = $row[0];
      $projectID = $row[1];
      $projectScore = 0;
      /* find the matching project/student skills */
      if ($stmt2 = mysqli_prepare($link, "SELECT DISTINCT projectskill.PS_Rating, projectskill.PS_ProjectID, projectskill.PS_SkillID, studentskill.SS_Rating FROM projectskill, studentskill WHERE projectskill.PS_ProjectID = ? AND studentskill.SS_StudentID = ? AND projectskill.PS_SkillID IN ( SELECT studentskill.SS_SkillID FROM studentskill WHERE studentskill.SS_StudentID = ? )")) {
        $stmt2->bind_param('iii', $projectID, $studentID, $studentID);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        /* loop through the matching skills and update the score */
        if ($result2 != "") {
          while ($row2 = mysqli_fetch_array($result2)) {
            if ($row2[3] >= $row[0])
              $projectScore += 2;
            else
              $projectScore += 1;
          }
        }
      }

      /* add points for GPA if required */
      $TakeGPAIntoAccount = 0;
      $GPALevel = 0;
      if ($stmt3 = mysqli_prepare($link, "SELECT TakeGPAIntoAccount, GPALevel FROM project WHERE ProjectID = ?")) {
        $stmt3->bind_param('i', $projectID);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        if ($result3 != "") {
          while ($row3 = mysqli_fetch_array($result3)) {
            $TakeGPAIntoAccount = $row3[0];
            $GPALevel = $row3[1];
            break;
          }
        }
      }
      if ($TakeGPAIntoAccount != 0) {
        $StudentGPA = 0;
        if ($stmt4 = mysqli_prepare($link, "SELECT StudentGPA from student where StudentID = ?")) {
          $stmt4->bind_param('i', $studentID);
          $stmt4->execute();
          $result4 = $stmt4->get_result();
          if ($result4 != "") {
            while ($row4 = mysqli_fetch_array($result4)) {
              $StudentGPA = $row4[0];
              break;
            }
          }
          if ($StudentGPA >= $GPALevel)
            $projectScore += 1;
        }
      }

      /* add points if the student's project preferences match this project */
      if ($stmt5 = mysqli_prepare($link, "SELECT SPP_Preference FROM studentprojectpreference WHERE SPP_StudentID = ? AND SPP_ProjectID = ?")) {
        $stmt5->bind_param('ii', $studentID, $projectID);
        $stmt5->execute();
        $result5 = $stmt5->get_result();
        if ($result5 != "") {
          while ($row5 = mysqli_fetch_array($result5)) {
            $SPP_Preference = $row5[0];
            if ($SPP_Preference == 1)
              $projectScore += 3;
            else if ($SPP_Preference == 2)
              $projectScore += 2;
            else if ($SPP_Preference == 1)
              $projectScore += 1;
            break;
          }
        }
      }

      /* update the points to the studentsuitability table */
      if ($stmt6 = mysqli_prepare($link, "UPDATE studentsuitability SET SuitabilityScore = ? WHERE SSR_StudentID = ? AND ProjectID = ?")) {
        $stmt6->bind_param('iii', $projectScore, $studentID, $projectID);
        $stmt6->execute();
        $result6 = $stmt6->get_result();
        if ($result6 != "")
          echo("<br>Error updating studentsuitability table");
      }
    }
  }
}

/* For Phase 2 - allocate the Minimum Required number of Students to each Project
   For Phase 3 - allocate up to the Maximum Required number of Students. */
function allocateStudents($link, $phase) {
  /* First delete any records that may have previously existed in the
     teammember table for the students that are about to be allocated. */
  if ($stmt = mysqli_prepare($link, "DELETE FROM teammember WHERE TM_StudentID in ( SELECT DISTINCT SSR_StudentID FROM studentsuitability )")) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result != "")
      echo("<br>Error deleting students from teammember table");
  }

  /* loop through the studentsuitability table */
loop:
  $stmt = mysqli_prepare($link, "SELECT SSR_StudentID, ProjectID FROM studentsuitability ORDER BY SuitabilitySCore DESC, SSR_StudentID, ProjectID ");
  $stmt->execute();
  $result = $stmt->get_result();
  $row_cnt = mysqli_num_rows($result);

  /* Return if no students left in studentsuitability table */
  if ($row_cnt < 1)
    return;

  if ($result != "") {
    while ($row = mysqli_fetch_array($result)) {
      $SSR_StudentID = $row[0];
      $ProjectID = $row[1];
      /* If the allocated count in the projectcounts table already equals
         the minimum count in the project table then skip to the next record
         in the studentsuitability table */
      $StudentMin = 0;
      $StudentMax = 0;
      $PC_AllocateCount = 0;
      if ($stmt2 = mysqli_prepare($link, "SELECT StudentMin, StudentMax FROM project WHERE ProjectID = ?")) {
        $stmt2->bind_param('i', $ProjectID);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($result2 != "") {
          while ($row2 = mysqli_fetch_array($result2)) {
            $StudentMin = $row2[0];
            $StudentMax = $row2[0];
            break;
          }
        }
      }
      if ($stmt3 = mysqli_prepare($link, "SELECT PC_AllocatedCount FROM projectcounts WHERE PC_ProjectID = ?")) {
        $stmt3->bind_param('i', $ProjectID);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        if ($result3 != "") {
          while ($row3 = mysqli_fetch_array($result3)) {
            $PC_AllocatedCount = $row3[0];
            break;
          }
        }
      }

      /* If in phase 2 and we have allocated the minimum required number
         of students for this project */
      if (($PC_AllocatedCount >= $StudentMin) and ($phase == 2))
        continue;

      /* If in phase 3 and we have allocated the maximum required number
         of students for this project */
      if (($PC_AllocatedCount >= $StudentMax) and ($phase == 3)) {
        /* Delete any records with this projectID from the studentsuitability
           table because the project is now full */
      if ($stmt3 = mysqli_prepare($link, "DELETE FROM studentsuitability WHERE ProjectID = ?")) {
        $stmt3->bind_param('i', $ProjectID);
        $stmt3->execute();
        $result3 = $stmt3->get_result();
        if ($result3 != "")
          echo("<br>Error deleting project from studentsuitability table");
        }
        goto loop;
      }

      /* Allocate the Student to the Project in the teammember table */
      if ($stmt4 = mysqli_prepare($link, "INSERT INTO teammember (TM_StudentID, TM_ProjectID) VALUES (?, ?);")) {
        $stmt4->bind_param('ii', $SSR_StudentID, $ProjectID);
        $stmt4->execute();
        $result4 = $stmt4->get_result();
        if ($result4 != "")
          echo("<br>Error inserting into teammember table");
      }
      /* The TM_ProjectID is used as the TM_Number when creating teammember
         records */
      if ($stmt4 = mysqli_prepare($link, "UPDATE teammember SET TM_Number = TM_ProjectID WHERE TM_StudentID = ? AND TM_ProjectID = ?")) {
        $stmt4->bind_param('ii', $SSR_StudentID, $ProjectID);
        $stmt4->execute();
        $result4 = $stmt4->get_result();
        if ($result4 != "")
          echo("<br>Error updating TM_Number in teammember table");
      }

      /* Increment the AllocatedCount field in the projectcounts table */
      $PC_AllocatedCount += 1;
      if ($stmt5 = mysqli_prepare($link, "UPDATE projectcounts SET PC_AllocatedCount = ? WHERE PC_ProjectID = ?")) {
        $stmt5->bind_param('ii', $PC_AllocatedCount, $ProjectID);
        $stmt5->execute();
        $result5 = $stmt5->get_result();
        if ($result5 != "")
          echo("<br>Error updating projectcounts table");
      }
      
      /* Now delete any other records for the allocated student from the
        studentsuitability table */
      if ($stmt6 = mysqli_prepare($link, "DELETE FROM studentsuitability WHERE SSR_StudentID = ?")) {
        $stmt6->bind_param('i', $SSR_StudentID);
        $stmt6->execute();
        $result6 = $stmt6->get_result();
        if ($result6 != "")
          echo("<br>Error deleting student from studentsuitability table");
      }
      goto loop;
    }
    /* If we have looped through all the table without allocating any students
       it means all the projects have been allocated the minimum number of
       students and we can return from this function. */
  }
}
/* -----------------------------------------------------------------------*/


/* -----------------------------------------------------------------------*/
/* Main function that is called from user interface submit button         */
/* -----------------------------------------------------------------------*/
function teamAlgorithm($CourseID, $SubjectID) {
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  // Connect to database
  $db_user = "tat";
  $db_password = "tat";
  $db_host = "localhost";
  $db_name = "tatalgorithm";

  $link = mysqli_connect($db_host, $db_user, $db_password, $db_name) or
    die(mysqli_error($link));

  /* Phase 1 - Create temporary tables */
  createTempTables($link, $CourseID, $SubjectID);
  dbg_StudentSuitability($link, "StudentSuitability table created (BEFORE scores are calculated):");
  dbg_ProjectCounts($link);

  /* Phase 1 - Calculate scores and update studentsuitability table */
  calcScores($link);
  dbg_StudentSuitability($link, "StudentSuitability table AFTER scores are calculated:");

  /* Phase 2 - allocate the Minimum Required number of Students to each Project */
  allocateStudents($link, 2);

  /* Phase 3 - allocate up to the Maximum Required number of Students */
  allocateStudents($link, 3);
  dbg_TeamMembers($link);

  mysqli_close($link);
}
/* ------------------------------------------------------------------------*/
?>