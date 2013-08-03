<?php
/**
 * The Tillikum Project (http://tillikum.org/)
 *
 * @link       http://tillikum.org/websvn/
 * @copyright  Copyright 2009-2012 Oregon State University (http://oregonstate.edu/)
 * @license    http://www.gnu.org/licenses/gpl-2.0-standalone.html GPLv2
 */

namespace TillikumX\Job;

use DateTime;
use Doctrine\ORM\EntityManager;
use Tillikum\Entity;
use Tillikum\Job\AbstractJob;
use Vo\DateRange;

class PropagateMealplanBookings extends AbstractJob
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Propagate meal plan bookings and rates from one term to the next.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\PropagateMealplanBookings';
    }

    public function getName()
    {
        return 'Propagate meal plan bookings and rates';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();

        $oldRateParameter = new DateTime($parameters['old_rate_date']);

        $newRateStartParameter = new DateTime($parameters['new_rate_start']);
        $newRateEndParameter = new DateTime($parameters['new_rate_end']);

        $csvResource = fopen('php://temp/maxmemory:' . 1 * 1024 * 1024, 'r+');

        fputcsv(
            $csvResource,
            array(
                'OSU ID',
                'Plan name',
                'Rate code',
                'Plan start',
                'Plan end',
                'Rate start',
                'Rate end',
            )
        );

        $result = $this->em->createQuery(
            "
            SELECT b, bi, r
            FROM Tillikum\Entity\Booking\Mealplan\Mealplan b
            JOIN b.person p
            JOIN p.bookings fb
            JOIN b.billing bi
            JOIN bi.rates r
            WHERE :oldRateDate BETWEEN r.start AND r.end AND
                  :newRateEndDate BETWEEN fb.start AND fb.end
            GROUP BY b.person
            "
        )
            ->setParameter('oldRateDate', $oldRateParameter)
            ->setParameter('newRateEndDate', $newRateEndParameter)
            ->getResult();

        $bookingCount = $rateCount = 0;
        foreach ($result as $booking) {
            $bookingCount += 1;
            $billing = $booking->billing;
            $person = $booking->person;

            foreach ($person->mealplans as $mealplanBooking) {
                $bookingRange = new DateRange($mealplanBooking->start, $mealplanBooking->end);
                $newRateRange = new DateRange($newRateStartParameter, $newRateEndParameter);

                if ($newRateRange->overlaps($bookingRange)) {
                    $message = new Entity\Job\Message\Message();
                    $message->job = $job;
                    $message->level = LOG_WARNING;
                    $message->message = "New meal plan booking date range for OSU ID {$person->osuid} would overlap existing meal plan booking from {$mealplanBooking->start->format('Y-m-d')} to {$mealplanBooking->end->format('Y-m-d')}, skipping.";

                    $this->em->persist($message);

                    continue 2;
                }
            }

            $rate = $billing->rates->filter(
                \Tillikum\Common\Booking\Bookings::createIncludedDateFilter($oldRateParameter)
            )
                ->last();

            $newBooking = clone($booking);
            $newBooking->billing = new \Tillikum\Entity\Booking\Mealplan\Billing\Billing();
            $newRate = clone($rate);

            $newRate->billing = $newBooking->billing;
            $newRate->billing->booking = $newBooking;
            $newBooking->billing->rates->add($newRate);

            $newBooking->start = $newRateStartParameter;
            $newRate->start = $newRateStartParameter;

            $newBooking->end = $newRateEndParameter;
            $newRate->end = $newRateEndParameter;

            $newBooking->created_at = new DateTime();
            $newRate->created_at = new DateTime();

            $newBooking->created_by = $parameters['identity'];
            $newRate->created_by = $parameters['identity'];

            $newBooking->updated_at = new DateTime();
            $newRate->updated_at = new DateTime();

            $newBooking->updated_by = $parameters['identity'];
            $newRate->updated_by = $parameters['identity'];

            if (!$job->is_dry_run) {
                $this->em->persist($newBooking);
                $this->em->persist($newBooking->billing);
                $this->em->persist($newRate);
            }

            $rateCount += 1;

            fputcsv(
                $csvResource,
                array(
                    $person->osuid,
                    $newBooking->mealplan->name,
                    $newRate->rule->description,
                    $newBooking->start->format('Y-m-d'),
                    $newBooking->end->format('Y-m-d'),
                    $newRate->start->format('Y-m-d'),
                    $newRate->end->format('Y-m-d'),
                )
            );
        }

        rewind($csvResource);
        $csv = stream_get_contents($csvResource);
        fclose($csvResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'Meal plan booking propagation ' . date('Y-m-d'). '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csv;
        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Created {$rateCount} new bookings and rates from {$bookingCount} bookings.";
        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
