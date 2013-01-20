<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Repository\Person;

use Doctrine\ORM\EntityRepository;

class Person extends EntityRepository
{
    public function createSearchQueryBuilder($input)
    {
        $input = trim($input);
        $input = preg_replace('/\s{2,}/', ' ', $input);
        $input = preg_replace('/,\s?/', ' ', $input);

        $qb = $this->getEntityManager()
        ->createQueryBuilder();

        $qb->where(
            $qb->expr()->orX(
                $qb->expr()->like(
                    $qb->expr()->concat(
                        'p.given_name', $qb->expr()->concat(
                            $qb->expr()->literal(' '), 'p.family_name'
                        )
                    ),
                    ':prefixInput'
                ),
                $qb->expr()->like(
                    $qb->expr()->concat(
                        'p.family_name', $qb->expr()->concat(
                            $qb->expr()->literal(' '), $qb->expr()->concat(
                                'p.given_name', $qb->expr()->concat(
                                    $qb->expr()->literal(' '), 'p.middle_name'
                                )
                            )
                        )
                    ),
                    ':prefixInput'
                ),
                'p.pidm = :exactInput',
                'p.onid = :exactInput',
                'p.osuid = :exactInput'
            )
        )
        ->setParameter('exactInput', $input)
        ->setParameter('prefixInput', $input . '%');

        return $qb;
    }
}
