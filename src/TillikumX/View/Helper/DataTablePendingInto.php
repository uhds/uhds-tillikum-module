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

class DataTablePendingInto extends AbstractHelper
{
    public function dataTablePendingInto($rows)
    {
        return $this->view->partial(
            '_partials/datatable/pending/into.phtml',
            array(
                'rows' => $rows
            )
        );
    }

    public function getCaption()
    {
        return 'Pending INTO applications';
    }
}
