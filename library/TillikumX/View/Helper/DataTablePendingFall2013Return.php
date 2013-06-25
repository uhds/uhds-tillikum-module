<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\View\Helper;

class DataTablePendingFall2013Return extends DataTablePendingHousingApplication
{
    public function dataTablePendingFall2013Return($rows)
    {
        return parent::dataTablePendingHousingApplication($rows);
    }

    public function getCaption()
    {
        return 'Pending fall 2013 returning student housing applications';
    }
}
