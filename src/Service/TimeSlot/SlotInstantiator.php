<?php


namespace App\Service\TimeSlot;


use App\Entity\Slot;
use App\Repository\SlotRepository;
use Doctrine\ORM\EntityManagerInterface;

class SlotInstantiator
{
    private EntityManagerInterface $manager;
    private SlotRepository $slotRepository;

    public function __construct(EntityManagerInterface $manager, SlotRepository $slotRepository)
    {
        $this->manager = $manager;
        $this->slotRepository = $slotRepository;
    }

    /**
     * Function to instantiate slot from time slots
     */
    public function instantiate($breakTime, $timeSlots)
    {
        $duration = 30;
        $totalDuration = $duration + $breakTime;
        foreach ($timeSlots as $timeSlot) {
            //Clear slots attached to timeSlot
            $this->clearSlots($timeSlot);

            $time = $timeSlot->getStartTime();
            $limit = $timeSlot->getEndTime()->sub(new \DateInterval('PT' . $totalDuration . 'M'));

            while ($time <= $limit) {
                //Check if slot exist
                $sl = $this->slotRepository->findExistingSlot($timeSlot->getDate(), $time);
                if (!$sl){
                    //Instantiate object
                    $slot = new Slot();
                    $slot
                        ->setTimeSlot($timeSlot)
                        ->setStatus(false)
                        ->setStartTime($time);

                    $this->manager->persist($slot);
                    $this->manager->flush();
                }

                //Increase time by duration
                $time = $timeSlot->getStartTime()->add(new \DateInterval('PT' . $totalDuration . 'M'));
            }
        }
    }

    /**
     * Function to remove all slots of timeSlot before creating new ones
     */
    public function clearSlots($timeSlot)
    {
        $slots = $this->slotRepository->findBy(['timeSlot' => $timeSlot]);

        foreach ($slots as $slot){
            if ($slot->getStatus() == false)
                $this->manager->remove($slot);
        }
        $this->manager->flush();
    }

    /**
     * Function to clear passed slots
     */
    public function clearPassedSlots($timeSlot)
    {
        $slots = $this->slotRepository->findBy(['timeSlot' => $timeSlot]);
        $now = new \Datetime();

        foreach ($slots as $slot){
            //Get complete datetime slot
            $date = $slot->getTimeSlot()->getDate()->format('d-m-Y');
            $time = $slot->getStartTime()->format('H:i');

            //Instantiate object DateTime with slot date and time
            $dateTimeSlot = new \DateTime($date . ' ' . $time);

            if ($dateTimeSlot <= $now && $slot->getStatus() == false)
                $this->manager->remove($slot);
        }
        $this->manager->flush();
    }
}