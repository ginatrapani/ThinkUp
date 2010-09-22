<?php
/**
 *
 * ThinkUp/webapp/session/register.php
 *
 * Copyright (c) 2009-2010 Gina Trapani, Christoffer Viken, unruthless, Dwi Widiastuti, Russell Smith, is0dvil
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
 * @author Gina Trapani <ginatrapani[at]gmail[dot]com>
 * @author Christoffer Viken <christoffer[at]viken[dot]me>
 * @author unruthless <ruthie[at]unruthless[dot]com>
 * @author Dwi Widiastuti <admin[at]diazuwi[dot]web[dot]id>
 * @author Russell Smith <russell[dot]smith[at]ukd1[dot]co[dot]uk>
 * @author is0dvil <rajisstillhere[at]gmail[dot]com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @copyright 2009-2010 Gina Trapani, Christoffer Viken, unruthless, Dwi Widiastuti, Russell Smith, is0dvil
*/
chdir('..');
require_once 'init.php';

$controller = new RegisterController();
echo $controller->go();
