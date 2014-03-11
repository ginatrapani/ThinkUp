<?php
/*
 Plugin Name: Frequency
 Description: How frequently you posted this week as compared to last week.
 When: Mondays
 */
/**
 *
 * ThinkUp/webapp/plugins/insightsgenerator/insights/frequency.php
 *
 * Copyright (c) 2013 Gina Trapani
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
 * @copyright 2013 Gina Trapani
 * @author Gina Trapani <ginatrapani [at] gmail [dot] com>
 */

class FrequencyInsight extends InsightPluginParent implements InsightPlugin {

    public function generateInsight(Instance $instance, $last_week_of_posts, $number_days) {
        parent::generateInsight($instance, $last_week_of_posts, $number_days);
        $this->logger->logInfo("Begin generating insight", __METHOD__.','.__LINE__);
        $insight_text = '';
        $milestones = array();
        $randomizer = (time() % 10);

        if (self::shouldGenerateWeeklyInsight('frequency', $instance, $insight_date='today',
        $regenerate_existing_insight=false, $day_of_week=1)) {
            $count = sizeof($last_week_of_posts);
            if ($count > 1) {
                $this->logger->logInfo("Last week had $count posts", __METHOD__.','.__LINE__);
                if ($randomizer < 3) {
                    $headline = "$this->username " . $this->terms->getVerb('posted') .
                        " <strong>$count times</strong> in the past week.";
                } else {
                    $headline = "$this->username had <strong>$count " .
                        $this->terms->getNoun('post', InsightTerms::PLURAL) . "</strong> over the past week.";
                }
                $milestones = array(
                    "per_row"    => 1,
                    "label_type" => "icon",
                    "items" => array(
                        0 => array(
                            "number" => $count,
                            "label"  => $this->terms->getNoun('post', $count),
                        ),
                    ),
                );
            } else {
                $this->logger->logInfo("Last week had no posts", __METHOD__.','.__LINE__);
                $button = array();
                switch ($instance->network) {
                    case 'twitter':

                        if ($randomizer < 3) {
                            $headline = "$this->username didn't post anything new on " . ucfirst($instance->network)
                                . " in the past week.";
                            $insight_text = "Sometimes we just don't have anything to say. Maybe let someone know you"
                                . " appreciate their work?";
                            $button = array(
                                "url" => "http://twitter.com/intent/tweet?text=You know who is really great?",
                                "label"  => "Tweet a word of praise",
                            );
                        } elseif ($randomizer < 7) {
                            $headline = "Seems like $this->username was pretty quiet on " . ucfirst($instance->network)
                                . " this past week.";
                            $insight_text = "Nothing wrong with waiting until there's something to say.";
                            $button = array(
                                "url" => "http://twitter.com/intent/tweet?text=Sorry I haven't tweeted in a while!",
                                "label"  => "Or just say hi to everyone.",
                            );
                        } else {
                            $headline = "$this->username didn't have any new " .
                                $this->terms->getNoun('post', InsightTerms::PLURAL) . " this week.";
                            $insight_text = "Nothing wrong with waiting until there's something to say.";
                            $button = array(
                                "url" => "http://twitter.com/intent/tweet?text=Hey there, friends.",
                                "label"  => "Have anything to say now?",
                            );
                        }

                        break;
                    case 'facebook':

                        if ($randomizer < 3) {
                            $headline = "$this->username didn't post anything new on " . ucfirst($instance->network)
                                . " in the past week.";
                            $insight_text = "Nothing wrong with being quiet. If you want, you could ask your friends "
                                ."what they've read lately.";
                            $button = array(
                                "url" => "http://www.facebook.com/sharer/sharer.php?u=http://upload.wikimedia.org/wikipedia/en/4/43/FlanneryOConnorCompleteStories.jpg&t=Ready any good books lately?",
                                "label"  => "Read any good books lately?",
                            );

                        } elseif ($randomizer < 7) {
                            $headline = "Seems like $this->username was pretty quiet on " . ucfirst($instance->network)
                                . " this past week.";
                            $insight_text = "Nothing wrong with waiting until there's something to say.";
                            $button = array(
                                "url" => "http://www.facebook.com/sharer/sharer.php?t=Hey there, friends!",
                                "label"  => "Or just say hi to your friends?",
                            );
                        } else {
                            $headline = "$this->username didn't have any new " .
                                $this->terms->getNoun('post', InsightTerms::PLURAL) . " this week.";
                            $insight_text = "Nothing wrong with waiting until there's something to say.";
                            $button = array(
                                "url" => "http://www.facebook.com/sharer/sharer.php?t=ThinkUp told me to say hi.",
                                "label"  => "Maybe you've got something to say now?",
                            );
                        }


                        break;
                    default:
                        $insight_text = "Huh, nothing. Fill the emptiness inside you by donating to an underfunded " .
                            "classroom.";
                        $button = array(
                            "url" => "http://www.donorschoose.org/",
                            "label"  => "Give to DonorsChoose.org",
                        );
                }
            }

            $insight_baseline_dao = DAOFactory::getDAO('InsightBaselineDAO');
            $insight_baseline_dao->insertInsightBaseline("frequency", $instance->id, $count,
            $this->insight_date);

            if ($count > 1) {
                //Week over week comparison
                //Get insight baseline from last Monday
                $last_monday = date('Y-m-d', strtotime('-7 day'));
                $last_monday_insight_baseline = $insight_baseline_dao->getInsightBaseline("frequency",
                $instance->id, $last_monday);
                if (isset($last_monday_insight_baseline) ) {
                    $this->logger->logInfo("Baseline had $last_monday_insight_baseline->value posts",
                        __METHOD__.','.__LINE__);
                    //compare it to this Monday's  number, and add a sentence comparing it.
                    if ($last_monday_insight_baseline->value > ($count) ) {
                        $difference = $last_monday_insight_baseline->value - $count;
                        if ($difference === 1) {
                            $insight_text = "That's $difference fewer " .
                            $this->terms->getNoun('post', InsightTerms::SINGULAR) . " than the prior week.";
                        } else {
                            $insight_text = "That's $difference fewer " .
                            $this->terms->getNoun('post', InsightTerms::PLURAL) . " than the prior week.";
                        }

                    } elseif ($last_monday_insight_baseline->value < ($count) ) {
                        $difference = $count - $last_monday_insight_baseline->value;
                        if ($difference === 1) {
                            $insight_text = "That's $difference more " .
                            $this->terms->getNoun('post', InsightTerms::SINGULAR) . " than the prior week.";
                        } else {
                            $insight_text = "That's $difference more " .
                            $this->terms->getNoun('post', InsightTerms::PLURAL) . " than the prior week.";
                        }
                    }
                }
            }
            //Instantiate the Insight object
            $my_insight = new Insight();

            //REQUIRED: Set the insight's required attributes
            $my_insight->instance_id = $instance->id;
            $my_insight->slug = 'frequency'; //slug to label this insight's content
            $my_insight->date = $this->insight_date; //date of the data this insight applies to
            $my_insight->headline = $headline;
            $my_insight->text = $insight_text;
            $my_insight->header_image = '';
            $my_insight->emphasis = Insight::EMPHASIS_LOW; //Set emphasis optionally, default is Insight::EMPHASIS_LOW
            $my_insight->filename = basename(__FILE__, ".php"); //Same for every insight, must be set exactly this way
            $my_insight->setMilestones($milestones);
            $my_insight->setButton($button);

            $this->insight_dao->insertInsight($my_insight);

        }

        $this->logger->logInfo("Done generating insight", __METHOD__.','.__LINE__);
    }
}

$insights_plugin_registrar = PluginRegistrarInsights::getInstance();
$insights_plugin_registrar->registerInsightPlugin('FrequencyInsight');
