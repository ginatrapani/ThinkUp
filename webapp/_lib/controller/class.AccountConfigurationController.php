<?php
/**
 *
 * ThinkUp/webapp/_lib/controller/class.AccountConfigurationController.php
 *
 * Copyright (c) 2009-2011 Terrance Shepherd, Gina Trapani
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkupapp.com).
 *
 * ThinkUp is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any
 * later version.
 *
 * ThinkUp is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with ThinkUp.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 *
 * AccountConfiguration Controller
 *
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2011 Terrance Shepherd, Gina Trapani
 * @author Terrance Shepehrd
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class AccountConfigurationController extends ThinkUpAuthController {

    /**
     * Constructor
     * @param bool $session_started
     * @return AccountConfigurationController
     */
    public function __construct($session_started=false) {
        parent::__construct($session_started);
        $this->disableCaching();
        $this->setViewTemplate('account.index.tpl');
        $this->setPageTitle('Configure Your Account');
    }

    public function authControl() {
        $webapp = Webapp::getInstance();
        $owner_dao = DAOFactory::getDAO('OwnerDAO');
        $invite_dao = DAOFactory::getDAO('InviteDAO');
        $owner = $owner_dao->getByEmail($this->getLoggedInUser());
        $this->addToView('owner', $owner);
        $this->addToView('logo_link', '');

        //proces password change
        if (isset($_POST['changepass']) && $_POST['changepass'] == 'Change password' && isset($_POST['oldpass'])
        && isset($_POST['pass1']) && isset($_POST['pass2'])) {
            $origpass = $owner_dao->getPass($this->getLoggedInUser());
            if (!$this->app_session->pwdCheck($_POST['oldpass'], $origpass)) {
                $this->addErrorMessage("Old password does not match or empty.");
            } elseif ($_POST['pass1'] != $_POST['pass2']) {
                $this->addErrorMessage("New passwords did not match. Your password has not been changed.");
            } elseif (strlen($_POST['pass1']) < 5) {
                $this->addErrorMessage("New password must be at least 5 characters. ".
                "Your password has not been changed." );
            } else {
                $cryptpass = $this->app_session->pwdcrypt($_POST['pass1']);
                $owner_dao->updatePassword($this->getLoggedInUser(), $cryptpass);
                $this->addSuccessMessage("Your password has been updated.");
            }
        }

        /*
         * The following block of code does the work of Inviting a user.
         * It first checks if the invite button has been and if the an
         * email address has been entered. 
         */
        // process invite
	    if (isset($_POST['invite']) && ( $_POST['invite'] == 'Invite' )  && isset($_POST['email']) ) {
		    // Validate email address if it is not valid show error message.
            if (!Utils::validateEmail($_POST['email'])) {
			    $this->addErrorMessage("Incorrect email. Please enter valid email address.");
            } else { 
                // Checks to see if a user with that email address exists if so show error message
    		    if ($owner_dao->doesOwnerExist($_POST['email'])) {
	    			$this->addErrorMessage("User account already exists.");
		        } else {
                    // Everything is valid, so set up for the email and invite
			        $config = Config::getInstance() ;
		    		$es = new SmartyThinkUp();
			    	$es->caching=false;
			    	$session = new Session();
			        /*
                     * The following block of code creates a a checking system for the invite processors.
                     * a  bool variable is first set to see if the creating invite_code worked.
                     * it then generates a random code and tries to add the code into the system.
                     * if adding does not work it return 0 and tries again until it is successful
                     * and returns 1
                     */
                    $did_invite_work = 0 ;
                    while ( $did_invite_work == 0 ) {
    			        $invite_code =  substr(md5(uniqid(rand(), true)), 0, 10) ;
            	        $did_invite_work = $invite_dao->addInviteCode( $invite_code ) ;
                    }   
                    
                    /*
                     * The following the block of code formats the email template. It first 
                     * assigns the host of the current request to $server. It assigns the server
                     * variable to smarty. It also assigns the generated invite_code above to smarty.
                     * It then fetches the email template with all of the assigned information
                     * returned to and stored in the varable $message. $message contains the
                     * entire body of the invite email
                     */ 	   
	    		    $server = $_SERVER['HTTP_HOST'];
                    $es->assign('server', $server );
				    $es->assign('invite_code', $invite_code );
				    $message = $es->fetch('_email.invite.tpl');

                    // Check if mail button is clicked and if it is sends the email
                    Mailer::mail($_POST['email'], "Activate Your ".$config->getValue('app_title')
                    ." Account", $message);

                    // if everything worked add the sucess banner
                    unset($_SESSION['ckey']);
                    $this->addSuccessMessage("Success! Invitation Sent.");
                }   
            }
        }   

        //process account deletion
        if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['instance_id']) &&
        is_numeric($_POST['instance_id'])) {
            $owner_instance_dao = DAOFactory::getDAO('OwnerInstanceDAO');
            $instance_dao = DAOFactory::getDAO('InstanceDAO');
            $instance = $instance_dao->get($_POST['instance_id']);
            if ( isset($instance) ) {
                if ($this->isAdmin()) {
                    //delete all owner_instances
                    $owner_instance_dao->deleteByInstance($instance->id);
                    //delete instance
                    $instance_dao->delete($instance->network_username, $instance->network);
                    $this->addSuccessMessage('Account deleted.');
                } else  {
                    if ( $owner_instance_dao->doesOwnerHaveAccess($owner, $instance) ) {
                        //delete owner instance
                        $total_deletions = $owner_instance_dao->delete($owner->id, $instance->id);
                        if ( $total_deletions > 0 ) {
                            //delete instance if no other owners have it
                            $remaining_owner_instances = $owner_instance_dao->getByInstance($instance->id);
                            if (sizeof($remaining_owner_instances) == 0 ) {
                                $instance_dao->delete($instance->network_username, $instance->network);
                            }
                            $this->addSuccessMessage('Account deleted.');
                        }
                    } else {
                        $this->addErrorMessage('Insufficient privileges.');
                    }
                }
            } else {
                $this->addErrorMessage('Instance doesn\'t exist.');
            }
        }
        $this->view_mgr->clear_all_cache();

        /* Begin plugin-specific configuration handling */
        if (isset($_GET['p'])) {
            // add config js to header
            if($this->isAdmin()) {
                $this->addHeaderJavaScript('assets/js/plugin_options.js');
            }
            $active_plugin = $_GET['p'];
            $pobj = $webapp->getPluginObject($active_plugin);
            $p = new $pobj;
            $this->addToView('body', $p->renderConfiguration($owner));
            $profiler = Profiler::getInstance();
            $profiler->clearLog();
        } else {
            $pld = DAOFactory::getDAO('PluginDAO');
            $config = Config::getInstance();
            $installed_plugins = $pld->getInstalledPlugins($config->getValue("source_root_path"));
            $this->addToView('installed_plugins', $installed_plugins);
        }
        /* End plugin-specific configuration handling */

        if ($owner->is_admin) {
            if (!isset($instance_dao)) {
                $instance_dao = DAOFactory::getDAO('InstanceDAO');
            }
            $owners = $owner_dao->getAllOwners();
            foreach ($owners as $o) {
                $instances = $instance_dao->getByOwner($o, true);
                $o->setInstances($instances);
            }
            $this->addToView('owners', $owners);
        }

        return $this->generateView();
    }
}
