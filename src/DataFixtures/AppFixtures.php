<?php

namespace App\DataFixtures;

use App\Entity\Place;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
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
            $place->setName($faker->streetName);
            $place->setStreet($faker->streetAddress());
            $place->setLatitude($faker->latitude());
            $place->setLongitude($faker->longitude());
            $place->setCity($faker->randomElement($cities));
            $manager->persist($place);
            $places[] = $place;
            }


        $users = [];
        for ($i = 0; $i < 50; $i++) {
            $user = new User();
            $user->setName($faker->lastName);
            $user->setFirstName($faker->firstName);
            $user->setPseudo($faker->name);
            $user->setPhone($faker->phoneNumber);
            $user->setEmail($faker->email);
            $user->setIsAdmin(false);
            $user->setIsActive(true);
            $user->setSite($faker->randomElement($sites));
            $user->setPassword($faker->password);


            $manager->persist($user);
            $users[] = $user;
        }


        for ($i = 0; $i < 20; $i++) {
            $event = new Event();
            $event->setName("Event " . $faker->word);
            $startDate = $faker->dateTimeBetween('+6 days', '+1 month');
            $endDate = (clone $startDate)->modify('+'. $faker->numberBetween(1, 8) .' hours');
            $registrationDeadline = (clone $startDate)->modify('-'. $faker->numberBetween(1, 5) .' days');

            $event->setName("Event " . $faker->word);
            $event->setStartDateTime($startDate);
            $event->setEndDateTime($endDate);
            $event->setRegistrationDeadline($registrationDeadline);

            $event->setMaxParticipants($faker->numberBetween(5, 20));
            $event->setEventInfo($faker->sentence);

            $event->setState($faker->randomElement($states));
            $event->setPlace($faker->randomElement($places));
            $event->setSite($faker->randomElement($sites));
            $organizer = $faker->randomElement($users);
            $event->setOrganizer($organizer);


            foreach ($faker->randomElements($users, $faker->numberBetween(2, 6)) as $registered) {
                $event->addRegisteredParticipant($registered);
            }


            $manager->persist($event);
        }


        $manager->flush();
    }
}
