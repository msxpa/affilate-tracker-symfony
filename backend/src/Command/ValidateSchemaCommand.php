<?php
// src/Command/ValidateSchemaCommand.php

namespace App\Command;

use App\Entity\Click;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:db:validate-schema', description: 'Validate schema excluding partitioned clicks table')]
final class ValidateSchemaCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        // Фильтруем Click — Doctrine не понимает партиционирование
        $filteredMetadata = array_filter(
            $metadata,
            fn ($meta) => $meta->getName() !== Click::class
        );

        // Проверяем схему без Click
        $schemaTool = new SchemaTool($this->em);
        $updateSql = $schemaTool->getUpdateSchemaSql(array_values($filteredMetadata));

        if (count($updateSql) > 0) {
            $output->writeln('<error>Schema is not in sync (excluding clicks):</error>');
            foreach ($updateSql as $sql) {
                $output->writeln('  ' . $sql);
            }
            return Command::FAILURE;
        }

        // Отдельно проверяем clicks через прямой SQL
        $connection = $this->em->getConnection();
        $partitions = $connection->fetchAllAssociative("
            SELECT child.relname AS partition
            FROM pg_inherits
            JOIN pg_class parent ON pg_inherits.inhparent = parent.oid
            JOIN pg_class child  ON pg_inherits.inhrelid  = child.oid
            WHERE parent.relname = 'clicks'
        ");

        if (count($partitions) === 0) {
            $output->writeln('<error>Table clicks has no partitions!</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Schema OK (clicks excluded, ' . count($partitions) . ' partitions found)</info>');
        return Command::SUCCESS;
    }
}