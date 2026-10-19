<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Form\Type;

use MauticPlugin\MauticOmnichannelMessagingBundle\Entity\MessageTemplate;
use MauticPlugin\MauticOmnichannelMessagingBundle\Helper\ChannelRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for creating / editing a MessageTemplate.
 * Works standalone for unit tests; in Mautic it is used by TemplateController.
 */
final class MessageTemplateType extends AbstractType
{
    public function __construct(
        private readonly ?ChannelRegistry $registry = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $channelChoices = [
            'WhatsApp' => 'whatsapp',
            'WeChat OA' => 'wechat',
        ];
        if (null !== $this->registry) {
            $channelChoices = [];
            foreach ($this->registry->withTemplates() as $ch) {
                $channelChoices[$ch->getDisplayName()] = $ch->getName();
            }
        }

        $builder->add('name', TextType::class, [
            'label'    => 'mautic.omnichannel.template.name',
            'required' => true,
            'attr'     => ['class' => 'form-control'],
        ]);

        $builder->add('channel', ChoiceType::class, [
            'label'    => 'mautic.omnichannel.form.channel',
            'choices'  => $channelChoices,
            'required' => true,
            'attr'     => ['class' => 'form-control'],
        ]);

        $builder->add('language', TextType::class, [
            'label'    => 'mautic.omnichannel.form.language',
            'required' => true,
            'data'     => 'en_US',
            'attr'     => ['class' => 'form-control'],
        ]);

        $builder->add('category', ChoiceType::class, [
            'label'   => 'mautic.omnichannel.template.category',
            'choices' => [
                'Marketing' => 'MARKETING',
                'Utility'   => 'UTILITY',
                'Authentication' => 'AUTHENTICATION',
            ],
            'attr' => ['class' => 'form-control'],
        ]);

        $builder->add('status', ChoiceType::class, [
            'label'   => 'mautic.omnichannel.template.status',
            'choices' => array_combine(
                MessageTemplate::getAvailableStatuses(),
                MessageTemplate::getAvailableStatuses()
            ),
            'attr' => ['class' => 'form-control'],
        ]);

        $builder->add('body', TextareaType::class, [
            'label'    => 'mautic.omnichannel.template.body',
            'required' => true,
            'attr'     => ['class' => 'form-control', 'rows' => 6],
        ]);

        $builder->add('isPublished', CheckboxType::class, [
            'label'    => 'mautic.core.form.published',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageTemplate::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'omnichannel_template';
    }
}
