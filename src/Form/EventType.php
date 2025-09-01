<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Event;
use App\Entity\User;
use App\Entity\Place;
use App\Entity\Site;
use App\Entity\State;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('startDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de la sortie'
            ])
            ->add('endDateTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de fin de la sortie'
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date limite d’inscription'
            ])
            ->add('maxParticipants', IntegerType::class, ['label' => 'Nombre maximum de participant.es'])
            ->add('eventInfo', TextAreaType::class, [
                'label' => 'Informations de l\'évènement',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Ajoutez une description ou des infos utiles sur la sortie'
                ]])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
                'label' => 'Site de l\'évènement',
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'name',
                'label' => 'Lieu de l’évènement',
                'required' => false, // Permet de créer un nouveau lieu
                'placeholder' => 'Choisir un lieu existant ou créer un nouveau',
                'attr' => ['class' => 'form-select'],
                'choice_attr' => function(?Place $place, $key, $value) {
                    if (!$place) return [];
                    return [
                        'data-lat' => $place->getLatitude(),
                        'data-lng' => $place->getLongitude(),
                    ];
                },
            ])
            ->add('newPlace', TextType::class, [
                'label' => 'Nouveau lieu',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Nom du nouveau lieu']
            ])

            ->add('newPlaceStreet', TextType::class, [
                'label' => 'Rue du nouveau lieu',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Rue',
                    'id'=>'newPlaceStreetField'
                ]
            ])

            ->add('newPlaceCity', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ville',
                    'id'=>'cityField'
                ]
            ])

            ->add('newPlaceLat', HiddenType::class, ['mapped' => false])
            ->add('newPlaceLng', HiddenType::class, ['mapped' => false]);

$builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
    $form = $event->getForm();

    $place = $form->get('place')->getData();
    $newPlace = $form->get('newPlace')->getData();
    $street = $form->get('newPlaceStreet')->getData();
    $city = $form->get('newPlaceCity')->getData();
    $lat = $form->get('newPlaceLat')->getData();
    $lng = $form->get('newPlaceLng')->getData();

    // Si aucun lieu existant n'est choisi et qu'on veut créer un nouveau
    if (!$place) {
        if (empty($newPlace)) {
            $form->get('newPlace')->addError(new FormError('Le nom du nouveau lieu est obligatoire.'));
        }
        if (empty($street)) {
            $form->get('newPlaceStreet')->addError(new FormError('La rue est obligatoire.'));
        }
        if (!$city) {
            $form->get('newPlaceCity')->addError(new FormError('La ville est obligatoire.'));
        }
        if (empty($lat) || empty($lng)) {
            $form->get('newPlace')->addError(new FormError('Veuillez cliquer sur la carte pour positionner le lieu.'));
        }
    }
});
}

public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Event::class,
    ]);
}
}
