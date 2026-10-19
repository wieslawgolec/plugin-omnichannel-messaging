<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOmnichannelMessagingBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates omnichannel messaging tables.
 */
final class Version20261019120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Omnichannel Messaging: templates, conversations, message_log tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS ' . $this->prefix('omnichannel_message_templates') . ' (
            id INT AUTO_INCREMENT NOT NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            date_added DATETIME DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_by_user VARCHAR(191) DEFAULT NULL,
            date_modified DATETIME DEFAULT NULL,
            modified_by INT DEFAULT NULL,
            modified_by_user VARCHAR(191) DEFAULT NULL,
            checked_out DATETIME DEFAULT NULL,
            checked_out_by INT DEFAULT NULL,
            checked_out_by_user VARCHAR(191) DEFAULT NULL,
            name VARCHAR(191) NOT NULL,
            channel VARCHAR(64) DEFAULT NULL,
            language VARCHAR(16) DEFAULT NULL,
            category VARCHAR(64) DEFAULT NULL,
            status VARCHAR(32) DEFAULT NULL,
            body LONGTEXT NOT NULL,
            parameters JSON DEFAULT NULL,
            external_id VARCHAR(191) DEFAULT NULL,
            rejection_reason LONGTEXT DEFAULT NULL,
            date_approved DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX omni_tpl_channel_status (channel, status),
            INDEX omni_tpl_name_channel (name, channel)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS ' . $this->prefix('omnichannel_conversations') . ' (
            id INT AUTO_INCREMENT NOT NULL,
            lead_id INT DEFAULT NULL,
            channel VARCHAR(64) NOT NULL,
            contact_identifier VARCHAR(191) NOT NULL,
            status VARCHAR(32) NOT NULL,
            last_inbound_at DATETIME DEFAULT NULL,
            last_outbound_at DATETIME DEFAULT NULL,
            window_expires_at DATETIME DEFAULT NULL,
            message_count INT NOT NULL DEFAULT 0,
            subject VARCHAR(255) DEFAULT NULL,
            date_added DATETIME DEFAULT NULL,
            date_modified DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX omni_conv_channel_contact (channel, contact_identifier),
            INDEX omni_conv_lead (lead_id),
            INDEX omni_conv_status (status)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS ' . $this->prefix('omnichannel_message_log') . ' (
            id INT AUTO_INCREMENT NOT NULL,
            conversation_id INT DEFAULT NULL,
            lead_id INT DEFAULT NULL,
            channel VARCHAR(64) NOT NULL,
            direction VARCHAR(16) NOT NULL,
            status VARCHAR(32) NOT NULL,
            body LONGTEXT NOT NULL,
            external_message_id VARCHAR(191) DEFAULT NULL,
            template_name VARCHAR(191) DEFAULT NULL,
            used_template TINYINT(1) NOT NULL DEFAULT 0,
            error_message LONGTEXT DEFAULT NULL,
            media JSON DEFAULT NULL,
            raw_payload JSON DEFAULT NULL,
            date_added DATETIME DEFAULT NULL,
            date_sent DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            INDEX omni_msg_conversation (conversation_id),
            INDEX omni_msg_lead (lead_id),
            INDEX omni_msg_channel_dir (channel, direction),
            INDEX omni_msg_external (external_message_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ' . $this->prefix('omnichannel_message_log'));
        $this->addSql('DROP TABLE IF EXISTS ' . $this->prefix('omnichannel_conversations'));
        $this->addSql('DROP TABLE IF EXISTS ' . $this->prefix('omnichannel_message_templates'));
    }

    private function prefix(string $table): string
    {
        // Mautic table prefix is injected at runtime; fallback for standalone
        return $table;
    }
}
