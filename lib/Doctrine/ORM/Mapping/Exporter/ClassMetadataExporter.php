<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping;

class ClassMetadataExporter implements Exporter
{
    const VARIABLE = '$this';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var Mapping\ClassMetadata $value */
        $reflectionClass = $value->getReflectionClass();
        $namespace       = $reflectionClass->getNamespaceName();
        $lines           = [];

        $lines[] = '<?php';
        $lines[] = null;

        if ($namespace) {
            $lines[] = 'namespace ' . $namespace . ';';
            $lines[] = null;
        }

        $lines[] = 'use Doctrine\DBAL\Types\Type;';
        $lines[] = 'use Doctrine\ORM\Mapping;';
        $lines[] = 'use Doctrine\ORM\Mapping\Factory\ClassMetadataBuildingContext;';
        $lines[] = null;
        $lines[] = $this->exportClass($value, $indentationLevel);
        $lines[] = null;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportClass(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $reflectionClass   = $metadata->getReflectionClass();
        $shortClassName    = $reflectionClass->getShortName();
        $extendedClassName = ($metadata instanceof Mapping\MappedSuperClassMetadata)
            ? 'MappedSuperClassMetadata'
            : 'EntityClassMetadata'
        ;

        $lines[] = sprintf('class %sClassMetadata extends Mapping\%s', $shortClassName, $extendedClassName);
        $lines[] = '{';
        $lines[] = $this->exportConstructor($metadata, $indentationLevel + 1);
        $lines[] = '}';
        $lines[] = null;

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportConstructor(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $objectReference = $bodyIndentation . static::VARIABLE;
        $lines           = [];

        $lines[] = $indentation . 'public function __construct(';
        $lines[] = $bodyIndentation . 'ClassMetadataBuildingContext $metadataBuildingContext,';
        $lines[] = $bodyIndentation . '?ClassMetadata $parent = null';
        $lines[] = $indentation . ')';
        $lines[] = $indentation . '{';
        $lines[] = $bodyIndentation . 'parent::__construct("' . $metadata->getClassName() . '", $parent);';

        if ($metadata->getCustomRepositoryClassName()) {
            $lines[] = null;
            $lines[] = $objectReference . '->setCustomRepositoryClassName("' . $metadata->getCustomRepositoryClassName() . '");';
        }

        if ($metadata->changeTrackingPolicy) {
            $lines[] = null;
            $lines[] = $objectReference . '->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::' . strtoupper($metadata->changeTrackingPolicy) . ');';
        }

        $lines[] = $this->exportInheritance($metadata, $indentationLevel);
        $lines[] = $this->exportTable($metadata, $indentationLevel);
        $lines[] = $this->exportProperties($metadata, $indentationLevel);
        $lines[] = $this->exportLifecycleCallbacks($metadata, $indentationLevel);
        $lines[] = $indentation . '}';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportInheritance(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $objectReference = $bodyIndentation . static::VARIABLE;
        $lines           = [];

        if ($metadata->inheritanceType) {
            $lines[] = null;
            $lines[] = $objectReference . '->setInheritanceType(Mapping\InheritanceType::' . strtoupper($metadata->inheritanceType) . ');';
        }

        if ($metadata->discriminatorColumn) {
            $lines[] = null;
            $lines[] = $bodyIndentation . '// Discriminator mapping';
            $lines[] = $this->exportDiscriminatorMetadata($metadata, $indentationLevel + 1);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportTable(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $lines           = [];

        if ($metadata->table) {
            $lines[] = null;
            $lines[] = $bodyIndentation . '// Table';
            $lines[] = $this->exportTableMetadata($metadata->table, $indentationLevel + 1);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportProperties(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $lines           = [];

        foreach ($metadata->getDeclaredPropertiesIterator() as $name => $property) {
            $lines[] = null;
            $lines[] = $bodyIndentation . '// Property: ' . $name;
            $lines[] = $this->exportProperty($property, $indentationLevel + 1);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportLifecycleCallbacks(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $bodyIndentation = str_repeat(self::INDENTATION, $indentationLevel + 1);
        $objectReference = $bodyIndentation . static::VARIABLE;
        $lines           = [];

        if ($metadata->lifecycleCallbacks) {
            $lines[] = null;
            $lines[] = $bodyIndentation . '// Lifecycle callbacks';

            foreach ($metadata->lifecycleCallbacks as $event => $callbacks) {
                foreach ($callbacks as $callback) {
                    $lines[] = $objectReference . '->addLifecycleCallback("' . $callback . '", "' . $event . '");';
                }
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\ClassMetadata $metadata
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportDiscriminatorMetadata(Mapping\ClassMetadata $metadata, int $indentationLevel) : string
    {
        $variableExporter      = new VariableExporter();
        $discriminatorExporter = new DiscriminatorColumnMetadataExporter();
        $indentation           = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference       = $indentation . static::VARIABLE;
        $lines                 = [];

        $lines[] = $discriminatorExporter->export($metadata->discriminatorColumn, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->setDiscriminatorColumn(' . $discriminatorExporter::VARIABLE . ');';

        if ($metadata->discriminatorMap) {
            $discriminatorMap = $variableExporter->export($metadata->discriminatorMap, $indentationLevel + 1);

            $lines[] = $objectReference . '->setDiscriminatorMap(' . $discriminatorMap . ');';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\TableMetadata $table
     * @param int                   $indentationLevel
     *
     * @return string
     */
    private function exportTableMetadata(Mapping\TableMetadata $table, int $indentationLevel) : string
    {
        $tableExporter   = new TableMetadataExporter();
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = $tableExporter->export($table, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->setTable(' . $tableExporter::VARIABLE . ');';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param Mapping\Property $property
     * @param int              $indentationLevel
     *
     * @return string
     */
    private function exportProperty(Mapping\Property $property, int $indentationLevel) : string
    {
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        switch (true) {
            case ($property instanceof Mapping\VersionFieldMetadata):
                $propertyExporter = new VersionFieldMetadataExporter();
                break;

            case ($property instanceof Mapping\FieldMetadata):
                $propertyExporter = new FieldMetadataExporter();
                break;

            case ($property instanceof Mapping\OneToOneAssociationMetadata):
                $propertyExporter = new OneToOneAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\OneToManyAssociationMetadata):
                $propertyExporter = new OneToManyAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\ManyToOneAssociationMetadata):
                $propertyExporter = new ManyToOneAssociationMetadataExporter();
                break;

            case ($property instanceof Mapping\ManyToManyAssociationMetadata):
                $propertyExporter = new ManyToManyAssociationMetadataExporter();
                break;

            default:
                $propertyExporter = new TransientMetadataExporter();
                break;
        }

        $lines[] = $propertyExporter->export($property, $indentationLevel);
        $lines[] = null;
        $lines[] = $objectReference . '->addProperty(' . $propertyExporter::VARIABLE . ');';

        return implode(PHP_EOL, $lines);
    }
}
