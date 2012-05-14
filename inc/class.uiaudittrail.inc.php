<?php
/**
* UI Class to explore Audit Trail
* @author WLT
**/ 
class uiaudittrail extends CommonFunctions
{
  /**
  * class Constructor
  * @param array $configStudy array of configuration variables    
  * @param instanciation $ctrlRef  
  * @author WLT
  * 
  **/ 
  function uiaudittrail($configStudy,$ctrlRef)
  {	
    CommonFunctions::__construct($configStudy,$ctrlRef);
  }

  /**
  * Main interface 
  * @return string HTML to display
  * @author WLT
  **/     
  public function getInterface()
  {
    $html = "";
    
    //Main menu
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    //Filters
    $htmlFilters = $this->getFilters();
    
    //Results
    $thmlResults = $this->getResults();

    $html = " <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.6.2.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.16.custom.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/grid.locale-en.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/jquery.jqGrid.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.jqAltBox.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/audittrail.js') . "'></SCRIPT>

              $menu

              <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                  <span class='ui-dialog-title'>Audit Trail</span>
                </div> 
                <div class='ui-dialog-content ui-widget-content'>
                $htmlFilters
                <br/>
                $thmlResults
                </div>                  
                ";
    
    return $html;
  }
  
  /**
  * @desc filters
  * @return string HTML to set the filters (sites, date)
  * @author WLT,TPI
  **/ 
  private function getFilters(){
    $html = "";
    
    //Retrieving filters values after a POST
    $selectedSites = array();
    if(isset($_POST['tblSites'])) $selectedSites = $_POST['tblSites'];
    $startDate = "";
    if(isset($_POST['startDate'])) $startDate = $_POST['startDate'];
    $endDate = "";
    if(isset($_POST['endDate'])) $endDate = $_POST['endDate'];
    
    //Get the user's sites list
    $sitesList = $this->m_ctrl->boacl()->getUserProfiles();
    
    $html .= "<div id='filterParamsAudit' class='ui-widget'>
                        <div class='ui-widget-header'>Search Audit Trail</div>
                        <form action='".$_SERVER['PHP_SELF']."?menuaction=".$this->getCurrentApp(false).".uietude.auditTrailInterface' method='post'>";
    //Sites
    $html .= "<div id='siteFilter' class='auditFilers'><h4>Select Site(s): </h4><div id='siteFilterList'>";
    foreach($sitesList as $site){
      $checked = "";
      if(in_array($site['siteId'], $selectedSites)){
        $checked = "checked='checked'";
      }
      $html .= "<div class='auditSiteSelector'><label for='cbSite".$site['siteId']."'><input id='cbSite".$site['siteId']."' type='checkbox' name='tblSites[]' value='".$site['siteId']."' $checked />".$site['siteName']."</label></div>";  
    }
    $html .= "</div></div>";

    //Dates
    $html .= "<div id='dateFilter' class='auditFilers'><h4>Choose date range: </h4>
                          <div id='selDateRange'>
                          <label>Start Date:</label><input readonly='true' id='datepickerStartDate' name='startDate' type='text' />
                          <br />
                          <br />
                          <label>End Date:</label><input readonly='true' id='datepickerEndDate' name='endDate' type='text' />
                        </div>
                        </div>";
    
    //Actions
    $html .= "<div id='auditActions' class='auditFilers'><h4>Actions: </h4>
                          <div><input type='button' value='Reset filters' onclick='resetFilters();'></div>
                          <div><input type='submit' value='Filter Audit Trail'></div>
                        </div>";
    
    $html .= "</form>";
    
    $html .= "<script>
              	$(function() {
	                 $('#datepickerStartDate').datepicker({
                			showOn: 'both',
                			buttonImage: '". $this->getCurrentApp(false) ."/templates/default/images/calendar.gif',
                			buttonImageOnly: true});
                   $('#datepickerStartDate').datepicker( 'option', 'dateFormat', 'yy-mm-dd' );		                 
	                 $('#datepickerEndDate').datepicker({
                			showOn: 'both',
                			buttonImage: '". $this->getCurrentApp(false) ."/templates/default/images/calendar.gif',
                			buttonImageOnly: true});
                   $('#datepickerEndDate').datepicker( 'option', 'dateFormat', 'yy-mm-dd');
                   
                   $('#datepickerStartDate').val('$startDate');
                   $('#datepickerEndDate').val('$endDate');
	                 
                });
                
                function resetFilters(){
                  $('#siteFilter :checked').removeAttr('checked');
                  $('#dateFilter input').val('');
                }
              </script>";
    
    return $html;
  }
  
  /**
  * @desc Results
  * @return string HTML to display the results in the audit trail
  * @author WLT,TPI
  **/ 
  private function getResults(){
    $html = "";
    
    
    //We have POSTed data to retrieve Audit Trail
    if(isset($_POST['tblSites']) && isset($_POST['startDate']) && $_POST['startDate']!='' && isset($_POST['endDate']) && $_POST['endDate']!=''){
      $html .= "<div id='auditResults'><div class='ui-widget-header'>Results</div>";
      $ATlist = $this->m_ctrl->bocdiscoo()->getAuditTrailByDate($_POST['tblSites'],$_POST['startDate'],$_POST['endDate']);  
      if(count($ATlist)==0){
        $html .= "<i>No Audit Trail found for the selected criterias</i>";
      }else{
        $html .= "
        <table><thead><tr><th>Subject</th><th>Visit</th><th>Form</th><th>Item</th><th>Value</th><th>User</th><th>Date</th></tr></thead><tbody>";
        foreach($ATlist as $AT){
          $html .= "<tr><td>" . $AT['subjectKey'] ."</td>
                         <td>" . $AT['studyEvent'] ."</td>
                         <td>" . $AT['form'] ."</td>
                         <td>" . $AT['item'] ."</td>
                         <td>" . $AT['value'] ."</td>
                         <td>" . $AT['user'] ."</td>
                         <td>" . $AT['auditDate'] ."</td></tr>";
        }
        $html .= "</tbody></table>";
      }
      $html .= "</div></div>";
    }
  
    return $html;
  }
}