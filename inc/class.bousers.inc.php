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

/*
management of sites
@author tpi
*/
class bousers extends CommonFunctions
{
  //eGroupware accounts
  var $boaccounts = NULL;
  
  //Constructor
  function __construct(&$tblConfig,$ctrlRef)
  {
      parent::__construct($tblConfig,$ctrlRef);
      $this->boaccounts =& CreateObject('admin.boaccounts');
  }
  
  /*
  * @desc Function ti add a user in the eGroupware accounts
  * @param string $login : identifiant
  * @param string $password : password
  * @param optional string $firstname : First name
  * @param optional string $lastname : Last name
  * @param optional string $email : Email
  * @param optional string $primary_group : Primary group
  * @param optional array $groups : Groups for the user
  */
  public function addUser($login, $password, $firstname="", $lastname="", $email="", $primary_group="", $groups=array()){
    //Primary group ids
    if($primary_group==""){
      $primary_group = "Default";
    }
    $primary_group_id = $GLOBALS['egw']->accounts->name2id($primary_group);
    
    //Groups ids
    $groups_ids = array();
    foreach($groups as $group){
      $groups_ids[] = $GLOBALS['egw']->accounts->name2id($group);
    }
    
    $userData = array(
        'account_type'          => 'u',
        'account_lid'           => $login,
        'account_firstname'     => $firstname,
        'account_lastname'      => $lastname,
        'account_passwd'        => $password,
        'status'                => 'A',
        'account_status'        => 'A',
        'old_loginid'           => '',
        'account_id'            => '',
        'account_primary_group' => $primary_group_id,
        'account_passwd_2'      => $password,
        'account_groups'        => $groups_ids,
        'anonymous'             => '',
        'changepassword'        => '',
        'account_permissions'   => '',
        'homedirectory'         => '',
        'loginshell'            => '',
        'account_expires_never' => 'True',
        'account_email'         => $email
    );
    
    $results = $this->boaccounts->add_user($userData);
    if($results!==true && count($results>0)){
      $str = implode("<br />", $results);
      throw new Exception("Some errors occured while trying to create a new account: <br />". $str);
    }
  }
  
  /**
   * @desc this function check if the username in alix is the same as the username in egroupware : the username in egroupware may have been change => we have to update the username in alix (that is also the userId)
   * @param $egwUserId egroupware LoginID
   * @param $userId alix userID (should be the same as $account_lid egroupware LoginID)
   * @return array[old username, new username]
   */      
  public function checkUserId($egwUserId, $userId){
    //if user has no defined profile in alix => nothing else to check
    $sql = "SELECT USERID FROM egw_alix_acl WHERE EGWUSERID='". $egwUserId ."'";
    $GLOBALS['egw']->db->query($sql);
    if(!$GLOBALS['egw']->db->next_record()){
      //nothing has to be checked
      return array($userId,$userId);
    }else{
      //get Alix userId
      $alixUserId = $GLOBALS['egw']->db->f('USERID');
      //get Egroupware LoginID
      $account_lid = $GLOBALS['egw']->accounts->id2name($egwUserId);
      if($account_lid!=$alixUserId){
        //update alix userId
        $sql = "UPDATE egw_alix_acl SET USERID='". $account_lid ."' WHERE EGWUSERID='". $egwUserId ."'";
        $GLOBALS['egw']->db->query($sql);
      }
      return array($alixUserId,$account_lid);
    }
  }


  /**
   * @desc Get eGroupware Users lists - cf egroupware/admin/inc/class.uiaccounts.inc.php - list_users
   */
  public function getUserList($type,$start,$sort,$order){

	$search_param = array(
	'type' => $type,
	'start' => $start,
	'sort' => $sort,
	'order' => $order,
	);
	
	return $GLOBALS['egw']->accounts->search($search_param);
	}
}
