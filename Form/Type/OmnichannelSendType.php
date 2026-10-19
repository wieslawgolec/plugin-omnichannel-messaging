<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type;

use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Campaign action form for sending an omnichannel message.
 */
final class OmnichannelSendType extends AbstractType
{
    public function __construct(
        private readonly ChannelRegistry $registry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $channelChoices = [];
        foreach ($this->registry->all() as $channel) {
            $channelChoices[$channel->getDisplayName()] = $channel->getName();
        }

        $builder->add('channel', ChoiceType::class, [
            'label'   => 'mautic.omnichannel.form.channel',
            'choices' => $channelChoices,
            'attr'    => ['class' => 'form-control'],
            'required' => true,
        ]);

        $builder->add('message', TextareaType::class, [
            'label'      => 'mautic.omnichannel.form.message',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'rows'    => 5,
                'tooltip' => 'mautic.omnichannel.form.message.tooltip',
            ],
            'required' => true,
        ]);

        $builder->add('template_name', TextType::class, [
            'label'      => 'mautic.omnichannel.form.template_name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.omnichannel.form.template_name.tooltip',
            ],
            'required' => false,
        ]);

        $builder->add('language', TextType::class, [
            'label'      => 'mautic.omnichannel.form.language',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'data'       => 'en_US',
            'required'   => false,
        ]);

        $builder->add('to_override', TextType::class, [
            'label'      => 'mautic.omnichannel.form.to_override',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.omnichannel.form.to_override.tooltip',
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'omnichannel_send';
    }
}
