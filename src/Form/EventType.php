<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Participant;
use App\Entity\Place;
use App\Entity\Site;
use App\Entity\State;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('startDateTime')
            ->add('duration')
            ->add('registrationDeadline')
            ->add('maxParticipants')
            ->add('eventInfo')
            ->add('state', EntityType::class, [
                'class' => State::class,
                'choice_label' => 'description',
            ])
            ->add('site', EntityType::class, [
                'class' => Site::class,
                'choice_label' => 'name',
            ])
            ->add('place', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'name',
            ])
            ->add('registeredParticipants', EntityType::class, [
                'class' => Participant::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('organizer', EntityType::class, [
                'class' => Participant::class,
                'choice_label' => 'name',
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
