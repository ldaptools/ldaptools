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

use LdapTools\Connection\LdapControl;
use LdapTools\Exception\SchemaParserException;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\ArrayToOperator;
use LdapTools\Utilities\MBString;
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
     * @var array Schema names to their YAML mappings (ie. ['ad' => [ <yaml array> ]])
     */
    protected $schemas = [];

    /**
     * @var string The folder where the schema files are located.
     */
    protected $schemaFolder = '';

    /**
     * @var string The folder where the default schema files reside.
     */
    protected $defaultSchemaFolder = '';

    /**
     * @var array
     */
    protected $optionMap = [
        'class' => 'setObjectClass',
        'category' => 'setObjectCategory',
        'attributes_to_select' => 'setAttributesToSelect',
        'repository' => 'setRepository',
        'default_values' => 'setDefaultValues',
        'required_attributes' => 'setRequiredAttributes',
        'default_container' => 'setDefaultContainer',
        'converter_options' => 'setConverterOptions',
        'multivalued_attributes' => 'setMultivaluedAttributes',
        'base_dn' => 'setBaseDn',
        'paging' => 'setUsePaging',
        'scope' => 'setScope',
        'rdn' => 'setRdn',
    ];

    /**
     * @var array
     */
    protected $normalizer = [
        'rdn' => 'normalizeToArray',
    ];

    /**
     * @var ArrayToOperator
     */
    protected $arrayToOp;

    /**
     * @param string $schemaFolder
     */
    public function __construct($schemaFolder)
    {
        $this->schemaFolder = $schemaFolder;
        $this->defaultSchemaFolder = __DIR__.'/../../../../resources/schema';
        $this->arrayToOp = new ArrayToOperator();
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
        return new \DateTime('@'.filemtime($this->getSchemaFileName($this->schemaFolder, $schemaName)));
    }

    /**
     * {@inheritdoc}
     */
    public function parse($schemaName, $objectType)
    {
        $this->parseSchemaNameToArray($schemaName);

        return $this->parseYamlForObject($this->schemas[$this->schemaFolder][$schemaName], $schemaName, $objectType);
    }

    /**
     * {@inheritdoc}
     */
    public function parseAll($schemaName)
    {
        $this->parseSchemaNameToArray($schemaName);
        $types = [];
        $ldapObjectSchemas = [];

        if (isset($this->schemas[$this->schemaFolder][$schemaName]['objects'])) {
            $types = array_column($this->schemas[$this->schemaFolder][$schemaName]['objects'], 'type');
        }

        foreach ($types as $type) {
            $ldapObjectSchemas[] = $this->parseYamlForObject(
                $this->schemas[$this->schemaFolder][$schemaName],
                $schemaName,
                $type
            );
        }

        return $ldapObjectSchemas;
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
        $objectSchema = $this->getObjectFromSchema($schema, $objectType);
        $objectSchema = $this->mergeAnyExtendedSchemas($objectSchema, $schemaName);
        $objectSchema = $this->cleanObjectArray($objectSchema);
        $this->updateObjectArray($schemaName, $objectSchema);
        
        $ldapObjectSchema = new LdapObjectSchema($schemaName, $objectSchema['type']);
        foreach ($this->optionMap as $option => $setter) {
            if (!array_key_exists($option, $objectSchema)) {
                continue;
            }
            $value = $objectSchema[$option];
            if (array_key_exists($option, $this->normalizer)) {
                $value = $this->{$this->normalizer[$option]}($value);
            }
            $ldapObjectSchema->$setter($value);
        }
        $ldapObjectSchema->setFilter($this->parseFilter($ldapObjectSchema, $objectSchema));
        $ldapObjectSchema->setAttributeMap(isset($objectSchema['attributes']) ? $objectSchema['attributes'] : []);
        $ldapObjectSchema->setConverterMap($this->parseConverterMap($objectSchema));
        $ldapObjectSchema->setControls(...$this->parseControls($objectSchema));
        $this->validateObjectSchema($ldapObjectSchema);

        return $ldapObjectSchema;
    }

    /**
     * Validates some of the schema values to check that they are allowed.
     *
     * @param LdapObjectSchema $schema
     * @param array $schemaArray
     * @throws SchemaParserException
     */
    protected function validateSchemaType(LdapObjectSchema $schema, array $schemaArray)
    {
        if (!((bool)count(array_filter(array_keys($schemaArray['attributes']), 'is_string')))) {
            throw new SchemaParserException('The attributes for a schema should be an associative array.');
        }
        if ($schema->getScope() && !in_array($schema->getScope(), QueryOperation::SCOPE)) {
            throw new SchemaParserException(sprintf(
                'The scope "%s" is not valid. Valid types are: %s',
                $schema->getScope(),
                implode(', ', QueryOperation::SCOPE)
            ));
        }
    }

    /**
     * Check for a specific object type in the schema and validate it.
     *
     * @param array $schema
     * @param string $objectType
     * @return array
     * @throws SchemaParserException
     */
    protected function getObjectFromSchema(array $schema, $objectType)
    {
        $objectSchema = null;
        foreach ($schema['objects'] as $ldapObject) {
            if (array_key_exists('type', $ldapObject) && MBString::strtolower($ldapObject['type']) == MBString::strtolower($objectType)) {
                $objectSchema = $ldapObject;
            }
        }
        if (is_null($objectSchema)) {
            throw new SchemaParserException(sprintf('Cannot find object type "%s" in schema.', $objectType));
        }

        return $objectSchema;
    }

    /**
     * Update the object in the schema array in case it extended a different object type.
     *
     * @param string $schemaName
     * @param array $schemaObject
     */
    protected function updateObjectArray($schemaName, $schemaObject)
    {
        foreach ($this->schemas[$this->schemaFolder][$schemaName]['objects'] as $name => $value) {
            if (array_key_exists('type', $value) && $value['type'] == $schemaObject['type']) {
                $this->schemas[$this->schemaFolder][$schemaName]['objects'][$name] = $schemaObject;
            }
        }
    }

    /**
     * Removes certain keys so they don't get parsed again.
     *
     * @param array $schemaObject
     * @return array
     */
    protected function cleanObjectArray(array $schemaObject)
    {
        unset($schemaObject['extends']);
        unset($schemaObject['extends_default']);
        
        return $schemaObject;
    }

    /**
     * Parse the converters section of an object schema definition to generate the attribute converter map.
     *
     * @param array $objectSchema
     * @return array
     */
    protected function parseConverterMap(array $objectSchema)
    {
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

        return $converterMap;
    }

    /**
     * Get the filter for the schema object.
     *
     * @param LdapObjectSchema $objectSchema
     * @param array $objectArray
     * @return \LdapTools\Query\Operator\BaseOperator
     * @throws SchemaParserException
     */
    protected function parseFilter(LdapObjectSchema $objectSchema, array $objectArray)
    {
        $filter = array_key_exists('filter', $objectArray) ? $objectArray['filter'] : [];
        
        if (empty($filter) && empty($objectSchema->getObjectClass()) && empty($objectSchema->getObjectCategory())) {
            throw new SchemaParserException(sprintf(
                'Object type "%s" must have one of the following defined: %s',
                $objectSchema->getObjectType(),
                implode(', ', ['class', 'category', 'filter'])
            ));
        }
        
        return $this->arrayToOp->getOperatorForSchema($objectSchema, $filter);
    }

    /**
     * @param array $objectSchema
     * @return LdapControl[]
     * @throws SchemaParserException
     */
    protected function parseControls($objectSchema)
    {
        $controls = [];

        if (array_key_exists('controls', $objectSchema)) {
            foreach ($objectSchema['controls'] as $control) {
                if (!is_array($control)) {
                    throw new SchemaParserException('The "controls" directive must contain arrays of controls.');
                }
                $controls[] = new LdapControl(...$control);
            }
        }
        
        return $controls;
    }

    /**
     * Validate that an object schema meets the minimum requirements.
     *
     * @param LdapObjectSchema $schema
     * @throws SchemaParserException
     */
    protected function validateObjectSchema($schema)
    {
        if (empty($schema->getAttributeMap())) {
            throw new SchemaParserException(sprintf('Object type "%s" has no attributes defined.', $schema->getObjectType()));
        } elseif (!((bool)count(array_filter(array_keys($schema->getAttributeMap()), 'is_string')))) {
            throw new SchemaParserException('The attributes for a schema should be an associative array.');
        }
        
        if ($schema->getScope() && !in_array($schema->getScope(), QueryOperation::SCOPE)) {
            throw new SchemaParserException(sprintf(
                'The scope "%s" is not valid. Valid types are: %s',
                $schema->getScope(),
                implode(', ', QueryOperation::SCOPE)
            ));
        }
    }

    /**
     * Given a schema name, parse it into the array.
     *
     * @param string $schemaName
     * @throws SchemaParserException
     */
    protected function parseSchemaNameToArray($schemaName)
    {
        if (!isset($this->schemas[$this->schemaFolder][$schemaName])) {
            $file = $this->getSchemaFileName($this->schemaFolder, $schemaName);
            try {
                $this->schemas[$this->schemaFolder][$schemaName] = Yaml::parse(file_get_contents($file));
            } catch (ParseException $e) {
                throw new SchemaParserException(sprintf('Error in configuration file: %s', $e->getMessage()));
            }
            $this->mergeDefaultSchemaFile($schemaName);
            $this->mergeIncludedSchemas($schemaName);
            if (!array_key_exists('objects', $this->schemas[$this->schemaFolder][$schemaName])) {
                throw new SchemaParserException(sprintf(
                    'Cannot find the "objects" section in the schema "%s" in "%s".',
                    $schemaName,
                    $this->schemaFolder
                ));
            }
        }
    }

    /**
     * If the 'include' directive is used, then merge the specified schemas into the current one.
     *
     * @param string $schemaName
     * @throws SchemaParserException
     */
    protected function mergeIncludedSchemas($schemaName)
    {
        $include = ['include' => [], 'include_default' => []];

        foreach (array_keys($include) as $key) {
            if (isset($this->schemas[$this->schemaFolder][$schemaName][$key])) {
                $include[$key] = $this->schemas[$this->schemaFolder][$schemaName][$key];
                $include[$key] = is_array($include[$key]) ? $include[$key] : [$include[$key]];
            }
        }

        foreach ($include['include'] as $schema) {
            $this->parseAll($schema);
            $this->schemas[$this->schemaFolder][$schemaName]['objects'] = array_merge(
                $this->schemas[$this->schemaFolder][$schemaName]['objects'],
                $this->schemas[$this->schemaFolder][$schema]['objects']
            );
        }

        $folder = $this->schemaFolder;
        $this->schemaFolder = $this->defaultSchemaFolder;
        foreach ($include['include_default'] as $schema) {
            $this->parseAll($schema);
            $this->schemas[$folder][$schemaName]['objects'] = array_merge(
                $this->schemas[$folder][$schemaName]['objects'],
                $this->schemas[$this->schemaFolder][$schema]['objects']
            );
        }
        $this->schemaFolder = $folder;
    }

    /**
     * If the 'extends_default' directive is used, then merge the specified default schema.
     *
     * @param string $schemaName
     * @throws SchemaParserException
     */
    protected function mergeDefaultSchemaFile($schemaName)
    {
        if (!isset($this->schemas[$this->schemaFolder][$schemaName]['extends_default'])) {
            return;
        }
        $defaultSchemaName = $this->schemas[$this->schemaFolder][$schemaName]['extends_default'];
        $folder = $this->schemaFolder;

        $this->schemaFolder = $this->defaultSchemaFolder;
        $this->parseAll($defaultSchemaName);
        // Perhaps an option at some point to specify the merge action/type? ie. replace vs merge.
        $this->schemas[$folder][$schemaName] = array_merge_recursive(
            $this->schemas[$this->schemaFolder][$defaultSchemaName],
            $this->schemas[$folder][$schemaName]
        );

        $this->schemaFolder = $folder;
    }

    /**
     * If the 'extends' option is given, then merge this schema object with the requested schema object.
     *
     * @param array $objectSchema
     * @param string $schemaName
     * @return array
     * @throws SchemaParserException
     */
    protected function mergeAnyExtendedSchemas(array $objectSchema, $schemaName)
    {
        if (!(isset($objectSchema['extends']) || isset($objectSchema['extends_default']))) {
            return $objectSchema;
        }

        return $this->mergeSchemaObjectArrays($this->getParentSchemaObject($objectSchema, $schemaName), $objectSchema);
    }

    /**
     * Performs the logic for merging one schema object array with another.
     *
     * @param array $parent The parent schema object being extended.
     * @param array $schema The base schema object being defined.
     * @return array
     */
    protected function mergeSchemaObjectArrays($parent, $schema)
    {
        // Directives used that exist in the schema being extended, that are arrays, should be merged.
        foreach (array_intersect_key($schema, $parent) as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = array_merge_recursive($parent[$key], $value);
            }
        }
        
        // Directives in the parent that have not been defined should be added.
        return array_replace($schema, array_diff_key($parent, $schema));
    }

    /**
     * If we need to retrieve one of the default schemas, then it's probably the case that the schema folder path was
     * manually defined. So retrieve the default schema object by parsing the name from the default folder path and then
     * reset the schema folder back to what it originally was.
     *
     * @param array $objectSchema
     * @return array
     * @throws SchemaParserException
     */
    protected function getExtendedDefaultSchemaObject(array $objectSchema)
    {
        if (!(is_array($objectSchema['extends_default']) && 2 == count($objectSchema['extends_default']))) {
            throw new SchemaParserException('The "extends_default" directive should be an array with exactly 2 values.');
        }
        $folder = $this->schemaFolder;
        $this->schemaFolder = $this->defaultSchemaFolder;

        $this->parseAll(reset($objectSchema['extends_default']));
        $parent = $this->getObjectFromSchema(
            $this->schemas[$this->defaultSchemaFolder][$objectSchema['extends_default'][0]],
            $objectSchema['extends_default'][1]
        );

        $this->schemaFolder = $folder;

        return $parent;
    }

    /**
     * Determines what parent array object to get based on the directive used.
     *
     * @param array $objectSchema
     * @param string $schemaName
     * @return array
     * @throws SchemaParserException
     */
    protected function getParentSchemaObject(array $objectSchema, $schemaName)
    {
        if (isset($objectSchema['extends_default'])) {
            $parent = $this->getExtendedDefaultSchemaObject($objectSchema);
        } elseif (isset($objectSchema['extends']) && is_string($objectSchema['extends'])) {
            $parent = $this->getObjectFromSchema($this->schemas[$this->schemaFolder][$schemaName], $objectSchema['extends']);
        } elseif (isset($objectSchema['extends']) && is_array($objectSchema['extends']) && 2 == count($objectSchema['extends'])) {
            $name = reset($objectSchema['extends']);
            $type = $objectSchema['extends'][1];
            $this->parseAll($name);
            $parent = $this->getObjectFromSchema($this->schemas[$this->schemaFolder][$name], $type);
        } else {
            throw new SchemaParserException('The directive "extends" must be a string or array with exactly 2 values.');
        }

        return $parent;
    }

    /**
     * Check for a YML file of the specified schema name and return the full path.
     *
     * @param string $folder
     * @param string $schema
     * @return string
     * @throws SchemaParserException
     */
    protected function getSchemaFileName($folder, $schema)
    {
        $file = null;

        foreach (['yml', 'yaml'] as $ext) {
            $fileCheck = $folder.'/'.$schema.'.'.$ext;
            if (is_readable($fileCheck)) {
                $file = $fileCheck;
                break;
            }
        }

        if (is_null($file)) {
            throw new SchemaParserException(sprintf('Cannot find schema for "%s" in "%s"', $schema, $folder));
        }

        return $file;
    }

    /**
     * @param mixed $value
     * @return array
     */
    protected function normalizeToArray($value)
    {
        return is_array($value) ? $value : [$value];
    }
}
