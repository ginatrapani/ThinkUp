<?php
/**
 *
 * ThinkUp/webapp/_lib/view/plugins/modifier.tweet_from_id.php
 *
 * Copyright (c) 2009-2010 Gina Trapani
 *
 * LICENSE:
 *
 * This file is part of ThinkUp.
 * 
 * ThinkUp is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ThinkUp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with ThinkUp.  If not, see <http://www.gnu.org/licenses/>.
 *
*/
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty Tweet from ID plugin
 *
 * Type:     modifier<br>
 * Name:     tweet_from_id<br>
 * Date:     March 9, 2010
 * Purpose:  Converts a tweet id into a full Tweet object.
 * Input:    status id
 * Example:  {$tweet->in_reply_to_id|tweet_from_id}
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2010 Gina Trapani
 * @author   Thomas Woodham
 * @version 1.0
 * @param integer
 * @return object
 */
function smarty_modifier_tweet_from_id($status_id) {
    $post_dao = DAOFactory::getDAO('PostDAO');
    if( $status_id > 0 ){
        $tweet = $post_dao->getPost( $status_id );
    } else {
        $tweet = new Post( array( 'id' => 0, 'status_id' => 0 ) );
    }
    return $tweet;
}
?>