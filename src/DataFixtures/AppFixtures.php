<?php

namespace App\DataFixtures;

use App\Entity\Place;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Participant;
use App\Entity\Site;
use App\Entity\Event;
use App\Entity\State;
use App\Entity\City;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');


        $sites = [];
        foreach (["Nantes", "Rennes", "Paris"] as $nameSite) {
            $site = new Site();
            $site->setName($nameSite);
            $manager->persist($site);
            $sites[] = $site;
        }


        $statesDescription = ["Créée", "Ouverte", "Clôturée", "Activité en cours", "Passée", "Annulée"];
        $states = [];
        foreach ($statesDescription as $description) {
            $state = new State();
            $state->setDescription($description);
            $manager->persist($state);
            $states[$description] = $state;
        }

        $cities=[];
        for ($i = 0; $i < 10; $i++) {
            $city = new City();
            $city->setName($faker->city());
            $city->setPostCode($faker->postcode());
            $manager->persist($city);
            $cities[] = $city;
        }

$places=[];
        for ($i = 0; $i < 10; $i++) {
            $place = new Place();
            $place->setName($faker->realText());
            $place->setStreet($faker->streetAddress());
            $place->setLatitude($faker->latitude());
            $place->setLongitude($faker->longitude());
            $place->setCity($faker->randomElement($cities));
            $manager->persist($place);
            $places[] = $place;
            }


        $participants = [];
        for ($i = 0; $i < 10; $i++) {
            $participant = new Participant();
            $participant->setName($faker->lastName);
            $participant->setFirstName($faker->firstName);
            $participant->setPhone($faker->phoneNumber);
            $participant->setMail($faker->email);
            $participant->setIsAdmin(false);
            $participant->setIsActive(true);
            $participant->setSite($faker->randomElement($sites));
            $participant->setPassword($faker->password);


            $manager->persist($participant);
            $participants[] = $participant;
        }


        for ($i = 0; $i < 10; $i++) {
            $event = new Event();
            $event->setName("Event " . $faker->word);
            $event->setStartDateTime($faker->dateTimeBetween('+1 days', '+1 month'));
            $event->setDuration($faker->numberBetween(60, 240));
            $event->setRegistrationDeadline($faker->dateTimeBetween('now', '+5 days'));
            $event->setMaxParticipants($faker->numberBetween(5, 20));
            $event->setEventInfo($faker->sentence);


            $event->setState($faker->randomElement($states));
            $event->setPlace($faker->randomElement($places));
            $event->setSite($faker->randomElement($sites));
            $organizer = $faker->randomElement($participants);
            $event->setOrganizer($organizer);


            foreach ($faker->randomElements($participants, $faker->numberBetween(2, 6)) as $registered) {
                $event->addRegisteredParticipant($registered);
            }


            $manager->persist($event);
        }


        $manager->flush();
    }
}
