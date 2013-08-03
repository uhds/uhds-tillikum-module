<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\View\Helper;

use Zend_View_Helper_Abstract as AbstractHelper;

/**
 * Helper for rendering the person tab view 'into' section
 */
class TabViewPersonInto extends AbstractHelper
{
    public function tabViewPersonInto()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        if (!method_exists($person, 'getIntoData')) {
            return false;
        }

        return $person->getIntoData() !== null;
    }

    public function render($person)
    {
        return $this->view->partial(
            '_partials/into.phtml',
            array(
                'person' => $person,
            )
        );
    }
}
