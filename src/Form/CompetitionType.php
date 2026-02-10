<?php

namespace App\Form;

use App\Entity\Competition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class CompetitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Competition Name',
                'attr' => ['placeholder' => 'Enter competition name']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4, 'placeholder' => 'Enter competition description']
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => ['placeholder' => 'Enter competition location']
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Maximum Participants',
                'attr' => ['min' => 1, 'placeholder' => 'Enter maximum number of participants']
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Start Date & Time',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datepicker']
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'End Date & Time',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => ['class' => 'datepicker']
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Competition Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, WebP)',
                    ])
                ],
                'attr' => ['accept' => 'image/*']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Upcoming' => 'upcoming',
                    'Ongoing' => 'ongoing',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled'
                ],
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Competition::class,
        ]);
    }
}