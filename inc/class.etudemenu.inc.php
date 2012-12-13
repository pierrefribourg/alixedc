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
  
class etudemenu extends CommonFunctions
{
  
  function __construct($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }
  
  /**
   * @desc Generation of the main menu of the application
   * //@param string $SiteId Centre du patient - utiliser pour l'affichage des droits de l'utilisateur connecté
   * @return string html
   */        	
  //public function getMenu($siteId=""){
  public function getMenu(){
    
    $user = $this->m_ctrl->boacl()->getUserInfo();
    $profile = $this->m_ctrl->boacl()->getUserProfile("",$siteId);
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Inclusion (CRT and INV only)
    $enroll = "";
    //access to enrolment is forbidden if items of the form are locked
    $enrollLocked = $this->m_ctrl->bolockdb()->isLocked($this->m_tblConfig['ENROL_SEOID'],$this->m_tblConfig['ENROL_SERK'],$this->m_tblConfig['ENROL_FORMOID'],$this->m_tblConfig['ENROL_FORMRK']);
    if(!$enrollLocked && $this->m_ctrl->boacl()->existUserProfileId(array("CRT","INV"))){
      $enroll = '<a id="addSubj" name="enroll" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectInterface',
                                                                         'MetaDataVersionOID' => $this->m_tblConfig["METADATAVERSION"],
                                                                         'SubjectKey' => $this->m_tblConfig['BLANK_OID'],
                                                                         'StudyEventOID' => $this->m_tblConfig['ENROL_SEOID'],
                                                                         'StudyEventRepeatKey' => $this->m_tblConfig['ENROL_SERK'],
                                                                         'FormOID' => $this->m_tblConfig['ENROL_FORMOID'],
                                                                         'FormRepeatKey' => $this->m_tblConfig['ENROL_FORMRK'])).'">
                <li class="ui-state-default"><img src="'.$this->getCurrentApp(false).'/templates/default/images/user_add.png" alt="" /><div><p>'. lang(enroll_subject) .'</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId(array("CRT","INV"))){
      $enroll = '<a name="enroll" href="#"><li class="ui-state-default inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/user_add.png" alt="" /><div><p>'. lang(enroll_subject) .'</p></div></li></a>';
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Subjects list
    $subjectsList = '<a name="subjects" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectListInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/user_manage.png" alt="" /><div><p>Subjects list</p></div></li></a>';
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Dashboard
    $dashboard = '<a name="dashboard" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/piechart2.png" alt="" /><div><p>Dashboard</p></div></li></a>';
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Documents
    $documents = '<a name="documents" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.documentsInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/folder.png" alt="" /><div><p>Documents</p></div></li></a>';
        
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Test mode
    $testmode = "";
    if(!$_SESSION[$this->getCurrentApp(false)]['forcetestmode']){
      if($_SESSION[$this->getCurrentApp(false)]['testmode']){
        $testmode = '<a name="testmode" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface','testmode'=>'false')).'"><li class="ui-state-default" id="testModeMenu" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/application_warning.png" alt="" /><div><p>Exit test mode</p></div></li></a>';
      }else{
        $testmode = '<a name="testmode" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface','testmode'=>'true')) .'"><li class="ui-state-default" id="testModeMenu" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/application_warning.png" alt="" /><div><p>Test Mode</p></div></li></a>';
      }
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Queries
    $queries = "";
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
      $queries = '<a name="queries" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.queriesInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/file_notification_warning.png" alt="" /><div><p>Queries</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $queries = '<a name="queries" href="#"><li class="ui-state-default inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt="" /><div><p>Queries</p></div></li></a>';
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Deviations
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
      $deviations = '<a name="deviations" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.deviationsInterface')).'"><li class="ui-state-default"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt="" /><div><p>Deviations</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $deviations = '<a name="deviations" href="#"><li class="ui-state-default inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt=""/><div><p>Deviations</p></div></li></a>';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Audit Trail
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
      $auditTrail = '<a name="audittrail" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.auditTrailInterface')).'"><li class="ui-state-default"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt="" /><div><p>Audit Trail</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $auditTrail = '<a name="audittrail" href="#"><li class="ui-state-default inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt=""/><div><p>Audit Trail</p></div></li></a>';
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Tools
    $toolsButtons = '<a name="tools" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface')).'"><li class="ui-state-default" id="adminMenu"><img src="'.$GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/notification_warning.png" alt="" /><div><p>Tools</p></div></li></a>';
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Button: Log out
    //$logout = '<a name="logout" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.logout')).'"><li class="ui-state-default"><img src="'.$this->getCurrentApp(false).'/templates/default/images/logout2.png" alt="" /><div><p>Logout</p></div></li></a>';
    $logout = '<a name="logout" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.logout')).'"><li class="ui-state-default button_icon_only"><img src="'.$this->getCurrentApp(false).'/templates/default/images/logout2.png" alt="" /></li></a>';
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //UserId Link
    if($GLOBALS['egw_info']['user']['apps']['admin']){
      $userLink = "index.php?menuaction=". $this->getCurrentApp(false) .".uietude.usersInterface&action=viewUser&userId=". $user['login'];
    }else{
      $userLink = "index.php?menuaction=". $this->getCurrentApp(false) .".uietude.preferencesInterface";
    }
    
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //Final HTML
    $menu = "<div id='mysite' class='divSideboxHeader'>
               <div>". $this->m_tblConfig['APP_NAME'] ."</div>
               <span id='userInfo'>
          		   [<a href='$userLink'>". $user['login'] ."</a>]
                 <span style='color: #ccc;'>". $user['fullname'] ."</span> Last login: ". date('j M Y H:i \G\M\T+', $user['lastlogin']) ."". substr(date('O',$user['lastlogin']),2,1) ."
               </span>
             </div>
             <div id='toolbar_ico'>
              <ul>
                ".$enroll."
                ".$subjectsList."
                ".$dashboard."
                ".$documents."
                ".$testmode."
                ".$queries."
                ".$deviations."
                ".$auditTrail."
                ".$toolsButtons."
                ".$logout."
              </ul>
            </div>
            <script>var userId = '".strtolower($user['login'])."'</script>
            <script language='JavaScript' src='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/debug.js',array())."'></script>";
    
    
    //HOOK => etudemenu_getMenu_htmlContent
    $this->callHook(__FUNCTION__,"htmlContent",array(&$menu,$this));
    
    return $menu;
	}	
}
