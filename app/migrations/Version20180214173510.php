<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180214173510 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE TABLE referent_tags (id INT UNSIGNED AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX referent_tag_name_unique (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE adherent_referent_tag (adherent_id INT UNSIGNED NOT NULL, referent_tag_id INT UNSIGNED NOT NULL, INDEX IDX_79E8AFFD25F06C53 (adherent_id), INDEX IDX_79E8AFFD9C262DB3 (referent_tag_id), PRIMARY KEY(adherent_id, referent_tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE adherent_referent_tag ADD CONSTRAINT FK_79E8AFFD25F06C53 FOREIGN KEY (adherent_id) REFERENCES adherents (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE adherent_referent_tag ADD CONSTRAINT FK_79E8AFFD9C262DB3 FOREIGN KEY (referent_tag_id) REFERENCES referent_tags (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE adherent_referent_tag DROP FOREIGN KEY FK_79E8AFFD9C262DB3');
        $this->addSql('DROP TABLE referent_tags');
        $this->addSql('DROP TABLE adherent_referent_tag');
    }
}
