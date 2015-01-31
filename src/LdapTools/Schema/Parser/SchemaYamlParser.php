<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Schema\Parser;

use LdapTools\Exception\SchemaParserException;
use LdapTools\Schema\LdapObjectSchema;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Parses a schema definition from a YAML file.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SchemaYamlParser implements SchemaParserInterface
{
    /**
     * @param string $schemaFolder
     */
    public function __construct($schemaFolder)
    {
        $this->schemaFolder = $schemaFolder;
    }

    /**
     * Given the schema name, return the last time the schema was modified in DateTime format.
     *
     * @param string $schemaName
     * @return \DateTime
     * @throws SchemaParserException
     */
    public function getSchemaModificationTime($schemaName)
    {
        $file = $this->schemaFolder.'/'.$schemaName.'.yml';
        $this->validateFileCanBeRead($file);

        return new \DateTime('@'.filemtime($file));
    }

    /**
     * {@inheritdoc}
     */
    public function parse($schemaName, $objectType)
    {
        $file = $this->schemaFolder.'/'.$schemaName.'.yml';
        $this->validateFileCanBeRead($file);

        try {
            $schema = Yaml::parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw new SchemaParserException(sprintf('Error in configuration file: %s', $e->getMessage()));
        }

        return $this->parseYamlForObject($schema, $schemaName, $objectType);
    }

    protected function validateFileCanBeRead($file)
    {
        if (!is_readable($file)) {
            throw new SchemaParserException(sprintf("Cannot read schema file: %s", $file));
        }
    }

    /**
     * Attempt to find the object type definition in the schema and create its object representation.
     *
     * @param array $schema
     * @param string $schemaName
     * @param string $objectType
     * @return LdapObjectSchema
     * @throws SchemaParserException
     */
    protected function parseYamlForObject(array $schema, $schemaName, $objectType)
    {
        if (!array_key_exists('objects', $schema)) {
            throw new SchemaParserException('Cannot find the "objects" section in the schema file.');
        }
        $objectSchema = $this->getObjectFromSchema($schema, $objectType);
        $ldapObjectSchema = new LdapObjectSchema($schemaName, $objectType);

        $converterMap = [];
        if (array_key_exists('converters', $objectSchema)) {
            foreach ($objectSchema['converters'] as $converter => $attributes) {
                if (is_array($attributes)) {
                    foreach ($attributes as $attribute) {
                        $converterMap[$attribute] = $converter;
                    }
                } elseif (is_string($attributes)) {
                    $converterMap[$attributes] = $converter;
                }
            }
        }

        if (array_key_exists('category', $objectSchema)) {
            $ldapObjectSchema->setObjectCategory($objectSchema['category']);
        }
        if (array_key_exists('class', $objectSchema)) {
            $ldapObjectSchema->setObjectClass($objectSchema['class']);
        }
        if (array_key_exists('attributes_to_select', $objectSchema)) {
            $ldapObjectSchema->setAttributesToSelect($objectSchema['attributes_to_select']);
        }
        if (array_key_exists('repository', $objectSchema)) {
            $ldapObjectSchema->setRepository($objectSchema['repository']);
        }

        if (!((bool)count(array_filter(array_keys($objectSchema['attributes']), 'is_string')))) {
            throw new SchemaParserException('The attributes for a schema should be an associative array.');
        }

        $ldapObjectSchema->setAttributeMap($objectSchema['attributes']);
        $ldapObjectSchema->setConverterMap($converterMap);

        return $ldapObjectSchema;
    }

    /**
     * Check for a specific object type in the schema and validate it.
     *
     * @param array $schema
     * @param string $objectType
     * @return null|array
     * @throws SchemaParserException
     */
    protected function getObjectFromSchema(array $schema, $objectType)
    {
        $objectSchema = null;
        foreach ($schema['objects'] as $ldapObject) {
            if (array_key_exists('type', $ldapObject) && $ldapObject['type'] == $objectType) {
                $objectSchema = $ldapObject;
            }
        }
        if (!$objectSchema) {
            throw new SchemaParserException(sprintf('Cannot find object type "%s" in schema.', $objectType));
        }

        if (!array_key_exists('class', $objectSchema)) {
            throw new SchemaParserException(sprintf('Object type "%s" has no class defined.', $objectType));
        }
        if (!array_key_exists('attributes', $objectSchema) || empty($objectSchema['attributes'])) {
            throw new SchemaParserException(sprintf('Object type "%s" has no attributes defined.', $objectType));
        }

        return $objectSchema;
    }
}
