<?php
/**
 *
 * ThinkUp/webapp/_lib/model/class.PluginOption.php
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
 * Plugin Option
 *
 * A ThinkUp plugin option
 *
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2010 Gina Trapani
 * @author Mark Wilkie <mwilkie[at]gmail[dot]com>
 *
 */
class PluginOption {
    /*
     * @var int id
     */
    var $id;

    /*
     * @var int plugin id
     */
    var $plugin_id;
    
    /*
     * @var str plugin option name
     */
    var $option_name;

    /*
     * @var str plugin option value
     */
    var $option_value;

}