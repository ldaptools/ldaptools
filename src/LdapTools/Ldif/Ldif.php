<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif;

use LdapTools\Connection\LdapAwareInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Ldif\Entry\LdifEntryInterface;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Schema\SchemaAwareInterface;

/**
 * Represents a LDIF file with a set of entries.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Ldif
{
    use LdifStringBuilderTrait;

    /**
     * The DN directive expected for all entries.
     */
    const DIRECTIVE_DN = 'dn';

    /**
     * The directive to specify a LDAP control.
     */
    const DIRECTIVE_CONTROL = 'control';

    /**
     * The directive to specify the changetype for an entry.
     */
    const DIRECTIVE_CHANGETYPE = 'changetype';

    /**
     * The directive indicating the LDIF version.
     */
    const DIRECTIVE_VERSION = 'version';

    /**
     * Represents the start of a comment.
     */
    const COMMENT = '#';

    /**
     * Indicates the start of a URL to get data from.
     */
    const URL = '<';

    /**
     * Possible line endings to use.
     */
    const LINE_ENDING = [
        'WINDOWS' => "\r\n",
        'UNIX' => "\n",
    ];

    /**
     * Represents the separator for the tokens of LDIF data.
     */
    const ENTRY_SEPARATOR = "\r\n";

    /**
     * The separator for a key/value pair in a LDIF entry.
     */
    const KEY_VALUE_SEPARATOR = ':';

    /**
     * @var int The LDIF version for the entries.
     */
    protected $version = 1;

    /**
     * @var LdifEntryInterface[]
     */
    protected $entries = [];

    /**
     * @var LdifEntryBuilder
     */
    protected $entryBuilder;

    /**
     * @var LdapObjectSchemaFactory|null
     */
    protected $schemaFactory;

    /**
     * @var LdapConnectionInterface|null
     */
    protected $connection;

    /**
     * @var OperationHydrator
     */
    protected $hydrator;

    /**
     * @param LdapConnectionInterface|null $connection
     * @param LdapObjectSchemaFactory|null $schemaFactory
     */
    public function __construct(LdapConnectionInterface $connection = null, LdapObjectSchemaFactory $schemaFactory = null)
    {
        $this->schemaFactory = $schemaFactory;
        $this->connection = $connection;
        $this->hydrator = new OperationHydrator($connection);
        $this->entryBuilder = new LdifEntryBuilder();
    }

    /**
     * Set the LDIF version.
     *
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the LDIF version.
     *
     * @return int|null
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Used for more fluid addition of entries to the LDIF object.
     *
     * @return LdifEntryBuilder
     */
    public function entry()
    {
        return $this->entryBuilder;
    }

    /**
     * Add one or more LDIF entry objects.
     *
     * @param LdifEntryInterface[] ...$entries
     * @return $this
     */
    public function addEntry(LdifEntryInterface ...$entries)
    {
        foreach ($entries as $entry) {
            $this->entries[] = $entry;
        }

        return $this;
    }

    /**
     * Get the entries represented by this LDIF object.
     *
     * @return LdifEntryInterface[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * Get the string representation of the LDIF object with all of the entries it has.
     *
     * @return string
     */
    public function toString()
    {
        $ldif = $this->addCommentsToString('');
        if (!is_null($this->version)) {
            $ldif .= $this->getLdifLine(self::DIRECTIVE_VERSION, $this->version);
        }
        foreach ($this->entries as $entry) {
            $this->setupEntry($entry);
            $ldif .= $this->lineEnding.$entry->toString();
        }

        return $ldif;
    }

    /**
     * Get all of the operations represented by all of the entries for this LDIF object.
     *
     * @return LdapOperationInterface[]
     */
    public function toOperations()
    {
        $operations = [];

        foreach ($this->entries as $entry) {
            $this->setupEntry($entry);
            $operations[] = $entry->toOperation();
        }

        return $operations;
    }

    /**
     * @param LdifEntryInterface $entry
     */
    protected function setupEntry(LdifEntryInterface $entry)
    {
        if (!is_null($entry->getType()) && $entry instanceof SchemaAwareInterface) {
            $entry->setLdapObjectSchema($this->getSchemaForType($entry->getType()));
        }
        if ($entry instanceof LdapAwareInterface) {
            $entry->setLdapConnection($this->connection);
        }
        $entry->setLineEnding($this->lineEnding);
        $entry->setLineFolding($this->lineFolding);
        $entry->setMaxLineLength($this->maxLineLength);
    }

    /**
     * @param string $type
     * @return \LdapTools\Schema\LdapObjectSchema
     */
    protected function getSchemaForType($type)
    {
        if (!$this->schemaFactory || !$this->connection) {
            throw new InvalidArgumentException('If you set a schema type for a LDIF entry you must use a SchemaFactory and LdapConnection in the LDIF constructor.');
        }

        return $this->schemaFactory->get($this->connection->getConfig()->getSchemaName(), $type);
    }
}
