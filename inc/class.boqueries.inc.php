<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *
    * This file is part of ALIX.                                               *
    *                                                                          *
    * ALIX is free software: you can redistribute it and/or modify             *
    * it under the terms of the GNU General Public License as published by     *
    * the Free Software Foundation, either version 3 of the License, or        *
    * (at your option) any later version.                                      *
    *                                                                          *
    * ALIX is distributed in the hope that it will be useful,                  *
    * but WITHOUT ANY WARRANTY; without even the implied warranty of           *
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
    * GNU General Public License for more details.                             *
    *                                                                          *
    * You should have received a copy of the GNU General Public License        *
    * along with ALIX.  If not, see <http://www.gnu.org/licenses/>.            *
    \**************************************************************************/
    
require_once("class.CommonFunctions.php");

class boqueries extends CommonFunctions
{   
  var $statuses = array(
      "O" => "Open",
      "A" => "Value confirmed",
      "P" => "Resolution proposed",
      "R" => "Resolved",
      "C" => "Closed",
    );
  var $types = array(
      "CM" => "Missing or badformat",
      "HC" => "Inconsistency",
      "SC" => "Information",
    );

  function boqueries(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }


/**
 * Return the status of a form based on the queries :
 * No queries opened : FROZEN
 * At least one HC opened and no missing (CM) : INCONSISTENT
 * At least one missing : PARTIAL    
 * @return string "FILLED","PARTIAL","INCONSISTENT" 
 * @author wlt 
 **/ 
  function getFormStatus($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $profileId="CRT"){
    $queryStatus = "O,P"; //For CRT and INV, we only show open queries but not the confirmed queries
    if($profileId=="CRA"){ //CRAs need to see when a query has been confirmed (acknowledged) or resolved (value modified for a manual query) (it still have to be reopened or closed)
      $queryStatus .= ",A,R";
    }
    $nbCM = $this->getQueriesCount($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID,$FormRepeatKey, "", "", "", "", $queryStatus, "Y", "QUERYTYPE='CM'");      
    if($nbCM>0){
      $frmStatus = "MISSING";
    }else{
      $nbHC = $this->getQueriesCount($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID,$FormRepeatKey, "", "", "", "", $queryStatus, "Y", "QUERYTYPE='HC'");     
      if($nbHC>0){
        $frmStatus = "INCONSISTENT";
      }else{
        $frmStatus = "FILLED";
      }
    }
    return $frmStatus;
  }

/**
 * Return the status of a Subject based on the queries :
 * No queries opened : FROZEN
 * At least one HC opened : INCONSISTENT
 * At least one missing and no HC : PARTIAL    
 * @return string "FILLED","PARTIAL","INCONSISTENT" 
 * @author wlt 
 **/ 
  function getSubjectStatus($SubjectKey){
    $nbCM = $this->getQueriesCount($SubjectKey, "", "", "","", "", "", "","","O", "Y", "QUERYTYPE='CM'");     
    if($nbCM>0){
      $subjStatus = "MISSING";
    }else{
      $nbHC = $this->getQueriesCount($SubjectKey, "", "", "","", "", "", "","","O", "Y", "QUERYTYPE='HC'");           
      if($nbHC>0){
        $subjStatus = "INCONSISTENT";
      }else{
        $subjStatus = "FILLED";
      }
    }
    return $subjStatus;
  }

  /**
  * Close queries of the specified ItemGroup
  **/
  public function closeQueries($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey){
    $this->addLog(__METHOD__."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)",INFO);
    
    //user identification
    $userId = $this->m_user;
    $siteId = $this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
    $profileId = $this->m_ctrl->boacl()->getUserProfileId("",$siteId);
    
    //get queries of this itemgroup
    $queries = $this->getQueriesList($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $ItemGroupOID, $ItemGroupRepeatKey, "", "", "", "Y");
    //for each queries
    foreach($queries as $query){
      //if the query is not already closed
      if($query["QUERYSTATUS"] != "C"){
        //close this query
        $this->closeQuery($query["QUERYID"],$userId,$profileId);
      }
    }
  }

  /**
  * Close query $queryId  
  **/
  function closeQuery($queryId,$userId,$profileId){
    $this->addLog(__METHOD__."($queryId,$userId,$profileId)",INFO);

    $sql = "UPDATE egw_alix_queries 
            SET ISLAST='N'
            WHERE QUERYID='$queryId'";
    $GLOBALS['egw']->db->query($sql);
    
    //Get the Item new value, to be stored into the queries db
    $value = "?";
    $decodedValue = "?";
    $sql = "SELECT * FROM egw_alix_queries
            WHERE QUERYID='$queryId'";
    $GLOBALS['egw']->db->query($sql);
    if($GLOBALS['egw']->db->next_record()){
      $value = $this->m_ctrl->bocdiscoo()->getValue($GLOBALS['egw']->db->f('SUBJKEY'),$GLOBALS['egw']->db->f('SEOID'),$GLOBALS['egw']->db->f('SERK'),$GLOBALS['egw']->db->f('FRMOID'),$GLOBALS['egw']->db->f('FRMRK'),$GLOBALS['egw']->db->f('IGOID'),$GLOBALS['egw']->db->f('IGRK'),$GLOBALS['egw']->db->f('ITEMOID'));
      $decodedValue = $this->m_ctrl->bocdiscoo()->getDecodedValue($GLOBALS['egw']->db->f('SUBJKEY'),$GLOBALS['egw']->db->f('SEOID'),$GLOBALS['egw']->db->f('SERK'),$GLOBALS['egw']->db->f('FRMOID'),$GLOBALS['egw']->db->f('FRMRK'),$GLOBALS['egw']->db->f('IGOID'),$GLOBALS['egw']->db->f('IGRK'),$GLOBALS['egw']->db->f('ITEMOID'));
    }
    $sql = "INSERT INTO egw_alix_queries(CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                                         LABEL,ITEMTITLE,ISMANUAL,QUERYTYPE,QUERYSTATUS,ANSWER,BYWHO,BYWHOGROUP,UPDATEDT,ISLAST,VALUE,DECODE)
              (
               SELECT CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                      LABEL,ITEMTITLE,ISMANUAL,QUERYTYPE,'C',ANSWER,'$userId','$profileId',now(),'Y','". $value ."','". addslashes($decodedValue) ."'
               FROM egw_alix_queries 
               WHERE QUERYID=$queryId
              )";                              
                                         
    $GLOBALS['egw']->db->query($sql);    
  }

  /**
  * get the existing queries list from db depending on incoming parameters
  * @return false or array(
  *                        'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
  *                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
  *                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
  *                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
  *                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
  *                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
  *                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
  *                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
  *                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
  *                        'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
  *                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
  *                        'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
  *                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
  *                        'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
  *                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
  *                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
  *                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
  *                        'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
  *                        'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
  *                        'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
  *                        'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
  *                        'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
  *                        'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
  *                       );
  * @author wlt,tpi
  **/
  function getQueriesList($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupRepeatKey="", $ItemOID="", $position="", $queryStatus="", $isLast="", $where="", $orderBy="", $limit=""){
     $tblQueries = array();
    $sql = "SELECT QUERYID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                   LABEL,ITEMTITLE,ISMANUAL,BYWHO,BYWHOGROUP,UPDATEDT,
                   QUERYTYPE,QUERYSTATUS,ANSWER,VALUE,DECODE
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";
    
    //Get sites list for current user
    $userProfiles = $this->m_ctrl->boacl()->getUserProfiles();
    $sql .= " AND SITEID IN (";
    $iProfile = 0;
    foreach($userProfiles as $userProfile){
      if($iProfile>0) $sql .= ",";
      $sql .= "'". $userProfile['siteId'] ."'";
      $iProfile++;
    }
    $sql .= ")";

    if($where!=""){
      $sql .= " AND ($where)";
    }

    if($SubjectKey!=""){
      $sql .= " AND SUBJKEY='$SubjectKey'";
    }

    if($StudyEventOID!=""){
      $sql .= " AND SEOID='$StudyEventOID'";
    }

    if($StudyEventRepeatKey!=""){
      $sql .= " AND SERK='$StudyEventRepeatKey'";
    }

    if($FormOID!=""){
      $sql .= " AND FRMOID='$FormOID'";
    }

    if($FormRepeatKey!=""){
      $sql .= " AND FRMRK='$FormRepeatKey'";
    }

    if($ItemGroupOID!=""){
      $sql .= " AND IGOID='$ItemGroupOID'";
    }

    if($ItemGroupRepeatKey!=""){
      $sql .= " AND IGRK='$ItemGroupRepeatKey'";
    }

    if($ItemOID!=""){
      $sql .= " AND ITEMOID='$ItemOID'";
    }

    if($position!=""){
      $sql .= " AND POSITION='$position'";
    }

    if($queryStatus!=""){
      $queryStatuses = explode(",", $queryStatus);
      $sqlStatus .= "";
      foreach($queryStatuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "QUERYSTATUS='$qS'";
      }
      $sql .= " AND (". $sqlStatus .")";
    }

    if($isLast!=""){
      $sql .= " AND ISLAST='$isLast'";
    }
    
    if($orderBy!=""){
      $sql .= " ORDER BY $orderBy";
    }
    
    if($limit!=""){
      $sql .= " LIMIT $limit";
    }
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblQueries[] = array(
                            'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                            'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                            'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'), //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
                            'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                            'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                            'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                           );
    }
    return $tblQueries;
  }

  /**
  * Retrieve query $queryId information
  * @param int $queryId
  * @return false or array(
  *                      'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
  *                      'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
  *                      'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
  *                      'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
  *                      'SERK'=>$GLOBALS['egw']->db->f('SERK'),
  *                      'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
  *                      'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
  *                      'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
  *                      'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
  *                      'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
  *                      'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
  *                      'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
  *                      'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
  *                      'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
  *                      'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
  *                      'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
  *                      'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
  *                      'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
  *                      'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
  *                      'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
  *                      'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
  *                      'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
  *                      'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
  *                     );
  * @author wlt,tpi
  **/
  function getQuery($queryId){
    $tblQuery = false;
    $sql = "SELECT QUERYID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                   LABEL,ITEMTITLE,ISMANUAL,BYWHO,BYWHOGROUP,UPDATEDT,
                   QUERYTYPE,QUERYSTATUS,ANSWER,VALUE,DECODE
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                  QUERYID='".$queryId."'
            LIMIT 1";
    
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblQuery = array(
                            'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                            'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                            'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'), //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
                            'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                            'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                            'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                           );
    }
    return $tblQuery;
  }

  function getQueriesCount($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupRepeatKey="", $ItemOID="", $position="", $queryStatus="", $isLast="", $where=""){
    $tblQueries = array();

    $sql = "SELECT COUNT(*) as NBQUERIES
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";
    
    //Liste des centres autorisés pour l'utilisateur courant
    if($this->m_ctrl->boacl()->existUserProfileId("SPO")==false && $SubjectKey==""){
      $userProfiles = $this->m_ctrl->boacl()->getUserProfiles();
      $sql .= " AND SITEID IN (";
      $iProfile = 0;
      foreach($userProfiles as $userProfile){
        if($iProfile>0) $sql .= ",";
        $sql .= "'". $userProfile['siteId'] ."'";
        $iProfile++;
      }
      $sql .= ")";
    }
    
    if($where!=""){
      $sql .= " AND ($where)";
    }

    if($SubjectKey!=""){
      $sql .= " AND SUBJKEY='$SubjectKey'";
    }

    if($StudyEventOID!=""){
      $sql .= " AND SEOID='$StudyEventOID'";
    }

    if($StudyEventRepeatKey!=""){
      $sql .= " AND SERK='$StudyEventRepeatKey'";
    }

    if($FormOID!=""){
      $sql .= " AND FRMOID='$FormOID'";
    }

    if($FormRepeatKey!=""){
      $sql .= " AND FRMRK='$FormRepeatKey'";
    }

    if($ItemGroupOID!=""){
      $sql .= " AND IGOID='$ItemGroupOID'";
    }

    if($ItemGroupRepeatKey!=""){
      $sql .= " AND IGRK='$ItemGroupRepeatKey'";
    }

    if($ItemOID!=""){
      $sql .= " AND ITEMOID='$ItemOID'";
    }

    if($position!=""){
      $sql .= " AND POSITION='$position'";
    }

    if($queryStatus!=""){
      $queryStatuses = explode(",", $queryStatus);
      $sqlStatus = "";
      foreach($queryStatuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "QUERYSTATUS='$qS'";
      }
      $sql .= " AND (". $sqlStatus .")";
    }

    if($isLast!=""){
      $sql .= " AND ISLAST='$isLast'";
    }
    
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    $res = false;
    if($GLOBALS['egw']->db->next_record()){
      $res = $GLOBALS['egw']->db->f('NBQUERIES');
    }
    return $res;
  }
  
  public function getStatusLabel($status){
    return $this->statuses[$status];
  }
  
  public function getTypeLabel($type){
    return $this->types[$type];
  }
  
  public function getStatuses(){
    return $this->statuses;
  }
  
  public function getTypes(){
    return $this->types;
  }

  /**
   * Update all queries status with queries detected by the CRF (automatic updates)
   * @param string $queryType : 'M' => Mandatory 'C' => Consistency (Hard ou Soft)
   **/
  function updateQueries($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $queryType, $queries)
  {
    $this->addLog(__METHOD__."($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $queryType, $queries)",INFO);
      
    //HOOK => boqueries_updateQueries_form
    $this->callHook(__FUNCTION__,"form",array($FormOID, $FormRepeatKey, $queryType, &$queries));

    $userId = $this->m_user;
    
    if($queryType=="C"){
      $where = "(QUERYTYPE='SC' or QUERYTYPE='HC')";
    }else{
      $where = "QUERYTYPE='CM'";
    }

    //Get non closed queries for asked form
    $sql = "SELECT QUERYID,IGOID,IGRK,ITEMOID,POSITION,LABEL,ITEMTITLE,ISMANUAL,QUERYTYPE,VALUE,DECODE,QUERYSTATUS
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                          SUBJKEY='$SubjectKey' AND
                          SEOID='$StudyEventOID' AND
                          SERK='$StudyEventRepeatKey' AND
                          FRMOID='$FormOID' AND
                          FRMRK='$FormRepeatKey' AND
                          ISLAST='Y' AND
                          QUERYSTATUS<>'C' AND
                          $where";
    $GLOBALS['egw']->db->query($sql);
    $tblQueryFromDB = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblQueryFromDB[] = array('ItemGroupOID' => $GLOBALS['egw']->db->f('IGOID'), 
                                'ItemGroupRepeatKey' => $GLOBALS['egw']->db->f('IGRK'),
                                'ItemOID' => $GLOBALS['egw']->db->f('ITEMOID'),
                                'Position' => $GLOBALS['egw']->db->f('POSITION'),
                                'QueryId' => $GLOBALS['egw']->db->f('QUERYID'),
                                'Description' => $GLOBALS['egw']->db->f('LABEL'),
                                'Title' => $GLOBALS['egw']->db->f('ITEMTITLE'),
                                'IsManual' => $GLOBALS['egw']->db->f('ISMANUAL'),
                                'Type' => $GLOBALS['egw']->db->f('QUERYTYPE'),
                                'QueryStatus' => $GLOBALS['egw']->db->f('QUERYSTATUS'));  
    }
    
    foreach($tblQueryFromDB as $queryDB){  
      $this->addLog(__METHOD__." : query {$queryDB['QueryId']} not closed",INFO);
      //Query is still here
      if($queryDB['Position']<0){ //Do not close automatically the manual queries
        $queryDB['Value'] = $this->m_ctrl->bocdiscoo()->getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$queryDB['ItemGroupOID'],$queryDB['ItemGroupRepeatKey'],$queryDB['ItemOID']);
        $queryDB['Decode'] = $this->m_ctrl->bocdiscoo()->getDecodedValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$queryDB['ItemGroupOID'],$queryDB['ItemGroupRepeatKey'],$queryDB['ItemOID']);
        if($queryDB['IsManual']=='N'){ //CRF can ask for an update of automatically modified queries
          $this->updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, false, 'Value changed', $queryDB);
        }else{
          if($queryDB['QueryStatus']=="O" && $queryDB['Value']!=$queryDB['Decode']){ //Manually opened queries in status OPENED are automatically set to RESOLVED if value has been modified
            //Resolve query
            $this->updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, false, 'Value changed', $queryDB);
          }
        }
      }else{
        if($queryDB['IsManual']=='N' //CRF can ask for an update of automatically modified queries
          || $queryDB['QueryStatus']=='P' //CRF can ask for an update of RESOLUTION PROPOSED queries
          ){
          $i=0;
          $bFind = false;
          while($i<count($queries) && $bFind == false){
            $query = $queries[$i];
            $i++;
            if( $query['ItemGroupOID']==$queryDB['ItemGroupOID'] && 
                $query['ItemGroupRepeatKey']==$queryDB['ItemGroupRepeatKey'] &&
                $query['ItemOID']==$queryDB['ItemOID'] &&
                $query['Position']==$queryDB['Position']
                ){
                  //Found !
                  $bFind = true;
                  $this->addLog(__METHOD__." : query {$queryDB['QueryId']} still here",INFO);
                }
          }
          if($bFind==false){
            //Close query
            $siteId = $this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
            $profileId = $this->m_ctrl->boacl()->getUserProfileId("",$siteId);
            $this->closeQuery($queryDB['QueryId'],$userId,$profileId);
          }
         }else{
           //nothing: the CRF do no touch these manually modified queries
         }  
      } 
    }
    
    foreach($queries as $query){
      $this->updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, false, '', $query);
    }   
  }

  /**
  * Add or update query $query
  * @param array $query query, with fields : array("ItemOID" => '',
  *                                                "ItemGroupOID" => '',
  *                                                "ItemGroupRepeatKey" => '',
  *                                                "Description" => '',
  *                                                "Title" => ''
  *                                                'Position' => '' (RangeCheck position into the ItemDef)
  *                                                'Type' =>  'CM' (CheckMandatory),
  *                                                           'CS' (CheckSoft)
  *                                                           'CH' (CheckHard),
  *                                                'Value' => '',
  *                                                'Decode' => '')
  * @return false or array(
  *                        'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
  *                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
  *                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
  *                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
  *                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
  *                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
  *                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
  *                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
  *                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
  *                        'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
  *                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
  *                        'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
  *                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
  *                        'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
  *                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
  *                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
  *                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
  *                        'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
  *                        'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
  *                        'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
  *                        'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
  *                        'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
  *                        'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
  *                       );
  * @author wlt,tpi
  **/
  function updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $isManual, $answer, $query, $queryStatus='O')
  {
    $this->addLog(__METHOD__."($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $isManual, $answer, {$query['ItemGroupOID']}/{$query['ItemGroupRepeatKey']}/{$query['Position']}/{$query['ItemOID']}, $queryStatus)",INFO);
  
    if($isManual){
      $cIsManual = "Y";
    }else{
      $cIsManual = "N";
    }

    //Is there any change ?
    $sql = "SELECT LABEL,ITEMTITLE,ISMANUAL,ANSWER,QUERYSTATUS,QUERYTYPE,POSITION,VALUE,CONTEXTKEY
              FROM egw_alix_queries
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                            SUBJKEY='$SubjectKey' AND
                            SEOID='$StudyEventOID' AND
                            SERK='$StudyEventRepeatKey' AND
                            FRMOID='$FormOID' AND
                            FRMRK='$FormRepeatKey' AND
                            IGOID='{$query['ItemGroupOID']}' AND
                            IGRK='{$query['ItemGroupRepeatKey']}' AND
                            POSITION='{$query['Position']}' AND
                            ITEMOID='{$query['ItemOID']}' AND
                            ISLAST='Y'";
    $GLOBALS['egw']->db->query($sql);
    $hasModif = false;
    
    if($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw']->db->f('LABEL') != $query['Description'] ||
        $GLOBALS['egw']->db->f('ITEMTITLE') != $query['Title'] ||
        $GLOBALS['egw']->db->f('ANSWER') != $answer ||
        $GLOBALS['egw']->db->f('QUERYSTATUS') != $queryStatus ||
        $GLOBALS['egw']->db->f('VALUE') != $query['Value'] ||
        $GLOBALS['egw']->db->f('CONTEXTKEY') != $query['ContextKey']){
          
           //M : query OPEN manually (ARC), A : query OPEN automatically (CRF)
          $QUERYORIGIN = ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A');
          
          if($isManual){ //Every manual modifications are allowed
            //@TODO : check rights here. Rights are already checks in javascript.
            $hasModif = true;
          }else{ //Handle of automatic query update (CRF)
            if($GLOBALS['egw']->db->f('VALUE') != $query['Value'] || $GLOBALS['egw']->db->f('CONTEXTKEY') != $query['ContextKey']){ 
              //CRF can update a query only if the value have been updated
              /*
              * CRF can update :
              *              OPEN queries 
              *               - and set to RESOLVED manual queries
              *              les queries RESOLUTION PROPOSED
              *               - if new stasus is CLOSED
              *               - description only if it was updated              
              *              CONFIRMED queries  if new status is OPEN
              *              CLOSED queries
              *               - automatically closed
              *               - automatically opened
              */
              if($GLOBALS['egw']->db->f('QUERYSTATUS') == "O"){
                $hasModif = true;
                if($QUERYORIGIN == "M"){
                  $queryStatus = "R";
                }
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "P"){
                if($queryStatus == "C"){
                  $hasModif = true;
                }elseif($GLOBALS['egw']->db->f('LABEL') != $query['Description']){
                  $isManual = $GLOBALS['egw']->db->f('ISMANUAL');
                  $answer = $GLOBALS['egw']->db->f('ANSWER');
                  $query['Type'] = $GLOBALS['egw']->db->f('QUERYTYPE');
                  $queryStatus = $GLOBALS['egw']->db->f('QUERYSTATUS');
                  $hasModif = true;
                }
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "A" && $queryStatus == "O"){
                $hasModif = true;
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "C"){
                if($GLOBALS['egw']->db->f('ISMANUAL') != "Y"){
                  $hasModif = true;
                }elseif($QUERYORIGIN == "A"){
                  $hasModif = true;
                }
              }
            }
          }
      }
    }else{
      //No previous record, query will be inserted
      $hasModif = true;
    }

    if($hasModif){    
      $userId = $this->m_user; 
      $siteId = $this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");

      $sql = "UPDATE egw_alix_queries 
              SET ISLAST='N'
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                    SUBJKEY='$SubjectKey' AND
                    SEOID='$StudyEventOID' AND
                    SERK='$StudyEventRepeatKey' AND
                    FRMOID='$FormOID' AND
                    FRMRK='$FormRepeatKey' AND
                    IGOID='".$query['ItemGroupOID']."' AND
                    IGRK='".$query['ItemGroupRepeatKey']."' AND
                    POSITION='".$query['Position']."' AND
                    ITEMOID='".$query['ItemOID']."'";
      $this->addLog(__METHOD__." : hasModif = true => mise à jour de la query  {$query['ItemGroupOID']}/{$query['ItemGroupRepeatKey']}/{$query['Position']}/{$query['ItemOID']} ",INFO);
      $GLOBALS['egw']->db->query($sql);
  
      $sql = "INSERT INTO egw_alix_queries(CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,
                                            POSITION,ITEMOID,LABEL,ITEMTITLE,ISMANUAL,BYWHO,UPDATEDT,
                                            QUERYTYPE,QUERYSTATUS,ANSWER,ISLAST,VALUE,DECODE,CONTEXTKEY)
             VALUES('".$this->getCurrentApp(true)."','$siteId','$SubjectKey','$StudyEventOID','$StudyEventRepeatKey','$FormOID','$FormRepeatKey','".$query['ItemGroupOID']."','".$query['ItemGroupRepeatKey']."',
                    '".$query['Position']."','".$query['ItemOID']."','".addslashes($query['Description'])."','".addslashes($query['Title'])."','$cIsManual','$userId',now(),
                    '".$query['Type']."','".$queryStatus."','".addslashes($answer)."','Y','".$query['Value']."','".addslashes($query['Decode'])."','".$query['ContextKey']."')";
      $GLOBALS['egw']->db->query($sql);
      
      return array(
                    'QUERYID'=>$GLOBALS['egw']->db->get_last_insert_id("egw_alix_queries", "QUERYID"),
                    'SITEID'=>$siteId,
                    'SUBJKEY'=>$SubjectKey,
                    'SEOID'=>$StudyEventOID,
                    'SERK'=>$StudyEventRepeatKey,
                    'FRMOID'=>$FormOID,
                    'FRMRK'=>$FormRepeatKey,
                    'IGOID'=>$query['ItemGroupOID'],
                    'IGRK'=>$query['ItemGroupRepeatKey'],
                    'POSITION'=>$query['Position'],
                    'ITEMOID'=>$query['ItemOID'],
                    'LABEL'=>$query['Description'],
                    'ITEMTITLE'=>$query['Title'],
                    'ISMANUAL'=>$cIsManual,
                    'BYWHO'=>$userId,
                    'UPDATEDT'=>date("Y-m-d H:i:s"),
                    'QUERYTYPE'=>$query['Type'],
                    'QUERYSTATUS'=>$queryStatus,
                    'QUERYORIGIN'=> ($query['Position']<0 ? 'M' : 'A'), //M : query OPEN manually (ARC), A : query OPEN automatically (CRF)
                    'ANSWER'=>$answer,
                    'VALUE'=>$query['Value'],
                    'DECODE'=>$query['Decode']
                   );
    }
    else{
      return false;
    }            
  }
  
  /**
   * Delete queries of Subject $SubjectKey - only used from uidbadmin
   *
   **/        
  public function deleteQueries($SubjectKey){
    $this->addLog(__METHOD__ . " SubjectKey='$SubjectKey'",INFO);
    if($SubjectKey==""){
      $this->addLog("boqueries->deleteQueries SubjectKey is empty",FATAL);
    }
    $sql = "DELETE FROM egw_alix_queries WHERE SUBJKEY='$SubjectKey'";
    $GLOBALS['egw']->db->query($sql);
  }
  
}