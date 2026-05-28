<?php

namespace App\Form;

use App\Entity\Boutique;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BoutiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la boutique *',
                'attr' => ['placeholder' => 'Ex : SHA Boutique', 'class' => 'w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#8b674f]']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description *',
                'attr' => ['rows' => 4, 'class' => 'w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#8b674f]']
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom', // ou le nom de la propriété contenant le nom de la catégorie
                'label' => 'Catégorie *',
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#8b674f]']
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (logo)',
                'mapped' => false, // On gère l'upload manuellement dans le controller
                'required' => false,
                'attr' => ['class' => 'w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#f4e5d9] file:text-[#603f2a] hover:file:bg-[#e6d0bf]']
            ])
            ->add('photo_couverture', FileType::class, [
                'label' => 'Photo de couverture',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#f4e5d9] file:text-[#603f2a] hover:file:bg-[#e6d0bf]']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Boutique::class,
        ]);
    }
}