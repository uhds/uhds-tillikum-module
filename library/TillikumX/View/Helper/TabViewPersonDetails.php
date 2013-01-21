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
 * Helper for rendering the person tab view 'details' section
 */
class TabViewPersonDetails extends AbstractHelper
{
    public function tabViewPersonDetails()
    {
        return $this;
    }

    public function canShowTab($person)
    {
        return true;
    }

    public function render($person)
    {
        return $this->view->partial(
            '_partials/details.phtml',
            array(
                'person' => $person,
                'tbc' => new \Uhds_View_Helper_TranslateBannerCode(),
            )
        );
    }
}
