<?php
/**
 *
 * ThinkUp/webapp/plugins/instagram/model/class.InstagramPlugin.php
 *
 * Copyright (c) 2013-2016 Dimosthenis Nikoudis
 *
 * LICENSE:
 *
 * This file is part of ThinkUp (http://thinkup.com).
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
 * Instagram Plugin
 *
 * @author Dimosthenis Nikoudis <dnna[at]dnna[dot]gr>
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2013-2016 Dimosthenis Nikoudis
 */
class InstagramPlugin extends Plugin implements CrawlerPlugin {

    public function __construct($vals=null) {
        parent::__construct($vals);
        $this->folder_name = 'instagram';
        $this->addRequiredSetting('instagram_app_id');
        $this->addRequiredSetting('instagram_api_secret');
    }

    public function activate() {
    }

    public function deactivate() {
        //Pause all active instagram user profile and page instances
        $instance_dao = DAOFactory::getDAO('InstagramInstanceDAO');
        $instagram_instances = $instance_dao->getAllInstances("DESC", true, "instagram");
        foreach ($instagram_instances as $ti) {
            $instance_dao->setActive($ti->id, false);
        }
    }

    public function crawl() {
        $logger = Logger::getInstance();
        $config = Config::getInstance();
        $instance_dao = DAOFactory::getDAO('InstagramInstanceDAO');
        $owner_instance_dao = DAOFactory::getDAO('OwnerInstanceDAO');
        $owner_dao = DAOFactory::getDAO('OwnerDAO');

        $plugin_option_dao = DAOFactory::GetDAO('PluginOptionDAO');
        $options = $plugin_option_dao->getOptionsHash('instagram', true); //get cached

        $max_api_calls = isset($options['max_api_calls']) ? $options['max_api_calls']->option_value : 2500;

        $current_owner = $owner_dao->getByEmail(Session::getLoggedInUser());

        //crawl instagram user profiles and pages
        $instances = $instance_dao->getActiveInstancesStalestFirstForOwnerByNetworkNoAuthError($current_owner,
        'instagram');

        foreach ($instances as $instance) {
            $logger->setUsername(ucwords($instance->network) . ' | '.$instance->network_username );
            $logger->logUserSuccess("Starting to collect data for ".$instance->network_username."'s ".
            ucwords($instance->network), __METHOD__.','.__LINE__);

            $tokens = $owner_instance_dao->getOAuthTokens($instance->id);
            $access_token = $tokens['oauth_access_token'];

            $instance_dao->updateLastRun($instance->id);
            $dashboard_module_cacher = new DashboardModuleCacher($instance);
            try {
                /**
                 * 1. Fetch user info, media + its likes and comments.
                 * 2. Fetch user's likes.
                 * 3. Fetch user's friends, and update stale relationships.
                 * 4. Fetch user's followers, and update stale relationships.
                 * 5. Update stale friends' profiles.
                 */
                $instagram_crawler = new InstagramCrawler($instance, $access_token, $max_api_calls);
                $instagram_crawler->fetchPostsAndReplies();
                $instagram_crawler->fetchLikes();
                $instagram_crawler->fetchFriends();
                $instagram_crawler->fetchFollowers();
                $instagram_crawler->updateStaleFriendsProfiles();
            } catch (Instagram\Core\ApiAuthException $e) {
                //The access token is invalid, save in owner_instances table
                $owner_instance_dao->setAuthErrorByTokens($instance->id, $access_token, '', $e->getMessage());
                //Send email alert
                //Get owner by auth tokens first, then send to that person
                $owner_email_to_notify = $owner_instance_dao->getOwnerEmailByInstanceTokens($instance->id,
                    $access_token, '');
                $email_attempt = $this->sendInvalidOAuthEmailAlert($owner_email_to_notify, $instance->network_username);
                if ($email_attempt) {
                    $logger->logUserInfo('Sent reauth email to '.$owner_email_to_notify, __METHOD__.','.__LINE__);
                } else {
                    $logger->logInfo('Didn\'t send reauth email to '.$owner_email_to_notify, __METHOD__.','.__LINE__);
                }
                $logger->logUserError(get_class($e) . ' '.$e->getMessage(), __METHOD__.','.__LINE__);
            } catch (Exception $e) {
                $logger->logUserError(get_class($e) . ' '.$e->getMessage(), __METHOD__.','.__LINE__);
            }
            $dashboard_module_cacher->cacheDashboardModules();

            $instance_dao->save($instagram_crawler->instance, 0, $logger);
            Reporter::reportVersion($instance);
            $logger->logUserSuccess("Finished collecting data for ".$instance->network_username."'s ".
            ucwords($instance->network), __METHOD__.','.__LINE__);
        }
    }

    /**
     * Send user email alert about invalid OAuth tokens, at most one message per week.
     * In test mode, this will only write the message body to a file in the application data directory.
     * @param str $email
     * @param str $username
     */
    private function sendInvalidOAuthEmailAlert($email, $username) {
        //Determine whether or not an email about invalid tokens was sent in the past 7 days
        $should_send_email = true;
        $option_dao = DAOFactory::getDAO('OptionDAO');
        $plugin_dao = DAOFactory::getDAO('PluginDAO');

        $plugin_id = $plugin_dao->getPluginId('instagram');
        $last_email_timestamp = $option_dao->getOptionByName(OptionDAO::PLUGIN_OPTIONS.'-'.$plugin_id,
        'invalid_oauth_email_sent_timestamp');
        if (isset($last_email_timestamp)) { //option exists, a message was sent
            //a message was sent in the past week
            if ($last_email_timestamp->option_value > strtotime('-1 week') ) {
                $should_send_email = false;
            } else {
                $option_dao->updateOption($last_email_timestamp->option_id, time());
            }
        } else {
            $option_dao->insertOption(OptionDAO::PLUGIN_OPTIONS.'-'.$plugin_id,
            'invalid_oauth_email_sent_timestamp', time());
        }

        if ($should_send_email) {
            $mailer_view_mgr = new ViewManager();
            $mailer_view_mgr->caching=false;

            $mailer_view_mgr->assign('thinkup_site_url', Utils::getApplicationURL());
            $mailer_view_mgr->assign('email', $email );
            $mailer_view_mgr->assign('faceboook_user_name', $username);
            $message = $mailer_view_mgr->fetch(Utils::getPluginViewDirectory('instagram').'_email.invalidtoken.tpl');

            Mailer::mail($email, "Please re-authorize ThinkUp to access ". $username. " on Instagram", $message);
        }
    }

    public function renderConfiguration($owner) {
        $controller = new InstagramPluginConfigurationController($owner);
        return $controller->go();
    }

    public function renderInstanceConfiguration($owner, $instance_username, $instance_network) {
        return '';
    }

    public function getDashboardMenuItems($instance) {
    }

    public function getPostDetailMenuItems($post) {
    }
}
