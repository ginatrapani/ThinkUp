<?php
/*
 Plugin Name: Time Spent
 Description: How much time has a user spent on a given network
 */

/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/timespent.pho
 *
 * Copyright (c) 2014 Chris Moyer
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
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2014 Chris Moyer
 * @author Chris Moyer <chris[at]inarow[dot]net>
 */

class TimeSpentInsight extends InsightPluginParent implements InsightPlugin {
    /**
     * Slug for this insight
     */
    var $slug = 'time_spent';

    public function generateInsight(Instance $instance, User $user, $last_week_of_posts, $number_days) {
        parent::generateInsight($instance, $user, $last_week_of_posts, $number_days);
        $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);

        $firstrun = !$this->insight_dao->doesInsightExist($this->slug, $instance->id);

        if ($instance->network == 'facebook' &&
           ($firstrun || $this->shouldGenerateMonthlyInsight($this->slug, $instance, 'today', $regenerate=false, 3))) {
            if ($firstrun) {
                $number_of_posts = count($last_week_of_posts);
                $period = 'week';
            } else {
                $post_dao = DAOFactory::getDAO('PostDAO');
                $number_of_posts = $post_dao->countAllPostsByUserSinceDaysAgo($instance->network_user_id,
                    $instance->network, date('t'));
                $period = 'month';
            }
            if ($number_of_posts >= 4) { // at least a minute
                $insight = new Insight();
                $insight->slug = $this->slug;
                $insight->emphasis = Insight::EMPHASIS_MED;
                $insight->filename = basename(__FILE__, ".php");
                $insight->instance_id = $instance->id;
                $insight->date = $this->insight_date;
                $insight->text = $this->postsToInsightText($number_of_posts);
                $insight->headline = $this->username . " " . $this->terms->getVerb('posted')
                    . " " . $number_of_posts." time".($number_of_posts==1?'':'s')." in the past $period.";
                $this->insight_dao->insertInsight($insight);
            }
        }

        if ($instance->network == 'twitter') {
            $number_of_posts = $user->post_count;
            $hundreds_of_posts = intval($number_of_posts / 100);
            // At least a minute of posts and either 100 or the first run.
            if ($number_of_posts > 4 && ($archived_posts_in_hundreds > 0 || $firstrun)) {
                $baseline_slug = "time_spent_".$hundreds_of_posts;
                $baseline_dao = DAOFactory::getDAO('InsightBaselineDAO');
                if (!$baseline_dao->doesInsightBaselineExist($baseline_slug, $instance->id)) {
                    if ($hundreds_of_posts) {
                        $baseline_dao->insertInsightBaseline($baseline_slug, $instance->id, $hundreds_of_posts);
                    }

                    $insight = new Insight();
                    $insight->slug = $this->slug;
                    $insight->emphasis = Insight::EMPHASIS_MED;
                    $insight->filename = basename(__FILE__, ".php");
                    $insight->instance_id = $instance->id;
                    $insight->date = $this->insight_date;
                    $insight->text = $this->postsToInsightText($number_of_posts);
                    $insight->headline = $this->username . " tweeted ".$number_of_posts
                        ." time".($number_of_posts==1?'':'s').".";
                    $this->insight_dao->insertInsight($insight);
                }
            }
        }

        $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
    }

    /**
     * For a given number of posts, generate insight text
     *
     * @param int $number_of_posts How many posts
     * @return str The insight text
     */
    private function postsToInsightText($number_of_posts) {
        $posting_seconds = ($number_of_posts * 15);

        $insight_text = 'That\'s over<strong>';
        $posting_time = self::secondsToTime($posting_seconds);
        if ($posting_time["d"]) {
            $insight_text .= ' ' . $posting_time["d"] . ' day'.(($posting_time["d"]>1)?'s':'');
        }
        if ($posting_time["h"]) {
            $insight_text .= ' ' . $posting_time["h"] . ' hour'.(($posting_time["h"]>1)?'s':'');
        }
        if ($posting_time["m"]) {
            $insight_text .= ' ' . $posting_time["m"] . ' minute'.(($posting_time["m"]>1)?'s':'');
        }

        $insight_text .= '</strong> of '. $this->username.'\'s life.';

        return $insight_text;
    }

    /**
     * Convert seconds to larger units of time
     *
     * @param int $inputSeconds How many seconds
     * @return array Units of time array
     */
    private function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour  = 60 * $secondsInAMinute;
        $secondsInADay    = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // return the final array
        $obj = array(
            'd' => (int) $days,
            'h' => (int) $hours,
            'm' => (int) $minutes,
            's' => (int) $seconds,
        );
        return $obj;
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('TimeSpentInsight');
