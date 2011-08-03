<?php
/**
 *
 * ThinkUp/webapp/_lib/controller/class.LoginController.php
 *
 * Copyright (c) 2009-2011 Gina Trapani
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
 * Login Controller
 *
 * @TODO Build mechanism for redirecting user to originally-requested logged-in only page.
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2011 Gina Trapani
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class LoginController extends ThinkUpController {

    public function control() {
        $this->setPageTitle('Log in');
        $this->setViewTemplate('session.login.tpl');
        $this->view_mgr->addHelp('login', 'userguide/accounts/index');
        $this->disableCaching();
        //don't show login form if already logged in
        if ($this->isLoggedIn()) {
            $controller = new DashboardController(true);
            return $controller->go();
        } else  {
            $owner_dao = DAOFactory::getDAO('OwnerDAO');

            if (isset($_POST['Submit']) && $_POST['Submit']=='Log In' && isset($_POST['email']) &&
            isset($_POST['pwd']) ) {
                if ( $_POST['email']=='' || $_POST['pwd']=='') {
                    if ( $_POST['email']=='') {
                        $this->addErrorMessage("Email must not be empty");
                        return $this->generateView();
                    } else {
                        $this->addErrorMessage("Password must not be empty");
                        return $this->generateView();
                    }
                } else {
                    $session = new Session();
                    $user_email = $_POST['email'];
                    if (get_magic_quotes_gpc()) {
                        $user_email = stripslashes($user_email);
                    }
                    $this->addToView('email', $user_email);
                    $owner = $owner_dao->getByEmail($user_email);
                    /* The login controller needs to deal with 2 possibilites, either the user has a unique salt or
                     * the default salt
                     */
                    // Get the salt
                    $salt =  $owner_dao->getSaltByEmail($user_email);
                    // Generate wjat the password would be if they had a unique salt
                    $salted_password =  $owner_dao->generatePassword($_POST['pwd'], $salt);
                    // Check if its valid
                    $valid_pass =  $owner_dao->checkSaltedPassword($user_email, $salted_password);                       
                    if (!$owner) {
                        $this->addErrorMessage("Incorrect email");
                        return $this->generateView();
                    } elseif (!$owner->is_activated) {
                        $this->addErrorMessage("Inactive account. " . $owner->account_status. ". ".
                        '<a href="forgot.php">Reset your password.</a>');
                        return $this->generateView();
                        /*
                         * If the password hashed with and without a unique salt doesn't match the one stored in
                         * the database the password supplied is wrong 
                         */
                    } elseif (!$session->pwdCheck($_POST['pwd'], $owner_dao->getPass($user_email)) && !$valid_pass) { 
                        if ($owner->failed_logins >= 10) {
                            $owner_dao->deactivateOwner($user_email);
                            $owner_dao->setAccountStatus($user_email,
                            "Account deactivated due to too many failed logins");
                        }
                        $owner_dao->incrementFailedLogins($user_email);
                        $this->addErrorMessage("Incorrect password");
                        return $this->generateView();
                    } else {
                        // user has logged in sucessfully this sets variables in the session
                        $session->completeLogin($owner);
                        $owner_dao->updateLastLogin($user_email);
                        $owner_dao->resetFailedLogins($user_email);
                        $owner_dao->clearAccountStatus('');
                        $controller = new DashboardController(true);
                        return $controller->control();
                    }
                }
            } else  {
                return $this->generateView();
            }
        }
    }
}
