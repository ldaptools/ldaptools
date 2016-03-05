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

use LdapTools\Connection\LdapControl;
use LdapTools\Exception\LdifParserException;
use LdapTools\Exception\LdifUrlLoaderException;
use LdapTools\Ldif\Entry\LdifEntryAdd;
use LdapTools\Ldif\Entry\LdifEntryDelete;
use LdapTools\Ldif\Entry\LdifEntryModDn;
use LdapTools\Ldif\Entry\LdifEntryModify;
use LdapTools\Ldif\Entry\LdifEntryModRdn;
use LdapTools\Ldif\Ldif;
use LdapTools\Ldif\Entry\LdifEntryInterface;
use LdapTools\Ldif\UrlLoader\BaseUrlLoader;
use LdapTools\Ldif\UrlLoader\UrlLoaderInterface;
use LdapTools\Utilities\LdapUtilities;

/**
 * Parses a LDIF string to a form that can be easily entered into LDAP with LdapTools.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifParser
{
    /**
     * Valid directives for certain changetypes.
     */
    const VALID_DIRECTIVES = [
        LdifEntryInterface::TYPE_MODIFY => [
            LdifEntryModify::DIRECTIVE_ADD,
            LdifEntryModify::DIRECTIVE_DELETE,
            LdifEntryModify::DIRECTIVE_REPLACE,
        ],
        LdifEntryInterface::TYPE_MODDN => [
            LdifEntryModDn::DIRECTIVE_NEWRDN,
            LdifEntryModDn::DIRECTIVE_DELETEOLDRDN,
            LdifEntryModDn::DIRECTIVE_NEWSUPERIOR,
        ],
        LdifEntryInterface::TYPE_MODRDN => [
            LdifEntryModRdn::DIRECTIVE_NEWRDN,
            LdifEntryModRdn::DIRECTIVE_DELETEOLDRDN,
            LdifEntryModRdn::DIRECTIVE_NEWSUPERIOR,
        ],
    ];

    /**
     * @var array A simple changetype to full class name mapping.
     */
    protected $changeTypeMap = [
        LdifEntryInterface::TYPE_ADD => LdifEntryAdd::class,
        LdifEntryInterface::TYPE_DELETE => LdifEntryDelete::class,
        LdifEntryInterface::TYPE_MODDN => LdifEntryModDn::class,
        LdifEntryInterface::TYPE_MODRDN => LdifEntryModRdn::class,
        LdifEntryInterface::TYPE_MODIFY => LdifEntryModify::class,
    ];

    /**
     * An array of UrlLoaders with the key set to the type of URL they handle.
     *
     * @var UrlLoaderInterface[]
     */
    protected $urlLoaders = [];

    /**
     * @var int The current line number we are on during parsing.
     */
    protected $line = 0;

    /**
     * @var string[]
     */
    protected $lines;

    /**
     * @var string[] Any comments pending for the next entry in the LDIF.
     */
    protected $commentQueue = [];

    public function __construct()
    {
        $this->urlLoaders[UrlLoaderInterface::TYPE_FILE] = new BaseUrlLoader();
        $this->urlLoaders[UrlLoaderInterface::TYPE_HTTP] = new BaseUrlLoader();
        $this->urlLoaders[UrlLoaderInterface::TYPE_HTTPS] = new BaseUrlLoader();
    }

    /**
     * Parses a string containing LDIF data and returns an object with the entries it contains.
     *
     * @param string $ldif
     * @return Ldif
     * @throws LdifParserException
     */
    public function parse($ldif)
    {
        $ldifObject = new Ldif();

        $this->setup($ldif);
        while (!$this->isEndOfLdif()) {
            if ($this->isComment()) {
                $this->addCommentToQueueOrLdif($ldifObject);
                $this->nextLine();
            } elseif ($this->isStartOfEntry()) {
                $ldifObject->addEntry($this->parseEntry());
            } elseif ($this->startsWith(Ldif::DIRECTIVE_VERSION.Ldif::KEY_VALUE_SEPARATOR)) {
                $this->setLdifVersion($ldifObject, $this->getKeyAndValue($this->currentLine())[1]);
                $this->nextLine();
            } elseif ($this->isEndOfEntry()) {
                $this->nextLine();
            } else {
                $this->throwException('Unexpected line in LDIF');
            }
        }
        $this->cleanup();

        return $ldifObject;
    }

    /**
     * Set a URL loader to be used by the parser.
     *
     * @param string $type The URL type (ie. file, http, etc)
     * @param UrlLoaderInterface $loader
     */
    public function setUrlLoader($type, UrlLoaderInterface $loader)
    {
        $this->urlLoaders[$type] = $loader;
    }

    /**
     * Check if a URL loader for a specific URL type exists.
     *
     * @param string $type
     * @return bool
     */
    public function hasUrlLoader($type)
    {
        return array_key_exists($type, $this->urlLoaders);
    }

    /**
     * Remove a URL loader by its string type.
     *
     * @param string $type
     */
    public function removeUrlLoader($type)
    {
        unset($this->urlLoaders[$type]);
    }

    /**
     * @param string $ldif
     */
    protected function setup($ldif)
    {
        $this->line = 0;
        // This accounts for various line endings across different OS types and forces it to one type.
        $this->lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $ldif));
    }

    /**
     * Do a bit of cleanup post parsing.
     */
    protected function cleanup()
    {
        $this->lines = null;
        $this->line = 0;
    }

    /**
     * Parse an entry from the DN position until we reach the start of the next entry. Return the entry that was parsed.
     *
     * @return LdifEntryInterface
     */
    protected function parseEntry()
    {
        $entry = $this->parseCommonDirectives($this->getKeyAndValue($this->currentLine())[1]);

        if (!empty($this->commentQueue)) {
            $entry->addComment(...$this->commentQueue);
            $this->commentQueue = [];
        }

        // Nothing further to do with a simple deletion...
        if ($entry instanceof LdifEntryDelete) {
            return $entry;
        }

        while (!$this->isEndOfLdif() && !$this->isStartOfEntry()) {
            if ($this->isComment()) {
                $entry->addComment(substr($this->currentLine(), 1));
                $this->nextLine();
            } elseif ($this->isEndOfEntry()) {
                break;
            } else {
                list($key, $value) = $this->getKeyAndValue($this->currentLine());
                $this->addDirectiveToEntry($key, $value, $entry);
                $this->nextLine();
            }
        }

        return $entry;
    }

    /**
     * Parses directives that are potentially common to all entries and returns the LdifEntry object. Common directives
     * include: changetype, control
     *
     * @param $dn
     * @return LdifEntryInterface
     * @throws LdifParserException
     */
    protected function parseCommonDirectives($dn)
    {
        $changeType = null;
        $controls = [];

        $this->nextLine();
        while ($this->isCommonDirective()) {
            if ($this->isComment()) {
                $this->nextLine();
                continue;
            // We need to exit the loop completely in this case...
            } elseif ($this->isEndOfLdif() || $this->isStartOfEntry()) {
                break;
            } elseif ($this->isCommonDirective()) {
                list($key, $value) = $this->getKeyAndValue($this->currentLine());

                if ($key == Ldif::DIRECTIVE_CHANGETYPE && is_null($changeType)) {
                    $changeType = $value;
                } elseif ($key == Ldif::DIRECTIVE_CHANGETYPE && !is_null($changeType)) {
                    $this->throwException('The changetype directive has already been defined');
                } else {
                    $controls[] = $this->getLdapControl($value);
                }
            }
            $this->nextLine();
        }
        $changeType = $changeType ?: LdifEntryInterface::TYPE_ADD;
        $entry = $this->getLdifEntryObject($dn, $changeType);

        foreach ($controls as $control) {
            $entry->addControl($control);
        }

        return $entry;
    }

    /**
     * Get the LdifEntry for the changetype.
     *
     * @param string $dn
     * @param string $changeType
     * @return LdifEntryInterface
     * @throws LdifParserException
     */
    protected function getLdifEntryObject($dn, $changeType)
    {
        if (!array_key_exists($changeType, $this->changeTypeMap)) {
            $this->throwException(sprintf('The changetype "%s" is invalid', $changeType));
        }

        return new $this->changeTypeMap[$changeType]($dn);
    }

    /**
     * @param bool $advance Whether to advance the currently active line ahead or not.
     * @return string|false
     */
    protected function nextLine($advance = true)
    {
        $line = $this->line + 1;
        if ($advance) {
            $this->line++;
        }
        if (!isset($this->lines[$line])) {
            return false;
        }

        return $this->lines[$line];
    }

    /**
     * @return bool|string
     */
    protected function currentLine()
    {
        if (!isset($this->lines[$this->line])) {
            return false;
        }

        return $this->lines[$this->line];
    }

    /**
     * Check if the current line starts with a specific value.
     *
     * @param string $value
     * @param null|string $line
     * @return bool
     */
    protected function startsWith($value, $line = null)
    {
        if (is_null($line)) {
            $line = $this->currentLine();
        }

        return (substr($line, 0, strlen($value)) === $value);
    }

    /**
     * Checks for the start of a LDIF entry on the current line.
     *
     * @return bool
     */
    protected function isStartOfEntry()
    {
        return $this->startsWith(Ldif::DIRECTIVE_DN.Ldif::KEY_VALUE_SEPARATOR);
    }

    /**
     * Check if we are at the end of the LDIF string.
     *
     * @return bool
     */
    protected function isEndOfLdif()
    {
        return $this->currentLine() === false;
    }

    /**
     * Check if we are at the end of a LDIF entry.
     *
     * @return bool
     */
    protected function isEndOfEntry()
    {
        return $this->currentLine() === '';
    }

    /**
     * Check if the current line is a comment.
     *
     * @return bool
     */
    protected function isComment()
    {
        return $this->startsWith(Ldif::COMMENT);
    }

    /**
     * Check if the line is a directive common to any change type (ie. changetype or control).
     *
     * @return bool
     */
    protected function isCommonDirective()
    {
        return $this->startsWith(Ldif::DIRECTIVE_CONTROL) || $this->startsWith(Ldif::DIRECTIVE_CHANGETYPE);
    }

    /**
     * Check if a line is a continuation of a previous value.
     *
     * @param string $line
     * @return bool
     */
    protected function isContinuedValue($line)
    {
        return (!empty($line) && $line[0] === " ");
    }

    /**
     * @param string $line
     * @return array
     * @throws LdifParserException
     */
    protected function getKeyAndValue($line)
    {
        $position = strpos($line, Ldif::KEY_VALUE_SEPARATOR);

        // There must be a key/value separator ':' present in the line, and it cannot be the first value
        if ($position === false || $position === 0) {
            $this->throwException('Expecting a LDIF directive');
        }

        // This accounts for double '::' base64 format.
        $key = rtrim(substr($line, 0, $position), ':');

        // Base64 encoded format...
        if ($this->startsWith(Ldif::KEY_VALUE_SEPARATOR, substr($line, $position + 1))) {
            $value = base64_decode($this->getContinuedValues(ltrim(substr($line, $position + 2), ' ')));
        // The value needs to be retrieved from a URL...
        } elseif ($this->startsWith(Ldif::URL, substr($line, $position + 1))) {
            // Start the position after the URL indicator and remove any spaces.
            $value = $this->getValueFromUrl($this->getContinuedValues(ltrim(substr($line, $position + 2), ' ')));
        // Just a typical value format...
        } else {
            // A space at the start of the value should be ignored. A value beginning with a space should be base64 encoded.
            $value = $this->getContinuedValues(ltrim(substr($line, $position + 1), " "));
        }

        return [$key, $value];
    }

    /**
     * Check for any continued values and concatenate them into one.
     *
     * @param $value
     * @return string
     */
    protected function getContinuedValues($value)
    {
        while ($this->isContinuedValue($this->nextLine(false))) {
            $value .= substr($this->nextLine(), 1);
        }

        return $value;
    }

    /**
     * Get the value of the URL data via a UrlLoader.
     *
     * @param string $url
     * @return string
     */
    protected function getValueFromUrl($url)
    {
        $type = substr($url, 0, strpos($url, Ldif::KEY_VALUE_SEPARATOR));

        if (!$this->hasUrlLoader($type)) {
            $this->throwException(sprintf('Cannot find a URL loader for type "%s"', $type));
        }

        try {
            return $this->urlLoaders[$type]->load($url);
        } catch (LdifUrlLoaderException $e) {
            $this->throwException($e->getMessage());
        }
    }

    /**
     * Figures out what to add to the LDIF entry for a specific key/value directive given.
     *
     * @param string $key
     * @param string $value
     * @param LdifEntryInterface $entry
     */
    protected function addDirectiveToEntry($key, $value, LdifEntryInterface $entry)
    {
        if ($entry instanceof LdifEntryAdd) {
            $entry->addAttribute($key, $value);
        } elseif ($entry instanceof LdifEntryModDn) {
            $this->addModDnDirective($entry, $key, $value);
        } elseif ($entry instanceof LdifEntryModify) {
            $this->addModifyDirective($entry, $key, $value);
        }
    }

    /**
     * @param LdifEntryModDn $entry
     * @param string $key
     * @param string $value
     * @throws LdifParserException
     */
    protected function addModDnDirective(LdifEntryModDn $entry, $key, $value)
    {
        $this->validateDirectiveInChange(LdifEntryInterface::TYPE_MODDN, $key);

        if ($key == LdifEntryModDn::DIRECTIVE_DELETEOLDRDN) {
            $entry->setDeleteOldRdn($this->getBoolFromStringInt($value));
        } elseif ($key == LdifEntryModDn::DIRECTIVE_NEWRDN) {
            $entry->setNewRdn($value);
        } elseif ($key == LdifEntryModDn::DIRECTIVE_NEWSUPERIOR) {
            $entry->setNewLocation($value);
        }
    }

    /**
     * @param LdifEntryModify $entry
     * @param string $key
     * @param string $value
     * @throws LdifParserException
     */
    protected function addModifyDirective(LdifEntryModify $entry, $key, $value)
    {
        $this->validateDirectiveInChange(LdifEntryInterface::TYPE_MODIFY, $key);

        $this->nextLine();
        if ($key == LdifEntryModify::DIRECTIVE_ADD) {
            $values = $this->getValuesForModifyAction($value, 'adding');
            $entry->add($value, $values);
        } elseif ($key == LdifEntryModify::DIRECTIVE_DELETE) {
            $values = $this->getValuesForModifyAction($value, 'deleting');
            if (empty($values)) {
                $entry->reset($value);
            } else {
                $entry->delete($value, $values);
            }
        } elseif ($key == LdifEntryModify::DIRECTIVE_REPLACE) {
            $values = $this->getValuesForModifyAction($value, 'replacing');
            $entry->replace($value, $values);
        }
    }

    /**
     * Validate a control directive and get the value for the control and the criticality.
     *
     * @param string $value
     * @return LdapControl
     * @throws LdifParserException
     */
    protected function getLdapControl($value)
    {
        $values = explode(' ', $value);

        // This should never happen, but it seems better to cover it in case...
        if (empty($values) || $values === false) {
            $this->throwException(sprintf('Expecting a LDAP control but got "%s"', $value));
        }
        // The first value should be an actual OID...
        if (!preg_match(LdapUtilities::MATCH_OID, $values[0])) {
            $this->throwException(sprintf('The control directive has an invalid OID format "%s"', $values[0]));
        }

        $control = new LdapControl($values[0]);
        if (isset($values[1])) {
            $control->setCriticality($this->getBoolFromStringBool($values[1]));
        }
        if (isset($values[2])) {
            $control->setValue($values[2]);
        }

        return $control;
    }

    /**
     * @param Ldif $ldif
     * @param int $version
     * @throws LdifParserException
     */
    protected function setLdifVersion(Ldif $ldif, $version)
    {
        if ($version != '1') {
            $this->throwException(sprintf('LDIF version "%s" is not currently supported.', $version));
        } elseif (count($ldif->getEntries()) !== 0) {
            $this->throwException('The LDIF version must be defined before any entries.');
        }

        $ldif->setVersion($version);
    }

    /**
     * @param string $type The changetype.
     * @param string $directive The directive used.
     * @throws LdifParserException If the directive is not valid for the changetype.
     */
    protected function validateDirectiveInChange($type, $directive)
    {
        if (!in_array($directive, self::VALID_DIRECTIVES[$type])) {
            $this->throwException(sprintf(
                'Directive "%s" is not valid for a "%s" changetype',
                $directive,
                $type
            ));
        }
    }

    /**
     * @param string $attribute
     * @param string $action
     * @return array
     * @throws LdifParserException
     */
    protected function getValuesForModifyAction($attribute, $action)
    {
        $values = [];

        while ($this->currentLine() !== LdifEntryModify::SEPARATOR && !$this->isEndOfLdif() && !$this->isEndOfEntry()) {
            if ($this->isComment()) {
                $this->nextLine();
                continue;
            }
            list($attrKey, $attrValue) = $this->getKeyAndValue($this->currentLine());

            if ($attribute !== $attrKey) {
                $this->throwException(sprintf(
                    'Attribute "%s" does not match "%s" for %s values.',
                    $attrValue,
                    $attribute,
                    $action
                ));
            }
            $values[] = $attrValue;
            $this->nextLine();
        }

        return $values;
    }

    /**
     * Convert an expected string "true" or "false" to bool.
     *
     * @param string $value
     * @return bool
     * @throws LdifParserException
     */
    protected function getBoolFromStringBool($value)
    {
        if (!($value == 'true' || $value == 'false')) {
            $this->throwException(sprintf('Expected "true" or "false" but got %s', $value));
        }

        return $value === 'true' ? true : false;
    }

    /**
     * Convert an expected string "0" or "1" to bool.
     *
     * @param string $value
     * @return bool
     * @throws LdifParserException
     */
    protected function getBoolFromStringInt($value)
    {
        if (!($value == '0' || $value == '1')) {
            $this->throwException(sprintf('Expected "0" or "1" but got: %s', $value));
        }

        return (bool) $value;
    }

    /**
     * A simple helper to add additional information to the exception.
     *
     * @param string $message
     * @throws LdifParserException
     */
    protected function throwException($message)
    {
        throw new LdifParserException($message, $this->currentLine(), $this->line + 1);
    }

    /**
     * Determine whether the comment should be added to the LDIF itself, or if it's a comment for an entry within the
     * LDIF. If it's for an entry in the LDIF, then we make the assumption that we an empty line separates comments
     * between the LDIF comments overall and the start of a comment for an entry. This seems like the most reasonable
     * way to do it, though it still may not be perfect.
     *
     * @param \LdapTools\Ldif\Ldif $ldif
     */
    protected function addCommentToQueueOrLdif(Ldif $ldif)
    {
        $comment = $this->getContinuedValues(substr($this->currentLine(), 1));

        // Remove the single space from the start of a comment, but leave the others intact.
        if ($this->startsWith(' ', $comment)) {
            $comment = substr($comment, 1);
        }

        // If we already have an entry added to LDIF, then the comment should go in the queue for the next entry.
        if (count($ldif->getEntries()) > 0) {
            $this->commentQueue[] = $comment;
        // Check the section of the LDIF to look for an empty line. If so, assume it's for a future entry.
        } elseif (array_search('', array_slice($this->lines, 0, $this->line))) {
            $this->commentQueue[] = $comment;
        // No empty lines and we have not reached an entry yet, so this should be a comment for the LDIF overall.
        } else {
            $ldif->addComment($comment);
        }
    }
}
