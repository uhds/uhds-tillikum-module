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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Tillikum\Entity;
use Tillikum\Job\AbstractJob;

class OrchardCourtBilling extends AbstractJob
{
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getDescription()
    {
        return 'Assess billing for all Orchard Court facility bookings.';
    }

    public function getFormClass()
    {
        return 'TillikumX\Form\Job\OrchardCourtBilling';
    }

    public function getName()
    {
        return 'Orchard Court facility booking billing assessment';
    }

    public function run()
    {
        $job = $this->getJobEntity();
        $parameters = $this->getParameters();
        $endParameter = new DateTime($parameters['end']);

        $csvResource = fopen('php://temp/maxmemory:' . 16 * 1024 * 1024, 'r+');

        fputcsv(
            $csvResource,
            array(
                'OSU ID',
                'Family name',
                'Given name',
                'Billed rate ID',
                'Billed rate start',
                'Billed rate end',
                'Billing ID',
                'Old billed through',
                'New billed through',
            )
        );

        $result = $this->em->createQuery(
            "
            SELECT b, bi, r
            FROM Tillikum\Entity\Booking\Facility\Facility b
            JOIN b.facility f
            JOIN f.facility_group fg
            JOIN b.billing bi
            JOIN bi.rates r
            WHERE (bi.through < r.end OR bi.through IS NULL) AND
                  fg.id = :facilityId
            "
        )
            // Orchard Court
            ->setParameter('facilityId', 'd7e9b0536c8fec14ea94e94cb0e63eb9')
            ->getResult();

        $bookingCount = $eventCount = $rateCount = 0;
        $billingEvents = new ArrayCollection();
        foreach ($result as $booking) {
            $bookingCount += 1;
            $billing = $booking->billing;

            foreach ($billing->rates as $rate) {
                // Nothing to do
                if ($billing->through >= $rate->end) {
                    continue;
                }

                $rateCount += 1;

                $oldBilledThrough = null;
                if ($billing->through) {
                    $creditEvent = new Entity\Billing\Event\FacilityBooking();
                    $creditEvent->person = $booking->person;
                    $creditEvent->rule = $rate->rule;
                    $creditEvent->is_processed = false;
                    $creditEvent->facility = $booking->facility;
                    $creditEvent->is_credit = true;
                    $creditEvent->start = $rate->start;
                    $creditEvent->end = min($rate->end, $billing->through);

                    $oldBilledThrough = clone $creditEvent->end;

                    $billingEvents->add($creditEvent);

                    $eventCount += 1;
                }

                if (!$job->is_dry_run) {
                    $billing->through = min($rate->end, $endParameter);
                }

                $chargeEvent = new Entity\Billing\Event\FacilityBooking();
                $chargeEvent->person = $booking->person;
                $chargeEvent->rule = $rate->rule;
                $chargeEvent->is_processed = false;
                $chargeEvent->facility = $booking->facility;
                $chargeEvent->is_credit = false;
                $chargeEvent->start = $rate->start;
                $chargeEvent->end = min($rate->end, $endParameter);

                $billingEvents->add($chargeEvent);

                $eventCount += 1;

                fputcsv(
                    $csvResource,
                    array(
                        $booking->person->osuid ?: $booking->person->id,
                        $booking->person->family_name,
                        $booking->person->given_name,
                        $rate->id,
                        $rate->start->format('Y-m-d'),
                        $rate->end->format('Y-m-d'),
                        $billing->id,
                        isset($oldBilledThrough) ? $oldBilledThrough->format('Y-m-d') : '',
                        $chargeEvent->end->format('Y-m-d'),
                    )
                );
            }
        }

        if (!$job->is_dry_run) {
            foreach ($billingEvents as $event) {
                $this->em->persist($event);
            }
        }

        rewind($csvResource);
        $csv = stream_get_contents($csvResource);
        fclose($csvResource);

        $attachment = new Entity\Job\Attachment\Attachment();
        $attachment->job = $job;
        $attachment->name = 'Orchard Court Billing ' . date('Y-m-d'). '.csv';
        $attachment->media_type = 'text/csv';
        $attachment->attachment = $csv;
        $this->em->persist($attachment);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Processed {$rateCount} rates in {$bookingCount} bookings.";
        $this->em->persist($message);

        $message = new Entity\Job\Message\Message();
        $message->job = $job;
        $message->level = LOG_INFO;
        $message->message = "Generated {$eventCount} billing events.";
        $this->em->persist($message);

        $job->run_state = Entity\Job\Job::RUN_STATE_STOPPED;
        $job->job_state = Entity\Job\Job::JOB_STATE_SUCCESS;

        $this->em->flush();
    }
}
