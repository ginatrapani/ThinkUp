<?php
/**
 *
 * ThinkUp/webapp/_lib/model/class.Menu.php
 *
 * Copyright (c) 2009-2010 Gina Trapani
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
 * Menu
 * Container for a set of menu items.
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2010 Gina Trapani
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 *
 */
class Menu {
    /**
     * @var str
     */
    var $heading;
    /**
     * @var array
     */
    var $items = array();

    /**
     * Constructor
     * @param str $heading Menu heading
     * @return Menu
     */
    public function __construct($heading) {
        $this->heading = $heading;
    }

    /**
     * Add item to menu
     * @param MenuItem $menu_item
     */
    public function addMenuItem($menu_item) {
        array_push($this->items, $menu_item);
    }
}